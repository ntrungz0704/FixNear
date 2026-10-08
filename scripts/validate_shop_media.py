"""Ensure public shops never advertise a system logo as a branch photo."""

from urllib.request import urlopen
import json
import sys


base = sys.argv[1] if len(sys.argv) > 1 else "http://127.0.0.1:8000"
with urlopen(base + "/api/get_shops.php", timeout=10) as response:
    shops = json.load(response)["data"]
misrepresented = [shop.get("id") for shop in shops if shop.get("image") or shop.get("image_kind") != "none"]
print(f"Published shops: {len(shops)}; unsupported branch photos shown: {len(misrepresented)}")
if misrepresented:
    raise SystemExit(f"Shop IDs with unsupported photos: {misrepresented}")
