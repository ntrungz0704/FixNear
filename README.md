# FixNear

Website tra cứu thiết bị, chi phí sửa chữa tham khảo và thông tin cửa hàng tại TP.HCM.

## Công nghệ của website

Giao diện dùng HTML5, CSS3 và JavaScript; máy chủ dùng PHP 8.1+ và MySQL. Bản chạy thử có thể dùng dữ liệu JSON khi chưa cấu hình MySQL. Python chỉ nằm trong các tiện ích tạo dữ liệu và kiểm thử ngoại tuyến ở `scripts/` và `tools/`; website không gọi Python khi người dùng truy cập. GitHub tính cả các tiện ích đó vào biểu đồ ngôn ngữ của repository.

## Chạy trên máy cá nhân

Yêu cầu PHP 8.1+ với `mbstring`, `json`, `openssl`.

```bash
php -S 127.0.0.1:8000 -t . router.php
```

Mở `http://127.0.0.1:8000/`. Bản clone dùng JSON mặc định ở môi trường phát triển, nên giao diện và dữ liệu công khai giống bản trên GitHub mà không cần MySQL.

### Khôi phục đúng dữ liệu riêng đang có

GitHub có `data/private-snapshot.enc`, là bản sao **đã mã hóa** của 6 tệp tài khoản, yêu cầu sửa chữa, liên hệ, đánh giá, yêu thích và báo sai tại thời điểm xuất. Để bản clone hiện đúng các dữ liệu này, chép riêng tệp khóa do chủ dự án giữ tới máy mới, rồi chạy trong PowerShell trước khi dùng website:

```powershell
$env:FIXNEAR_SNAPSHOT_KEY_FILE = 'C:\duong-dan-rieng\snapshot.key'
php scripts/private_snapshot.php verify
php scripts/private_snapshot.php restore
php -S 127.0.0.1:8000 -t . router.php
```

Khóa hiện tại trên máy chủ dự án nằm tại `C:\Users\ntrun\.fixnear\snapshot.key`, **không nằm trong Git**. Không gửi khóa cùng mã nguồn và không đăng khóa công khai. Lệnh khôi phục chỉ chạy vào thư mục dữ liệu chưa có 6 tệp riêng, để tránh ghi đè dữ liệu mới. Mọi thay đổi sau thời điểm xuất cần xuất lại bản mã hóa; Git clone không tự đồng bộ dữ liệu đang phát sinh. Bản mã hóa không thay thế sao lưu riêng có kiểm soát truy cập. Nếu không có khóa, bản clone vẫn chạy với dữ liệu công khai và có thể tạo một admin riêng bằng `scripts/create_admin.php`.

Muốn cập nhật bản mã hóa sau khi dữ liệu thay đổi, chạy `php scripts/private_snapshot.php export` với `FIXNEAR_SNAPSHOT_KEY_FILE` đã đặt, kiểm tra bằng `verify`, rồi commit tệp `.enc`. Hệ thống chỉ cho phép một admin; lệnh `create_admin.php` chỉ cập nhật email admin hiện tại hoặc tạo admin khi chưa có.

```bash
php scripts/preflight.php
python scripts/validate_catalog.py
python scripts/validate_price_evidence.py
python scripts/validate_shop_evidence.py
python scripts/validate_shop_media.py
python scripts/test_user_flows.py
python tests/test_audit_shop_media.py
```

## Thu thập & Đối soát ảnh cửa hàng (Image Research Engine)

Công cụ tự động thu thập và xác minh ảnh chi nhánh từ website chính thức:

```bash
# Chạy thử 5 cửa hàng
python scripts/audit_shop_media.py --limit 5 --dry-run

# Nghiên cứu toàn bộ cửa hàng
python scripts/audit_shop_media.py --all

# Chỉ kiểm tra lại ảnh lỗi hoặc còn thiếu
python scripts/audit_shop_media.py --retry-missing

# Xuất dữ liệu CSV cho Google Sheets (tab Ảnh cửa hàng - Gemini)
python scripts/audit_shop_media.py --export-csv

# Hiển thị thống kê
python scripts/audit_shop_media.py --report
```

## Triển khai

Đọc [hướng dẫn hosting](docs/HOSTING.md) trước khi đưa lên máy chủ. Cần cấu hình production, thư mục dữ liệu riêng ngoài web root và tài khoản quản trị mới. Giá ước tính không phải báo giá của cửa hàng; ảnh chi nhánh chỉ được hiển thị khi có dữ liệu đúng địa điểm và quyền sử dụng hợp lệ.
