"""Rate-limited HTTP client with exponential backoff and probe utilities."""

import time
import ssl
import threading
from urllib.parse import urlparse
from urllib.request import Request, urlopen
from urllib.error import HTTPError, URLError
from typing import Tuple, Optional, Dict, Any

from .security import validate_and_sanitize_url


class SafeHttpFetcher:
    """Production-grade HTTP client with per-domain rate limiting and backoff."""

    def __init__(
        self,
        timeout: float = 8.0,
        max_retries: int = 3,
        min_domain_delay: float = 0.5,
        max_html_bytes: int = 2_000_000,
        max_image_bytes: int = 10_000_000,
        user_agent: str = (
            "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 "
            "(KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36 (FixNear-Audit/2.0)"
        ),
    ):
        self.timeout = timeout
        self.max_retries = max_retries
        self.min_domain_delay = min_domain_delay
        self.max_html_bytes = max_html_bytes
        self.max_image_bytes = max_image_bytes
        self.user_agent = user_agent

        self._domain_locks: Dict[str, threading.Lock] = {}
        self._last_access: Dict[str, float] = {}
        self._global_lock = threading.Lock()

        # SSL context: try default, fallback to unverified for local dev or misconfigured intermediate certs
        self._ssl_default = ssl.create_default_context()
        self._ssl_unverified = ssl._create_unverified_context()

    def _get_domain_lock(self, domain: str) -> threading.Lock:
        with self._global_lock:
            if domain not in self._domain_locks:
                self._domain_locks[domain] = threading.Lock()
            return self._domain_locks[domain]

    def _throttle(self, domain: str):
        lock = self._get_domain_lock(domain)
        with lock:
            now = time.time()
            last = self._last_access.get(domain, 0.0)
            elapsed = now - last
            if elapsed < self.min_domain_delay:
                time.sleep(self.min_domain_delay - elapsed)
            self._last_access[domain] = time.time()

    def fetch_html(self, url: str) -> Tuple[Optional[str], Optional[str], Optional[str]]:
        """Fetch HTML document safely.

        Returns:
            (html_content, final_url, error_message)
        """
        is_safe, clean_url, sec_err = validate_and_sanitize_url(url)
        if not is_safe:
            return None, None, sec_err

        domain = urlparse(clean_url).netloc.lower()
        backoff = 1.0

        for attempt in range(1, self.max_retries + 1):
            self._throttle(domain)
            try:
                req = Request(
                    clean_url,
                    headers={
                        "User-Agent": self.user_agent,
                        "Accept": "text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8",
                        "Accept-Language": "vi,en-US;q=0.9,en;q=0.8",
                    },
                )
                try:
                    res = urlopen(req, timeout=self.timeout, context=self._ssl_default)
                except Exception as net_err:
                    if "ssl" in str(net_err).lower() or "certificate" in str(net_err).lower():
                        res = urlopen(req, timeout=self.timeout, context=self._ssl_unverified)
                    else:
                        raise

                with res:
                    final_url = res.url
                    # Verify content length if provided
                    cl = res.headers.get("Content-Length")
                    if cl and int(cl) > self.max_html_bytes:
                        return None, final_url, f"HTML size exceeds {self.max_html_bytes} bytes"

                    raw = res.read(self.max_html_bytes)
                    charset = res.headers.get_content_charset() or "utf-8"
                    try:
                        html = raw.decode(charset, errors="replace")
                    except Exception:
                        html = raw.decode("utf-8", errors="replace")

                    return html, final_url, None

            except HTTPError as exc:
                if exc.code in {400, 401, 403, 404, 410}:
                    return None, None, f"HTTP {exc.code}: {exc.reason}"
                if exc.code == 429:
                    # Rate limited: wait longer
                    time.sleep(backoff * 2)
                if attempt == self.max_retries:
                    return None, None, f"HTTP {exc.code} after {attempt} attempts"

            except (URLError, TimeoutError) as exc:
                if attempt == self.max_retries:
                    return None, None, f"Network error: {exc}"

            except Exception as exc:
                return None, None, f"Unexpected fetch error: {exc}"

            time.sleep(backoff)
            backoff *= 2.0

        return None, None, "Max retries exceeded"

    def probe_image(self, image_url: str, referer: Optional[str] = None) -> Tuple[bool, Dict[str, Any], Optional[str]]:
        """Verify that an image URL is alive, accessible and returns an image Content-Type.

        Returns:
            (is_valid_image, metadata, error_message)
        """
        is_safe, clean_url, sec_err = validate_and_sanitize_url(image_url)
        if not is_safe:
            return False, {}, sec_err

        domain = urlparse(clean_url).netloc.lower()
        self._throttle(domain)

        headers = {
            "User-Agent": self.user_agent,
            "Accept": "image/avif,image/webp,image/apng,image/svg+xml,image/*,*/*;q=0.8",
        }
        if referer:
            headers["Referer"] = referer
        else:
            headers["Referer"] = f"https://{domain}"

        # First attempt: HEAD probe
        req_head = Request(clean_url, headers=headers, method="HEAD")
        try:
            try:
                res = urlopen(req_head, timeout=self.timeout, context=self._ssl_default)
            except Exception as net_err:
                if "ssl" in str(net_err).lower() or "certificate" in str(net_err).lower():
                    res = urlopen(req_head, timeout=self.timeout, context=self._ssl_unverified)
                else:
                    raise
            with res:
                ct = (res.headers.get("Content-Type") or "").lower()
                cl = res.headers.get("Content-Length")
                final_url = res.url

                if not ct.startswith("image/"):
                    return False, {}, f"Non-image content type: '{ct}'"

                content_len = int(cl) if (cl and cl.isdigit()) else 0
                if content_len > self.max_image_bytes:
                    return False, {}, f"Image too large: {content_len} bytes"

                if 0 < content_len < 1500:
                    return False, {}, f"Image too small (likely 1x1 tracking pixel): {content_len} bytes"

                return True, {
                    "content_type": ct,
                    "content_length": content_len,
                    "final_url": final_url,
                }, None

        except HTTPError as exc:
            if exc.code not in {405, 403}:  # Method Not Allowed or Forbidden for HEAD
                return False, {}, f"HTTP {exc.code} on image probe"

        except Exception:
            pass

        # Fallback attempt: GET with small byte range (first 4KB to read header)
        try:
            req_get = Request(
                clean_url,
                headers={**headers, "Range": "bytes=0-4096"},
                method="GET",
            )
            try:
                res = urlopen(req_get, timeout=self.timeout, context=self._ssl_default)
            except Exception as net_err:
                if "ssl" in str(net_err).lower() or "certificate" in str(net_err).lower():
                    res = urlopen(req_get, timeout=self.timeout, context=self._ssl_unverified)
                else:
                    raise
            with res:
                ct = (res.headers.get("Content-Type") or "").lower()
                cl = res.headers.get("Content-Length")
                final_url = res.url

                if not ct.startswith("image/"):
                    return False, {}, f"Non-image content type: '{ct}'"

                content_len = int(cl) if (cl and cl.isdigit()) else 0
                return True, {
                    "content_type": ct,
                    "content_length": content_len,
                    "final_url": final_url,
                }, None

        except HTTPError as exc:
            return False, {}, f"HTTP {exc.code}: {exc.reason}"
        except Exception as exc:
            return False, {}, f"Image probe error: {exc}"
