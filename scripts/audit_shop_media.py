"""Inspect public website image metadata for shop media provenance."""

from concurrent.futures import ThreadPoolExecutor, as_completed
from html.parser import HTMLParser
from pathlib import Path
from urllib.parse import urlparse, urljoin
from urllib.request import Request, urlopen
import json


class MetaParser(HTMLParser):
    def __init__(self):
        super().__init__()
        self.images = []
        self.logos = []

    def handle_starttag(self, tag, attrs):
        if tag == "img":
            values = dict(attrs)
            if "logo" in ((values.get("alt") or "") + " " + (values.get("class") or "") + " " + (values.get("src") or "")).lower() and values.get("src"):
                self.logos.append(values["src"])
            return
        if tag != "meta":
            return
        values = dict(attrs)
        key = (values.get("property") or values.get("name") or "").lower()
        if key in {"og:image", "twitter:image"} and values.get("content"):
            self.images.append(values["content"])


def inspect(url):
    try:
        request = Request(url, headers={"User-Agent": "Mozilla/5.0 (compatible; FixNear-audit/1.0)"})
        with urlopen(request, timeout=8) as response:
            html = response.read(500_000).decode("utf-8", "ignore")
            final_url = response.url
        parser = MetaParser()
        parser.feed(html)
        return {"site": url, "final": final_url, "images": [urljoin(final_url, image) for image in parser.images[:3]], "logos": [urljoin(final_url, image) for image in parser.logos[:3]]}
    except Exception as exc:
        return {"site": url, "error": f"{type(exc).__name__}: {exc}"}


if __name__ == "__main__":
    shops = json.loads((Path(__file__).resolve().parents[1] / "data" / "shops.json").read_text(encoding="utf-8"))
    sites = sorted({shop.get("website", "") for shop in shops if shop.get("website") and "google.com/maps" not in shop["website"]})
    with ThreadPoolExecutor(max_workers=6) as pool:
        futures = {pool.submit(inspect, site): site for site in sites}
        for future in as_completed(futures):
            print(json.dumps(future.result(), ensure_ascii=True))
