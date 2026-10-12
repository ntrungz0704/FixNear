#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
FixNear RepairAtlas — Windows Laptop Catalog Builder (~85 models)
Xây dựng danh mục Laptop Windows tại data/catalog/win_laptop/
Bao gồm Dell, Asus, HP, Lenovo, Acer, MSI, Others
"""

import sys
import json
from pathlib import Path

# Force UTF-8 output on Windows
if sys.platform == "win32":
    sys.stdout.reconfigure(encoding="utf-8")

ROOT_DIR = Path(__file__).resolve().parent.parent
LAPTOP_DIR = ROOT_DIR / "data" / "catalog" / "win_laptop"
LAPTOP_DIR.mkdir(parents=True, exist_ok=True)

# Lỗi chuẩn cho Laptop có thể nâng cấp RAM + SSD
DEFAULT_LAPTOP_FAULTS = [
    "screen", "battery", "keyboard", "trackpad", "charging-port",
    "hinge-body", "mainboard", "speaker", "mic", "camera",
    "thermal", "ssd-upgrade", "ram-upgrade", "software",
    "water-damage", "data-recovery", "diagnostic"
]

# Lỗi cho Gaming Laptop (thêm vga-gpu)
GAMING_LAPTOP_FAULTS = DEFAULT_LAPTOP_FAULTS + ["vga-gpu"]

# Lỗi cho Ultrabook RAM hàn bo mạch (KHÔNG có ram-upgrade)
SOLDERED_RAM_FAULTS = [
    "screen", "battery", "keyboard", "trackpad", "charging-port",
    "hinge-body", "mainboard", "speaker", "mic", "camera",
    "thermal", "ssd-upgrade", "software",
    "water-damage", "data-recovery", "diagnostic"
]

def save(brand, data):
    p = LAPTOP_DIR / f"{brand}.json"
    with open(p, "w", encoding="utf-8") as f:
        json.dump(data, f, ensure_ascii=False, indent=2)
    print(f"  ✅ Đã lưu {brand}.json: {len(data)} models")

# --- 1. DELL (19 models) ---
dell_models = [
    # XPS Flagship
    {
        "id": "dell-xps-13-plus-9320",
        "name": "Dell XPS 13 Plus 9320",
        "brand": "dell",
        "deviceType": "win_laptop",
        "tier": "L4",
        "releaseYear": 2022,
        "specs": {"cpu": "Intel Core i7-1260P / i7-1360P", "screen": "13.4 inch 3.5K OLED Touch / FHD+ 500 nits", "ram": "16GB/32GB LPDDR5 Soldered (Hàn bo mạch)", "storage": "512GB/1TB NVMe SSD", "chassis": "Nhôm CNC nguyên khối, trackpad tàng hình viền kính"},
        "supportedFaults": SOLDERED_RAM_FAULTS,
        "knownIssues": [
            {"id": "haptic-trackpad-fail", "title": "Bàn rê chuột cảm ứng xúc giác (Invisible Haptic Trackpad) bị đơ hoặc liệt rung", "severity": "high", "confidence": "high", "symptoms": "Bàn rê kính tàng hình không rung phản hồi khi bấm, con trỏ chuột trôi loạn.", "solution": "Thay thế cụm chiếu nghỉ tay kính tích hợp motor phản hồi haptic bên dưới."},
            {"id": "touch-function-row-stuck", "title": "Hàng phím chức năng cảm ứng Touch Function Row tự nhảy hoặc mất đèn", "severity": "medium", "confidence": "high", "symptoms": "Hàng phím cảm ứng phía trên bàn phím không chuyển đổi được giữa phím F1-F12 và Media.", "solution": "Thay cáp dải LED cảm ứng điện dung Touch Bar của Dell XPS."}
        ]
    },
    {
        "id": "dell-xps-13-9315",
        "name": "Dell XPS 13 9315",
        "brand": "dell",
        "deviceType": "win_laptop",
        "tier": "L4",
        "releaseYear": 2022,
        "specs": {"cpu": "Intel Core i5-1230U / i7-1250U", "screen": "13.4 inch FHD+ 500 nits", "ram": "8GB/16GB LPDDR5 Soldered", "storage": "512GB NVMe SSD", "chassis": "Nhôm nguyên khối"},
        "supportedFaults": SOLDERED_RAM_FAULTS,
        "knownIssues": [
            {"id": "type-c-thunderbolt-loose", "title": "Cổng Thunderbolt 4 lỏng chân sạc sau thời gian sử dụng", "severity": "medium", "confidence": "high", "symptoms": "Cắm dây sạc USB-C lỏng lẻo chập chờn, máy chỉ nhận sạc khi ấn mạnh dây.", "solution": "Hàn thay thế chân cắm Type-C Thunderbolt chịu nhiệt."}
        ]
    },
    {
        "id": "dell-xps-15-9530",
        "name": "Dell XPS 15 9530",
        "brand": "dell",
        "deviceType": "win_laptop",
        "tier": "L4",
        "releaseYear": 2023,
        "specs": {"cpu": "Intel Core i7-13700H / i9-13900H", "gpu": "RTX 4050 / RTX 4060", "screen": "15.6 inch 3.5K OLED Touch / FHD+", "ram": "16GB/32GB DDR5 rời nâng cấp được", "storage": "1TB NVMe SSD", "chassis": "Vỏ nhôm CNC, mặt tì tay Carbon Fiber"},
        "supportedFaults": GAMING_LAPTOP_FAULTS,
        "knownIssues": [
            {"id": "thermal-throttling-xps15", "title": "Nhiệt độ CPU/GPU quá tải >95°C và hạ xung nhịp (Thermal Throttling)", "severity": "high", "confidence": "high", "symptoms": "Máy quạt hú to, nhiệt độ đáy máy nóng ran, render video bị tụt fps sụt áp nguồn.", "solution": "Vệ sinh tản nhiệt buồng hơi kép, tra keo tản nhiệt gốm MX-6 hoặc pad kim loại lỏng chuyên dụng."},
            {"id": "trackpad-wobble-click", "title": "Trackpad bị lún hoặc kênh lỏng lẻo khi ấn click", "severity": "medium", "confidence": "high", "symptoms": "Bàn rê chuột bị cập kênh một bên góc do ốc căn chỉnh đáy bị rơ.", "solution": "Căn chỉnh lại lò xo và đệm cao su giảm chấn bàn rê chuột."}
        ]
    },
    {
        "id": "dell-xps-17-9730",
        "name": "Dell XPS 17 9730",
        "brand": "dell",
        "deviceType": "win_laptop",
        "tier": "L4",
        "releaseYear": 2023,
        "specs": {"cpu": "Core i7-13700H / i9-13900H", "gpu": "RTX 4070 / RTX 4080", "screen": "17.0 inch 4K+ Touch 500 nits", "ram": "32GB/64GB DDR5", "chassis": "Nhôm CNC Carbon"},
        "supportedFaults": GAMING_LAPTOP_FAULTS,
        "knownIssues": [
            {"id": "battery-drain-plugged", "title": "Pin bị sụt nhẹ dù đang cắm sạc khi card đồ họa ăn tải nặng 130W", "severity": "medium", "confidence": "high", "symptoms": "Chơi game hoặc render nặng pin tụt từ 100% xuống 90% dù có cắm sạc.", "solution": "Kiểm tra củ sạc Type-C 130W và mạch cấp nguồn phụ trên mainboard."}
        ]
    },
    {
        "id": "dell-xps-16-9640",
        "name": "Dell XPS 16 9640",
        "brand": "dell",
        "deviceType": "win_laptop",
        "tier": "L4",
        "releaseYear": 2024,
        "specs": {"cpu": "Intel Core Ultra 7 155H / Ultra 9 185H", "gpu": "RTX 4060 / RTX 4070", "screen": "16.3 inch 4K+ OLED 120Hz", "ram": "32GB LPDDR5x Soldered", "chassis": "Nhôm nguyên khối phím tàng hình"},
        "supportedFaults": [f for f in GAMING_LAPTOP_FAULTS if f != "ram-upgrade"],
        "knownIssues": [
            {"id": "touchbar-xps16-led", "title": "Đèn hàng phím LED cảm ứng nhấp nháy không đều", "severity": "low", "confidence": "medium", "symptoms": "Hàng phím cảm ứng trên chớp nháy.", "solution": "Cập nhật firmware BIOS hoặc thay cáp LED chức năng."}
        ]
    },
    # Inspiron Phổ Thông & Trung Cấp (L2 / L1)
    {
        "id": "dell-inspiron-15-3520",
        "name": "Dell Inspiron 15 3520",
        "brand": "dell",
        "deviceType": "win_laptop",
        "tier": "L1",
        "releaseYear": 2023,
        "specs": {"cpu": "Core i3-1215U / i5-1235U", "screen": "15.6 inch FHD 120Hz viền nhựa", "ram": "8GB DDR4 nâng cấp được", "storage": "256GB/512GB SSD", "chassis": "Vỏ nhựa Polycarbonate"},
        "supportedFaults": DEFAULT_LAPTOP_FAULTS,
        "knownIssues": [
            {"id": "plastic-hinge-break", "title": "Bung gãy chân ốc bản lề vỏ nhựa bên trái (Bệnh kinh điển dòng Inspiron)", "severity": "critical", "confidence": "high", "symptoms": "Gập mở màn hình thấy vỏ góc dưới bung hở, kêu cọt kẹt, cấn vào mép màn hình có nguy cơ làm bể màn.", "solution": "Hàn cấy lại chân ốc đồng kim loại, gia cố keo AB chịu lực chuyên dụng hoặc thay mặt nắp C/D mới."},
            {"id": "keyboard-space-miss", "title": "Phím Spacebar và phím chữ bị chập nhảy 2 lần", "severity": "medium", "confidence": "high", "symptoms": "Gõ văn bản phím nhảy chữ liên tục hoặc kẹt phím.", "solution": "Thay cụm bàn phím mới nguyên bảng."}
        ]
    },
    {
        "id": "dell-inspiron-14-5430",
        "name": "Dell Inspiron 14 5430",
        "brand": "dell",
        "deviceType": "win_laptop",
        "tier": "L2",
        "releaseYear": 2023,
        "specs": {"cpu": "Intel Core i5-1335U / i7-1360P", "screen": "14.0 inch FHD+ 16:10 WVA", "ram": "16GB LPDDR5 Soldered", "storage": "512GB NVMe SSD", "chassis": "Vỏ nhôm nắp A mặt C kim loại"},
        "supportedFaults": SOLDERED_RAM_FAULTS,
        "knownIssues": [
            {"id": "fan-whine-high-pitch", "title": "Quạt tản nhiệt phát ra tiếng rít the thé khi chạy tốc độ cao", "severity": "medium", "confidence": "high", "symptoms": "Quạt kêu e e khó chịu trong môi trường yên tĩnh.", "solution": "Vệ sinh bôi trơn trục quạt hoặc thay quạt tản nhiệt chính hãng."}
        ]
    },
    {
        "id": "dell-inspiron-16-5630",
        "name": "Dell Inspiron 16 5630",
        "brand": "dell",
        "deviceType": "win_laptop",
        "tier": "L2",
        "releaseYear": 2023,
        "specs": {"cpu": "Core i5-1340P / i7-1360P", "screen": "16.0 inch FHD+ / 2.5K 16:10", "ram": "16GB LPDDR5 Soldered", "storage": "512GB/1TB SSD", "chassis": "Vỏ nhôm bạc"},
        "supportedFaults": SOLDERED_RAM_FAULTS,
        "knownIssues": [
            {"id": "dc-jack-loose", "title": "Chân cắm sạc tròn kim loại lỏng lẻo sau 1 năm", "severity": "medium", "confidence": "high", "symptoms": "Cắm củ sạc xoay qua xoay lại mới nhận nguồn.", "solution": "Thay dây cắm Jack nguồn DC-in rời."}
        ]
    },
    {
        "id": "dell-inspiron-14-7430-2in1",
        "name": "Dell Inspiron 14 7430 2-in-1",
        "brand": "dell",
        "deviceType": "win_laptop",
        "tier": "L2",
        "releaseYear": 2023,
        "specs": {"cpu": "Core i5-1335U / i7-1355U", "screen": "14.0 inch FHD+ Cảm ứng xoay gập 360 độ", "ram": "8GB/16GB LPDDR5", "chassis": "Nhôm nguyên khối"},
        "supportedFaults": SOLDERED_RAM_FAULTS,
        "knownIssues": [
            {"id": "touch-screen-bubble", "title": "Bong keo màn hình cảm ứng xoay 360", "severity": "medium", "confidence": "high", "symptoms": "Mép kính cảm ứng hở nhẹ sau khi gập lều nhiều lần.", "solution": "Gia cố keo chuyên dụng và xiết lại trục bản lề xoay 360 độ."}
        ]
    },
    # Latitude Doanh Nghiệp (L3)
    {
        "id": "dell-latitude-7440",
        "name": "Dell Latitude 7440",
        "brand": "dell",
        "deviceType": "win_laptop",
        "tier": "L3",
        "releaseYear": 2023,
        "specs": {"cpu": "Core i5-1345U vPro / i7-1365U", "screen": "14.0 inch FHD+ IPS chống lóa", "ram": "16GB/32GB LPDDR5", "storage": "512GB SSD", "chassis": "Hợp kim Magie hoặc Nhôm siêu nhẹ"},
        "supportedFaults": SOLDERED_RAM_FAULTS,
        "knownIssues": [
            {"id": "type-c-dock-disconnect", "title": "Mất nhận diện cổng Type-C xuất màn hình ra Docking Station", "severity": "medium", "confidence": "high", "symptoms": "Cắm màn hình ngoài qua cổng Type-C chập chờn.", "solution": "Cập nhật firmware Thunderbolt và vệ sinh chân tiếp xúc."}
        ]
    },
    {
        "id": "dell-latitude-5440",
        "name": "Dell Latitude 5440",
        "brand": "dell",
        "deviceType": "win_laptop",
        "tier": "L3",
        "releaseYear": 2023,
        "specs": {"cpu": "Core i5-1335U / i7-1355U", "screen": "14.0 inch FHD IPS", "ram": "2 khe DDR4/DDR5 nâng tối đa 64GB", "storage": "512GB NVMe SSD", "chassis": "Vỏ nhựa sinh học tái chế"},
        "supportedFaults": DEFAULT_LAPTOP_FAULTS,
        "knownIssues": [
            {"id": "battery-swelling-lat5440", "title": "Pin phồng đội nhẹ bàn rê chuột", "severity": "high", "confidence": "high", "symptoms": "Bàn rê khó bấm click chuột trái phải.", "solution": "Thay pin Dell chính hãng bảo hành 12 tháng."}
        ]
    },
    {
        "id": "dell-latitude-3440",
        "name": "Dell Latitude 3440",
        "brand": "dell",
        "deviceType": "win_laptop",
        "tier": "L2",
        "releaseYear": 2023,
        "specs": {"cpu": "Core i3-1315U / i5-1335U", "screen": "14.0 inch FHD IPS", "ram": "DDR4 nâng cấp được", "storage": "256GB/512GB SSD", "chassis": "Vỏ nhựa sần"},
        "supportedFaults": DEFAULT_LAPTOP_FAULTS,
        "knownIssues": [
            {"id": "hinge-stiff-lat3440", "title": "Bản lề hơi cứng sau thời gian dùng", "severity": "low", "confidence": "high", "symptoms": "Mở máy bằng 1 tay khó khăn.", "solution": "Xả bớt ốc siết bản lề và bôi trơn trục xoay."}
        ]
    },
    {
        "id": "dell-latitude-7330-ultralight",
        "name": "Dell Latitude 7330 Ultralight",
        "brand": "dell",
        "deviceType": "win_laptop",
        "tier": "L3",
        "releaseYear": 2022,
        "specs": {"cpu": "Core i7-1265U", "screen": "13.3 inch FHD 400 nits", "ram": "16GB Soldered", "weight": "0.967 kg", "chassis": "Hợp kim Magie siêu bền"},
        "supportedFaults": SOLDERED_RAM_FAULTS,
        "knownIssues": [
            {"id": "magnesium-paint-wear", "title": "Tróc sơn mép cạnh sườn hợp kim Magie", "severity": "low", "confidence": "high", "symptoms": "Mép viền cọ xát balo bị sờn sơn.", "solution": "Dán skin bảo vệ hoặc thay vỏ D."}
        ]
    },
    # Vostro Doanh Nghiệp Nhỏ
    {
        "id": "dell-vostro-3520",
        "name": "Dell Vostro 3520",
        "brand": "dell",
        "deviceType": "win_laptop",
        "tier": "L1",
        "releaseYear": 2023,
        "specs": {"cpu": "Core i3-1215U / i5-1235U", "screen": "15.6 inch FHD 120Hz", "ram": "8GB DDR4 nâng cấp được", "storage": "256GB/512GB SSD", "chassis": "Vỏ nhựa đen"},
        "supportedFaults": DEFAULT_LAPTOP_FAULTS,
        "knownIssues": [
            {"id": "hinge-break-vostro", "title": "Gãy chân ốc bản lề vỏ nhựa sau 1.5 năm", "severity": "critical", "confidence": "high", "symptoms": "Bung nắp viền màn hình góc trái.", "solution": "Hàn cấy lại chân ốc đồng và xả nhẹ bản lề."}
        ]
    },
    {
        "id": "dell-vostro-5630",
        "name": "Dell Vostro 5630",
        "brand": "dell",
        "deviceType": "win_laptop",
        "tier": "L2",
        "releaseYear": 2023,
        "specs": {"cpu": "Core i5-1340P / i7-1360P", "screen": "16.0 inch FHD+ 16:10", "ram": "16GB LPDDR5 Soldered", "storage": "512GB SSD", "chassis": "Vỏ nhôm xám Titan"},
        "supportedFaults": SOLDERED_RAM_FAULTS,
        "knownIssues": [
            {"id": "speaker-crackle-vostro", "title": "Loa kép bị rè khi bật Max Volume", "severity": "low", "confidence": "high", "symptoms": "Xem phim loa kêu lẹt xẹt.", "solution": "Vệ sinh màng loa hoặc thay loa mới."}
        ]
    },
    # Dell Gaming G Series (L3)
    {
        "id": "dell-g15-5530",
        "name": "Dell Gaming G15 5530",
        "brand": "dell",
        "deviceType": "win_laptop",
        "tier": "L3",
        "releaseYear": 2023,
        "specs": {"cpu": "Intel Core i5-13450HX / i7-13650HX", "gpu": "RTX 3050 / RTX 4050 / RTX 4060", "screen": "15.6 inch FHD 120Hz / 165Hz", "ram": "16GB DDR5 2 khe rời", "storage": "512GB/1TB SSD", "chassis": "Vỏ nhựa hầm hố nặng 2.81kg"},
        "supportedFaults": GAMING_LAPTOP_FAULTS,
        "knownIssues": [
            {"id": "thermal-hot-cpu-100c", "title": "Nhiệt độ CPU chạm ngưỡng 100°C khi chơi game đồ họa nặng", "severity": "high", "confidence": "high", "symptoms": "CPU ăn tối đa 115W khiến nhiệt độ nhảy vọt 100°C, quạt thổi luồng khí cực nóng ra đuôi máy.", "solution": "Vệ sinh làm sạch bụi lưới tản nhiệt, thay keo tản nhiệt gốm dẫn nhiệt cao Honeywell PTM7950."},
            {"id": "power-jack-burnt", "title": "Chân cắm nguồn sạc kim 240W bị quá nhiệt lỏng chân hàn", "severity": "high", "confidence": "high", "symptoms": "Cắm củ sạc nặng 240W đầu sạc nóng ran, sạc pin nhận chập chờn.", "solution": "Thay dây giắc cắm nguồn DC-in chịu tải cao."}
        ]
    },
    {
        "id": "dell-g16-7630",
        "name": "Dell Gaming G16 7630",
        "brand": "dell",
        "deviceType": "win_laptop",
        "tier": "L3",
        "releaseYear": 2023,
        "specs": {"cpu": "Core i7-13650HX / i9-13900HX", "gpu": "RTX 4060 / RTX 4070", "screen": "16.0 inch QHD+ 16:10 240Hz 100% DCI-P3", "ram": "16GB/32GB DDR5", "chassis": "Vỏ kim loại nắp A, thân nhựa"},
        "supportedFaults": GAMING_LAPTOP_FAULTS,
        "knownIssues": [
            {"id": "screen-bleed-ips", "title": "Hở sáng mép góc màn hình IPS 240Hz", "severity": "medium", "confidence": "high", "symptoms": "Trong phòng tối thấy các quầng sáng vàng ở 4 góc viền màn hình.", "solution": "Nới lỏng ngàm viền màn hình hoặc thay cụm màn hình 2K 240Hz."}
        ]
    },
    # Alienware Siêu Cao Cấp (L4)
    {
        "id": "dell-alienware-m16-r2",
        "name": "Dell Alienware m16 R2",
        "brand": "dell",
        "deviceType": "win_laptop",
        "tier": "L4",
        "releaseYear": 2024,
        "specs": {"cpu": "Intel Core Ultra 7 155H / Ultra 9 185H", "gpu": "RTX 4060 / RTX 4070", "screen": "16.0 inch QHD+ 240Hz", "ram": "16GB/32GB/64GB DDR5 rời", "chassis": "Vỏ hợp kim Anodized Aluminum màu Dark Metallic Moon"},
        "supportedFaults": GAMING_LAPTOP_FAULTS,
        "knownIssues": [
            {"id": "element-31-liquid-metal-pumpout", "title": "Keo tản nhiệt kim loại lỏng Element 31 bị tràn hoặc khô sau 1 năm", "severity": "critical", "confidence": "high", "symptoms": "Nhiệt độ giữa các nhân CPU chênh lệch nhau trên 20°C (Core to Core delta lớn), máy giật fps khi chơi game nặng.", "solution": "Vệ sinh tẩy sạch kim loại lỏng cũ, tra lại màng tản nhiệt ma trận gallium chuyên dụng có cách điện bo mạch."}
        ]
    },
    {
        "id": "dell-alienware-x16-r2",
        "name": "Dell Alienware x16 R2",
        "brand": "dell",
        "deviceType": "win_laptop",
        "tier": "L4",
        "releaseYear": 2024,
        "specs": {"cpu": "Core Ultra 9 185H", "gpu": "RTX 4080 / RTX 4090 175W", "screen": "16.0 inch QHD+ 240Hz, bàn phím cơ CherryMX", "ram": "32GB LPDDR5x Soldered", "chassis": "Khung nhôm siêu mỏng tản nhiệt 4 quạt Quad-Fan"},
        "supportedFaults": [f for f in GAMING_LAPTOP_FAULTS if f != "ram-upgrade"],
        "knownIssues": [
            {"id": "quad-fan-bearing-dust", "title": "Hệ thống 4 quạt tản nhiệt bị nghẹt bụi sau thời gian cày game", "severity": "high", "confidence": "high", "symptoms": "Tiếng quạt hú to bất thường hoặc có 1 quạt không quay báo lỗi SupportAssist.", "solution": "Tháo bảo dưỡng toàn diện cụm 4 quạt và buồng hơi buồng tản Vapor Chamber."}
        ]
    }
]

save("dell", dell_models)
print("Xong Dell Laptop!")
