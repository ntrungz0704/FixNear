# FixNear Truth Audit — Báo cáo kiểm toán

Ngày audit: 2026-09-28  
Phạm vi: mã PHP/JS, dữ liệu JSON, HTTP/API local và giao diện công khai. Không scrape Google Maps hoặc khẳng định dữ liệu từ URL seed là chính xác.

## Kết luận điều hành

Không thể kết luận 68 cửa hàng, giá, ưu đãi, chính sách hoặc đánh giá là thật/chính xác. `audit/verification_ledger.*` có 68/68 bản ghi `UNVERIFIED`.

| Mục tiêu | Kết luận | Lý do |
|---|---|---|
| GitHub demo học tập | Có điều kiện | Có thể push cùng báo cáo/ledger và nhãn demo, nhưng phải biết API hiện vẫn rò dữ liệu seed chưa xác minh. |
| Hosting thử nghiệm | Chưa đạt chuẩn truth-audit | Cần áp dụng patch P0/P1, cấu hình production, dữ liệu ngoài web root và bổ sung pháp lý/riêng tư. |
| Dịch vụ thương mại / “đã xác minh” | Không sẵn sàng | Không có bằng chứng đủ chuẩn cho 68 cửa hàng, 1.193 giá, 15 ưu đãi và 7 review seed. |

## Phase 0 — Dữ liệu và schema

| Hạng mục | Đếm local | VERIFIED | UNVERIFIED | Kiểm tra cấu trúc |
|---|---:|---:|---:|---|
| Cửa hàng (`data/shops.json`) | 68 | 0 | 68 | Có id/tên/địa chỉ/quận/điện thoại/tọa độ/Maps URL; 0 nguồn + ngày. |
| Dòng dịch vụ (`data/shop_services.json`) | 1.193 | 0 | 1.193 | 0 giá âm, 0 min > max, 0 foreign key mồ côi; 0 nguồn giá + ngày. |
| Ưu đãi sinh viên seed | 15 | 0 | 15 | 0 URL nguồn và ngày đối soát theo chi nhánh. |
| Chính sách xem sửa/ký linh kiện | Có trong seed | 0 | Không xác định theo từng field | 0 URL nguồn + ngày. |
| Review (`data/reviews.json`) | 7 | 0 | 7 | Shop/rating tham chiếu hợp lệ nhưng không có `origin=user_submission` hoặc `verified_at`. |

Khác: 68/68 seed `is_verified=true`, nhưng `config/db.php:43-47` ghi đè thành `false` trừ khi có nguồn + ngày. Tọa độ 68/68 nằm trong bounding box TP.HCM rộng; chưa geocode độc lập nên không xác minh đúng quận/địa chỉ. Có 5 nhóm số điện thoại trùng, cần con người xác nhận đó là hotline hệ thống hay dữ liệu lặp.

## Phase 1 — Kết quả kỹ thuật có bằng chứng

- PHP lint: 38/38 file hợp lệ; JavaScript `main.js`, `map.js`, `chatbot.js` parse được; 45 JSON đọc được.
- HTTP local: 17 trang/API chính HTTP 200, 6 trang admin trả 302 khi chưa đăng nhập; `/config/db.php`, `/data/*.json`, `/scripts/*`, SQL và installer trả 404 qua router.
- Header: HTML/API có `X-Content-Type-Options: nosniff`; session có `HttpOnly`, `SameSite=Lax`; HSTS chỉ bật ở HTTPS + production.
- Không có Git repository trong workspace, nên không thể kiểm lịch sử commit hoặc khẳng định không có secret trong lịch sử.
- Không thấy secret runtime trong quét source hiện tại; giá trị mật khẩu ở `HOSTING.md` chỉ là ví dụ.

## Findings

### High

