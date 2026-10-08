"""Check every home brand tile against the asset manifest and capture the UI."""

from base64 import b64decode
from pathlib import Path
from urllib.request import urlopen
from websockets.sync.client import connect
from visual_qa import ChromePage, CHROME, OUTPUT
import json
import socket
import subprocess
import tempfile
import time


def main():
    OUTPUT.mkdir(exist_ok=True)
    with tempfile.TemporaryDirectory(prefix="fixnear-brands-") as profile:
        with socket.socket() as sock:
            sock.bind(("127.0.0.1", 0))
            port = sock.getsockname()[1]
        process = subprocess.Popen([
            str(CHROME), "--headless=new", "--disable-gpu", "--no-first-run",
            "--remote-allow-origins=*", f"--remote-debugging-port={port}",
            f"--user-data-dir={profile}", "about:blank",
        ], stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL,
           creationflags=getattr(subprocess, "CREATE_NO_WINDOW", 0))
        try:
            for _ in range(80):
                try:
                    target = next(x for x in json.load(urlopen(f"http://127.0.0.1:{port}/json", timeout=1)) if x.get("type") == "page")
                    break
                except (OSError, StopIteration):
                    time.sleep(0.1)
            else:
                raise RuntimeError("Chrome did not start")
            with connect(target["webSocketDebuggerUrl"], origin="http://localhost", max_size=20_000_000) as ws:
                page = ChromePage(ws)
                page.call("Page.enable")
                page.call("Runtime.enable")
                page.call("Emulation.setDeviceMetricsOverride", {"width": 1440, "height": 900, "deviceScaleFactor": 1, "mobile": False})
                page.call("Page.navigate", {"url": "http://127.0.0.1:8000/index.php"})
                time.sleep(1.3)
                devices = page.evaluate("Object.keys(FIXNEAR_BRANDS)")
                for device in devices:
                    result = page.evaluate(f"(async()=>{{selectDeviceType({json.dumps(device)});const grid=document.getElementById('fn-brands-grid-container');const cards=[...grid.querySelectorAll('.fn-brand-card:not(.fn-brand-card-custom)')]; await Promise.all(cards.map(c=>{{const i=c.querySelector('img');if(!i)return Promise.resolve();i.loading='eager';return i.complete?Promise.resolve():new Promise(r=>{{i.onload=r;i.onerror=r;setTimeout(r,10000)}})}})); window.scrollTo(0,window.scrollY+grid.getBoundingClientRect().top-250);return {{count:cards.length,missing:cards.filter(c=>!c.querySelector('img')&&!['gaming_pc','graphic_pc'].includes(c.dataset.brandId)).map(c=>c.innerText.trim()),wrong:cards.filter(c=>{{const id=FIXNEAR_GROUP_LOGOS[c.dataset.brandId]||c.dataset.brandId;const f=window.FIXNEAR_BRAND_ASSETS[id];const i=c.querySelector('img');return f&&(!i||!i.src.endsWith('/'+f))}}).map(c=>c.innerText.trim()),broken:cards.filter(c=>{{const i=c.querySelector('img');return i&&(!i.complete||!i.naturalWidth)}}).map(c=>c.innerText.trim()),overflow:document.documentElement.scrollWidth>innerWidth+2}}}})()")
                    assert not result["missing"] and not result["wrong"] and not result["broken"] and not result["overflow"], (device, result)
                    shot = page.call("Page.captureScreenshot", {"format": "png", "captureBeyondViewport": False})
                    (OUTPUT / f"qa-brands-{device}.png").write_bytes(b64decode(shot["data"]))
                    print(device, result)
                page.call("Page.navigate", {"url": "http://127.0.0.1:8000/request_repair.php"})
                time.sleep(1.3)
                wizard_devices = page.evaluate("Object.keys(WIZARD_DATA)")
                for device in wizard_devices:
                    script = f"""(async()=>{{
                        const device={json.dumps(device)};
                        selectedDeviceKey=device;
                        const response=await fetch('api/get_catalog.php?action=brands&device='+encodeURIComponent(device));
                        catalogBrandsByDevice[device]=(await response.json()).data;
                        renderStep2Brands();
                        const brands=catalogBrandsByDevice[device];
                        const cards=[...document.querySelectorAll('#fn-brand-grid-container .fn-rb-brand-card')];
                        await Promise.all(cards.map(c=>{{const i=c.querySelector('img');if(!i)return Promise.resolve();i.loading='eager';return i.complete?Promise.resolve():new Promise(r=>{{i.onload=r;i.onerror=r;setTimeout(r,10000)}})}}));
                        return {{count:cards.length,models:brands.reduce((sum,b)=>sum+b.modelCount,0),
                            missing:cards.flatMap((c,index)=>c.querySelector('img')?[]:[brands[index].id]),
                            wrong:cards.filter((c,index)=>{{const id=REPAIR_WIZARD_LOGO_ALIASES[brands[index].id]||brands[index].id;const f=window.FIXNEAR_BRAND_ASSETS[id];const i=c.querySelector('img');return f&&(!i||!i.src.endsWith('/'+f))}}).map(c=>c.innerText.trim()),
                            broken:cards.filter(c=>{{const i=c.querySelector('img');return i&&(!i.complete||!i.naturalWidth)}}).map(c=>c.innerText.trim())}};
                    }})()"""
                    result = page.evaluate(script)
                    print("request", device, result)
                    generic = {"others", "gaming", "workstation", "office", "oem_brand"}
                    assert set(result["missing"]) <= generic and not result["wrong"] and not result["broken"], (device, result)
                    wizard = page.evaluate(f"""(async()=>{{
                        const device={json.dumps(device)};
                        document.querySelector('.fn-rb-device-card[data-device-key="'+device+'"]').click();
                        document.querySelector('#fn-brand-grid-container .fn-rb-brand-card').click();
                        for(let i=0;i<40&&!document.querySelector('#fn-model-grid-container .fn-rb-model-card');i++) await new Promise(r=>setTimeout(r,50));
                        const models=document.querySelectorAll('#fn-model-grid-container .fn-rb-model-card');
                        if(!models.length)return {{models:0,device:selectedDeviceKey,brand:selectedBrandId,subtitle:document.getElementById('step-3-subtitle').textContent,grid:document.getElementById('fn-model-grid-container').textContent.slice(0,120)}};
                        models[0].click();
                        const issues=document.querySelectorAll('#fn-repair-grid-container .fn-rb-repair-card');
                        if(issues.length)issues[0].click();
                        await new Promise(r=>setTimeout(r,350));
                        return {{models:models.length,issues:issues.length,finalStepVisible:getComputedStyle(document.getElementById('wizard-step-5')).display!=='none',requiredContact:[...document.querySelectorAll('#customer-phone,#customer-email,#customer-zalo')].every(e=>e.required)}};
                    }})()""")
                    assert wizard.get("models", 0) > 0 and wizard["issues"] > 0 and wizard["finalStepVisible"] and wizard["requiredContact"], (device, wizard)
                    print("wizard", device, wizard)
        finally:
            process.terminate()
            try:
                process.wait(timeout=5)
            except subprocess.TimeoutExpired:
                process.kill()
                process.wait(timeout=5)


if __name__ == "__main__":
    main()
