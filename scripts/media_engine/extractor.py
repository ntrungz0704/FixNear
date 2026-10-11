"""HTML and JSON-LD extractor for media candidates and branch navigation."""

import json
import re
from html.parser import HTMLParser
from urllib.parse import urljoin, urlparse
from typing import List, Dict, Any, Optional

from .models import RawCandidate, ImageType
from .classifier import classify_candidate


RE_BRANCH_LINK = re.compile(
    r"(he-thong-cua-hang|he-thong-chi-nhanh|chi-nhanh|cua-hang|lien-he|store|branch|dia-chi|showroom|shops?)",
    re.IGNORECASE,
)


class PageExtractor(HTMLParser):
    """Parses HTML document to extract metadata, images, and branch URLs."""

    def __init__(self, base_url: str):
        super().__init__()
        self.base_url = base_url
        self.raw_candidates: List[RawCandidate] = []
        self.branch_links: List[str] = []
        self.page_text_tokens: List[str] = []

        self._current_tag: Optional[str] = None
        self._in_script_ld_json = False
        self._json_ld_buffer = []

    def handle_starttag(self, tag: str, attrs: List[tuple]):
        self._current_tag = tag.lower()
        attr_dict = {k.lower(): v for k, v in attrs if v is not None}

        # 1. Check meta tags (og:image, twitter:image)
        if self._current_tag == "meta":
            prop = (attr_dict.get("property") or attr_dict.get("name") or "").lower()
            content = attr_dict.get("content", "").strip()
            if content and prop in {"og:image", "twitter:image", "og:image:url", "twitter:image:src"}:
                resolved = urljoin(self.base_url, content)
                self.raw_candidates.append(
                    RawCandidate(
                        url=resolved,
                        source_page_url=self.base_url,
                        context_text=f"Meta {prop}",
                        alt_text="",
                        tag_name="meta",
                        is_og_meta=True,
                        detected_type=ImageType.BRAND_GENERAL,
                    )
                )

        # 2. Check JSON-LD
        elif self._current_tag == "script":
            if attr_dict.get("type") == "application/ld+json":
                self._in_script_ld_json = True
                self._json_ld_buffer = []

        # 3. Check <img> tags
        elif self._current_tag == "img":
            # Extract src or lazy-load attributes
            src = (
                attr_dict.get("data-src")
                or attr_dict.get("data-original")
                or attr_dict.get("data-lazy-src")
                or attr_dict.get("src")
                or ""
            ).strip()

            if not src and "srcset" in attr_dict:
                # Parse first candidate in srcset
                parts = attr_dict["srcset"].split(",")
                if parts:
                    src = parts[0].strip().split()[0]

            if src:
                resolved = urljoin(self.base_url, src)
                alt = attr_dict.get("alt", "").strip()
                title = attr_dict.get("title", "").strip()
                css_class = attr_dict.get("class", "").strip()
                context = f"{title} {css_class}".strip()

                self.raw_candidates.append(
                    RawCandidate(
                        url=resolved,
                        source_page_url=self.base_url,
                        context_text=context,
                        alt_text=alt,
                        tag_name="img",
                        is_og_meta=False,
                    )
                )

        # 4. Check <a> links for store locator / branch directory
        elif self._current_tag == "a":
            href = attr_dict.get("href", "").strip()
            if href and not href.startswith(("#", "javascript:", "tel:", "mailto:")):
                resolved_link = urljoin(self.base_url, href)
                # Keep only internal links on the same domain
                if urlparse(resolved_link).netloc.lower() == urlparse(self.base_url).netloc.lower():
                    link_text_hint = href + " " + attr_dict.get("title", "")
                    if RE_BRANCH_LINK.search(link_text_hint):
                        if resolved_link not in self.branch_links:
                            self.branch_links.append(resolved_link)

    def handle_endtag(self, tag: str):
        if tag.lower() == "script" and self._in_script_ld_json:
            self._in_script_ld_json = False
            raw_json = "".join(self._json_ld_buffer).strip()
            self._parse_json_ld(raw_json)

    def handle_data(self, data: str):
        clean = data.strip()
        if clean:
            self.page_text_tokens.append(clean)
            if self._in_script_ld_json:
                self._json_ld_buffer.append(data)

    def _parse_json_ld(self, raw_json: str):
        try:
            data = json.loads(raw_json)
            items = data if isinstance(data, list) else [data]
            for item in items:
                if not isinstance(item, dict):
                    continue
                # Look for images in LocalBusiness or Organization
                images = item.get("image") or item.get("photo")
                if isinstance(images, str):
                    images = [images]
                elif isinstance(images, dict) and "url" in images:
                    images = [images["url"]]
                elif not isinstance(images, list):
                    images = []

                for img_url in images:
                    if isinstance(img_url, str) and img_url.strip():
                        resolved = urljoin(self.base_url, img_url.strip())
                        self.raw_candidates.append(
                            RawCandidate(
                                url=resolved,
                                source_page_url=self.base_url,
                                context_text="JSON-LD Structured Data",
                                alt_text=item.get("name") or "",
                                tag_name="json-ld",
                                is_json_ld=True,
                                detected_type=ImageType.BRANCH_REPRESENTATIVE,
                            )
                        )
        except Exception:
            pass

    def get_full_page_text(self) -> str:
        return " ".join(self.page_text_tokens)


def extract_page_media(html_content: str, base_url: str):
    """Extract candidate images, branch links, and text from HTML."""
    parser = PageExtractor(base_url)
    try:
        parser.feed(html_content)
    except Exception:
        pass
    return parser.raw_candidates, parser.branch_links, parser.get_full_page_text()
