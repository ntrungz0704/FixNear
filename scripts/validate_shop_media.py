"""Ensure every displayed shop photo has matching branch evidence."""

from pathlib import Path
import json
import os
import subprocess


root = Path(__file__).resolve().parents[1]
environment = os.environ.copy()
environment["FIXNEAR_DISABLE_MYSQL"] = "1"
payload = subprocess.check_output(
    ["php", "-r", "require 'config/db.php'; echo json_encode(db()->getShops(), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);"],
    cwd=root,
    env=environment,
    text=True,
    encoding="utf-8",
)
shops = json.loads(payload)
evidence = {
    item["shop_id"]: item
    for item in json.loads((root / "data" / "shop-image-candidates.json").read_text(encoding="utf-8"))
}
misrepresented = []
missing = []
shown = 0
for shop in shops:
    photo = evidence.get(shop["id"], {})
    expected = (
        photo.get("verification_status") == "verified"
        and photo.get("image_type") in {"storefront", "branch_representative"}
        and photo.get("shop_name") == shop["name"]
        and photo.get("shop_address") == shop["address"]
        and photo.get("image_url") == shop.get("raw_image")
    )
    if shop.get("image"):
        shown += 1
        if not expected or shop["image"] != photo["image_url"]:
            misrepresented.append(shop["id"])
    elif shop.get("image_kind") != "none":
        misrepresented.append(shop["id"])
    elif expected:
        missing.append(shop["id"])
print(f"Published shops: {len(shops)}; evidenced branch photos shown: {shown}; unsupported photos shown: {len(misrepresented)}; missing approved photos: {len(missing)}")
if misrepresented or missing:
    raise SystemExit(f"Unsupported photo IDs: {misrepresented}; missing approved photo IDs: {missing}")
