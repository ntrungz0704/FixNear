# FixNear Truth Audit — Tiến độ

Ngày cập nhật: 2026-09-28

## Phase 0: Khảo sát repo — ✅ Hoàn tất
- 68 shops, 18 services, 1.193 shop_services, 7 reviews, 28 faults, 319 models
- Schema xác nhận: 0/68 có source_url/verified_at/google_place_id

## Phase 1: Kiểm toán code — ✅ Hoàn tất
- PHP lint: 38/38 OK
- JSON: 45/45 OK  
- Phát hiện: 1 Critical, 4 High, 6 Medium, 3 Low

## Phase 2: Sổ xác minh — ✅ Baseline + chờ xác minh web
- Ledger 68/68 UNVERIFIED tại `audit/verification_ledger.csv` + `.json`
- Chưa thực hiện tra cứu web nguồn chính thức

## Phase 3: Báo cáo — ✅ Hoàn tất
- `audit/AUDIT_REPORT.md`
- `audit/ASSET_SERVICE_AUDIT_2026-09-28.md`

## Phase 4: Patch — ✅ ĐÃ ÁP DỤNG (phiên 2026-09-28 21:57–22:00)

### Patch đã áp dụng (PHP lint passed 6/6)

| # | Mức | File | Mô tả | Trạng thái |
|---|---|---|---|---|
| C1 | **Critical** | `shops.php:176-185` | Badge "✓ ĐÃ XÁC MINH" → có điều kiện `source_verified` | ✅ Đã sửa |
| H1 | **High** | `api/get_shops.php:25-68` | API whitelist fields, chỉ trả verified data | ✅ Đã sửa |
| H2 | **High** | `api/get_catalog.php:97-99` | Thêm `price_status: UNVERIFIED` + disclaimer | ✅ Đã sửa |
| H3 | **High** | `config/db.php:399-401` | Review `is_hidden=true` mặc định + moderation_status | ✅ Đã sửa |
| M1 | **Medium** | `shops.php:93` | Bỏ nêu tên "Điện Thoại Vui" không nguồn | ✅ Đã sửa |
| M2 | **Medium** | `config/db.php:138-140` | Smartwatch fallback sang phone → chỉ smartwatch | ✅ Đã sửa |
| M3 | **Medium** | `request_repair.php:448,451` | Bỏ SLA 15 phút + "Báo Giá Minh Bạch" | ✅ Đã sửa |
| M4 | **Medium** | `nginx.conf.example` (mới) | Cấu hình Nginx chặn data/config/scripts | ✅ Đã tạo |
| — | **Medium** | `shops.php:163` | Label xếp theo → phản ánh đúng logic (A-Z khi chưa verified) | ✅ Đã sửa |
| — | **Medium** | `shop_detail.php:38-40` | Review success message → "chờ duyệt" | ✅ Đã sửa |

### Chưa sửa (cần bổ sung thêm)

| # | Mức | Mô tả | Lý do |
|---|---|---|---|
| H4 | High | Trang Privacy Policy | Cần người thật cung cấp nội dung pháp lý |
| M5 | Medium | Rate limit IP/CAPTCHA | Cần tích hợp service bên ngoài |
| M6 | Medium | Placeholder hotline/email/địa chỉ footer | Cần thông tin thật từ chủ dự án |
| L1 | Low | 90 ghi chú chéo cửa hàng | Cần rà thủ công shop_services.json |
| L2 | Low | 8 ảnh Unsplash dùng chung | Cần gắn nhãn "ảnh minh họa" hoặc ảnh thật |
| L3 | Low | div onclick thiếu keyboard | Cần refactor accessibility |

## Lần tiếp theo
- Phase 2 đợt 1–5: xác minh web nguồn chính thức 10–15 CH/đợt
- Hoàn thiện H4 (privacy page) khi có nội dung pháp lý
- Rà L1 (90 ghi chú chéo) trong shop_services.json
