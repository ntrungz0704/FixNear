#!/usr/bin/env python3
"""FIXNEAR — STORE IMAGE RESEARCH & AUTOMATION ENGINE

Automated discovery, extraction, verification and export of shop storefront
and branch representative imagery from official brand web properties.
"""

import argparse
import csv
import json
import logging
import os
import sys
from concurrent.futures import ThreadPoolExecutor, as_completed
from datetime import datetime, timezone
from pathlib import Path
from typing import List, Dict, Optional, Tuple, Any

from media_engine.models import (
    ShopItem,
    ImageAuditRecord,
    VerificationStatus,
    LicenseStatus,
    ImageType,
    RawCandidate,
)
from media_engine.fetcher import SafeHttpFetcher
from media_engine.extractor import extract_page_media
from media_engine.classifier import classify_candidate
from media_engine.matcher import evaluate_branch_match, parse_address_components
from media_engine.exporter import (
    export_candidates_json,
    export_candidates_csv,
    export_sheets_csv,
    generate_summary_report,
)


logging.basicConfig(
    level=logging.INFO,
    format="[%(asctime)s] [%(levelname)s] %(message)s",
    datefmt="%H:%M:%S",
)
logger = logging.getLogger("fixnear.image_engine")

PROJECT_ROOT = Path(__file__).resolve().parents[1]
DEFAULT_SHOPS_JSON = PROJECT_ROOT / "data" / "shops.json"
DEFAULT_OUTPUT_JSON = PROJECT_ROOT / "data" / "shop-image-candidates.json"
DEFAULT_OUTPUT_CSV = PROJECT_ROOT / "data" / "shop-image-candidates.csv"
DEFAULT_OUTPUT_SHEETS_CSV = PROJECT_ROOT / "data" / "shop-image-candidates-sheets.csv"
DEFAULT_CHECKPOINT_FILE = PROJECT_ROOT / "data" / ".shop-image-checkpoint.json"
EVIDENCE_FILE = PROJECT_ROOT / "data" / "shop-address-evidence.json"

ADDRESS_EVIDENCE = {}
if EVIDENCE_FILE.is_file():
    try:
        ADDRESS_EVIDENCE = json.loads(EVIDENCE_FILE.read_text(encoding="utf-8"))
    except Exception:
        pass

# Known domain map for major verified repair chains in TP.HCM
CHAIN_DOMAIN_MAP = {
    "điện thoại vui": "https://dienthoaivui.com.vn",
    "dienthoaivui": "https://dienthoaivui.com.vn",
    "fastcare": "https://fastcare.vn",
    "viện di động": "https://viendidong.com",
    "viendidong": "https://viendidong.com",
    "bệnh viện điện thoại": "https://chamsocdidong.com",
    "24h": "https://chamsocdidong.com",
    "chamsocdidong": "https://chamsocdidong.com",
    "sài gòn số": "https://saigonso.com",
    "saigonso": "https://saigonso.com",
    "bảo hành one": "https://baohanhone.com",
    "sửa chữa laptop 24h": "https://suachualaptop24h.com",
    "suachualaptop24h": "https://suachualaptop24h.com",
    "thuận phát mobile": "https://thuanphatmobile.vn",
    "thuanphatmobile": "https://thuanphatmobile.vn",
    "cấp cứu laptop": "https://capcuulaptop.com",
    "laptopcare": "https://laptopcare.vn",
    "thành nhân tnc": "https://www.tnc.com.vn",
    "cares": "https://cares.vn",
    "icare center": "https://icare.center",
    "thế giới laptop 24h": "https://thegioilaptop24h.com",
    "bệnh viện laptop zin": "https://benhvienlaptop.com",
}


def load_shops(source_path: Path) -> List[ShopItem]:
    """Load shops from JSON or CSV file."""
    if not source_path.is_file():
        raise FileNotFoundError(f"Source file not found: {source_path}")

    shops: List[ShopItem] = []
    suffix = source_path.suffix.lower()

    if suffix == ".json":
        data = json.loads(source_path.read_text(encoding="utf-8"))
        if isinstance(data, list):
            for idx, item in enumerate(data, 1):
                if isinstance(item, dict):
                    shops.append(ShopItem.from_dict(item, default_id=idx))
    elif suffix == ".csv":
        with open(source_path, "r", encoding="utf-8", errors="replace") as f:
            reader = csv.DictReader(f)
            for idx, row in enumerate(reader, 1):
                shops.append(ShopItem.from_dict(row, default_id=idx))
    else:
        raise ValueError(f"Unsupported source format: {suffix} (expected .json or .csv)")

    return shops


