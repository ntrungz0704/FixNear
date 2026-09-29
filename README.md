# 🛠️ FIXNEAR — Nền Tảng Tra Cứu & Kết Nối Sửa Chữa Thiết Bị Công Nghệ Cho Sinh Viên

> **"Hỏng đồ? Tìm đúng chỗ sửa."**  
> Dự án môn học **PDP104 – Kỹ năng làm việc** · **FPT Polytechnic Hồ Chí Minh**  
> Giảng viên hướng dẫn: **Cô Nguyễn Khánh Ly**  
> Nhóm thực hiện: **FIXNEAR (Nhóm 5 thành viên)**

---

## 👥 Danh Sách Thành Viên & Phân Công Trọng Trách

| STT | Họ và Tên | MSSV | Vai Trò | Nhiệm Vụ Chuyên Môn | Email Liên Hệ |
| :---: | :--- | :---: | :--- | :--- | :--- |
| **01** | **Nguyễn Phạm Thành Trung** | **PS47261** | Trưởng nhóm / Full-stack | Điều phối dự án, kiến trúc hệ thống Dual-Engine (JSON/MySQL), tích hợp Full-stack & Báo cáo tổng thể | `ntrungz0704@gmail.com` |
| **02** | **Nguyễn Minh Hiếu** | **PS47378** | Backend & Testing | Xây dựng API xử lý dữ liệu, bộ kiểm thử nghiệp vụ (CRUD/API/Form), bảo mật CSRF & phân quyền hệ thống | `Minhhieu3901@gmail.com` |
| **03** | **Trần Ngọc Xuân Đình** | **PS47346** | UI/UX & Frontend | Thiết kế giao diện Responsive đa thiết bị (Mobile/Tablet/PC), tương tác bản đồ Leaflet.js, tối ưu trải nghiệm | `tranngocxuandinh2004@gmail.com` |
| **04** | **Lê Bùi Hồng Phúc** | **PS47318** | Khảo sát & Dữ liệu | Khảo sát nhu cầu sinh viên thực tế, thu thập và chuẩn hóa bộ dữ liệu 68 cửa hàng & 319 dòng thiết bị TP.HCM | `lephuc34tanlap@gmail.com` |
| **05** | **Bàn Tiến Kim** | **PS47376** | Đối ngoại & Nội dung | Khảo sát thực địa, biên tập nội dung tiêu chuẩn an toàn "3 Không", truyền thông và kịch bản thuyết trình | `bantienkim204@gmail.com` |

---

## 🎯 7 Bước Hình Thành Ý Tưởng & Triển Khai (Mô Hình ASM 7 Bước)

1. **Bước 1: Tìm các vấn đề của nhóm đối tượng sinh viên**
   - Sinh viên thường xuyên gặp sự cố hỏng hóc máy tính, điện thoại nhưng không biết tiệm nào sửa uy tín quanh trường.
   - Lo sợ bị "chặt chém" giá, tráo đổi linh kiện ("luộc đồ"), trễ hẹn lấy máy ảnh hưởng việc học tập và đồ án.
2. **Bước 2: Chọn vấn đề trọng tâm**
   - Thiếu một kênh thông tin tập trung, minh bạch về giá cả, thời gian bảo hành và đánh giá thực tế của các cửa hàng sửa chữa điện tử quanh khu vực trường học.
3. **Bước 3: Xác định nguyên nhân gốc rễ**
   - Thông tin trên mạng bị phân mảnh hoặc chạy quảng cáo ảo; thiếu sự đối chiếu thực tế; sinh viên thiếu kiến thức kỹ thuật để tự đánh giá mức độ pan bệnh.
4. **Bước 4: Đưa ra các ý tưởng giải pháp**
   - Xây dựng website tra cứu nhanh theo vị trí GPS vệ tinh, tính khoảng cách km đến các tiệm gần nhất, phân loại chi tiết pan bệnh và khoảng giá tham khảo chuẩn.
5. **Bước 5: Lựa chọn giải pháp tối ưu (MVP FixNear)**
   - Phát triển nền tảng web chạy mượt mà, tải nhanh không phụ thuộc framework cồng kềnh, tích hợp Dual-Engine Database (chạy độc lập bằng file JSON an toàn hoặc mở rộng MySQL XAMPP).
6. **Bước 6: Lập kế hoạch hành động 3 tuần**
   - Kế hoạch phân công Gantt Chart chi tiết từng ngày cho 5 thành viên: từ nghiên cứu, thiết kế wireframe, lập trình backend, kiểm thử, xác minh dữ liệu thực tế đến đóng gói báo cáo thuyết trình.
7. **Bước 7: Đánh giá, đo lường và kiểm soát chất lượng**
   - Đạt 100% mục tiêu MVP: hoàn thiện 68 cửa hàng thật tại 17 quận TP.HCM, catalog 319 model máy, quy trình xử lý yêu cầu báo giá và tích hợp AI Chatbot kỹ thuật.

---

## 🚀 Các Tính Năng Nổi Bật

- 📍 **Định Vị Vệ Tinh GPS & Bản Đồ Tương Tác:** Tự động tính khoảng cách km thực tế từ vị trí sinh viên tới từng tiệm sửa chữa qua công thức Haversine và bản đồ Leaflet.
- 📱 **Kho Thiết Bị Siêu Khủng (319 Model):** Bao phủ 6 nhóm sản phẩm: *Điện thoại, Laptop Windows, MacBook / iMac, Máy tính bảng, PC Gaming / AIO, Smartwatch*.
- 🛡️ **Tiêu Chuẩn "3 Không" Minh Bạch:**
  - *Không tráo đổi linh kiện* (hướng dẫn ký tên linh kiện).
  - *Không vẽ thêm bệnh — Không khống giá* (báo giá trọn gói minh bạch).
  - *Không đùn đẩy trách nhiệm bảo hành* (cam kết phiếu bảo hành rõ ràng).
