"""Check that each published shop street address appears on its cited site."""

from pathlib import Path
from urllib.request import Request, urlopen
from html.parser import HTMLParser
import hashlib
import json
import re
import unicodedata


ROOT = Path(__file__).resolve().parents[1]
evidence = json.loads((ROOT / "data" / "shop-address-evidence.json").read_text(encoding="utf-8"))
shops = {str(shop["id"]): shop for shop in json.loads((ROOT / "data" / "shops.json").read_text(encoding="utf-8"))}


class PageText(HTMLParser):
    def __init__(self):
        super().__init__()
        self.parts = []

    def handle_data(self, data):
        self.parts.append(data)


def compact(value):
    value = value.lower().replace("ba tháng hai", "3 tháng 2")
    value = unicodedata.normalize("NFKD", value.replace("đ", "d"))
    value = "".join(char for char in value if not unicodedata.combining(char))
    return re.sub(r"[^a-z0-9]", "", value)


pages = {}
phone_pages = {}
for key, url in evidence["sources"].items():
    request = Request(url, headers={"User-Agent": "Mozilla/5.0"})
    with urlopen(request, timeout=12) as response:
        parser = PageText()
        parser.feed(response.read().decode("utf-8", "ignore"))
        content = " ".join(parser.parts)
        pages[key] = compact(content)
        phone_pages[key] = re.sub(r"[^0-9]", "", content)

unmatched = []
unmatched_phone = []
for shop_id, source in evidence["shops"].items():
    record = shops[shop_id]
    expected_hash = hashlib.sha256((record["name"] + "|" + record["address"].split(",", 1)[0]).encode("utf-8")).hexdigest()
    if evidence.get("recordHashes", {}).get(shop_id) != expected_hash:
        unmatched.append((shop_id, "record hash mismatch", source))
    street = shops[shop_id]["address"].split(",", 1)[0]
    if compact(street) not in pages[source]:
        unmatched.append((shop_id, street, source))
    phone = re.sub(r"[^0-9]", "", evidence.get("phoneOverrides", {}).get(shop_id, shops[shop_id].get("phone", "")))
    if phone and phone not in phone_pages[source]:
        unmatched_phone.append((shop_id, phone, source))
print(f"Published address evidence: {len(evidence['shops'])} shops; unmatched: {len(unmatched)}")
for item in unmatched:
    print(json.dumps(item, ensure_ascii=True))
print(f"Phone numbers absent from cited pages: {len(unmatched_phone)}")
for item in unmatched_phone:
    print(json.dumps(item, ensure_ascii=True))
if unmatched or unmatched_phone:
    raise SystemExit(1)
