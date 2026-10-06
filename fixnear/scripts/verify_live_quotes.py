#!/usr/bin/env python3
"""Check whether stored listing prices are still visible on provider pages.

This is a release check, not a price scraper: a number elsewhere on a long page
is not proof of the exact component. Review each listing before updating it.
"""

from concurrent.futures import ThreadPoolExecutor
from html.parser import HTMLParser
from pathlib import Path
from urllib.request import Request, urlopen
import json
import re

ROOT = Path(__file__).resolve().parents[1]
quotes = json.loads((ROOT / "data/pricing/source-quotes.json").read_text(encoding="utf-8"))


class VisibleText(HTMLParser):
    def __init__(self):
        super().__init__()
        self.skip = 0
        self.parts = []

    def handle_starttag(self, tag, attrs):
        if tag in {"script", "style"}:
            self.skip += 1

    def handle_endtag(self, tag):
        if tag in {"script", "style"} and self.skip:
            self.skip -= 1

    def handle_data(self, value):
        if not self.skip:
            self.parts.append(value)


def fetch(url):
    request = Request(url, headers={"User-Agent": "Mozilla/5.0"})
    with urlopen(request, timeout=20) as response:
        parser = VisibleText()
        parser.feed(response.read().decode("utf-8", "ignore"))
        return " ".join(parser.parts)


urls = sorted({item["sourceUrl"] for item in quotes})
with ThreadPoolExecutor(max_workers=4) as executor:
    results = list(executor.map(lambda url: (url, fetch(url)), urls))
pages = dict(results)

missing = []
missing_context = []
for item in quotes:
    formatted = f"{item['priceVnd']:,}".replace(",", ".")
    # Some publishers use comma grouping or spaces in their visible text.
    separator = r"[.,\s]"
    price_pattern = separator.join(re.escape(part) for part in formatted.split("."))
    matches = list(re.finditer(rf"(?<!\d){price_pattern}(?!\d)", pages[item["sourceUrl"]]))
    if not matches:
        missing.append((item["modelId"], item["faultId"], item["componentName"], formatted, item["sourceUrl"]))
    # Provider titles reorder words (e.g. "dung lượng chuẩn chính hãng Pisen").
    # Require the identifying first token near at least one matching price.
    identifier = item["componentName"].split()[0].casefold()
    if matches and not any(identifier in pages[item["sourceUrl"]][max(0, hit.start() - 130):hit.end() + 25].casefold() for hit in matches):
        missing_context.append((item["componentName"], formatted, item["sourceUrl"]))

print(f"Live source pages fetched: {len(pages)}; listing prices absent from visible page: {len(missing)}/{len(quotes)}")
print(f"Component identifier absent near price: {len(missing_context)}/{len(quotes)}")
for item in missing:
    print("MISSING:", json.dumps(item, ensure_ascii=False))
for item in missing_context:
    print("CONTEXT:", json.dumps(item, ensure_ascii=False))
raise SystemExit(1 if missing or missing_context else 0)