def resolve_official_website(shop: ShopItem) -> str:
    """Determine the official website URL for a shop."""
    website = (shop.website or "").strip()
    if website and "google.com/maps" not in website and website.startswith(("http://", "https://")):
        return website

    # Lookup by brand name
    name_lower = shop.name.lower()
    for key, domain in CHAIN_DOMAIN_MAP.items():
        if key in name_lower:
            return domain

    return ""


def process_single_shop(shop: ShopItem, fetcher: SafeHttpFetcher) -> ImageAuditRecord:
    """Execute complete research, discovery, and verification pipeline for one shop."""
    checked_time = datetime.now(timezone.utc).isoformat()
    official_website = resolve_official_website(shop)

    if not official_website:
        return ImageAuditRecord(
            shop_id=shop.shop_id,
            shop_name=shop.name,
            shop_address=shop.address,
            website="",
            map_url=shop.map_url,
            stt=shop.stt,
            verification_status=VerificationStatus.NOT_FOUND,
            license_status=LicenseStatus.UNKNOWN,
            image_type=ImageType.NONE,
            branch_match_evidence="Không có website chính thức hoặc nguồn đối chiếu",
            checked_at=checked_time,
            error="Missing official website",
        )

    logger.info("Auditing [%d] %s -> %s", shop.shop_id, shop.name, official_website)

    # Look up verified branch source from address evidence
    verified_sources = ADDRESS_EVIDENCE.get("sources", {})
    shop_source_key = ADDRESS_EVIDENCE.get("shops", {}).get(str(shop.shop_id), "")
    verified_branch_url = verified_sources.get(shop_source_key, "")

    # Target URLs to explore
    urls_to_explore = []
    if verified_branch_url:
        urls_to_explore.append(verified_branch_url)
    urls_to_explore.append(official_website)

    all_candidates: List[Tuple[RawCandidate, str, bool, str]] = []
    # (candidate, source_page, is_page_matched, match_evidence)

    home_matched = False
    home_evidence = ""

    for explore_url in urls_to_explore:
        html, final_url, err = fetcher.fetch_html(explore_url)
        if not html:
            continue
        page_url = final_url or explore_url
        cands, branch_links, page_text = extract_page_media(html, page_url)
        is_matched, evid_text, _ = evaluate_branch_match(
            page_text, shop.address, shop.phone, shop.district
        )
        if is_matched:
            home_matched = True
            home_evidence = f"Trang chi nhánh ({page_url}): {evid_text}"

        for c in cands:
            all_candidates.append((c, page_url, is_matched, evid_text))

        # If not matched yet, check branch links
        if not home_matched and branch_links:
            addr_info = parse_address_components(shop.address)
            street_kw = addr_info["street_name"].lower()
            district_kw = (shop.district or "").lower()

            prioritized = []
            for bl in branch_links[:8]:
                bl_lower = bl.lower()
                if (street_kw and street_kw in bl_lower) or (district_kw and district_kw in bl_lower):
                    prioritized.append(bl)

            for b_url in prioritized[:2]:
                b_html, b_final, _ = fetcher.fetch_html(b_url)
                if b_html:
                    b_page = b_final or b_url
                    b_cands, _, b_text = extract_page_media(b_html, b_page)
                    b_match, b_evid, _ = evaluate_branch_match(
                        b_text, shop.address, shop.phone, shop.district
                    )
                    if b_match:
                        home_matched = True
                        home_evidence = f"Trang chi nhánh ({b_page}): {b_evid}"
                    for c in b_cands:
                        all_candidates.append((c, b_page, b_match, b_evid))

    # 4. Filter and score candidate images
    viable_candidates: List[Tuple[float, RawCandidate, str, str, str]] = []
    # (final_score, candidate, img_type, verification_status, evidence)

    for cand, page_src, page_matched, page_evid in all_candidates:
        accepted, cand_type, base_score, reason = classify_candidate(
            cand.url,
            alt_text=cand.alt_text,
            context_text=cand.context_text,
            is_og_meta=cand.is_og_meta,
            is_json_ld=cand.is_json_ld,
        )
        if not accepted:
            continue

        score = base_score
        verif_status = VerificationStatus.PENDING_REVIEW
        evidence = reason

        if page_matched:
            score += 50.0
            verif_status = VerificationStatus.VERIFIED
            evidence = f"{reason}. {home_evidence}"
        elif cand_type == ImageType.BRAND_GENERAL:
            verif_status = VerificationStatus.PENDING_REVIEW
            evidence = f"{reason}. Ảnh đại diện thương hiệu chung từ website, chưa có ảnh định danh chi nhánh."

        viable_candidates.append((score, cand, cand_type, verif_status, evidence))

    # Sort descending by score
    viable_candidates.sort(key=lambda x: x[0], reverse=True)

    # 5. Probe best candidate image
    for score, cand, cand_type, verif_status, evidence in viable_candidates:
        is_alive, meta, probe_err = fetcher.probe_image(cand.url, referer=cand.source_page_url)
        if is_alive:
            final_img_url = meta.get("final_url") or cand.url
            return ImageAuditRecord(
                shop_id=shop.shop_id,
                shop_name=shop.name,
                shop_address=shop.address,
                website=official_website,
                image_url=final_img_url,
                source_page_url=cand.source_page_url,
                image_type=cand_type,
                verification_status=verif_status,
                license_status=LicenseStatus.UNKNOWN,
                branch_match_evidence=evidence,
                checked_at=checked_time,
                error=None,
                map_url=shop.map_url,
                stt=shop.stt,
            )

    # If no candidate passed probe
    return ImageAuditRecord(
        shop_id=shop.shop_id,
        shop_name=shop.name,
        shop_address=shop.address,
        website=official_website,
        map_url=shop.map_url,
        stt=shop.stt,
        verification_status=VerificationStatus.NOT_FOUND,
        license_status=LicenseStatus.UNKNOWN,
        image_type=ImageType.NONE,
        branch_match_evidence="Không tìm thấy hình ảnh chi nhánh hợp lệ đáp ứng tiêu chuẩn",
        checked_at=checked_time,
        error="No valid image candidates found or accessible",
    )


