"""Security validations, URL sanitization and SSRF prevention."""

import ipaddress
import socket
from urllib.parse import urlparse, urljoin
from typing import Optional, Tuple


# Cache resolved IPs to avoid repeated DNS lookups
_DNS_CACHE = {}


def is_safe_ip(ip_str: str) -> bool:
    """Ensure an IP address is a public, routable internet address."""
    try:
        ip = ipaddress.ip_address(ip_str)
        return not (
            ip.is_private
            or ip.is_loopback
            or ip.is_link_local
            or ip.is_reserved
            or ip.is_multicast
            or ip.is_unspecified
        )
    except ValueError:
        return False


def validate_and_sanitize_url(url: str, base_url: Optional[str] = None) -> Tuple[bool, str, Optional[str]]:
    """Validate a URL against SSRF and formatting vulnerabilities.

    Returns:
        (is_valid, normalized_url, error_message)
    """
    if not url or not isinstance(url, str):
        return False, "", "Empty URL"

    url = url.strip()
    if base_url:
        url = urljoin(base_url, url)

    try:
        parsed = urlparse(url)
    except Exception as exc:
        return False, "", f"Malformed URL: {exc}"

    if parsed.scheme.lower() not in {"http", "https"}:
        return False, "", f"Unsupported URL scheme: {parsed.scheme}"

    hostname = (parsed.hostname or "").strip().lower()
    if not hostname:
        return False, "", "Missing hostname"

    # Block well-known internal hostnames
    if hostname in {"localhost", "127.0.0.1", "::1", "0.0.0.0", "local", "lan"}:
        return False, "", f"SSRF blocked: Disallowed local hostname '{hostname}'"

    # Check cached IP or resolve
    if hostname in _DNS_CACHE:
        ip_addr = _DNS_CACHE[hostname]
    else:
        try:
            # Resolve host to IP
            addr_info = socket.getaddrinfo(hostname, None)
            if not addr_info:
                return False, "", f"Could not resolve host: {hostname}"
            ip_addr = addr_info[0][4][0]
            _DNS_CACHE[hostname] = ip_addr
        except socket.gaierror as exc:
            return False, "", f"DNS resolution failed: {exc}"
        except Exception as exc:
            return False, "", f"Socket resolution error: {exc}"

    if not is_safe_ip(ip_addr):
        return False, "", f"SSRF blocked: Resolved IP '{ip_addr}' is private/internal"

    return True, url, None
