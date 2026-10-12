#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
FixNear RepairAtlas — Smartwatch Catalog Builder (~26 models)
Xây dựng danh mục Đồng Hồ Thông Minh tại data/catalog/smartwatch/
Bao gồm Apple, Samsung, Garmin, Others
"""

import sys
import json
from pathlib import Path

# Force UTF-8 output on Windows
if sys.platform == "win32":
    sys.stdout.reconfigure(encoding="utf-8")

ROOT_DIR = Path(__file__).resolve().parent.parent
SW_DIR = ROOT_DIR / "data" / "catalog" / "smartwatch"
SW_DIR.mkdir(parents=True, exist_ok=True)

# Lỗi chuẩn cho Smartwatch (Tuyệt đối KHÔNG có keyboard/trackpad/ram/ssd/hinge)
SMARTWATCH_FAULTS = [
    "screen", "glass-press", "battery", "digital-crown", "taptic-engine",
    "charging-port", "speaker", "mic", "mainboard", "water-damage",
    "software", "diagnostic"
]

def save(brand, data):
    p = SW_DIR / f"{brand}.json"
    with open(p, "w", encoding="utf-8") as f:
        json.dump(data, f, ensure_ascii=False, indent=2)
    print(f"  ✅ Đã lưu {brand}.json: {len(data)} models")

# --- 1. APPLE WATCH (10 models) ---
apple_smartwatches = [
    # Apple Watch Ultra (W3)
    {
        "id": "apple-watch-ultra-2",
        "name": "Apple Watch Ultra 2 (49mm)",
        "brand": "apple",
        "deviceType": "smartwatch",
        "tier": "W3",
        "releaseYear": 2023,
        "specs": {"case": "49mm Vỏ Titanium cấp độ hàng không, kính Sapphire phẳng", "screen": "LTPO OLED 3000 nits sáng nhất", "chip": "Apple S9 SiP (cử chỉ chạm 2 lần Double Tap)", "battery": "542 mAh (lên đến 72 giờ Low Power)", "water": "Kháng nước 100m, chuẩn lặn EN13319"},
        "supportedFaults": SMARTWATCH_FAULTS,
        "knownIssues": [
            {"id": "action-button-orange-stiff", "title": "Nút tác vụ Action Button màu cam bị kẹt cát bụi sau khi đi biển lặn", "severity": "medium", "confidence": "high", "symptoms": "Nút bấm màu cam bên trái ấn bị lún không phản hồi hoặc mất độ nảy.", "solution": "Vệ sinh sóng siêu âm tẩy muối biển cát bám và thay ron làm kín phím Action."},
            {"id": "double-tap-gesture-lag", "title": "Cử chỉ chạm hai ngón tay Double Tap chập chờn khi tay lạnh", "severity": "low", "confidence": "high", "symptoms": "Chạm ngón cái và ngón trỏ vào nhau đồng hồ không phản hồi nhận cuộc gọi.", "solution": "Cân chỉnh cảm biến gia tốc và lưu lượng máu quang học trong WatchOS."}
        ]
    },
    {
        "id": "apple-watch-ultra-1",
        "name": "Apple Watch Ultra (49mm 2022)",
        "brand": "apple",
        "deviceType": "smartwatch",
        "tier": "W3",
        "releaseYear": 2022,
        "specs": {"case": "49mm Vỏ Titanium, còi báo động Siren 86dB", "screen": "LTPO OLED 2000 nits", "chip": "Apple S8 SiP", "battery": "542 mAh"},
        "supportedFaults": SMARTWATCH_FAULTS,
        "knownIssues": [
            {"id": "depth-gauge-sensor-salt", "title": "Cảm biến đo độ sâu lặn biển bị bám muối biển báo sai thông số", "severity": "medium", "confidence": "high", "symptoms": "Bật app Độ sâu không hiện mét nước hoặc báo lỗi cảm biến.", "solution": "Ngâm rửa hóa chất tẩy muối chuyên dụng hoặc thay cụm cảm biến áp suất đáy máy."}
        ]
    },
    # Apple Watch Series 9 & 8 & 7 & 6 & 5 (W2)
    {
        "id": "apple-watch-series-9",
        "name": "Apple Watch Series 9 (41mm / 45mm)",
        "brand": "apple",
        "deviceType": "smartwatch",
        "tier": "W2",
        "releaseYear": 2023,
        "specs": {"case": "41mm / 45mm Nhôm / Thép không gỉ", "screen": "LTPO OLED 2000 nits cạnh cong tràn viền", "chip": "Apple S9 SiP Double Tap", "battery": "308 mAh (45mm)"},
        "supportedFaults": SMARTWATCH_FAULTS,
        "knownIssues": [
            {"id": "ghost-touch-watchos", "title": "Màn hình tự nhảy loạn cảm ứng (Ghost Touch) tự gọi điện hoặc mở app", "severity": "high", "confidence": "high", "symptoms": "Mặt đồng hồ tự nhảy liên tục dù không chạm tay.", "solution": "Cập nhật bản vá WatchOS hoặc ép lại mặt kính cảm ứng màn hình mới."}
        ]
    },
    {
        "id": "apple-watch-series-8",
        "name": "Apple Watch Series 8 (41mm / 45mm)",
        "brand": "apple",
        "deviceType": "smartwatch",
        "tier": "W2",
        "releaseYear": 2022,
        "specs": {"case": "41mm / 45mm Nhôm / Thép", "screen": "LTPO OLED Always-On 1000 nits", "chip": "Apple S8 SiP, cảm biến nhiệt độ cổ tay", "battery": "308 mAh"},
        "supportedFaults": SMARTWATCH_FAULTS,
        "knownIssues": [
            {"id": "digital-crown-gunk", "title": "Nút xoay Digital Crown bị kẹt cứng không xoay cuộn được", "severity": "medium", "confidence": "high", "symptoms": "Xoay nút cảm giác sượng tay, bám cặn mồ hôi khô sau khi tập gym.", "solution": "Vệ sinh tẩy cặn trục xoay quang học Digital Crown dưới dòng nước ấm chuyên dụng."}
        ]
    },
    {
        "id": "apple-watch-series-7",
        "name": "Apple Watch Series 7 (41mm / 45mm)",
        "brand": "apple",
        "deviceType": "smartwatch",
        "tier": "W2",
        "releaseYear": 2021,
        "specs": {"case": "41mm / 45mm Viền màn hình siêu mỏng 1.7mm", "screen": "OLED tràn viền thác nước", "chip": "Apple S7 SiP", "battery": "309 mAh, sạc nhanh USB-C"},
        "supportedFaults": SMARTWATCH_FAULTS,
        "knownIssues": [
            {"id": "edge-glass-shatter-s7", "title": "Nứt mẻ mặt kính mép cong siêu tràn viền sau khi va đập cạnh bàn", "severity": "high", "confidence": "high", "symptoms": "Mặt kính vỡ cạnh cong góc 12h hoặc 6h.", "solution": "Ép mặt kính cảm ứng cong Apple Watch Series 7 bằng khuôn nhiệt chính xác."}
        ]
    },
    {
        "id": "apple-watch-series-6",
        "name": "Apple Watch Series 6 (40mm / 44mm)",
        "brand": "apple",
        "deviceType": "smartwatch",
        "tier": "W2",
        "releaseYear": 2020,
        "specs": {"case": "40mm / 44mm Nhôm / Thép", "screen": "OLED Always-On", "chip": "Apple S6 SiP, cảm biến đo nồng độ oxy SpO2", "battery": "303 mAh"},
        "supportedFaults": SMARTWATCH_FAULTS,
        "knownIssues": [
            {"id": "blank-screen-issue-s6", "title": "Màn hình tự nhiên đen thui vĩnh viễn (Chương trình bảo hành màn hình Apple S6)", "severity": "critical", "confidence": "high", "symptoms": "Màn hình tối đen hoàn toàn dù máy vẫn rung nhận thông báo.", "solution": "Thay cụm màn hình hiển thị OLED Apple Watch Series 6 zin bóc máy."}
        ]
    },
    {
        "id": "apple-watch-series-5",
        "name": "Apple Watch Series 5 (40mm / 44mm)",
        "brand": "apple",
        "deviceType": "smartwatch",
        "tier": "W2",
        "releaseYear": 2019,
        "specs": {"case": "40mm / 44mm Nhôm / Thép / Gốm", "screen": "OLED Always-on Display đầu tiên", "chip": "Apple S5 SiP, La bàn từ tính", "battery": "296 mAh"},
        "supportedFaults": SMARTWATCH_FAULTS,
        "knownIssues": [
            {"id": "battery-swelling-screen-pop", "title": "Pin phồng đội bung bật màn hình lên trên (Bệnh kinh điển sau 4 năm)", "severity": "critical", "confidence": "high", "symptoms": "Màn hình bị hở viền keo, nhấc bổng lên khỏi thân nhôm do thỏi pin phồng to.", "solution": "Tách màn hình an toàn, thay pin Apple Watch mới và ép lại ron cao su chịu nước."}
        ]
    },
    # Apple Watch SE & Series 4 (W1)
    {
        "id": "apple-watch-se-2-2022",
        "name": "Apple Watch SE 2 (40mm / 44mm 2022)",
        "brand": "apple",
        "deviceType": "smartwatch",
        "tier": "W1",
        "releaseYear": 2022,
        "specs": {"case": "40mm / 44mm Vỏ nhôm, mặt đáy vật liệu composite dệt nylon", "screen": "Retina OLED 1000 nits", "chip": "Apple S8 SiP", "battery": "296 mAh"},
        "supportedFaults": SMARTWATCH_FAULTS,
        "knownIssues": [
            {"id": "composite-back-scratch", "title": "Trầy xước mặt đáy nilon composite quanh thấu kính cảm biến nhịp tim", "severity": "low", "confidence": "high", "symptoms": "Mặt đáy nhựa sau xước dăm mất bóng.", "solution": "Đánh bóng phục hồi mặt đáy hoặc thay nắp lưng cảm biến."}
        ]
    },
    {
        "id": "apple-watch-se-1-2020",
        "name": "Apple Watch SE 1 (40mm / 44mm 2020)",
        "brand": "apple",
        "deviceType": "smartwatch",
        "tier": "W1",
        "releaseYear": 2020,
        "specs": {"case": "40mm / 44mm Vỏ nhôm", "screen": "Retina OLED", "chip": "Apple S5 SiP", "battery": "296 mAh"},
        "supportedFaults": SMARTWATCH_FAULTS,
        "knownIssues": [
            {"id": "battery-aged-se1", "title": "Pin chai dùng nửa ngày đã hết", "severity": "medium", "confidence": "high", "symptoms": "Pin tụt dốc, sập nguồn khi thời tiết lạnh.", "solution": "Thay pin mới bảo hành 6 tháng."}
        ]
    },
    {
        "id": "apple-watch-series-4",
        "name": "Apple Watch Series 4 (40mm / 44mm)",
        "brand": "apple",
        "deviceType": "smartwatch",
        "tier": "W1",
        "releaseYear": 2018,
        "specs": {"case": "40mm / 44mm Nhôm / Thép", "screen": "Retina OLED", "chip": "Apple S4 SiP, cảm biến điện tâm đồ ECG", "battery": "291 mAh"},
        "supportedFaults": SMARTWATCH_FAULTS,
        "knownIssues": [
            {"id": "water-taptic-vibrate", "title": "Mô-tơ rung Taptic Engine rung liên tục không ngừng sau khi bị vô nước", "severity": "high", "confidence": "high", "symptoms": "Đồng hồ rung giật liên hồi, máy nóng ran.", "solution": "Sấy khô sóng siêu âm bo mạch, thay thế mô tơ rung Taptic Engine mới."}
        ]
    }
]

save("apple", apple_smartwatches)

# --- 2. SAMSUNG GALAXY WATCH (6 models) ---
samsung_smartwatches = [
    # Watch 6 Classic (W2)
    {
        "id": "samsung-galaxy-watch-6-classic",
        "name": "Galaxy Watch 6 Classic (43mm / 47mm)",
        "brand": "samsung",
        "deviceType": "smartwatch",
        "tier": "W2",
        "releaseYear": 2023,
        "specs": {"case": "43mm / 47mm Thép không gỉ, viền bezel xoay vật lý", "screen": "1.3 / 1.5 inch Super AMOLED kính Sapphire", "chip": "Exynos W930", "battery": "300 / 425 mAh"},
        "supportedFaults": SMARTWATCH_FAULTS,
        "knownIssues": [
            {"id": "rotating-bezel-stuck", "title": "Vòng bezel xoay vật lý bị kẹt bụi hoặc kêu lạo xạo khi xoay", "severity": "medium", "confidence": "high", "symptoms": "Vòng xoay cứng đơ, không xoay được menu ứng dụng.", "solution": "Tháo vệ sinh 4 viên bi trợ lực và lò xo đệm dưới vòng bezel xoay."},
            {"id": "back-sensor-glass-crack", "title": "Nứt kính thấu kính cảm biến nhịp tim & BIA mặt đáy", "severity": "high", "confidence": "high", "symptoms": "Mặt đáy nứt đường chéo làm đo huyết áp và nhịp tim bị lỗi.", "solution": "Thay mặt kính cảm biến BioActive mặt đáy chống nước."}
        ]
    },
    {
        "id": "samsung-galaxy-watch-6",
        "name": "Galaxy Watch 6 (40mm / 44mm)",
        "brand": "samsung",
        "deviceType": "smartwatch",
        "tier": "W2",
        "releaseYear": 2023,
        "specs": {"case": "40mm / 44mm Vỏ nhôm Armor Aluminum viền mỏng", "screen": "Super AMOLED kính Sapphire", "chip": "Exynos W930", "battery": "300 / 425 mAh"},
        "supportedFaults": SMARTWATCH_FAULTS,
        "knownIssues": [
            {"id": "sapphire-scratch-gw6", "title": "Trầy xước mép viền nhôm sau khi cọ quẹt cửa", "severity": "low", "confidence": "high", "symptoms": "Mép viền cấn xước dăm.", "solution": "Đánh bóng phục hồi hoặc dán viền bezel bảo vệ."}
        ]
    },
    # Watch 5 Pro (W3)
    {
        "id": "samsung-galaxy-watch-5-pro",
        "name": "Galaxy Watch 5 Pro (45mm)",
        "brand": "samsung",
        "deviceType": "smartwatch",
        "tier": "W3",
        "releaseYear": 2022,
        "specs": {"case": "45mm Vỏ Titanium nguyên khối, gờ bảo vệ nhô cao", "screen": "Super AMOLED Sapphire", "battery": "590 mAh (pin trâu 80 giờ)", "charging": "10W sạc nhanh không dây"},
        "supportedFaults": SMARTWATCH_FAULTS,
        "knownIssues": [
            {"id": "d-buckle-magnetic-loose", "title": "Khóa hít nam châm D-Buckle bị lỏng ngàm gài sau 2 năm", "severity": "low", "confidence": "high", "symptoms": "Khóa dây dễ bung khi vận động mạnh.", "solution": "Thay ngàm lò xo khóa dây nam châm D-Buckle."}
        ]
    },
    {
        "id": "samsung-galaxy-watch-5",
        "name": "Galaxy Watch 5 (40mm / 44mm)",
        "brand": "samsung",
        "deviceType": "smartwatch",
        "tier": "W2",
        "releaseYear": 2022,
        "specs": {"case": "40mm / 44mm Vỏ nhôm", "screen": "Super AMOLED Sapphire", "chip": "Exynos W920", "battery": "284 / 410 mAh"},
        "supportedFaults": SMARTWATCH_FAULTS,
        "knownIssues": [
            {"id": "battery-aged-gw5", "title": "Pin tụt nhanh sau thời gian dài theo dõi giấc ngủ", "severity": "medium", "confidence": "high", "symptoms": "Pin dùng không trọn vẹn 1 ngày.", "solution": "Thay pin mới chính hãng Samsung bảo hành 6 tháng."}
        ]
    },
    # Watch 4 Series (W1)
    {
        "id": "samsung-galaxy-watch-4-classic",
        "name": "Galaxy Watch 4 Classic (42mm / 46mm)",
        "brand": "samsung",
        "deviceType": "smartwatch",
        "tier": "W1",
        "releaseYear": 2021,
        "specs": {"case": "42mm / 46mm Thép không gỉ viền xoay", "screen": "Super AMOLED Gorilla Glass DX", "chip": "Exynos W920 WearOS", "battery": "247 / 361 mAh"},
        "supportedFaults": SMARTWATCH_FAULTS,
        "knownIssues": [
            {"id": "bezel-ball-bearing-lost", "title": "Rơi mất bi đệm xoay vòng bezel khi va quệt mạnh", "severity": "medium", "confidence": "high", "symptoms": "Vòng xoay bị lỏng lẻo trượt tự do không có khấc nảy.", "solution": "Thay thế bộ 4 viên bi đệm và lò xo hồi vị bezel xoay."}
        ]
    },
    {
        "id": "samsung-galaxy-watch-4",
        "name": "Galaxy Watch 4 (40mm / 44mm)",
        "brand": "samsung",
        "deviceType": "smartwatch",
        "tier": "W1",
        "releaseYear": 2021,
        "specs": {"case": "40mm / 44mm Vỏ nhôm", "screen": "Super AMOLED", "chip": "Exynos W920", "battery": "247 / 361 mAh"},
        "supportedFaults": SMARTWATCH_FAULTS,
        "knownIssues": [
            {"id": "battery-swelling-gw4", "title": "Pin phồng đội hở màn hình hiển thị", "severity": "high", "confidence": "high", "symptoms": "Màn hình kênh hở mép viền.", "solution": "Thay pin mới và dán lại keo chống nước."}
        ]
    }
]

save("samsung", samsung_smartwatches)

# --- 3. GARMIN (5 models) ---
garmin_smartwatches = [
    {
        "id": "garmin-fenix-7-pro-solar",
        "name": "Garmin Fenix 7 Pro Sapphire Solar",
        "brand": "garmin",
        "deviceType": "smartwatch",
        "tier": "W3",
        "releaseYear": 2023,
        "specs": {"case": "47mm / 51mm Viền Titanium, nắp lưng thép/titan, đèn pin LED tích hợp", "screen": "1.3 inch MIP phản xạ ánh sáng mặt trời sạc Solar", "battery": "Pin lên đến 22 ngày ở chế độ Smartwatch", "water": "Kháng nước 10 ATM 100 mét"},
        "supportedFaults": SMARTWATCH_FAULTS,
        "knownIssues": [
            {"id": "solar-ring-charging-fade", "title": "Vòng thấu kính hấp thụ năng lượng mặt trời Power Sapphire bị ố mờ", "severity": "low", "confidence": "high", "symptoms": "Cường độ nạp năng lượng Solar Lux giảm sút sau khi tắm bùn lầy.", "solution": "Vệ sinh làm sạch bề mặt quang học kính Sapphire."},
            {"id": "button-double-click-fail", "title": "5 phím cơ học bấm bị sượng cát sau khi chạy địa hình Trail", "severity": "medium", "confidence": "high", "symptoms": "Nút Start/Stop bị dính phím hoặc mất tiếng click cơ học.", "solution": "Vệ sinh màng lò xo đệm cao su kín nước 5 nút bấm cơ."}
        ]
    },
    {
        "id": "garmin-epix-pro-gen-2",
        "name": "Garmin Epix Pro (Gen 2) Sapphire",
        "brand": "garmin",
        "deviceType": "smartwatch",
        "tier": "W3",
        "releaseYear": 2023,
        "specs": {"case": "42/47/51mm Viền Titanium", "screen": "1.4 inch AMOLED sắc nét, kính Sapphire", "sensor": "Cảm biến tim Elevate V5 có ECG", "battery": "Lên đến 31 ngày"},
        "supportedFaults": SMARTWATCH_FAULTS,
        "knownIssues": [
            {"id": "led-flashlight-burnt", "title": "Bóng đèn pin LED chiếu sáng tích hợp cạnh trên không sáng", "severity": "low", "confidence": "high", "symptoms": "Bấm kích hoạt đèn pin đôi không sáng.", "solution": "Thay module bóng LED chiếu sáng mini chính hãng Garmin."}
        ]
    },
    {
        "id": "garmin-forerunner-965",
        "name": "Garmin Forerunner 965",
        "brand": "garmin",
        "deviceType": "smartwatch",
        "tier": "W2",
        "releaseYear": 2023,
        "specs": {"case": "47mm Viền Titanium siêu nhẹ, thân polymer", "screen": "1.4 inch AMOLED cảm ứng sắc nét", "battery": "23 ngày Smartwatch, 31 giờ GPS"},
        "supportedFaults": SMARTWATCH_FAULTS,
        "knownIssues": [
            {"id": "charging-contact-pins-corrosion", "title": "4 chân tiếp xúc đồng cắm sạc mặt lưng bị rỉ xanh (Lỗi kinh điển của Garmin)", "severity": "high", "confidence": "high", "symptoms": "Cắm dây sạc kẹp hoặc dây tròn Garmin không nhận điện do mồ hôi ăn mòn chân đồng.", "solution": "Cạo sạch lớp oxy hóa mạ lại tiếp điểm đồng hoặc thay cụm nắp lưng cảm biến mới."}
        ]
    },
    {
        "id": "garmin-forerunner-265",
        "name": "Garmin Forerunner 265",
        "brand": "garmin",
        "deviceType": "smartwatch",
        "tier": "W2",
        "releaseYear": 2023,
        "specs": {"case": "42mm / 46mm Vỏ polymer gia cố sợi", "screen": "1.1 / 1.3 inch AMOLED", "battery": "13 ngày Smartwatch"},
        "supportedFaults": SMARTWATCH_FAULTS,
        "knownIssues": [
            {"id": "garmin-pin-rust-265", "title": "Oxy hóa chân sạc 4 chấu mặt lưng sau khi chạy bộ trời mưa", "severity": "high", "confidence": "high", "symptoms": "Sạc không vào điện.", "solution": "Làm sạch chân sạc tiếp xúc hoặc dùng nút bịt silicon bảo vệ."}
        ]
    },
    {
        "id": "garmin-venu-3",
        "name": "Garmin Venu 3",
        "brand": "garmin",
        "deviceType": "smartwatch",
        "tier": "W2",
        "releaseYear": 2023,
        "specs": {"case": "45mm Viền thép không gỉ, tích hợp loa & micro đàm thoại", "screen": "1.4 inch AMOLED", "battery": "14 ngày Smartwatch"},
        "supportedFaults": SMARTWATCH_FAULTS,
        "knownIssues": [
            {"id": "speaker-muffled-water", "title": "Loa ngoài bị nghẹt nước sau khi bơi hồ", "severity": "low", "confidence": "high", "symptoms": "Nghe gọi âm lượng nhỏ và rè.", "solution": "Dùng tính năng rung đẩy nước hoặc vệ sinh màng loa chống nước."}
        ]
    }
]

save("garmin", garmin_smartwatches)

# --- 4. OTHERS (5 models) ---
others_smartwatches = [
    {
        "id": "huawei-watch-gt-4",
        "name": "Huawei Watch GT 4 (46mm / 41mm)",
        "brand": "huawei",
        "deviceType": "smartwatch",
        "tier": "W2",
        "releaseYear": 2023,
        "specs": {"case": "46mm Thiết kế bát giác độc đáo bằng thép không gỉ", "screen": "1.43 inch AMOLED 466x466", "battery": "Pin 14 ngày", "charging": "Đế sạc không dây từ tính"},
        "supportedFaults": SMARTWATCH_FAULTS,
        "knownIssues": [
            {"id": "crown-rotary-sensor-lag", "title": "Nút vặn Digital Crown cuộn menu bị nhảy trễ nhịp", "severity": "low", "confidence": "high", "symptoms": "Xoay nút cuộn danh sách trôi không mượt.", "solution": "Cân chỉnh cảm biến encoder quang học nút xoay."}
        ]
    },
    {
        "id": "huawei-watch-ultimate",
        "name": "Huawei Watch Ultimate",
        "brand": "huawei",
        "deviceType": "smartwatch",
        "tier": "W3",
        "releaseYear": 2023,
        "specs": {"case": "Vật liệu kim loại lỏng Liquid Metal gốc Zirconium siêu bền, vành gốm Nanotech", "screen": "1.5 inch LTPO AMOLED Sapphire", "water": "Kháng nước 100m lặn sâu chuyên nghiệp"},
        "supportedFaults": SMARTWATCH_FAULTS,
        "knownIssues": [
            {"id": "ceramic-bezel-crack", "title": "Nứt vành gốm xanh biển Nanotech sau va chạm đá ngầm", "severity": "medium", "confidence": "high", "symptoms": "Vành gốm nứt mẻ gờ ngoài.", "solution": "Thay vành bezel gốm chính hãng Huawei."}
        ]
    },
    {
        "id": "amazfit-gtr-4",
        "name": "Amazfit GTR 4",
        "brand": "others",
        "deviceType": "smartwatch",
        "tier": "W1",
        "releaseYear": 2022,
        "specs": {"case": "Vỏ hợp kim nhôm tròn cổ điển", "screen": "1.43 inch AMOLED", "battery": "Pin 14 ngày (475 mAh)"},
        "supportedFaults": SMARTWATCH_FAULTS,
        "knownIssues": [
            {"id": "heart-rate-glass-scratch", "title": "Trầy xước mặt kính đáy cảm biến BioTracker", "severity": "low", "confidence": "high", "symptoms": "Mặt đáy nhựa xước dăm.", "solution": "Đánh bóng phục hồi mặt kính cảm biến."}
        ]
    },
    {
        "id": "xiaomi-watch-s3",
        "name": "Xiaomi Watch S3",
        "brand": "xiaomi",
        "deviceType": "smartwatch",
        "tier": "W1",
        "releaseYear": 2023,
        "specs": {"case": "Vỏ hợp kim nhôm, vòng bezel tháo rời thay thế phong cách", "screen": "1.43 inch AMOLED 60Hz", "battery": "Pin 15 ngày (486 mAh)"},
        "supportedFaults": SMARTWATCH_FAULTS,
        "knownIssues": [
            {"id": "interchangeable-bezel-loose", "title": "Vòng bezel thay đổi phong cách bị lỏng lẫy khóa nam châm", "severity": "low", "confidence": "high", "symptoms": "Vòng bezel dễ xoay tuột khỏi mặt đồng hồ.", "solution": "Thay ngàm nam châm định vị vòng bezel mới."}
        ]
    },
    {
        "id": "coros-pace-3",
        "name": "COROS Pace 3",
        "brand": "others",
        "deviceType": "smartwatch",
        "tier": "W1",
        "releaseYear": 2023,
        "specs": {"case": "Vỏ polymer siêu nhẹ chỉ 30g kèm dây nylon", "screen": "1.2 inch MIP phản xạ chống chói", "battery": "38 giờ GPS liên tục"},
        "supportedFaults": SMARTWATCH_FAULTS,
        "knownIssues": [
            {"id": "coros-digital-dial-stiff", "title": "Núm xoay Digital Dial bị cứng sau khi dính bùn đất chạy giải", "severity": "medium", "confidence": "high", "symptoms": "Núm xoay bị cộm cát sượng tay.", "solution": "Tháo vệ sinh tẩy bụi đất vòng đệm cao su núm xoay."}
        ]
    }
]

save("others", others_smartwatches)
print("=== HOÀN TẤT TẠO TOÀN BỘ 4 FILE CATALOG ĐỒNG HỒ THÔNG MINH ===")
