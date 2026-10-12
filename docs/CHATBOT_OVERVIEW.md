# TÀI LIỆU TỔNG QUAN HỆ THỐNG TRỢ LÝ CHATBOT AI FIXNEAR
**Phiên bản:** 2.0 (Smart Customer Support & Diagnosis)  
**Nhánh Git:** `member/tien-kim`  
**Cập nhật:** 13/10/2026  

---

## 1. Mục Tiêu & Triết Lý Thiết Kế
Chatbot FixNear hoạt động như một **Chuyên viên Chăm sóc Khách hàng & Cố vấn Kỹ thuật Thiết bị** chuyên nghiệp, tuân thủ 3 nguyên tắc bất khả xâm phạm:
1. **Ưu tiên giải quyết vấn đề và cảm xúc của khách hàng:** Không cố tình chèo kéo bán hàng hay quảng cáo khi khách đang gặp sự cố/khiếu nại.
2. **Chỉ cung cấp dữ liệu thực tế từ Database FixNear:** Giá cả, linh kiện, dòng máy và tiệm sửa chữa hoàn toàn dựa trên dữ liệu thật của nền tảng (319 dòng máy, 28 lỗi pan bệnh, 68 cửa hàng).
3. **Phản hồi tự nhiên, chuyên nghiệp như chat người thật:** Tự động điều chỉnh phong cách giao tiếp dựa theo tâm lý và ý định của khách.

---

## 2. Hệ Thống Nhận Diện Ý Định (Intent Classification - 7 Chế Độ)

Hệ thống phân tích ngữ cảnh tin nhắn theo 7 nhóm trạng thái:

| Ý định (Intent) | Trạng thái người dùng | Hành vi phản hồi của Chatbot |
| :--- | :--- | :--- |
| **`ANGRY_COMPLAINT`** | Khách tức giận, khiếu nại, thất vọng | **Chế độ xoa dịu**: Công nhận cảm xúc, xin lỗi chân thành, ẩn hoàn toàn quảng cáo shop/dịch vụ, hướng dẫn quy trình gửi khiếu nại qua trang `contact.php` hoặc `report.php` của BQT. |
| **`URGENT_SUPPORT`** | Thiết bị gặp nguy hiểm (rơi nước, phồng pin, khói, chập cháy) | **Chế độ khẩn cấp**: Đưa ra cảnh báo an toàn kỹ thuật ngay lập tức (ngắt nguồn, không cắm sạc, không tự cạy pin) trước khi hướng dẫn đưa tới trạm gần nhất. |
| **`REPAIR_TRACKING`** | Tra cứu trạng thái sửa chữa qua mã đơn hoặc SĐT | Tự động quét cơ sở dữ liệu `repair_requests.json` để kiểm tra tiến độ đơn hàng theo mã `FN-xxxx` hoặc số điện thoại. |
| **`PRODUCT_INQUIRY`** | Hỏi về linh kiện, thiết bị, giá tiền sửa chữa | Truy vấn cơ sở tri thức giá linh kiện 3 cấp (`RepairAtlasPricing`) theo model máy cụ thể, đưa ra khoảng giá chuẩn thị trường sinh viên. |
| **`SERVICE_INQUIRY`** | Hỏi dịch vụ, chính sách bảo hành, cửa hàng theo khu vực | Đối soát 68 cửa hàng uy tín theo Quận/Huyện, hiển thị địa chỉ, đánh giá sao, cam kết bảo hành và link dẫn trực tiếp đến trang cửa hàng. |
| **`GENERAL_GREETING`** | Chào hỏi xã giao, cảm ơn | Trò chuyện cởi mở, thân thiện, gợi ý các chủ đề hỗ trợ mà website có thể giải quyết. |
| **`OUT_OF_SCOPE`** | Hỏi việc không liên quan (chính trị, thời tiết, code...) | Từ chối lịch sự và khéo léo định hướng khách hàng quay trở lại chủ đề sửa chữa điện thoại, máy tính. |

---

## 3. Kiến Trúc Kỹ Thuật

```
[Trình duyệt Khách hàng]
    │
    ├── sessionStorage: Lưu tin nhắn & trạng thái mở chat (tự dọn dẹp khi đóng tab)
    │
    ▼ (Fetch POST JSON)
[Backend FixNear: api/chatbot.php]
    │
    ├── 1. Phân loại ý định (Rule-based & Context Heuristics)
    ├── 2. Tra cứu Cơ sở Dữ liệu nội bộ:
    │      ├─ data/models.json (319 dòng thiết bị)
    │      ├─ data/faults.json (28 danh mục pan bệnh)
    │      ├─ data/services.json & shop_services.json
    │      ├─ data/shops.json (68 tiệm sửa được kiểm duyệt)
    │      └─ data/repair_requests.json (Tra cứu đơn hàng)
    │
    ├── 3. Tổng hợp System Prompt ngữ cảnh & Dữ liệu thực tế
    │
    ▼ (cURL SSL REST API)
[Google Gemini AI Engine: gemini-3.8-flash]
    └── Tự động Fallback: gemini-3.8-flash ➔ gemini-flash-lite-latest ➔ gemini-flash-latest
```

---

## 4. Trải Nghiệm Giao Diện (UI/UX)
* **Khung Chat Tinh Gọn:** Kích thước chuẩn 390px × 560px, bo góc hiện đại, giao diện đồng bộ phong cách FixNear.
* **Đã loại bỏ thẻ vàng chiếm diện tích:** Dành toàn bộ không gian cho việc đọc và cuộn tin nhắn.
* **Nút Làm Mới Nhanh (`🔄`):** Cho phép người dùng chủ động xóa lịch sử và bắt đầu phiên hỏi đáp mới bất kỳ lúc nào.
* **Lưu phiên theo tab:** Reload (F5) trang không bị mất nội dung chat; khi đóng tab thì lịch sử tự hủy an toàn, bảo mật thông tin.
