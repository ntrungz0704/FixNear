# Kiểm tra ảnh, logo, giá và khả năng nhận sửa

Ngày kiểm tra: 2026-09-28  
Phương pháp: đọc toàn bộ assets/data local, HEAD request với 8 URL ảnh duy nhất và gọi API local. Không suy diễn dữ liệu seed là xác minh ngoài đời thực.

## Ảnh và logo

| Hạng mục | Kết quả |
|---|---|
| Ảnh/logo local | 34/34 file đọc được; 30 logo thương hiệu PNG, logo FixNear SVG, logo PNG, avatar và banner. |
| Kích thước local | PNG/JPG có kích thước đọc được; SVG có viewBox `0 0 100 100`. |
| Ảnh trong 68 shop records | 68/68 có URL; chỉ 8 URL Unsplash duy nhất. |
| Tải URL remote | 8/8 trả HTTP 200 `image/jpeg`. |
| Tính đại diện | Không xác minh: mỗi ảnh Unsplash được gán cho 8–9 cửa hàng. Đây là stock image, không phải ảnh chứng minh mặt tiền/cửa hàng thật. |
| Markup hiển thị | Các ảnh shop đều có `alt`, `width` và `height`; shop list/request có `loading="lazy"`. |

Kết luận ảnh: không có link ảnh hỏng trong thời điểm kiểm tra, nhưng không được dùng ảnh này làm bằng chứng cửa hàng tồn tại hoặc nhận sửa. Nên gắn nhãn “ảnh minh họa” hoặc thay bằng ảnh do cửa hàng cấp kèm nguồn/quyền sử dụng.

## Giá sửa chữa

| Hạng mục | Kết quả |
|---|---|
| Dòng giá `shop_services.json` | 1.193 |
| Giá âm / min > max | 0 / 0 |
| Nguồn giá + ngày hiệu lực | 0/1.193 |
| Giá `0–0` | 68 dòng (toàn bộ service chẩn đoán). |
| Ghi chú nhắc tên cửa hàng khác | 90 dòng; ví dụ shop id 10 FASTCARE Khánh Hội nhưng note ghi FASTCARE Quang Trung. |

Kết luận giá: dữ liệu có cấu trúc số học hợp lệ nhưng không đủ bằng chứng để gọi là giá thực tế. 90 ghi chú chéo cửa hàng là lỗi dữ liệu rõ ràng; cần xóa/đối soát từng dòng trước khi công khai. API catalog hiện vẫn trả số ước tính; xem finding High trong `AUDIT_REPORT.md`.

## Cửa hàng có nhận sửa không?

Trong JSON, mọi cửa hàng được gán 14–18 loại service (trung bình 17,54), không có shop nào thiếu service. Đây chỉ là liên kết dữ liệu; không có 1.193 URL nguồn hoặc ngày đối soát để chứng minh cửa hàng nhận dịch vụ đó ngoài đời.

| Thiết bị | Shop tự khai trong `devices` | API trả khi lọc | Đánh giá |
|---|---:|---:|---|
| phone | 62 | 62 | Chỉ là khai báo seed, chưa xác minh. |
| laptop | 57 | 57 | Chỉ là khai báo seed, chưa xác minh. |
| mac | 52 | 60 | API thêm 8 shop laptop qua heuristic. |
| tablet | 63 | 63 | Chỉ là khai báo seed, chưa xác minh. |
| pc | 21 | 57 | API thêm 36 shop laptop qua heuristic. |
| smartwatch | 0 | 62 | Lỗi logic: không shop nào khai smartwatch nhưng API trả toàn bộ shop phone. |

Có 45 liên kết shop–service không có thiết bị giao nhau, ví dụ shop chỉ `phone,tablet` vẫn được gán SSD/RAM/bàn phím/trackpad. Vì vậy kết quả lọc không đủ để tuyên bố “cửa hàng nhận sửa thiết bị đó”.

Nguồn lỗi runtime: `config/db.php:125-139` chủ động nới Mac/PC/smartwatch sang shop laptop/phone; `config/db.php:161-174` lọc service độc lập với thiết bị. Cần thay heuristic bằng quan hệ đã đối soát theo `(shop_id, device_type, service_id)`.
