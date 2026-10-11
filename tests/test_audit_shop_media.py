"""Automated test suite for FixNear Store Image Research & Verification Engine."""

import unittest
import tempfile
import json
import csv
from pathlib import Path

# Add scripts directory to sys.path
import sys
sys.path.insert(0, str(Path(__file__).resolve().parents[1] / "scripts"))

from media_engine.models import (
    ShopItem,
    ImageAuditRecord,
    VerificationStatus,
    LicenseStatus,
    ImageType,
)
from media_engine.security import validate_and_sanitize_url, is_safe_ip
from media_engine.classifier import classify_candidate
from media_engine.matcher import evaluate_branch_match, parse_address_components
from media_engine.extractor import extract_page_media
from media_engine.exporter import (
    export_candidates_json,
    export_candidates_csv,
    export_sheets_csv,
    generate_summary_report,
)
from audit_shop_media import load_shops, resolve_official_website, CheckpointManager


class TestShopImageResearchEngine(unittest.TestCase):

    def test_01_load_shops_json_and_csv(self):
        """Test parsing of shop lists from both JSON and CSV sources."""
        with tempfile.NamedTemporaryFile("w+", suffix=".json", delete=False, encoding="utf-8") as f:
            json.dump([
                {"id": 1, "name": "Điện Thoại Vui", "address": "136 Nguyễn Thái Học, Q.1", "phone": "1800 2064", "website": "https://dienthoaivui.com.vn"},
                {"id": 2, "name": "FASTCARE", "address": "580 CMT8, Q.3", "phone": "1800 2057", "website": "https://fastcare.vn"}
            ], f)
            json_path = Path(f.name)

        with tempfile.NamedTemporaryFile("w+", suffix=".csv", delete=False, encoding="utf-8") as f:
            f.write("STT,Tên cửa hàng,Địa chỉ,SĐT,Website/Facebook\n")
            f.write("1,Điện Thoại Vui,136 Nguyễn Thái Học,1800 2064,https://dienthoaivui.com.vn\n")
            csv_path = Path(f.name)

        try:
            shops_json = load_shops(json_path)
            self.assertEqual(len(shops_json), 2)
            self.assertEqual(shops_json[0].shop_id, 1)
            self.assertEqual(shops_json[0].name, "Điện Thoại Vui")

            shops_csv = load_shops(csv_path)
            self.assertEqual(len(shops_csv), 1)
            self.assertEqual(shops_csv[0].name, "Điện Thoại Vui")
        finally:
            json_path.unlink(missing_ok=True)
            csv_path.unlink(missing_ok=True)

    def test_02_url_normalization_and_ssrf_safety(self):
        """Test URL normalization and SSRF validation."""
        # Loopback / internal must be rejected
        is_safe, _, err = validate_and_sanitize_url("http://localhost:8000/admin")
        self.assertFalse(is_safe)
        self.assertIn("Disallowed", err)

        is_safe, _, err = validate_and_sanitize_url("http://127.0.0.1/test")
        self.assertFalse(is_safe)

        # Public internet address must be accepted
        is_safe, clean_url, err = validate_and_sanitize_url("https://dienthoaivui.com.vn/uploads/store.jpg")
        self.assertTrue(is_safe)
        self.assertIsNone(err)
        self.assertEqual(clean_url, "https://dienthoaivui.com.vn/uploads/store.jpg")

        # Relative resolution
        is_safe, clean_url, _ = validate_and_sanitize_url("/images/branch.webp", base_url="https://fastcare.vn/about")
        self.assertTrue(is_safe)
        self.assertEqual(clean_url, "https://fastcare.vn/images/branch.webp")

    def test_03_extract_og_image(self):
        """Test extraction of og:image and twitter:image."""
        sample_html = """
        <!DOCTYPE html>
        <html>
        <head>
            <meta property="og:image" content="https://example.com/share-brand.png">
            <meta name="twitter:image" content="/images/twitter-card.jpg">
        </head>
        <body><h1>Hello</h1></body>
        </html>
        """
        candidates, _, _ = extract_page_media(sample_html, "https://example.com")
        urls = [c.url for c in candidates]
        self.assertIn("https://example.com/share-brand.png", urls)
        self.assertIn("https://example.com/images/twitter-card.jpg", urls)

    def test_04_extract_branch_images_and_links(self):
        """Test extraction of lazy-loaded images, json-ld, and branch navigation links."""
        sample_html = """
        <html>
        <body>
            <a href="/he-thong-cua-hang" title="Xem chi nhánh">Danh sách cửa hàng</a>
            <img data-src="/uploads/cua-hang-quan-1-mat-tien.jpg" alt="Mặt tiền chi nhánh Quận 1" class="branch-photo">
            <script type="application/ld+json">
            {
                "@type": "LocalBusiness",
                "name": "Chi nhánh 1",
                "image": "https://example.com/branch1-full.jpg"
            }
            </script>
        </body>
        </html>
        """
        candidates, branch_links, text = extract_page_media(sample_html, "https://example.com")
        urls = [c.url for c in candidates]
        self.assertIn("https://example.com/uploads/cua-hang-quan-1-mat-tien.jpg", urls)
        self.assertIn("https://example.com/branch1-full.jpg", urls)
        self.assertIn("https://example.com/he-thong-cua-hang", branch_links)

    def test_05_distinguish_logo_product_and_storefront(self):
        """Test that logos, SVG icons, and products are strictly rejected."""
        # Logo test
        accepted, _, _, reason = classify_candidate("https://example.com/assets/logo.png", alt_text="FixNear Logo")
        self.assertFalse(accepted)
        self.assertIn("Logo", reason)

        # SVG icon test
        accepted, _, _, reason = classify_candidate("https://example.com/icon-tool.svg")
        self.assertFalse(accepted)
        self.assertIn("SVG", reason)

        # Product thumbnail test
        accepted, _, _, reason = classify_candidate("https://example.com/san-pham/thay-man-hinh-iphone.jpg")
        self.assertFalse(accepted)
        self.assertIn("Product", reason)

        # Marketing banner test
        accepted, _, _, reason = classify_candidate("https://example.com/banner-khuyen-mai-8-3.jpg")
        self.assertFalse(accepted)
        self.assertIn("banner", reason.lower())

        # Storefront candidate test (Positive match)
        accepted, img_type, score, _ = classify_candidate(
            "https://example.com/photos/mat-tien-chi-nhanh-cmt8.jpg",
            alt_text="Mặt tiền cửa hàng 580 Cách Mạng Tháng 8",
        )
        self.assertTrue(accepted)
        self.assertEqual(img_type, ImageType.STOREFRONT)
        self.assertGreater(score, 30.0)

    def test_06_branch_address_and_phone_matching(self):
        """Test matching branch address and phone against webpage text."""
        page_html_text = """
        Hệ thống cửa hàng Viện Di Động tại TP.HCM:
        Chi nhánh 1: 73 Trần Quang Khải, Phường Tân Định, Quận 1. Hotline: 1800 6729.
        Chi nhánh 2: 608 Lê Văn Sỹ, Phường 14, Quận 3.
        """
        # Match Chi nhánh 1
        matched, evidence, score = evaluate_branch_match(
            page_html_text,
            shop_address="73 Trần Quang Khải, Phường Tân Định, Quận 1, TP.HCM",
            shop_phone="1800 6729",
            shop_district="Quận 1",
        )
        self.assertTrue(matched)
        self.assertIn("Trần Quang Khải", evidence)
        self.assertIn("1800 6729", evidence)
        self.assertGreater(score, 50.0)

        # Mismatch with non-existent store
        matched_fake, _, _ = evaluate_branch_match(
            page_html_text,
            shop_address="999 Xa Lộ Hà Nội, Phường Hiệp Phú, TP. Thủ Đức",
            shop_phone="0999 999 999",
            shop_district="TP. Thủ Đức",
        )
        self.assertFalse(matched_fake)

    def test_07_export_json_and_csv_schemas(self):
        """Test output generation for JSON, standard CSV, and Google Sheets format."""
        record = ImageAuditRecord(
            shop_id=1,
            shop_name="Điện Thoại Vui - 136 Nguyễn Thái Học",
            shop_address="136 Nguyễn Thái Học, P. Phạm Ngũ Lão, Q.1",
            website="https://dienthoaivui.com.vn",
            image_url="https://dienthoaivui.com.vn/uploads/branch-136.webp",
            source_page_url="https://dienthoaivui.com.vn/he-thong-cua-hang",
            image_type=ImageType.STOREFRONT,
            verification_status=VerificationStatus.VERIFIED,
            license_status=LicenseStatus.UNKNOWN,
            branch_match_evidence="Khớp số nhà 136 và đường Nguyễn Thái Học",
            checked_at="2026-10-11T20:00:00Z",
            error=None,
            map_url="https://maps.google.com/?q=1",
            stt=1,
        )

        with tempfile.TemporaryDirectory() as tmpdir:
            json_p = Path(tmpdir) / "candidates.json"
            csv_p = Path(tmpdir) / "candidates.csv"
            sheets_p = Path(tmpdir) / "candidates-sheets.csv"

            export_candidates_json([record], json_p)
            export_candidates_csv([record], csv_p)
            export_sheets_csv([record], sheets_p)

            # Verify JSON
            loaded_json = json.loads(json_p.read_text(encoding="utf-8"))
            self.assertEqual(len(loaded_json), 1)
            self.assertEqual(loaded_json[0]["shop_id"], 1)
            self.assertEqual(loaded_json[0]["verification_status"], "verified")

            # Verify standard CSV
            with open(csv_p, "r", encoding="utf-8") as f:
                reader = list(csv.DictReader(f))
                self.assertEqual(len(reader), 1)
                self.assertEqual(reader[0]["shop_name"], "Điện Thoại Vui - 136 Nguyễn Thái Học")
                self.assertEqual(reader[0]["license_status"], "unknown")

            # Verify Sheets CSV headers
            with open(sheets_p, "r", encoding="utf-8") as f:
                reader = list(csv.DictReader(f))
                self.assertEqual(len(reader), 1)
                self.assertIn("Tên cửa hàng", reader[0])
                self.assertIn("Google Maps URL", reader[0])
                self.assertIn("Bằng chứng chi nhánh", reader[0])

    def test_08_summary_report_generation(self):
        """Test summary report text formatting."""
        r1 = ImageAuditRecord(
            shop_id=1, shop_name="S1", shop_address="A1", website="https://s1.vn",
            image_url="https://s1.vn/img.jpg", image_type=ImageType.STOREFRONT,
            verification_status=VerificationStatus.VERIFIED, license_status=LicenseStatus.UNKNOWN
        )
        r2 = ImageAuditRecord(
            shop_id=2, shop_name="S2", shop_address="A2", website="",
            image_url=None, image_type=ImageType.NONE,
            verification_status=VerificationStatus.NOT_FOUND, license_status=LicenseStatus.UNKNOWN
        )
        report = generate_summary_report([r1, r2])
        self.assertIn("Tổng số cửa hàng khảo sát: 2", report)
        self.assertIn("Verified", report)
        self.assertIn("Storefront", report)

    def test_09_checkpoint_manager_idempotence(self):
        """Test checkpoint saving and resuming without data loss."""
        with tempfile.TemporaryDirectory() as tmpdir:
            chk_path = Path(tmpdir) / ".checkpoint.json"
            mgr = CheckpointManager(chk_path)
            self.assertEqual(mgr.load(), {})

            rec = ImageAuditRecord(
                shop_id=10, shop_name="S10", shop_address="A10", website="https://s10.vn",
                verification_status=VerificationStatus.VERIFIED, image_type=ImageType.STOREFRONT,
            )
            mgr.save({10: rec})

            loaded = mgr.load()
            self.assertIn(10, loaded)
            self.assertEqual(loaded[10]["shop_name"], "S10")


if __name__ == "__main__":
    unittest.main(verbosity=2)
