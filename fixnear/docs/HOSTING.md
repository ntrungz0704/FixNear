# Đưa FixNear lên host/domain

## Yêu cầu

- Apache 2.4+, PHP 8.1+ với `mbstring`, `json`, `openssl`.
- HTTPS hợp lệ.
- Thư mục dữ liệu riêng có quyền đọc/ghi cho PHP.

## Cấu hình bắt buộc

1. Upload mã nguồn. Giữ nguyên `.htaccess` nếu document root là thư mục dự án.
2. Sao chép `config/local.php.example` thành `config/local.php`.
3. Đặt `environment` thành `production`.
4. Đặt `data_dir` tới thư mục nằm **ngoài** `public_html`, ví dụ `/home/ACCOUNT/fixnear-data`.
5. Sao chép dữ liệu cần dùng trong `data/` sang thư mục đó. Không sao chép tài khoản/yêu cầu thử nghiệm lên production.
   Các tệp phát sinh `users.json`, `repair_requests.json`, `contact_messages.json`, `reviews.json`, `favorites.json` và `reports.json` không còn được theo dõi trong Git. Nếu chuyển một hệ thống đang dùng, hãy sao lưu và chuyển riêng các bản ghi thật qua kênh bảo mật; bản cài đặt mới có thể bắt đầu với các tệp này vắng mặt.
6. Cấp quyền tối thiểu đủ để PHP đọc/ghi thư mục dữ liệu (thường 750 cho thư mục, 640/660 cho file tùy cấu hình host).
7. `data/users.json` là dữ liệu riêng của máy chạy và không nằm trong Git. Tạo quản trị viên bằng terminal host, dùng biến môi trường tạm thời để mật khẩu không nằm trong source:

   ```bash
   FIXNEAR_ADMIN_EMAIL="admin@domain.vn" FIXNEAR_ADMIN_NAME="Quản trị viên" FIXNEAR_ADMIN_PASSWORD="mat-khau-rieng-it-nhat-12-ky-tu" php scripts/create_admin.php
   ```

   Xóa/unset các biến tạm sau khi lệnh chạy xong. Không công khai `config/local.php`.
8. Bật SSL, trỏ domain, rồi kiểm tra chuyển hướng HTTPS trong bảng điều khiển host.

FixNear dùng JSON khi chưa kết nối MySQL. Khi cấu hình MySQL trong môi trường phát triển và CSDL trống, ứng dụng có thể nhập `data/fixnear_db.sql`, là **dữ liệu mẫu**. Production không tự nhập bản SQL này; hãy cấp CSDL sạch và dữ liệu đã xác minh bằng quy trình triển khai riêng. Không nhập bản SQL mẫu lên production.

## Kiểm tra sau khi upload

Chạy kiểm tra tự động trước, sau khi đã cấu hình `config/local.php` và thư mục dữ liệu production:

```bash
php scripts/preflight.php
```

Lệnh phải kết thúc với `0 lỗi`. Cảnh báo môi trường development hoặc dữ liệu nằm trong `public_html` phải được xử lý trước khi mở website cho người dùng.

- `/`, `/models.php`, `/shops.php`, `/search.php` trả HTTP 200.
- `/config/db.php`, `/data/users.json`, `/fixnear_db.sql`, `/install.php` trả 403/404 từ Internet.
- Đăng ký, đăng nhập, đăng xuất hoạt động; cookie có `HttpOnly`, `SameSite=Lax`, `Secure` trên HTTPS.
- Gửi yêu cầu sửa chữa và tra cứu lại bằng mã + số điện thoại.
- Form đánh giá, báo sai và liên hệ chống CSRF và giới hạn tần suất.
- Kiểm tra 360 px, 768 px và desktop; không có cuộn ngang.
- Kiểm tra backup định kỳ cho thư mục dữ liệu ngoài `public_html`.

## Dữ liệu cần xác minh trước khi công bố thương mại

- Giá, bảo hành, ưu đãi sinh viên: cần URL nguồn và ngày đối soát cho từng cửa hàng.
- Điểm/số lượt đánh giá Google: cần Place ID và thời điểm đồng bộ hợp lệ.
- Hotline, email, địa chỉ pháp lý và chính sách quyền riêng tư của đơn vị vận hành.

Xem thêm báo cáo kiểm chứng tại `outputs/FACT_VERIFICATION_2026-09-27.md` trong bản phát triển (thư mục này bị chặn trên host).
