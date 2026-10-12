#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
FixNear RepairAtlas — Generate All Remaining Laptops (~66 models)
Bao gồm Asus, HP, Lenovo, Acer, MSI, Others
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

DEFAULT_LAPTOP_FAULTS = [
    "screen", "battery", "keyboard", "trackpad", "charging-port",
    "hinge-body", "mainboard", "speaker", "mic", "camera",
    "thermal", "ssd-upgrade", "ram-upgrade", "software",
    "water-damage", "data-recovery", "diagnostic"
]

GAMING_LAPTOP_FAULTS = DEFAULT_LAPTOP_FAULTS + ["vga-gpu"]

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

# --- 2. ASUS (16 models) ---
asus_models = [
    # TUF Gaming
    {
        "id": "asus-tuf-gaming-a15",
        "name": "ASUS TUF Gaming A15",
        "brand": "asus",
        "deviceType": "win_laptop",
        "tier": "L3",
        "releaseYear": 2023,
        "specs": {"cpu": "Ryzen 7 7735HS / Ryzen 9 7940HS", "gpu": "RTX 4050 / RTX 4060", "screen": "15.6 inch FHD 144Hz 100% sRGB", "ram": "16GB DDR5 rời", "chassis": "Vỏ kim loại Jaeger Gray"},
        "supportedFaults": GAMING_LAPTOP_FAULTS,
        "knownIssues": [
            {"id": "tuf-fan-dust-noise", "title": "Quạt Arc Flow nghẹt bụi gây nhiệt độ CPU >95°C và hú to", "severity": "high", "confidence": "high", "symptoms": "Nhiệt độ nóng vùng phím WASD, quạt rú ồn ào.", "solution": "Vệ sinh lưới quạt chống bụi Anti-Dust Tunnels và thay keo gốm dẫn nhiệt."},
            {"id": "tuf-wifi-disconnect", "title": "Card mạng Wi-Fi MediaTek MT7921 chập chờn rớt mạng khi chơi game", "severity": "medium", "confidence": "high", "symptoms": "Đang chơi game online bỗng mất Wi-Fi, không tìm thấy danh sách mạng.", "solution": "Thay card Wi-Fi Intel AX210 chuẩn WiFi 6E ổn định tuyệt đối."}
        ]
    },
    {
        "id": "asus-tuf-gaming-f15",
        "name": "ASUS TUF Gaming F15",
        "brand": "asus",
        "deviceType": "win_laptop",
        "tier": "L3",
        "releaseYear": 2023,
        "specs": {"cpu": "Core i7-12700H / i7-13620H", "gpu": "RTX 4050 / RTX 4060", "screen": "15.6 inch FHD 144Hz", "ram": "16GB DDR4/DDR5 rời", "chassis": "Khung chuẩn quân đội MIL-STD-810H"},
        "supportedFaults": GAMING_LAPTOP_FAULTS,
        "knownIssues": [
            {"id": "tuf-hinge-creak", "title": "Bản lề kêu cót két và nặng tay sau 1 năm", "severity": "low", "confidence": "high", "symptoms": "Mở máy nghe tiếng lách cách ở hai góc bản lề.", "solution": "Xả ốc hãm lực bản lề và tra dầu bôi trơn chuyên dụng."}
        ]
    },
    {
        "id": "asus-tuf-dash-f15",
        "name": "ASUS TUF Dash F15",
        "brand": "asus",
        "deviceType": "win_laptop",
        "tier": "L3",
        "releaseYear": 2022,
        "specs": {"cpu": "Core i7-12650H", "gpu": "RTX 3060", "screen": "15.6 inch FHD 144Hz mỏng nhẹ", "ram": "16GB DDR5 (1 khe hàn + 1 khe rời)", "chassis": "Vỏ nhôm trắng Moonlight White"},
        "supportedFaults": GAMING_LAPTOP_FAULTS,
        "knownIssues": [
            {"id": "power-drop-gaming", "title": "Sụt nguồn đột ngột khi GPU boost công suất 105W", "severity": "high", "confidence": "high", "symptoms": "Chơi game nặng máy tự sập nguồn khởi động lại.", "solution": "Kiểm tra dàn tụ lọc MOSFET nguồn GPU và củ sạc 200W."}
        ]
    },
    # ROG Gaming Cao Cấp
    {
        "id": "asus-rog-strix-g16",
        "name": "ASUS ROG Strix G16",
        "brand": "asus",
        "deviceType": "win_laptop",
        "tier": "L4",
        "releaseYear": 2024,
        "specs": {"cpu": "Intel Core i9-14900HX", "gpu": "RTX 4060 / RTX 4070 / RTX 4080", "screen": "16.0 inch QHD+ 240Hz Nebula Display", "ram": "16GB/32GB DDR5 5600MHz", "chassis": "Vỏ kim loại dải LED RGB quanh thân"},
        "supportedFaults": GAMING_LAPTOP_FAULTS,
        "knownIssues": [
            {"id": "liquid-metal-spill-prevention", "title": "Kim loại lỏng tản nhiệt Thermal Grizzly Conductonaut bị lệch pha tản", "severity": "high", "confidence": "high", "symptoms": "Nhiệt độ giữa các nhân chênh lệch quá cao, máy bóp xung tụt fps.", "solution": "Dàn đều lại lớp kim loại lỏng nguyên bản hoặc thay bằng miếng pad Honeywell PTM7950 an toàn tuyệt đối."},
            {"id": "coil-whine-gpu", "title": "Tiếng rít cuộn cảm biến dòng (Coil Whine) khi GPU hoạt động tải nặng", "severity": "medium", "confidence": "high", "symptoms": "Khu vực bàn phím phát ra tiếng vo ve the thé khi bật game 3D.", "solution": "Đổ keo silicone cách âm chân cuộn cảm hoặc chỉnh giới hạn khung hình FPS."}
        ]
    },
    {
        "id": "asus-rog-strix-scar-16",
        "name": "ASUS ROG Strix SCAR 16",
        "brand": "asus",
        "deviceType": "win_laptop",
        "tier": "L4",
        "releaseYear": 2024,
        "specs": {"cpu": "Core i9-14900HX", "gpu": "RTX 4080 / RTX 4090 175W", "screen": "16.0 inch Mini-LED 2.5K 240Hz 1100 nits", "ram": "32GB/64GB DDR5", "chassis": "Vỏ bán trong suốt Semi-translucent"},
        "supportedFaults": GAMING_LAPTOP_FAULTS,
        "knownIssues": [
            {"id": "mini-led-blooming", "title": "Quầng sáng mờ quanh con trỏ chuột trên nền đen (Blooming Effect)", "severity": "low", "confidence": "high", "symptoms": "Hiệu ứng quang học tự nhiên của công nghệ Mini-LED 2048 vùng làm mờ.", "solution": "Cân chỉnh chế độ Single-Zone / Multi-Zone trong Armoury Crate."}
        ]
    },
    {
        "id": "asus-rog-zephyrus-g14",
        "name": "ASUS ROG Zephyrus G14 (2024)",
        "brand": "asus",
        "deviceType": "win_laptop",
        "tier": "L4",
        "releaseYear": 2024,
        "specs": {"cpu": "Ryzen 9 8945HS", "gpu": "RTX 4060 / RTX 4070", "screen": "14.0 inch 3K OLED 120Hz ROG Nebula, dải đèn Slash Lighting", "ram": "16GB/32GB LPDDR5X Soldered", "chassis": "Nhôm CNC nguyên khối siêu sang chỉ 1.5kg"},
        "supportedFaults": [f for f in GAMING_LAPTOP_FAULTS if f != "ram-upgrade"],
        "knownIssues": [
            {"id": "slash-lighting-burn", "title": "Dải đèn LED Slash Lighting chéo nắp A không sáng", "severity": "low", "confidence": "high", "symptoms": "Dải đèn LED chéo mặt lưng mất tín hiệu sáng.", "solution": "Thay cáp dải LED nắp lưng hoặc cập nhật firmware Aura Sync."}
        ]
    },
    {
        "id": "asus-rog-zephyrus-g16",
        "name": "ASUS ROG Zephyrus G16 (2024)",
        "brand": "asus",
        "deviceType": "win_laptop",
        "tier": "L4",
        "releaseYear": 2024,
        "specs": {"cpu": "Intel Core Ultra 9 185H", "gpu": "RTX 4070 / RTX 4080", "screen": "16.0 inch 2.5K OLED 240Hz", "ram": "32GB LPDDR5X Soldered", "chassis": "Nhôm CNC mỏng 1.49cm"},
        "supportedFaults": [f for f in GAMING_LAPTOP_FAULTS if f != "ram-upgrade"],
        "knownIssues": [
            {"id": "oled-flicker-low", "title": "Màn hình OLED chớp nhẹ ở tần số làm mờ thấp", "severity": "low", "confidence": "medium", "symptoms": "Nháy mắt mỏi khi làm việc phòng tối.", "solution": "Bật tính năng DC Dimming giảm chớp OLED trong phần mềm."}
        ]
    },
    {
        "id": "asus-rog-flow-z13",
        "name": "ASUS ROG Flow Z13",
        "brand": "asus",
        "deviceType": "win_laptop",
        "tier": "L4",
        "releaseYear": 2023,
        "specs": {"cpu": "Core i9-13900H", "gpu": "RTX 4060 / RTX 4070 trong thân máy tính bảng 13.4 inch", "screen": "13.4 inch QHD+ 165Hz Touch", "ram": "16GB LPDDR5 Soldered", "chassis": "Máy tính bảng gaming kèm bàn phím tháo rời"},
        "supportedFaults": [f for f in GAMING_LAPTOP_FAULTS if f != "ram-upgrade"],
        "knownIssues": [
            {"id": "keyboard-folio-detach", "title": "Bàn phím Folio rời kết nối nam châm chập chờn", "severity": "medium", "confidence": "high", "symptoms": "Gõ phím lúc nhận lúc không khi nhấc máy nghiêng.", "solution": "Vệ sinh chân tiếp xúc vàng Pogo Pin hoặc thay bàn phím Folio mới."}
        ]
    },
    # Zenbook Mỏng Nhẹ Doanh Nhân
    {
        "id": "asus-zenbook-14-oled-ux3405",
        "name": "ASUS Zenbook 14 OLED UX3405",
        "brand": "asus",
        "deviceType": "win_laptop",
        "tier": "L3",
        "releaseYear": 2024,
        "specs": {"cpu": "Intel Core Ultra 5 125H / Ultra 7 155H", "screen": "14.0 inch 3K 120Hz Lumina OLED", "ram": "16GB/32GB LPDDR5X Soldered", "battery": "75 Wh", "chassis": "Vỏ nhôm xanh Ponder Blue 1.2kg"},
        "supportedFaults": SOLDERED_RAM_FAULTS,
        "knownIssues": [
            {"id": "oled-static-burnin", "title": "Bóng mờ lưu ảnh thanh Taskbar trên tấm nền Lumina OLED", "severity": "medium", "confidence": "medium", "symptoms": "Xuất hiện vệt mờ các icon ứng dụng khi mở nền trắng toàn màn hình.", "solution": "Chạy chu trình làm mới pixel Pixel Refresh hoặc thay màn hình OLED 3K."}
        ]
    },
    {
        "id": "asus-zenbook-14-flip-oled",
        "name": "ASUS Zenbook 14 Flip OLED",
        "brand": "asus",
        "deviceType": "win_laptop",
        "tier": "L3",
        "releaseYear": 2023,
        "specs": {"cpu": "Core i7-1360P", "screen": "14.0 inch 2.8K OLED Cảm ứng xoay gập 360", "ram": "16GB Soldered", "chassis": "Nhôm nguyên khối"},
        "supportedFaults": SOLDERED_RAM_FAULTS,
        "knownIssues": [
            {"id": "360-hinge-sensor", "title": "Cảm biến xoay màn hình tự xoay lộn ngược", "severity": "low", "confidence": "high", "symptoms": "Đặt nằm ngang nhưng màn hình tự khóa hướng dọc.", "solution": "Cân chỉnh lại con quay hồi chuyển trên bo mạch."}
        ]
    },
    {
        "id": "asus-zenbook-duo-2024",
        "name": "ASUS Zenbook Duo (2024)",
        "brand": "asus",
        "deviceType": "win_laptop",
        "tier": "L4",
        "releaseYear": 2024,
        "specs": {"cpu": "Core Ultra 9 185H", "screen": "2 Màn hình đôi 14.0 inch 3K OLED 120Hz", "ram": "32GB LPDDR5X Soldered", "chassis": "Thiết kế màn hình kép độc bản kèm bàn phím Bluetooth"},
        "supportedFaults": SOLDERED_RAM_FAULTS,
        "knownIssues": [
            {"id": "dual-screen-cable-flex", "title": "Cáp nối màn hình phụ bên dưới bị lỏng chập chờn", "severity": "high", "confidence": "high", "symptoms": "Màn hình thứ 2 dưới đáy chớp nháy hoặc không nhận cảm ứng.", "solution": "Hàn thay cụm cáp truyền tín hiệu eDP màn hình kép."}
        ]
    },
    # Vivobook Phổ Thông & Học Sinh
    {
        "id": "asus-vivobook-15-oled",
        "name": "ASUS Vivobook 15 OLED",
        "brand": "asus",
        "deviceType": "win_laptop",
        "tier": "L2",
        "releaseYear": 2023,
        "specs": {"cpu": "Core i5-13500H / Ryzen 5 7530U", "screen": "15.6 inch FHD OLED 60Hz 600 nits", "ram": "8GB hàn + 1 khe rời nâng cấp", "chassis": "Vỏ nhựa nắp kim loại"},
        "supportedFaults": DEFAULT_LAPTOP_FAULTS,
        "knownIssues": [
            {"id": "plastic-hinge-broken-vivo", "title": "Gãy chân ốc bản lề vỏ nhựa bên phải (Lỗi rất phổ biến trên Vivobook)", "severity": "critical", "confidence": "high", "symptoms": "Gập máy nghe tiếng 'tách', nắp viền màn hình bung ra hở chân ốc đồng gãy.", "solution": "Hàn đúc lại chân ốc composite, gia cố lực bản lề không lo gãy lại."}
        ]
    },
    {
        "id": "asus-vivobook-14",
        "name": "ASUS Vivobook 14",
        "brand": "asus",
        "deviceType": "win_laptop",
        "tier": "L1",
        "releaseYear": 2023,
        "specs": {"cpu": "Core i3-1215U / i5-1235U", "screen": "14.0 inch FHD IPS", "ram": "8GB nâng cấp được", "storage": "512GB SSD", "chassis": "Vỏ nhựa bạc"},
        "supportedFaults": DEFAULT_LAPTOP_FAULTS,
        "knownIssues": [
            {"id": "keyboard-chattering-vivo", "title": "Bàn phím chập nhảy chữ hoặc liệt hàng phím số", "severity": "medium", "confidence": "high", "symptoms": "Nhấn phím không ăn hoặc tự động gõ chữ liên tục.", "solution": "Thay bàn phím laptop mới nguyên bảng."}
        ]
    },
    {
        "id": "asus-vivobook-pro-15-oled",
        "name": "ASUS Vivobook Pro 15 OLED",
        "brand": "asus",
        "deviceType": "win_laptop",
        "tier": "L3",
        "releaseYear": 2023,
        "specs": {"cpu": "Ryzen 7 6800H / Core i7-13700H", "gpu": "RTX 3050 / RTX 4050", "screen": "15.6 inch 2.8K OLED 120Hz", "ram": "16GB rời", "chassis": "Vỏ nhôm nắp A"},
        "supportedFaults": GAMING_LAPTOP_FAULTS,
        "knownIssues": [
            {"id": "hot-battery-drain-pro", "title": "Nhiệt độ nóng và pin tụt nhanh khi render đồ họa", "severity": "medium", "confidence": "high", "symptoms": "Quạt thổi hơi nóng mạnh, pin dùng được hơn 2 tiếng.", "solution": "Vệ sinh tản nhiệt ống đồng kép và thay pin mới."}
        ]
    },
    {
        "id": "asus-vivobook-go-15",
        "name": "ASUS Vivobook Go 15",
        "brand": "asus",
        "deviceType": "win_laptop",
        "tier": "L1",
        "releaseYear": 2023,
        "specs": {"cpu": "Ryzen 5 7520U", "screen": "15.6 inch FHD 60Hz", "ram": "8GB/16GB LPDDR5 Soldered", "chassis": "Vỏ nhựa siêu tiết kiệm"},
        "supportedFaults": SOLDERED_RAM_FAULTS,
        "knownIssues": [
            {"id": "trackpad-click-hard", "title": "Bàn rê chuột bị lún nặng tay", "severity": "low", "confidence": "high", "symptoms": "Bấm click chuột trái phải cấn bụi sượng tay.", "solution": "Vệ sinh cơ cấu phím bấm bàn rê chuột."}
        ]
    },
    {
        "id": "asus-expertbook-b9-oled",
        "name": "ASUS ExpertBook B9 OLED",
        "brand": "asus",
        "deviceType": "win_laptop",
        "tier": "L4",
        "releaseYear": 2023,
        "specs": {"cpu": "Core i7-1355U vPro", "screen": "14.0 inch 2.8K OLED 16:10", "ram": "32GB LPDDR5 Soldered", "weight": "0.99 kg", "chassis": "Hợp kim Magie - Liti siêu nhẹ"},
        "supportedFaults": SOLDERED_RAM_FAULTS,
        "knownIssues": [
            {"id": "mag-lithium-paint-scratch", "title": "Trầy xước cạnh hợp kim Magie - Liti", "severity": "low", "confidence": "high", "symptoms": "Màu đen sao băng bị cấn xước góc.", "solution": "Sơn tĩnh điện phục hồi viền hoặc dán bảo vệ."}
        ]
    }
]

