#!/usr/bin/env python3
"""Remove device/fault links whose repair description does not apply to that device."""
import argparse
import json
from collections import Counter
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
parser = argparse.ArgumentParser()
parser.add_argument("--apply", action="store_true", help="write normalized catalog files")
args = parser.parse_args()

removed = Counter()
changed_files = 0
for path in sorted((ROOT / "data" / "catalog").glob("*/*.json")):
    models = json.loads(path.read_text(encoding="utf-8"))
    changed = False
    for model in models:
        device = model["deviceType"]
        allowed = []
        for fault in model.get("supportedFaults", []):
            invalid = (
                (device == "phone" and fault in {"thermal", "software"})
                or (device == "phone" and fault == "hinge-body" and model["tier"] != "P5")
                or (device == "tablet" and fault in {"thermal", "software"})
                or (device == "tablet" and fault == "hinge-body" and "surface" not in model["name"].lower())
                or (device == "smartwatch" and fault == "software")
                or (device == "smartwatch" and model["brand"].lower() != "apple"
                    and fault in {"digital-crown", "taptic-engine"})
            )
            if invalid:
                removed[(device, fault)] += 1
                changed = True
            else:
                allowed.append(fault)
        model["supportedFaults"] = allowed
    if changed:
        changed_files += 1
        if args.apply:
            path.write_text(json.dumps(models, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")

print(f"Files requiring correction: {changed_files}; invalid links: {sum(removed.values())}")
for (device, fault), count in sorted(removed.items()):
    print(f"  {device}/{fault}: {count}")
if not args.apply and changed_files:
    print("Run with --apply to update catalog files.")
