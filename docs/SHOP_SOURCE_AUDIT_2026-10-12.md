# Kiểm tra nguồn cửa hàng và giá — 12/10/2026

- Dữ liệu gốc: **68 bản ghi / 17 khu vực**. Tất cả bản ghi có thể tra cứu trên website.
- Địa chỉ có thể đối chiếu trên website của cửa hàng/hệ thống: **55 bản ghi**; số điện thoại khớp nguồn: **54 bản ghi**. CareS có địa chỉ trên nguồn nhưng số gọi trong dữ liệu không khớp nên không hiển thị. Mỗi bản ghi được gắn khóa nguồn và băm tên + số nhà trong `data/shop-address-evidence.json`.
- **13 bản ghi còn lại chưa có nguồn chính thức khớp địa chỉ**. Website hiển thị trạng thái cần kiểm tra và không đưa ra số điện thoại chưa đối chiếu.
- Bảng giá có **50 lựa chọn có nguồn / 13 cặp model–dịch vụ** trong tổng số 319 model. Các mức ước tính của FixNear được tách riêng với giá niêm yết của cửa hàng.

## Bản ghi cần đối chiếu tiếp

| ID | Bản ghi | Địa chỉ trong dữ liệu |
| ---: | --- | --- |
| 6 | Viện Di Động - 608 Lê Văn Sỹ Q.3 | 608 Lê Văn Sỹ, Phường 14, Quận 3, TP.HCM |
| 7 | FASTCARE - 580 Cách Mạng Tháng 8 Q.3 | 580 Cách Mạng Tháng 8, Phường 11, Quận 3, TP.HCM |
| 8 | Sửa Chữa Laptop 24h - CMT8 Q.3 | 283/47 Cách Mạng Tháng 8, Phường 12, Quận 3, TP.HCM |
| 12 | Hoàng Vũ Mobile - 150 Đoàn Văn Bơ Q.4 | 150 Đoàn Văn Bơ, Phường 9, Quận 4, TP.HCM |
| 13 | Viện Di Động - 60 Trần Phú Q.5 | 60 Trần Phú, Phường 4, Quận 5, TP.HCM |
| 20 | Hcare - 206 Hậu Giang Q.6 | 206 Hậu Giang, Phường 9, Quận 6, TP.HCM |
| 26 | FASTCARE - 231 Phạm Thế Hiển Q.8 | 231 Phạm Thế Hiển, Phường 3, Quận 8, TP.HCM |
| 33 | Điện Thoại Vui - 12 Ông Ích Khiêm Q.11 | 12 Ông Ích Khiêm, Phường 10, Quận 11, TP.HCM |
| 44 | Viện Di Động - 241 Bạch Đằng Bình Thạnh | 241 Bạch Đằng, Phường 15, Quận Bình Thạnh, TP.HCM |
| 46 | FASTCARE - 600 Kinh Dương Vương Bình Tân | 600 Kinh Dương Vương, Phường An Lạc, Quận Bình Tân, TP.HCM |
| 51 | Viện Di Động - 73 Trần Thị Nghỉ Gò Vấp | 73 Trần Thị Nghỉ, Phường 7, Quận Gò Vấp, TP.HCM |
| 54 | Điện Thoại Vui - 161 Phan Xích Long Phú Nhuận | 161 Phan Xích Long, Phường 2, Quận Phú Nhuận, TP.HCM |
| 56 | Giahuymobile - 461 Nguyễn Kiệm Phú Nhuận | 461-463 Nguyễn Kiệm, Phường 9, Quận Phú Nhuận, TP.HCM |

Nguồn tham chiếu đáng tin cậy là trang vị trí/dịch vụ trên website của chính đơn vị. `scripts/validate_shop_evidence.py` kiểm tra số nhà và số điện thoại xuất hiện trên trang dẫn; `scripts/validate_price_evidence.py` kiểm tra cấu trúc giá và mã model/dịch vụ. Phần chưa có nguồn không được đánh dấu đã xác thực.

## Nguồn đã đối chiếu

| Hệ thống/cửa hàng | Trang |
| --- | --- |
| dienthoaivui | https://dienthoaivui.com.vn/tong-hop-dia-chi-cua-hang-sua-chua-tot-nhat-tai-tphcm |
| dienthoaivui_extra | https://dienthoaivui.com.vn/thay-cap-sac-iphone-12 |
| fastcare | https://fastcare.vn/he-thong-cua-hang |
| chamsocdidong | https://chamsocdidong.com/hospital.html |
| saigonso | https://saigonso.com/lien-he |
| viendidong | https://viendidong.com/shop/ |
| baohanhone | https://baohanhone.com/pages/dat-lich-hen |
| laptopcare | https://laptopcare.vn/tin-tuc/luu-y-trung-tam-dich-vu-laptopcare-chi-co-tai-quan-4-tphcm |
| icare | https://icare.center/tim-kiem |
| libby | https://libbyrepaircenter.com/ |
| iservice | https://www.iservice.vn/pages/lien-he |
| capcuulaptop | https://capcuulaptop.com/ |
| thegioilaptop24h | https://www.thegioilaptop24h.com/gioi-thieu |
| benhvienlaptop | https://benhvienlaptop.com/sua-laptop-binh-thanh/ |
| thuanphat | https://thuanphatmobile.vn/thay-pin-ipad.html |
| antmobile | https://antmobile.com.vn/shop/ |
| tranphat | https://tranphatmobile.vn/lien-he/ |
| songthien | https://songthienmobile.com/ |
| hungphat | https://hungphatcomputer.com/lien-he-hung-phat-computer/ |
| daitin | https://daitinmobile.com/lien-he/ |
| viendidong_locations | https://viendidong.com/he-thong-cua-hang/ |
| cares | https://cares.vn/san-pham/thay-pin-iphone-14/ |
