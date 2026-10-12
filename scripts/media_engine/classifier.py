"""Image classification and filtering rules."""

import re
from urllib.parse import urlparse
from typing import Tuple, Optional
from .models import ImageType


# Patterns that strongly identify a logo or icon (MUST BE REJECTED as a shop photo)
RE_LOGO = re.compile(
    r"(logo|favicon|icon|brand-mark|badge|avatar|symbol|header_logo|footer_logo|apple-touch)",
    re.IGNORECASE,
)

# Patterns for analytics tracking pixels and beacons (MUST BE REJECTED)
RE_TRACKER = re.compile(
    r"(facebook\.com/tr|google-analytics|googletagmanager|doubleclick|pixel|tracking|beacon|"
    r"criteo|hotjar|clarity\.ms|tiktok\.com|stats|hit-counter|noscript=1|1x1)",
    re.IGNORECASE,
)

# Patterns for tiny icon dimensions embedded in URL or path
RE_TINY_DIMENSIONS = re.compile(
    r"(\b(16|24|32|48|50|60|64)x(16|24|32|48|50|60|64)\b)",
    re.IGNORECASE,
)

# Patterns that indicate a specific product or repair service component (MUST BE REJECTED)
RE_PRODUCT = re.compile(
    r"(product|san-pham|man-hinh|pin-|thay-pin|ep-kinh|thay-loa|thay-camera|linh-kien|phu-kien|"
    r"case-|bao-da|sac-du-phong|op-lung|tai-nghe|airpods|cu-sac|cap-|cable|innostyle|adapter|"
    r"apple-watch-seri|iphone-\d+|galaxy-s\d+|dan-man-hinh|cuong-luc)",
    re.IGNORECASE,
)

# Patterns for marketing promo, UI banners, payments, policy icons
RE_BANNER = re.compile(
    r"(banner|slider|carousel|flash-sale|uudai|khuyenmai|khuyen-mai|promo|payment|visa|mastercard|"
    r"momo|vnpay|zalopay|tragop|giao-hang|ship-cod|background|\bbg-|\bads\b|quang-cao|"
    r"policy|chinh-sach|cam-ket|tieu-chuan|chung-nhan|certificate|danh-gia)",
    re.IGNORECASE,
)

# Positive cues for storefront / exterior
RE_STOREFRONT = re.compile(
    r"(mat-tien|cua-hang|storefront|exterior|facade|chi-nhanh|showroom|co-so|dia-chi|tru-so|shop-front)",
    re.IGNORECASE,
)

# Positive cues for interior / workshop / counter
RE_INTERIOR = re.compile(
    r"(khong-gian|ben-trong|interior|ban-ky-thuat|quay-sua|tiep-nhan|ky-thuat-vien|phong-sua|workshop)",
    re.IGNORECASE,
)


def classify_candidate(
    url: str,
    alt_text: str = "",
    context_text: str = "",
    is_og_meta: bool = False,
    is_json_ld: bool = False,
) -> Tuple[bool, str, float, str]:
    """Classify an image URL and context.

    Returns:
        (is_accepted, image_type, score, rejection_reason_or_match_detail)
    """
    url_lower = url.lower()
    path = urlparse(url).path.lower()
    combined_signals = f"{url_lower} {alt_text} {context_text}".lower()

    # 1. Reject tracking pixels and beacons
    if RE_TRACKER.search(url_lower):
        return False, ImageType.NONE, 0.0, "Rejected: Analytics tracker or pixel beacon"

    # 2. Reject SVG logos / icons
    if path.endswith(".svg"):
        return False, ImageType.NONE, 0.0, "Rejected: Vector SVG graphic (likely icon/logo)"

    # 3. Reject tiny thumbnail dimensions in URL
    if RE_TINY_DIMENSIONS.search(url_lower):
        return False, ImageType.NONE, 0.0, "Rejected: Micro-thumbnail dimensions in URL"

    # 4. Check for logo
    if RE_LOGO.search(path) or (alt_text and RE_LOGO.search(alt_text)):
        return False, ImageType.NONE, 0.0, "Rejected: Logo or icon signature detected"

    # 5. Check for product / component thumbnails
    if RE_PRODUCT.search(path) or (alt_text and RE_PRODUCT.search(alt_text)):
        return False, ImageType.NONE, 0.0, "Rejected: Product/part catalog image"

    # 6. Check for marketing / payment banners
    if RE_BANNER.search(combined_signals):
        return False, ImageType.NONE, 0.0, "Rejected: Promotional or UI banner"

    # 7. Check positive cues
    score = 10.0
    detected_type = ImageType.BRANCH_REPRESENTATIVE

    if RE_STOREFRONT.search(combined_signals):
        detected_type = ImageType.STOREFRONT
        score += 40.0
    elif RE_INTERIOR.search(combined_signals):
        detected_type = ImageType.INTERIOR
        score += 30.0

    if is_json_ld:
        score += 20.0
    if is_og_meta:
        # og:image is general unless the page itself is branch-specific
        if detected_type == ImageType.BRANCH_REPRESENTATIVE:
            detected_type = ImageType.BRAND_GENERAL
            score -= 5.0

    return True, detected_type, score, f"Candidate accepted as {detected_type}"
