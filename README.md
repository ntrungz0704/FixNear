# FixNear

Website tra cứu thiết bị, chi phí sửa chữa tham khảo và thông tin cửa hàng tại TP.HCM.

## Công nghệ của website

Giao diện dùng HTML5, CSS3 và JavaScript; máy chủ dùng PHP 8.1+ và MySQL. Bản chạy thử có thể dùng dữ liệu JSON khi chưa cấu hình MySQL. Python chỉ nằm trong các tiện ích tạo dữ liệu và kiểm thử ngoại tuyến ở `scripts/` và `tools/`; website không gọi Python khi người dùng truy cập. GitHub tính cả các tiện ích đó vào biểu đồ ngôn ngữ của repository.

## Chạy trên máy cá nhân

Yêu cầu PHP 8.1+ với `mbstring`, `json`, `openssl`.

```bash
php -S 127.0.0.1:8000 -t . router.php
```

Mở `http://127.0.0.1:8000/`. Không cần MySQL để chạy với dữ liệu JSON mẫu. Dữ liệu người dùng, yêu cầu sửa chữa và cấu hình riêng được giữ ngoài Git.

## Kiểm tra

```bash
php scripts/preflight.php
python scripts/validate_catalog.py
python scripts/validate_price_evidence.py
python scripts/validate_shop_evidence.py
python scripts/validate_shop_media.py
python scripts/test_user_flows.py
```

## Triển khai

Đọc [hướng dẫn hosting](docs/HOSTING.md) trước khi đưa lên máy chủ. Cần cấu hình production, thư mục dữ liệu riêng ngoài web root và tài khoản quản trị mới. Giá ước tính không phải báo giá của cửa hàng; ảnh chi nhánh chỉ được hiển thị khi có dữ liệu đúng địa điểm và quyền sử dụng hợp lệ.
