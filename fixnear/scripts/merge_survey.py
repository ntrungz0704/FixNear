#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
FixNear RepairAtlas — Survey Data Merger
Hợp nhất dữ liệu báo giá khảo sát thực tế từ file CSV vào data/pricing/overrides.json
"""

import sys
import os
import json
import csv
import argparse
from pathlib import Path

# Force UTF-8 output on Windows
if sys.platform == "win32":
    sys.stdout.reconfigure(encoding="utf-8")

SCRIPT_DIR = Path(__file__).resolve().parent
ROOT_DIR = SCRIPT_DIR.parent
DATA_DIR = ROOT_DIR / "data"
OVERRIDES_FILE = DATA_DIR / "pricing" / "overrides.json"
CSV_TEMPLATE_FILE = SCRIPT_DIR / "survey_prices_template.csv"


def export_template(target_path: Path):
    """Xuất file CSV mẫu để đội ngũ khảo sát nhập liệu giá thực tế từ các cửa hàng"""
    fieldnames = [
        "model_id",
        "fault_id",
        "grade",
        "min_price",
        "max_price",
        "turnaround",
        "warranty_months",
        "note"
    ]
    sample_rows = [
        {
            "model_id": "apple-iphone-13-pro-max",
            "fault_id": "screen",
            "grade": "genuine",
            "min_price": 6500000,
            "max_price": 7800000,
            "turnaround": "1 - 2 ngày",
            "warranty_months": 12,
            "note": "Màn zin bóc máy chính hãng Apple 100%, fix TrueTone"
        },
        {
            "model_id": "apple-iphone-13-pro-max",
            "fault_id": "face-id",
            "grade": "standard",
            "min_price": 850000,
            "max_price": 1200000,
            "turnaround": "2 - 4 giờ",
            "warranty_months": 6,
            "note": "Hàn cáp Dot Projector cứu Face ID"
        },
        {
            "model_id": "samsung-galaxy-z-fold-5",
            "fault_id": "foldable-cable",
            "grade": "standard",
            "min_price": 1800000,
            "max_price": 2400000,
            "turnaround": "3 - 5 giờ",
            "warranty_months": 6,
            "note": "Hàn nối câu cáp bản lề gập mất âm thanh"
        }
    ]

    with open(target_path, "w", newline="", encoding="utf-8-sig") as f:
        writer = csv.DictWriter(f, fieldnames=fieldnames)
        writer.writeheader()
        writer.writerows(sample_rows)

    print(f"✅ Đã tạo file CSV mẫu tại: {target_path}")


def merge_survey_csv(csv_path: Path, dry_run: bool = False):
    """Đọc file CSV khảo sát và sáp nhập vào overrides.json"""
    if not csv_path.exists():
        print(f"❌ Không tìm thấy file: {csv_path}")
        return False

    with open(OVERRIDES_FILE, "r", encoding="utf-8") as f:
        overrides = json.load(f)

    if "models" not in overrides:
        overrides["models"] = {}

    merged_count = 0
    with open(csv_path, "r", encoding="utf-8-sig") as f:
        reader = csv.DictReader(f)
        for row in reader:
            m_id = row.get("model_id", "").strip()
            f_id = row.get("fault_id", "").strip()
            grade = row.get("grade", "").strip().lower()

            if not m_id or not f_id or not grade:
                continue

            try:
                min_p = int(float(row.get("min_price", 0)))
                max_p = int(float(row.get("max_price", 0)))
            except ValueError:
                print(f"⚠️ Bỏ qua dòng có giá không hợp lệ: {row}")
                continue

            turnaround = row.get("turnaround", "30 - 60 phút").strip()
            try:
                w_months = int(float(row.get("warranty_months", 6)))
            except ValueError:
                w_months = 6
            note = row.get("note", "").strip()

            if m_id not in overrides["models"]:
                overrides["models"][m_id] = {}
            if f_id not in overrides["models"][m_id]:
                overrides["models"][m_id][f_id] = {}

            overrides["models"][m_id][f_id][grade] = {
                "min": min_p,
                "max": max_p,
                "turnaround": turnaround,
                "warrantyMonths": w_months,
                "note": note
            }
            merged_count += 1

    if dry_run:
        print(f"🔍 [DRY-RUN] Sẽ hợp nhất {merged_count} bản ghi giá vào overrides.json (chưa ghi đè file).")
    else:
        with open(OVERRIDES_FILE, "w", encoding="utf-8") as f:
            json.dump(overrides, f, ensure_ascii=False, indent=2)
        print(f"✅ Đã hợp nhất thành công {merged_count} bản ghi giá vào {OVERRIDES_FILE.name}!")

    return True


def main():
    parser = argparse.ArgumentParser(description="FixNear RepairAtlas Survey Data Merger")
    parser.add_argument("--export-template", action="store_true", help="Xuất file CSV mẫu khảo sát giá")
    parser.add_argument("--csv", help="Đường dẫn file CSV chứa giá khảo sát cần sáp nhập")
    parser.add_argument("--dry-run", action="store_true", help="Chạy thử không ghi vào file overrides.json")

    args = parser.parse_args()

    if args.export_template:
        export_template(CSV_TEMPLATE_FILE)
        return

    if args.csv:
        merge_survey_csv(Path(args.csv), dry_run=args.dry_run)
    else:
        print("Vui lòng chỉ định --csv <đường-dẫn> hoặc --export-template")
        parser.print_help()


if __name__ == "__main__":
    main()