- 📋 **Quy Trình Tiếp Nhận Yêu Cầu & Tra Cứu:** Sinh viên gửi yêu cầu báo giá trực tuyến, theo dõi tiến độ sửa chữa bằng mã hồ sơ `FN-xxxx` và số điện thoại.
- 🤖 **Trợ Lý Kỹ Thuật AI Chatbot 24/7:** Tư vấn sơ bộ dấu hiệu hư hỏng, ước tính thời gian và chi phí xử lý tức thì cho sinh viên.
- ⚙️ **Cổng Quản Trị Toàn Diện (Admin Portal):** Quản lý hồ sơ tiệm, dịch vụ sửa chữa, duyệt phản hồi/đánh giá và báo cáo thông tin sai lệch từ cộng đồng.

---

## 💻 Hướng Dẫn Cài Đặt & Khởi Chạy Nhanh

### Cách 1: Khởi Chạy Nhanh 1-Chạm (Khuyến Nghị Cho Mọi Thành Viên)
1. Đảm bảo máy tính đã cài đặt **PHP** (có sẵn trong XAMPP hoặc cài đặt PHP CLI).
2. Nhấp đúp chuột vào file:
   ```bash
   start_server.bat
   ```
3. Trình duyệt sẽ tự động mở trang web tại địa chỉ:
   ```
   http://localhost:8000
   ```

### Cách 2: Chạy Qua Máy Chủ Web XAMPP Apache
1. Sao chép toàn bộ thư mục `fixnear` vào:
   ```
   C:\xampp\htdocs\fixnear
   ```
2. Mở **XAMPP Control Panel** và nhấn **Start** cho **Apache** (và MySQL nếu muốn dùng database quan hệ).
3. Mở trình duyệt và truy cập:
   ```
   http://localhost/fixnear
   ```

---

## 🔐 Thông Tin Tài Khoản Thử Nghiệm

- **Tài khoản Quản Trị Viên (Admin):**
  - URL quản trị: `http://localhost:8000/admin/`
  - Email: `admin@fixnear.vn`
  - Mật khẩu: `admin123`
- **Tài khoản Sinh Viên / Người Dùng (Demo):**
  - Email: `vannam.nguyen@gmail.com`
  - Mật khẩu: `password123`
  *(Hoặc bấm nút "Đăng ký" trên web để tạo tài khoản mới ngay lập tức)*

---

## 📁 Cấu Trúc Dự Án

```
├── FixNear_BaoCao_ASM_7Buoc_v2.pptx  # Slide thuyết trình ASM 7 Bước hoàn chỉnh
├── BAI 1 GIỚI THIỆU.pptx              # Tài liệu tham khảo bài giảng môn học
├── HƯỚNG DẪN LÀM ASM.pptx             # Hướng dẫn khung tiêu chuẩn ASM FPT Poly
├── start_server.bat                   # Bộ khởi chạy nhanh 1-click cho máy tính
├── README.md                          # Tài liệu dự án & thông tin thành viên
└── fixnear/                           # Toàn bộ mã nguồn ứng dụng web
    ├── admin/                         # Phân hệ Quản trị viên (Admin Dashboard)
    ├── api/                           # Các API xử lý gửi yêu cầu, đánh giá, báo cáo
    ├── assets/                        # Hình ảnh, CSS3, JavaScript tương tác, icons
    ├── config/                        # Cấu hình Dual-Engine DB & môi trường
    ├── data/                          # Cơ sở dữ liệu JSON an toàn & bền vững 100%
    ├── includes/                      # Header, Footer, Navbar, AI Chatbot, Pricing Engine
    ├── scripts/                       # Scripts kiểm thử hệ thống & tạo admin
    ├── index.php                      # Trang chủ nền tảng FixNear
    ├── models.php                     # Tra cứu 319 model thiết bị
    ├── model_detail.php               # Bảng giá chi tiết & pan bệnh dòng máy
    ├── shops.php                      # Danh mục 68 cửa hàng sửa chữa TP.HCM
    ├── shop_detail.php                # Hồ sơ chi tiết cửa hàng & đánh giá
    ├── search.php                     # Bộ lọc tìm kiếm & bản đồ GPS thông minh
    ├── request_repair.php             # Form gửi yêu cầu báo giá & sửa chữa
    ├── track_request.php              # Tra cứu tình trạng hồ sơ sửa chữa
    ├── contact.php                    # Liên hệ & gửi góp ý cho ban điều hành
    ├── login.php / register.php       # Hệ thống đăng nhập / đăng ký thành viên
    └── fixnear_db.sql                 # Bản SQL hỗ trợ triển khai cơ sở dữ liệu MySQL
```

---

## 📜 Cam Kết Học Thuật & Bản Quyền Nhóm

Dự án được xây dựng và phát triển bởi tập thể **Nhóm FIXNEAR — Lớp Kỹ năng làm việc (PDP104) — FPT Polytechnic TP.HCM**. Toàn bộ mã nguồn, tài liệu báo cáo và số liệu khảo sát được thực hiện nghiêm túc, trung thực và phục vụ trực tiếp cho mục đích học tập, đánh giá môn học.
