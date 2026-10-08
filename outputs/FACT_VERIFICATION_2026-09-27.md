# FixNear — Báo cáo kiểm chứng dữ liệu trước triển khai

Ngày kiểm tra: 27/09/2026  
Phạm vi: 68 cửa hàng, đánh giá hiển thị, giá dịch vụ, ưu đãi học sinh–sinh viên, liên kết Google Maps và các tuyên bố chính sách sửa chữa.

## Kết luận ngắn

Không thể kết luận toàn bộ dữ liệu hiện tại là “thật và chính xác”. Bản dựng đã được sửa để không công khai các con số hoặc cam kết chưa có bằng chứng. Có thể đưa mã nguồn lên GitHub dưới dạng dự án tra cứu/ước tính; chưa nên quảng bá là sàn cửa hàng đã xác minh cho đến khi hoàn tất quy trình nguồn dữ liệu bên dưới.

## Kết quả kiểm tra

| Hạng mục | Số lượng | Có nguồn + ngày đối soát | Xử lý trên giao diện |
|---|---:|---:|---|
| Bản ghi cửa hàng | 68 | 0 | Ghi rõ là bản ghi; không gắn nhãn “đã xác minh” |
| Liên kết Google Maps | 68 | 0 Place ID + thời điểm | Không công bố lại điểm/số lượt; cho người dùng mở Maps trực tiếp |
| Nhận xét mẫu | 7 | 0 | Ẩn khỏi trang công khai; chỉ nhận xét người dùng tự gửi mới được hiển thị |
| Mức giá theo cửa hàng/dịch vụ | 1.193 | 0 | Ghi rõ là khoảng ước tính FixNear, không phải báo giá chính thức |
| Tuyên bố ưu đãi sinh viên | 15 | 0 theo từng chi nhánh | Không hiển thị huy hiệu/nội dung ưu đãi |
| Chính sách xem sửa/ký linh kiện | Có trong dữ liệu seed | 0 | Không hiển thị khi thiếu URL nguồn và ngày kiểm tra |

## Đối chiếu Google Maps

Đã thử mở/đối chiếu toàn bộ 68 URL tìm kiếm Maps đang lưu:

- 0 bản ghi khớp hoàn toàn với cả điểm và số lượt đánh giá đang lưu.
- 26 truy vấn trả về một địa điểm nhưng số liệu khác dữ liệu seed.
- 42 truy vấn không thể phân giải chắc chắn bằng truy vấn tự động.
- Một số URL tìm kiếm yếu trả về nhầm chi nhánh hoặc nhầm doanh nghiệp, nên không được tự động chép điểm về hệ thống.

Ví dụ chênh lệch quan sát tại thời điểm kiểm tra:

- Điện Thoại Vui 136 Nguyễn Thái Học: dữ liệu seed 4,9/3.850; kết quả hiện thời quan sát được 4,9/3.209.
- FASTCARE Nguyễn Thị Thập: dữ liệu seed 4,7/1.350; kết quả hiện thời quan sát được 4,9/1.596.
- ANT Mobile: dữ liệu seed 4,6/488; kết quả hiện thời quan sát được 5,0/236.
- CareS: dữ liệu seed 4,8/1.240; kết quả hiện thời quan sát được 5,0/5.925.

Các số trên chỉ là ảnh chụp theo thời điểm, không được dùng làm dữ liệu bền vững. Cách đúng là lưu Google Place ID, URL địa điểm chính xác và `google_verified_at`, rồi định kỳ đối soát lại.

## Nguồn chính thức tìm được

- Điện Thoại Vui có trang Back to School 2026 ở cấp hệ thống: https://dienthoaivui.com.vn/back-to-school. Điều kiện thay đổi theo chương trình/sản phẩm; nguồn này không chứng minh ưu đãi cho từng chi nhánh trong dữ liệu FixNear.
- Viện Di Động có trang giá sửa Huawei và thông tin một cơ sở: https://viendidong.com/sua-chua-huawei/. Chỉ dùng được cho dịch vụ/thiết bị/cơ sở nêu cụ thể trên trang.
- FASTCARE có bài giá tham khảo: https://fastcare.vn/blog/sua-dien-thoai-thai-ha.html. Đây là nội dung cho khu vực Hà Nội, không chứng minh giá của mọi chi nhánh TP.HCM.

## Thay đổi an toàn đã áp dụng

- Chỉ coi cửa hàng được xác minh khi có `source_url` và `verified_at`.
- Chỉ hiển thị điểm Google khi có `google_place_id` và `google_verified_at`.
- Chỉ hiển thị ưu đãi sinh viên khi có `student_discount_source_url` và `student_discount_verified_at`.
- Chỉ hiển thị chính sách sửa chữa khi có `policy_source_url` và `policy_verified_at`.
- Ẩn nhận xét seed không có nguồn; bỏ các nhận xét Google được viết cứng.
- Đổi toàn bộ giá chưa có nguồn thành khoảng ước tính, yêu cầu người dùng xác nhận báo giá.
- Bỏ tuyên bố “đối tác”, điểm Google viết cứng và thông tin hotline/email mẫu.
- Khóa `install.php` mặc định; chỉ chạy cục bộ khi đặt `FIXNEAR_ENABLE_INSTALLER=1`.
- Thêm `data/.htaccess` để chặn truy cập JSON trên Apache.

## Điều kiện bắt buộc trước production

1. Tạo kho nguồn thực tế cho từng cửa hàng: URL trang chính thức/Maps, Place ID, ngày kiểm tra và người kiểm tra.
2. Lấy bảng giá có ngày hiệu lực; lưu URL/ảnh/chứng từ và tách rõ linh kiện, công, bảo hành, VAT.
3. Xác nhận ưu đãi theo từng chi nhánh, điều kiện thiết bị, thời hạn và giấy tờ cần xuất trình.
4. Chỉ công khai đánh giá do người dùng gửi sau quy trình chống spam/moderation; không trình bày fixture như khách hàng thật.
5. Thay thông tin pháp lý, địa chỉ, hotline, email và chính sách riêng tư bằng thông tin của chủ thể vận hành thật.
6. Không phục vụ thư mục `data/` trực tiếp. Với Nginx phải thêm rule chặn riêng; `.htaccess` chỉ có tác dụng trên Apache.
7. Bỏ tài khoản/người dùng mẫu trước production, tạo admin bằng mật khẩu riêng và cấu hình gửi email thật.
8. Dùng HTTPS, rate limit/CAPTCHA cho form, backup dữ liệu và theo dõi lỗi.

## Trạng thái phát hành

- **GitHub cho mục đích học tập/demo:** được, sau khi rà soát lại dữ liệu người dùng mẫu và lịch sử commit.
- **Domain/hosting dạng bản thử nghiệm:** được nếu hiển thị rõ “dữ liệu tham khảo/chưa xác minh” và chặn `data/`.
- **Dịch vụ thương mại tuyên bố đã xác minh:** chưa đạt; cần hoàn tất 8 điều kiện trên.
