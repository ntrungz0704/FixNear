# Báo cáo kiểm tra toàn bộ dự án FixNear

Ngày kiểm tra: 27/09/2026  
Môi trường: PHP 8.5.5, PHP built-in server, dữ liệu JSON fallback  
Quy tắc đếm: mỗi phần tử catalog có `id` duy nhất, `name`, `brand`, `deviceType` hợp lệ và đang nằm trong tệp JSON công khai được tính là một model. Dự án chưa có cờ nháp/ẩn cho model, vì vậy toàn bộ 319 bản ghi đều xuất hiện công khai. Mỗi biến thể đang có `id` riêng được tính là một model riêng.

## 1. Tóm tắt kết quả

- Đã kiểm tra cú pháp toàn bộ 32 tệp PHP; không còn lỗi cú pháp.
- Đã thống nhất wizard trang chủ với API catalog, loại bỏ việc dùng danh sách model minh họa khi API không có dữ liệu.
- Đã giữ quy tắc hãng con không đếm trùng: Redmi/Poco lọc từ nhóm Xiaomi; các nhãn hãng chưa có model trả trạng thái rỗng và cho nhập mô tả.
- Đã sửa khoảng cách giả: khi chưa có GPS/khu vực, hệ thống không còn lấy tâm Quận 1 rồi ghi là “cách bạn”.
- Đã bổ sung kiểm tra máy chủ cho biểu mẫu yêu cầu, mã hồ sơ chống trùng, lịch sử trạng thái và trang tra cứu khách không đăng nhập bằng mã + số điện thoại.
- Đã sửa cuộn ngang trên mobile, co giãn grid, tab model, modal nhỏ, focus bàn phím, skip link và `prefers-reduced-motion`.
- Đã sửa đường dẫn CSS/JS/navbar khi trang nằm trong thư mục `admin/`.
- Đã thay các số quảng bá cứng ở phần chính bằng số đọc từ dữ liệu và gắn nhãn dữ liệu mẫu/tham khảo.
- Đã bổ sung tìm kiếm kết hợp thiết bị + hãng + từ khóa và phân trang 24 model/trang, giữ nguyên trạng thái trong URL.
- Đã thêm CSRF cho toàn bộ biểu mẫu ghi dữ liệu, chuyển xóa/đổi trạng thái quản trị từ GET sang POST, chặn redirect ngoài và redirect header bất hợp lệ.
- Đã chuyển lớp ghi JSON sang thay thế tệp nguyên tử, gắn yêu cầu mới với tài khoản và hiển thị lịch sử yêu cầu của tài khoản.
- Tài khoản mật khẩu dạng cũ được nâng cấp sang hash an toàn ngay sau lần đăng nhập hợp lệ.

## 2. Số lượng trước và sau

Không thêm model/cửa hàng/đánh giá giả. Bản ghi kiểm thử yêu cầu đã được xóa sau khi chạy luồng.

| Hạng mục | Trước | Sau | Nguồn / ghi chú |
|---|---:|---:|---|
| Điện thoại | 140 | 140 | 10 tệp nhóm hãng |
| Laptop Windows | 85 | 85 | 7 tệp nhóm hãng |
| MacBook / Mac | 17 | 17 | Apple |
| Máy tính bảng | 30 | 30 | 4 tệp nhóm hãng |
| PC / All-in-One | 21 | 21 | 5 nhóm cấu hình |
| Đồng hồ thông minh | 26 | 26 | 4 tệp nhóm hãng |
| Tổng model | 319 | 319 | Không trùng `id` |
| Taxonomy lỗi | 28 | 28 | `data/faults.json` |
| Dịch vụ | 18 | 18 | `data/services.json`, dữ liệu mẫu |
| Liên kết model–lỗi | 4.789 | 4.789 | Tổng `supportedFaults` |
| Lỗi đặc thù | 362 | 362 | Tổng `knownIssues` |
| Quan hệ cửa hàng–dịch vụ | 1.193 | 1.193 | `data/shop_services.json` |
| Cửa hàng | 68 | 68 | 68 bản ghi có tọa độ; chưa xác minh độc lập |
| Thợ riêng lẻ | 0 | 0 | Chưa có entity/bảng thợ riêng |
| Khu vực | 17 | 17 | 16 quận + TP. Thủ Đức trong bộ dữ liệu hiện tại |
| Đánh giá công khai | 7 | 7 | Dữ liệu dự án, chưa xác minh nguồn độc lập |
| Yêu cầu người dùng | 4 | 4 | 2 pending, 1 contacted, 1 completed |