- `shops.php:75-95,162,177` — ba thẻ mô tả xem sửa/ký linh kiện/ưu đãi và badge `ĐÃ XÁC MINH` được render không điều kiện. Dữ liệu audit có 0 nguồn chính sách/ưu đãi/cửa hàng; UI hiện đưa tuyên bố sai cho 68 card.
- `api/get_shops.php:13-26` — API trả nguyên raw object sau normalize; thử HTTP chứng minh nó vẫn có `google_rating=4.9`, `google_reviews_count=3850`, `student_discount`, `allows_onsite_watch`, `requires_component_signing` và description seed khi tất cả cờ `*_verified=false`.
- `api/get_catalog.php:54-82` — API trả đầy đủ ma trận số giá ước tính của pricing engine. UI che số nhưng bất kỳ client nào gọi API vẫn nhận giá không có nguồn.
- `config/db.php:367-368,393-403` — review mới từ tài khoản đăng nhập trở thành `user_submission`, `is_hidden=false` và hiển thị ngay. Không có moderation trước công khai; “đã gửi bởi người dùng” không đồng nghĩa review đúng hoặc đã xác minh.

### Medium

- `request_repair.php:448,451` — hứa “Báo Giá Minh Bạch” và phản hồi/báo giá trong 15 phút nhưng 0 bằng chứng SLA hoặc cửa hàng nhận xử lý. Đây là claim vận hành không được ledger xác minh.
- `contact.php:95-112`, `includes/footer.php:264-266` — địa chỉ, hotline, email chủ thể vận hành là placeholder; không có trang chính sách riêng tư/retention cho form lưu điện thoại/email/yêu cầu.
- `config/app.php:76-89` — rate limit lưu trong session; người dùng mới/cookie mới có thể reset. Không có CAPTCHA hay rate limit IP/server-side cho form công khai.
- `config/db.php:407-439,641-675` — các thao tác update/delete/reply review và admin chưa dùng lock JSON như các mutator khác; rủi ro lost update khi concurrent writes.
- `.htaccess` chỉ bảo vệ Apache. `HOSTING.md` cảnh báo nhưng không có cấu hình Nginx tương đương đi kèm; host Nginx sai document root có thể lộ JSON.
- UI audit: `index.php:64-145,264`, `request_repair.php:215-257`, `includes/navbar.php:59` dùng `div` có `onclick` hoặc backdrop click; không có keyboard semantics. `models.php:133` đặt `outline:none` inline. 10 ảnh PHP bị thiếu width/height theo quét tĩnh. Xem `web-design-guidelines` audit.

### Low

- `scripts/check_full.php:5` cố định `127.0.0.1:8000`; khi server chưa chạy nó báo code 0 cho mọi URL và không giải thích nguyên nhân.
- Một số copy/comment cũ dùng “bảng giá” hoặc “uy tín”; cần rà lại sau khi áp dụng P0 để không mâu thuẫn nhãn truth-audit.

## Những gì kiểm toán không thể tự xác minh

- Danh tính/hoạt động của từng chi nhánh, tọa độ thuộc đúng địa chỉ, hotline/giờ mở cửa hiện tại.
- Google Place ID, rating/review count tại thời điểm hiện tại: không có API key Place Details và không scrape Google Maps.
- Giá, thời hạn bảo hành, linh kiện, ưu đãi SV, quyền xem sửa/ký linh kiện và thời gian phản hồi.
- Lịch sử Git/secret cũ, vì đây chưa là Git repository.

## Việc bắt buộc cho người thật

1. Làm 10–15 cửa hàng/lần trong ledger: URL chính thức/Place ID, ngày truy cập, ghi chú chứng cứ ngắn và người kiểm tra.
2. Gọi/nhắn chi nhánh để xác nhận hotline, giờ, giá, ưu đãi, bảo hành và chính sách; không suy rộng cấp hệ thống sang chi nhánh.
3. Hoàn thiện chủ thể vận hành, địa chỉ, hotline/email, privacy policy và retention/deletion cho dữ liệu form.
4. Dùng Google Place Details hợp lệ nếu có API key và tuân theo điều khoản nguồn; không lấy URL search làm bằng chứng đủ chuẩn.
5. Chỉ sau đó cập nhật field nguồn + ngày và để UI/API tự kích hoạt trạng thái VERIFIED.

## Đề xuất patch

Diff chưa áp dụng nằm tại `audit/PATCH_PROPOSALS.md`.