save("asus", asus_models)

# --- 3. HP (13 models) ---
hp_models = [
    # Victus Gaming
    {
        "id": "hp-victus-16",
        "name": "HP Victus 16",
        "brand": "hp",
        "deviceType": "win_laptop",
        "tier": "L3",
        "releaseYear": 2023,
        "specs": {"cpu": "Core i5-13500H / Ryzen 7 7840HS", "gpu": "RTX 4050 / RTX 4060", "screen": "16.1 inch FHD/QHD 165Hz", "ram": "16GB DDR5 2 khe rời", "chassis": "Vỏ nhựa logo V chữ nổi"},
        "supportedFaults": GAMING_LAPTOP_FAULTS,
        "knownIssues": [
            {"id": "screen-wobble-victus", "title": "Màn hình rung lắc mạnh khi gõ phím (Screen Wobble)", "severity": "medium", "confidence": "high", "symptoms": "Màn hình 16.1 inch bị rung bần bật khi để trước quạt gió hoặc gõ phím mạnh.", "solution": "Siết ốc trục hãm bản lề hai bên và gia cố thanh nẹp màn hình."},
            {"id": "sleep-black-screen-hall", "title": "Lỗi cảm biến Hall Sensor gập màn hình: Mở máy lên màn hình vẫn tối đen", "severity": "high", "confidence": "high", "symptoms": "Máy vẫn chạy đèn nguồn sáng nhưng màn hình không lên hình sau khi gập mở nắp.", "solution": "Xử lý thay thế cảm biến từ trường Hall Sensor trên vi mạch màn hình."}
        ]
    },
    {
        "id": "hp-victus-15",
        "name": "HP Victus 15",
        "brand": "hp",
        "deviceType": "win_laptop",
        "tier": "L3",
        "releaseYear": 2023,
        "specs": {"cpu": "Core i5-12450H / i5-13420H", "gpu": "GTX 1650 / RTX 3050 / RTX 4050", "screen": "15.6 inch FHD 144Hz", "ram": "8GB/16GB DDR4 rời", "chassis": "Vỏ nhựa"},
        "supportedFaults": GAMING_LAPTOP_FAULTS,
        "knownIssues": [
            {"id": "dc-charging-pin-loose", "title": "Chân cắm sạc tròn kim loại bị lỏng đứt chân hàn bo mạch", "severity": "high", "confidence": "high", "symptoms": "Cắm củ sạc 200W xoay góc mới vào điện.", "solution": "Hàn thay thế Jack cắm nguồn DC-in chịu nhiệt."}
        ]
    },
    # Omen Cao Cấp
    {
        "id": "hp-omen-16",
        "name": "HP Omen 16",
        "brand": "hp",
        "deviceType": "win_laptop",
        "tier": "L4",
        "releaseYear": 2023,
        "specs": {"cpu": "Core i7-13700HX / Ryzen 7 7840HS", "gpu": "RTX 4070 / RTX 4080", "screen": "16.1 inch QHD 240Hz", "ram": "16GB/32GB DDR5", "chassis": "Vỏ nhôm Shadow Black"},
        "supportedFaults": GAMING_LAPTOP_FAULTS,
        "knownIssues": [
            {"id": "black-screen-hall-sensor-omen", "title": "Lỗi cảm biến gập Hall Sensor màn hình đen thui (Lỗi chập cáp Omen 2023)", "severity": "critical", "confidence": "high", "symptoms": "Sau vài tháng sử dụng, bật máy quạt quay đèn phím sáng nhưng màn hình đen hoàn toàn không hiển thị.", "solution": "Kỹ thuật viên cách ly cảm biến Hall Sensor chập mạch hoặc thay thế cụm cáp tín hiệu màn hình chính hãng."}
        ]
    },
    {
        "id": "hp-omen-transcend-14",
        "name": "HP Omen Transcend 14",
        "brand": "hp",
        "deviceType": "win_laptop",
        "tier": "L4",
        "releaseYear": 2024,
        "specs": {"cpu": "Core Ultra 7 155H / Ultra 9 185H", "gpu": "RTX 4060 / RTX 4070", "screen": "14.0 inch 2.8K OLED 120Hz", "ram": "16GB/32GB LPDDR5x Soldered", "chassis": "Nhôm nguyên khối mỏng 1.63kg"},
        "supportedFaults": [f for f in GAMING_LAPTOP_FAULTS if f != "ram-upgrade"],
        "knownIssues": [
            {"id": "vapor-chamber-hot", "title": "Thân máy ấm lan tỏa toàn bộ chiếu nghỉ tay", "severity": "low", "confidence": "high", "symptoms": "Do khung nhôm siêu mỏng truyền nhiệt buồng hơi.", "solution": "Vệ sinh quạt gió tản nhiệt và bôi keo dẫn nhiệt cao cấp."}
        ]
    },
    # Pavilion Phổ Thông
    {
        "id": "hp-pavilion-15",
        "name": "HP Pavilion 15",
        "brand": "hp",
        "deviceType": "win_laptop",
        "tier": "L2",
        "releaseYear": 2023,
        "specs": {"cpu": "Core i5-1235U / i5-1335U", "screen": "15.6 inch FHD IPS", "ram": "8GB/16GB DDR4 rời", "storage": "512GB SSD", "chassis": "Nắp nhôm thân nhựa"},
        "supportedFaults": DEFAULT_LAPTOP_FAULTS,
        "knownIssues": [
            {"id": "pavilion-hinge-crack", "title": "Bung gãy chân ốc bản lề góc trái mặt C (Bệnh rất phổ biến trên Pavilion)", "severity": "critical", "confidence": "high", "symptoms": "Vỏ máy góc bản lề bị banh hở khi mở nắp, cấn đè vào panel màn hình.", "solution": "Hàn đúc lại chân ốc đồng kim loại và nới lỏng trục xoay bản lề."}
        ]
    },
    {
        "id": "hp-pavilion-14",
        "name": "HP Pavilion 14",
        "brand": "hp",
        "deviceType": "win_laptop",
        "tier": "L2",
        "releaseYear": 2023,
        "specs": {"cpu": "Core i5-1335U / i7-1355U", "screen": "14.0 inch FHD IPS", "ram": "DDR4 nâng cấp được", "storage": "512GB SSD", "chassis": "Nhôm bạc nắp máy"},
        "supportedFaults": DEFAULT_LAPTOP_FAULTS,
        "knownIssues": [
            {"id": "keyboard-enter-broken", "title": "Phím Enter và phím Backspace chập liệt", "severity": "medium", "confidence": "high", "symptoms": "Nhấn phím không phản hồi.", "solution": "Thay thế bàn phím Pavilion mới."}
        ]
    },
    {
        "id": "hp-pavilion-plus-14",
        "name": "HP Pavilion Plus 14",
        "brand": "hp",
        "deviceType": "win_laptop",
        "tier": "L2",
        "releaseYear": 2023,
        "specs": {"cpu": "Core i5-13500H / Ryzen 7 7840U", "screen": "14.0 inch 2.8K OLED 120Hz", "ram": "16GB Soldered", "chassis": "Nhôm nguyên khối"},
        "supportedFaults": SOLDERED_RAM_FAULTS,
        "knownIssues": [
            {"id": "battery-short-oled", "title": "Màn hình OLED 2.8K tiêu hao pin nhanh", "severity": "medium", "confidence": "high", "symptoms": "Pin dùng được khoảng 4 tiếng.", "solution": "Thay pin dung lượng cao hoặc tối ưu độ sáng màn hình."}
        ]
    },
    {
        "id": "hp-pavilion-x360-14",
        "name": "HP Pavilion x360 14",
        "brand": "hp",
        "deviceType": "win_laptop",
        "tier": "L2",
        "releaseYear": 2023,
        "specs": {"cpu": "Core i3-1315U / i5-1335U", "screen": "14.0 inch FHD Cảm ứng xoay 360", "ram": "8GB/16GB DDR4", "chassis": "Vỏ kim loại xoay gập"},
        "supportedFaults": DEFAULT_LAPTOP_FAULTS,
        "knownIssues": [
            {"id": "touch-ghost-x360", "title": "Loạn cảm ứng góc trên màn hình khi cắm sạc", "severity": "medium", "confidence": "high", "symptoms": "Màn hình tự click lung tung.", "solution": "Cân chỉnh cảm ứng hoặc thay củ sạc chuẩn chống rò điện."}
        ]
    },
    # Envy Sang Trọng
    {
        "id": "hp-envy-16",
        "name": "HP Envy 16",
        "brand": "hp",
        "deviceType": "win_laptop",
        "tier": "L3",
        "releaseYear": 2023,
        "specs": {"cpu": "Core i7-13700H", "gpu": "RTX 4060", "screen": "16.0 inch WQXGA 120Hz", "ram": "16GB/32GB DDR5 rời", "chassis": "Nhôm nguyên khối Natural Silver"},
        "supportedFaults": GAMING_LAPTOP_FAULTS,
        "knownIssues": [
            {"id": "speaker-crackle-bang", "title": "Loa Bang & Olufsen rè màng âm thanh", "severity": "low", "confidence": "high", "symptoms": "Nghe nhạc âm trầm bị rè xẹt.", "solution": "Vệ sinh màng loa hoặc thay cụm loa kép B&O."}
        ]
    },
    {
        "id": "hp-envy-x360-14",
        "name": "HP Envy x360 14",
        "brand": "hp",
        "deviceType": "win_laptop",
        "tier": "L3",
        "releaseYear": 2024,
        "specs": {"cpu": "Core Ultra 5 125U / Ultra 7 155U", "screen": "14.0 inch 2.8K OLED Cảm ứng 120Hz xoay 360", "ram": "16GB/32GB LPDDR5 Soldered", "chassis": "Nhôm CNC mỏng nhẹ"},
        "supportedFaults": SOLDERED_RAM_FAULTS,
        "knownIssues": [
            {"id": "pen-disconnect-envy", "title": "Mất nhận bút cảm ứng HP Rechargeable MPP2.0", "severity": "low", "confidence": "high", "symptoms": "Vẽ viết không ăn nét.", "solution": "Thay ngòi bút hoặc cân chỉnh lớp số hóa Digitizer."}
        ]
    },
    # Spectre Đầu Bảng
    {
        "id": "hp-spectre-x360-14",
        "name": "HP Spectre x360 14 (2024)",
        "brand": "hp",
        "deviceType": "win_laptop",
        "tier": "L4",
        "releaseYear": 2024,
        "specs": {"cpu": "Intel Core Ultra 7 155H", "screen": "14.0 inch 2.8K OLED 120Hz IMAX Enhanced xoay 360", "ram": "16GB/32GB LPDDR5x Soldered", "chassis": "Nhôm CNC cắt vát kim cương Nightfall Black"},
        "supportedFaults": SOLDERED_RAM_FAULTS,
        "knownIssues": [
            {"id": "haptic-pad-spectre", "title": "Bàn rê chuột xúc giác Haptic Touchpad bị trễ nhịp", "severity": "low", "confidence": "high", "symptoms": "Bấm con trỏ chuột phản hồi hơi chậm.", "solution": "Cập nhật firmware Synaptics Haptic Driver."}
        ]
    },
    # ProBook & EliteBook Doanh Nghiệp
    {
        "id": "hp-probook-450-g10",
        "name": "HP ProBook 450 G10",
        "brand": "hp",
        "deviceType": "win_laptop",
        "tier": "L2",
        "releaseYear": 2023,
        "specs": {"cpu": "Core i5-1335U / i7-1355U", "screen": "15.6 inch FHD IPS", "ram": "2 khe DDR4 nâng tối đa 64GB", "storage": "512GB SSD", "chassis": "Khung nhôm nắp A&C"},
        "supportedFaults": DEFAULT_LAPTOP_FAULTS,
        "knownIssues": [
            {"id": "battery-swelling-pb450", "title": "Pin phồng đội nhẹ mặt bàn phím", "severity": "high", "confidence": "high", "symptoms": "Bàn phím nhô cao ở giữa.", "solution": "Thay pin HP chính hãng bảo hành 12 tháng."}
        ]
    },
    {
        "id": "hp-elitebook-840-g10",
        "name": "HP EliteBook 840 G10",
        "brand": "hp",
        "deviceType": "win_laptop",
        "tier": "L3",
        "releaseYear": 2023,
        "specs": {"cpu": "Core i5-1345U / i7-1365U vPro", "screen": "14.0 inch WUXGA 16:10 400 nits chống nhìn trộm Sure View", "ram": "2 khe DDR5 rời nâng tối đa 64GB", "storage": "512GB/1TB SSD", "chassis": "Hợp kim Magie Nhôm cao cấp"},
        "supportedFaults": DEFAULT_LAPTOP_FAULTS,
        "knownIssues": [
            {"id": "sureview-dim", "title": "Màn hình chống nhìn trộm Sure View hơi tối và góc nhìn hẹp", "severity": "low", "confidence": "high", "symptoms": "Độ sáng cảm giác tối hơn màn thường.", "solution": "Tắt phím F2 Sure View hoặc thay màn hình IPS chuẩn sRGB."}
        ]
    }
]

