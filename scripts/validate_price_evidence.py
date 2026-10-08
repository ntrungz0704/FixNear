#!/usr/bin/env python3
"""Validate exact provider listings without treating model estimates as sourced prices."""
import json
from datetime import date
from pathlib import Path
from urllib.parse import urlparse

ROOT = Path(__file__).resolve().parents[1]
catalog = {}
for path in (ROOT / "data" / "catalog").glob("*/*.json"):
    for model in json.loads(path.read_text(encoding="utf-8")):
        catalog[model["id"]] = set(model.get("supportedFaults", []))

quotes = json.loads((ROOT / "data" / "pricing" / "source-quotes.json").read_text(encoding="utf-8"))
errors = []
seen = set()
covered = set()
for index, quote in enumerate(quotes, start=1):
    model_id = quote.get("modelId", "")
    fault_id = quote.get("faultId", "")
    key = (model_id, fault_id, quote.get("provider"), quote.get("componentName"))
    if key in seen:
        errors.append(f"row {index}: duplicate quote {key}")
    seen.add(key)
    if fault_id not in catalog.get(model_id, set()):
        errors.append(f"row {index}: unknown model/fault {model_id}/{fault_id}")
    if not quote.get("provider") or not quote.get("componentBrand") or not quote.get("componentName"):
        errors.append(f"row {index}: missing provider or component identity")
    if type(quote.get("priceVnd")) is not int or quote["priceVnd"] <= 0:
        errors.append(f"row {index}: invalid VND price")
    if not isinstance(quote.get("warrantyMonths"), int) or not 0 <= quote["warrantyMonths"] <= 36:
        errors.append(f"row {index}: invalid listed warranty")
    parsed = urlparse(quote.get("sourceUrl", ""))
    if parsed.scheme != "https" or not parsed.netloc:
        errors.append(f"row {index}: source must be an HTTPS URL")
    try:
        checked = date.fromisoformat(quote["checkedAt"])
        if checked > date.today():
            errors.append(f"row {index}: checkedAt is in the future")
    except (KeyError, ValueError):
        errors.append(f"row {index}: invalid checkedAt date")
    covered.add((model_id, fault_id))

print(f"Models: {len(catalog)}; sourced listings: {len(quotes)}; model/fault pairs: {len(covered)}")
for error in errors:
    print(f"ERROR: {error}")
raise SystemExit(1 if errors else 0)
