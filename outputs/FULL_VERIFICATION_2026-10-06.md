# Kiểm tra dự án FixNear — 06/10/2026

## Phạm vi và cách hiểu dữ liệu

Đã kiểm tra mã và dữ liệu trong thư mục dự án, thư mục Drive được cung cấp và các trang giá chính thức được dẫn bên dưới. Ảnh chụp màn hình và nội dung trong tài liệu Drive được dùng làm **dữ liệu để đối chiếu**, không phải chỉ dẫn vận hành. Các giá từ website là **giá niêm yết của hệ thống vào ngày kiểm tra**, chưa phải báo giá được cửa hàng xác nhận cho từng chi nhánh, tình trạng máy hay tồn kho.

## Kết quả kiểm kê

| Hạng mục | Kết quả | Ý nghĩa |
|---|---:|---|
| Model thiết bị trong catalog | 319 | 6 nhóm thiết bị |
| Tổ hợp model/lỗi còn hợp lệ | 4.232 | Có dự đoán từ ma trận, không phải giá thực tế |
| Tổ hợp có ít nhất một giá niêm yết kèm nguồn | 9/4.232 | iPhone 11–15, 29 cấu hình linh kiện |
| Tổ hợp chưa có nguồn giá | 4.223/4.232 | Không được gắn nhãn “đã xác thực” |
| Bản ghi cửa hàng | 68 | 0 bản ghi có đủ `source_url` và `verified_at` |
| Giá dịch vụ gắn với cửa hàng | 1.193 | 0 giá có nguồn và ngày đối soát theo dịch vụ/chi nhánh |
| Đánh giá mẫu | 7 | Chưa có đánh giá người dùng có bằng chứng; đã ẩn khỏi giao diện công khai |
| Phản hồi khảo sát trong Sheet | 25 | Mẫu thăm dò nhỏ; không suy rộng thành nhu cầu thị trường |

CSV [PRICE_COVERAGE_2026-10-06.csv](PRICE_COVERAGE_2026-10-06.csv) liệt kê từng tổ hợp model/lỗi, ba khoảng **dự đoán** và tình trạng nguồn. Có thể lọc `NO_SOURCE` để lập kế hoạch thu thập giá. Không có căn cứ để tự suy ra hãng linh kiện, linh kiện “zin/OEM/chính hãng”, thời gian sửa hoặc bảo hành từ khoảng dự đoán.

## Giá niêm yết đã đối chiếu

Đã ghi 29 cấu hình trong `data/pricing/source-quotes.json` và tab `Giá linh kiện có nguồn`. Bảng sau là một phần ví dụ; danh sách đầy đủ trên trang `prices.php` của website, mỗi hàng có URL và ngày đối chiếu.

| Model | Hạng mục | Đơn vị | Linh kiện được nêu trên trang | Giá (đ) | BH trang niêm yết |
|---|---|---|---|---:|---:|
| iPhone 13 | Màn hình | FASTCARE | JK Soft OLED | 1.990.000 | 12 tháng |
| iPhone 13 | Màn hình | FASTCARE | GX OLED | 1.800.000 | 12 tháng |
| iPhone 13 | Màn hình | FASTCARE | UTECK màn bóc máy xử lý kính | 2.990.000 | 12 tháng |
| iPhone 13 | Màn hình | FASTCARE | UTECK Pro màn bóc máy | 3.350.000 | 12 tháng |
| iPhone 13 | Pin | Điện Thoại Vui | Pisen dung lượng chuẩn | 920.000 | 12 tháng |
| iPhone 13 | Pin | Điện Thoại Vui | Pisen dung lượng siêu cao | 1.190.000 | 12 tháng |
| iPhone 13 | Pin | Điện Thoại Vui | Vmas dung lượng chuẩn | 720.000 | 18 tháng |
| iPhone 13 | Pin | Điện Thoại Vui | GENA dung lượng siêu cao | 1.050.000 | 12 tháng |

