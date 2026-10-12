#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
FixNear RepairAtlas — Tablet Catalog Builder (~30 models)
Xây dựng danh mục Máy Tính Bảng tại data/catalog/tablet/
Bao gồm Apple, Samsung, Xiaomi, Others
"""

import sys
import json
from pathlib import Path

# Force UTF-8 output on Windows
if sys.platform == "win32":
    sys.stdout.reconfigure(encoding="utf-8")

ROOT_DIR = Path(__file__).resolve().parent.parent
TABLET_DIR = ROOT_DIR / "data" / "catalog" / "tablet"
TABLET_DIR.mkdir(parents=True, exist_ok=True)

DEFAULT_TABLET_FAULTS = [
    "screen", "glass-press", "battery", "charging-port", "mainboard",
    "camera", "speaker", "mic", "hinge-body", "thermal", "software",
    "water-damage", "data-recovery", "diagnostic"
]

PENCIL_TABLET_FAULTS = DEFAULT_TABLET_FAULTS + ["pencil-charging"]
PRO_TABLET_FAULTS = PENCIL_TABLET_FAULTS + ["face-id"]

def save(brand, data):
    p = TABLET_DIR / f"{brand}.json"
    with open(p, "w", encoding="utf-8") as f:
        json.dump(data, f, ensure_ascii=False, indent=2)
    print(f"  ✅ Đã lưu {brand}.json: {len(data)} models")

# --- 1. APPLE (14 models) ---
apple_tablets = [
    # iPad Pro M4 (2024)
    {
        "id": "apple-ipad-pro-13-m4-2024",
        "name": "iPad Pro 13 inch (M4 2024)",
        "brand": "apple",
        "deviceType": "tablet",
        "tier": "T3",
        "releaseYear": 2024,
        "specs": {"chip": "Apple M4 (3nm)", "screen": "13.0 inch Ultra Retina XDR Tandem OLED 120Hz (OLED 2 lớp xếp chồng), mỏng 5.1mm", "ram": "8GB/16GB", "storage": "256GB - 2TB", "pencil": "Hỗ trợ Apple Pencil Pro & Apple Pencil USB-C"},
        "supportedFaults": PRO_TABLET_FAULTS,
        "knownIssues": [
            {"id": "tandem-oled-delicate", "title": "Màn hình OLED 2 lớp Tandem OLED siêu mỏng 5.1mm cực kỳ nhạy cảm với lực tì đè", "severity": "critical", "confidence": "high", "symptoms": "Tì đè mạnh hoặc bỏ balo bị cấn làm màn hình sọc kẻ hoặc nứt kính hiển thị.", "solution": "Thay thế cụm màn hình Ultra Retina XDR Tandem OLED nguyên bản bóc máy."},
            {"id": "pencil-pro-barrel-roll", "title": "Mất nhận diện cử chỉ bóp Squeeze và xoay Barrel Roll trên Apple Pencil Pro", "severity": "medium", "confidence": "high", "symptoms": "Hít sạc bên hông vẫn nhận pin nhưng không phản hồi cử chỉ rung xúc giác Haptic.", "solution": "Thay cáp từ tính sạc nam châm hông máy chuyên dụng cho Apple Pencil Pro."}
        ]
    },
    {
        "id": "apple-ipad-pro-11-m4-2024",
        "name": "iPad Pro 11 inch (M4 2024)",
        "brand": "apple",
        "deviceType": "tablet",
        "tier": "T3",
        "releaseYear": 2024,
        "specs": {"chip": "Apple M4", "screen": "11.1 inch Ultra Retina XDR Tandem OLED 120Hz", "ram": "8GB/16GB", "storage": "256GB - 2TB"},
        "supportedFaults": PRO_TABLET_FAULTS,
        "knownIssues": [
            {"id": "ultra-thin-chassis-bend", "title": "Thân máy siêu mỏng 5.3mm dễ cong nhẹ góc loa khi chịu lực uốn", "severity": "high", "confidence": "high", "symptoms": "Đặt trên mặt phẳng máy bị bập bênh.", "solution": "Nắn chỉnh phục hồi khung nhôm unibody chuẩn cân bằng lực."}
        ]
    },
    # iPad Pro M2 (2022)
    {
        "id": "apple-ipad-pro-12-9-m2-2022",
        "name": "iPad Pro 12.9 inch (M2 2022)",
        "brand": "apple",
        "deviceType": "tablet",
        "tier": "T3",
        "releaseYear": 2022,
        "specs": {"chip": "Apple M2", "screen": "12.9 inch Liquid Retina XDR Mini-LED 120Hz 1600 nits", "ram": "8GB/16GB", "ports": "Thunderbolt / USB 4"},
        "supportedFaults": PRO_TABLET_FAULTS,
        "knownIssues": [
            {"id": "pencil-hover-lag", "title": "Tính năng di chuột không chạm Apple Pencil Hover bị chập chờn", "severity": "medium", "confidence": "high", "symptoms": "Bút đưa cách màn hình 12mm không hiện con trỏ xem trước.", "solution": "Cân chỉnh lại cuộn cảm biến điện từ số hóa Digitizer."}
        ]
    },
    {
        "id": "apple-ipad-pro-11-m2-2022",
        "name": "iPad Pro 11 inch (M2 2022)",
        "brand": "apple",
        "deviceType": "tablet",
        "tier": "T3",
        "releaseYear": 2022,
        "specs": {"chip": "Apple M2", "screen": "11.0 inch Liquid Retina IPS 120Hz ProMotion", "ram": "8GB/16GB"},
        "supportedFaults": PRO_TABLET_FAULTS,
        "knownIssues": [
            {"id": "face-id-horizontal-error", "title": "Lỗi Face ID bị che khuất khi cầm ngang máy", "severity": "low", "confidence": "high", "symptoms": "Máy báo tay đang che camera TrueDepth.", "solution": "Vệ sinh màng kính camera hoặc cân chỉnh Dot Projector."}
        ]
    },
    # iPad Pro M1 (2021)
    {
        "id": "apple-ipad-pro-12-9-m1-2021",
        "name": "iPad Pro 12.9 inch (M1 2021)",
        "brand": "apple",
        "deviceType": "tablet",
        "tier": "T3",
        "releaseYear": 2021,
        "specs": {"chip": "Apple M1", "screen": "12.9 inch Liquid Retina XDR Mini-LED 10.000 bóng LED", "ram": "8GB/16GB"},
        "supportedFaults": PRO_TABLET_FAULTS,
        "knownIssues": [
            {"id": "mini-led-blooming-dark", "title": "Hiện tượng quầng sáng nở hoa Blooming quanh chữ trắng trong đêm", "severity": "low", "confidence": "high", "symptoms": "Vệt mờ ánh sáng bao quanh phụ đề trên nền đen.", "solution": "Cân chỉnh độ tương phản màn hình hoặc cập nhật iPadOS."}
        ]
    },
    {
        "id": "apple-ipad-pro-11-m1-2021",
        "name": "iPad Pro 11 inch (M1 2021)",
        "brand": "apple",
        "deviceType": "tablet",
        "tier": "T3",
        "releaseYear": 2021,
        "specs": {"chip": "Apple M1", "screen": "11.0 inch Liquid Retina 120Hz", "ram": "8GB/16GB"},
        "supportedFaults": PRO_TABLET_FAULTS,
        "knownIssues": [
            {"id": "battery-aged-ipad-m1", "title": "Pin chai dung lượng dưới 80% sau 3 năm cày game vẽ đồ họa", "severity": "medium", "confidence": "high", "symptoms": "Pin dùng được hơn 3 tiếng, sạc lâu đầy.", "solution": "Thay pin iPad Pro chính hãng dung lượng chuẩn."}
        ]
    },
    # iPad Air M2 & M1
    {
        "id": "apple-ipad-air-13-m2-2024",
        "name": "iPad Air 13 inch (M2 2024)",
        "brand": "apple",
        "deviceType": "tablet",
        "tier": "T2",
        "releaseYear": 2024,
        "specs": {"chip": "Apple M2", "screen": "13.0 inch Liquid Retina IPS 60Hz (2732x2048)", "ram": "8GB", "ports": "USB-C, Smart Connector"},
        "supportedFaults": PENCIL_TABLET_FAULTS,
        "knownIssues": [
            {"id": "large-screen-crack-air13", "title": "Màn hình 13 inch kích thước lớn dễ nứt góc khi rơi", "severity": "high", "confidence": "high", "symptoms": "Mặt kính rạn nứt góc nhưng cảm ứng vẫn nhận.", "solution": "Ép mặt kính màn hình Retina lớn bằng máy hút chân không chuyên nghiệp."}
        ]
    },
    {
        "id": "apple-ipad-air-11-m2-2024",
        "name": "iPad Air 11 inch (M2 2024)",
        "brand": "apple",
        "deviceType": "tablet",
        "tier": "T2",
        "releaseYear": 2024,
        "specs": {"chip": "Apple M2", "screen": "11.0 inch Liquid Retina IPS 60Hz", "ram": "8GB", "ports": "USB-C"},
        "supportedFaults": PENCIL_TABLET_FAULTS,
        "knownIssues": [
            {"id": "touch-id-top-button-stiff", "title": "Phím nguồn tích hợp Touch ID phía trên bấm bị nặng sượng", "severity": "low", "confidence": "high", "symptoms": "Nút nguồn mất độ nảy, quét vân tay khó khăn.", "solution": "Vệ sinh lò xo phím nguồn hoặc thay cáp nút nguồn Touch ID."}
        ]
    },
    {
        "id": "apple-ipad-air-5-m1-2022",
        "name": "iPad Air 5 (M1 2022)",
        "brand": "apple",
        "deviceType": "tablet",
        "tier": "T2",
        "releaseYear": 2022,
        "specs": {"chip": "Apple M1", "screen": "10.9 inch Liquid Retina IPS 60Hz", "ram": "8GB", "ports": "USB-C 10Gbps"},
        "supportedFaults": PENCIL_TABLET_FAULTS,
        "knownIssues": [
            {"id": "chassis-creak-press", "title": "Thân nhôm sau lưng bị kêu ọp ẹp khi ấn tay (Vấn đề gia công vỏ mỏng)", "severity": "low", "confidence": "high", "symptoms": "Cầm một tay ấn vào giữa nắp lưng nghe tiếng cọt kẹt nhẹ.", "solution": "Đệm thêm lớp foam chống rung bên trong nắp lưng máy."},
            {"id": "pencil-2-charging-fail", "title": "Hít sạc Apple Pencil 2 bên hông không vào điện hoặc ngắt kết nối", "severity": "high", "confidence": "high", "symptoms": "Gắn bút Apple Pencil 2 lên cạnh phải không hiện popup pin kết nối.", "solution": "Thay cuộn cảm ứng sạc không dây nam châm hông iPad."}
        ]
    },
    {
        "id": "apple-ipad-air-4-2020",
        "name": "iPad Air 4 (2020)",
        "brand": "apple",
        "deviceType": "tablet",
        "tier": "T2",
        "releaseYear": 2020,
        "specs": {"chip": "Apple A14 Bionic", "screen": "10.9 inch Liquid Retina IPS 60Hz", "ram": "4GB", "ports": "USB-C"},
        "supportedFaults": PENCIL_TABLET_FAULTS,
        "knownIssues": [
            {"id": "usbc-port-wear-air4", "title": "Cổng sạc Type-C cắm lỏng lẻo chập chờn sau 3 năm", "severity": "medium", "confidence": "high", "symptoms": "Cắm dây sạc nhận rồi ngắt liên tục.", "solution": "Hàn thay thế cụm chân sạc USB-C mới."}
        ]
    },
    # iPad Gen Phổ Thông (T1)
    {
        "id": "apple-ipad-gen-10-2022",
        "name": "iPad Gen 10 10.9 inch (2022)",
        "brand": "apple",
        "deviceType": "tablet",
        "tier": "T1",
        "releaseYear": 2022,
        "specs": {"chip": "Apple A14 Bionic", "screen": "10.9 inch Liquid Retina IPS (Màn ép liền cảm ứng)", "ram": "4GB", "ports": "USB-C 2.0"},
        "supportedFaults": DEFAULT_TABLET_FAULTS,
        "knownIssues": [
            {"id": "screen-gap-air-dust", "title": "Màn hình không có lớp phủ kháng phản chiếu dễ bám vân tay", "severity": "low", "confidence": "high", "symptoms": "Lóa bóng đèn khi dùng phòng học.", "solution": "Dán kính cường lực phủ nhám Paperlike chuyên dụng."}
        ]
    },
    {
        "id": "apple-ipad-gen-9-2021",
        "name": "iPad Gen 9 10.2 inch (2021)",
        "brand": "apple",
        "deviceType": "tablet",
        "tier": "T1",
        "releaseYear": 2021,
        "specs": {"chip": "Apple A13 Bionic", "screen": "10.2 inch Retina IPS (Kính cảm ứng ngoài và màn LCD hiển thị rời nhau)", "ram": "3GB", "ports": "Lightning, Touch ID nút Home"},
        "supportedFaults": DEFAULT_TABLET_FAULTS,
        "knownIssues": [
            {"id": "glass-touch-crack-separate", "title": "Vỡ mặt kính cảm ứng ngoài (Ép kính riêng rất tiết kiệm chi phí)", "severity": "medium", "confidence": "high", "symptoms": "Mặt kính vỡ nứt ngoài, màn hình LCD bên trong vẫn hiển thị sáng đẹp.", "solution": "Thay riêng kính cảm ứng ngoài (Digitizer Glass) giá cực rẻ, không cần thay nguyên bộ màn hình."},
            {"id": "lightning-dust-lint", "title": "Cổng Lightning kẹt xơ vải bụi bẩn cắm sạc không ăn", "severity": "low", "confidence": "high", "symptoms": "Đầu cắm sạc không thể cắm sát ngập vào máy.", "solution": "Vệ sinh chuyên sâu chân cắm Lightning."}
        ]
    },
    {
        "id": "apple-ipad-gen-8-2020",
        "name": "iPad Gen 8 10.2 inch (2020)",
        "brand": "apple",
        "deviceType": "tablet",
        "tier": "T1",
        "releaseYear": 2020,
        "specs": {"chip": "Apple A12 Bionic", "screen": "10.2 inch Retina IPS", "ram": "3GB", "ports": "Lightning"},
        "supportedFaults": DEFAULT_TABLET_FAULTS,
        "knownIssues": [
            {"id": "home-button-stuck-g8", "title": "Phím Home Touch ID bị kẹt bụi bấm cứng ngắc", "severity": "medium", "confidence": "high", "symptoms": "Ấn phím Home rất nặng tay.", "solution": "Vệ sinh màng đệm cao su nút Home."}
        ]
    },
    # iPad Mini
    {
        "id": "apple-ipad-mini-6-2021",
        "name": "iPad Mini 6 8.3 inch (2021)",
        "brand": "apple",
        "deviceType": "tablet",
        "tier": "T2",
        "releaseYear": 2021,
        "specs": {"chip": "Apple A15 Bionic", "screen": "8.3 inch Liquid Retina IPS viền mỏng", "ram": "4GB", "ports": "USB-C, sạc nam châm Pencil 2"},
        "supportedFaults": PENCIL_TABLET_FAULTS,
        "knownIssues": [
            {"id": "jelly-scrolling-vertical", "title": "Hiện tượng cuộn trang gợn sóng (Jelly Scrolling) khi lướt web theo chiều dọc", "severity": "low", "confidence": "high", "symptoms": "Một bên màn hình cuộn nhanh hơn bên còn lại tạo cảm giác gợn sóng thị giác.", "solution": "Đặc tính quét tần số của controller màn hình LCD, dùng theo chiều ngang để triệt tiêu."},
            {"id": "battery-small-mini6", "title": "Pin chai sau thời gian dài chơi game Liên Quân / PUBG", "severity": "medium", "confidence": "high", "symptoms": "Dung lượng pin 5124 mAh tiêu hao nhanh sau 2 tiếng chơi game.", "solution": "Thay pin mới chất lượng cao bảo hành 6 tháng."}
        ]
    }
]

save("apple", apple_tablets)

# --- 2. SAMSUNG (8 models) ---
samsung_tablets = [
    # Flagship Tab S9 Ultra & S9+ & S9 (T3)
    {
        "id": "samsung-galaxy-tab-s9-ultra",
        "name": "Galaxy Tab S9 Ultra",
        "brand": "samsung",
        "deviceType": "tablet",
        "tier": "T3",
        "releaseYear": 2023,
        "specs": {"screen": "14.6 inch Dynamic AMOLED 2X 120Hz WQXGA+ khổng lồ, kháng nước IP68", "chip": "Snapdragon 8 Gen 2 for Galaxy", "battery": "11.200 mAh", "spen": "Bút S-Pen kháng nước sạc 2 chiều"},
        "supportedFaults": PENCIL_TABLET_FAULTS,
        "knownIssues": [
            {"id": "giant-screen-drop-crack", "title": "Màn hình 14.6 inch khổng lồ dễ nứt vỡ khi chịu lực tì đè", "severity": "critical", "confidence": "high", "symptoms": "Cấn đồ vật làm nứt tấm nền AMOLED hoặc sọc chỉ dọc.", "solution": "Thay cụm màn hình Dynamic AMOLED 2X 14.6 inch chính hãng Samsung."},
            {"id": "spen-charging-strip-back", "title": "Rãnh nam châm sạc bút S-Pen mặt lưng không nhận sạc", "severity": "medium", "confidence": "high", "symptoms": "Hít bút S-Pen sau lưng không thấy biểu tượng báo sạc pin.", "solution": "Thay dải cuộn từ tính sạc bút sau nắp lưng."}
        ]
    },
    {
        "id": "samsung-galaxy-tab-s9-plus",
        "name": "Galaxy Tab S9+",
        "brand": "samsung",
        "deviceType": "tablet",
        "tier": "T3",
        "releaseYear": 2023,
        "specs": {"screen": "12.4 inch Dynamic AMOLED 2X 120Hz", "chip": "Snapdragon 8 Gen 2", "battery": "10.090 mAh", "charging": "45W"},
        "supportedFaults": PENCIL_TABLET_FAULTS,
        "knownIssues": [
            {"id": "battery-aged-s9p", "title": "Pin sụt nhanh khi dùng độ sáng cao ngoài trời", "severity": "medium", "confidence": "high", "symptoms": "Onscreen giảm chỉ còn 4 tiếng.", "solution": "Thay pin mới chất lượng cao."}
        ]
    },
    {
        "id": "samsung-galaxy-tab-s9",
        "name": "Galaxy Tab S9",
        "brand": "samsung",
        "deviceType": "tablet",
        "tier": "T3",
        "releaseYear": 2023,
        "specs": {"screen": "11.0 inch Dynamic AMOLED 2X 120Hz", "chip": "Snapdragon 8 Gen 2", "battery": "8400 mAh", "charging": "45W"},
        "supportedFaults": PENCIL_TABLET_FAULTS,
        "knownIssues": [
            {"id": "spen-nib-wear", "title": "Ngòi bút cao su S-Pen bị mòn tòe ngòi", "severity": "low", "confidence": "high", "symptoms": "Vẽ viết ma sát kém nhạy.", "solution": "Thay ngòi bút S-Pen mới."}
        ]
    },
    {
        "id": "samsung-galaxy-tab-s9-fe",
        "name": "Galaxy Tab S9 FE / FE+",
        "brand": "samsung",
        "deviceType": "tablet",
        "tier": "T2",
        "releaseYear": 2023,
        "specs": {"screen": "10.9 / 12.4 inch IPS LCD 90Hz, kháng nước IP68", "chip": "Exynos 1380", "battery": "8000 / 10090 mAh", "charging": "45W"},
        "supportedFaults": PENCIL_TABLET_FAULTS,
        "knownIssues": [
            {"id": "lcd-shatter-s9fe", "title": "Màn hình nứt vỡ LCD", "severity": "high", "confidence": "high", "symptoms": "Kính vỡ sọc kẻ hoặc chảy mực đen.", "solution": "Thay cụm màn hình IPS LCD mới."}
        ]
    },
    {
        "id": "samsung-galaxy-tab-s8-ultra",
        "name": "Galaxy Tab S8 Ultra",
        "brand": "samsung",
        "deviceType": "tablet",
        "tier": "T3",
        "releaseYear": 2022,
        "specs": {"screen": "14.6 inch Super AMOLED 120Hz", "chip": "Snapdragon 8 Gen 1", "battery": "11.200 mAh", "charging": "45W"},
        "supportedFaults": PENCIL_TABLET_FAULTS,
        "knownIssues": [
            {"id": "snap8gen1-hot-s8u", "title": "Thân máy ấm lên khi chơi game nặng", "severity": "medium", "confidence": "high", "symptoms": "Lưng máy nóng vùng camera.", "solution": "Vệ sinh tản nhiệt và thay pin mới."}
        ]
    },
    {
        "id": "samsung-galaxy-tab-s8-plus",
        "name": "Galaxy Tab S8+",
        "brand": "samsung",
        "deviceType": "tablet",
        "tier": "T3",
        "releaseYear": 2022,
        "specs": {"screen": "12.4 inch Super AMOLED 120Hz", "chip": "Snapdragon 8 Gen 1", "battery": "10.090 mAh"},
        "supportedFaults": PENCIL_TABLET_FAULTS,
        "knownIssues": [
            {"id": "battery-aged-s8p", "title": "Pin chai cần bảo dưỡng", "severity": "medium", "confidence": "high", "symptoms": "Sập nguồn khi pin báo dưới 15%.", "solution": "Thay pin mới chất lượng cao."}
        ]
    },
    # Tab A Series Phổ Thông
    {
        "id": "samsung-galaxy-tab-a9-plus",
        "name": "Galaxy Tab A9+",
        "brand": "samsung",
        "deviceType": "tablet",
        "tier": "T1",
        "releaseYear": 2023,
        "specs": {"screen": "11.0 inch TFT LCD 90Hz", "chip": "Snapdragon 695", "battery": "7040 mAh", "charging": "15W"},
        "supportedFaults": DEFAULT_TABLET_FAULTS,
        "knownIssues": [
            {"id": "speaker-crackle-a9p", "title": "Loa 4 loa ngoài rè nhẹ khi mở max volume", "severity": "low", "confidence": "high", "symptoms": "Âm thanh rè lẹt xẹt.", "solution": "Vệ sinh màng loa hoặc thay loa mới."}
        ]
    },
    {
        "id": "samsung-galaxy-tab-a8",
        "name": "Galaxy Tab A8 10.5 inch (2022)",
        "brand": "samsung",
        "deviceType": "tablet",
        "tier": "T1",
        "releaseYear": 2022,
        "specs": {"screen": "10.5 inch TFT LCD WUXGA", "chip": "Unisoc Tiger T618", "battery": "7040 mAh", "charging": "15W"},
        "supportedFaults": DEFAULT_TABLET_FAULTS,
        "knownIssues": [
            {"id": "charging-loose-a8", "title": "Cổng sạc Type-C cắm lỏng chập chờn", "severity": "medium", "confidence": "high", "symptoms": "Sạc ngắt quãng liên tục.", "solution": "Thay bo chân sạc phụ."}
        ]
    }
]

save("samsung", samsung_tablets)

# --- 3. XIAOMI (5 models) ---
xiaomi_tablets = [
    {
        "id": "xiaomi-pad-6-pro",
        "name": "Xiaomi Pad 6 Pro",
        "brand": "xiaomi",
        "deviceType": "tablet",
        "tier": "T2",
        "releaseYear": 2023,
        "specs": {"screen": "11.0 inch IPS LCD 2.8K 144Hz 10-bit", "chip": "Snapdragon 8+ Gen 1", "battery": "8600 mAh", "charging": "67W"},
        "supportedFaults": PENCIL_TABLET_FAULTS,
        "knownIssues": [
            {"id": "smart-pen-charging-drop", "title": "Mất nhận sạc nam châm bút cảm ứng Xiaomi Smart Pen 2", "severity": "medium", "confidence": "high", "symptoms": "Bút không kết nối bluetooth.", "solution": "Thay cuộn cảm biến từ tính hông máy."}
        ]
    },
    {
        "id": "xiaomi-pad-6",
        "name": "Xiaomi Pad 6",
        "brand": "xiaomi",
        "deviceType": "tablet",
        "tier": "T2",
        "releaseYear": 2023,
        "specs": {"screen": "11.0 inch IPS LCD 2.8K 144Hz", "chip": "Snapdragon 870", "battery": "8840 mAh", "charging": "33W"},
        "supportedFaults": PENCIL_TABLET_FAULTS,
        "knownIssues": [
            {"id": "battery-aged-pad6", "title": "Pin sụt nhanh sau thời gian dài cày phim học tập", "severity": "medium", "confidence": "high", "symptoms": "Dung lượng pin giảm.", "solution": "Thay pin mới bảo hành 6 tháng."}
        ]
    },
    {
        "id": "xiaomi-pad-5",
        "name": "Xiaomi Pad 5",
        "brand": "xiaomi",
        "deviceType": "tablet",
        "tier": "T2",
        "releaseYear": 2021,
        "specs": {"screen": "11.0 inch IPS LCD 2.5K 120Hz", "chip": "Snapdragon 860", "battery": "8720 mAh", "charging": "33W"},
        "supportedFaults": PENCIL_TABLET_FAULTS,
        "knownIssues": [
            {"id": "touch-jitter-pad5", "title": "Cảm ứng hơi loạn khi cắm sạc củ sạc lạ", "severity": "medium", "confidence": "high", "symptoms": "Màn hình tự nhảy cảm ứng khi sạc pin.", "solution": "Thay củ sạc chuẩn dòng hoặc thay màn hình mới."}
        ]
    },
    {
        "id": "xiaomi-redmi-pad-pro",
        "name": "Redmi Pad Pro",
        "brand": "xiaomi",
        "deviceType": "tablet",
        "tier": "T1",
        "releaseYear": 2024,
        "specs": {"screen": "12.1 inch IPS LCD 2.5K 120Hz", "chip": "Snapdragon 7s Gen 2", "battery": "10.000 mAh", "charging": "33W"},
        "supportedFaults": DEFAULT_TABLET_FAULTS,
        "knownIssues": [
            {"id": "glass-crack-rpad-pro", "title": "Mặt kính màn hình 12.1 inch nứt vỡ", "severity": "medium", "confidence": "high", "symptoms": "Kính rạn ngoài, cảm ứng còn dùng tốt.", "solution": "Ép mặt kính màn hình mới."}
        ]
    },
    {
        "id": "xiaomi-redmi-pad-se",
        "name": "Redmi Pad SE",
        "brand": "xiaomi",
        "deviceType": "tablet",
        "tier": "T1",
        "releaseYear": 2023,
        "specs": {"screen": "11.0 inch IPS LCD 90Hz", "chip": "Snapdragon 680", "battery": "8000 mAh", "charging": "10W / 18W"},
        "supportedFaults": DEFAULT_TABLET_FAULTS,
        "knownIssues": [
            {"id": "charging-slow-se", "title": "Thời gian sạc pin lâu", "severity": "low", "confidence": "high", "symptoms": "Sạc đầy mất hơn 3 tiếng.", "solution": "Kiểm tra củ cáp sạc nhanh tương thích."}
        ]
    }
]

save("xiaomi", xiaomi_tablets)

# --- 4. OTHERS (3 models) ---
others_tablets = [
    {
        "id": "lenovo-tab-p11-pro-gen-2",
        "name": "Lenovo Tab P11 Pro Gen 2",
        "brand": "others",
        "deviceType": "tablet",
        "tier": "T2",
        "releaseYear": 2022,
        "specs": {"screen": "11.2 inch 2.5K OLED 120Hz HDR10+", "chip": "MediaTek Kompanio 1300T", "battery": "8200 mAh", "charging": "30W"},
        "supportedFaults": PENCIL_TABLET_FAULTS,
        "knownIssues": [
            {"id": "pen-precision-p11", "title": "Bút Lenovo Precision Pen 3 chập chờn kết nối", "severity": "low", "confidence": "high", "symptoms": "Viết vẽ bị đứt nét.", "solution": "Thay ngòi bút hoặc cân chỉnh Bluetooth."}
        ]
    },
    {
        "id": "microsoft-surface-pro-8",
        "name": "Microsoft Surface Pro 8",
        "brand": "others",
        "deviceType": "tablet",
        "tier": "T3",
        "releaseYear": 2021,
        "specs": {"screen": "13.0 inch PixelSense Flow 120Hz Cảm ứng", "chip": "Core i5-1135G7 / i7-1185G7", "battery": "51.5 Wh", "ports": "2x Thunderbolt 4, Surface Connect"},
        "supportedFaults": PENCIL_TABLET_FAULTS,
        "knownIssues": [
            {"id": "surface-battery-swell-p8", "title": "Pin phồng đội hở màn hình PixelSense", "severity": "critical", "confidence": "high", "symptoms": "Viền màn hình bị kênh nứt keo.", "solution": "Bóc tách màn hình bằng bàn nhiệt thay pin Surface chính hãng."}
        ]
    },
    {
        "id": "huawei-matepad-11-5-papermatte",
        "name": "Huawei MatePad 11.5 PaperMatte",
        "brand": "others",
        "deviceType": "tablet",
        "tier": "T2",
        "releaseYear": 2023,
        "specs": {"screen": "11.5 inch 2.2K 120Hz màn nhám chống chói như giấy", "chip": "Snapdragon 7 Gen 1", "battery": "7700 mAh", "charging": "22.5W"},
        "supportedFaults": PENCIL_TABLET_FAULTS,
        "knownIssues": [
            {"id": "papermatte-coating-wear", "title": "Mòn lớp nhám PaperMatte tại vùng hay viết bút nhiều", "severity": "low", "confidence": "medium", "symptoms": "Bề mặt nhám bị bóng nhẹ sau thời gian dài ghi chú.", "solution": "Dán film Paperlike phục hồi cảm giác viết như giấy."}
        ]
    }
]

save("others", others_tablets)
print("=== HOÀN TẤT TẠO TOÀN BỘ 4 FILE CATALOG MÁY TÍNH BẢNG ===")
