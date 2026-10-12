#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
FixNear RepairAtlas — Pricing Engine
Tính toán ma trận giá linh kiện 3 cấp (Standard, OEM, Genuine) dựa trên:
Base Tier Matrix × Brand Factor × Grade Factor (+ Overrides)
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
PRICING_DIR = DATA_DIR / "pricing"


class PricingEngine:
    def __init__(self, data_dir=DATA_DIR):
        self.data_dir = Path(data_dir)
        self.pricing_dir = self.data_dir / "pricing"
        self._load_data()

    def _load_data(self):
        with open(self.pricing_dir / "tier-matrix.json", "r", encoding="utf-8") as f:
            self.tier_matrix = json.load(f)

        with open(self.pricing_dir / "brand-factors.json", "r", encoding="utf-8") as f:
            self.brand_factors = json.load(f)

        with open(self.pricing_dir / "grade-factors.json", "r", encoding="utf-8") as f:
            self.grade_factors = json.load(f)

        with open(self.pricing_dir / "overrides.json", "r", encoding="utf-8") as f:
            self.overrides = json.load(f)

        with open(self.data_dir / "faults.json", "r", encoding="utf-8") as f:
            faults_list = json.load(f)
            self.faults = {item["id"]: item for item in faults_list}

    @staticmethod
    def round_price(val: float) -> int:
        """
        Làm tròn giá tiền VND theo quy tắc thực tế tại TP.HCM:
        - Giá < 1,000,000 VND: làm tròn bước 10,000 VND
        - Giá >= 1,000,000 VND: làm tròn bước 50,000 VND
        """
        if val <= 0:
            return 0
        if val < 1_000_000:
            return int(round(val / 10000.0) * 10000)
        else:
            return int(round(val / 50000.0) * 50000)

    @staticmethod
    def format_price(vnd: int) -> str:
        """Định dạng số tiền hiển thị theo chuẩn Việt Nam, ví dụ 1.250.000đ"""
        if vnd == 0:
            return "Miễn phí (0đ)"
        return f"{vnd:,.0f}đ".replace(",", ".")

    def calculate_price(
        self,
        device_type: str,
        tier: str,
        brand: str,
        fault_id: str,
        grade: str = "standard",
        model_id: str = None,
    ) -> dict:
        """
        Tính giá sửa chữa cho một pan bệnh cụ thể theo cấp linh kiện.
        Trả về dict { min, max, turnaround, warrantyMonths, grade, gradeName, isOverride, note }
        """
        # 1. Kiểm tra Override thủ công trước
        if model_id and "models" in self.overrides:
            model_overrides = self.overrides["models"].get(model_id, {})
            if fault_id in model_overrides and grade in model_overrides[fault_id]:
                ov = model_overrides[fault_id][grade]
                return {
                    "min": ov["min"],
                    "max": ov["max"],
                    "turnaround": ov.get("turnaround", "30 - 60 phút"),
                    "warrantyMonths": ov.get("warrantyMonths", 6),
                    "grade": grade,
                    "gradeName": self.grade_factors.get(grade, {}).get("name", grade),
                    "isOverride": True,
                    "note": ov.get("note", "Báo giá khảo sát thực tế"),
                }

        # 2. Tìm trong Tier Matrix
        device_matrix = self.tier_matrix.get(device_type, {})
        tier_data = device_matrix.get(tier, {})
        faults_in_tier = tier_data.get("faults", {})

        base = faults_in_tier.get(fault_id)
        if not base and fault_id in self.faults:
            # Tra lỗi cha nếu lỗi hiện tại là sub-fault
            parent_id = self.faults[fault_id].get("parentFaultId")
            if parent_id and parent_id in faults_in_tier:
                base = faults_in_tier[parent_id]

        if not base:
            return None

        # 3. Nhân hệ số Hãng và Linh kiện
        brand_factor = self.brand_factors.get(brand.lower(), 1.0)
        grade_info = self.grade_factors.get(grade, {"factor": 1.0, "warrantyMultiplier": 1.0})
        grade_factor = grade_info.get("factor", 1.0)
        warranty_mult = grade_info.get("warrantyMultiplier", 1.0)

        # Trường hợp kiểm tra chẩn đoán 0đ
        if base.get("min", 0) == 0 and base.get("max", 0) == 0:
            min_vnd = 0
            max_vnd = 0
            warranty_months = 0
        else:
            raw_min = base["min"] * brand_factor * grade_factor
            raw_max = base["max"] * brand_factor * grade_factor
            min_vnd = self.round_price(raw_min)
            max_vnd = self.round_price(raw_max)
            if min_vnd > max_vnd:
                max_vnd = min_vnd
            base_warranty = base.get("warrantyMonths", 3)
            warranty_months = max(1, min(24, int(round(base_warranty * warranty_mult))))

        return {
            "min": min_vnd,
            "max": max_vnd,
            "turnaround": base.get("turnaround", "30 - 60 phút"),
            "warrantyMonths": warranty_months,
            "grade": grade,
            "gradeName": grade_info.get("name", grade),
            "isOverride": False,
            "note": "",
        }

    def get_model_prices(
        self,
        device_type: str,
        tier: str,
        brand: str,
        supported_faults: list,
        model_id: str = None,
    ) -> dict:
        """
        Tính toàn bộ ma trận giá 3 cấp (standard, oem, genuine) cho tất cả supportedFaults của model.
        """
        result = {}
        grades = ["standard", "oem", "genuine"]

        for fault_id in supported_faults:
            grade_dict = {}
            for grade in grades:
                p = self.calculate_price(device_type, tier, brand, fault_id, grade, model_id)
                if p:
                    grade_dict[grade] = p
            if grade_dict:
                result[fault_id] = grade_dict
        return result


