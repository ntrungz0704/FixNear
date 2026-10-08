#!/usr/bin/env python3
"""Compatibility entry point for the audited 2026 brand asset sync.

The old Wikipedia search downloader could silently pick unrelated pictures.
Use the pinned/official source manifest instead.
"""

from pathlib import Path
import runpy


if __name__ == "__main__":
    runpy.run_path(str(Path(__file__).resolve().parents[1] / "scripts/sync_brand_logos.py"), run_name="__main__")
