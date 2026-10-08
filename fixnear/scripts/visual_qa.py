"""Capture responsive screenshots and validate the browser price-filter controls."""

from base64 import b64decode
from pathlib import Path
from urllib.request import urlopen
from websockets.sync.client import connect
import json
import socket
import subprocess
import tempfile
import time


ROOT = Path(__file__).resolve().parents[1]
OUTPUT = ROOT / "outputs"
CHROME = Path(r"C:\Program Files\Google\Chrome\Application\chrome.exe")


class ChromePage:
    def __init__(self, websocket):
        self.websocket = websocket
        self.next_id = 0

    def call(self, method, params=None):
        self.next_id += 1
        message_id = self.next_id
        self.websocket.send(json.dumps({"id": message_id, "method": method, "params": params or {}}))
        while True:
            message = json.loads(self.websocket.recv())
            if message.get("id") == message_id:
                if "error" in message:
                    raise RuntimeError(message["error"])
                return message.get("result", {})

    def evaluate(self, expression):
        result = self.call("Runtime.evaluate", {"expression": expression, "returnByValue": True, "awaitPromise": True})
        if "exceptionDetails" in result:
            raise RuntimeError(result["exceptionDetails"])
        return result["result"].get("value")


def main():
    OUTPUT.mkdir(exist_ok=True)
    with tempfile.TemporaryDirectory(prefix="fixnear-chrome-") as profile:
        with socket.socket() as sock:
            sock.bind(("127.0.0.1", 0))
            port = sock.getsockname()[1]
        process = subprocess.Popen([
            str(CHROME), "--headless=new", "--disable-gpu", "--no-first-run", "--no-default-browser-check",
            "--remote-allow-origins=*", f"--remote-debugging-port={port}", f"--user-data-dir={profile}",
            "about:blank",
        ], stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL, creationflags=getattr(subprocess, "CREATE_NO_WINDOW", 0))
        try:
            for _ in range(80):
                try:
                    targets = json.load(urlopen(f"http://127.0.0.1:{port}/json", timeout=1))
                    target = next(item for item in targets if item.get("type") == "page")
                    break
                except (OSError, StopIteration):
                    time.sleep(0.1)
            else:
                raise RuntimeError("Chrome DevTools endpoint did not start")
            with connect(target["webSocketDebuggerUrl"], origin="http://localhost", max_size=20_000_000) as ws:
                page = ChromePage(ws)
                page.call("Page.enable")
                page.call("Runtime.enable")
                for label, width, height in (("desktop", 1440, 900), ("tablet", 768, 1024), ("mobile", 390, 844)):
                    page.call("Emulation.setDeviceMetricsOverride", {"width": width, "height": height, "deviceScaleFactor": 1, "mobile": width < 600})
                    routes = (
                        ("prices", "prices.php"), ("home", "index.php"), ("models", "models.php"),
                        ("model-detail", "model_detail.php?id=apple-iphone-13"),
                        ("model-detail-tablet", "model_detail.php?id=apple-ipad-air-11-m2-2024"),
                        ("shops", "shops.php"), ("shop-detail", "shop_detail.php?id=1"),
                        ("map", "map.php"), ("search", "search.php"),
                        ("request", "request_repair.php"), ("contact", "contact.php"),
                        ("login", "login.php"), ("register", "register.php"),
                        ("tracking", "track_request.php"),
                    )
                    for slug, route in routes:
                        page.call("Page.navigate", {"url": f"http://127.0.0.1:8000/{route}"})
                        time.sleep(1.1)
                        metrics = page.evaluate("({width:innerWidth,scroll:document.documentElement.scrollWidth})")
                        assert metrics["width"] == width, (route, metrics)
                        assert metrics["scroll"] <= width + 2, (route, metrics)
                        screenshot = page.call("Page.captureScreenshot", {"format": "png", "captureBeyondViewport": False})
                        (OUTPUT / f"qa-{slug}-{label}.png").write_bytes(b64decode(screenshot["data"]))
                        if slug == "home":
                            footer_height = page.evaluate("Math.round(document.querySelector('footer.fn-footer').getBoundingClientRect().height)")
                            assert footer_height <= 330, (label, footer_height)
                            page.evaluate("document.querySelector('footer.fn-footer').scrollIntoView({block:'end'})")
                            screenshot = page.call("Page.captureScreenshot", {"format": "png", "captureBeyondViewport": False})
                            (OUTPUT / f"qa-footer-{label}.png").write_bytes(b64decode(screenshot["data"]))
                        if slug.startswith("model-detail"):
                            page.evaluate("document.getElementById('bang-gia').scrollIntoView({block:'start'})")
                            screenshot = page.call("Page.captureScreenshot", {"format": "png", "captureBeyondViewport": False})
                            (OUTPUT / f"qa-{slug}-price-list-{label}.png").write_bytes(b64decode(screenshot["data"]))
                            shop_layout = page.evaluate("""[...document.querySelectorAll('.fn-model-shop-card')].map(card => {
                                const title = card.querySelector('h3').getBoundingClientRect();
                                const facts = card.querySelector('.fn-model-shop-facts').getBoundingClientRect();
                                const actions = card.querySelector('.fn-model-shop-actions').getBoundingClientRect();
                                const bounds = card.getBoundingClientRect();
                                return {padding: Math.round(title.left - bounds.left), gap: Math.round(facts.top - title.bottom), actionGap: Math.round(actions.top - facts.bottom)};
                            })""")
                            assert len(shop_layout) == 3 and all(item["padding"] >= 14 and item["gap"] >= 0 and item["actionGap"] >= 0 for item in shop_layout), (label, shop_layout)
                            page.evaluate("document.querySelector('.fn-model-shops').scrollIntoView({block:'start'})")
                            page.evaluate("Promise.all([...document.querySelectorAll('.fn-model-shop-media img')].map(img => img.decode().catch(() => null)))")
                            screenshot = page.call("Page.captureScreenshot", {"format": "png", "captureBeyondViewport": False})
                            (OUTPUT / f"qa-{slug}-shops-{label}.png").write_bytes(b64decode(screenshot["data"]))
                        if slug == "shops" and label == "desktop":
                            action_tops = page.evaluate("[...document.querySelectorAll('.fn-shop-list .fn-shop-card')].slice(0,3).map(card=>Math.round(card.querySelector('a[href^=\"tel:\"]')?.getBoundingClientRect().top ?? -1))")
                            assert len(action_tops) == 3 and min(action_tops) > 0 and max(action_tops) - min(action_tops) <= 2, action_tops
                        if slug == "request":
                            device_count = page.evaluate("document.querySelectorAll('.fn-rb-device-card').length")
                            assert device_count == 6, device_count
                            brand_count = page.evaluate("(() => {document.querySelectorAll('.fn-rb-device-card')[0].click(); return document.querySelectorAll('.fn-rb-brand-card').length})()")
                            assert brand_count >= 8, brand_count
                            time.sleep(.35)
                            screenshot = page.call("Page.captureScreenshot", {"format": "png", "captureBeyondViewport": False})
                            (OUTPUT / f"qa-request-brands-{label}.png").write_bytes(b64decode(screenshot["data"]))
                        if slug == "prices":
                            controls = page.evaluate("({brandDisabled:document.getElementById('price-brand').disabled,modelDisabled:document.getElementById('price-model').disabled,modelOptions:document.getElementById('price-model').options.length})")
                            assert controls["brandDisabled"] and controls["modelDisabled"] and controls["modelOptions"] == 1, controls
                            if label == "desktop":
                                result = page.evaluate("(() => {const d=document.getElementById('price-device'); d.value='phone'; d.dispatchEvent(new Event('change')); const b=document.getElementById('price-brand'); b.value='apple'; b.dispatchEvent(new Event('change')); const m=document.getElementById('price-model'); return {brands:b.options.length,models:m.options.length,allApple:[...m.options].every(o=>!o.value || o.value.startsWith('apple-'))};})()")
                                assert result["brands"] > 2 and result["models"] == 36 and result["allApple"], result
                            page.evaluate("window.scrollTo(0, document.querySelector('.fn-price-table').getBoundingClientRect().top + window.scrollY - 100)")
                            screenshot = page.call("Page.captureScreenshot", {"format": "png", "captureBeyondViewport": False})
                            (OUTPUT / f"qa-price-table-{label}.png").write_bytes(b64decode(screenshot["data"]))
                        print(f"{label} {route}: {metrics}")
        finally:
            process.terminate()
            try:
                process.wait(timeout=5)
            except subprocess.TimeoutExpired:
                process.kill()
                process.wait(timeout=5)


if __name__ == "__main__":
    main()
