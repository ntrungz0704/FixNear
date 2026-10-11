"""Address and phone matching logic for branch verification."""

import re
import unicodedata
from typing import Tuple, Dict, Any, List


def strip_accents(text: str) -> str:
    """Normalize and remove diacritics for flexible fuzzy comparisons."""
    text = unicodedata.normalize("NFD", text)
    text = "".join(char for char in text if unicodedata.category(char) != "Mn")
    return unicodedata.normalize("NFC", text).lower()


def clean_phone(phone: str) -> str:
    """Extract only digits from a phone number string."""
    return re.sub(r"\D+", "", phone or "")


def parse_address_components(address: str) -> Dict[str, str]:
    """Parse address into street number, street name, and ward/district clues."""
    parts = [p.strip() for p in address.split(",") if p.strip()]
    first_part = parts[0] if parts else address

    # Match house number (e.g., "136", "249-251", "56/2B", "1500")
    num_match = re.match(r"^([0-9]+(?:[\/\-][0-9A-Za-z]+)*)\s+(.*)$", first_part)
    if num_match:
        house_num = num_match.group(1).strip()
        street_name = num_match.group(2).strip()
    else:
        house_num = ""
        street_name = first_part

    # Clean prefixes like "Đường", "Phố"
    clean_street = re.sub(r"^(đường|duong|phố|pho|số|so)\s+", "", street_name, flags=re.I).strip()

    return {
        "raw": address,
        "house_num": house_num,
        "street_name": clean_street,
        "parts": parts,
    }


def evaluate_branch_match(
    page_text: str,
    shop_address: str,
    shop_phone: str = "",
    shop_district: str = "",
) -> Tuple[bool, str, float]:
    """Check if a webpage or element text contains verifiable evidence of the specific branch.

    Returns:
        (is_matched, evidence_description, confidence_score)
    """
    if not page_text or not shop_address:
        return False, "Không có nội dung để đối soát", 0.0

    norm_page = strip_accents(page_text)
    addr_info = parse_address_components(shop_address)

    house_num = addr_info["house_num"]
    street_name = addr_info["street_name"]
    norm_street = strip_accents(street_name)
    norm_district = strip_accents(shop_district)

    evidences: List[str] = []
    score = 0.0

    # 1. Phone number match
    phone_digits = clean_phone(shop_phone)
    if len(phone_digits) >= 7 and phone_digits in clean_phone(page_text):
        evidences.append(f"Khớp số hotline '{shop_phone}'")
        score += 40.0

    # 2. House number + street match
    if house_num and norm_street and len(norm_street) >= 3:
        # Check if number appears within proximity of street name
        if norm_street in norm_page:
            # Check for house number
            if house_num.lower() in norm_page:
                evidences.append(f"Khớp số nhà '{house_num}' và đường '{street_name}'")
                score += 50.0
            else:
                evidences.append(f"Khớp tên đường '{street_name}'")
                score += 20.0

    elif norm_street and len(norm_street) >= 3 and norm_street in norm_page:
        evidences.append(f"Khớp tên đường '{street_name}'")
        score += 20.0

    # 3. District match
    if norm_district and len(norm_district) >= 3 and norm_district in norm_page:
        evidences.append(f"Khớp khu vực '{shop_district}'")
        score += 15.0

    is_matched = score >= 35.0
    evidence_desc = "; ".join(evidences) if evidences else "Không tìm thấy thông tin chi nhánh trên trang"

    return is_matched, evidence_desc, score