save("hp", hp_models)

# --- 4. LENOVO (13 models) ---
lenovo_models = [
    # Legion Gaming Đỉnh Cao
    {
        "id": "lenovo-legion-pro-5-16",
        "name": "Lenovo Legion Pro 5 16",
        "brand": "lenovo",
        "deviceType": "win_laptop",
        "tier": "L3",
        "releaseYear": 2023,
        "specs": {"cpu": "Core i7-13700HX / Ryzen 7 7745HX", "gpu": "RTX 4060 / RTX 4070 140W", "screen": "16.0 inch WQXGA 2.5K 16:10 240Hz 500 nits", "ram": "16GB/32GB DDR5 rời", "chassis": "Vỏ kim loại nắp máy tản nhiệt Legion Coldfront 5.0"},
        "supportedFaults": GAMING_LAPTOP_FAULTS,
        "knownIssues": [
            {"id": "legion-power-brick-burnt", "title": "Đầu cắm sạc hình chữ nhật Slim Tip 300W nóng chảy chân nhựa", "severity": "high", "confidence": "high", "symptoms": "Đầu sạc vàng hình chữ nhật bị xỉn màu ám đen, cắm sạc máy báo sạc chậm.", "solution": "Thay dây cắm Jack nguồn Slim Tip chịu dòng 15A chuyên dụng."},
            {"id": "thermal-paste-dry-legion", "title": "Keo tản nhiệt PTM7950 thoái hóa sau 2 năm cày game nặng", "severity": "medium", "confidence": "high", "symptoms": "Nhiệt độ CPU chạm 98°C, quạt gió hú hết công suất liên tục.", "solution": "Vệ sinh làm sạch lưới tản nhiệt tản nhiệt đồng và dán lại màng Honeywell PTM7950 chuẩn gốc."}
        ]
    },
    {
        "id": "lenovo-legion-pro-7-16",
        "name": "Lenovo Legion Pro 7 16",
        "brand": "lenovo",
        "deviceType": "win_laptop",
        "tier": "L4",
        "releaseYear": 2024,
        "specs": {"cpu": "Core i9-14900HX", "gpu": "RTX 4080 / RTX 4090 175W", "screen": "16.0 inch 2.5K 240Hz", "ram": "32GB DDR5", "chassis": "Khung nhôm Magie cao cấp"},
        "supportedFaults": GAMING_LAPTOP_FAULTS,
        "knownIssues": [
            {"id": "vapor-chamber-leak", "title": "Hiệu suất tản nhiệt buồng hơi Vapor Chamber sụt giảm", "severity": "high", "confidence": "high", "symptoms": "Nhiệt độ GPU nhảy vọt lên 86°C nhanh chóng.", "solution": "Bảo dưỡng thay thế cụm tản nhiệt buồng hơi nguyên khối."}
        ]
    },
    {
        "id": "lenovo-legion-slim-5-16",
        "name": "Lenovo Legion Slim 5 16",
        "brand": "lenovo",
        "deviceType": "win_laptop",
        "tier": "L3",
        "releaseYear": 2023,
        "specs": {"cpu": "Ryzen 7 7840HS", "gpu": "RTX 4060", "screen": "16.0 inch 2.5K 165Hz", "ram": "16GB DDR5 rời", "chassis": "Vỏ nhôm xám Storm Grey"},
        "supportedFaults": GAMING_LAPTOP_FAULTS,
        "knownIssues": [
            {"id": "sleep-drain-legion", "title": "Máy tự bật quạt nóng máy trong balo khi để chế độ Sleep", "severity": "medium", "confidence": "high", "symptoms": "Lấy máy ra khỏi balo thân máy nóng ran và pin cạn kiệt.", "solution": "Tắt Modern Standby chuyển sang Hibernate trong Windows."}
        ]
    },
    # LOQ Gaming Phổ Thông
    {
        "id": "lenovo-loq-15irh8",
        "name": "Lenovo LOQ 15IRH8",
        "brand": "lenovo",
        "deviceType": "win_laptop",
        "tier": "L3",
        "releaseYear": 2023,
        "specs": {"cpu": "Core i5-13420H / i7-13620H", "gpu": "RTX 3050 / RTX 4050 / RTX 4060", "screen": "15.6 inch FHD/WQHD 144Hz/165Hz", "ram": "16GB DDR5 rời", "chassis": "Vỏ nhựa cao cấp phong cách Legion"},
        "supportedFaults": GAMING_LAPTOP_FAULTS,
        "knownIssues": [
            {"id": "motherboard-dead-loq", "title": "Lỗi chết nguồn sụt áp bo mạch chủ Mainboard (Lỗi phổ biến dòng LOQ 2023-2024)", "severity": "critical", "confidence": "high", "symptoms": "Đang dùng máy bỗng sập nguồn tối om, cắm sạc đèn nguồn không sáng, kích nguồn không có phản hồi.", "solution": "Đo đạc thay IC quản lý nguồn, sửa chập tụ tầng công suất 19V hoặc bảo hành mainboard."}
        ]
    },
    {
        "id": "lenovo-loq-15iax9",
        "name": "Lenovo LOQ 15IAX9",
        "brand": "lenovo",
        "deviceType": "win_laptop",
        "tier": "L3",
        "releaseYear": 2024,
        "specs": {"cpu": "Core i5-12450HX / i7-13650HX", "gpu": "RTX 3050 / RTX 4050", "screen": "15.6 inch FHD 144Hz 100% sRGB", "ram": "16GB DDR5 rời", "chassis": "Vỏ nhựa Luna Grey"},
        "supportedFaults": GAMING_LAPTOP_FAULTS,
        "knownIssues": [
            {"id": "battery-drop-gaming-loq", "title": "Tụt pin khi cắm củ sạc 170W chơi game nặng", "severity": "medium", "confidence": "high", "symptoms": "Pin tụt 5-10% sau 1 tiếng chơi game dù có cắm sạc.", "solution": "Nâng cấp lên củ sạc chính hãng Lenovo 230W Slim Tip."}
        ]
    },
    # ThinkPad Doanh Nhân Bền Bỉ
    {
        "id": "lenovo-thinkpad-x1-carbon-gen-11",
        "name": "Lenovo ThinkPad X1 Carbon Gen 11",
        "brand": "lenovo",
        "deviceType": "win_laptop",
        "tier": "L4",
        "releaseYear": 2023,
        "specs": {"cpu": "Core i7-1355U / i7-1365U vPro", "screen": "14.0 inch 2.8K OLED / WUXGA chống lóa", "ram": "16GB/32GB LPDDR5 Soldered", "storage": "512GB/1TB SSD", "weight": "1.12 kg", "chassis": "Sợi Carbon dệt nắp A, hợp kim Magie mặt đáy"},
        "supportedFaults": SOLDERED_RAM_FAULTS,
        "knownIssues": [
            {"id": "trackpoint-red-drift", "title": "Con trỏ chuột núm đỏ TrackPoint tự động trôi từ từ sang một bên", "severity": "medium", "confidence": "high", "symptoms": "Chuột tự dịch chuyển trên màn hình mà không ai chạm vào núm đỏ.", "solution": "Cân chỉnh lại cảm biến biến dạng lực TrackPoint hoặc thay núm cao su mới."},
            {"id": "rubber-coating-peel", "title": "Bong tróc lớp phủ nhung mờ cao cấp sau vài năm dùng", "severity": "low", "confidence": "high", "symptoms": "Lớp phủ nhung mặt lưng bị bóng mỡ mồ hôi tay hoặc tróc góc viền.", "solution": "Dán skin dán bảo vệ 3M chuyên dụng hoặc thay vỏ nắp máy."}
        ]
    },
    {
        "id": "lenovo-thinkpad-t14-gen-4",
        "name": "Lenovo ThinkPad T14 Gen 4",
        "brand": "lenovo",
        "deviceType": "win_laptop",
        "tier": "L3",
        "releaseYear": 2023,
        "specs": {"cpu": "Core i5-1335U / Ryzen 7 Pro 7840U", "screen": "14.0 inch WUXGA 16:10", "ram": "DDR5 nâng cấp được", "storage": "512GB SSD", "chassis": "Vỏ nhựa PPS sợi thủy tinh"},
        "supportedFaults": DEFAULT_LAPTOP_FAULTS,
        "knownIssues": [
            {"id": "thunderbolt-firmware-loop", "title": "Cổng sạc Type-C không nhận sạc sau khi cập nhật Windows", "severity": "high", "confidence": "high", "symptoms": "Cắm dây Type-C không có đèn báo nguồn.", "solution": "Nạp lại chip ROM BIOS/Thunderbolt bằng máy nạp chuyên dụng."}
        ]
    },
    {
        "id": "lenovo-thinkpad-e14-gen-5",
        "name": "Lenovo ThinkPad E14 Gen 5",
        "brand": "lenovo",
        "deviceType": "win_laptop",
        "tier": "L2",
        "releaseYear": 2023,
        "specs": {"cpu": "Core i5-1335U / Ryzen 5 7530U", "screen": "14.0 inch WUXGA 16:10", "ram": "8GB hàn + 1 khe rời", "storage": "512GB SSD", "chassis": "Vỏ nhôm nắp A"},
        "supportedFaults": DEFAULT_LAPTOP_FAULTS,
        "knownIssues": [
            {"id": "keyboard-e14-water", "title": "Bàn phím chập do đổ nước vào khe", "severity": "high", "confidence": "high", "symptoms": "Phím bấm nhảy chữ hoặc liệt cụm phím chữ cái.", "solution": "Thay bàn phím ThinkPad mới nguyên bảng."}
        ]
    },
    {
        "id": "lenovo-thinkpad-p16s-gen-2",
        "name": "Lenovo ThinkPad P16s Gen 2",
        "brand": "lenovo",
        "deviceType": "win_laptop",
        "tier": "L3",
        "releaseYear": 2023,
        "specs": {"cpu": "Core i7-1370P / Ryzen 7 Pro 7840U", "gpu": "RTX A500 4GB chuyên đồ họa", "screen": "16.0 inch WUXGA 16:10", "ram": "32GB/64GB LPDDR5x", "chassis": "Máy trạm di động mỏng nhẹ"},
        "supportedFaults": SOLDERED_RAM_FAULTS,
        "knownIssues": [
            {"id": "cad-driver-crash", "title": "Card đồ họa RTX A500 bị văng khi vẽ AutoCAD/Revit nặng", "severity": "medium", "confidence": "high", "symptoms": "Đang xoay mô hình 3D phần mềm báo lỗi driver card màn hình.", "solution": "Cài đặt Driver ISV Certified chính hãng Nvidia Studio."}
        ]
    },
    # IdeaPad & Yoga
    {
        "id": "lenovo-ideapad-slim-5-14",
        "name": "Lenovo IdeaPad Slim 5 14",
        "brand": "lenovo",
        "deviceType": "win_laptop",
        "tier": "L2",
        "releaseYear": 2023,
        "specs": {"cpu": "Core i5-13500H / Ryzen 5 7530U", "screen": "14.0 inch WUXGA 16:10 IPS / OLED", "ram": "16GB LPDDR5 Soldered", "storage": "512GB SSD", "chassis": "Vỏ nhôm nắp A&D"},
        "supportedFaults": SOLDERED_RAM_FAULTS,
        "knownIssues": [
            {"id": "hinge-plastic-ideapad", "title": "Bản lề hơi cứng gây cấn mép màn hình", "severity": "medium", "confidence": "high", "symptoms": "Mở máy nghe tiếng kêu cọt kẹt.", "solution": "Xả nhẹ ốc bản lề và tra mỡ bôi trơn chuyên dụng."}
        ]
    },
    {
        "id": "lenovo-ideapad-3-15",
        "name": "Lenovo IdeaPad 3 15",
        "brand": "lenovo",
        "deviceType": "win_laptop",
        "tier": "L1",
        "releaseYear": 2022,
        "specs": {"cpu": "Core i3-1215U / Ryzen 5 5625U", "screen": "15.6 inch FHD TN/IPS", "ram": "8GB nâng cấp được", "storage": "256GB/512GB SSD", "chassis": "Vỏ nhựa"},
        "supportedFaults": DEFAULT_LAPTOP_FAULTS,
        "knownIssues": [
            {"id": "hinge-break-ideapad3", "title": "Bung gãy chân ốc bản lề vỏ nhựa bên trái", "severity": "critical", "confidence": "high", "symptoms": "Vỏ mép dưới màn hình bung toác chân ốc đồng.", "solution": "Hàn cấy chân ốc kim loại chịu lực cao."}
        ]
    },
    {
        "id": "lenovo-ideapad-gaming-3",
        "name": "Lenovo IdeaPad Gaming 3",
        "brand": "lenovo",
        "deviceType": "win_laptop",
        "tier": "L2",
        "releaseYear": 2022,
        "specs": {"cpu": "Core i5-12500H / Ryzen 5 6600H", "gpu": "RTX 3050", "screen": "15.6 inch FHD 120Hz", "ram": "16GB DDR4 rời", "chassis": "Vỏ nhựa màu xanh Onyx Grey"},
        "supportedFaults": GAMING_LAPTOP_FAULTS,
        "knownIssues": [
            {"id": "fan-bearing-grind", "title": "Quạt tản nhiệt kêu rè rè cọ xát cánh quạt", "severity": "medium", "confidence": "high", "symptoms": "Tiếng kêu lạo xạo phát ra từ quạt tản nhiệt bên phải.", "solution": "Vệ sinh bôi trơn hoặc thay quạt tản nhiệt mới."}
        ]
    },
    {
        "id": "lenovo-yoga-slim-7-pro",
        "name": "Lenovo Yoga Slim 7 Pro",
        "brand": "lenovo",
        "deviceType": "win_laptop",
        "tier": "L3",
        "releaseYear": 2023,
        "specs": {"cpu": "Ryzen 7 7735HS / Core i7-13700H", "screen": "14.5 inch 2.8K 3K 120Hz PureSight OLED", "ram": "16GB/32GB Soldered", "chassis": "Nhôm nguyên khối bo cong"},
        "supportedFaults": SOLDERED_RAM_FAULTS,
        "knownIssues": [
            {"id": "battery-aged-yoga", "title": "Pin sụt nhanh do màn hình độ phân giải cao 2.8K 120Hz", "severity": "medium", "confidence": "high", "symptoms": "Thời lượng pin giảm chỉ còn hơn 3 tiếng.", "solution": "Thay pin dung lượng cao mới bảo hành 6 tháng."}
        ]
    }
]

