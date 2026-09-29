# 🛠️ FIXNEAR — Nền Tảng Tra Cứu & Kết Nối Sửa Chữa Thiết Bị Công Nghệ Cho Sinh Viên

> **"Hỏng đồ? Tìm đúng chỗ sửa."**  
> Dự án môn học **PDP104 – Kỹ năng làm việc** · **FPT Polytechnic Hồ Chí Minh**  
> Giảng viên hướng dẫn: **Cô Nguyễn Khánh Ly**  
> Nhóm thực hiện: **FIXNEAR (Nhóm 5 thành viên)**

---

## 👥 Danh Sách Thành Viên, Phân Công & Nhánh Git Riêng

Mỗi thành viên được phân bổ **01 nhánh Git riêng biệt** để làm việc độc lập. Quy định nghiêm ngặt: **Mọi thay đổi code phải push vào nhánh cá nhân và tạo Pull Request (PR) để Trưởng nhóm duyệt trước khi được merge vào nhánh chính (`main`)**.

| STT | Họ và Tên | MSSV | Vai Trò | Nhánh Git Cá Nhân | Nhiệm Vụ Chuyên Môn | Email Liên Hệ |
| :---: | :--- | :---: | :--- | :--- | :--- | :--- |
| **01** | **Nguyễn Phạm Thành Trung** | **PS47261** | **Trưởng nhóm / Full-stack** | `main` *(Quản lý & Duyệt PR)* | Quản lý dự án, kiến trúc hệ thống PHP + MySQL, duyệt code Pull Request, tích hợp full-stack & báo cáo tổng thể | `ntrungz0704@gmail.com` |
| **02** | **Nguyễn Minh Hiếu** | **PS47378** | **Backend & Testing** | `member/minh-hieu` | Xây dựng xử lý dữ liệu PHP, kết nối CSDL MySQL PDO, bảo mật CSRF & bộ kiểm thử nghiệp vụ | `Minhhieu3901@gmail.com` |
| **03** | **Trần Ngọc Xuân Đình** | **PS47346** | **UI/UX & Frontend** | `member/xuan-dinh` | Thiết kế giao diện HTML5/CSS3, tiện ích Tailwind CSS, JavaScript tương tác, bản đồ Leaflet.js | `tranngocxuandinh2004@gmail.com` |
| **04** | **Lê Bùi Hồng Phúc** | **PS47318** | **Khảo sát & Dữ liệu** | `member/hong-phuc` | Thu thập & chuẩn hóa bộ dữ liệu 68 cửa hàng và bảng giá dịch vụ, quản lý file CSDL SQL | `lephuc34tanlap@gmail.com` |
| **05** | **Bàn Tiến Kim** | **PS47376** | **Đối ngoại & Nội dung** | `member/tien-kim` | Khảo sát thực địa, biên tập nội dung tiêu chuẩn an toàn "3 Không", tài liệu thuyết trình & truyền thông | `bantienkim204@gmail.com` |

---

## 💻 Tech Stack Chuẩn Môn Học (100% Web Native)

Dự án được xây dựng hoàn toàn dựa trên các công nghệ web tiêu chuẩn, tải nhanh, dễ cài đặt và tương thích tối đa với môi trường XAMPP / Hosting:

* **HTML5**: Cấu trúc ngữ nghĩa chuẩn (`<header>`, `<nav>`, `<main>`, `<section>`, `<article>`, `<footer>`).
* **CSS3**: Bố cục hiện đại Flexbox, CSS Grid, Glassmorphism, Responsive 100% trên Mobile, Tablet và Desktop.
* **Tailwind CSS**: Tích hợp bộ tiện ích CSS utility classes nhanh qua CDN giúp tinh chỉnh style linh hoạt.
* **JavaScript (ES6+)**: Xử lý tương tác client-side mượt mà, định vị GPS qua Geolocation API, bản đồ Leaflet.js, bộ lọc tìm kiếm tức thì, Trợ lý AI Chatbot tư vấn sửa chữa.
* **PHP 8.x**: Xử lý backend chuẩn mực, kiến trúc module hóa an toàn, phiên làm việc Session, bảo vệ CSRF Token, Rate Limiting chống spam, mã hóa mật khẩu BCRYPT.
* **MySQL / MariaDB**: Hệ quản trị cơ sở dữ liệu quan hệ với 8 bảng chuẩn hóa (`users`, `shops`, `services`, `shop_services`, `repair_requests`, `reviews`, `wrong_info_reports`, `contact_messages`), kết nối qua **PHP Data Objects (PDO)** chống SQL Injection 100%.