Nguồn: [FASTCARE — thay màn hình iPhone 13](https://fastcare.vn/thay-man-hinh-iphone/thay-man-hinh-iphone-13), [Điện Thoại Vui — thay pin iPhone 13](https://dienthoaivui.com.vn/thay-pin-iphone-13). Kiểm tra ngày 06/10/2026. Tên hãng trong bảng là **hãng linh kiện được trang bán hàng ghi**, không phải chứng nhận xuất xứ độc lập; Pisen, Vmas và GENA không được gọi là pin Apple. Cần gọi đúng chi nhánh để xác nhận giá trọn gói, tồn kho và điều kiện bảo hành trước khi đặt lịch.

Nguồn bổ sung: [FASTCARE iPhone 11](https://fastcare.vn/thay-man-hinh-iphone/thay-man-hinh-iphone-11), [iPhone 12](https://fastcare.vn/thay-man-hinh-iphone/thay-man-hinh-iphone-12), [iPhone 14](https://fastcare.vn/thay-man-hinh-iphone/thay-man-hinh-iphone-14), [iPhone 15](https://fastcare.vn/thay-man-hinh-iphone/thay-man-hinh-iphone-15); [Điện Thoại Vui pin iPhone 11](https://dienthoaivui.com.vn/thay-pin-iphone-11), [pin iPhone 14](https://dienthoaivui.com.vn/thay-pin-iphone-14), [pin iPhone 15](https://dienthoaivui.com.vn/thay-pin-iphone-15). Với các trang có giá sản phẩm và giá trong bài viết lệch nhau, dữ liệu mới dùng giá hiện trên ô sản phẩm và ghi rõ phạm vi/khuyến mãi. Chưa nhập pin iPhone 12 do trang cùng lúc hiển thị giá mâu thuẫn.

## Đã sửa trong dự án

- Thêm 29 giá có nguồn vào `data/pricing/source-quotes.json`, tách chúng khỏi kết quả của mô hình dự đoán. API và trang model trả/hiển thị trạng thái nguồn, ngày kiểm tra, linh kiện và URL. Trang `prices.php` liệt kê tên, hãng, giá, đơn vị và nguồn trên từng hàng, có bộ lọc.
- Đổi ba cấp giá từ hàm ý chất lượng linh kiện sang ba **kịch bản chi phí thấp/trung bình/cao**; bỏ khẳng định bảo hành, loại linh kiện và giá tại cửa hàng khi thiếu bằng chứng.
- Ẩn 7 đánh giá mẫu; chỉ công bố đánh giá mới do người dùng gửi trong luồng JSON. Ở cấu hình MySQL cũ, bảng đánh giá chưa có cột nguồn gốc nên chưa công bố đánh giá cho tới khi bổ sung cấu trúc và quy trình duyệt.
- Chuẩn hóa catalog, loại 557 liên kết lỗi không phù hợp loại thiết bị (ví dụ bản lề điện thoại không gập, phần mềm Windows trên điện thoại). Giữ nguyên 319 model.
- Hiển thị logo ảnh cục bộ cho 30 hãng thiết bị khi có file trên trang tra cứu và luồng gửi yêu cầu, có nhãn chữ/biểu tượng thay thế cho nhóm chung. Logo thiết bị **không** chứng minh quan hệ đối tác hay hãng của linh kiện thay thế. Logo của từng hãng linh kiện trong 8 giá niêm yết chưa được kiểm tra quyền dùng/tính chính thức nên hiển thị **tên hãng bằng chữ**.
- Bỏ giá chatbot không có nguồn, khẳng định chẩn đoán miễn phí, và tự mở quảng cáo ưu đãi khi chưa có xác minh theo chi nhánh. Mô tả lỗi model được ghi là giả thuyết cần chẩn đoán thực tế.
- Sửa bản SQL tạo mới: trạng thái xác minh/chính sách mặc định 0, số liệu Google mặc định rỗng, bổ sung trường nguồn và ngày đối soát, và lưu nguồn gốc review. 68 bản ghi seed được nhập với trạng thái chưa xác minh. Các cài đặt MySQL cũ cần nâng cấp schema riêng; dự án không tự thay đổi cơ sở dữ liệu đang vận hành.
- Chặn việc tự nhập bản SQL có dữ liệu mẫu khi môi trường đặt là `production`; tài liệu hosting đã giải thích rõ luồng JSON/MySQL.

## Đã sửa trong Google Sheet được cung cấp

Trong [Báo cáo Dự án FixNear](https://docs.google.com/spreadsheets/d/1tJQkbVFYuZEGc2EBCYsfyFEImfn8lX4CPwX_gJypfB4/edit):

- 68 trạng thái trong `Core 68 Stores (KNLV Tasks)` và 68 trạng thái tại `Top 4 / Khu Vực` chuyển từ `VERIFIED ONLINE` sang `UNVERIFIED` do thiếu chứng cứ theo từng trường; tỷ lệ xác minh chi tiết hiện 0/68.
- Thêm tab `Giá linh kiện có nguồn` với 29 cấu hình, giá, bảo hành niêm yết, URL, ngày kiểm tra và lưu ý phải xác nhận theo chi nhánh.
- Đổi tiêu đề/bổ chú thích ở trang tổng hợp để phân biệt danh sách tham khảo với danh sách đã xác minh.

## Phần chưa thể xác thực từ tài liệu hiện có

1. **68 cửa hàng:** cần URL chính thức hoặc Google Place ID đúng từng chi nhánh; ngày kiểm tra; địa chỉ, điện thoại, giờ mở, bản đồ, ưu đãi, chính sách và tình trạng hoạt động. Nguồn chung “Google Maps / Website chính thức” và liên kết tới trang tìm kiếm không đủ xác minh từng trường.
2. **1.193 giá dịch vụ của cửa hàng:** cần báo giá hoặc trang giá đúng chi nhánh, model, linh kiện, thời điểm, điều kiện bảo hành và chi phí trọn gói. Giá niêm yết cấp hệ thống ở bảng trên chưa tự động xác thực bản ghi giá chi nhánh.
3. **4.223 tổ hợp model/lỗi:** chưa có giá nguồn. CSV cung cấp danh sách đầy đủ để đối soát dần. Các khoảng hiện có chỉ là phép tính của mô hình, không có sai số thống kê đã hiệu chuẩn hoặc chứng cứ thực nghiệm để gọi là giá thị trường.
4. **Logo linh kiện:** cần tài sản/logo chính thức cho từng hãng linh kiện và quyền hiển thị thích hợp. Không nên tự vẽ logo, gán nhầm logo hãng thiết bị cho linh kiện hoặc dùng nhãn “chính hãng” không có chứng từ.
5. **Khảo sát và ERD:** Sheet có 25 phản hồi và dashboard tương ứng; Form trong thư mục Drive trả lỗi quyền truy cập 403 nên không kiểm tra được cấu hình câu hỏi/form gốc. Đã đọc ba trang ERD (người dùng, cửa hàng, dịch vụ, liên kết giá, đánh giá, báo sai, yêu cầu và liên hệ). ERD cũ ghi các mặc định xác minh/chính sách/điểm đánh giá thiếu chứng cứ; bản SQL tạo mới đã được sửa, còn sơ đồ Drive cần cập nhật theo schema mới.
6. **Dữ liệu mẫu còn lại:** yêu cầu sửa chữa, liên hệ và một số mô tả catalog có tính minh họa; cần đối chiếu với dữ liệu vận hành thật trước khi công bố các chỉ số kinh doanh hay cam kết.

## Kiểm tra kỹ thuật

- `python scripts/validate_catalog.py`: đạt; 319 model, 4.232 liên kết model/lỗi.
- `python scripts/validate_price_evidence.py`: đạt; 29 báo giá có nguồn, 9 tổ hợp.
- `python scripts/export_price_coverage.py`: xuất 4.232 dòng.
- `php scripts/preflight.php`: 12 đạt, 2 cảnh báo cấu hình phát triển, 0 lỗi.
- `php scripts/check_full.php`: các URL và router được kiểm tra đạt.
- PHP lint và Node syntax check của các file sửa: đạt. Trang model, cửa hàng và API trả HTTP 200 trên máy cục bộ; 30/30 file logo trả HTTP 200. Đã kiểm tra trực quan trang giá model; không có bằng chứng để tuyên bố toàn bộ đường đi người dùng hoặc từng logo đã được thử nghiệm thủ công.

## Tiêu chuẩn để đổi `UNVERIFIED` thành `VERIFIED ONLINE`

Chỉ đổi trạng thái khi từng bản ghi có nguồn gốc cụ thể, thời điểm kiểm tra, loại thông tin được xác minh và kết quả đối chiếu. Giá cần xác định đúng model, lỗi, linh kiện, chi nhánh và phạm vi chi phí. Nên lưu ảnh/chứng từ hoặc URL ổn định, ghi người/đơn vị đối soát và ngày hết hiệu lực của kết quả.