save("lenovo", lenovo_models)

# --- 5. ACER (9 models) ---
acer_models = [
    # Predator Gaming
    {
        "id": "acer-predator-helios-16",
        "name": "Acer Predator Helios 16",
        "brand": "acer",
        "deviceType": "win_laptop",
        "tier": "L4",
        "releaseYear": 2023,
        "specs": {"cpu": "Core i7-13700HX / i9-13900HX", "gpu": "RTX 4070 / RTX 4080 175W", "screen": "16.0 inch WQXGA 240Hz 500 nits, dải đèn LED RGB nắp lưng", "ram": "16GB/32GB DDR5 rời", "chassis": "Vỏ kim loại đen hầm hố"},
        "supportedFaults": GAMING_LAPTOP_FAULTS,
        "knownIssues": [
            {"id": "aeroblade-fan-high-pitch", "title": "Quạt kim loại AeroBlade 3D thế hệ 5 phát tiếng rít the thé", "severity": "medium", "confidence": "high", "symptoms": "Cánh quạt kim loại siêu mỏng tạo tiếng rít tần số cao khi quay 5000 vòng/phút.", "solution": "Cân chỉnh động lực học quạt và làm sạch bụi bám khe tản."},
            {"id": "liquid-metal-offset", "title": "Nhiệt độ CPU chạm ngưỡng 100°C do kim loại lỏng bị dồn lệch", "severity": "high", "confidence": "high", "symptoms": "Nhiệt độ nhân CPU không đều, máy tự động sụt xung nhịp.", "solution": "Dàn phẳng lại lớp keo kim loại lỏng hoặc thay bằng pad Honeywell PTM7950."}
        ]
    },
    {
        "id": "acer-predator-helios-neo-16",
        "name": "Acer Predator Helios Neo 16",
        "brand": "acer",
        "deviceType": "win_laptop",
        "tier": "L3",
        "releaseYear": 2023,
        "specs": {"cpu": "Core i5-13500HX / i7-13700HX", "gpu": "RTX 4050 / RTX 4060", "screen": "16.0 inch WQXGA 165Hz 100% sRGB", "ram": "16GB DDR5 rời", "chassis": "Nắp nhôm khắc mật mã Morse"},
        "supportedFaults": GAMING_LAPTOP_FAULTS,
        "knownIssues": [
            {"id": "charging-dc-hot-neo16", "title": "Đầu sạc DC kim tròn 330W nóng ran chân cắm", "severity": "high", "confidence": "high", "symptoms": "Chân cắm sạc lỏng lẻo sau thời gian cắm rút.", "solution": "Thay dây cắm Jack nguồn DC-in chịu tải cao."}
        ]
    },
    # Nitro Gaming Quốc Dân
    {
        "id": "acer-nitro-5-tiger",
        "name": "Acer Nitro 5 Tiger",
        "brand": "acer",
        "deviceType": "win_laptop",
        "tier": "L3",
        "releaseYear": 2022,
        "specs": {"cpu": "Core i5-12500H / i7-12700H", "gpu": "RTX 3050 / RTX 3050 Ti / RTX 3060", "screen": "15.6 inch FHD 144Hz", "ram": "8GB/16GB DDR4 rời", "chassis": "Vỏ nhựa đường chỉ đỏ xanh"},
        "supportedFaults": GAMING_LAPTOP_FAULTS,
        "knownIssues": [
            {"id": "nitro5-thermal-throttling", "title": "Quá nhiệt CPU >95°C nghẹt bụi tản nhiệt sau 1 năm sử dụng", "severity": "high", "confidence": "high", "symptoms": "Nhiệt độ nóng rát vùng phím số, chơi game tụt fps giật cục (drop fps).", "solution": "Vệ sinh làm sạch bụi bẩn khe đồng tản nhiệt, bôi keo tản nhiệt gốm MX-4 / MX-6 cao cấp."},
            {"id": "nitro5-power-jack-break", "title": "Bung gãy chân cắm sạc nguồn phía đuôi máy", "severity": "high", "confidence": "high", "symptoms": "Cắm củ sạc vào bị thụt sâu vào trong thân máy.", "solution": "Hàn cố định chân giắc sạc nguồn hoặc thay cụm cáp sạc đuôi mới."}
        ]
    },
    {
        "id": "acer-nitro-16-phoenix",
        "name": "Acer Nitro 16 Phoenix",
        "brand": "acer",
        "deviceType": "win_laptop",
        "tier": "L3",
        "releaseYear": 2023,
        "specs": {"cpu": "Ryzen 7 7735HS / Core i5-13500H", "gpu": "RTX 4050 / RTX 4060", "screen": "16.0 inch WUXGA/WQXGA 165Hz 100% sRGB", "ram": "16GB DDR5 rời", "chassis": "Vỏ nhựa logo N cánh chim"},
        "supportedFaults": GAMING_LAPTOP_FAULTS,
        "knownIssues": [
            {"id": "phoenix-fan-bearing", "title": "Tiếng kêu lạo xạo ở cánh quạt tản nhiệt CPU", "severity": "medium", "confidence": "high", "symptoms": "Quạt quay có tiếng gõ cơ học nhỏ.", "solution": "Tra dầu bôi trơn trục quạt hoặc thay quạt mới."}
        ]
    },
    {
        "id": "acer-nitro-v-15",
        "name": "Acer Nitro V 15",
        "brand": "acer",
        "deviceType": "win_laptop",
        "tier": "L2",
        "releaseYear": 2023,
        "specs": {"cpu": "Core i5-13420H", "gpu": "RTX 2050 / RTX 4050", "screen": "15.6 inch FHD 144Hz", "ram": "8GB/16GB DDR5 rời", "chassis": "Vỏ nhựa mỏng nhẹ"},
        "supportedFaults": GAMING_LAPTOP_FAULTS,
        "knownIssues": [
            {"id": "battery-aged-nitrov", "title": "Pin dung lượng nhỏ 57Wh tụt nhanh khi chơi game", "severity": "medium", "confidence": "high", "symptoms": "Pin dùng văn phòng được 3 tiếng.", "solution": "Thay pin mới chất lượng cao."}
        ]
    },
    # Aspire Phổ Thông
    {
        "id": "acer-aspire-3-a315",
        "name": "Acer Aspire 3 A315",
        "brand": "acer",
        "deviceType": "win_laptop",
        "tier": "L1",
        "releaseYear": 2023,
        "specs": {"cpu": "Core i3-1215U / Ryzen 5 7520U", "screen": "15.6 inch FHD 60Hz", "ram": "8GB nâng cấp được", "storage": "256GB/512GB SSD", "chassis": "Vỏ nhựa bạc"},
        "supportedFaults": DEFAULT_LAPTOP_FAULTS,
        "knownIssues": [
            {"id": "aspire3-hinge-break", "title": "Gãy chân ốc bản lề vỏ nhựa bên trái (Lỗi kinh điển trên Aspire)", "severity": "critical", "confidence": "high", "symptoms": "Mở nắp máy góc trái bung toác, rách vỏ mép viền.", "solution": "Hàn cấy lại chân ốc đồng gia cố keo AB chịu lực chuyên dụng."}
        ]
    },
    {
        "id": "acer-aspire-5-a515",
        "name": "Acer Aspire 5 A515",
        "brand": "acer",
        "deviceType": "win_laptop",
        "tier": "L2",
        "releaseYear": 2023,
        "specs": {"cpu": "Core i5-1335U / i5-13420H", "screen": "15.6 inch FHD IPS", "ram": "DDR4 nâng cấp được", "storage": "512GB SSD", "chassis": "Nắp nhôm thân nhựa"},
        "supportedFaults": DEFAULT_LAPTOP_FAULTS,
        "knownIssues": [
            {"id": "keyboard-chattering-aspire5", "title": "Bàn phím nhảy chữ hoặc liệt phím số", "severity": "medium", "confidence": "high", "symptoms": "Gõ văn bản không ăn phím.", "solution": "Thay bàn phím laptop Acer mới."}
        ]
    },
    {
        "id": "acer-aspire-7-gaming",
        "name": "Acer Aspire 7 Gaming",
        "brand": "acer",
        "deviceType": "win_laptop",
        "tier": "L2",
        "releaseYear": 2023,
        "specs": {"cpu": "Core i5-12450H", "gpu": "GTX 1650 / RTX 2050 / RTX 3050", "screen": "15.6 inch FHD 144Hz", "ram": "8GB/16GB DDR4 rời", "chassis": "Nắp nhôm thân nhựa đen"},
        "supportedFaults": GAMING_LAPTOP_FAULTS,
        "knownIssues": [
            {"id": "hinge-plastic-aspire7", "title": "Chân ốc bản lề lỏng lẻo sau thời gian dài gập mở", "severity": "medium", "confidence": "high", "symptoms": "Mở nắp máy cập kênh.", "solution": "Hàn cấy chân ốc và siết lại bản lề."}
        ]
    },
    # Swift Mỏng Nhẹ
    {
        "id": "acer-swift-go-14-oled",
        "name": "Acer Swift Go 14 OLED",
        "brand": "acer",
        "deviceType": "win_laptop",
        "tier": "L3",
        "releaseYear": 2023,
        "specs": {"cpu": "Core i5-13500H / Core Ultra 5 125H", "screen": "14.0 inch 2.8K OLED 90Hz", "ram": "16GB LPDDR5 Soldered", "chassis": "Nhôm nguyên khối mỏng 1.25kg"},
        "supportedFaults": SOLDERED_RAM_FAULTS,
        "knownIssues": [
            {"id": "oled-battery-swift", "title": "Pin sụt nhanh do màn hình OLED 2.8K", "severity": "medium", "confidence": "high", "symptoms": "Onscreen được hơn 3 tiếng.", "solution": "Thay pin dung lượng cao và giảm tần số quét."}
        ]
    }
]