class CheckpointManager:
    """Manages idempotent state saving and resuming."""

    def __init__(self, filepath: Path):
        self.filepath = filepath

    def load(self) -> Dict[int, Dict[str, Any]]:
        if not self.filepath.is_file():
            return {}
        try:
            raw = json.loads(self.filepath.read_text(encoding="utf-8"))
            return {int(k): v for k, v in raw.items()}
        except Exception:
            return {}

    def save(self, records: Dict[int, ImageAuditRecord]):
        try:
            self.filepath.parent.mkdir(parents=True, exist_ok=True)
            serializable = {str(k): v.to_dict() for k, v in records.items()}
            self.filepath.write_text(
                json.dumps(serializable, ensure_ascii=False, indent=2),
                encoding="utf-8",
            )
        except Exception as exc:
            logger.warning("Failed to save checkpoint: %s", exc)


def parse_args():
    parser = argparse.ArgumentParser(
        description="FixNear Store Image Research & Verification Engine"
    )
    parser.add_argument("--all", action="store_true", help="Audit all stores in source data")
    parser.add_argument("--limit", type=int, default=None, help="Limit number of shops to process")
    parser.add_argument("--retry-missing", action="store_true", help="Only audit shops missing an image")
    parser.add_argument("--export-csv", action="store_true", help="Export CSVs from existing JSON candidate data and exit")
    parser.add_argument("--report", action="store_true", help="Print summary report of candidates and exit")
    parser.add_argument("--dry-run", action="store_true", help="Dry run: don't write output files to disk")
    parser.add_argument("--source", type=Path, default=DEFAULT_SHOPS_JSON, help="Path to input shops JSON or CSV")
    parser.add_argument("--output-json", type=Path, default=DEFAULT_OUTPUT_JSON, help="Output candidate JSON path")
    parser.add_argument("--output-csv", type=Path, default=DEFAULT_OUTPUT_CSV, help="Output candidate CSV path")
    parser.add_argument("--workers", type=int, default=4, help="Thread pool worker count (default 4)")
    parser.add_argument("--timeout", type=float, default=8.0, help="HTTP request timeout in seconds")
    return parser.parse_args()


