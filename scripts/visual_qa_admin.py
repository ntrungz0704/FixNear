"""Exercise every admin page at desktop, tablet, and phone widths in an isolated login."""

from base64 import b64decode
from http.cookiejar import CookieJar
from pathlib import Path
from urllib.request import HTTPCookieProcessor, build_opener, urlopen
from websockets.sync.client import connect
import json
import os
import shutil
import socket
import subprocess
import tempfile
import time

from test_user_flows import csrf, request
from visual_qa import CHROME, ChromePage, OUTPUT, ROOT


def free_port():
    with socket.socket() as sock:
        sock.bind(("127.0.0.1", 0))
        return sock.getsockname()[1]


def main():
    OUTPUT.mkdir(exist_ok=True)
    with tempfile.TemporaryDirectory(prefix="fixnear-admin-qa-") as temporary:
        data_dir = Path(temporary) / "data"
        data_dir.mkdir()
        for filename in (
            "shops.json", "services.json", "shop_services.json", "reviews.json",
            "reports.json", "repair_requests.json", "contact_messages.json", "favorites.json",
        ):
            shutil.copy2(ROOT / "data" / filename, data_dir / filename)
        password = "Test-admin-visual-2026"
        password_hash = subprocess.check_output(
            ["php", "-r", f"echo password_hash('{password}', PASSWORD_DEFAULT);"], text=True
        )
        (data_dir / "users.json").write_text(json.dumps([{
            "id": 1, "name": "Visual Test Admin", "email": "admin-visual@example.invalid",
            "phone": "", "password": password_hash, "role": "admin",
        }]), encoding="utf-8")

        site_port, debug_port = free_port(), free_port()
        base = f"http://127.0.0.1:{site_port}"
        env = os.environ.copy()
        env.update({"FIXNEAR_DATA_DIR": str(data_dir), "FIXNEAR_DISABLE_MYSQL": "1", "FIXNEAR_ENV": "test"})
        no_window = getattr(subprocess, "CREATE_NO_WINDOW", 0)
        server = subprocess.Popen(
            ["php", "-S", f"127.0.0.1:{site_port}", "-t", str(ROOT), str(ROOT / "router.php")],
            cwd=ROOT, env=env, stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL,
            creationflags=no_window,
        )
        chrome = subprocess.Popen([
            str(CHROME), "--headless=new", "--disable-gpu", "--no-first-run", "--no-default-browser-check",
            "--remote-allow-origins=*", f"--remote-debugging-port={debug_port}",
            f"--user-data-dir={Path(temporary) / 'chrome'}", "about:blank",
        ], stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL, creationflags=no_window)
        try:
            cookies = CookieJar()
            opener = build_opener(HTTPCookieProcessor(cookies))
            for _ in range(80):
                try:
                    status, _, form = request(opener, base, "/login.php")
                    if status == 200:
                        break
                except OSError:
                    time.sleep(.1)
            else:
                raise AssertionError("Test server did not start")
            status, url, _ = request(opener, base, "/login.php", {
                "csrf_token": csrf(form), "email": "admin-visual@example.invalid", "password": password,
            })
            assert status == 200 and url.endswith("/admin/index.php"), url

            for _ in range(80):
                try:
                    target = next(t for t in json.load(urlopen(f"http://127.0.0.1:{debug_port}/json", timeout=1)) if t.get("type") == "page")
                    break
                except (OSError, StopIteration):
                    time.sleep(.1)
            else:
                raise AssertionError("Chrome DevTools endpoint did not start")

            with connect(target["webSocketDebuggerUrl"], origin="http://localhost", max_size=20_000_000) as ws:
                page = ChromePage(ws)
                page.call("Page.enable")
                page.call("Runtime.enable")
                page.call("Network.enable")
                for cookie in cookies:
                    page.call("Network.setCookie", {"name": cookie.name, "value": cookie.value, "url": base})
                routes = (
                    ("dashboard", "index.php"), ("requests", "requests.php"),
                    ("shops", "shops.php"), ("services", "services.php"),
                    ("reviews", "reviews.php"), ("reports", "reports.php"),
                    ("contacts", "contacts.php"), ("shop-edit", "shop_edit.php"),
                )
                for label, width, height in (("desktop", 1440, 900), ("tablet", 768, 1024), ("mobile", 390, 844)):
                    page.call("Emulation.setDeviceMetricsOverride", {
                        "width": width, "height": height, "deviceScaleFactor": 1, "mobile": width < 600,
                    })
                    for slug, route in routes:
                        page.call("Page.navigate", {"url": f"{base}/admin/{route}"})
                        metrics = {}
                        for _ in range(25):
                            time.sleep(.1)
                            metrics = page.evaluate("""({url:location.href,width:innerWidth,scroll:document.documentElement.scrollWidth,
                                title:document.querySelector('.fn-admin-content h1')?.textContent.trim() ?? '',
                                nav:!!document.querySelector('.fn-admin-navbar'),
                                links:document.querySelectorAll('.fn-admin-sidebar a').length})""")
                            if metrics["title"] and metrics["links"]:
                                break
                        assert metrics["width"] == width and metrics["scroll"] <= width + 2, (label, route, metrics)
                        assert metrics["title"] and metrics["nav"] and metrics["links"] >= 1, (label, route, metrics)
                        if label == "mobile":
                            cells = page.evaluate("""[...document.querySelectorAll('.fn-admin-content .fn-price-table tbody td:not([colspan])')].map(cell => cell.dataset.label || '')""")
                            assert all(cells), (label, route, cells)
                        screenshot = page.call("Page.captureScreenshot", {"format": "png", "captureBeyondViewport": False})
                        (OUTPUT / f"qa-admin-{slug}-{label}.png").write_bytes(b64decode(screenshot["data"]))
                        print(label, route, json.dumps(metrics, ensure_ascii=True))
        finally:
            for process in (chrome, server):
                process.terminate()
                try:
                    process.wait(timeout=5)
                except subprocess.TimeoutExpired:
                    process.kill()
                    process.wait(timeout=5)


if __name__ == "__main__":
    main()