---

## 🌿 QUY TRÌNH LÀM VIỆC VỚI GIT & GITHUB (BẮT BUỘC CHO 4 THÀNH VIÊN)

Để đảm bảo source code không bị xung đột (conflict) và code chỉ được đưa lên nhánh chính sau khi được Trưởng nhóm kiểm tra, **toàn bộ 4 thành viên phải tuân thủ nghiêm ngặt quy trình sau**:

```
[Thành viên code trên nhánh cá nhân]
             │
             ▼
[Commit & Push lên nhánh riêng trên GitHub]
             │
             ▼
[Tạo Pull Request (PR) về nhánh 'main']
             │
             ▼
[Trưởng nhóm Nguyễn Phạm Thành Trung Review & Test]
             │
      ┌──────┴──────┐
   (Đạt)         (Cần sửa)
      │             │
      ▼             ▼
[Merge vào main]  [Phản hồi yêu cầu sửa lại]
```

### 1. Sơ Đồ Phân Nhánh
* Nhánh **`main`**: Nhánh chính (Production). **Được bảo vệ nghiêm ngặt bằng Git Hook**. Không ai được phép `push` trực tiếp vào `main`.
* Nhánh **`member/minh-hieu`**: Nhánh làm việc riêng của bạn Nguyễn Minh Hiếu.
* Nhánh **`member/xuan-dinh`**: Nhánh làm việc riêng của bạn Trần Ngọc Xuân Đình.
* Nhánh **`member/hong-phuc`**: Nhánh làm việc riêng của bạn Lê Bùi Hồng Phúc.
* Nhánh **`member/tien-kim`**: Nhánh làm việc riêng của bạn Bàn Tiến Kim.

---

### 2. Các Bước Làm Việc Hàng Ngày Dành Cho Thành Viên

#### Bước 1: Chuyển sang đúng nhánh của mình
Mở terminal (Git Bash hoặc VS Code Terminal) tại thư mục dự án và chuyển sang nhánh được phân công:
```bash
# Ví dụ thành viên Minh Hiếu:
git checkout member/minh-hieu

# Hoặc nếu là Xuân Đình:
git checkout member/xuan-dinh

# Hoặc Hồng Phúc:
git checkout member/hong-phuc

# Hoặc Tiến Kim:
git checkout member/tien-kim
```

#### Bước 2: Kéo code mới nhất từ nhánh `main` về nhánh mình
Trước khi bắt đầu viết code mới, luôn luôn kéo code mới nhất từ nhánh `main` về để tránh bị lỗi thời:
```bash
git pull origin main
```

#### Bước 3: Viết code, chỉnh sửa và kiểm thử
Thực hiện các công việc theo phân công trên máy cá nhân. Sau khi code và test chạy tốt:
```bash
# Xem các file vừa sửa đổi
git status

# Thêm tất cả thay đổi vào vùng chuẩn bị commit
git add .

# Tạo commit với thông điệp rõ ràng
git commit -m "feat: hoàn thiện giao diện bản đồ tìm kiếm tiệm gần nhất"
```

> **💡 Quy tắc viết Commit Message chuẩn:**
> - `feat: ...` : Thêm tính năng mới (vd: `feat: thêm bộ lọc theo khoảng giá`)
> - `fix: ...`  : Sửa lỗi (vd: `fix: sửa lỗi hiển thị số điện thoại cửa hàng`)
> - `docs: ...` : Chỉnh sửa tài liệu (vd: `docs: cập nhật hướng dẫn cài đặt`)
> - `style: ...`: Chỉnh sửa CSS, giao diện (vd: `style: căn chỉnh padding thẻ cửa hàng`)

#### Bước 4: Đẩy (Push) code lên nhánh cá nhân trên GitHub
```bash
# Ví dụ thành viên Minh Hiếu push vào nhánh của mình:
git push origin member/minh-hieu

# Ví dụ thành viên Xuân Đình push vào nhánh của mình:
git push origin member/xuan-dinh

# Ví dụ thành viên Hồng Phúc:
git push origin member/hong-phuc

# Ví dụ thành viên Tiến Kim:
git push origin member/tien-kim
```
*(Nếu cố tình gõ `git push origin main`, hệ thống Git Hook sẽ tự động chặn lại và từ chối lệnh đẩy).*

