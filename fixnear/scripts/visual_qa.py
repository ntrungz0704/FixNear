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
                        ("shops", "shops.php"), ("shop-detail", "shop_detail.php?id=1"),
                        ("map", "map.php"), ("request", "request_repair.php"),
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
