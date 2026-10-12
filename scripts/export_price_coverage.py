#!/usr/bin/env python3
"""Export every model/fault pair, separating forecast coverage from source coverage."""
import csv
import json
from collections import Counter
from pathlib import Path

from pricing_engine import PricingEngine

ROOT = Path(__file__).resolve().parents[1]
OUT = ROOT / "outputs" / "PRICE_COVERAGE_LATEST.csv"
quotes = json.loads((ROOT / "data" / "pricing" / "source-quotes.json").read_text(encoding="utf-8"))
by_pair = Counter((item["modelId"], item["faultId"]) for item in quotes)
engine = PricingEngine(data_dir=ROOT / "data")
fields = ["model_id", "model_name", "device_type", "device_brand", "fault_id", "fault_name",
          "source_listing_count", "source_status", "estimate_low_min_vnd", "estimate_low_max_vnd",
          "estimate_mid_min_vnd", "estimate_mid_max_vnd", "estimate_high_min_vnd", "estimate_high_max_vnd"]
rows = []
for path in sorted((ROOT / "data" / "catalog").glob("*/*.json")):
    for model in json.loads(path.read_text(encoding="utf-8")):
        for fault_id in model.get("supportedFaults", []):
            row = {
                "model_id": model["id"], "model_name": model["name"],
                "device_type": model["deviceType"], "device_brand": model["brand"],
                "fault_id": fault_id, "fault_name": engine.faults[fault_id]["name"],
                "source_listing_count": by_pair[(model["id"], fault_id)],
                "source_status": "SOURCE_LISTED" if by_pair[(model["id"], fault_id)] else "NO_SOURCE",
            }
            for grade, label in (("standard", "low"), ("oem", "mid"), ("genuine", "high")):
                estimate = engine.calculate_price(model["deviceType"], model["tier"], model["brand"],
                                                  fault_id, grade, model["id"])
                row[f"estimate_{label}_min_vnd"] = estimate["min"] if estimate else ""
                row[f"estimate_{label}_max_vnd"] = estimate["max"] if estimate else ""
            rows.append(row)

OUT.parent.mkdir(exist_ok=True)
with OUT.open("w", encoding="utf-8-sig", newline="") as handle:
    writer = csv.DictWriter(handle, fieldnames=fields)
    writer.writeheader()
    writer.writerows(rows)
print(f"{len(rows)} model/fault pairs; {sum(row['source_status'] == 'SOURCE_LISTED' for row in rows)} with source listings; {OUT}")