def main():
    args = parse_args()

    # Handle export-csv mode
    if args.export_csv:
        if not args.output_json.is_file():
            logger.error("Candidate JSON not found at: %s", args.output_json)
            sys.exit(1)
        raw_data = json.loads(args.output_json.read_text(encoding="utf-8"))
        records = [ImageAuditRecord(**d) for d in raw_data]
        export_candidates_csv(records, args.output_csv)
        export_sheets_csv(records, DEFAULT_OUTPUT_SHEETS_CSV)
        logger.info("Successfully exported CSVs: %s & %s", args.output_csv, DEFAULT_OUTPUT_SHEETS_CSV)
        print(generate_summary_report(records))
        return

    # Handle report mode
    if args.report:
        if not args.output_json.is_file():
            logger.error("Candidate JSON not found at: %s", args.output_json)
            sys.exit(1)
        raw_data = json.loads(args.output_json.read_text(encoding="utf-8"))
        records = [ImageAuditRecord(**d) for d in raw_data]
        print(generate_summary_report(records))
        return

    # Load source shops
    logger.info("Loading shops from: %s", args.source)
    shops = load_shops(args.source)
    logger.info("Total shops loaded: %d", len(shops))

    checkpoint_mgr = CheckpointManager(DEFAULT_CHECKPOINT_FILE)
    checkpoint_data = checkpoint_mgr.load()
    existing_records: Dict[int, ImageAuditRecord] = {}

    for sid, rec_dict in checkpoint_data.items():
        try:
            existing_records[sid] = ImageAuditRecord(**rec_dict)
        except Exception:
            pass

    # Filter target shops
    target_shops: List[ShopItem] = []
    for s in shops:
        if args.retry_missing:
            prev = existing_records.get(s.shop_id)
            if prev and prev.image_url and prev.verification_status != VerificationStatus.NOT_FOUND:
                continue
        target_shops.append(s)

    if args.limit:
        target_shops = target_shops[: args.limit]

    logger.info("Shops scheduled for audit: %d", len(target_shops))

    fetcher = SafeHttpFetcher(timeout=args.timeout)
    results_map: Dict[int, ImageAuditRecord] = dict(existing_records)

    with ThreadPoolExecutor(max_workers=max(1, args.workers)) as pool:
        future_to_shop = {pool.submit(process_single_shop, s, fetcher): s for s in target_shops}
        for future in as_completed(future_to_shop):
            shop_item = future_to_shop[future]
            try:
                record = future.result()
                results_map[shop_item.shop_id] = record
                status_icon = "✓" if record.image_url else "✗"
                logger.info(
                    "[%s] Shop #%d: %s | Image: %s | Type: %s | Status: %s",
                    status_icon,
                    record.shop_id,
                    record.shop_name,
                    record.image_url or "None",
                    record.image_type,
                    record.verification_status,
                )
            except Exception as exc:
                logger.error("Exception processing shop #%d: %s", shop_item.shop_id, exc)
                results_map[shop_item.shop_id] = ImageAuditRecord(
                    shop_id=shop_item.shop_id,
                    shop_name=shop_item.name,
                    shop_address=shop_item.address,
                    website=shop_item.website,
                    verification_status=VerificationStatus.REJECTED,
                    error=str(exc),
                )

    # Save checkpoint
    checkpoint_mgr.save(results_map)

    # Ensure results follow input shop order
    ordered_records: List[ImageAuditRecord] = []
    for s in shops:
        if s.shop_id in results_map:
            ordered_records.append(results_map[s.shop_id])
        else:
            ordered_records.append(
                ImageAuditRecord(
                    shop_id=s.shop_id,
                    shop_name=s.name,
                    shop_address=s.address,
                    website=s.website,
                    verification_status=VerificationStatus.NOT_FOUND,
                    error="Not processed in this run",
                )
            )

    report_text = generate_summary_report(ordered_records)
    print("\n" + report_text + "\n")

    if not args.dry_run:
        export_candidates_json(ordered_records, args.output_json)
        export_candidates_csv(ordered_records, args.output_csv)
        export_sheets_csv(ordered_records, DEFAULT_OUTPUT_SHEETS_CSV)
        logger.info("Saved candidates JSON -> %s", args.output_json)
        logger.info("Saved candidates CSV  -> %s", args.output_csv)
        logger.info("Saved Google Sheets CSV -> %s", DEFAULT_OUTPUT_SHEETS_CSV)
    else:
        logger.info("[Dry Run] Skipping file writes.")


if __name__ == "__main__":
    main()
