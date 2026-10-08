"""Ensure published shops never advertise a logo as a branch photo."""

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
misrepresented = [shop.get("id") for shop in shops if shop.get("image") or shop.get("image_kind") != "none"]
print(f"Published shops: {len(shops)}; unsupported branch photos shown: {len(misrepresented)}")
if misrepresented:
    raise SystemExit(f"Shop IDs with unsupported photos: {misrepresented}")
