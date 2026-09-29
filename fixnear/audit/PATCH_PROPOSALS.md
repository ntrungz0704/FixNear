# Đề xuất patch sau audit — Trạng thái áp dụng

Ngày cập nhật: 2026-09-28 22:00

## ĐÃ ÁP DỤNG — PHP lint passed 6/6

### P0 — Badge "✓ ĐÃ XÁC MINH" vô điều kiện → ✅ ĐÃ SỬA
- **File:** `shops.php:176-185`
- **Trước:** Badge xanh "✓ ĐÃ XÁC MINH" render cứng cho mọi 68 card
- **Sau:** Kiểm tra `$shop['source_verified']`; khi false hiện "📋 Dữ liệu tham khảo"

### P0 — Thu hẹp API get_shops → ✅ ĐÃ SỬA
- **File:** `api/get_shops.php:25-68`
- **Trước:** `echo json_encode(['data' => $shops])` trả raw toàn bộ field
- **Sau:** Whitelist 17 trường an toàn; `google_rating`, `student_discount`, policy chỉ trả khi `*_verified=true`

### P0 — Pricing API gắn nhãn UNVERIFIED → ✅ ĐÃ SỬA
- **File:** `api/get_catalog.php:97-99`
- **Sau:** Response có `price_status: "UNVERIFIED"` + `price_disclaimer`

### P1 — Review moderation → ✅ ĐÃ SỬA
- **File:** `config/db.php:399-401`
- **Trước:** `$data['is_hidden'] = false` — hiện ngay
- **Sau:** `$data['is_hidden'] = true` + `moderation_status = 'pending'`
- **Kèm:** `shop_detail.php:38-40` đổi thông báo thành "chờ duyệt"

### P1 — SLA 15 phút không nguồn → ✅ ĐÃ SỬA
- **File:** `request_repair.php:448,451`
- **Trước:** "Xem Báo Giá Minh Bạch" + "báo giá trong 15 phút"
- **Sau:** "Xem Khoảng Giá Ước Tính" + "do cửa hàng xác nhận trực tiếp"

### Bổ sung — ĐÃ SỬA
- `shops.php:93` — bỏ nêu tên "Điện Thoại Vui" Back to School
- `shops.php:163` — label xếp theo phản ánh đúng logic (A-Z khi chưa verified)
- `config/db.php:138-140` — smartwatch chỉ match smartwatch (bỏ fallback phone)
- `nginx.conf.example` (mới) — cấu hình Nginx chặn data/config/scripts

## CHƯA ÁP DỤNG — Cần thông tin từ người thật

### P2 — Trang Privacy Policy
Cần chủ dự án cung cấp nội dung chính sách bảo mật, quy trình retention/xóa dữ liệu, thông tin chủ thể vận hành.

### P2 — Placeholder footer
`contact.php`, `includes/footer.php` dòng 264-268 — "Chưa công bố", "Chưa cấu hình" cần thay bằng hotline/email/địa chỉ thật.

### P2 — Rate limit + CAPTCHA
Cần tích hợp CAPTCHA (reCAPTCHA/hCaptcha) cho form công khai và rate limit IP-based (Redis/Memcached hoặc file-based).

### P3 — 90 ghi chú chéo cửa hàng
`data/shop_services.json` có 90 dòng ghi chú nhắc tên cửa hàng khác. Cần rà và sửa từng dòng.

### P3 — Ảnh minh họa
8 URL Unsplash dùng chung cho 68 shop. Gắn nhãn "ảnh minh họa" hoặc thay bằng ảnh thật từ cửa hàng.
