#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
FixNear RepairAtlas — Catalog Validator
Kiểm định 100% tính toàn vẹn của danh mục:
1. Schema & Tính duy nhất của Model ID
2. Độ bao phủ giá (0 model thiếu giá)
3. Sanity Check (Phát hiện các tổ hợp pan bệnh vô lý)
"""

import sys
import os
import json
import argparse
from pathlib import Path

# Force UTF-8 output on Windows
if sys.platform == "win32":
    sys.stdout.reconfigure(encoding="utf-8")

SCRIPT_DIR = Path(__file__).resolve().parent
ROOT_DIR = SCRIPT_DIR.parent
DATA_DIR = ROOT_DIR / "data"
CATALOG_DIR = DATA_DIR / "catalog"

from pricing_engine import PricingEngine


class CatalogValidator:
    REQUIRED_MODEL_FIELDS = [
        "id", "name", "brand", "deviceType", "tier",
        "releaseYear", "specs", "supportedFaults", "knownIssues"
    ]
    VALID_SEVERITIES = {"critical", "high", "medium", "low"}
    VALID_CONFIDENCES = {"high", "medium", "verify"}

    def __init__(self, data_dir=DATA_DIR):
        self.data_dir = Path(data_dir)
        self.catalog_dir = self.data_dir / "catalog"
        self.pricing_engine = PricingEngine(data_dir=self.data_dir)
        self.known_fault_ids = set(self.pricing_engine.faults.keys())
        self.seen_model_ids = {}
        self.errors = []
        self.warnings = []
        self.stats = {
            "total_files": 0,
            "total_models": 0,
            "models_by_category": {},
            "total_fault_links": 0,
            "total_known_issues": 0
        }

    def validate_known_issue(self, model_id: str, idx: int, issue: dict):
        required = ["id", "title", "severity", "confidence", "symptoms", "solution"]
        for field in required:
            if field not in issue or not str(issue[field]).strip():
                self.errors.append(f"Model [{model_id}] knownIssues[{idx}] thiếu trường bắt buộc: '{field}'")

        sev = issue.get("severity")
        if sev and sev not in self.VALID_SEVERITIES:
            self.errors.append(f"Model [{model_id}] knownIssues[{idx}] có severity không hợp lệ: '{sev}'")

        conf = issue.get("confidence")
        if conf and conf not in self.VALID_CONFIDENCES:
            self.errors.append(f"Model [{model_id}] knownIssues[{idx}] có confidence không hợp lệ: '{conf}'")

    def run_sanity_checks(self, model: dict):
        """Kiểm tra các tổ hợp vô lý vi phạm quy tắc kỹ thuật phần cứng"""
        m_id = model["id"]
        dev = model.get("deviceType", "")
        faults = set(model.get("supportedFaults", []))
        name = model.get("name", "")
        specs = json.dumps(model.get("specs", {}), ensure_ascii=False)

        # Sanity 1: Apple Silicon Macs không có nâng RAM / SSD rời
        if dev == "macbook" or (dev == "pc_desktop" and "apple" in model.get("brand", "").lower()):
            chip_desc = str(model.get("specs", {}).get("chip", "")) + " " + name
            import re
            is_apple_silicon = bool(re.search(r'\bM[1-4](\s+Pro|\s+Max|\s+Ultra)?\b', chip_desc, re.IGNORECASE))
            if is_apple_silicon:
                if "ram-upgrade" in faults:
                    self.errors.append(f"[SANITY VÔ LÝ] Model Apple Silicon [{m_id}] ({name}) không thể có lỗi 'ram-upgrade' (Unified Memory tích hợp)")
                if "ssd-upgrade" in faults:
                    self.errors.append(f"[SANITY VÔ LÝ] Model Apple Silicon [{m_id}] ({name}) không thể có lỗi 'ssd-upgrade' (NAND hàn bo mạch)")

        # Sanity 2: PC Desktop không thể có pin (battery)
        if dev == "pc_desktop":
            if "battery" in faults:
                self.errors.append(f"[SANITY VÔ LÝ] Máy tính để bàn PC [{m_id}] không thể có lỗi 'battery'")

        # Sanity 3: Smartwatch không thể có bàn phím, trackpad, ram, ssd, bản lề
        if dev == "smartwatch":
            invalid_sw = {"keyboard", "trackpad", "ram-upgrade", "ssd-upgrade", "hinge-body", "foldable-cable"}
            intersection = faults.intersection(invalid_sw)
            if intersection:
                self.errors.append(f"[SANITY VÔ LÝ] Đồng hồ [{m_id}] có các lỗi không áp dụng: {list(intersection)}")

        # Sanity 4: Điện thoại thường không thể có cáp gập bản lề (foldable-cable) trừ khi là P5
        if dev == "phone" and "foldable-cable" in faults and model.get("tier") != "P5":
            self.errors.append(f"[SANITY VÔ LÝ] Model điện thoại thường [{m_id}] không ở tier P5 nhưng có lỗi 'foldable-cable'")

        # Sanity 5: Máy không phải Apple Watch / Galaxy Watch xoay không thể có digital-crown
        if "digital-crown" in faults and dev != "smartwatch":
            self.errors.append(f"[SANITY VÔ LÝ] Thiết bị [{m_id}] không phải Smartwatch nhưng có lỗi 'digital-crown'")

    def validate_model(self, file_path: Path, model: dict, idx: int, verbose: bool = False):
        m_id = model.get("id")
        if not m_id:
            self.errors.append(f"File [{file_path.name}] model thứ {idx} thiếu trường 'id'")
            return

        # Check unique ID
        if m_id in self.seen_model_ids:
            prev_file = self.seen_model_ids[m_id]
            self.errors.append(f"Trùng lặp Model ID [{m_id}] giữa file '{file_path.name}' và '{prev_file}'")
        else:
            self.seen_model_ids[m_id] = file_path.name

        # Check required fields
        for field in self.REQUIRED_MODEL_FIELDS:
            if field not in model:
                self.errors.append(f"Model [{m_id}] thiếu trường bắt buộc: '{field}'")

        # Check deviceType and tier
        dev_type = model.get("deviceType")
        tier = model.get("tier")
        brand = model.get("brand")

        if dev_type not in self.pricing_engine.tier_matrix:
            self.errors.append(f"Model [{m_id}] có deviceType không hợp lệ: '{dev_type}'")
        elif tier not in self.pricing_engine.tier_matrix[dev_type]:
            self.errors.append(f"Model [{m_id}] có tier '{tier}' không tồn tại trong tier-matrix của '{dev_type}'")

        # Check supportedFaults
        faults = model.get("supportedFaults", [])
        if not isinstance(faults, list) or len(faults) == 0:
            self.errors.append(f"Model [{m_id}] danh sách 'supportedFaults' bị rỗng hoặc không phải list")
        else:
            for f_id in faults:
                if f_id not in self.known_fault_ids:
                    self.errors.append(f"Model [{m_id}] có lỗi không xác định trong faults.json: '{f_id}'")
                else:
                    # Check pricing completeness for all 3 grades
                    for grade in ["standard", "oem", "genuine"]:
                        price = self.pricing_engine.calculate_price(dev_type, tier, brand, f_id, grade, m_id)
                        if price is None:
                            self.errors.append(f"Model [{m_id}] THIẾU GIÁ: không tính được giá cho lỗi '{f_id}', cấp '{grade}'")
                        elif price["min"] > price["max"]:
                            self.errors.append(f"Model [{m_id}] GIÁ SAI LỆCH: lỗi '{f_id}' cấp '{grade}' có min ({price['min']}) > max ({price['max']})")

            self.stats["total_fault_links"] += len(faults)

        # Check knownIssues
        known_issues = model.get("knownIssues", [])
        if not isinstance(known_issues, list) or len(known_issues) == 0:
            self.warnings.append(f"Model [{m_id}] chưa có lỗi đặc thù 'knownIssues'")
        else:
            for ki_idx, issue in enumerate(known_issues):
                self.validate_known_issue(m_id, ki_idx, issue)
            self.stats["total_known_issues"] += len(known_issues)

        # Run Sanity Checks
        self.run_sanity_checks(model)

        self.stats["total_models"] += 1
        cat = model.get("deviceType", "unknown")
        self.stats["models_by_category"][cat] = self.stats["models_by_category"].get(cat, 0) + 1

        if verbose:
            print(f"  [OK] Model: {m_id:35} | Tier: {tier:4} | Lỗi: {len(faults):2} | Pan đặc thù: {len(known_issues):2}")

    def validate_catalog(self, target_category: str = None, verbose: bool = False) -> bool:
        """Quét và kiểm định toàn bộ danh mục trong thư mục catalog"""
        if not self.catalog_dir.exists():
            self.errors.append(f"Thư mục catalog không tồn tại: {self.catalog_dir}")
            return False

        categories = [d for d in self.catalog_dir.iterdir() if d.is_dir()]
        if target_category:
            categories = [d for d in categories if d.name == target_category]
            if not categories:
                self.errors.append(f"Không tìm thấy thư mục danh mục: '{target_category}'")
                return False

        for cat_dir in sorted(categories):
            json_files = sorted(list(cat_dir.glob("*.json")))
            if verbose:
                print(f"\n📂 Đang quét phân hệ: {cat_dir.name} ({len(json_files)} files)")

            for jf in json_files:
                self.stats["total_files"] += 1
                try:
                    with open(jf, "r", encoding="utf-8") as f:
                        data = json.load(f)
                except Exception as e:
                    self.errors.append(f"Không thể đọc JSON file [{jf.name}]: {e}")
                    continue

                if not isinstance(data, list):
                    self.errors.append(f"File [{jf.name}] cấu trúc gốc phải là một mảng JSON (Array of models)")
                    continue

                for idx, model in enumerate(data):
                    self.validate_model(jf, model, idx, verbose=verbose)

        return len(self.errors) == 0

    def print_report(self):
        print("\n" + "=" * 70)
        print("  BÁO CÁO KIỂM ĐỊNH FIXNEAR REPAIRATLAS CATALOG")
        print("=" * 70)
        print(f"Tổng số file JSON quét được: {self.stats['total_files']}")
        print(f"Tổng số Model nạp thành công: {self.stats['total_models']}")
        print(f"Tổng số liên kết pan bệnh:   {self.stats['total_fault_links']}")
        print(f"Tổng số Pan bệnh đặc thù:    {self.stats['total_known_issues']}")
        print("-" * 70)
        print("Phân bố Model theo nhóm thiết bị:")
        for cat, cnt in sorted(self.stats["models_by_category"].items()):
            print(f"  • {cat:15}: {cnt:3} models")
        print("-" * 70)

        if self.warnings:
            print(f"\n⚠️ CẢNH BÁO ({len(self.warnings)}):")
            for w in self.warnings[:10]:
                print(f"  - {w}")
            if len(self.warnings) > 10:
                print(f"  ... và {len(self.warnings) - 10} cảnh báo khác.")

        if self.errors:
            print(f"\n❌ LỖI NGHIÊM TRỌNG ({len(self.errors)}):")
            for err in self.errors:
                print(f"  - {err}")
            print("\n❌ KẾT QUẢ: KIỂM ĐỊNH THẤT BẠI. Cần sửa các lỗi trên trước khi tiếp tục.")
            return False
        else:
            print("\n✅ KẾT QUẢ: 100% KIỂM ĐỊNH ĐẠT YÊU CẦU!")
            print("   - Không có Model ID trùng lặp")
            print("   - 0 Model bị thiếu giá")
            print("   - Không có tổ hợp pan bệnh vô lý (Sanity checks passed)")
            return True


def main():
    parser = argparse.ArgumentParser(description="FixNear RepairAtlas Catalog Validator")
    parser.add_argument("--category", help="Chỉ kiểm định 1 nhóm: phone, win_laptop, macbook, tablet, pc_desktop, smartwatch")
    parser.add_argument("--verbose", "-v", action="store_true", help="In chi tiết từng model")
    args = parser.parse_args()

    validator = CatalogValidator()
    success = validator.validate_catalog(target_category=args.category, verbose=args.verbose)
    passed = validator.print_report()

    sys.exit(0 if (success and passed) else 1)


if __name__ == "__main__":
    main()
