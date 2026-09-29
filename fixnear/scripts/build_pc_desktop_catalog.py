#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
FixNear RepairAtlas — PC & Desktop Catalog Builder (~21 models/configs)
Xây dựng danh mục Máy Tính Bàn & AIO tại data/catalog/pc_desktop/
Bao gồm Gaming, Workstation, Office, OEM Brand, Apple Desktop
"""

import sys
import json
from pathlib import Path

# Force UTF-8 output on Windows
if sys.platform == "win32":
    sys.stdout.reconfigure(encoding="utf-8")

ROOT_DIR = Path(__file__).resolve().parent.parent
PC_DIR = ROOT_DIR / "data" / "catalog" / "pc_desktop"
PC_DIR.mkdir(parents=True, exist_ok=True)

# Lỗi chuẩn cho PC Thùng (Tower) - Tuyệt đối KHÔNG có battery
PC_TOWER_FAULTS = [
    "mainboard", "psu-repair", "vga-gpu", "thermal", "aio-liquid-cooler",
    "ssd-upgrade", "ram-upgrade", "software", "data-recovery", "diagnostic"
]

# Lỗi cho PC Văn Phòng (không có VGA rời, tản khí)
OFFICE_PC_FAULTS = [
    "mainboard", "psu-repair", "thermal",
    "ssd-upgrade", "ram-upgrade", "software", "data-recovery", "diagnostic"
]

# Lỗi cho Máy liền màn hình All-in-One (AIO) - Có màn hình
AIO_FAULTS = [
    "screen", "mainboard", "psu-repair", "thermal",
    "ssd-upgrade", "ram-upgrade", "software", "data-recovery", "diagnostic"
]

# Lỗi cho Apple Silicon Desktop (iMac M1/M3, Mac Studio, Mac Mini - KHÔNG ram/ssd rời)
APPLE_SILICON_DESKTOP_FAULTS = [
    "screen", "mainboard", "psu-repair", "thermal",
    "software", "data-recovery", "diagnostic"
]

MAC_MINI_STUDIO_FAULTS = [
    "mainboard", "psu-repair", "thermal",
    "software", "data-recovery", "diagnostic"
]

def save(filename, data):
    p = PC_DIR / f"{filename}.json"
    with open(p, "w", encoding="utf-8") as f:
        json.dump(data, f, ensure_ascii=False, indent=2)
    print(f"  ✅ Đã lưu {filename}.json: {len(data)} configs/models")

# --- 1. GAMING (5 configs) ---
gaming_configs = [
    {
        "id": "pc-gaming-ultra-i9-rtx4090",
        "name": "PC Gaming Ultra High-End (i9 14900K / RTX 4090)",
        "brand": "gaming",
        "deviceType": "pc_desktop",
        "tier": "D3",
        "releaseYear": 2024,
        "specs": {"cpu": "Intel Core i9-14900K / AMD Ryzen 9 7950X3D", "gpu": "Nvidia GeForce RTX 4090 24GB GDDR6X", "cooling": "Tản nhiệt nước AIO 360mm ARGB", "psu": "Nguồn 1000W - 1200W chuẩn ATX 3.0 PCIe 5.0", "ram": "64GB DDR5 RGB", "chassis": "Vỏ bể cá 2 mặt kính cường lực"},
        "supportedFaults": PC_TOWER_FAULTS,
        "knownIssues": [
            {"id": "12vhpwr-melting-connector", "title": "Cháy sém đầu cắm nguồn 12VHPWR (16-pin) cấp cho card RTX 4090", "severity": "critical", "confidence": "high", "symptoms": "Đầu cắm nguồn 16 chân bị nóng chảy nhựa, có mùi khét, máy sập nguồn không kích được.", "solution": "Thay thế giắc cắm 12V-2x6 thế hệ mới, hàn lại chân tiếp xúc nguồn trên bo mạch VGA và thay cáp nguồn modul chính hãng."},
            {"id": "aio-pump-failure-100c", "title": "Hỏng bơm tản nhiệt nước AIO khiến CPU nhảy vọt 100°C tắt máy ngay", "severity": "critical", "confidence": "high", "symptoms": "Vừa bật máy lên vài phút CPU báo 100°C và tự tắt ngắt điện, sờ 2 ống tản 1 ống rất nóng 1 ống nguội ngắt.", "solution": "Thay bơm tản AIO mới hoặc phục hồi cánh quạt bơm, châm lại dung dịch làm mát coolant chống đóng cặn."}
        ]
    },
    {
        "id": "pc-gaming-high-i7-rtx4070ti",
        "name": "PC Gaming High-End (i7 14700K / RTX 4070 Ti)",
        "brand": "gaming",
        "deviceType": "pc_desktop",
        "tier": "D3",
        "releaseYear": 2023,
        "specs": {"cpu": "Core i7-13700K / i7-14700K / Ryzen 7 7800X3D", "gpu": "RTX 4070 Ti / 4070 Ti Super 16GB", "cooling": "Tản nhiệt nước AIO 240/360mm", "psu": "850W Gold", "ram": "32GB DDR5"},
        "supportedFaults": PC_TOWER_FAULTS,
        "knownIssues": [
            {"id": "vram-thermal-pad-leak", "title": "Miếng tản nhiệt pad VRAM chảy dầu và nhiệt độ bộ nhớ GPU nóng >90°C", "severity": "medium", "confidence": "high", "symptoms": "Card đồ họa rỉ dầu ẩm quanh chip nhớ VRAM, quạt tản nhiệt quay 100% ồn ào.", "solution": "Vệ sinh tẩy sạch dầu silicon và thay thế thermal pad dẫn nhiệt cao cấp Gelid/Thermalright."}
        ]
    },
    {
        "id": "pc-gaming-mid-i5-rtx4060",
        "name": "PC Gaming Quốc Dân (i5 13400F / RTX 4060)",
        "brand": "gaming",
        "deviceType": "pc_desktop",
        "tier": "D2",
        "releaseYear": 2023,
        "specs": {"cpu": "Intel Core i5-13400F / Ryzen 5 7600", "gpu": "GeForce RTX 4060 8GB / RTX 3060 12GB", "cooling": "Tản nhiệt khí tháp đôi 4 ống đồng", "psu": "650W Bronze", "ram": "16GB/32GB DDR4/DDR5"},
        "supportedFaults": [f for f in PC_TOWER_FAULTS if f != "aio-liquid-cooler"],
        "knownIssues": [
            {"id": "ram-slot-dust-boot", "title": "Bụi bám khe RAM khiến máy bật quạt quay nhưng không lên hình (No POST)", "severity": "medium", "confidence": "high", "symptoms": "Bấm nút nguồn đèn LED cây sáng quạt quay tít mù nhưng màn hình đen thui, đèn báo EZ Debug Mainboard dừng ở đèn DRAM vàng.", "solution": "Vệ sinh chân đồng thanh RAM bằng cồn chuyên dụng và xịt sạch bụi khe cắm RAM."}
        ]
    },
    {
        "id": "pc-gaming-esport-i5-gtx1660s",
        "name": "PC Gaming eSports (i5 10400F / GTX 1660 Super)",
        "brand": "gaming",
        "deviceType": "pc_desktop",
        "tier": "D2",
        "releaseYear": 2021,
        "specs": {"cpu": "Core i5-10400F / i5-12400F", "gpu": "GTX 1660 Super 6GB / RTX 2060", "cooling": "Tản khí tháp đơn", "psu": "550W", "ram": "16GB DDR4"},
        "supportedFaults": [f for f in PC_TOWER_FAULTS if f != "aio-liquid-cooler"],
        "knownIssues": [
            {"id": "gpu-fan-rattle-gtx", "title": "Quạt card màn hình kêu rè rè hoặc ngừng quay 1 bên quạt", "severity": "low", "confidence": "high", "symptoms": "Card đồ họa nóng nhanh khi vào game.", "solution": "Thay thế cặp cánh quạt tản nhiệt card VGA mới."}
        ]
    },
    {
        "id": "pc-gaming-entry-i3-gtx1650",
        "name": "PC Gaming Phổ Thông (i3 12100F / GTX 1650)",
        "brand": "gaming",
        "deviceType": "pc_desktop",
        "tier": "D2",
        "releaseYear": 2022,
        "specs": {"cpu": "Core i3-12100F", "gpu": "GTX 1650 4GB / RX 6600", "psu": "500W", "ram": "16GB DDR4"},
        "supportedFaults": [f for f in PC_TOWER_FAULTS if f != "aio-liquid-cooler"],
        "knownIssues": [
            {"id": "thermal-paste-dry-cpu", "title": "Keo tản nhiệt CPU bị khô cứng sau 2 năm", "severity": "low", "confidence": "high", "symptoms": "Quạt CPU kêu to, máy đơ nhẹ.", "solution": "Vệ sinh tra keo tản nhiệt gốm mới."}
        ]
    }
]

save("gaming", gaming_configs)

# --- 2. WORKSTATION (3 configs) ---
workstation_configs = [
    {
        "id": "ws-pro-render-dual-xeon-threadripper",
        "name": "Workstation Render 3D / AI Deep Learning (Threadripper / Dual Xeon)",
        "brand": "workstation",
        "deviceType": "pc_desktop",
        "tier": "D3",
        "releaseYear": 2023,
        "specs": {"cpu": "AMD Threadripper Pro 7985WX 64 nhân / Dual Intel Xeon Platinum", "gpu": "Dual Nvidia RTX A6000 48GB / RTX 4090", "cooling": "Tản nước Custom hoặc AIO công nghiệp", "psu": "1600W - 2000W Titanium", "ram": "128GB - 512GB ECC Registered DDR5"},
        "supportedFaults": PC_TOWER_FAULTS,
        "knownIssues": [
            {"id": "ecc-ram-socket-corrosion", "title": "Chân cắm RAM ECC nhiều kênh (8-Channel) oxy hóa gây dump xanh BSOD", "severity": "high", "confidence": "high", "symptoms": "Máy đang render 3D dở bỗng hiện màn hình xanh thông báo WHEA_UNCORRECTABLE_ERROR.", "solution": "Vệ sinh đo áp socket CPU TR5/LGA4677 và làm sạch 8 kênh khe cắm RAM ECC."},
            {"id": "vrm-overheat-multi-gpu", "title": "Dàn pha nguồn VRM bo mạch chủ quá nhiệt do tải CPU/GPU 1500W liên tục", "severity": "high", "confidence": "high", "symptoms": "Mainboard tự bóp xung nhịp CPU từ 4.5GHz xuống 2.0GHz để tự bảo vệ.", "solution": "Độ thêm quạt tản nhiệt cưỡng bức cho dàn heatsink VRM và thay thermal pad dẫn nhiệt 12.8W/mK."}
        ]
    },
    {
        "id": "ws-architecture-cad-i9-rtxa4000",
        "name": "Workstation Kiến Trúc / CAD / Revit (i9 14900K / RTX 4000 Ada)",
        "brand": "workstation",
        "deviceType": "pc_desktop",
        "tier": "D3",
        "releaseYear": 2024,
        "specs": {"cpu": "Intel Core i9-14900K", "gpu": "Nvidia RTX 4000 SFF Ada Generation 20GB", "ram": "64GB DDR5", "psu": "850W Platinum"},
        "supportedFaults": PC_TOWER_FAULTS,
        "knownIssues": [
            {"id": "bios-microcode-instability", "title": "Lỗi mất ổn định vi mã Microcode CPU Intel thế hệ 13/14 (Lỗi Vmin Shift)", "severity": "critical", "confidence": "high", "symptoms": "Văng phần mềm 3Ds Max, Unreal Engine hoặc lỗi 'Out of video memory' khi compile shader.", "solution": "Cập nhật BIOS bản vá vi mã 0x129/0x12B mới nhất và cân chỉnh giới hạn điện áp an toàn."}
        ]
    },
    {
        "id": "ws-creator-video-i7-rtx4070",
        "name": "Workstation Dựng Phim 4K / Đồ Họa 2D (i7 13700K / RTX 4070)",
        "brand": "workstation",
        "deviceType": "pc_desktop",
        "tier": "D2",
        "releaseYear": 2023,
        "specs": {"cpu": "Core i7-13700K", "gpu": "RTX 4070 12GB", "ram": "32GB/64GB DDR5", "psu": "750W Gold"},
        "supportedFaults": PC_TOWER_FAULTS,
        "knownIssues": [
            {"id": "nvme-ssd-thermal-throttle", "title": "Ổ cứng SSD NVMe PCIe 4.0 quá nóng >75°C tự giảm tốc độ xuất file video", "severity": "medium", "confidence": "high", "symptoms": "Export video Premiere Pro lúc đầu rất nhanh về sau bị khựng đơ máy.", "solution": "Lắp thanh tản nhiệt nhôm khối dày kèm ống đồng cho ổ cứng SSD NVMe."}
        ]
    }
]

save("workstation", workstation_configs)

# --- 3. OFFICE (3 configs) ---
office_configs = [
    {
        "id": "pc-office-standard-i5",
        "name": "PC Văn Phòng Đa Nhiệm (Core i5 12400 / 16GB RAM)",
        "brand": "office",
        "deviceType": "pc_desktop",
        "tier": "D1",
        "releaseYear": 2022,
        "specs": {"cpu": "Intel Core i5-12400 / i5-13400 (Intel UHD Graphics 730)", "ram": "16GB DDR4", "storage": "512GB NVMe SSD", "psu": "450W", "chassis": "Vỏ case văn phòng gọn gàng"},
        "supportedFaults": OFFICE_PC_FAULTS,
        "knownIssues": [
            {"id": "cmos-battery-dead", "title": "Hết pin CMOS CR2032 máy tự nhảy sai giờ và bắt ấn F1 để khởi động", "severity": "low", "confidence": "high", "symptoms": "Mỗi lần rút nguồn cắm lại máy báo CMOS Checksum Error và sai ngày giờ hệ thống.", "solution": "Thay pin cúc áo CMOS CR2032 Maxell/Panasonic chính hãng lấy ngay 5 phút."},
            {"id": "psu-capacitor-leak", "title": "Nguồn văn phòng nổ tụ sụt áp khiến máy tự tắt khi cắm nhiều USB", "severity": "high", "confidence": "high", "symptoms": "Máy chập chờn, cắm máy in hoặc ổ cứng di động vào là tự sập nguồn khởi động lại.", "solution": "Thay thế bộ nguồn máy tính công suất thực đạt chuẩn 80 Plus."}
        ]
    },
    {
        "id": "pc-office-basic-i3",
        "name": "PC Văn Phòng Cơ Bản (Core i3 10100 / 8GB RAM)",
        "brand": "office",
        "deviceType": "pc_desktop",
        "tier": "D1",
        "releaseYear": 2020,
        "specs": {"cpu": "Core i3-10100 / i3-12100", "ram": "8GB DDR4", "storage": "256GB SSD", "psu": "400W"},
        "supportedFaults": OFFICE_PC_FAULTS,
        "knownIssues": [
            {"id": "windows-slow-junk", "title": "Hệ điều hành Windows bị đầy file rác và nhiễm mã độc quảng cáo", "severity": "medium", "confidence": "high", "symptoms": "Mở file Excel nặng bị đơ xoay vòng tròn liên tục.", "solution": "Cài lại Windows 10/11 sạch, cài full Office, font chữ và diệt virus chuyên sâu."}
        ]
    },
    {
        "id": "pc-office-mini-itx",
        "name": "PC Mini ITX Để Bàn Siêu Nhỏ Gọn",
        "brand": "office",
        "deviceType": "pc_desktop",
        "tier": "D1",
        "releaseYear": 2023,
        "specs": {"cpu": "Intel N100 / Core i3-1215U Mini PC", "ram": "8GB/16GB DDR4", "storage": "512GB SSD", "adapter": "Nguồn Adapter 12V/19V ngoài"},
        "supportedFaults": OFFICE_PC_FAULTS,
        "knownIssues": [
            {"id": "mini-fan-dusty", "title": "Quạt tản nhiệt lồng sóc siêu nhỏ bị kẹt bụi", "severity": "medium", "confidence": "high", "symptoms": "Thân máy nóng ran và quạt thổi ồn.", "solution": "Vệ sinh làm sạch bụi quạt lồng sóc."}
        ]
    }
]

save("office", office_configs)

# --- 4. OEM BRAND & AIO (5 models) ---
oem_configs = [
    {
        "id": "dell-optiplex-small-form-factor",
        "name": "Dell OptiPlex Small Form Factor (SFF 7010 / 5090)",
        "brand": "oem_brand",
        "deviceType": "pc_desktop",
        "tier": "D1",
        "releaseYear": 2023,
        "specs": {"cpu": "Core i5-13500 / i7-13700", "form": "Thùng máy nằm siêu nhỏ SFF đồng bộ Dell", "psu": "Nguồn độc quyền Dell 180W / 260W 8-pin", "ram": "16GB DDR4/DDR5"},
        "supportedFaults": OFFICE_PC_FAULTS,
        "knownIssues": [
            {"id": "dell-proprietary-psu-fail", "title": "Chết nguồn đồng bộ độc quyền Dell chân cắm 6-pin/8-pin", "severity": "critical", "confidence": "high", "symptoms": "Đèn led nút nguồn chớp nháy màu cam 2 lần vàng 1 lần trắng báo mã lỗi nguồn.", "solution": "Sửa chữa bo nguồn đồng bộ Dell hoặc thay thế nguồn zin bóc máy chuẩn theo case."}
        ]
    },
    {
        "id": "hp-prodesk-elitedesk-g9",
        "name": "HP ProDesk / EliteDesk 800 G9 Mini Desktop",
        "brand": "oem_brand",
        "deviceType": "pc_desktop",
        "tier": "D1",
        "releaseYear": 2023,
        "specs": {"cpu": "Core i5-13500T / i7-13700T", "form": "Máy mini bé bằng cuốn sổ tay (1 Lít)", "ram": "16GB SODIMM nâng cấp được", "storage": "512GB NVMe SSD"},
        "supportedFaults": OFFICE_PC_FAULTS,
        "knownIssues": [
            {"id": "hp-power-brick-lost", "title": "Lỏng chân cắm sạc nguồn kim nhỏ HP Smart AC", "severity": "medium", "confidence": "high", "symptoms": "Cắm củ sạc máy báo nguồn không tương thích.", "solution": "Hàn lại chân Jack sạc DC-in hoặc thay nguồn adapter 90W HP chuẩn."}
        ]
    },
    {
        "id": "asus-expertcenter-d7-tower",
        "name": "Asus ExpertCenter D7 Tower",
        "brand": "oem_brand",
        "deviceType": "pc_desktop",
        "tier": "D1",
        "releaseYear": 2023,
        "specs": {"cpu": "Core i5-13400 / i7-13700", "ram": "16GB DDR4", "psu": "500W 80 Plus Platinum", "form": "Thùng case đứng doanh nghiệp"},
        "supportedFaults": OFFICE_PC_FAULTS,
        "knownIssues": [
            {"id": "front-audio-jack-broken", "title": "Gãy chân cắm tai nghe 3.5mm mặt trước case", "severity": "low", "confidence": "high", "symptoms": "Cắm tai nghe chỉ nghe được 1 bên tai.", "solution": "Thay cụm bo mạch âm thanh mặt trước (Front I/O Board)."}
        ]
    },
    {
        "id": "dell-inspiron-aio-24-5420",
        "name": "Dell Inspiron All-in-One 24 inch 5420 (Máy liền màn)",
        "brand": "oem_brand",
        "deviceType": "pc_desktop",
        "tier": "D2",
        "releaseYear": 2023,
        "specs": {"cpu": "Core i5-1335U / i7-1355U", "screen": "23.8 inch FHD IPS viền siêu mỏng, loa thanh tích hợp", "ram": "16GB DDR4 nâng cấp được", "storage": "512GB SSD"},
        "supportedFaults": AIO_FAULTS,
        "knownIssues": [
            {"id": "aio-screen-lines", "title": "Sọc màn hình hiển thị hoặc đốm sáng tấm nền All-in-One 24 inch", "severity": "critical", "confidence": "high", "symptoms": "Màn hình xuất hiện đường kẻ chỉ ngang hoặc sọc dọc.", "solution": "Thay tấm nền panel màn hình hiển thị All-in-One 24 inch chuẩn Dell."},
            {"id": "pop-up-camera-stuck", "title": "Camera thò thụt (Pop-up Webcam) bị kẹt không bật lên được", "severity": "low", "confidence": "high", "symptoms": "Nhấn vào đỉnh máy camera không nảy lên.", "solution": "Sửa cơ cấu lẫy lò xo cụm webcam thò thụt."}
        ]
    },
    {
        "id": "hp-envy-aio-34-curved",
        "name": "HP Envy All-in-One 34 inch Màn Cong 5K",
        "brand": "oem_brand",
        "deviceType": "pc_desktop",
        "tier": "D3",
        "releaseYear": 2022,
        "specs": {"cpu": "Core i7-12700 / i9-12900", "gpu": "RTX 3060 / RTX 3080 Max-Q", "screen": "34.0 inch WUHD 5K2K (5120x2160) IPS Cong 21:9", "ram": "32GB/64GB DDR5 rời"},
        "supportedFaults": AIO_FAULTS,
        "knownIssues": [
            {"id": "5k-curved-screen-crack", "title": "Nứt vỡ màn hình cong 34 inch 5K sau va chạm", "severity": "critical", "confidence": "high", "symptoms": "Chảy mực góc màn hình cong siêu rộng.", "solution": "Thay tấm nền panel màn hình cong 34 inch 5K chính hãng HP."}
        ]
    }
]

save("oem_brand", oem_configs)

# --- 5. APPLE DESKTOP (5 models) ---
apple_desktop_models = [
    {
        "id": "apple-imac-24-m3-2023",
        "name": "Apple iMac 24 inch (M3 2023)",
        "brand": "apple_desktop",
        "deviceType": "pc_desktop",
        "tier": "D2",
        "releaseYear": 2023,
        "specs": {"chip": "Apple M3 (8-core CPU, 8/10-core GPU)", "screen": "23.5 inch 4.5K Retina (4480x2520) 500 nits, mỏng 11.5mm", "ram": "8GB/16GB/24GB Unified Memory Soldered", "power": "Bộ nguồn adapter gắn ngoài kèm cổng mạng LAN"},
        "supportedFaults": APPLE_SILICON_DESKTOP_FAULTS,
        "knownIssues": [
            {"id": "imac-m3-screen-line", "title": "Sọc màn hình 4.5K Retina do va đập hoặc cấn góc", "severity": "critical", "confidence": "high", "symptoms": "Màn hình xuất hiện đường kẻ chỉ sọc dọc hoặc chảy mực đốm đen.", "solution": "Thay nguyên cụm màn hình hiển thị iMac 24 inch 4.5K Retina chuẩn Apple bóc máy."},
            {"id": "magnetic-power-cord-loose", "title": "Dây nguồn nam châm tròn phía sau lưng máy cắm lỏng chập chờn", "severity": "medium", "confidence": "high", "symptoms": "Chạm vào dây nguồn máy tự tắt ngắt điện đột ngột.", "solution": "Vệ sinh chân tiếp xúc nam châm hoặc thay dây nguồn dệt iMac."}
        ]
    },
    {
        "id": "apple-imac-24-m1-2021",
        "name": "Apple iMac 24 inch (M1 2021)",
        "brand": "apple_desktop",
        "deviceType": "pc_desktop",
        "tier": "D2",
        "releaseYear": 2021,
        "specs": {"chip": "Apple M1", "screen": "23.5 inch 4.5K Retina", "ram": "8GB/16GB Soldered", "power": "Nguồn Adapter ngoài 143W"},
        "supportedFaults": APPLE_SILICON_DESKTOP_FAULTS,
        "knownIssues": [
            {"id": "imac-stand-crooked", "title": "Chân đế nhôm nguyên khối bị lệch nghiêng nhẹ 2-3mm", "severity": "low", "confidence": "high", "symptoms": "Màn hình một bên cao hơn bên kia khi đo thước thủy.", "solution": "Cân chỉnh siết ốc trục hãm chân đế nhôm bên trong thân máy."}
        ]
    },
    {
        "id": "apple-imac-27-5k-retina-2020",
        "name": "Apple iMac 27 inch 5K Retina (Intel 2020 A2115)",
        "brand": "apple_desktop",
        "deviceType": "pc_desktop",
        "tier": "D3",
        "releaseYear": 2020,
        "specs": {"cpu": "Core i5 / i7 / i9 thế hệ 10", "gpu": "AMD Radeon Pro 5300 / 5500 XT / 5700 XT", "screen": "27.0 inch 5K Retina (5120x2880) TrueTone", "ram": "4 khe cắm RAM SODIMM tháo lắp nhanh nắp sau nâng tối đa 128GB", "power": "Bộ nguồn gắn trong thân máy"},
        "supportedFaults": [
            "screen", "mainboard", "psu-repair", "thermal", "vga-gpu",
            "ssd-upgrade", "ram-upgrade", "software", "data-recovery", "diagnostic"
        ],
        "knownIssues": [
            {"id": "imac-5k-pink-edge", "title": "Viền màn hình 5K Retina bị ố hồng (Pink / Red Hue Edge)", "severity": "high", "confidence": "high", "symptoms": "4 cạnh viền màn hình xuất hiện viền đỏ hoặc hồng nhạt khi hiển thị nền xám trắng.", "solution": "Tẩy ẩm khử từ tấm nền hoặc thay cụm panel màn hình 5K Retina chính hãng LG Display."},
            {"id": "imac-internal-psu-blow", "title": "Nổ tụ bộ nguồn gắn trong (Internal Power Supply) khiến máy không kích nguồn được", "severity": "critical", "confidence": "high", "symptoms": "Cắm dây nguồn không có phản hồi, bấm nút nguồn không quay quạt.", "solution": "Sửa chữa thay thế tụ lọc nguồn hoặc thay bo nguồn công suất cao gắn trong của iMac 27."}
        ]
    },
    {
        "id": "apple-mac-mini-m2-pro-2023",
        "name": "Apple Mac Mini (M2 / M2 Pro 2023)",
        "brand": "apple_desktop",
        "deviceType": "pc_desktop",
        "tier": "D2",
        "releaseYear": 2023,
        "specs": {"chip": "Apple M2 (8C/10C) / M2 Pro (10C/12C)", "ram": "8GB/16GB/32GB Unified Memory Soldered", "storage": "256GB - 8TB", "form": "Hộp nhôm vuông nguyên khối 19.7 x 19.7 cm"},
        "supportedFaults": MAC_MINI_STUDIO_FAULTS,
        "knownIssues": [
            {"id": "mac-mini-wifi-bluetooth-interference", "title": "Nhiễu sóng Wi-Fi và Bluetooth khi cắm nhiều thiết bị USB 3.0 phía sau", "severity": "medium", "confidence": "high", "symptoms": "Chuột Magic Mouse hoặc bàn phím Magic Keyboard bị giật lag ngắt kết nối.", "solution": "Bọc băng keo đồng chống nhiễu EMF cho cổng USB và ăng-ten đáy máy."}
        ]
    },
    {
        "id": "apple-mac-studio-m2-ultra-2023",
        "name": "Apple Mac Studio (M2 Max / M2 Ultra 2023)",
        "brand": "apple_desktop",
        "deviceType": "pc_desktop",
        "tier": "D3",
        "releaseYear": 2023,
        "specs": {"chip": "Apple M2 Max / M2 Ultra 24-core CPU, 76-core GPU", "ram": "64GB/128GB/192GB Unified Memory Soldered", "cooling": "Khối tản nhiệt đồng nguyên chất 2 quạt đôi", "weight": "3.6 kg"},
        "supportedFaults": MAC_MINI_STUDIO_FAULTS,
        "knownIssues": [
            {"id": "mac-studio-fan-whine", "title": "Tiếng rít gió the thé (High-pitched Whine) ở tốc độ quạt mặc định 1300 RPM", "severity": "low", "confidence": "high", "symptoms": "Phát ra âm thanh rít nhỏ trong phòng thu âm cách âm tuyệt đối.", "solution": "Vệ sinh làm sạch bụi lưới tổ ong đáy máy và căn chỉnh tốc độ quạt qua Macs Fan Control."}
        ]
    }
]

save("apple_desktop", apple_desktop_models)
print("=== HOÀN TẤT TẠO TOÀN BỘ 5 FILE CATALOG PC & AIO ===")