### Điện thoại theo bộ lọc tệp catalog

| Bộ lọc | Số model | Ghi chú |
|---|---:|---|
| Apple | 35 | `apple.json` |
| Google | 10 | Nhãn UI “Google Pixel” ánh xạ `google` |
| OnePlus | 6 | `oneplus.json` |
| Oppo | 16 | `oppo.json` |
| Realme | 7 | `realme.json` |
| Samsung | 27 | `samsung.json` |
| Sony | 6 | `sony.json` |
| Vivo | 8 | `vivo.json` |
| Xiaomi | 19 | Bao gồm tên Xiaomi/Redmi/POCO; hãng con chỉ là bộ lọc tên, không cộng thêm vào tổng |
| Hãng khác | 6 | Honor 1, Huawei 1, Infinix 1, Nothing 2, nhãn others 1 |
| **Tổng** | **140** | Khớp danh sách và tab |

Nhãn Motorola, Nokia, Tecno, ZTE/Nubia và iQOO hiện chưa có model công khai trong catalog. Wizard vẫn cho người dùng chọn/nhập mô tả nhưng không dựng model giả.

## 3. Đối chiếu trang và chức năng

| Trang/chức năng | Trước | Thay đổi | Kiểm tra | Kết quả |
|---|---|---|---|---|
| Trang chủ | Model từ mảng JS minh họa; số quảng bá cứng; khoảng cách từ tâm Q.1 ghi như vị trí người dùng | Dùng API catalog, thống kê động, chỉ hiện khoảng cách khi có vị trí | Chọn Apple/Samsung/Redmi/Poco và hãng rỗng; kiểm tra DOM | Đạt với dữ liệu hiện có |
| Bảng giá model | Số đúng nhưng tab mobile tràn, thiếu phân trang | Tab 1 cột ở mobile; tìm kiếm kết hợp; phân trang 24 model/trang | Trang phone 24/24/24/24/24/20; lọc Apple + “iPhone 13” trả 8; 3 viewport | Đạt |
| Chi tiết model | Có bảng giá 3 cấp tính từ ma trận | Giữ nguyên; báo cáo nêu rõ đây là ước tính mô hình | Mở model hợp lệ/ID không tồn tại | Đạt trong phạm vi dữ liệu |
| Gửi yêu cầu | Thiếu validate server, mã 4 số dễ trùng, ngôn ngữ ám chỉ đã ghép | Validate tên/email/điện thoại/khu vực/độ dài; mã mới; trạng thái `pending`; gợi ý chưa phân công | POST sai không ghi; POST đúng ghi; đổi trạng thái; xóa bản ghi test | Đạt |
| Theo dõi yêu cầu | Không có cho khách | Thêm `track_request.php`, bắt buộc mã + số điện thoại; tài khoản xem được hồ sơ của mình | Tra mã đúng/sai và kiểm tra điều kiện sở hữu | Đạt |
| Cửa hàng | Tuyên bố đã xác minh/thực tế quá mức | Gắn nhãn bản ghi tham khảo; thống kê động | Lọc từ khóa/khu vực/thiết bị | Đạt về thao tác; nguồn ngoài chưa xác minh |
| Bản đồ/tìm kiếm | Hiện khoảng cách dù không có GPS | Không tính/hiện khoảng cách nếu thiếu tọa độ | Có và không có tọa độ | Đạt |
| Đăng nhập/đăng ký | Redirect có thể nhận URL ngoài; label/autocomplete thiếu; mật khẩu mẫu dạng cũ | Chặn open redirect; bổ sung label/autocomplete; CSRF; tự nâng cấp hash sau đăng nhập | POST thiếu CSRF trả 403; token hợp lệ chạy bình thường | Đạt |
| Quản trị | Asset/navbar sai đường dẫn; thao tác thay đổi qua GET | Sửa prefix asset; POST + CSRF; lưu lịch sử; giới hạn trạng thái hợp lệ | PHP lint, kiểm tra HTML form và kiểm tra token HTTP | Đạt |
| Responsive | Mobile rộng 751px do menu ẩn; Contact/grid tràn | Chặn overflow, co grid/tabs/modal, focus/reduced motion | 1440×900, 768×1024, 390×844 trên 9 trang; kiểm tra lại trang model sau phân trang | Không tràn ngang |

