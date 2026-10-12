"""Data models and type definitions for shop image research."""

from dataclasses import dataclass, field, asdict
from typing import Optional, List, Dict, Any
from datetime import datetime, timezone


class VerificationStatus:
    VERIFIED = "verified"
    PENDING_REVIEW = "pending_review"
    NOT_FOUND = "not_found"
    REJECTED = "rejected"


class LicenseStatus:
    PERMITTED = "permitted"
    UNKNOWN = "unknown"
    RESTRICTED = "restricted"


class ImageType:
    STOREFRONT = "storefront"
    INTERIOR = "interior"
    BRANCH_REPRESENTATIVE = "branch_representative"
    BRAND_GENERAL = "brand_general"
    NONE = "none"


@dataclass
class ShopItem:
    shop_id: int
    name: str
    address: str
    ward: str = ""
    district: str = ""
    phone: str = ""
    website: str = ""
    map_url: str = ""
    stt: Optional[int] = None
    raw_data: Dict[str, Any] = field(default_factory=dict)

    @classmethod
    def from_dict(cls, data: Dict[str, Any], default_id: int = 0) -> "ShopItem":
        sid = data.get("id") or data.get("shop_id") or data.get("STT") or default_id
        try:
            sid = int(sid)
        except (ValueError, TypeError):
            sid = default_id

        stt_val = data.get("STT")
        try:
            stt = int(stt_val) if stt_val is not None else None
        except (ValueError, TypeError):
            stt = None

        return cls(
            shop_id=sid,
            name=str(data.get("name") or data.get("Tên cửa hàng") or "").strip(),
            address=str(data.get("address") or data.get("Địa chỉ") or "").strip(),
            ward=str(data.get("ward") or "").strip(),
            district=str(data.get("district") or data.get("Khu vực") or "").strip(),
            phone=str(data.get("phone") or data.get("SĐT") or "").strip(),
            website=str(data.get("website") or data.get("Website/Facebook") or "").strip(),
            map_url=str(data.get("map_url") or data.get("Google Maps URL") or data.get("Google Maps") or "").strip(),
            stt=stt,
            raw_data=data,
        )


@dataclass
class RawCandidate:
    url: str
    source_page_url: str
    context_text: str = ""
    alt_text: str = ""
    tag_name: str = "img"
    is_og_meta: bool = False
    is_json_ld: bool = False
    score: float = 0.0
    detected_type: str = ImageType.BRANCH_REPRESENTATIVE


@dataclass
class ImageAuditRecord:
    shop_id: int
    shop_name: str
    shop_address: str
    website: str
    image_url: Optional[str] = None
    source_page_url: Optional[str] = None
    image_type: str = ImageType.NONE
    verification_status: str = VerificationStatus.NOT_FOUND
    license_status: str = LicenseStatus.UNKNOWN
    branch_match_evidence: str = ""
    checked_at: str = field(default_factory=lambda: datetime.now(timezone.utc).isoformat())
    error: Optional[str] = None
    map_url: str = ""
    stt: Optional[int] = None

    def to_dict(self) -> Dict[str, Any]:
        return asdict(self)

    def to_sheet_row(self) -> Dict[str, Any]:
        """Convert record to columns compatible with Google Sheets tab."""
        return {
            "STT": self.stt or self.shop_id,
            "Tên cửa hàng": self.shop_name,
            "Địa chỉ": self.shop_address,
            "Google Maps URL": self.map_url,
            "Image URL": self.image_url or "Không tìm thấy URL ảnh phù hợp",
            "Trang nguồn": self.source_page_url or "",
            "Loại ảnh": self.image_type,
            "Bằng chứng chi nhánh": self.branch_match_evidence,
            "Bản quyền": self.license_status,
            "Trạng thái": self.verification_status,
            "Ngày kiểm tra": self.checked_at[:10],
            "Ghi chú / Lỗi": self.error or "",
        }
