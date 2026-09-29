# BÁO CÁO AUDIT HIỆN TRẠNG & KIỂM KÊ DANH MỤC BAN ĐẦU (MODULE M0)
**Dự án:** FixNear RepairAtlas — Danh Mục Hãng × Dòng Máy × Lỗi × Báo Giá Ước Tính  
**Thời gian thực hiện:** 2026-09-23 22:07 (Local Time)  
**Trạng thái Module:** HOÀN THÀNH (M0)

---

## 1. KHẢO SÁT HỆ THỐNG HIỆN TẠI (CODEBASE AUDIT)

### 1.1. Cấu trúc Công Nghệ
- **Ngôn ngữ & Runtime:** PHP 8.x, chạy trực tiếp trên máy chủ thử nghiệm cục bộ `http://localhost:8080`.
- **Cơ chế lưu trữ (Dual-Engine Data):** Singleton `FixNearDB` tại `config/db.php` ưu tiên kết nối MySQL PDO (`fixnear_db`), tự động chuyển sang đọc/ghi JSON File DB an toàn tại thư mục `data/` nếu MySQL tắt.
- **Frontend & Giao diện:** Vanilla HTML5, CSS3 hiện đại, Vanilla JavaScript không dùng framework nặng, bản đồ Leaflet.js tương tác.
- **Thư mục dữ liệu hiện tại (`data/`):**
  - `shops.json`: 68 cửa hàng thật 100% tại TP.HCM (4 cửa hàng/quận x 17 quận/huyện).
  - `services.json`: 18 lỗi dịch vụ tiêu chuẩn.
  - `shop_services.json`: Bảng quan hệ cửa hàng - dịch vụ.
  - `repair_requests.json`: Hồ sơ đơn đặt hẹn của khách hàng.
  - `reviews.json`, `reports.json`, `users.json`, `favorites.json`.

### 1.2. Hiện Trạng Wizard & Bảng Giá
- Trang `request_repair.php` hiện đang nhúng một khối JavaScript `WIZARD_DATA` tĩnh (dòng 544–964) định nghĩa tạm thời một số hãng và model. Dữ liệu này chưa có bảng giá theo model, chưa có phân cấp linh kiện và chưa có trang tra cứu chi tiết.
- Bảng 18 dịch vụ hiện tại chỉ có một khung giá chung (ví dụ màn hình 650k–3.2tr), không phân biệt được giữa flagship đắt tiền (iPhone 16 Pro Max, Z Fold) và máy giá rẻ (Redmi 13C).

### 1.3. Khảo Sát 2 Trang Tham Khảo
- **`repairotg.com`:**
  - Cung cấp giao diện duyệt 6 nhóm thiết bị (Phone, Laptop, Tablet, Console, Watch, Other) kèm số lượng cửa hàng hỗ trợ.
  - Cụm chip "Popular faults" (iPhone screen, MacBook battery, v.v.).
  - 4 trụ cột niềm tin: Verified shops, Transparent pricing, Warranty-backed, Same-day options.
- **`repairbookings.com`:**
  - Bộ 3 chỉ số cốt lõi: Giá (Price) – Bảo hành (Warranty) – Thời gian xử lý (Turnaround).
  - Luồng so sánh minh bạch trước khi đặt lịch.

---

## 2. BẢNG KIỂM KÊ DANH MỤC MODEL BASELINE (~319 MODELS)

Dựa trên dữ liệu đầu vào chuẩn tại PHẦN 4, toàn bộ danh mục được chuẩn hóa định danh slug theo schema: `{brand}-{series}-{model}`:

| STT | Nhóm Thiết Bị | Mã slug | Số Hãng | Số Lượng Model | Chi Tiết Các Hãng Phân Bổ |
| :---: | :--- | :---: | :---: | :---: | :--- |
| 1 | **Điện thoại** | `phone` | 10 | **140 models** | Apple iPhone (35), Samsung (27), Xiaomi/Redmi/POCO (19), Oppo (16), Vivo (8), Realme (7), Google Pixel (10), Sony Xperia (6), OnePlus (6), Khác (Huawei, Honor, Nothing, Nokia, ROG Phone, Infinix) (6) |
| 2 | **Laptop Windows** | `win_laptop` | 7 | **85 models** | Dell (19), Asus (16), HP (13), Lenovo (13), Acer (9), MSI (9), Khác (LG Gram, Surface, Gigabyte, VAIO) (6) |
| 3 | **MacBook / Mac** | `macbook` | 1 | **17 models** | MacBook Pro M1/M2/M3 & Intel (11), MacBook Air M1/M2/M3 & Intel (6) |
| 4 | **Máy tính bàn & AIO** | `pc_desktop` | 5 | **21 models** | PC Gaming (5), Workstation (3), PC Văn phòng (3), Máy bộ đồng bộ Dell/HP/Asus AIO (5), Apple iMac/Mac Mini/Mac Studio (5) |
| 5 | **Máy tính bảng** | `tablet` | 4 | **30 models** | Apple iPad Pro/Air/Gen/Mini (14), Samsung Galaxy Tab S/A (8), Xiaomi Pad (5), Khác (Lenovo, Surface, Huawei) (3) |
| 6 | **Đồng hồ thông minh** | `smartwatch` | 4 | **26 models** | Apple Watch Ultra/Series/SE (10), Galaxy Watch 4/5/6 (6), Garmin Fenix/Forerunner/Venu (5), Khác (Huawei, Amazfit, Xiaomi) (5) |
| **TỔNG** | **6 NHÓM** | — | **31 Hãng/Nhóm** | **319 MODELS** | **Độ phủ toàn diện cho thị trường TP.HCM** |

---

## 3. CHECKPOINT NGHIỆM THU M0
- [x] Đã hoàn thành audit toàn bộ codebase.
- [x] Đã trích xuất và phân tích insight từ 2 website tham khảo `repairotg.com` & `repairbookings.com`.
- [x] Đã kiểm kê và chốt danh sách **319 models** thuộc 6 nhóm thiết bị.
- [x] Sẵn sàng chuyển sang **Module M1: Schema, Faults Taxonomy, Pricing Engine & Validator**.