#### Bước 5: Tạo Pull Request (PR) trên GitHub
1. Truy cập vào kho mã nguồn dự án: [https://github.com/ntrungz0704/FixNear](https://github.com/ntrungz0704/FixNear)
2. Bạn sẽ thấy một thanh thông báo màu vàng hiện lên kèm nút xanh: **"Compare & pull request"**. Hãy bấm vào đó.
3. Kiểm tra xem nhánh nhận có phải là `base: main` và nhánh gửi là nhánh cá nhân của bạn (ví dụ: `compare: member/minh-hieu`).
4. Ghi rõ tiêu đề và mô tả ngắn gọn những gì bạn đã làm trong lần cập nhật này.
5. Bấm nút xanh **"Create pull request"**.
6. Nhắn tin báo cho Trưởng nhóm (Nguyễn Phạm Thành Trung) vào kiểm tra và duyệt code.

---

### 3. Quy Trình Trưởng Nhóm Duyệt & Hợp Nhất (Review & Merge)

Khi nhận được Pull Request từ thành viên:
1. Trưởng nhóm mở mục **Pull requests** trên GitHub.
2. Nhấp vào PR của thành viên, chuyển sang tab **Files changed** để đối soát từng dòng code được sửa đổi.
3. Nếu code đạt chuẩn:
   - Nhấn nút xanh **"Merge pull request"** -> chọn **"Confirm merge"**.
   - Code sẽ chính thức được tích hợp an toàn vào nhánh `main`.
4. Nếu code có lỗi hoặc chưa đạt yêu cầu:
   - Viết bình luận trực tiếp trên dòng code bị lỗi yêu cầu thành viên chỉnh sửa lại.
   - Thành viên sửa lại trên máy cá nhân và lặp lại Bước 3 & 4 (PR trên GitHub sẽ tự động cập nhật commit mới).

---

## 🚀 Hướng Dẫn Cài Đặt & Chạy Dự Án

### Cách 1: Khởi Chạy Nhanh 1-Chạm (Khuyến Nghị)
1. Cài đặt **XAMPP** (hoặc PHP 8.x trên máy tính).
2. Mở **XAMPP Control Panel** và nhấn **Start** cho **MySQL**.
3. Nhấp đúp chuột vào file:
   ```bash
   start_server.bat
   ```
4. Trình duyệt sẽ tự động mở trang web tại địa chỉ:
   ```
   http://localhost:8000
   ```
5. *(Lần đầu tiên chạy)*: Truy cập `http://localhost:8000/install.php` và bấm **"Bắt Đầu Cài Đặt CSDL MySQL Vào XAMPP"** để hệ thống tự động tạo database `fixnear_db` và nạp đủ 8 bảng dữ liệu.

### Cách 2: Triển Khai Qua Máy Chủ Web XAMPP Apache
1. Sao chép thư mục `fixnear` vào thư mục web của XAMPP:
   ```
   C:\xampp\htdocs\fixnear
   ```
2. Mở **XAMPP Control Panel** và nhấn **Start** cả **Apache** và **MySQL**.
3. Mở trình duyệt và truy cập:
   ```
   http://localhost/fixnear/install.php
   ```
   Bấm nút cài đặt CSDL 1-Click để hoàn tất nạp database.
4. Sử dụng website tại:
   ```
   http://localhost/fixnear
   ```

---

## 🔐 Thông Tin Tài Khoản Thử Nghiệm

* **Tài khoản Quản Trị Viên (Admin):**
  * URL quản trị: `http://localhost:8000/admin/`
  * Email: `admin@fixnear.vn`
  * Mật khẩu: `admin123`
* **Tài khoản Sinh Viên / Người Dùng (Demo):**
  * Email: `vannam.nguyen@gmail.com`
  * Mật khẩu: `password123`
  *(Hoặc bấm nút "Đăng ký" trên thanh điều hướng để tạo tài khoản mới)*

---

## 📁 Cấu Trúc Dự Án

```
├── .githooks/                         # Git Hook chặn đẩy code trực tiếp vào main
│   └── pre-push                       # Hook bảo vệ nhánh main tự động
├── FixNear_BaoCao_ASM_7Buoc_v2.pptx  # Slide thuyết trình ASM 7 Bước hoàn chỉnh
├── BAI 1 GIỚI THIỆU.pptx              # Tài liệu tham khảo bài giảng môn học
├── HƯỚNG DẪN LÀM ASM.pptx             # Hướng dẫn khung tiêu chuẩn ASM FPT Poly
├── start_server.bat                   # Bộ khởi chạy nhanh máy chủ PHP 1-click
├── README.md                          # Tài liệu dự án, phân công & Git Workflow
└── fixnear/                           # Toàn bộ mã nguồn ứng dụng web
    ├── admin/                         # Phân hệ Bảng Điều Khiển Quản Trị Viên
    │   ├── index.php                  # Thống kê KPI, trạng thái CSDL MySQL
    │   ├── requests.php               # Quản lý yêu cầu báo giá & sửa chữa
    │   ├── shops.php / shop_edit.php  # Thêm / Sửa / Xóa danh sách cửa hàng
    │   ├── services.php               # Quản lý bảng giá & dịch vụ từng tiệm
    │   ├── reviews.php                # Duyệt & phản hồi đánh giá khách hàng
    │   └── reports.php                # Xử lý báo cáo thông tin sai từ cộng đồng
    ├── api/                           # Các API xử lý AJAX (tra cứu, gửi đơn, đánh giá)
    ├── assets/
    │   ├── css/style.css              # Custom CSS3 bố cục & animation
    │   ├── js/                        # JavaScript xử lý định vị GPS, bản đồ, lọc
    │   └── images/                    # Logo, avatar, hình ảnh thực tế các tiệm
    ├── config/
    │   ├── app.php                    # Cấu hình môi trường & thông số kết nối MySQL
    │   └── db.php                     # Lớp FixNearDB: PDO MySQL Engine & JSON Fallback
    ├── data/                          # Bản sao lưu dữ liệu JSON dự phòng
    ├── includes/
    │   ├── header.php                 # Thẻ Head, nạp Tailwind CSS & Leaflet.js
    │   ├── navbar.php                 # Thanh điều hướng sinh viên & nút chức năng
    │   ├── footer.php                 # Chân trang & cam kết tiêu chuẩn "3 Không"
    │   ├── chatbot.php                # Trợ lý AI tư vấn bắt bệnh thiết bị 24/7
    │   └── pricing_engine.php         # Bộ tính toán khoảng giá linh kiện theo dòng máy
    ├── contact.php                    # Trang liên hệ & hợp tác
    ├── index.php                      # Trang chủ nền tảng FixNear
    ├── install.php                    # Trình cài đặt CSDL MySQL 1-Click
    ├── login.php / register.php       # Đăng nhập & Đăng ký tài khoản
    ├── models.php / model_detail.php  # Danh mục 319 dòng thiết bị & giá sửa
    ├── request_repair.php             # Form tạo yêu cầu đặt lịch & ước tính giá
    ├── search.php                     # Tìm kiếm cửa hàng theo quận & GPS vệ tinh
    ├── shops.php / shop_detail.php    # Chi tiết hồ sơ tiệm sửa & đánh giá thật
    ├── track_request.php              # Tra cứu tiến độ sửa chữa bằng mã hồ sơ
    └── fixnear_db.sql                 # CSDL MySQL chuẩn 8 bảng (315KB đầy đủ dữ liệu)
```

---

## 🎯 7 Bước Hình Thành Ý Tưởng & Triển Khai (Mô Hình ASM PDP104)

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
   - Phát triển nền tảng web chạy mượt mà bằng PHP & MySQL, tải nhanh không phụ thuộc framework cồng kềnh, tích hợp Tailwind CSS và Leaflet Map tương tác.
6. **Bước 6: Lập kế hoạch hành động 3 tuần**
   - Kế hoạch phân công Gantt Chart chi tiết từng ngày cho 5 thành viên trên 5 nhánh Git: từ nghiên cứu, thiết kế wireframe, lập trình backend, kiểm thử, xác minh dữ liệu thực tế đến đóng gói báo cáo thuyết trình.
7. **Bước 7: Đánh giá, đo lường và kiểm soát chất lượng**
   - Đạt 100% mục tiêu MVP: hoàn thiện 68 cửa hàng thật tại 17 quận TP.HCM, catalog 319 model máy, quy trình xử lý yêu cầu báo giá và tích hợp AI Chatbot kỹ thuật.

---

## 📜 Cam Kết Học Thuật & Bản Quyền Nhóm

Dự án được xây dựng và phát triển bởi tập thể **Nhóm FIXNEAR — Lớp Kỹ năng làm việc (PDP104) — FPT Polytechnic TP.HCM**. Toàn bộ mã nguồn, tài liệu báo cáo và số liệu khảo sát được thực hiện nghiêm túc, trung thực và phục vụ trực tiếp cho mục đích học tập, đánh giá môn học.
