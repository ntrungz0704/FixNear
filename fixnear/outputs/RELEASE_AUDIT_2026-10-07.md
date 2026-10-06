# FixNear — kiểm tra trước khi đẩy mã (07/10/2026)

## Đã thực hiện

- Bộ lọc bảng giá đi theo nhóm thiết bị → hãng → model → hạng mục, áp dụng cho 319 model hiện có. Kết quả hiển thị theo bố cục co giãn ở desktop, tablet và điện thoại.
- Giữ màu cam chủ đạo và phục hồi banner `public/assets/images/promo_banner.jpg`.
- Logo của 31 thương hiệu được thay bằng tài sản vector/ảnh từ nguồn đã ghi trong `data/brand-logo-sources.json`, kiểm tra ngày 07/10/2026. Lưới thương hiệu trên trang chủ và biểu mẫu đặt dịch vụ dùng chung nguồn ảnh cho điện thoại, laptop, Mac, PC, tablet và đồng hồ; trang danh mục/model/bảng giá dùng cùng bảng ánh xạ. Các nhóm không phải hãng dùng nhãn chữ. Loại bỏ logo tự vẽ cũ khỏi dữ liệu giao diện.
- Tách giá ước tính khỏi giá niêm yết có URL nguồn. Dữ liệu hiện có 33 dòng giá dẫn nguồn, bao phủ 12 cặp model/lỗi; 319 model đều có giá **dự đoán**, không phải giá xác thực.
- 35/68 bản ghi thuộc 5 hệ thống có số nhà/tên đường và hotline đối chiếu với website chính thức được công bố ở giao diện công khai. 33 bản ghi còn lại ở màn hình quản trị để đối soát tiếp. Ảnh stock sai ngữ cảnh đã được thay bằng logo website của hệ thống hoặc ảnh chụp website có nguồn; nơi không có ảnh sẽ ghi rõ.
- Số cửa hàng và khu vực ở trang chủ/chân trang lấy từ đúng danh sách công khai (35 bản ghi), tránh hiển thị số bản ghi nội bộ ở nút mở danh sách.
- Tên hãng trên ô lựa chọn được xuống dòng đầy đủ thay vì cắt bằng dấu ba chấm; số liệu công khai hiển thị ngay giá trị thật, không chạy hiệu ứng đếm trung gian.
- Yêu cầu đặt dịch vụ của khách và thành viên, đăng ký/đăng nhập, đánh giá chờ duyệt, trả lời đánh giá và trạng thái yêu cầu phía quản trị được kiểm thử bằng dữ liệu cô lập. Màn hình quản trị thăm dò trạng thái mới mỗi 10 giây.

## Bằng chứng cửa hàng

Nguồn đối chiếu tên/địa chỉ: [Điện Thoại Vui](https://dienthoaivui.com.vn/thay-cap-sac-iphone-12), [FASTCARE](https://fastcare.vn/he-thong-cua-hang), [Bệnh Viện Điện Thoại 24h](https://chamsocdidong.com/hospital.html), [Sài Gòn Số](https://saigonso.com/thay-chan-sac-ip-11-pro-max), [Viện Di Động](https://viendidong.com/shop/). Mã cửa hàng, hash bản ghi, ngày kiểm tra và URL nguồn nằm trong `data/shop-address-evidence.json`.

## Giới hạn cần giữ rõ khi công bố

- Tọa độ ghim, khoảng cách tính từ ghim, giờ mở cửa, chính sách và điểm Google chưa được xác nhận đầy đủ cho từng chi nhánh. Nút chỉ đường tìm theo địa chỉ đã đối chiếu; ghim trên bản đồ được ghi là tham khảo.
- Logo hệ thống và ảnh trang web không phải ảnh mặt tiền của từng chi nhánh.
- Ảnh robot là hình minh họa cho trợ lý FixNear; banner quảng cáo là tài sản cũ do dự án cung cấp. Chúng không được trình bày như ảnh thiết bị hoặc ảnh cửa hàng thật. Không có bộ ảnh sản phẩm chính thức cho từng model trong kho dữ liệu hiện tại.
- Banner cũ chứa nội dung ưu đãi 15% sinh viên; chưa có nguồn xác nhận ưu đãi này còn hiệu lực. Giao diện nhắc khách xác nhận trực tiếp.
- Luồng test dùng JSON trong máy phát triển. Chưa kiểm thử môi trường MySQL/hosting thật, đồng bộ nhiều máy chủ hoặc giao dịch thực tế với cửa hàng. Đồng bộ quản trị là polling 10 giây, không phải WebSocket tức thì.

## Kiểm thử đã chạy

- `python scripts/validate_catalog.py` — 319 model, 4.232 cặp model/lỗi.
- `python scripts/validate_price_evidence.py` — 33 dòng giá dẫn nguồn.
- `python scripts/verify_live_quotes.py` — 12 trang nguồn còn hiển thị 33 mức giá; có dấu hiệu tên linh kiện ở gần giá. Kiểm tra này chưa xác nhận mọi điều khoản bán hàng tại chi nhánh.
- `python scripts/validate_shop_evidence.py` — 35 địa chỉ và số điện thoại khớp trang nguồn theo bộ kiểm.
- `python scripts/validate_shop_media.py` — 35 cửa hàng công khai, không có URL media lỗi trong lúc kiểm.
- `python scripts/validate_brand_logos.py` — đối chiếu 31 tệp thương hiệu với nguồn và mã SHA-256, không còn tệp logo cũ trong thư mục.
- `python scripts/visual_qa_brands.py` — lưới hãng và biểu mẫu đặt dịch vụ cho cả sáu nhóm thiết bị, ảnh tải thành công và không tràn ngang.
- `python scripts/test_user_flows.py` — bộ lọc, đặt dịch vụ khách/thành viên, tài khoản, quản trị, duyệt/trả lời đánh giá.
- `python scripts/visual_qa.py` — 11 tuyến trang, gồm trang chính, bảng giá, model, cửa hàng, bản đồ, tài khoản và đặt dịch vụ ở 1440/768/390 px; không cuộn ngang.
- `php scripts/check_full.php` — URL công khai, API, quyền admin và chặn đường dẫn dữ liệu.
- `php -l` cho toàn bộ tệp PHP — không có lỗi cú pháp.