save("acer", acer_models)

# --- 6. MSI (9 models) ---
msi_models = [
    # Flagship Siêu Cấp
    {
        "id": "msi-titan-18-hx",
        "name": "MSI Titan 18 HX",
        "brand": "msi",
        "deviceType": "win_laptop",
        "tier": "L4",
        "releaseYear": 2024,
        "specs": {"cpu": "Core i9-14900HX", "gpu": "RTX 4090 175W", "screen": "18.0 inch 4K Mini-LED 120Hz, bàn phím cơ Cherry MX", "ram": "64GB/128GB DDR5 4 khe", "chassis": "Quái vật tản nhiệt buồng hơi Vapor Chamber nặng 3.6kg"},
        "supportedFaults": GAMING_LAPTOP_FAULTS,
        "knownIssues": [
            {"id": "heavy-chassis-hinge", "title": "Bản lề chịu tải màn hình khổng lồ 18 inch bị căng lực sau 1 năm", "severity": "high", "confidence": "high", "symptoms": "Màn hình 18 inch nặng mở ra hơi cập kênh.", "solution": "Cân chỉnh siết ốc lò xo trợ lực bản lề kép."}
        ]
    },
    {
        "id": "msi-raider-ge78-hx",
        "name": "MSI Raider GE78 HX",
        "brand": "msi",
        "deviceType": "win_laptop",
        "tier": "L4",
        "releaseYear": 2023,
        "specs": {"cpu": "Core i9-13980HX", "gpu": "RTX 4080 / RTX 4090", "screen": "17.0 inch QHD+ 240Hz, dải đèn Matrix RGB Mystic Light", "ram": "32GB DDR5 rời", "chassis": "Vỏ kim loại dải LED ma trận"},
        "supportedFaults": GAMING_LAPTOP_FAULTS,
        "knownIssues": [
            {"id": "mystic-led-bar-dead", "title": "Dải đèn LED Matrix phía trước chiếu nghỉ bị tắt một đoạn", "severity": "low", "confidence": "high", "symptoms": "Dải đèn cầu vồng bị tối vài hạt LED.", "solution": "Thay dải cáp LED Mystic Light chính hãng."}
        ]
    },
    # Gaming Phổ Thông (Katana, Cyborg, GF63)
    {
        "id": "msi-katana-15",
        "name": "MSI Katana 15",
        "brand": "msi",
        "deviceType": "win_laptop",
        "tier": "L3",
        "releaseYear": 2023,
        "specs": {"cpu": "Core i7-13620H", "gpu": "RTX 4050 / RTX 4060", "screen": "15.6 inch FHD 144Hz", "ram": "16GB DDR5 rời", "chassis": "Vỏ nhựa đen phong cách kiếm thủ Katana"},
        "supportedFaults": GAMING_LAPTOP_FAULTS,
        "knownIssues": [
            {"id": "msi-hinge-shatter", "title": "Gãy bản lề vỏ nhựa bên trái/phải (Bệnh kinh điển của MSI Katana / GF Series)", "severity": "critical", "confidence": "high", "symptoms": "Mở màn hình thấy vỏ góc bung toạc, kêu tách gãy chân ốc nhựa, cấn đè nứt góc màn hình.", "solution": "Hàn gia cố cấy chân ốc đồng, xả nhẹ độ nặng bản lề hai bên, bao gãy trọn đời máy."},
            {"id": "hot-cpu-temps-katana", "title": "Nhiệt độ CPU chạm ngưỡng 96°C quạt hú to khi chơi game", "severity": "high", "confidence": "high", "symptoms": "Máy nóng ran vùng phím, giật khung hình.", "solution": "Vệ sinh lưới tản nhiệt Cooler Boost 5 và thay keo tản nhiệt gốm MX-6."}
        ]
    },
    {
        "id": "msi-katana-17",
        "name": "MSI Katana 17",
        "brand": "msi",
        "deviceType": "win_laptop",
        "tier": "L3",
        "releaseYear": 2023,
        "specs": {"cpu": "Core i7-13620H", "gpu": "RTX 4060", "screen": "17.3 inch FHD 144Hz", "ram": "16GB DDR5 rời", "chassis": "Vỏ nhựa"},
        "supportedFaults": GAMING_LAPTOP_FAULTS,
        "knownIssues": [
            {"id": "hinge-crack-17inch", "title": "Gãy chân ốc bản lề màn hình 17.3 inch to nặng", "severity": "critical", "confidence": "high", "symptoms": "Bung nắp góc viền màn hình.", "solution": "Hàn cấy chân ốc kim loại chịu lực cao."}
        ]
    },
    {
        "id": "msi-cyborg-15",
        "name": "MSI Cyborg 15",
        "brand": "msi",
        "deviceType": "win_laptop",
        "tier": "L2",
        "releaseYear": 2023,
        "specs": {"cpu": "Core i5-12450H / i7-12650H", "gpu": "RTX 4050 / RTX 4060 45W", "screen": "15.6 inch FHD 144Hz, vỏ bán trong suốt", "ram": "16GB DDR5 rời", "chassis": "Vỏ nhựa trong suốt Cyberpunk mỏng nhẹ 1.98kg"},
        "supportedFaults": GAMING_LAPTOP_FAULTS,
        "knownIssues": [
            {"id": "single-fan-hot", "title": "Thiết kế chỉ có 1 quạt tản nhiệt nên máy ấm nhanh khi chơi game nặng", "severity": "medium", "confidence": "high", "symptoms": "Nhiệt độ nóng tập trung ở cạnh bên trái máy.", "solution": "Vệ sinh làm sạch bụi quạt đơn định kỳ và bôi keo tản nhiệt cao cấp."}
        ]
    },
    {
        "id": "msi-thin-gf63",
        "name": "MSI Thin GF63",
        "brand": "msi",
        "deviceType": "win_laptop",
        "tier": "L2",
        "releaseYear": 2023,
        "specs": {"cpu": "Core i5-12450H", "gpu": "RTX 2050 / RTX 3050 / RTX 4050 45W", "screen": "15.6 inch FHD 144Hz", "ram": "8GB/16GB DDR4 rời", "chassis": "Nắp nhôm phay xước, thân nhựa mỏng 1.86kg"},
        "supportedFaults": GAMING_LAPTOP_FAULTS,
        "knownIssues": [
            {"id": "gf63-hinge-broken", "title": "Bung gãy chân ốc bản lề hai bên góc (Lỗi rất phổ biến dòng GF63)", "severity": "critical", "confidence": "high", "symptoms": "Bung nắp viền B và gãy chân ngàm mặt C.", "solution": "Hàn đúc lại chân ốc kim loại gia cố keo chịu lực."}
        ]
    },
    # Văn Phòng Modern & Mỏng Nhẹ Stealth
    {
        "id": "msi-modern-14",
        "name": "MSI Modern 14",
        "brand": "msi",
        "deviceType": "win_laptop",
        "tier": "L1",
        "releaseYear": 2023,
        "specs": {"cpu": "Core i3-1215U / i5-1235U / Ryzen 5 7530U", "screen": "14.0 inch FHD IPS", "ram": "8GB/16GB DDR4 nâng cấp được", "storage": "512GB SSD", "chassis": "Vỏ nhôm đen mỏng nhẹ 1.4kg"},
        "supportedFaults": DEFAULT_LAPTOP_FAULTS,
        "knownIssues": [
            {"id": "modern14-dc-loose", "title": "Chân cắm sạc nguồn DC nhỏ dễ lỏng lẻo sau 1 năm", "severity": "medium", "confidence": "high", "symptoms": "Cắm dây sạc xoay qua lại mới nhận pin.", "solution": "Hàn thay chân Jack nguồn DC trên bo mạch."}
        ]
    },
    {
        "id": "msi-modern-15",
        "name": "MSI Modern 15",
        "brand": "msi",
        "deviceType": "win_laptop",
        "tier": "L1",
        "releaseYear": 2023,
        "specs": {"cpu": "Core i5-1335U / Ryzen 7 7730U", "screen": "15.6 inch FHD IPS", "ram": "DDR4 nâng cấp được", "storage": "512GB SSD", "chassis": "Vỏ nhôm đen"},
        "supportedFaults": DEFAULT_LAPTOP_FAULTS,
        "knownIssues": [
            {"id": "keyboard-space-modern", "title": "Phím cách Spacebar bị kẹt một bên mép", "severity": "low", "confidence": "high", "symptoms": "Nhấn phím cách không nảy.", "solution": "Cân chỉnh thanh cân bằng lò xo phím Spacebar."}
        ]
    },
    {
        "id": "msi-stealth-16-studio",
        "name": "MSI Stealth 16 Studio",
        "brand": "msi",
        "deviceType": "win_laptop",
        "tier": "L4",
        "releaseYear": 2023,
        "specs": {"cpu": "Core i7-13700H / i9-13900H", "gpu": "RTX 4070 / RTX 4080", "screen": "16.0 inch QHD+ 240Hz / 4K 120Hz Mini-LED", "ram": "32GB DDR5 rời", "chassis": "Hợp kim Magie Nhôm siêu nhẹ 1.99kg"},
        "supportedFaults": GAMING_LAPTOP_FAULTS,
        "knownIssues": [
            {"id": "audio-dynaudio-speaker", "title": "Hệ thống 6 loa Dynaudio rè nhẹ âm bass khi mở max volume", "severity": "low", "confidence": "high", "symptoms": "Tiếng rè màng loa ở âm trầm.", "solution": "Vệ sinh màng loa hoặc thay loa trầm subwoofer."}
        ]
    }
]

