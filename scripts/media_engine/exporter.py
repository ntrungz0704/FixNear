"""Export tools for candidates JSON, CSV, Google Sheets format and summary reporting."""

import json
import csv
from pathlib import Path
from typing import List, Dict, Any
from .models import ImageAuditRecord, VerificationStatus, LicenseStatus, ImageType


def export_candidates_json(records: List[ImageAuditRecord], output_path: Path):
    """Write all audit records to JSON."""
    output_path.parent.mkdir(parents=True, exist_ok=True)
    data = [r.to_dict() for r in records]
    with open(output_path, "w", encoding="utf-8") as f:
        json.dump(data, f, ensure_ascii=False, indent=2)


def export_candidates_csv(records: List[ImageAuditRecord], output_path: Path):
    """Write audit records to standard CSV."""
    output_path.parent.mkdir(parents=True, exist_ok=True)
    fieldnames = [
        "shop_id",
        "shop_name",
        "shop_address",
        "website",
        "image_url",
        "source_page_url",
        "image_type",
        "verification_status",
        "license_status",
        "branch_match_evidence",
        "checked_at",
        "error",
    ]
    with open(output_path, "w", encoding="utf-8", newline="") as f:
        writer = csv.DictWriter(f, fieldnames=fieldnames)
        writer.writeheader()
        for r in records:
            d = r.to_dict()
            writer.writerow({k: d.get(k, "") for k in fieldnames})


def export_sheets_csv(records: List[ImageAuditRecord], output_path: Path):
    """Export formatted CSV compatible with Google Sheets tab 'Ảnh cửa hàng - Gemini'."""
    output_path.parent.mkdir(parents=True, exist_ok=True)
    fieldnames = [
        "STT",
        "Tên cửa hàng",
        "Địa chỉ",
        "Google Maps URL",
        "Image URL",
        "Trang nguồn",
        "Loại ảnh",
        "Bằng chứng chi nhánh",
        "Bản quyền",
        "Trạng thái",
        "Ngày kiểm tra",
        "Ghi chú / Lỗi",
    ]
    with open(output_path, "w", encoding="utf-8", newline="") as f:
        writer = csv.DictWriter(f, fieldnames=fieldnames)
        writer.writeheader()
        for r in records:
            writer.writerow(r.to_sheet_row())


def generate_summary_report(records: List[ImageAuditRecord]) -> str:
    """Generate a clean markdown summary report."""
    total = len(records)
    with_img = sum(1 for r in records if r.image_url)
    verified = sum(1 for r in records if r.verification_status == VerificationStatus.VERIFIED)
    pending = sum(1 for r in records if r.verification_status == VerificationStatus.PENDING_REVIEW)
    not_found = sum(1 for r in records if r.verification_status == VerificationStatus.NOT_FOUND)
    rejected = sum(1 for r in records if r.verification_status == VerificationStatus.REJECTED)

    type_counts = {
        ImageType.STOREFRONT: sum(1 for r in records if r.image_type == ImageType.STOREFRONT),
        ImageType.INTERIOR: sum(1 for r in records if r.image_type == ImageType.INTERIOR),
        ImageType.BRANCH_REPRESENTATIVE: sum(1 for r in records if r.image_type == ImageType.BRANCH_REPRESENTATIVE),
        ImageType.BRAND_GENERAL: sum(1 for r in records if r.image_type == ImageType.BRAND_GENERAL),
        ImageType.NONE: sum(1 for r in records if r.image_type == ImageType.NONE),
    }

    license_counts = {
        LicenseStatus.PERMITTED: sum(1 for r in records if r.license_status == LicenseStatus.PERMITTED),
        LicenseStatus.UNKNOWN: sum(1 for r in records if r.license_status == LicenseStatus.UNKNOWN),
        LicenseStatus.RESTRICTED: sum(1 for r in records if r.license_status == LicenseStatus.RESTRICTED),
    }

    lines = [
        "============================================================",
        "  FIXNEAR - STORE IMAGE RESEARCH & VERIFICATION REPORT",
        "============================================================",
        f"Tổng số cửa hàng khảo sát: {total}",
        f"Tìm thấy URL ảnh:          {with_img} ({with_img/total*100:.1f}%)" if total else "0",
        f"Không tìm thấy ảnh:         {not_found}",
        "",
        "--- PHÂN LOẠI TRẠNG THÁI XÁC MINH (Verification Status) ---",
        f"  * Verified (Khớp đúng chi nhánh):   {verified}",
        f"  * Pending Review (Cần duyệt thêm):  {pending}",
        f"  * Not Found (Không tìm thấy):       {not_found}",
        f"  * Rejected (Không hợp lệ/Lỗi):      {rejected}",
        "",
        "--- PHÂN LOẠI HÌNH ẢNH (Image Type) ---",
        f"  * Storefront (Mặt tiền chi nhánh):  {type_counts[ImageType.STOREFRONT]}",
        f"  * Interior (Không gian bên trong):  {type_counts[ImageType.INTERIOR]}",
        f"  * Branch Representative:            {type_counts[ImageType.BRANCH_REPRESENTATIVE]}",
        f"  * Brand General (Logo/Banner tổng): {type_counts[ImageType.BRAND_GENERAL]}",
        f"  * None:                             {type_counts[ImageType.NONE]}",
        "",
        "--- TÌNH TRẠNG BẢN QUYỀN (License Status) ---",
        f"  * Permitted (Được cấp phép công khai): {license_counts[LicenseStatus.PERMITTED]}",
        f"  * Unknown (Cần kiểm tra thỏa thuận):   {license_counts[LicenseStatus.UNKNOWN]}",
        f"  * Restricted (Bị hạn chế):             {license_counts[LicenseStatus.RESTRICTED]}",
        "============================================================",
    ]
    return "\n".join(lines)
