#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
FixNear RepairAtlas — Generate Full Phone Catalog (~140 models)
Bao gồm Samsung, Xiaomi, Oppo, Vivo, Realme, Google, Sony, OnePlus, Others
"""

import sys
import json
from pathlib import Path

# Force UTF-8 output on Windows
if sys.platform == "win32":
    sys.stdout.reconfigure(encoding="utf-8")

ROOT_DIR = Path(__file__).resolve().parent.parent
PHONE_DIR = ROOT_DIR / "data" / "catalog" / "phone"
PHONE_DIR.mkdir(parents=True, exist_ok=True)

DEFAULT_FAULTS = [
    "screen", "glass-press", "battery", "charging-port", "water-damage",
    "mainboard", "camera", "speaker", "mic", "hinge-body", "back-glass",
    "thermal", "software", "data-recovery", "diagnostic"
]

FOLD_FAULTS = DEFAULT_FAULTS + ["foldable-cable"]

def save(brand, data):
    p = PHONE_DIR / f"{brand}.json"
    with open(p, "w", encoding="utf-8") as f:
        json.dump(data, f, ensure_ascii=False, indent=2)
    print(f"  ✅ Đã lưu {brand}.json: {len(data)} models")

# --- 2. SAMSUNG (27 models) ---
samsung_models = [
    # Flagship Ultra & Plus (P4)
    {
        "id": "samsung-galaxy-s24-ultra",
        "name": "Galaxy S24 Ultra",
        "brand": "samsung",
        "deviceType": "phone",
        "tier": "P4",
        "releaseYear": 2024,
        "specs": {"screen": "6.8 inch Dynamic AMOLED 2X 120Hz phẳng, kính Gorilla Armor chống chói", "chip": "Snapdragon 8 Gen 3 for Galaxy (4nm)", "battery": "5000 mAh", "charging": "45W Super Fast Charging 2.0"},
        "supportedFaults": DEFAULT_FAULTS,
        "knownIssues": [
            {"id": "grainy-display", "title": "Hiện tượng hạt cát sần màn hình (Grainy / Mura Screen) ở độ sáng thấp", "severity": "medium", "confidence": "high", "symptoms": "Trong phòng tối khi giảm độ sáng dưới 20%, nền xám xuất hiện các hạt lấm tấm không đồng đều.", "solution": "Cân chỉnh gamma phần mềm hoặc thay cụm màn hình Dynamic AMOLED 2X mới."},
            {"id": "spen-scratches", "title": "Bút S-Pen bị kẹt khe cắm hoặc mất nhận cảm ứng không chạm Air Actions", "severity": "low", "confidence": "high", "symptoms": "Bút S-Pen không kết nối bluetooth hoặc kẹt ngàm lò xo trong thân máy.", "solution": "Vệ sinh khe bút, thay ngòi bút hoặc thay tụ điện kết nối trên thân bút S-Pen."}
        ]
    },
    {
        "id": "samsung-galaxy-s24-plus",
        "name": "Galaxy S24+",
        "brand": "samsung",
        "deviceType": "phone",
        "tier": "P4",
        "releaseYear": 2024,
        "specs": {"screen": "6.7 inch Dynamic AMOLED 2X QHD+ 120Hz", "chip": "Exynos 2400 / Snapdragon 8 Gen 3", "battery": "4900 mAh", "charging": "45W"},
        "supportedFaults": DEFAULT_FAULTS,
        "knownIssues": [
            {"id": "charging-thermal-drop", "title": "Sạc nhanh 45W tự giảm tốc độ khi thân máy ấm", "severity": "low", "confidence": "medium", "symptoms": "Công suất sạc sụt từ 45W xuống 15W sau 10 phút sạc đầu.", "solution": "Vệ sinh chân tiếp xúc cổng Type-C và bôi keo tản nhiệt hỗ trợ vi mạch nguồn."}
        ]
    },
    {
        "id": "samsung-galaxy-s24",
        "name": "Galaxy S24",
        "brand": "samsung",
        "deviceType": "phone",
        "tier": "P3",
        "releaseYear": 2024,
        "specs": {"screen": "6.2 inch Dynamic AMOLED 2X FHD+ 120Hz", "chip": "Exynos 2400 / Snapdragon 8 Gen 3", "battery": "4000 mAh", "charging": "25W"},
        "supportedFaults": DEFAULT_FAULTS,
        "knownIssues": [
            {"id": "battery-small-s24", "title": "Dung lượng pin 4000mAh tiêu hao nhanh khi dùng mạng 5G", "severity": "medium", "confidence": "high", "symptoms": "Máy ấm nhẹ vùng cạnh viền kim loại và pin tụt khá nhanh khi dùng ngoài trời nắng.", "solution": "Kiểm tra chu kỳ sạc và tối ưu hóa ứng dụng nền One UI."}
        ]
    },
    {
        "id": "samsung-galaxy-s23-ultra",
        "name": "Galaxy S23 Ultra",
        "brand": "samsung",
        "deviceType": "phone",
        "tier": "P4",
        "releaseYear": 2023,
        "specs": {"screen": "6.8 inch Dynamic AMOLED 2X cong nhẹ 120Hz", "chip": "Snapdragon 8 Gen 2 for Galaxy", "battery": "5000 mAh", "charging": "45W"},
        "supportedFaults": DEFAULT_FAULTS,
        "knownIssues": [
            {"id": "corner-glass-bubble", "title": "Bọt khí hoặc nhăn góc dưới màn hình cong", "severity": "low", "confidence": "high", "symptoms": "Góc dưới cùng bên phải màn hình xuất hiện nếp nhăn nhỏ cấu trúc kính ép.", "solution": "Kiểm tra cấu trúc quang học, ép lại kính hoặc dán film UV gia cố lực."}
        ]
    },
    {
        "id": "samsung-galaxy-s23-plus",
        "name": "Galaxy S23+",
        "brand": "samsung",
        "deviceType": "phone",
        "tier": "P4",
        "releaseYear": 2023,
        "specs": {"screen": "6.6 inch Dynamic AMOLED 2X 120Hz", "chip": "Snapdragon 8 Gen 2", "battery": "4700 mAh", "charging": "45W"},
        "supportedFaults": DEFAULT_FAULTS,
        "knownIssues": [
            {"id": "banana-blur-camera", "title": "Hiện tượng mờ viền hình chuối (Banana Blur) khi chụp văn bản gần", "severity": "medium", "confidence": "high", "symptoms": "Chụp tài liệu cự ly gần bị mất nét hình vòng cung ở các góc trang sách.", "solution": "Căn chỉnh cụm thấu kính khẩu độ lớn hoặc lùi xa dùng zoom 2x."}
        ]
    },
    {
        "id": "samsung-galaxy-s23",
        "name": "Galaxy S23",
        "brand": "samsung",
        "deviceType": "phone",
        "tier": "P3",
        "releaseYear": 2023,
        "specs": {"screen": "6.1 inch Dynamic AMOLED 2X 120Hz", "chip": "Snapdragon 8 Gen 2", "battery": "3900 mAh", "charging": "25W"},
        "supportedFaults": DEFAULT_FAULTS,
        "knownIssues": [
            {"id": "type-c-moisture", "title": "Báo phát hiện độ ẩm cổng sạc Moisture Detected liên tục", "severity": "medium", "confidence": "high", "symptoms": "Máy không bị rơi nước nhưng liên tục kêu bíp báo ẩm và khóa không cho sạc có dây.", "solution": "Vệ sinh sấy khô cảm biến độ ẩm chân sạc Type-C hoặc thay cụm bo sạc dưới."}
        ]
    },
    {
        "id": "samsung-galaxy-s22-ultra",
        "name": "Galaxy S22 Ultra",
        "brand": "samsung",
        "deviceType": "phone",
        "tier": "P4",
        "releaseYear": 2022,
        "specs": {"screen": "6.8 inch Edge QHD+ AMOLED 120Hz", "chip": "Snapdragon 8 Gen 1", "battery": "5000 mAh", "charging": "45W"},
        "supportedFaults": DEFAULT_FAULTS,
        "knownIssues": [
            {"id": "green-line-update", "title": "Xuất hiện sọc xanh lá / hồng dọc màn hình sau khi cập nhật phần mềm", "severity": "critical", "confidence": "high", "symptoms": "Màn hình tự phát sinh một hoặc nhiều đường chỉ sọc dọc màu xanh lá hoặc tím chói mắt.", "solution": "Hàn laser vi mạch cổ cáp màn hình chuyên dụng (Laser Bonding) hoặc thay màn hình zin."},
            {"id": "hot-battery-drain", "title": "Nhiệt độ nóng và tụt pin do chip Snapdragon 8 Gen 1", "severity": "high", "confidence": "high", "symptoms": "Lưng máy nóng nhanh trên 42 độ C dù chỉ lướt Facebook hoặc xem Youtube.", "solution": "Thay pin mới dung lượng cao và bôi lại keo tản nhiệt graphene cho vi mạch."}
        ]
    },
    {
        "id": "samsung-galaxy-s22-plus",
        "name": "Galaxy S22+",
        "brand": "samsung",
        "deviceType": "phone",
        "tier": "P3",
        "releaseYear": 2022,
        "specs": {"screen": "6.6 inch Dynamic AMOLED 120Hz", "chip": "Snapdragon 8 Gen 1", "battery": "4500 mAh", "charging": "45W"},
        "supportedFaults": DEFAULT_FAULTS,
        "knownIssues": [
            {"id": "green-line-s22p", "title": "Sọc màn hình AMOLED dọc mép camera", "severity": "critical", "confidence": "high", "symptoms": "Sọc xanh lá hoặc sọc trắng chạy dọc từ camera trước xuống.", "solution": "Bắn laser phục hồi đường mạch AMOLED hoặc thay màn hình nguyên bộ."}
        ]
    },
    {
        "id": "samsung-galaxy-s22",
        "name": "Galaxy S22",
        "brand": "samsung",
        "deviceType": "phone",
        "tier": "P3",
        "releaseYear": 2022,
        "specs": {"screen": "6.1 inch Dynamic AMOLED 120Hz", "chip": "Snapdragon 8 Gen 1", "battery": "3700 mAh", "charging": "25W"},
        "supportedFaults": DEFAULT_FAULTS,
        "knownIssues": [
            {"id": "battery-short-s22", "title": "Pin chai nhanh sau 2 năm onscreen chỉ còn 3 tiếng", "severity": "high", "confidence": "high", "symptoms": "Pin yếu nhanh, sập nguồn bất ngờ khi pin báo dưới 15%.", "solution": "Thay pin zin chính hãng Samsung bảo hành 6-12 tháng."}
        ]
    },
    {
        "id": "samsung-galaxy-s21-ultra",
        "name": "Galaxy S21 Ultra",
        "brand": "samsung",
        "deviceType": "phone",
        "tier": "P3",
        "releaseYear": 2021,
        "specs": {"screen": "6.8 inch Dynamic AMOLED 2X WQHD+ 120Hz", "chip": "Exynos 2100 / Snapdragon 888", "battery": "5000 mAh", "charging": "25W"},
        "supportedFaults": DEFAULT_FAULTS,
        "knownIssues": [
            {"id": "black-screen-death-s21u", "title": "Màn hình đen thui (Black Screen of Death) nhưng máy vẫn rung chuông", "severity": "critical", "confidence": "high", "symptoms": "Màn hình tối hoàn toàn không hiển thị, gọi đến vẫn đổ chuông và nhận vân tay.", "solution": "Kiểm tra áp cấp màn hình hiển thị, hàn câu vi cáp hoặc thay màn hình nguyên bộ."}
        ]
    },
    {
        "id": "samsung-galaxy-s21-plus",
        "name": "Galaxy S21+",
        "brand": "samsung",
        "deviceType": "phone",
        "tier": "P3",
        "releaseYear": 2021,
        "specs": {"screen": "6.7 inch AMOLED 120Hz phẳng", "chip": "Exynos 2100", "battery": "4800 mAh", "charging": "25W"},
        "supportedFaults": DEFAULT_FAULTS,
        "knownIssues": [
            {"id": "green-line-s21p", "title": "Sọc màn hình xanh lá cây sau khi máy quá nóng", "severity": "critical", "confidence": "high", "symptoms": "Màn hình xuất hiện đường kẻ thẳng đứng màu xanh.", "solution": "Bắn laser nối mạch màn hình hoặc thay màn hình mới."}
        ]
    },
    {
        "id": "samsung-galaxy-s21",
        "name": "Galaxy S21",
        "brand": "samsung",
        "deviceType": "phone",
        "tier": "P2",
        "releaseYear": 2021,
        "specs": {"screen": "6.2 inch AMOLED 120Hz, lưng nhựa Glasstic", "chip": "Exynos 2100", "battery": "4000 mAh", "charging": "25W"},
        "supportedFaults": DEFAULT_FAULTS,
        "knownIssues": [
            {"id": "plastic-back-peel", "title": "Bong tróc keo dán nắp lưng nhựa Glasstic", "severity": "low", "confidence": "high", "symptoms": "Nắp lưng sau bị hở mép viền do keo lão hóa vì nhiệt độ nóng.", "solution": "Vệ sinh keo cũ và dán lại keo chống nước chuyên dụng chuẩn nhà máy."}
        ]
    },
    {
        "id": "samsung-galaxy-s20-fe",
        "name": "Galaxy S20 FE",
        "brand": "samsung",
        "deviceType": "phone",
        "tier": "P2",
        "releaseYear": 2020,
        "specs": {"screen": "6.5 inch Super AMOLED 120Hz phẳng", "chip": "Snapdragon 865", "battery": "4500 mAh", "charging": "25W"},
        "supportedFaults": DEFAULT_FAULTS,
        "knownIssues": [
            {"id": "touch-jitter-s20fe", "title": "Cảm ứng bị loạn nhấp nháy khi zoom 2 ngón tay", "severity": "medium", "confidence": "high", "symptoms": "Cảm ứng đa điểm chập chờn khi chơi game PUBG hoặc zoom ảnh.", "solution": "Cập nhật firmware cảm ứng hoặc thay màn hình Super AMOLED nguyên bộ."}
        ]
    },
    # Galaxy Z Series (Màn hình gập - Tier P5)
    {
        "id": "samsung-galaxy-z-fold-6",
        "name": "Galaxy Z Fold 6",
        "brand": "samsung",
        "deviceType": "phone",
        "tier": "P5",
        "releaseYear": 2024,
        "specs": {"screen": "Màn trong 7.6 inch gập LTPO 120Hz, màn ngoài 6.3 inch 120Hz", "chip": "Snapdragon 8 Gen 3 for Galaxy", "battery": "4400 mAh", "charging": "25W"},
        "supportedFaults": FOLD_FAULTS,
        "knownIssues": [
            {"id": "inner-screen-protector-bubble", "title": "Bong tróc miếng dán màn hình trong tại vị trí nếp gấp bản lề", "severity": "medium", "confidence": "high", "symptoms": "Miếng dán bảo vệ gốc bắt đầu xuất hiện vệt bọt khí chạy dọc theo nếp gấp giữa.", "solution": "Bóc dán lại film bảo vệ chuyên dụng cho màn gập bằng máy hút chân không chuyên nghiệp."}
        ]
    },
    {
        "id": "samsung-galaxy-z-fold-5",
        "name": "Galaxy Z Fold 5",
        "brand": "samsung",
        "deviceType": "phone",
        "tier": "P5",
        "releaseYear": 2023,
        "specs": {"screen": "Màn trong 7.6 inch gập giọt nước không khe hở, màn ngoài 6.2 inch", "chip": "Snapdragon 8 Gen 2", "battery": "4400 mAh", "charging": "25W"},
        "supportedFaults": FOLD_FAULTS,
        "knownIssues": [
            {"id": "hinge-cable-wifi", "title": "Đứt cáp bản lề gập gây mất âm thanh loa và chập chờn Wi-Fi", "severity": "critical", "confidence": "high", "symptoms": "Mở thẳng màn hình lớn ra thì loa ngoài mất tiếng hoặc Wi-Fi tự tắt, gập lại nửa chừng lại nghe được.", "solution": "Hàn nối câu cáp bản lề đôi Z Fold hoặc thay cụm cáp bản lề chính hãng."}
        ]
    },
    {
        "id": "samsung-galaxy-z-fold-4",
        "name": "Galaxy Z Fold 4",
        "brand": "samsung",
        "deviceType": "phone",
        "tier": "P5",
        "releaseYear": 2022,
        "specs": {"screen": "Màn trong 7.6 inch gập 120Hz, màn ngoài 6.2 inch", "chip": "Snapdragon 8+ Gen 1", "battery": "4400 mAh", "charging": "25W"},
        "supportedFaults": FOLD_FAULTS,
        "knownIssues": [
            {"id": "hinge-not-flat", "title": "Bản lề không mở thẳng được 180 độ (kẹt góc 170 độ)", "severity": "high", "confidence": "high", "symptoms": "Mở máy ra bị cứng, màn hình gập không thể mở phẳng hoàn toàn như ban đầu do bụi kẹt chổi quét bản lề.", "solution": "Tháo vệ sinh cấu trúc bánh răng bản lề, tẩy bụi xơ vải hoặc thay cụm xương bản lề."}
        ]
    },
    {
        "id": "samsung-galaxy-z-flip-6",
        "name": "Galaxy Z Flip 6",
        "brand": "samsung",
        "deviceType": "phone",
        "tier": "P5",
        "releaseYear": 2024,
        "specs": {"screen": "Màn trong 6.7 inch gập 120Hz, màn phụ ngoài 3.4 inch", "chip": "Snapdragon 8 Gen 3", "battery": "4000 mAh, tản nhiệt buồng hơi", "charging": "25W"},
        "supportedFaults": FOLD_FAULTS,
        "knownIssues": [
            {"id": "flip-hinge-click", "title": "Tiếng kêu lách cách nhẹ khi gập mở màn hình", "severity": "low", "confidence": "medium", "symptoms": "Khi gập mở bản lề phát ra âm thanh cơ học nhỏ.", "solution": "Kiểm tra lò xo trợ lực và bơm dung dịch bôi trơn bản lề chuyên dụng."}
        ]
    },
    {
        "id": "samsung-galaxy-z-flip-5",
        "name": "Galaxy Z Flip 5",
        "brand": "samsung",
        "deviceType": "phone",
        "tier": "P5",
        "releaseYear": 2023,
        "specs": {"screen": "Màn trong 6.7 inch gập 120Hz, màn ngoài Flex Window 3.4 inch", "chip": "Snapdragon 8 Gen 2", "battery": "3700 mAh", "charging": "25W"},
        "supportedFaults": FOLD_FAULTS,
        "knownIssues": [
            {"id": "screen-crease-blackline", "title": "Nứt đen chảy mực ngay nếp gấp giữa sau va đập", "severity": "critical", "confidence": "high", "symptoms": "Màn hình trong xuất hiện đốm đen hoặc đường sọc ngang ngay nếp gập đôi.", "solution": "Thay cụm màn hình trong dẻo chính hãng Samsung kèm khung sườn."}
        ]
    },
    {
        "id": "samsung-galaxy-z-flip-4",
        "name": "Galaxy Z Flip 4",
        "brand": "samsung",
        "deviceType": "phone",
        "tier": "P5",
        "releaseYear": 2022,
        "specs": {"screen": "Màn trong 6.7 inch gập 120Hz", "chip": "Snapdragon 8+ Gen 1", "battery": "3700 mAh", "charging": "25W"},
        "supportedFaults": FOLD_FAULTS,
        "knownIssues": [
            {"id": "hinge-dislocated", "title": "Bản lề bị lệch gập kêu cọt kẹt sau rơi rớt", "severity": "high", "confidence": "high", "symptoms": "Hai nửa thân máy bị xô lệch, gập lại không khít mép.", "solution": "Nắn chỉnh cơ cấu bản lề trục đôi hoặc thay khung sườn mới."}
        ]
    },
    # Note Series
    {
        "id": "samsung-galaxy-note-20-ultra",
        "name": "Galaxy Note 20 Ultra",
        "brand": "samsung",
        "deviceType": "phone",
        "tier": "P3",
        "releaseYear": 2020,
        "specs": {"screen": "6.9 inch Dynamic AMOLED 2X WQHD+ 120Hz cong viền", "chip": "Exynos 990 / Snapdragon 865+", "battery": "4500 mAh", "charging": "25W"},
        "supportedFaults": DEFAULT_FAULTS,
        "knownIssues": [
            {"id": "green-white-screen-note20u", "title": "Màn hình trắng xóa hoặc sọc xanh lá (Lỗi kinh điển Note 20 Ultra)", "severity": "critical", "confidence": "high", "symptoms": "Đang dùng bình thường màn hình chuyển sang màu xanh lá cây hoặc trắng đục chớp giật.", "solution": "Ép cổ cáp vi mạch màn hình công nghệ laser không cần thay màn zin đắt tiền."}
        ]
    },
    {
        "id": "samsung-galaxy-note-20",
        "name": "Galaxy Note 20",
        "brand": "samsung",
        "deviceType": "phone",
        "tier": "P2",
        "releaseYear": 2020,
        "specs": {"screen": "6.7 inch Super AMOLED Plus 60Hz phẳng, lưng nhựa", "chip": "Exynos 990", "battery": "4300 mAh", "charging": "25W"},
        "supportedFaults": DEFAULT_FAULTS,
        "knownIssues": [
            {"id": "battery-aged-note20", "title": "Pin chai phồng hở mép lưng nhựa", "severity": "medium", "confidence": "high", "symptoms": "Pin nhanh hết, lưng nhựa phồng kênh.", "solution": "Thay pin mới bảo hành 6 tháng."}
        ]
    },
    {
        "id": "samsung-galaxy-note-10-plus",
        "name": "Galaxy Note 10+",
        "brand": "samsung",
        "deviceType": "phone",
        "tier": "P2",
        "releaseYear": 2019,
        "specs": {"screen": "6.8 inch Dynamic AMOLED cong", "chip": "Exynos 9825", "battery": "4300 mAh", "charging": "45W"},
        "supportedFaults": DEFAULT_FAULTS,
        "knownIssues": [
            {"id": "curved-glass-shatter", "title": "Nứt vỡ mặt kính mép cong viền", "severity": "medium", "confidence": "high", "symptoms": "Kính vỡ góc cong nhưng hiển thị và cảm ứng Spen vẫn tốt.", "solution": "Ép kính cong màn hình nguyên bản bằng khuôn ép nhiệt chuẩn."}
        ]
    },
    # A Series Quốc Dân
    {
        "id": "samsung-galaxy-a55-5g",
        "name": "Galaxy A55 5G",
        "brand": "samsung",
        "deviceType": "phone",
        "tier": "P2",
        "releaseYear": 2024,
        "specs": {"screen": "6.6 inch Super AMOLED 120Hz viền kim loại", "chip": "Exynos 1480 (4nm)", "battery": "5000 mAh", "charging": "25W"},
        "supportedFaults": DEFAULT_FAULTS,
        "knownIssues": [
            {"id": "fingerprint-optical-slow", "title": "Cảm biến vân tay quang học dưới màn hình nhận diện chậm", "severity": "low", "confidence": "high", "symptoms": "Đặt ngón tay quét mở khóa mất hơn 1 giây hoặc thất bại khi dán kính cường lực dày.", "solution": "Cân chỉnh lại cảm biến vân tay quang học và sử dụng kính cường lực chuẩn tương thích."}
        ]
    },
    {
        "id": "samsung-galaxy-a54-5g",
        "name": "Galaxy A54 5G",
        "brand": "samsung",
        "deviceType": "phone",
        "tier": "P2",
        "releaseYear": 2023,
        "specs": {"screen": "6.4 inch Super AMOLED 120Hz lưng kính", "chip": "Exynos 1380", "battery": "5000 mAh", "charging": "25W"},
        "supportedFaults": DEFAULT_FAULTS,
        "knownIssues": [
            {"id": "warm-frame-camera", "title": "Khung máy ấm khi quay video ngoài trời", "severity": "low", "confidence": "high", "symptoms": "Quay video nắng lâu làm máy nóng và hạ độ sáng màn hình.", "solution": "Kiểm tra tản nhiệt và vệ sinh chân tiếp xúc bo mạch."}
        ]
    },
    {
        "id": "samsung-galaxy-a35-5g",
        "name": "Galaxy A35 5G",
        "brand": "samsung",
        "deviceType": "phone",
        "tier": "P2",
        "releaseYear": 2024,
        "specs": {"screen": "6.6 inch Super AMOLED 120Hz", "chip": "Exynos 1380", "battery": "5000 mAh", "charging": "25W"},
        "supportedFaults": DEFAULT_FAULTS,
        "knownIssues": [
            {"id": "glass-corner-crack-a35", "title": "Nứt kính bảo vệ góc sau rơi", "severity": "medium", "confidence": "high", "symptoms": "Mặt kính vỡ ngoài, cảm ứng và màn hình trong còn nguyên.", "solution": "Ép mặt kính màn hình mới bằng keo OCA."}
        ]
    },
    {
        "id": "samsung-galaxy-a15",
        "name": "Galaxy A15 4G/5G",
        "brand": "samsung",
        "deviceType": "phone",
        "tier": "P1",
        "releaseYear": 2023,
        "specs": {"screen": "6.5 inch Super AMOLED 90Hz", "chip": "Helio G99 / Dimensity 6100+", "battery": "5000 mAh", "charging": "25W"},
        "supportedFaults": DEFAULT_FAULTS,
        "knownIssues": [
            {"id": "speaker-lower-dirt", "title": "Loa ngoài rè do bụi mạt sắt bám màng loa", "severity": "low", "confidence": "high", "symptoms": "Nghe nhạc hoặc chuông báo thức bị rè chói tai.", "solution": "Vệ sinh màng loa hoặc thay module cụm loa ngoài zin."}
        ]
    },
    {
        "id": "samsung-galaxy-a05s",
        "name": "Galaxy A05s",
        "brand": "samsung",
        "deviceType": "phone",
        "tier": "P1",
        "releaseYear": 2023,
        "specs": {"screen": "6.7 inch PLS LCD 90Hz", "chip": "Snapdragon 680", "battery": "5000 mAh", "charging": "25W"},
        "supportedFaults": DEFAULT_FAULTS,
        "knownIssues": [
            {"id": "charging-board-loose", "title": "Cổng sạc Type-C chập chờn sau thời gian cắm sạc", "severity": "medium", "confidence": "high", "symptoms": "Cắm dây sạc nhận rồi ngắt liên tục.", "solution": "Thay bo mạch phụ chân sạc (Sub-board) nguyên cụm."}
        ]
    }
]

save("samsung", samsung_models)

# --- 3. XIAOMI (19 models) ---
xiaomi_models = [
    # Flagship Series (P4/P3)
    {
        "id": "xiaomi-14-ultra",
        "name": "Xiaomi 14 Ultra",
        "brand": "xiaomi",
        "deviceType": "phone",
        "tier": "P4",
        "releaseYear": 2024,
        "specs": {"screen": "6.73 inch LTPO AMOLED 120Hz 3000 nits", "chip": "Snapdragon 8 Gen 3", "battery": "5000 mAh", "charging": "90W HyperCharge, 80W Wireless"},
        "supportedFaults": DEFAULT_FAULTS,
        "knownIssues": [
            {"id": "camera-fogging-lens", "title": "Đọng hơi nước sương mù bên trong thấu kính camera Leica", "severity": "medium", "confidence": "high", "symptoms": "Khi chụp ảnh ngoài trời lạnh hoặc phòng điều hòa, ống kính chính 1 inch bị mờ sương bên trong.", "solution": "Hút ẩm chân không, sấy khô buồng quang học và thay ron cao su làm kín cụm camera Leica."},
            {"id": "heavy-module-crack", "title": "Nứt kính bảo vệ cụm camera tròn khổng lồ sau va đập", "severity": "medium", "confidence": "high", "symptoms": "Kính tròn bảo vệ ống kính bị nứt vỡ đường chéo gây lóa đèn ban đêm.", "solution": "Thay mặt kính bảo vệ camera sapphire chính hãng."}
        ]
    },
    {
        "id": "xiaomi-14",
        "name": "Xiaomi 14",
        "brand": "xiaomi",
        "deviceType": "phone",
        "tier": "P4",
        "releaseYear": 2023,
        "specs": {"screen": "6.36 inch LTPO OLED 120Hz phẳng viền siêu mỏng", "chip": "Snapdragon 8 Gen 3", "battery": "4610 mAh", "charging": "90W"},
        "supportedFaults": DEFAULT_FAULTS,
        "knownIssues": [
            {"id": "foggy-lens-x14", "title": "Hơi nước đọng nhẹ thấu kính camera khi quay video 8K", "severity": "low", "confidence": "high", "symptoms": "Mặt kính camera chính bị sương mờ trong vài phút đầu quay phim nặng.", "solution": "Tháo nắp lưng sấy buồng máy và phủ keo chống ẩm chuyên dụng."}
        ]
    },
    {
        "id": "xiaomi-13-pro",
        "name": "Xiaomi 13 Pro",
        "brand": "xiaomi",
        "deviceType": "phone",
        "tier": "P4",
        "releaseYear": 2022,
        "specs": {"screen": "6.73 inch LTPO AMOLED 120Hz cong viền, lưng gốm Ceramic", "chip": "Snapdragon 8 Gen 2", "battery": "4820 mAh", "charging": "120W"},
        "supportedFaults": DEFAULT_FAULTS,
        "knownIssues": [
            {"id": "ceramic-weight-crack", "title": "Lưng gốm nặng dễ nứt mẻ góc khi rơi không có ốp", "severity": "medium", "confidence": "high", "symptoms": "Mặt lưng nứt chân chim cạnh góc máy.", "solution": "Thay nắp lưng gốm chính hãng hoặc nắp lưng kính tương thích."}
        ]
    },
    {
        "id": "xiaomi-13",
        "name": "Xiaomi 13",
        "brand": "xiaomi",
        "deviceType": "phone",
        "tier": "P3",
        "releaseYear": 2022,
        "specs": {"screen": "6.36 inch AMOLED 120Hz phẳng", "chip": "Snapdragon 8 Gen 2", "battery": "4500 mAh", "charging": "67W"},
        "supportedFaults": DEFAULT_FAULTS,
        "knownIssues": [
            {"id": "battery-aged-x13", "title": "Pin chai sau thời gian dài sử dụng sạc nhanh 67W", "severity": "medium", "confidence": "high", "symptoms": "Thời lượng pin giảm, sạc nhanh nóng máy.", "solution": "Thay pin cell niken chất lượng cao bảo hành 6 tháng."}
        ]
    },
    {
        "id": "xiaomi-13t-pro",
        "name": "Xiaomi 13T Pro",
        "brand": "xiaomi",
        "deviceType": "phone",
        "tier": "P3",
        "releaseYear": 2023,
        "specs": {"screen": "6.67 inch CrystalRes AMOLED 144Hz", "chip": "MediaTek Dimensity 9200+", "battery": "5000 mAh", "charging": "120W HyperCharge"},
        "supportedFaults": DEFAULT_FAULTS,
        "knownIssues": [
            {"id": "120w-charging-port-wear", "title": "Chân sạc Type-C quá nhiệt lỏng ngàm do dòng sạc 120W cực lớn", "severity": "high", "confidence": "high", "symptoms": "Cắm củ sạc 120W không kích hoạt được chế độ HyperCharge mà chỉ nhận sạc thường.", "solution": "Hàn thay cụm chân sạc Type-C tải cao chuyên dụng chịu dòng 6A."}
        ]
    },
    {
        "id": "xiaomi-13t",
        "name": "Xiaomi 13T",
        "brand": "xiaomi",
        "deviceType": "phone",
        "tier": "P3",
        "releaseYear": 2023,
        "specs": {"screen": "6.67 inch AMOLED 144Hz", "chip": "Dimensity 8200-Ultra", "battery": "5000 mAh", "charging": "67W"},
        "supportedFaults": DEFAULT_FAULTS,
        "knownIssues": [
            {"id": "lens-fog-13t", "title": "Đọng sương thấu kính camera Leica khi trời mưa lạnh", "severity": "medium", "confidence": "high", "symptoms": "Ảnh chụp bị mờ đục như có lớp khói.", "solution": "Hút ẩm buồng camera và thay ron chống ẩm mới."}
        ]
    },
    {
        "id": "xiaomi-12-pro",
        "name": "Xiaomi 12 Pro",
        "brand": "xiaomi",
        "deviceType": "phone",
        "tier": "P3",
        "releaseYear": 2021,
        "specs": {"screen": "6.73 inch LTPO AMOLED 120Hz", "chip": "Snapdragon 8 Gen 1", "battery": "4600 mAh", "charging": "120W"},
        "supportedFaults": DEFAULT_FAULTS,
        "knownIssues": [
            {"id": "snap-8gen1-hot", "title": "Nhiệt độ nóng và tụt pin nhanh do chip Snapdragon 8 Gen 1", "severity": "high", "confidence": "high", "symptoms": "Thân máy nóng ran khi chơi game hoặc xem video liên tục.", "solution": "Vệ sinh tản nhiệt, bôi lại gel tản nhiệt và thay pin mới."}
        ]
    },
    {
        "id": "xiaomi-12",
        "name": "Xiaomi 12",
        "brand": "xiaomi",
        "deviceType": "phone",
        "tier": "P2",
        "releaseYear": 2021,
        "specs": {"screen": "6.28 inch AMOLED 120Hz nhỏ gọn", "chip": "Snapdragon 8 Gen 1", "battery": "4500 mAh", "charging": "67W"},
        "supportedFaults": DEFAULT_FAULTS,
        "knownIssues": [
            {"id": "battery-swelling-x12", "title": "Pin phồng đội nắp lưng", "severity": "high", "confidence": "high", "symptoms": "Hở mép viền lưng máy.", "solution": "Thay pin zin dung lượng cao."}
        ]
    },
    # Redmi Note Series Quốc Dân
    {
        "id": "xiaomi-redmi-note-13-pro-plus",
        "name": "Redmi Note 13 Pro+",
        "brand": "xiaomi",
        "deviceType": "phone",
        "tier": "P3",
        "releaseYear": 2023,
        "specs": {"screen": "6.67 inch 1.5K AMOLED 120Hz cong viền, kháng nước IP68", "chip": "Dimensity 7200-Ultra", "battery": "5000 mAh", "charging": "120W"},
        "supportedFaults": DEFAULT_FAULTS,
        "knownIssues": [
            {"id": "curved-screen-crack", "title": "Mặt kính màn hình cong dễ nứt mép viền", "severity": "medium", "confidence": "high", "symptoms": "Kính vỡ mép nhưng cảm ứng và màn hình 1.5K bên trong hiển thị bình thường.", "solution": "Ép kính cong bằng máy ép nhiệt chuyên dụng."}
        ]
    },
    {
        "id": "xiaomi-redmi-note-13-pro",
        "name": "Redmi Note 13 Pro 5G / 4G",
        "brand": "xiaomi",
        "deviceType": "phone",
        "tier": "P2",
        "releaseYear": 2023,
        "specs": {"screen": "6.67 inch 1.5K AMOLED 120Hz phẳng", "chip": "Snapdragon 7s Gen 2 / Helio G99-Ultra", "battery": "5100 mAh", "charging": "67W"},
        "supportedFaults": DEFAULT_FAULTS,
        "knownIssues": [
            {"id": "camera-200mp-focus", "title": "Lấy nét camera 200MP chậm trong điều kiện thiếu sáng", "severity": "low", "confidence": "high", "symptoms": "Chụp đêm bị out nét nhẹ.", "solution": "Cập nhật ứng dụng máy ảnh hoặc cân chỉnh thấu kính OIS."}
        ]
    },
    {
        "id": "xiaomi-redmi-note-13",
        "name": "Redmi Note 13",
        "brand": "xiaomi",
        "deviceType": "phone",
        "tier": "P2",
        "releaseYear": 2023,
        "specs": {"screen": "6.67 inch AMOLED 120Hz phẳng viền mỏng", "chip": "Snapdragon 685 / Dimensity 6080", "battery": "5000 mAh", "charging": "33W"},
        "supportedFaults": DEFAULT_FAULTS,
        "knownIssues": [
            {"id": "screen-touch-ghost", "title": "Loạn cảm ứng khi cắm sạc bằng củ sạc kém chất lượng", "severity": "medium", "confidence": "high", "symptoms": "Cắm sạc bàn phím tự nhảy chữ loạn xạ.", "solution": "Thay đổi củ cáp sạc chuẩn dòng và thay màng cảm ứng nếu bị rò điện."}
        ]
    },
    {
        "id": "xiaomi-redmi-note-12-pro",
        "name": "Redmi Note 12 Pro 5G",
        "brand": "xiaomi",
        "deviceType": "phone",
        "tier": "P2",
        "releaseYear": 2022,
        "specs": {"screen": "6.67 inch OLED 120Hz", "chip": "Dimensity 1080", "battery": "5000 mAh", "charging": "67W"},
        "supportedFaults": DEFAULT_FAULTS,
        "knownIssues": [
            {"id": "battery-aged-rn12p", "title": "Pin sụt nhanh sau 2 năm onscreen 4 tiếng", "severity": "medium", "confidence": "high", "symptoms": "Máy mau hết pin, sạc chậm hơn trước.", "solution": "Thay pin mới bảo hành 6 tháng."}
        ]
    },
    {
        "id": "xiaomi-redmi-note-12",
        "name": "Redmi Note 12",
        "brand": "xiaomi",
        "deviceType": "phone",
        "tier": "P2",
        "releaseYear": 2022,
        "specs": {"screen": "6.67 inch AMOLED 120Hz", "chip": "Snapdragon 685", "battery": "5000 mAh", "charging": "33W"},
        "supportedFaults": DEFAULT_FAULTS,
        "knownIssues": [
            {"id": "earpiece-volume-low", "title": "Loa trong nghe nhỏ tiếng", "severity": "low", "confidence": "high", "symptoms": "Đàm thoại nơi ồn ào rất khó nghe.", "solution": "Vệ sinh màng loa thoại hoặc thay loa trong."}
        ]
    },
    {
        "id": "xiaomi-redmi-13c",
        "name": "Redmi 13C",
        "brand": "xiaomi",
        "deviceType": "phone",
        "tier": "P1",
        "releaseYear": 2023,
        "specs": {"screen": "6.74 inch IPS LCD 90Hz giọt nước", "chip": "Helio G85", "battery": "5000 mAh", "charging": "18W"},
        "supportedFaults": DEFAULT_FAULTS,
        "knownIssues": [
            {"id": "lcd-shatter-r13c", "title": "Nứt vỡ màn hình hiển thị sau khi rơi", "severity": "high", "confidence": "high", "symptoms": "Màn hình nứt vỡ sọc kẻ hoặc chảy mực đen góc.", "solution": "Thay cụm màn hình IPS LCD mới chi phí rẻ lấy ngay."}
        ]
    },
    {
        "id": "xiaomi-redmi-12",
        "name": "Redmi 12",
        "brand": "xiaomi",
        "deviceType": "phone",
        "tier": "P1",
        "releaseYear": 2023,
        "specs": {"screen": "6.79 inch IPS LCD 90Hz, lưng kính", "chip": "Helio G88", "battery": "5000 mAh", "charging": "18W"},
        "supportedFaults": DEFAULT_FAULTS,
        "knownIssues": [
            {"id": "back-glass-crack-r12", "title": "Nứt vỡ mặt lưng kính sau khi cấn chìa khóa", "severity": "low", "confidence": "high", "symptoms": "Mặt lưng rạn nứt.", "solution": "Thay nắp lưng kính mới."}
        ]
    },
    # POCO Gaming Series
    {
        "id": "xiaomi-poco-f6-pro",
        "name": "POCO F6 Pro",
        "brand": "xiaomi",
        "deviceType": "phone",
        "tier": "P3",
        "releaseYear": 2024,
        "specs": {"screen": "6.67 inch WQHD+ AMOLED 120Hz 4000 nits", "chip": "Snapdragon 8 Gen 2", "battery": "5000 mAh", "charging": "120W"},
        "supportedFaults": DEFAULT_FAULTS,
        "knownIssues": [
            {"id": "gaming-high-temp", "title": "Nhiệt độ nóng vùng cụm camera khi cày game đồ họa nặng", "severity": "low", "confidence": "high", "symptoms": "Khung nhôm nóng trên 43 độ C khi chơi Genshin Impact 60fps.", "solution": "Vệ sinh tản nhiệt buồng hơi LiquidCool và tối ưu xung nhịp hệ điều hành."}
        ]
    },
    {
        "id": "xiaomi-poco-f6",
        "name": "POCO F6",
        "brand": "xiaomi",
        "deviceType": "phone",
        "tier": "P3",
        "releaseYear": 2024,
        "specs": {"screen": "6.67 inch 1.5K AMOLED 120Hz", "chip": "Snapdragon 8s Gen 3", "battery": "5000 mAh", "charging": "90W"},
        "supportedFaults": DEFAULT_FAULTS,
        "knownIssues": [
            {"id": "battery-fast-drain-f6", "title": "Pin tụt nhanh khi chơi game liên tục", "severity": "medium", "confidence": "high", "symptoms": "Sụt pin 25% mỗi giờ chơi game nặng.", "solution": "Kiểm tra chu kỳ sạc và tối ưu HyperOS."}
        ]
    },
    {
        "id": "xiaomi-poco-x6-pro",
        "name": "POCO X6 Pro",
        "brand": "xiaomi",
        "deviceType": "phone",
        "tier": "P2",
        "releaseYear": 2024,
        "specs": {"screen": "6.67 inch 1.5K AMOLED 120Hz", "chip": "Dimensity 8300-Ultra", "battery": "5000 mAh", "charging": "67W"},
        "supportedFaults": DEFAULT_FAULTS,
        "knownIssues": [
            {"id": "fingerprint-speed-x6p", "title": "Cảm biến vân tay dưới màn hình kém nhạy khi tay ẩm", "severity": "low", "confidence": "high", "symptoms": "Mở khóa nhiều lần không nhận vân tay.", "solution": "Cân chỉnh lại cảm biến quang học hoặc dán cường lực cao cấp."}
        ]
    },
    {
        "id": "xiaomi-poco-m6-pro",
        "name": "POCO M6 Pro",
        "brand": "xiaomi",
        "deviceType": "phone",
        "tier": "P2",
        "releaseYear": 2024,
        "specs": {"screen": "6.67 inch Flow AMOLED 120Hz", "chip": "Helio G99-Ultra", "battery": "5000 mAh", "charging": "67W"},
        "supportedFaults": DEFAULT_FAULTS,
        "knownIssues": [
            {"id": "charging-board-m6p", "title": "Chân sạc Type-C cắm lỏng lẻo sau va chạm", "severity": "medium", "confidence": "high", "symptoms": "Sạc ngắt quãng chập chờn.", "solution": "Thay cụm bo sạc phụ."}
        ]
    }
]

save("xiaomi", xiaomi_models)

# --- 4. OPPO (16 models) ---
oppo_models = [
    # Màn hình gập Find N Series (P5)
    {
        "id": "oppo-find-n3",
        "name": "Oppo Find N3",
        "brand": "oppo",
        "deviceType": "phone",
        "tier": "P5",
        "releaseYear": 2023,
        "specs": {"screen": "Màn trong 7.82 inch 120Hz nếp gấp tàng hình, màn ngoài 6.31 inch 120Hz", "chip": "Snapdragon 8 Gen 2", "battery": "4805 mAh", "charging": "67W SuperVOOC"},
        "supportedFaults": FOLD_FAULTS,
        "knownIssues": [
            {"id": "fold-cable-camera", "title": "Cáp bản lề truyền tín hiệu camera chập chờn sau thời gian gập mở nhiều", "severity": "high", "confidence": "high", "symptoms": "Mở màn hình trong chụp ảnh bị đứng app hoặc giật khung hình.", "solution": "Thay bộ cáp bản lề đôi gập chính hãng Oppo."}
        ]
    },
    {
        "id": "oppo-find-n3-flip",
        "name": "Oppo Find N3 Flip",
        "brand": "oppo",
        "deviceType": "phone",
        "tier": "P5",
        "releaseYear": 2023,
        "specs": {"screen": "Màn trong 6.8 inch gập 120Hz, màn ngoài dọc 3.26 inch, 3 camera Hasselblad", "chip": "Dimensity 9200", "battery": "4300 mAh", "charging": "44W SuperVOOC"},
        "supportedFaults": FOLD_FAULTS,
        "knownIssues": [
            {"id": "screen-bubble-crease", "title": "Bong bóng khí miếng dán nếp gấp giữa sau 6 tháng", "severity": "medium", "confidence": "high", "symptoms": "Miếng dán bảo vệ nếp gấp hở mép phát ra tiếng tách khi mở máy.", "solution": "Dán lại miếng dán UV chuyên dụng màn hình gập."}
        ]
    },
    {
        "id": "oppo-find-n2-flip",
        "name": "Oppo Find N2 Flip",
        "brand": "oppo",
        "deviceType": "phone",
        "tier": "P5",
        "releaseYear": 2022,
        "specs": {"screen": "Màn trong 6.8 inch gập, màn ngoài 3.26 inch", "chip": "Dimensity 9000+", "battery": "4300 mAh", "charging": "44W"},
        "supportedFaults": FOLD_FAULTS,
        "knownIssues": [
            {"id": "hinge-stiff", "title": "Bản lề hơi cứng khi gập lại nửa góc", "severity": "medium", "confidence": "high", "symptoms": "Gập góc FlexForm không giữ được vị trí mong muốn.", "solution": "Vệ sinh căn chỉnh cơ cấu bánh răng lò xo bản lề."}
        ]
    },
    # Find X Flagship (P4)
    {
        "id": "oppo-find-x6-pro",
        "name": "Oppo Find X6 Pro",
        "brand": "oppo",
        "deviceType": "phone",
        "tier": "P4",
        "releaseYear": 2023,
        "specs": {"screen": "6.82 inch LTPO3 AMOLED 120Hz 2500 nits cong", "chip": "Snapdragon 8 Gen 2", "battery": "5000 mAh", "charging": "100W SuperVOOC"},
        "supportedFaults": DEFAULT_FAULTS,
        "knownIssues": [
            {"id": "camera-glass-scratch", "title": "Trầy xước kính cụm camera tròn cỡ lớn", "severity": "low", "confidence": "high", "symptoms": "Ảnh chụp bị vệt sáng lóa đèn đường ban đêm.", "solution": "Thay kính bảo vệ ống kính camera chính hãng."}
        ]
    },
    {
        "id": "oppo-find-x5-pro",
        "name": "Oppo Find X5 Pro",
        "brand": "oppo",
        "deviceType": "phone",
        "tier": "P3",
        "releaseYear": 2022,
        "specs": {"screen": "6.7 inch LTPO2 AMOLED 120Hz, lưng gốm Ceramic", "chip": "Snapdragon 8 Gen 1", "battery": "5000 mAh", "charging": "80W"},
        "supportedFaults": DEFAULT_FAULTS,
        "knownIssues": [
            {"id": "snap8gen1-heat-x5p", "title": "Ấm lưng máy khi dùng sạc nhanh 80W", "severity": "medium", "confidence": "high", "symptoms": "Thân máy ấm lên rõ rệt khi sạc từ 0% lên 50%.", "solution": "Kiểm tra pin và vệ sinh keo tản nhiệt."}
        ]
    },
    # Reno Series Chụp Chân Dung (P3 / P2)
    {
        "id": "oppo-reno-12-pro",
        "name": "Oppo Reno 12 Pro",
        "brand": "oppo",
        "deviceType": "phone",
        "tier": "P3",
        "releaseYear": 2024,
        "specs": {"screen": "6.7 inch AMOLED 120Hz cong nhẹ 4 cạnh", "chip": "Dimensity 7300-Energy", "battery": "5000 mAh", "charging": "80W SuperVOOC"},
        "supportedFaults": DEFAULT_FAULTS,
        "knownIssues": [
            {"id": "screen-corner-drop-r12p", "title": "Nứt kính 4 mép cong nhẹ khi rơi", "severity": "medium", "confidence": "high", "symptoms": "Mặt kính rạn nứt góc nhưng cảm ứng vẫn mượt mà.", "solution": "Ép kính màn hình cong nhẹ công nghệ mới."}
        ]
    },
    {
        "id": "oppo-reno-12",
        "name": "Oppo Reno 12",
        "brand": "oppo",
        "deviceType": "phone",
        "tier": "P2",
        "releaseYear": 2024,
        "specs": {"screen": "6.7 inch AMOLED 120Hz cong 3D", "chip": "Dimensity 7300-Energy", "battery": "5000 mAh", "charging": "80W"},
        "supportedFaults": DEFAULT_FAULTS,
        "knownIssues": [
            {"id": "type-c-dust-r12", "title": "Bám bụi cổng sạc cắm SuperVOOC không nhận sạc nhanh", "severity": "low", "confidence": "high", "symptoms": "Chỉ nhận sạc chậm thường thay vì sạc nhanh SuperVOOC.", "solution": "Vệ sinh chân sạc Type-C hoặc thay bo sạc phụ."}
        ]
    },
    {
        "id": "oppo-reno-11-pro",
        "name": "Oppo Reno 11 Pro",
        "brand": "oppo",
        "deviceType": "phone",
        "tier": "P3",
        "releaseYear": 2024,
        "specs": {"screen": "6.7 inch OLED 120Hz cong", "chip": "Dimensity 8200", "battery": "4600 mAh", "charging": "80W"},
        "supportedFaults": DEFAULT_FAULTS,
        "knownIssues": [
            {"id": "battery-aged-r11p", "title": "Pin tụt nhanh sau thời gian dài sử dụng", "severity": "medium", "confidence": "high", "symptoms": "Dung lượng pin giảm, mau hết pin.", "solution": "Thay pin mới chất lượng cao."}
        ]
    },
    {
        "id": "oppo-reno-11",
        "name": "Oppo Reno 11",
        "brand": "oppo",
        "deviceType": "phone",
        "tier": "P2",
        "releaseYear": 2024,
        "specs": {"screen": "6.7 inch OLED 120Hz cong", "chip": "Dimensity 7050", "battery": "5000 mAh", "charging": "67W"},
        "supportedFaults": DEFAULT_FAULTS,
        "knownIssues": [
            {"id": "glass-crack-r11", "title": "Nứt mặt kính ngoài mép cong", "severity": "medium", "confidence": "high", "symptoms": "Kính nứt nhưng màn hình hiển thị không sọc.", "solution": "Ép mặt kính màn hình cong zin."}
        ]
    },
    {
        "id": "oppo-reno-10-pro-plus",
        "name": "Oppo Reno 10 Pro+",
        "brand": "oppo",
        "deviceType": "phone",
        "tier": "P3",
        "releaseYear": 2023,
        "specs": {"screen": "6.74 inch AMOLED 120Hz 1.5K", "chip": "Snapdragon 8+ Gen 1", "battery": "4700 mAh", "charging": "100W"},
        "supportedFaults": DEFAULT_FAULTS,
        "knownIssues": [
            {"id": "telephoto-lens-lag", "title": "Camera tiềm vọng 64MP rung nhẹ khi lấy nét zoom", "severity": "medium", "confidence": "high", "symptoms": "Chụp zoom 3x bị rung hình ảnh.", "solution": "Thay thế module chống rung camera tele."}
        ]
    },
    {
        "id": "oppo-reno-10",
        "name": "Oppo Reno 10",
        "brand": "oppo",
        "deviceType": "phone",
        "tier": "P2",
        "releaseYear": 2023,
        "specs": {"screen": "6.7 inch AMOLED 120Hz cong", "chip": "Dimensity 7050", "battery": "5000 mAh", "charging": "67W"},
        "supportedFaults": DEFAULT_FAULTS,
        "knownIssues": [
            {"id": "back-cover-scratch", "title": "Trầy xước nắp lưng màu gradient", "severity": "low", "confidence": "high", "symptoms": "Lưng xước dăm mất thẩm mỹ.", "solution": "Thay nắp lưng mới chính hãng."}
        ]
    },
    {
        "id": "oppo-reno-8",
        "name": "Oppo Reno 8 5G / 4G",
        "brand": "oppo",
        "deviceType": "phone",
        "tier": "P2",
        "releaseYear": 2022,
        "specs": {"screen": "6.43 inch AMOLED 90Hz", "chip": "Dimensity 1300 / Snapdragon 680", "battery": "4500 mAh", "charging": "80W / 33W"},
        "supportedFaults": DEFAULT_FAULTS,
        "knownIssues": [
            {"id": "battery-aged-r8", "title": "Pin chai cần bảo dưỡng", "severity": "medium", "confidence": "high", "symptoms": "Pin yếu nhanh sau 2 năm.", "solution": "Thay pin mới chất lượng cao."}
        ]
    },
    # A Series Phổ Thông
    {
        "id": "oppo-a79-5g",
        "name": "Oppo A79 5G",
        "brand": "oppo",
        "deviceType": "phone",
        "tier": "P2",
        "releaseYear": 2023,
        "specs": {"screen": "6.72 inch IPS LCD 90Hz", "chip": "Dimensity 6020", "battery": "5000 mAh", "charging": "33W"},
        "supportedFaults": DEFAULT_FAULTS,
        "knownIssues": [
            {"id": "lcd-shatter-a79", "title": "Vỡ màn hình LCD sau va chạm", "severity": "high", "confidence": "high", "symptoms": "Màn hình tối hoặc chảy sọc chỉ đen.", "solution": "Thay cụm màn hình LCD mới."}
        ]
    },
    {
        "id": "oppo-a78",
        "name": "Oppo A78",
        "brand": "oppo",
        "deviceType": "phone",
        "tier": "P1",
        "releaseYear": 2023,
        "specs": {"screen": "6.43 inch AMOLED 90Hz / LCD", "chip": "Snapdragon 680", "battery": "5000 mAh", "charging": "67W"},
        "supportedFaults": DEFAULT_FAULTS,
        "knownIssues": [
            {"id": "charging-slow-a78", "title": "Chân sạc Type-C oxy hóa sạc chậm", "severity": "medium", "confidence": "high", "symptoms": "Cắm sạc chập chờn.", "solution": "Thay bo chân sạc phụ."}
        ]
    },
    {
        "id": "oppo-a58",
        "name": "Oppo A58",
        "brand": "oppo",
        "deviceType": "phone",
        "tier": "P1",
        "releaseYear": 2023,
        "specs": {"screen": "6.72 inch IPS LCD FHD+", "chip": "Helio G85", "battery": "5000 mAh", "charging": "33W"},
        "supportedFaults": DEFAULT_FAULTS,
        "knownIssues": [
            {"id": "speaker-crackling-a58", "title": "Loa ngoài rè khi bật chế độ siêu âm lượng 300%", "severity": "low", "confidence": "high", "symptoms": "Bật âm lượng cực đại màng loa rè rung.", "solution": "Vệ sinh màng loa hoặc thay loa ngoài mới."}
        ]
    },
    {
        "id": "oppo-a38",
        "name": "Oppo A38",
        "brand": "oppo",
        "deviceType": "phone",
        "tier": "P1",
        "releaseYear": 2023,
        "specs": {"screen": "6.56 inch IPS LCD 90Hz", "chip": "Helio G85", "battery": "5000 mAh", "charging": "33W"},
        "supportedFaults": DEFAULT_FAULTS,
        "knownIssues": [
            {"id": "lcd-broken-a38", "title": "Nứt kính màn hình LCD", "severity": "medium", "confidence": "high", "symptoms": "Kính nứt ngoài, cảm ứng vẫn nhận.", "solution": "Ép kính bảo vệ màn hình LCD."}
        ]
    }
]

save("oppo", oppo_models)

# --- 5. VIVO (8 models) ---
vivo_models = [
    {
        "id": "vivo-x100-pro",
        "name": "Vivo X100 Pro",
        "brand": "vivo",
        "deviceType": "phone",
        "tier": "P4",
        "releaseYear": 2023,
        "specs": {"screen": "6.78 inch LTPO AMOLED 120Hz 3000 nits cong", "chip": "Dimensity 9300 (4nm)", "battery": "5400 mAh", "charging": "100W FlashCharge, 50W Wireless"},
        "supportedFaults": DEFAULT_FAULTS,
        "knownIssues": [
            {"id": "zeiss-lens-scratch", "title": "Xước kính bảo vệ cụm ống kính tiềm vọng Zeiss APO", "severity": "low", "confidence": "high", "symptoms": "Ảnh chụp ánh sáng ngược có quầng sáng lóa.", "solution": "Thay mặt kính bảo vệ camera Zeiss phủ T* chính hãng."}
        ]
    },
    {
        "id": "vivo-x90-pro",
        "name": "Vivo X90 Pro",
        "brand": "vivo",
        "deviceType": "phone",
        "tier": "P4",
        "releaseYear": 2022,
        "specs": {"screen": "6.78 inch AMOLED 120Hz, lưng da sinh thái", "chip": "Dimensity 9200", "battery": "4870 mAh", "charging": "120W"},
        "supportedFaults": DEFAULT_FAULTS,
        "knownIssues": [
            {"id": "leather-back-peel", "title": "Mòn sờn góc da sinh thái mặt lưng", "severity": "low", "confidence": "high", "symptoms": "Chất liệu da lưng sờn mép cạnh dưới.", "solution": "Thay nắp lưng da sinh thái mới."}
        ]
    },
    {
        "id": "vivo-v30-pro",
        "name": "Vivo V30 Pro",
        "brand": "vivo",
        "deviceType": "phone",
        "tier": "P3",
        "releaseYear": 2024,
        "specs": {"screen": "6.78 inch AMOLED 120Hz cong 3D", "chip": "Dimensity 8200", "battery": "5000 mAh", "charging": "80W FlashCharge"},
        "supportedFaults": DEFAULT_FAULTS,
        "knownIssues": [
            {"id": "aura-light-led", "title": "Vòng đèn Aura Light nhấp nháy không đều màu", "severity": "low", "confidence": "high", "symptoms": "Đèn led vòng Aura sau lưng chập chờn khi chụp chân dung đêm.", "solution": "Thay cáp module đèn Flash Aura Light."}
        ]
    },
    {
        "id": "vivo-v30",
        "name": "Vivo V30",
        "brand": "vivo",
        "deviceType": "phone",
        "tier": "P2",
        "releaseYear": 2024,
        "specs": {"screen": "6.78 inch AMOLED 120Hz cong siêu mỏng", "chip": "Snapdragon 7 Gen 3", "battery": "5000 mAh", "charging": "80W"},
        "supportedFaults": DEFAULT_FAULTS,
        "knownIssues": [
            {"id": "curved-glass-edge-v30", "title": "Nứt kính mép cong sau va chạm", "severity": "medium", "confidence": "high", "symptoms": "Vỡ kính cạnh viền cong.", "solution": "Ép kính màn hình cong công nghệ chân không."}
        ]
    },
    {
        "id": "vivo-v29",
        "name": "Vivo V29 5G",
        "brand": "vivo",
        "deviceType": "phone",
        "tier": "P2",
        "releaseYear": 2023,
        "specs": {"screen": "6.78 inch 1.5K AMOLED 120Hz cong", "chip": "Snapdragon 778G", "battery": "4600 mAh", "charging": "80W"},
        "supportedFaults": DEFAULT_FAULTS,
        "knownIssues": [
            {"id": "battery-aged-v29", "title": "Pin sụt nhanh sau 1.5 năm dùng sạc 80W", "severity": "medium", "confidence": "high", "symptoms": "Máy mau hết pin, thời gian sáng màn hình giảm.", "solution": "Thay pin chính hãng Vivo."}
        ]
    },
    {
        "id": "vivo-v27",
        "name": "Vivo V27 5G",
        "brand": "vivo",
        "deviceType": "phone",
        "tier": "P2",
        "releaseYear": 2023,
        "specs": {"screen": "6.78 inch AMOLED 120Hz cong", "chip": "Dimensity 7200", "battery": "4600 mAh", "charging": "66W"},
        "supportedFaults": DEFAULT_FAULTS,
        "knownIssues": [
            {"id": "type-c-v27", "title": "Cổng sạc Type-C lỏng sau va quệt dây cáp", "severity": "medium", "confidence": "high", "symptoms": "Chân cắm lỏng lẻo, dễ tuột cáp sạc.", "solution": "Thay bo mạch chân sạc phụ."}
        ]
    },
    {
        "id": "vivo-y36",
        "name": "Vivo Y36",
        "brand": "vivo",
        "deviceType": "phone",
        "tier": "P1",
        "releaseYear": 2023,
        "specs": {"screen": "6.64 inch IPS LCD 90Hz", "chip": "Snapdragon 680", "battery": "5000 mAh", "charging": "44W"},
        "supportedFaults": DEFAULT_FAULTS,
        "knownIssues": [
            {"id": "lcd-shatter-y36", "title": "Màn hình nứt vỡ LCD", "severity": "high", "confidence": "high", "symptoms": "Sọc màn hình hoặc liệt cảm ứng.", "solution": "Thay nguyên bộ màn hình IPS LCD mới."}
        ]
    },
    {
        "id": "vivo-y27",
        "name": "Vivo Y27",
        "brand": "vivo",
        "deviceType": "phone",
        "tier": "P1",
        "releaseYear": 2023,
        "specs": {"screen": "6.64 inch IPS LCD FHD+", "chip": "Helio G85", "battery": "5000 mAh", "charging": "44W"},
        "supportedFaults": DEFAULT_FAULTS,
        "knownIssues": [
            {"id": "speaker-earpiece-y27", "title": "Loa thoại nhỏ tiếng sau khi đi mưa", "severity": "low", "confidence": "high", "symptoms": "Nghe người khác nói chuyện rất bé.", "solution": "Vệ sinh màng loa hoặc thay loa thoại."}
        ]
    }
]

save("vivo", vivo_models)

# --- 6. REALME (7 models) ---
realme_models = [
    {
        "id": "realme-gt-5-pro",
        "name": "Realme GT 5 Pro",
        "brand": "realme",
        "deviceType": "phone",
        "tier": "P4",
        "releaseYear": 2023,
        "specs": {"screen": "6.78 inch AMOLED 144Hz 4500 nits 1.5K cong", "chip": "Snapdragon 8 Gen 3", "battery": "5400 mAh", "charging": "100W, 50W Wireless"},
        "supportedFaults": DEFAULT_FAULTS,
        "knownIssues": [
            {"id": "periscope-ois-heat", "title": "Camera tiềm vọng rung giật nhẹ khi máy đang nóng", "severity": "medium", "confidence": "high", "symptoms": "Ống kính tiềm vọng zoom 3x bị rung khi chụp liên tục.", "solution": "Cân chỉnh module chống rung OIS hoặc vệ sinh buồng tản nhiệt."}
        ]
    },
    {
        "id": "realme-gt-neo-5",
        "name": "Realme GT Neo 5 (240W / 150W)",
        "brand": "realme",
        "deviceType": "phone",
        "tier": "P3",
        "releaseYear": 2023,
        "specs": {"screen": "6.74 inch AMOLED 144Hz 1.5K, đèn Halo RGB", "chip": "Snapdragon 8+ Gen 1", "battery": "4600 mAh / 5000 mAh", "charging": "240W / 150W Ultra Fast"},
        "supportedFaults": DEFAULT_FAULTS,
        "knownIssues": [
            {"id": "240w-port-burn", "title": "Chân sạc Type-C quá tải nhiệt do công suất cực lớn 240W", "severity": "high", "confidence": "high", "symptoms": "Chân sạc có vết ám đen hoặc không nhận dòng sạc tối đa.", "solution": "Thay cụm bo chân sạc chịu tải dòng cao 12A chuyên dụng."}
        ]
    },
    {
        "id": "realme-12-pro-plus",
        "name": "Realme 12 Pro+",
        "brand": "realme",
        "deviceType": "phone",
        "tier": "P3",
        "releaseYear": 2024,
        "specs": {"screen": "6.7 inch AMOLED 120Hz cong viền, lưng da sinh thái", "chip": "Snapdragon 7s Gen 2", "battery": "5000 mAh", "charging": "67W"},
        "supportedFaults": DEFAULT_FAULTS,
        "knownIssues": [
            {"id": "leather-scratch-r12pp", "title": "Trầy xước da sinh thái mặt lưng quanh gờ đồng hồ", "severity": "low", "confidence": "high", "symptoms": "Chất liệu da sau lưng sờn mép gờ viền.", "solution": "Thay nắp lưng da sinh thái chính hãng."}
        ]
    },
    {
        "id": "realme-12-plus",
        "name": "Realme 12+",
        "brand": "realme",
        "deviceType": "phone",
        "tier": "P2",
        "releaseYear": 2024,
        "specs": {"screen": "6.67 inch AMOLED 120Hz phẳng", "chip": "Dimensity 7050", "battery": "5000 mAh", "charging": "67W"},
        "supportedFaults": DEFAULT_FAULTS,
        "knownIssues": [
            {"id": "touch-rainwater", "title": "Cảm ứng Rainwater Smart Touch nhận chập chờn khi tay ướt nhiều", "severity": "low", "confidence": "high", "symptoms": "Lau tay khô vuốt mới nhận chuẩn.", "solution": "Cập nhật phần mềm và kiểm tra tấm dán bảo vệ."}
        ]
    },
    {
        "id": "realme-11-pro",
        "name": "Realme 11 Pro",
        "brand": "realme",
        "deviceType": "phone",
        "tier": "P2",
        "releaseYear": 2023,
        "specs": {"screen": "6.7 inch AMOLED 120Hz cong", "chip": "Dimensity 7050", "battery": "5000 mAh", "charging": "67W"},
        "supportedFaults": DEFAULT_FAULTS,
        "knownIssues": [
            {"id": "curved-glass-crack-r11p", "title": "Nứt kính mép cong sau rơi", "severity": "medium", "confidence": "high", "symptoms": "Mặt kính rạn mép ngoài.", "solution": "Ép kính cong màn hình AMOLED."}
        ]
    },
    {
        "id": "realme-c67",
        "name": "Realme C67",
        "brand": "realme",
        "deviceType": "phone",
        "tier": "P1",
        "releaseYear": 2023,
        "specs": {"screen": "6.72 inch IPS LCD 90Hz", "chip": "Snapdragon 685", "battery": "5000 mAh", "charging": "33W"},
        "supportedFaults": DEFAULT_FAULTS,
        "knownIssues": [
            {"id": "screen-crack-c67", "title": "Màn hình nứt vỡ LCD", "severity": "high", "confidence": "high", "symptoms": "Màn hình đen góc hoặc sọc dọc.", "solution": "Thay cụm màn hình IPS LCD mới."}
        ]
    },
    {
        "id": "realme-c55",
        "name": "Realme C55",
        "brand": "realme",
        "deviceType": "phone",
        "tier": "P1",
        "releaseYear": 2023,
        "specs": {"screen": "6.72 inch IPS LCD 90Hz", "chip": "Helio G88", "battery": "5000 mAh", "charging": "33W"},
        "supportedFaults": DEFAULT_FAULTS,
        "knownIssues": [
            {"id": "charging-loose-c55", "title": "Chân sạc Type-C cắm lỏng lẻo", "severity": "medium", "confidence": "high", "symptoms": "Cắm dây sạc nhận chập chờn.", "solution": "Thay cụm bo sạc phụ."}
        ]
    }
]

save("realme", realme_models)

# --- 7. GOOGLE PIXEL (10 models) ---
google_models = [
    {
        "id": "google-pixel-9-pro-xl",
        "name": "Google Pixel 9 Pro XL",
        "brand": "google",
        "deviceType": "phone",
        "tier": "P4",
        "releaseYear": 2024,
        "specs": {"screen": "6.8 inch Super Actua OLED 120Hz LTPO 3000 nits", "chip": "Google Tensor G4 (4nm)", "battery": "5060 mAh", "charging": "37W Wired, 23W Wireless"},
        "supportedFaults": DEFAULT_FAULTS,
        "knownIssues": [
            {"id": "camera-visor-glass", "title": "Nứt kính thanh camera Bar (Camera Visor) kéo ngang lưng", "severity": "medium", "confidence": "high", "symptoms": "Kính thanh ngang camera sau nứt sau khi cấn cạnh bàn.", "solution": "Thay kính thanh ngang camera visor chính hãng Google."}
        ]
    },
    {
        "id": "google-pixel-9-pro",
        "name": "Google Pixel 9 Pro",
        "brand": "google",
        "deviceType": "phone",
        "tier": "P4",
        "releaseYear": 2024,
        "specs": {"screen": "6.3 inch Super Actua OLED 120Hz LTPO", "chip": "Google Tensor G4", "battery": "4700 mAh", "charging": "27W"},
        "supportedFaults": DEFAULT_FAULTS,
        "knownIssues": [
            {"id": "ultrasonic-fingerprint-thick-glass", "title": "Kén vân tay siêu âm khi dán kính cường lực dày", "severity": "low", "confidence": "high", "symptoms": "Vân tay siêu âm khó nhận khi dán kính không chuẩn.", "solution": "Dán kính cường lực đạt chứng nhận Made for Google."}
        ]
    },
    {
        "id": "google-pixel-9",
        "name": "Google Pixel 9",
        "brand": "google",
        "deviceType": "phone",
        "tier": "P3",
        "releaseYear": 2024,
        "specs": {"screen": "6.3 inch Actua OLED 120Hz", "chip": "Google Tensor G4", "battery": "4700 mAh", "charging": "27W"},
        "supportedFaults": DEFAULT_FAULTS,
        "knownIssues": [
            {"id": "thermal-hot-outdoor", "title": "Máy ấm khi dùng mạng di động ngoài trời nắng", "severity": "low", "confidence": "high", "symptoms": "Khung nhôm bóng ấm lên.", "solution": "Kiểm tra chu kỳ pin và tối ưu ứng dụng chạy ngầm."}
        ]
    },
    {
        "id": "google-pixel-8-pro",
        "name": "Google Pixel 8 Pro",
        "brand": "google",
        "deviceType": "phone",
        "tier": "P4",
        "releaseYear": 2023,
        "specs": {"screen": "6.7 inch Super Actua OLED 120Hz phẳng", "chip": "Google Tensor G3", "battery": "5050 mAh", "charging": "30W"},
        "supportedFaults": DEFAULT_FAULTS,
        "knownIssues": [
            {"id": "screen-bump-bumps", "title": "Vết lồi nhỏ dưới lớp kính màn hình (Display Bumps)", "severity": "medium", "confidence": "high", "symptoms": "Nhìn nghiêng dưới ánh đèn thấy các điểm chấm gồ nhẹ từ ốc bo mạch bên dưới.", "solution": "Thay màn hình nguyên bộ và lót lại đệm cao su vi mạch."}
        ]
    },
    {
        "id": "google-pixel-8",
        "name": "Google Pixel 8",
        "brand": "google",
        "deviceType": "phone",
        "tier": "P3",
        "releaseYear": 2023,
        "specs": {"screen": "6.2 inch Actua OLED 120Hz", "chip": "Google Tensor G3", "battery": "4575 mAh", "charging": "27W"},
        "supportedFaults": DEFAULT_FAULTS,
        "knownIssues": [
            {"id": "pink-line-screen", "title": "Sọc tím dọc màn hình OLED (Chương trình bảo hành mở rộng của Google)", "severity": "critical", "confidence": "high", "symptoms": "Màn hình tự phát sinh một đường sọc tím dọc từ trên xuống dưới.", "solution": "Thay cụm màn hình OLED chính hãng Google."}
        ]
    },
    {
        "id": "google-pixel-8a",
        "name": "Google Pixel 8a",
        "brand": "google",
        "deviceType": "phone",
        "tier": "P2",
        "releaseYear": 2024,
        "specs": {"screen": "6.1 inch Actua OLED 120Hz viền dày", "chip": "Google Tensor G3", "battery": "4492 mAh", "charging": "18W"},
        "supportedFaults": DEFAULT_FAULTS,
        "knownIssues": [
            {"id": "matte-back-scratch", "title": "Trầy xước bề mặt lưng nhựa nhám", "severity": "low", "confidence": "high", "symptoms": "Lưng sau xước vệt bóng.", "solution": "Thay nắp lưng hoặc dán PPF."}
        ]
    },
    {
        "id": "google-pixel-7-pro",
        "name": "Google Pixel 7 Pro",
        "brand": "google",
        "deviceType": "phone",
        "tier": "P3",
        "releaseYear": 2022,
        "specs": {"screen": "6.7 inch LTPO AMOLED 120Hz cong", "chip": "Google Tensor G2", "battery": "5000 mAh", "charging": "23W"},
        "supportedFaults": DEFAULT_FAULTS,
        "knownIssues": [
            {"id": "camera-glass-spontaneous", "title": "Mặt kính camera sau tự nứt vỡ trong thời tiết lạnh", "severity": "high", "confidence": "high", "symptoms": "Kính ống kính camera sau bỗng nhiên vỡ vụn hình tròn.", "solution": "Thay mặt kính camera sau và kiểm tra áp suất kín khí buồng camera."},
            {"id": "volume-button-fall-out", "title": "Phím tăng giảm âm lượng tự rơi rụng ra ngoài", "severity": "medium", "confidence": "high", "symptoms": "Phím âm lượng cơ học bị lỏng ngàm và rơi mất nút bấm.", "solution": "Lắp lại phím âm lượng mới có ngàm giữ kim loại gia cố."}
        ]
    },
    {
        "id": "google-pixel-7",
        "name": "Google Pixel 7",
        "brand": "google",
        "deviceType": "phone",
        "tier": "P2",
        "releaseYear": 2022,
        "specs": {"screen": "6.3 inch AMOLED 90Hz", "chip": "Google Tensor G2", "battery": "4355 mAh", "charging": "20W"},
        "supportedFaults": DEFAULT_FAULTS,
        "knownIssues": [
            {"id": "fingerprint-optical-slow-p7", "title": "Cảm biến vân tay quang học nhận chậm", "severity": "medium", "confidence": "high", "symptoms": "Nhận diện trễ 1-2 giây.", "solution": "Cân chỉnh lại công cụ Google Calibration Tool."}
        ]
    },
    {
        "id": "google-pixel-7a",
        "name": "Google Pixel 7a",
        "brand": "google",
        "deviceType": "phone",
        "tier": "P2",
        "releaseYear": 2023,
        "specs": {"screen": "6.1 inch OLED 90Hz", "chip": "Google Tensor G2", "battery": "4385 mAh", "charging": "18W"},
        "supportedFaults": DEFAULT_FAULTS,
        "knownIssues": [
            {"id": "battery-heat-tensor", "title": "Thân máy ấm và pin hao khi dùng 4G/5G", "severity": "medium", "confidence": "high", "symptoms": "Ấm lưng máy và hao pin nhanh.", "solution": "Kiểm tra chu kỳ sạc pin và cập nhật bản vá bảo mật."}
        ]
    },
    {
        "id": "google-pixel-6-pro",
        "name": "Google Pixel 6 Pro",
        "brand": "google",
        "deviceType": "phone",
        "tier": "P2",
        "releaseYear": 2021,
        "specs": {"screen": "6.71 inch LTPO AMOLED 120Hz cong", "chip": "Google Tensor (1st Gen)", "battery": "5003 mAh", "charging": "23W"},
        "supportedFaults": DEFAULT_FAULTS,
        "knownIssues": [
            {"id": "signal-drop-modem", "title": "Sóng di động chập chờn do chip modem mạng Exynos 5123", "severity": "high", "confidence": "high", "symptoms": "Đang gọi điện bị ngắt sóng hoặc tốc độ 4G chậm ở khu vực sóng yếu.", "solution": "Cập nhật firmware baseband hoặc kiểm tra tiếp địa ăng-ten bo mạch."}
        ]
    }
]

save("google", google_models)

# --- 8. SONY (6 models) ---
sony_models = [
    {
        "id": "sony-xperia-1-vi",
        "name": "Sony Xperia 1 VI",
        "brand": "sony",
        "deviceType": "phone",
        "tier": "P4",
        "releaseYear": 2024,
        "specs": {"screen": "6.5 inch LTPO OLED FHD+ 120Hz (tỉ lệ mới 19.5:9)", "chip": "Snapdragon 8 Gen 3", "battery": "5000 mAh", "charging": "30W USB PD, jack 3.5mm"},
        "supportedFaults": DEFAULT_FAULTS,
        "knownIssues": [
            {"id": "telephoto-macro-focus", "title": "Lấy nét camera tiềm vọng Telephoto Macro bị kẹt", "severity": "medium", "confidence": "high", "symptoms": "Chụp cận cảnh macro khoảng cách gần bị out nét mô-tơ ống kính.", "solution": "Cân chỉnh mô-tơ lấy nét ống kính telephoto liên tục."}
        ]
    },
    {
        "id": "sony-xperia-1-v",
        "name": "Sony Xperia 1 V",
        "brand": "sony",
        "deviceType": "phone",
        "tier": "P4",
        "releaseYear": 2023,
        "specs": {"screen": "6.5 inch 4K OLED 120Hz (21:9)", "chip": "Snapdragon 8 Gen 2", "battery": "5000 mAh", "charging": "30W"},
        "supportedFaults": DEFAULT_FAULTS,
        "knownIssues": [
            {"id": "4k-display-thermal", "title": "Màn hình 4K tự hạ độ phân giải khi thân máy nóng", "severity": "low", "confidence": "high", "symptoms": "Thân máy ấm lên khi quay 4K 120fps liên tục.", "solution": "Vệ sinh tản nhiệt và bôi keo tản nhiệt chuyên sâu."}
        ]
    },
    {
        "id": "sony-xperia-1-iv",
        "name": "Sony Xperia 1 IV",
        "brand": "sony",
        "deviceType": "phone",
        "tier": "P3",
        "releaseYear": 2022,
        "specs": {"screen": "6.5 inch 4K OLED 120Hz (21:9)", "chip": "Snapdragon 8 Gen 1", "battery": "5000 mAh", "charging": "30W"},
        "supportedFaults": DEFAULT_FAULTS,
        "knownIssues": [
            {"id": "overheat-camera-shutdown", "title": "Cảnh báo quá nhiệt tự đóng app Camera sau 5 phút quay 4K", "severity": "critical", "confidence": "high", "symptoms": "Báo biểu tượng nhiệt kế và bắt buộc tắt máy ảnh.", "solution": "Vệ sinh tản nhiệt, bôi lại keo tản nhiệt kim loại lỏng/graphene và kiểm tra pin."}
        ]
    },
    {
        "id": "sony-xperia-5-v",
        "name": "Sony Xperia 5 V",
        "brand": "sony",
        "deviceType": "phone",
        "tier": "P3",
        "releaseYear": 2023,
        "specs": {"screen": "6.1 inch FHD+ OLED 120Hz", "chip": "Snapdragon 8 Gen 2", "battery": "5000 mAh", "charging": "30W"},
        "supportedFaults": DEFAULT_FAULTS,
        "knownIssues": [
            {"id": "side-fingerprint-sony", "title": "Cảm biến vân tay cạnh bên phím nguồn chập chờn", "severity": "medium", "confidence": "high", "symptoms": "Vân tay không nhận hoặc bị mất mục cài đặt vân tay trong Cài đặt.", "solution": "Thay cụm cáp phím nguồn tích hợp cảm biến vân tay mới."}
        ]
    },
    {
        "id": "sony-xperia-5-iv",
        "name": "Sony Xperia 5 IV",
        "brand": "sony",
        "deviceType": "phone",
        "tier": "P3",
        "releaseYear": 2022,
        "specs": {"screen": "6.1 inch OLED 120Hz", "chip": "Snapdragon 8 Gen 1", "battery": "5000 mAh", "charging": "30W"},
        "supportedFaults": DEFAULT_FAULTS,
        "knownIssues": [
            {"id": "green-line-xperia", "title": "Sọc xanh lá cây chỉ đứng trên tấm nền OLED", "severity": "critical", "confidence": "high", "symptoms": "Xuất hiện sọc xanh chỉ thẳng đứng từ trên xuống.", "solution": "Bắn laser vi mạch màn hình hoặc thay màn hình OLED zin."}
        ]
    },
    {
        "id": "sony-xperia-10-vi",
        "name": "Sony Xperia 10 VI",
        "brand": "sony",
        "deviceType": "phone",
        "tier": "P2",
        "releaseYear": 2024,
        "specs": {"screen": "6.1 inch OLED 60Hz tỉ lệ 21:9", "chip": "Snapdragon 6 Gen 1", "battery": "5000 mAh", "charging": "20W"},
        "supportedFaults": DEFAULT_FAULTS,
        "knownIssues": [
            {"id": "charging-slow-x10", "title": "Thời gian sạc pin lâu (hơn 2 tiếng đầy pin)", "severity": "low", "confidence": "high", "symptoms": "Sạc đầy 5000mAh mất thời gian do giới hạn công suất sạc.", "solution": "Kiểm tra củ cáp sạc chuẩn PD."}
        ]
    }
]

save("sony", sony_models)

# --- 9. ONEPLUS (6 models) ---
oneplus_models = [
    {
        "id": "oneplus-12",
        "name": "OnePlus 12",
        "brand": "oneplus",
        "deviceType": "phone",
        "tier": "P4",
        "releaseYear": 2023,
        "specs": {"screen": "6.82 inch 2K LTPO AMOLED 120Hz 4500 nits cong", "chip": "Snapdragon 8 Gen 3", "battery": "5400 mAh", "charging": "100W SuperVOOC, 50W AirVOOC"},
        "supportedFaults": DEFAULT_FAULTS,
        "knownIssues": [
            {"id": "gap-camera-bump", "title": "Hở khe keo nhẹ giữa cụm camera tròn và mặt lưng", "severity": "low", "confidence": "high", "symptoms": "Bụi dễ lọt vào kẽ mép gờ camera.", "solution": "Dán lại keo chống nước chuyên dụng chuẩn nhà máy."}
        ]
    },
    {
        "id": "oneplus-11",
        "name": "OnePlus 11",
        "brand": "oneplus",
        "deviceType": "phone",
        "tier": "P3",
        "releaseYear": 2023,
        "specs": {"screen": "6.7 inch 2K AMOLED 120Hz cong", "chip": "Snapdragon 8 Gen 2", "battery": "5000 mAh", "charging": "100W"},
        "supportedFaults": DEFAULT_FAULTS,
        "knownIssues": [
            {"id": "green-line-op11", "title": "Sọc xanh lá cây chỉ đứng màn hình AMOLED", "severity": "critical", "confidence": "high", "symptoms": "Xuất hiện 1 vệt sọc xanh sáng chói sau khi máy ấm lên.", "solution": "Hàn laser vi mạch cổ cáp hoặc thay màn hình zin mới."}
        ]
    },
    {
        "id": "oneplus-10-pro",
        "name": "OnePlus 10 Pro",
        "brand": "oneplus",
        "deviceType": "phone",
        "tier": "P3",
        "releaseYear": 2022,
        "specs": {"screen": "6.7 inch LTPO2 AMOLED 120Hz cong", "chip": "Snapdragon 8 Gen 1", "battery": "5000 mAh", "charging": "80W"},
        "supportedFaults": DEFAULT_FAULTS,
        "knownIssues": [
            {"id": "chassis-snap-bend", "title": "Khung nhôm dễ gãy gập ngang dải ăng-ten khi chịu lực uốn", "severity": "critical", "confidence": "high", "symptoms": "Khung vỏ máy bị cong gãy ở vị trí dưới cụm camera sau khi bị tì đè túi quần.", "solution": "Thay toàn bộ vỏ khung sườn kim loại chính hãng và nắn chỉnh bo mạch."}
        ]
    },
    {
        "id": "oneplus-open",
        "name": "OnePlus Open",
        "brand": "oneplus",
        "deviceType": "phone",
        "tier": "P5",
        "releaseYear": 2023,
        "specs": {"screen": "Màn trong 7.82 inch 120Hz gập không nếp nhăn, màn ngoài 6.31 inch 120Hz", "chip": "Snapdragon 8 Gen 2", "battery": "4805 mAh", "charging": "67W"},
        "supportedFaults": FOLD_FAULTS,
        "knownIssues": [
            {"id": "open-hinge-dust", "title": "Bụi lọt bản lề gây kêu lạo xạo khi gập mở", "severity": "medium", "confidence": "high", "symptoms": "Gập mở có cảm giác cộm cơ học.", "solution": "Vệ sinh chuyên sâu cấu trúc bản lề Flexion Hinge."}
        ]
    },
    {
        "id": "oneplus-ace-3",
        "name": "OnePlus Ace 3 (12R)",
        "brand": "oneplus",
        "deviceType": "phone",
        "tier": "P3",
        "releaseYear": 2024,
        "specs": {"screen": "6.78 inch 1.5K AMOLED 120Hz 4500 nits cong", "chip": "Snapdragon 8 Gen 2", "battery": "5500 mAh", "charging": "100W"},
        "supportedFaults": DEFAULT_FAULTS,
        "knownIssues": [
            {"id": "curved-glass-ace3", "title": "Nứt vỡ mặt kính mép cong khi va quệt", "severity": "medium", "confidence": "high", "symptoms": "Mặt kính rạn nứt ngoài.", "solution": "Ép kính màn hình cong công nghệ OCA."}
        ]
    },
    {
        "id": "oneplus-nord-3",
        "name": "OnePlus Nord 3 5G",
        "brand": "oneplus",
        "deviceType": "phone",
        "tier": "P2",
        "releaseYear": 2023,
        "specs": {"screen": "6.74 inch AMOLED 120Hz phẳng", "chip": "Dimensity 9000", "battery": "5000 mAh", "charging": "80W"},
        "supportedFaults": DEFAULT_FAULTS,
        "knownIssues": [
            {"id": "battery-aged-nord3", "title": "Pin mau tụt khi bật màn hình 120Hz liên tục", "severity": "medium", "confidence": "high", "symptoms": "Onscreen chỉ còn 4 tiếng.", "solution": "Thay pin mới bảo hành 6 tháng."}
        ]
    }
]

save("oneplus", oneplus_models)

# --- 10. OTHERS (6 models) ---
others_models = [
    {
        "id": "asus-rog-phone-8-pro",
        "name": "ASUS ROG Phone 8 Pro",
        "brand": "others",
        "deviceType": "phone",
        "tier": "P4",
        "releaseYear": 2024,
        "specs": {"screen": "6.78 inch Samsung E6 AMOLED 165Hz LTPO, AniMe Matrix LED", "chip": "Snapdragon 8 Gen 3", "battery": "5500 mAh", "charging": "65W HyperCharge, 2 cổng Type-C"},
        "supportedFaults": DEFAULT_FAULTS,
        "knownIssues": [
            {"id": "side-type-c-short", "title": "Chập chân sạc Type-C cạnh hông (Side Port) do cắm phụ kiện quạt tản", "severity": "high", "confidence": "high", "symptoms": "Cổng sạc cạnh hông không nhận sạc hoặc không nhận quạt AeroActive Cooler.", "solution": "Thay bo chân sạc hông chuyên dụng cho ROG Phone."},
            {"id": "airtrigger-lag", "title": "Phím siêu âm AirTrigger cạnh viền phản hồi chập chờn", "severity": "medium", "confidence": "high", "symptoms": "Chạm vào phím cảm ứng siêu âm chơi game không ăn.", "solution": "Cân chỉnh độ nhạy phần mềm hoặc thay cáp cảm biến siêu âm."}
        ]
    },
    {
        "id": "nothing-phone-2",
        "name": "Nothing Phone (2)",
        "brand": "nothing",
        "deviceType": "phone",
        "tier": "P3",
        "releaseYear": 2023,
        "specs": {"screen": "6.7 inch LTPO OLED 120Hz, giao diện đèn Glyph LED trong suốt", "chip": "Snapdragon 8+ Gen 1", "battery": "4700 mAh", "charging": "45W"},
        "supportedFaults": DEFAULT_FAULTS,
        "knownIssues": [
            {"id": "glyph-led-strip-burn", "title": "Dải đèn Glyph Interface sau lưng bị đứt đoạn hoặc chớp nháy", "severity": "medium", "confidence": "high", "symptoms": "Một dải đèn LED Glyph không sáng khi có chuông cuộc gọi.", "solution": "Thay dải cáp LED Glyph Interface mặt lưng trong suốt."}
        ]
    },
    {
        "id": "nothing-phone-2a",
        "name": "Nothing Phone (2a)",
        "brand": "nothing",
        "deviceType": "phone",
        "tier": "P2",
        "releaseYear": 2024,
        "specs": {"screen": "6.7 inch AMOLED 120Hz viền mỏng đều", "chip": "Dimensity 7200 Pro", "battery": "5000 mAh", "charging": "45W"},
        "supportedFaults": DEFAULT_FAULTS,
        "knownIssues": [
            {"id": "transparent-plastic-scratch", "title": "Trầy xước mặt lưng nhựa trong suốt", "severity": "low", "confidence": "high", "symptoms": "Lưng nhựa trong dễ xước lông mèo khi cọ xát cát hạt.", "solution": "Dán film PPF phục hồi hoặc thay mặt lưng trong suốt mới."}
        ]
    },
    {
        "id": "huawei-mate-60-pro",
        "name": "Huawei Mate 60 Pro",
        "brand": "huawei",
        "deviceType": "phone",
        "tier": "P4",
        "releaseYear": 2023,
        "specs": {"screen": "6.82 inch LTPO OLED 120Hz cong 3 nốt ruồi, kính Kunlun 2", "chip": "Kirin 9000S (7nm), gọi vệ tinh", "battery": "5000 mAh", "charging": "88W SuperCharge"},
        "supportedFaults": DEFAULT_FAULTS,
        "knownIssues": [
            {"id": "satellite-antenna-drop", "title": "Mất kết nối cuộc gọi vệ tinh sau khi va đập cong sườn", "severity": "medium", "confidence": "verify", "symptoms": "Không tìm thấy vệ tinh định vị khẩn cấp.", "solution": "Căn chỉnh lại mạch ăng-ten vệ tinh tần số cao trên khung sườn máy."}
        ]
    },
    {
        "id": "honor-magic-6-pro",
        "name": "Honor Magic 6 Pro",
        "brand": "honor",
        "deviceType": "phone",
        "tier": "P4",
        "releaseYear": 2024,
        "specs": {"screen": "6.8 inch LTPO OLED 120Hz 4320Hz PWM cong viền", "chip": "Snapdragon 8 Gen 3", "battery": "5600 mAh Pin Silicon-Carbon", "charging": "80W, 66W Wireless"},
        "supportedFaults": DEFAULT_FAULTS,
        "knownIssues": [
            {"id": "silicon-carbon-battery-health", "title": "Hiển thị % dung lượng pin chập chờn khi dùng củ sạc lạ", "severity": "low", "confidence": "medium", "symptoms": "Báo sai tỷ lệ phần trăm pin khi không dùng đúng củ sạc Honor.", "solution": "Reset IC quản lý năng lượng pin Silicon-Carbon."}
        ]
    },
    {
        "id": "infinix-note-40-pro",
        "name": "Infinix Note 40 Pro",
        "brand": "infinix",
        "deviceType": "phone",
        "tier": "P2",
        "releaseYear": 2024,
        "specs": {"screen": "6.78 inch AMOLED 120Hz cong 3D viền mỏng", "chip": "Helio G99 Ultimate / Dimensity 7020", "battery": "5000 mAh", "charging": "70W All-Round FastCharge, 20W MagCharge"},
        "supportedFaults": DEFAULT_FAULTS,
        "knownIssues": [
            {"id": "magcharge-coil-disconnect", "title": "Vòng nam châm sạc MagCharge sạc không vào sau rơi", "severity": "medium", "confidence": "high", "symptoms": "Đặt sạc nam châm hít sau lưng không báo nhận điện.", "solution": "Hàn lại chân tiếp xúc cuộn cảm ứng sạc không dây mặt lưng."}
        ]
    }
]

save("others", others_models)
print("=== HOÀN TẤT TẠO TOÀN BỘ 10 FILE CATALOG ĐIỆN THOẠI ===")
