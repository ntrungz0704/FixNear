#!/usr/bin/env python3
"""Check provenance manifest, bytes and stale/mismatched brand files."""

from hashlib import sha256
from pathlib import Path
from urllib.parse import urlparse
import json
import struct
import xml.etree.ElementTree as ET

ROOT = Path(__file__).resolve().parents[1]
folder = ROOT / "public/assets/images/brands"
manifest = json.loads((ROOT / "data/brand-logo-sources.json").read_text(encoding="utf-8"))
errors = []
listed = set()
for name, item in manifest["logos"].items():
    file = item["file"]
    path = folder / file
    listed.add(file)
    if not path.is_file() or path.stem != name:
        errors.append(f"{name}: missing or mismatched file {file}")
        continue
    data = path.read_bytes()
    if sha256(data).hexdigest() != item["sha256"]:
        errors.append(f"{name}: checksum mismatch")
    if not all(urlparse(item[key]).scheme == "https" for key in ("assetUrl", "referenceUrl")):
        errors.append(f"{name}: source URL not HTTPS")
    try:
        if path.suffix == ".svg":
            root = ET.fromstring(data)
            if not root.tag.endswith("svg") or b"<script" in data.lower() or b"onload=" in data.lower():
                errors.append(f"{name}: unsafe or invalid SVG")
        elif path.suffix == ".png":
            if not data.startswith(b"\x89PNG\r\n\x1a\n") or min(struct.unpack(">II", data[16:24])) < 1:
                errors.append(f"{name}: invalid PNG")
        else:
            errors.append(f"{name}: unsupported extension")
    except (ET.ParseError, struct.error):
        errors.append(f"{name}: unreadable image")

stale = {p.name for p in folder.iterdir() if p.is_file()} - listed
if stale:
    errors.append(f"unlisted files: {', '.join(sorted(stale))}")
print(f"Audited brand marks: {len(manifest['logos'])}; stale assets: {len(stale)}; errors: {len(errors)}")
for error in errors:
    print("ERROR:", error)
raise SystemExit(1 if errors else 0)
