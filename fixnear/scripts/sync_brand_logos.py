#!/usr/bin/env python3
"""Refresh local brand marks from pinned vector and official website assets.

Run deliberately when brand identity changes; the site does not hotlink these
assets. The generated manifest records provenance and file hashes.
"""

from datetime import datetime
from hashlib import sha256
from html import unescape
from pathlib import Path
from urllib.request import Request, urlopen
import json
import re
from zoneinfo import ZoneInfo


ROOT = Path(__file__).resolve().parents[1]
DEST = ROOT / "public/assets/images/brands"
MANIFEST = ROOT / "data/brand-logo-sources.json"
SIMPLE_ICONS_COMMIT = "98820a4dc8c363ca72fa2c0d294ea4a0a9bba75d"
SIMPLE_IDS = [
    "acer", "apple", "asus", "dell", "garmin", "google", "honor", "hp",
    "huawei", "lenovo", "lg", "motorola", "msi", "nokia", "oneplus",
    "oppo", "razer", "samsung", "sony", "vivo", "xiaomi",
]
OFFICIAL = {
    "realme": ("png", "https://image01.realme.net/general/20181218/1545105227803.png", "https://www.realme.com/global/"),
    "poco": ("png", "https://i01.appmifile.com/webfile/globalimg/i18n/poco/POCO.png", "https://www.po.co/global/index.html"),
    "nothing": ("png", "https://cdn.shopify.com/s/files/1/0376/5420/0459/files/LOGO_400X200_0b0683f1-5666-4cc5-9b12-996061e29fd1.png?v=1666624074", "https://nothing.tech/"),
    "infinix": ("png", "https://www.infinixmobility.com/_nuxt/img/footerlogo.99b6809.png", "https://www.infinixmobility.com/"),
    "tecno": ("svg", "https://d13pvy8xd75yde.cloudfront.net/global/x_new/logo.svg", "https://www.tecno-mobile.com/"),
    "zte": ("png", "https://www.zte.com.cn/content/dam/zte-site/res-www-zte-com-cn/navigator/global/main/img/logo-en.png", "https://www.zte.com.cn/global/new_events/media.html"),
    "microsoft": ("svg", "https://learn.microsoft.com/en-us/entra/identity-platform/media/howto-add-branding-in-apps/ms-symbollockup_mssymbol_19.svg", "https://learn.microsoft.com/en-us/entra/identity-platform/howto-add-branding-in-apps"),
    "dynabook": ("svg", "https://us.dynabook.com/images/udc/2026/header/logo.svg", "https://us.dynabook.com/"),
    "redmi": ("svg", "https://commons.wikimedia.org/wiki/Special:FilePath/REDMI_New_Logo_(Red).svg", "https://www.mi.com/global/product-list/redmi/"),
}


def fetch(url):
    req = Request(url, headers={"User-Agent": "Mozilla/5.0 (FixNear brand asset audit)"})
    with urlopen(req, timeout=20) as response:
        data = response.read()
        if len(data) > 1_000_000:
            raise ValueError(f"brand asset too large: {url}")
        return data


def write_asset(name, ext, data, asset_url, reference_url, kind):
    if ext == "svg":
        data = data.replace(b"\r\n", b"\n")
    if ext == "svg" and b"<svg" not in data[:200]:
        raise ValueError(f"not SVG: {name}")
    if ext == "png" and not data.startswith(b"\x89PNG\r\n\x1a\n"):
        raise ValueError(f"not PNG: {name}")
    path = DEST / f"{name}.{ext}"
    path.write_bytes(data)
    return {
        "file": f"{name}.{ext}", "kind": kind, "assetUrl": asset_url,
        "referenceUrl": reference_url, "sha256": sha256(data).hexdigest(),
    }


def main():
    DEST.mkdir(parents=True, exist_ok=True)
    base = f"https://raw.githubusercontent.com/simple-icons/simple-icons/{SIMPLE_ICONS_COMMIT}"
    metadata = json.loads(fetch(base + "/data/simple-icons.json"))
    by_title = {item["title"].casefold(): item for item in metadata}
    result = {}
    for name in SIMPLE_IDS:
        item = by_title.get(name.casefold())
        if not item:
            raise ValueError(f"Simple Icons metadata lacks {name}")
        url = f"{base}/icons/{name}.svg"
        data = fetch(url)
        if name.upper().encode() not in data.upper():
            raise ValueError(f"SVG title does not match {name}")
        result[name] = write_asset(name, "svg", data, url, item["source"], "pinned-vector")
    for name, (ext, url, reference) in OFFICIAL.items():
        result[name] = write_asset(name, ext, fetch(url), url, reference,
                                   "brand-website" if name != "redmi" else "current-redmi-vector")

    # iQOO publishes the wordmark as an inline SVG in its official header.
    iqoo_url = "https://www.iqoo.com/en/"
    html = fetch(iqoo_url).decode("utf-8", "ignore")
    anchor = html.index('class="vep-pc-logo"')
    svg = html[html.index("<svg", anchor):html.index("</svg>", anchor) + 6]
    svg = unescape(svg).replace('fill="none"', 'fill="#111827"', 1)
    if "viewBox=\"0 0 104 28\"" not in svg:
        raise ValueError("iQOO header SVG changed; inspect before replacing")
    result["iqoo"] = write_asset("iqoo", "svg", svg.encode("utf-8"), iqoo_url, iqoo_url, "brand-website-inline-vector")

    output = {"checkedAt": datetime.now(ZoneInfo("Asia/Ho_Chi_Minh")).date().isoformat(),
              "simpleIconsCommit": SIMPLE_ICONS_COMMIT,
              "logos": dict(sorted(result.items()))}
    MANIFEST.write_text(json.dumps(output, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
    print(f"Wrote {len(result)} audited brand assets to {DEST}")


if __name__ == "__main__":
    main()
