"""Check that published shop logos actually load from their website origins."""

from concurrent.futures import ThreadPoolExecutor
from urllib.request import Request, urlopen
import json
import sys


base = sys.argv[1] if len(sys.argv) > 1 else "http://127.0.0.1:8000"
with urlopen(base + "/api/get_shops.php", timeout=10) as response:
    shops = json.load(response)["data"]
urls = sorted({shop["image"] for shop in shops if shop.get("image")})


def check(url):
    try:
        request = Request((base + "/" + url) if url.startswith("assets/") else url, headers={"User-Agent": "Mozilla/5.0"})
        with urlopen(request, timeout=10) as response:
            content_type = response.headers.get("Content-Type", "")
            return url, response.status == 200 and content_type.startswith("image/"), content_type
    except Exception as exc:
        return url, False, type(exc).__name__


with ThreadPoolExecutor(max_workers=6) as pool:
    results = list(pool.map(check, urls))
print(f"Published shops: {len(shops)}; website logos: {len(urls)}; broken: {sum(not item[1] for item in results)}")
for url, valid, status in results:
    if not valid:
        print(status, url)
if any(not item[1] for item in results):
    raise SystemExit(1)