def main():
    parser = argparse.ArgumentParser(description="FixNear RepairAtlas Pricing Engine CLI")
    parser.add_argument("--device", help="Loại thiết bị: phone, win_laptop, macbook, tablet, pc_desktop, smartwatch")
    parser.add_argument("--tier", help="Hạng máy: P1-P5, L1-L4, MAC1-MAC4, T1-T3, D1-D3, W1-W3")
    parser.add_argument("--brand", help="Hãng sản xuất: apple, samsung, xiaomi, dell, asus...")
    parser.add_argument("--fault", help="Mã pan bệnh: screen, battery, glass-press, face-id...")
    parser.add_argument("--grade", default="all", choices=["standard", "oem", "genuine", "all"], help="Cấp linh kiện")
    parser.add_argument("--model", help="Mã model cụ thể (để áp dụng overrides nếu có)")
    parser.add_argument("--test", action="store_true", help="Chạy bộ kiểm thử tự động (Unit Test)")

    args = parser.parse_args()
    engine = PricingEngine()

    if args.test:
        print("=== Chạy Unit Test Cho Pricing Engine ===")
        # Test 1: Rounding
        assert PricingEngine.round_price(456789) == 460000, "Làm tròn <1tr thất bại"
        assert PricingEngine.round_price(1234567) == 1250000, "Làm tròn >=1tr thất bại"
        assert PricingEngine.round_price(0) == 0, "Làm tròn 0đ thất bại"
        print("  [PASS] Quy tắc làm tròn (10k / 50k)")

        # Test 2: iPhone 13 Pro Max Screen (Override)
        res_ov = engine.calculate_price("phone", "P4", "apple", "screen", "genuine", "apple-iphone-13-pro-max")
        assert res_ov["isOverride"] is True, "Phải nhận diện override cho iPhone 13 Pro Max"
        assert res_ov["min"] == 6500000, f"Giá min override sai: {res_ov['min']}"
        print("  [PASS] Nhận diện Override thủ công (iPhone 13 Pro Max)")

        # Test 3: Standard formula (Xiaomi P2 screen)
        res_p2 = engine.calculate_price("phone", "P2", "xiaomi", "screen", "standard")
        assert res_p2 is not None, "Tính giá Xiaomi P2 không được rỗng"
        assert res_p2["min"] <= res_p2["max"], "min phải nhỏ hơn hoặc bằng max"
        print(f"  [PASS] Công thức chuẩn: Xiaomi P2 Screen Standard: {res_p2['min']:,}đ - {res_p2['max']:,}đ")

        # Test 4: Diagnostic 0đ
        res_diag = engine.calculate_price("win_laptop", "L3", "asus", "diagnostic", "standard")
        assert res_diag["min"] == 0 and res_diag["max"] == 0, "Chẩn đoán phải luôn là 0đ"
        print("  [PASS] Chẩn đoán toàn diện luôn là 0đ")

        print("=== 100% UNIT TEST PASSED ===")
        return

    if not args.device or not args.tier or not args.brand or not args.fault:
        print("Vui lòng cung cấp đủ --device --tier --brand --fault (hoặc dùng --test để chạy kiểm thử)")
        parser.print_help()
        return

    grades = ["standard", "oem", "genuine"] if args.grade == "all" else [args.grade]
    print(f"Báo giá ước tính: {args.device.upper()} | {args.brand.upper()} | Tier {args.tier} | Lỗi: {args.fault}")
    print("-" * 65)
    for g in grades:
        price = engine.calculate_price(args.device, args.tier, args.brand, args.fault, g, args.model)
        if price:
            tag = "[OVERRIDE]" if price["isOverride"] else "[MATRIX]"
            print(f"{tag} {price['gradeName']}:")
            print(f"   Khoảng giá: {PricingEngine.format_price(price['min'])} - {PricingEngine.format_price(price['max'])}")
            print(f"   Thời gian: {price['turnaround']} | Bảo hành: {price['warrantyMonths']} tháng")
            if price.get("note"):
                print(f"   Ghi chú: {price['note']}")
        else:
            print(f"Không tìm thấy bảng giá cho lỗi: {args.fault} cấp {g}")


if __name__ == "__main__":
    main()