## 4. Luồng người dùng đã chạy thử

1. Catalog validator: 319 model, 31 tệp, 4.789 liên kết pan bệnh, 362 lỗi đặc thù, không trùng ID.
2. Tìm model và bộ lọc: Apple 35, Samsung 27, Xiaomi-group 19; hãng không có dữ liệu trả empty state.
3. Không GPS: không còn khoảng cách “gần bạn”; có GPS/khu vực mới tính Haversine.
4. Gửi yêu cầu sai số điện thoại: bị chặn phía máy chủ, số bản ghi giữ nguyên.
5. Gửi yêu cầu hợp lệ: tạo mã `FN-yymmddHHMMSS-XXXX`, trạng thái `pending`, màn hình xác nhận không tuyên bố đã ghép thợ.
6. Cập nhật sang `completed`: trạng thái và `status_history` cùng được ghi.
7. Tra cứu khách: mã + số điện thoại đúng trả hồ sơ; sai một trong hai không lộ dữ liệu.
8. Bản ghi kiểm thử được xóa, dữ liệu yêu cầu trở lại 4 bản ghi ban đầu.
9. Phân trang điện thoại: 140 model thành 6 trang (5 trang × 24, trang cuối 20); bộ lọc Apple + `iPhone 13` trả 8 model.
10. CSRF: POST đăng nhập thiếu token trả HTTP 403; cùng request có token hợp lệ được xử lý bình thường.

## 5. Tệp đã thay đổi

- `assets/css/style.css`
- `assets/js/main.js`
- `config/db.php`
- `includes/header.php`
- `includes/navbar.php`
- `includes/footer.php`
- `index.php`
- `models.php`
- `shops.php`
- `search.php`
- `shop_detail.php`
- `request_repair.php`
- `login.php`
- `register.php`
- `admin/requests.php`
- `admin/index.php`
- `admin/shops.php`
- `admin/shop_edit.php`
- `admin/services.php`
- `admin/reviews.php`
- `admin/reports.php`
- `api/add_review.php`
- `api/add_report.php`
- `api/reply_review.php`
- `contact.php`
- `model_detail.php`
- `shop_detail.php`
- `install.php`
- `track_request.php` (mới)
- `outputs/FULL_PROJECT_AUDIT_2026-09-27.md` (mới)

Không có migration vì schema catalog/JSON không thay đổi; `status_history` được thêm tương thích ngược cho các yêu cầu mới và khi trạng thái cũ được cập nhật.

## 6. Hướng dẫn chạy

```text
php -S 127.0.0.1:8000
```

Mở `http://localhost:8000`. Kiểm định catalog bằng:

```text
python scripts/validate_catalog.py
```

## 7. Giới hạn còn lại

- 68 cửa hàng, điểm Google, số review và các review mẫu chưa có dấu vết nguồn/crawl timestamp đủ để gọi là “đã xác minh độc lập”. Giao diện đã hạ mức tuyên bố nhưng dữ liệu nguồn vẫn cần đối soát thủ công.
- Chưa có entity thợ riêng, phân công cụ thể, tài khoản cửa hàng/thợ hoặc thông báo thời gian thực.
- Giá 3 cấp Standard/OEM/Genuine được tính từ ma trận/hệ số; chỉ 1 override model được lưu. Đây là giá ước tính, không phải báo giá cuối cùng hay bằng chứng linh kiện chính hãng.
- Chưa có tải ảnh trong biểu mẫu yêu cầu; vì vậy chưa triển khai kiểm tra MIME/dung lượng ảnh.
- Chưa có quên mật khẩu, rate limiting hoặc khóa chống brute-force. Ba tài khoản mẫu cũ chỉ được chuyển từ mật khẩu rõ sang hash khi đăng nhập thành công; nên chạy migration bắt buộc trước production.
- Chế độ MySQL và JSON chưa hoàn toàn đồng nhất: phần lớn truy vấn đọc/ghi nghiệp vụ vẫn dùng JSON, trong khi một số thao tác sao chép sang MySQL. Cần chọn một nguồn chuẩn trước khi triển khai production.
- Một số nội dung marketing/review tĩnh vẫn mang tính minh họa; không nên dùng làm chứng thực thương mại trước khi có nguồn dữ liệu và quy trình xác minh.