save("msi", msi_models)

# --- 7. OTHERS (6 models) ---
others_models = [
    {
        "id": "lg-gram-16-2023",
        "name": "LG Gram 16 (2023/2024)",
        "brand": "others",
        "deviceType": "win_laptop",
        "tier": "L3",
        "releaseYear": 2023,
        "specs": {"cpu": "Intel Core i7-1360P / Core Ultra 7 155H", "screen": "16.0 inch 2.5K WQXGA 16:10 chống chói", "ram": "16GB/32GB LPDDR5 Soldered (Hàn bo mạch)", "storage": "512GB/1TB NVMe SSD", "weight": "1.19 kg", "chassis": "Hợp kim Magie Nano siêu nhẹ đạt chuẩn độ bền quân đội"},
        "supportedFaults": SOLDERED_RAM_FAULTS,
        "knownIssues": [
            {"id": "keyboard-flex-deep", "title": "Bàn phím hơi lún nhẹ khi gõ mạnh do vỏ hợp kim Magie mỏng", "severity": "low", "confidence": "high", "symptoms": "Chiếu nghỉ và khung phím hơi đàn hồi nhẹ khi ấn mạnh tay.", "solution": "Đệm thêm lớp foam chịu lực bên trong khung bo mạch."},
            {"id": "battery-swelling-gram", "title": "Pin dung lượng lớn 80Wh chai phồng sau 2 năm cắm sạc liên tục", "severity": "high", "confidence": "high", "symptoms": "Bàn rê chuột bị đội kênh nhẹ lên.", "solution": "Thay pin LG Gram chính hãng bảo hành 12 tháng."}
        ]
    },
    {
        "id": "lg-gram-14-2023",
        "name": "LG Gram 14 (2023/2024)",
        "brand": "others",
        "deviceType": "win_laptop",
        "tier": "L3",
        "releaseYear": 2023,
        "specs": {"cpu": "Core i5-1340P", "screen": "14.0 inch WUXGA 16:10", "ram": "16GB LPDDR5 Soldered", "weight": "0.999 kg", "chassis": "Hợp kim Magie trắng"},
        "supportedFaults": SOLDERED_RAM_FAULTS,
        "knownIssues": [
            {"id": "fan-whine-gram14", "title": "Quạt tản nhiệt kêu the thé khi quay nhanh", "severity": "low", "confidence": "high", "symptoms": "Tiếng rít nhẹ trong phòng yên tĩnh.", "solution": "Vệ sinh bôi trơn trục quạt LG Gram."}
        ]
    },
    {
        "id": "microsoft-surface-laptop-5",
        "name": "Microsoft Surface Laptop 5",
        "brand": "others",
        "deviceType": "win_laptop",
        "tier": "L4",
        "releaseYear": 2022,
        "specs": {"cpu": "Core i5-1235U / i7-1255U", "screen": "13.5 inch / 15.0 inch PixelSense Cảm ứng 3:2", "ram": "8GB/16GB LPDDR5x Soldered (Hàn bo mạch)", "storage": "256GB/512GB SSD rời", "chassis": "Vỏ nhôm nguyên khối bo viền hoặc vải Alcantara"},
        "supportedFaults": SOLDERED_RAM_FAULTS,
        "knownIssues": [
            {"id": "surface-connect-chập", "title": "Cổng sạc nam châm Surface Connect chập chờn lỏng chân hít", "severity": "high", "confidence": "high", "symptoms": "Cắm củ sạc nam châm đèn led đầu sạc không sáng hoặc chớp tắt.", "solution": "Vệ sinh chân tiếp xúc vàng hoặc thay cổng sạc Surface Connect."},
            {"id": "alcantara-dirty-oil", "title": "Vải Alcantara chiếu nghỉ tay bị ố vàng bám mồ hôi dầu", "severity": "low", "confidence": "high", "symptoms": "Bề mặt vải bị thâm màu không lau sạch được.", "solution": "Vệ sinh phục hồi chuyên sâu hoặc dán skin che khuyết điểm."}
        ]
    },
    {
        "id": "microsoft-surface-pro-9",
        "name": "Microsoft Surface Pro 9",
        "brand": "others",
        "deviceType": "win_laptop",
        "tier": "L4",
        "releaseYear": 2022,
        "specs": {"cpu": "Intel Core i5-1235U / i7-1255U / Microsoft SQ3", "screen": "13.0 inch PixelSense Flow 120Hz Cảm ứng", "ram": "8GB/16GB/32GB LPDDR5 Soldered", "storage": "SSD M.2 2230 tháo rời nhanh", "chassis": "Máy tính bảng kiêm laptop chân chống Kickstand"},
        "supportedFaults": SOLDERED_RAM_FAULTS,
        "knownIssues": [
            {"id": "surface-battery-screen-push", "title": "Pin phồng đội bung keo nứt màn hình cảm ứng PixelSense", "severity": "critical", "confidence": "high", "symptoms": "Màn hình cảm ứng bị hở viền vàng ố mép do thỏi pin bên trong phồng to.", "solution": "Tách màn hình bằng bàn nhiệt chuyên dụng, thay pin Surface chính hãng và dán lại ron màn hình."}
        ]
    },
    {
        "id": "gigabyte-g5-kf",
        "name": "Gigabyte G5 KF",
        "brand": "others",
        "deviceType": "win_laptop",
        "tier": "L2",
        "releaseYear": 2023,
        "specs": {"cpu": "Core i5-12500H", "gpu": "RTX 4060 75W", "screen": "15.6 inch FHD 144Hz", "ram": "16GB DDR4 rời", "chassis": "Vỏ nhựa Clevo"},
        "supportedFaults": GAMING_LAPTOP_FAULTS,
        "knownIssues": [
            {"id": "gigabyte-g5-loud-fan", "title": "Quạt tản nhiệt hú rất to và nhiệt độ GPU nóng trên 85°C", "severity": "high", "confidence": "high", "symptoms": "Quạt tản nhiệt thổi ồn ào ngay khi mở game.", "solution": "Vệ sinh thay keo tản nhiệt gốm và kê cao đáy máy tạo đối lưu gió."}
        ]
    },
    {
        "id": "vaio-sx14",
        "name": "VAIO SX14 (2023)",
        "brand": "others",
        "deviceType": "win_laptop",
        "tier": "L4",
        "releaseYear": 2023,
        "specs": {"cpu": "Core i7-1360P", "screen": "14.0 inch 4K / FHD chống lóa, bản lề nâng công thái học", "ram": "16GB/32GB LPDDR5 Soldered", "weight": "1.08 kg", "chassis": "Vỏ sợi Carbon đa hướng sản xuất tại Nhật Bản"},
        "supportedFaults": SOLDERED_RAM_FAULTS,
        "knownIssues": [
            {"id": "lift-hinge-wear-vaio", "title": "Chân cao su bản lề nâng công thái học bị mòn trượt trên bàn", "severity": "low", "confidence": "high", "symptoms": "Mở máy gờ tì bản lề trơn trượt trên mặt bàn kính.", "solution": "Thay thế đệm cao su chân đế bản lề VAIO."}
        ]
    }
]

save("others", others_models)
print("=== HOÀN TẤT TẠO TOÀN BỘ 7 FILE CATALOG LAPTOP WINDOWS ===")
