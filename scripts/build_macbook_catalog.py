#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
FixNear RepairAtlas — MacBook Catalog Builder (~17 models)
Xây dựng danh mục MacBook tại data/catalog/macbook/apple.json
"""

import sys
import json
from pathlib import Path

# Force UTF-8 output on Windows
if sys.platform == "win32":
    sys.stdout.reconfigure(encoding="utf-8")

ROOT_DIR = Path(__file__).resolve().parent.parent
MAC_DIR = ROOT_DIR / "data" / "catalog" / "macbook"
MAC_DIR.mkdir(parents=True, exist_ok=True)

# Lỗi chuẩn cho Apple Silicon Mac (M1/M2/M3 - KHÔNG CÓ ram-upgrade / ssd-upgrade)
APPLE_SILICON_MAC_FAULTS = [
    "screen", "battery", "keyboard", "trackpad", "charging-port",
    "hinge-body", "mainboard", "speaker", "mic", "camera",
    "thermal", "software", "water-damage", "data-recovery", "diagnostic"
]

# Lỗi cho MacBook Intel đời cũ 2015 (có ssd-upgrade)
MAC_INTEL_2015_FAULTS = [
    "screen", "battery", "keyboard", "trackpad", "charging-port",
    "hinge-body", "mainboard", "speaker", "mic", "camera",
    "thermal", "ssd-upgrade", "software", "water-damage", "data-recovery", "diagnostic"
]

# Lỗi cho MacBook Pro Intel Touch Bar (có flexgate)
MAC_INTEL_TOUCHBAR_FAULTS = [
    "screen", "flexgate", "battery", "keyboard", "trackpad", "charging-port",
    "hinge-body", "mainboard", "speaker", "mic", "camera",
    "thermal", "software", "water-damage", "data-recovery", "diagnostic"
]

macbook_models = [
    # --- 1. MacBook Pro M3 (MAC4) ---
    {
        "id": "apple-macbook-pro-16-m3-pro-max-2023",
        "name": "MacBook Pro 16 inch (M3 Pro / M3 Max 2023)",
        "brand": "apple",
        "deviceType": "macbook",
        "tier": "MAC4",
        "releaseYear": 2023,
        "specs": {"chip": "Apple M3 Pro / M3 Max (3nm)", "screen": "16.2 inch Liquid Retina XDR Mini-LED 120Hz ProMotion (3456x2234)", "ram": "18GB/36GB/48GB/128GB Unified Memory Soldered", "battery": "100 Wh", "ports": "MagSafe 3, 3x Thunderbolt 4, HDMI 2.1, SDXC"},
        "supportedFaults": APPLE_SILICON_MAC_FAULTS,
        "knownIssues": [
            {"id": "space-black-anodize-wear", "title": "Bong tróc mòn lớp mạ anode màu Đen Không Gian (Space Black) tại góc tì tay", "severity": "low", "confidence": "high", "symptoms": "Màu đen quanh mép chiếu nghỉ tay bị mòn lộ ánh kim nhôm sáng sau thời gian dùng nhiều mồ hôi.", "solution": "Dán skin bảo vệ 3M chống trầy hoặc thay mặt tì tay Topcase chính hãng Apple."},
            {"id": "mini-led-crack-xdr", "title": "Nứt vỡ màn hình Liquid Retina XDR Mini-LED siêu mỏng", "severity": "critical", "confidence": "high", "symptoms": "Màn hình bị sọc dọc hoặc chảy mực đốm đen sau khi cấn đầu bút/dây sạc.", "solution": "Thay nguyên cụm màn hình hiển thị Retina XDR nguyên bản bóc máy để giữ TrueTone và ProMotion 120Hz."}
        ]
    },
    {
        "id": "apple-macbook-pro-14-m3-pro-max-2023",
        "name": "MacBook Pro 14 inch (M3 / M3 Pro / M3 Max 2023)",
        "brand": "apple",
        "deviceType": "macbook",
        "tier": "MAC4",
        "releaseYear": 2023,
        "specs": {"chip": "Apple M3 / M3 Pro / M3 Max", "screen": "14.2 inch Liquid Retina XDR Mini-LED 120Hz (3024x1964)", "ram": "8GB/18GB/36GB Unified Memory Soldered", "battery": "70 Wh / 72.4 Wh", "ports": "MagSafe 3, Thunderbolt 4, HDMI, SDXC"},
        "supportedFaults": APPLE_SILICON_MAC_FAULTS,
        "knownIssues": [
            {"id": "liquid-retina-hinge-creak", "title": "Bản lề màn hình Mini-LED phát ra tiếng kêu cọt kẹt khi gập mở", "severity": "low", "confidence": "high", "symptoms": "Mở máy nghe tiếng lách cách ở hai góc trục bản lề.", "solution": "Cân chỉnh siết lại ốc hãm lực trục xoay bản lề hợp kim."}
        ]
    },
    # --- 2. MacBook Pro M2 (MAC4 & MAC3) ---
    {
        "id": "apple-macbook-pro-16-m2-pro-max-2023",
        "name": "MacBook Pro 16 inch (M2 Pro / M2 Max 2023)",
        "brand": "apple",
        "deviceType": "macbook",
        "tier": "MAC4",
        "releaseYear": 2023,
        "specs": {"chip": "Apple M2 Pro / M2 Max (5nm)", "screen": "16.2 inch Liquid Retina XDR Mini-LED 120Hz", "ram": "16GB/32GB/64GB/96GB Unified Memory", "battery": "100 Wh", "ports": "MagSafe 3, 3x Thunderbolt 4"},
        "supportedFaults": APPLE_SILICON_MAC_FAULTS,
        "knownIssues": [
            {"id": "cd3217-usb-power", "title": "Chập IC quản lý sạc USB-C CD3217 khiến máy không nhận sạc MagSafe", "severity": "critical", "confidence": "high", "symptoms": "Cắm sạc đèn MagSafe nhấp nháy màu cam hoặc không sáng đèn, máy cạn pin không lên nguồn.", "solution": "Đo đạc thay thế IC quản lý nguồn sạc Type-C CD3217 trên bo mạch chủ Logic Board."}
        ]
    },
    {
        "id": "apple-macbook-pro-14-m2-pro-max-2023",
        "name": "MacBook Pro 14 inch (M2 Pro / M2 Max 2023)",
        "brand": "apple",
        "deviceType": "macbook",
        "tier": "MAC4",
        "releaseYear": 2023,
        "specs": {"chip": "Apple M2 Pro / M2 Max", "screen": "14.2 inch Liquid Retina XDR 120Hz", "ram": "16GB/32GB Unified Memory", "battery": "70 Wh", "ports": "MagSafe 3, Thunderbolt 4"},
        "supportedFaults": APPLE_SILICON_MAC_FAULTS,
        "knownIssues": [
            {"id": "speaker-subwoofer-rattle", "title": "Loa trầm âm bass bị rè rung khi xem phim âm lượng lớn", "severity": "medium", "confidence": "high", "symptoms": "Màng loa trầm kép đối kháng bị rè lẹt xẹt ở dải tần số thấp.", "solution": "Thay thế cụm module loa âm trầm chính hãng Apple."}
        ]
    },
    {
        "id": "apple-macbook-pro-13-m2-2022",
        "name": "MacBook Pro 13 inch (M2 Touch Bar 2022)",
        "brand": "apple",
        "deviceType": "macbook",
        "tier": "MAC3",
        "releaseYear": 2022,
        "specs": {"chip": "Apple M2 (8-core CPU, 10-core GPU)", "screen": "13.3 inch Retina IPS 500 nits (2560x1600)", "ram": "8GB/16GB/24GB Soldered", "battery": "58.2 Wh", "special": "Thanh cảm ứng Touch Bar, quạt tản nhiệt đơn"},
        "supportedFaults": APPLE_SILICON_MAC_FAULTS,
        "knownIssues": [
            {"id": "touchbar-flicker-white", "title": "Thanh Touch Bar bị chớp nháy trắng góc bên phải", "severity": "high", "confidence": "high", "symptoms": "Đoạn cuối bên phải thanh cảm ứng Touch Bar chớp sáng liên tục rất khó chịu mắt.", "solution": "Thay cáp dải OLED Touch Bar mới nguyên cụm."},
            {"id": "nand-ssd-half-speed", "title": "Tốc độ đọc ghi SSD bản 256GB bị chậm do chỉ có 1 chip NAND", "severity": "low", "confidence": "high", "symptoms": "Copy file lớn tốc độ giảm một nửa so với bản M1.", "solution": "Tối ưu hóa bộ nhớ tạm hệ điều hành macOS."}
        ]
    },
    # --- 3. MacBook Pro M1 (MAC4 & MAC3) ---
    {
        "id": "apple-macbook-pro-16-m1-pro-max-2021",
        "name": "MacBook Pro 16 inch (M1 Pro / M1 Max 2021)",
        "brand": "apple",
        "deviceType": "macbook",
        "tier": "MAC4",
        "releaseYear": 2021,
        "specs": {"chip": "Apple M1 Pro / M1 Max", "screen": "16.2 inch Liquid Retina XDR Mini-LED 120Hz", "ram": "16GB/32GB/64GB Unified Memory", "battery": "100 Wh", "ports": "MagSafe 3, 3x Thunderbolt 4"},
        "supportedFaults": APPLE_SILICON_MAC_FAULTS,
        "knownIssues": [
            {"id": "notch-menubar-leak", "title": "Icon ứng dụng bị che khuất dưới phần tai thỏ (Notch)", "severity": "low", "confidence": "high", "symptoms": "Thanh Menu Bar bị ẩn các icon tiện ích dưới phần cắt camera.", "solution": "Cài đặt phần mềm điều chỉnh tỉ lệ Bartender hoặc cập nhật macOS."}
        ]
    },
    {
        "id": "apple-macbook-pro-14-m1-pro-max-2021",
        "name": "MacBook Pro 14 inch (M1 Pro / M1 Max 2021)",
        "brand": "apple",
        "deviceType": "macbook",
        "tier": "MAC4",
        "releaseYear": 2021,
        "specs": {"chip": "Apple M1 Pro / M1 Max", "screen": "14.2 inch Liquid Retina XDR Mini-LED 120Hz", "ram": "16GB/32GB Unified Memory", "battery": "70 Wh"},
        "supportedFaults": APPLE_SILICON_MAC_FAULTS,
        "knownIssues": [
            {"id": "screen-delamination-notch", "title": "Bong lớp chống lóa góc tai thỏ màn hình", "severity": "medium", "confidence": "high", "symptoms": "Mép kính quanh camera tai thỏ xuất hiện vết ố loang lổ.", "solution": "Vệ sinh tẩy lớp phủ chống lóa hoặc thay cụm màn hình mới."}
        ]
    },
    {
        "id": "apple-macbook-pro-13-m1-2020",
        "name": "MacBook Pro 13 inch (M1 Touch Bar 2020)",
        "brand": "apple",
        "deviceType": "macbook",
        "tier": "MAC3",
        "releaseYear": 2020,
        "specs": {"chip": "Apple M1 (8-core CPU, 8-core GPU)", "screen": "13.3 inch Retina IPS 500 nits", "ram": "8GB/16GB Unified Memory Soldered", "battery": "58.2 Wh", "ports": "2x Thunderbolt / USB 4"},
        "supportedFaults": APPLE_SILICON_MAC_FAULTS,
        "knownIssues": [
            {"id": "touchbar-dead-esc", "title": "Phím Esc ảo hoặc nút nguồn Touch ID không phản hồi", "severity": "medium", "confidence": "high", "symptoms": "Bấm Esc trên Touch Bar không ăn.", "solution": "Reset SMC/NVRAM hoặc thay thanh Touch Bar linh kiện."}
        ]
    },
    # --- 4. MacBook Pro Intel (MAC2 & MAC1) ---
    {
        "id": "apple-macbook-pro-16-intel-2019",
        "name": "MacBook Pro 16 inch (Intel 2019 A2141)",
        "brand": "apple",
        "deviceType": "macbook",
        "tier": "MAC2",
        "releaseYear": 2019,
        "specs": {"cpu": "Intel Core i7 / Core i9 8 nhân", "gpu": "AMD Radeon Pro 5300M / 5500M 4GB/8GB", "screen": "16.0 inch Retina IPS (3072x1920)", "ram": "16GB/32GB/64GB DDR4 Soldered", "battery": "100 Wh", "ports": "4x Thunderbolt 3"},
        "supportedFaults": [f for f in APPLE_SILICON_MAC_FAULTS if f != "ram-upgrade"],
        "knownIssues": [
            {"id": "intel-i9-overheat-fan", "title": "Nhiệt độ CPU Core i9 quá nóng >98°C và quạt gầm rú liên tục", "severity": "high", "confidence": "high", "symptoms": "Máy cực nóng vùng kim loại phía trên bàn phím, pin sụt nhanh chỉ còn 2 tiếng.", "solution": "Vệ sinh làm sạch bụi tản nhiệt kép và tra keo tản nhiệt gốm MX-6 cao cấp."},
            {"id": "gpu-panics-crash", "title": "Lỗi GPU Panic tự khởi động lại máy khi cắm màn hình ngoài", "severity": "critical", "confidence": "high", "symptoms": "Cắm màn hình 4K qua cổng Type-C máy bị sập nguồn hiện bảng thông báo lỗi GPU Panic.", "solution": "Đo đạc kiểm tra IC nguồn cấp cho card đồ họa rời AMD Radeon hoặc nạp lại VBIOS."}
        ]
    },
    {
        "id": "apple-macbook-pro-13-intel-touchbar-2018-2020",
        "name": "MacBook Pro 13 inch (Intel Touch Bar 2018-2020 A1989/A2251)",
        "brand": "apple",
        "deviceType": "macbook",
        "tier": "MAC2",
        "releaseYear": 2018,
        "specs": {"cpu": "Core i5 / i7 Quad-Core", "screen": "13.3 inch Retina TrueTone", "ram": "8GB/16GB Soldered", "keyboard": "Bàn phím cánh bướm Butterfly (2018-2019) / Magic Keyboard (2020)", "ports": "4x Thunderbolt 3"},
        "supportedFaults": MAC_INTEL_TOUCHBAR_FAULTS,
        "knownIssues": [
            {"id": "flexgate-stage-light", "title": "Lỗi đứt cáp Flexgate đèn sân khấu (Stage Light Effect) ở đáy màn hình", "severity": "critical", "confidence": "high", "symptoms": "Mở màn hình góc dưới 45 độ hiển thị bình thường, mở rộng trên 60 độ thì màn hình tối đen hoặc xuất hiện vệt đèn sân khấu phía dưới.", "solution": "Kỹ thuật viên hàn nối nối dài sợi cáp Flexgate bị đứt ngầm không cần thay nguyên cụm màn hình, tiết kiệm 80% chi phí."},
            {"id": "butterfly-keys-repeat", "title": "Kẹt phím cánh bướm gõ 1 chữ nhảy 2 lần hoặc liệt phím Space", "severity": "high", "confidence": "high", "symptoms": "Bụi lọt vào cơ chế cánh bướm siêu mỏng làm phím bị kẹt cứng hoặc lặp chữ.", "solution": "Vệ sinh xịt khí nén màng cao su hoặc thay nguyên cụm bàn phím Topcase mới."}
        ]
    },
    {
        "id": "apple-macbook-pro-15-intel-retina-2015",
        "name": "MacBook Pro 15 inch Retina (Mid 2015 A1398)",
        "brand": "apple",
        "deviceType": "macbook",
        "tier": "MAC1",
        "releaseYear": 2015,
        "specs": {"cpu": "Intel Core i7 Quad-Core 2.2GHz / 2.5GHz", "gpu": "Intel Iris Pro / AMD Radeon R9 M370X", "screen": "15.4 inch Retina (2880x1800)", "ram": "16GB DDR3L Soldered", "storage": "SSD PCIe tháo rời nâng cấp được", "ports": "MagSafe 2, 2x Thunderbolt 2, 2x USB 3.0, HDMI"},
        "supportedFaults": MAC_INTEL_2015_FAULTS,
        "knownIssues": [
            {"id": "staingate-coating-peel", "title": "Bong tróc lớp chống lóa màn hình Retina (Staingate)", "severity": "high", "confidence": "high", "symptoms": "Màn hình loang lổ các vệt ố màu xám như vệt dầu mỡ không lau sạch được.", "solution": "Tẩy lớp tráng gương chống lóa bằng dung dịch chuyên dụng hoặc dán film bảo vệ mới."},
            {"id": "battery-swelling-recall", "title": "Pin phồng đội cong nắp đáy vỏ nhôm D (Chương trình thu hồi Apple Battery Recall)", "severity": "critical", "confidence": "high", "symptoms": "Nắp đáy máy bị vênh kênh không đứng phẳng trên bàn, bàn rê chuột trackpad bị cứng không click được.", "solution": "Thay pin mới chất lượng cao bảo hành 12 tháng gấp để chống cháy nổ chập vi mạch."}
        ]
    },
    # --- 5. MacBook Air M3 (MAC3) ---
    {
        "id": "apple-macbook-air-15-m3-2024",
        "name": "MacBook Air 15 inch (M3 2024)",
        "brand": "apple",
        "deviceType": "macbook",
        "tier": "MAC3",
        "releaseYear": 2024,
        "specs": {"chip": "Apple M3 (8-core CPU, 10-core GPU)", "screen": "15.3 inch Liquid Retina 500 nits (2880x1864)", "ram": "8GB/16GB/24GB Unified Memory Soldered", "battery": "66.5 Wh", "cooling": "Thiết kế không quạt Fanless hoàn toàn im lặng", "ports": "MagSafe 3, 2x Thunderbolt / USB 4"},
        "supportedFaults": APPLE_SILICON_MAC_FAULTS,
        "knownIssues": [
            {"id": "anodize-fingerprint-m3", "title": "Bám dính dấu vân tay và mồ hôi trên màu Midnight", "severity": "low", "confidence": "high", "symptoms": "Lớp phủ anode màu đêm đen dễ bám vết dầu mỡ tay.", "solution": "Dán skin PPF mờ cao cấp bảo vệ bề mặt nhôm."}
        ]
    },
    {
        "id": "apple-macbook-air-13-m3-2024",
        "name": "MacBook Air 13 inch (M3 2024)",
        "brand": "apple",
        "deviceType": "macbook",
        "tier": "MAC3",
        "releaseYear": 2024,
        "specs": {"chip": "Apple M3", "screen": "13.6 inch Liquid Retina (2560x1664)", "ram": "8GB/16GB/24GB Soldered", "cooling": "Fanless không quạt", "ports": "MagSafe 3, 2x Thunderbolt"},
        "supportedFaults": APPLE_SILICON_MAC_FAULTS,
        "knownIssues": [
            {"id": "thermal-hot-under-load", "title": "Thân máy ấm lên khi xuất video dài do không có quạt tản nhiệt", "severity": "low", "confidence": "high", "symptoms": "Vùng nhôm đáy máy nóng nhẹ khi render clip 4K liên tục.", "solution": "Kê chân đế tản nhiệt nhôm để đối lưu không khí tự nhiên."}
        ]
    },
    # --- 6. MacBook Air M2 (MAC3) ---
    {
        "id": "apple-macbook-air-15-m2-2023",
        "name": "MacBook Air 15 inch (M2 2023)",
        "brand": "apple",
        "deviceType": "macbook",
        "tier": "MAC3",
        "releaseYear": 2023,
        "specs": {"chip": "Apple M2", "screen": "15.3 inch Liquid Retina", "ram": "8GB/16GB/24GB Soldered", "battery": "66.5 Wh", "cooling": "Fanless không quạt"},
        "supportedFaults": APPLE_SILICON_MAC_FAULTS,
        "knownIssues": [
            {"id": "screen-glass-hairline-crack", "title": "Mặt kính màn hình mỏng dễ nứt chân tóc khi cấn hạt bụi", "severity": "high", "confidence": "high", "symptoms": "Cấn miếng che webcam hoặc mảnh vụn nhỏ làm nứt kính góc dưới.", "solution": "Thay cụm màn hình hiển thị Retina nguyên bản bóc máy."}
        ]
    },
    {
        "id": "apple-macbook-air-13-m2-2022",
        "name": "MacBook Air 13 inch (M2 2022)",
        "brand": "apple",
        "deviceType": "macbook",
        "tier": "MAC3",
        "releaseYear": 2022,
        "specs": {"chip": "Apple M2", "screen": "13.6 inch Liquid Retina tai thỏ", "ram": "8GB/16GB Soldered", "ports": "MagSafe 3, 2x USB 4"},
        "supportedFaults": APPLE_SILICON_MAC_FAULTS,
        "knownIssues": [
            {"id": "midnight-scratches-port", "title": "Tróc sơn mép cổng sạc MagSafe màu Midnight", "severity": "low", "confidence": "high", "symptoms": "Đầu sạc hít nam châm cọ xát làm xước lộ màu bạc nhôm nguyên thủy.", "solution": "Dán decal khoét lỗ bảo vệ cổng sạc."}
        ]
    },
    # --- 7. MacBook Air M1 (MAC3) ---
    {
        "id": "apple-macbook-air-13-m1-2020",
        "name": "MacBook Air 13 inch (M1 2020)",
        "brand": "apple",
        "deviceType": "macbook",
        "tier": "MAC3",
        "releaseYear": 2020,
        "specs": {"chip": "Apple M1 (8-core CPU, 7/8-core GPU)", "screen": "13.3 inch Retina IPS 400 nits (2560x1600)", "ram": "8GB/16GB Unified Memory Soldered", "battery": "49.9 Wh", "cooling": "Fanless không quạt, bàn phím Magic Keyboard cắt kéo", "ports": "2x Thunderbolt / USB 4"},
        "supportedFaults": APPLE_SILICON_MAC_FAULTS,
        "knownIssues": [
            {"id": "spontaneous-screen-crack", "title": "Nứt màn hình Retina mỏng do cấn hạt cát hoặc dán miếng che camera", "severity": "critical", "confidence": "high", "symptoms": "Mở máy thấy một vệt đen sọc ngang hoặc đốm mực lan rộng sau khi gập nắp máy.", "solution": "Thay nguyên cụm màn hình Retina zin bóc máy (khuyên không dán bảo vệ bàn phím hay che webcam)."},
            {"id": "battery-cycle-count-high", "title": "Pin báo bảo trì Service Recommended sau 3-4 năm học tập làm việc", "severity": "medium", "confidence": "high", "symptoms": "Thời lượng pin sụt giảm chỉ còn 4-5 tiếng, máy báo khuyến nghị bảo trì.", "solution": "Thay pin MacBook Air M1 chính hãng dung lượng chuẩn bảo hành 12 tháng."}
        ]
    },
    # --- 8. MacBook Air Intel Retina (MAC2) ---
    {
        "id": "apple-macbook-air-13-intel-retina-2018-2020",
        "name": "MacBook Air 13 inch (Intel Retina 2018-2020 A1932/A2179)",
        "brand": "apple",
        "deviceType": "macbook",
        "tier": "MAC2",
        "releaseYear": 2018,
        "specs": {"cpu": "Core i3 / i5 Dual-Core / Quad-Core", "screen": "13.3 inch Retina TrueTone", "ram": "8GB/16GB LPDDR3 Soldered", "battery": "49.9 Wh", "ports": "2x Thunderbolt 3"},
        "supportedFaults": APPLE_SILICON_MAC_FAULTS,
        "knownIssues": [
            {"id": "butterfly-air-keys", "title": "Bàn phím cánh bướm kẹt nút bụi bẩn (model 2018-2019)", "severity": "high", "confidence": "high", "symptoms": "Gõ phím Space không nhảy chữ hoặc nhảy 2 dấu cách.", "solution": "Vệ sinh bàn phím hoặc thay cụm bàn phím Magic Keyboard."},
            {"id": "fan-loud-intel-air", "title": "Quạt đơn hú to nhiệt độ CPU lên 90°C chỉ khi mở vài tab Chrome", "severity": "medium", "confidence": "high", "symptoms": "Máy nóng ran và phát ra tiếng quạt gió rít liên tục.", "solution": "Vệ sinh quạt tản nhiệt, bôi keo tản nhiệt gốm và tắt các tác vụ chạy ngầm."}
        ]
    }
]

with open(MAC_DIR / "apple.json", "w", encoding="utf-8") as f:
    json.dump(macbook_models, f, ensure_ascii=False, indent=2)

print(f"  ✅ Đã lưu data/catalog/macbook/apple.json: {len(macbook_models)} models")
