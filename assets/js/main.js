/**
 * FixNear - Main Client Logic
 * Chuẩn hóa quy trình tra cứu theo RepairBookings (Device -> Brand -> Model -> Repair)
 * Logo thương hiệu từ ảnh cục bộ; bộ lọc dòng máy (Series Tabs)
 */

// Trạng thái của Wizard tìm kiếm
let currentWizardState = {
    device: 'phone',
    brandId: '',
    brand: '',
    model: '',
    activeSeries: 'Tất cả',
    serviceId: null,
    issueName: ''
};

// Nhãn chọn hãng và nhóm thiết bị. Ảnh thương hiệu được lấy từ assets local khi có.
const FIXNEAR_BRANDS = {
    phone: [
        {
            id: "apple",
            name: "Apple",
        },
        {
            id: "samsung",
            name: "Samsung",
        },
        {
            id: "xiaomi",
            name: "Xiaomi",
        },
        {
            id: "oppo",
            name: "Oppo",
        },
        {
            id: "redmi",
            name: "Redmi",
        },
        {
            id: "vivo",
            name: "Vivo",
        },
        {
            id: "realme",
            name: "Realme",
        },
        {
            id: "google",
            name: "Google Pixel",
        },
        {
            id: "sony",
            name: "Sony",
        },
        {
            id: "huawei",
            name: "Huawei",
        },
        {
            id: "oneplus",
            name: "OnePlus",
        },
        {
            id: "poco",
            name: "Poco",
        },
        {
            id: "iqoo",
            name: "iQOO",
        },
        {
            id: "honor",
            name: "Honor",
        },
        {
            id: "nothing",
            name: "Nothing",
        },
        {
            id: "nokia",
            name: "Nokia",
        },
        {
            id: "motorola",
            name: "Motorola",
        },
        {
            id: "infinix",
            name: "Infinix",
        },
        {
            id: "tecno",
            name: "Tecno",
        },
        {
            id: "zte",
            name: "ZTE / Nubia",
        }
    ],
    laptop: [
        {
            id: "dell",
            name: "Dell",
        },
        {
            id: "asus",
            name: "Asus",
        },
        {
            id: "hp",
            name: "HP",
        },
        {
            id: "lenovo",
            name: "Lenovo",
        },
        {
            id: "acer",
            name: "Acer",
        },
        {
            id: "msi",
            name: "MSI",
        },
        {
            id: "lg",
            name: "LG (Gram)",
        },
        {
            id: "microsoft",
            name: "Microsoft (Surface)",
        },
        {
            id: "razer",
            name: "Razer",
        },
        {
            id: "samsung_pc",
            name: "Samsung (Galaxy Book)",
        },
        {
            id: "huawei_pc",
            name: "Huawei (MateBook)",
        },
        {
            id: "toshiba",
            name: "Toshiba (Dynabook)",
        }
    ],
    mac: [
        {
            id: "macbook_pro",
            name: "MacBook Pro",
        },
        {
            id: "macbook_air",
            name: "MacBook Air",
        },
        {
            id: "imac",
            name: "iMac",
        },
        {
            id: "mac_mini",
            name: "Mac Mini & Studio",
        },
        {
            id: "mac_pro",
            name: "Mac Pro",
        }
    ],
    tablet: [
        {
            id: "apple_ipad",
            name: "Apple iPad",
        },
        {
            id: "samsung_tab",
            name: "Samsung Galaxy Tab",
        },
        {
            id: "xiaomi_pad",
            name: "Xiaomi Pad",
        },
        {
            id: "surface_pro",
            name: "Surface Pro",
        },
        {
            id: "lenovo_tab",
            name: "Lenovo Tab",
        },
        {
            id: "huawei_matepad",
            name: "Huawei MatePad",
        },
        {
            id: "oppo_pad",
            name: "Oppo Pad",
        }
    ],
    smartwatch: [
        {
            id: "apple_watch",
            name: "Apple Watch",
        },
        {
            id: "samsung_watch",
            name: "Galaxy Watch",
        },
        {
            id: "garmin",
            name: "Garmin",
        },
        {
            id: "huawei_watch",
            name: "Huawei Watch",
        },
        {
            id: "google_watch",
            name: "Pixel Watch",
        }
    ],
    pc: [
        {
            id: "gaming_pc",
            name: "PC Gaming",
        },
        {
            id: "graphic_pc",
            name: "PC Đồ Họa 3D",
        },
        {
            id: "dell_optiplex",
            name: "Dell Optiplex",
        },
        {
            id: "hp_prodesk",
            name: "HP ProDesk",
        },
        {
            id: "lenovo_thinkcentre",
            name: "Lenovo ThinkCentre",
        }
    ]
};

// Từ điển phân nhóm Dòng máy (Series Tabs) cho trải nghiệm UI/UX mượt mà
const FIXNEAR_SERIES_BY_BRAND = {
    lenovo: ["Tất cả", "Legion Gaming", "ThinkPad", "IdeaPad", "LOQ", "Yoga", "ThinkBook"],
    dell: ["Tất cả", "XPS", "Inspiron", "Latitude", "Vostro", "Gaming G15 / Alienware"],
    asus: ["Tất cả", "TUF Gaming", "ROG Strix / Zephyrus", "Vivobook", "Zenbook", "ExpertBook"],
    hp: ["Tất cả", "Victus / Omen Gaming", "Pavilion", "Envy", "ProBook", "EliteBook"],
    acer: ["Tất cả", "Nitro Gaming", "Aspire", "Predator", "Swift"],
    msi: ["Tất cả", "Katana / Bravo", "Cyborg / Thin", "Stealth / Modern"],
    apple: ["Tất cả", "iPhone 16", "iPhone 15", "iPhone 14", "iPhone 13", "iPhone 12/11/X"],
    samsung: ["Tất cả", "Galaxy S Series", "Galaxy Z Fold/Flip", "Galaxy A Series", "Galaxy Note"],
    xiaomi: ["Tất cả", "Xiaomi Flagship", "Redmi Note", "Redmi Phổ thông"],
    macbook_pro: ["Tất cả", "M3", "M2", "M1", "Intel"],
    macbook_air: ["Tất cả", "M3", "M2", "M1", "Retina"],
    imac: ["Tất cả", "M3", "M1", "Intel"],
    mac_mini: ["Tất cả", "M2", "M1", "Studio", "Intel"],
    apple_ipad: ["Tất cả", "iPad Pro", "iPad Air", "iPad Gen", "iPad Mini"],
    samsung_tab: ["Tất cả", "Galaxy Tab S", "Galaxy Tab A"]
};

// Từ điển Model chi tiết theo Từng Thương Hiệu
const FIXNEAR_MODELS_BY_BRAND = {
    phone: {
        apple: [
            "iPhone 16 Pro Max", "iPhone 16 Pro", "iPhone 16 Plus", "iPhone 16",
            "iPhone 15 Pro Max", "iPhone 15 Pro", "iPhone 15 Plus", "iPhone 15",
            "iPhone 14 Pro Max", "iPhone 14 Pro", "iPhone 14 Plus", "iPhone 14",
            "iPhone 13 Pro Max", "iPhone 13 Pro", "iPhone 13", "iPhone 13 mini",
            "iPhone 12 Pro Max", "iPhone 12 Pro", "iPhone 12", "iPhone 12 mini",
            "iPhone 11 Pro Max", "iPhone 11 Pro", "iPhone 11",
            "iPhone XS Max", "iPhone XS", "iPhone XR", "iPhone X",
            "iPhone 8 Plus", "iPhone 8", "iPhone SE (2022)"
        ],
        samsung: [
            "Galaxy S24 Ultra", "Galaxy S24+", "Galaxy S24",
            "Galaxy S23 Ultra", "Galaxy S23+", "Galaxy S23", "Galaxy S23 FE",
            "Galaxy S22 Ultra", "Galaxy S22+", "Galaxy S22",
            "Galaxy S21 Ultra", "Galaxy S21+", "Galaxy S21", "Galaxy S21 FE",
            "Galaxy Z Fold 6", "Galaxy Z Flip 6", "Galaxy Z Fold 5", "Galaxy Z Flip 5",
            "Galaxy Z Fold 4", "Galaxy Z Flip 4",
            "Galaxy Note 20 Ultra 5G", "Galaxy Note 20", "Galaxy Note 10+", "Galaxy Note 10",
            "Galaxy A55 5G", "Galaxy A35 5G", "Galaxy A25 5G", "Galaxy A15 5G",
            "Galaxy A54 5G", "Galaxy A34 5G", "Galaxy A24", "Galaxy A14",
            "Galaxy A73 5G", "Galaxy A53 5G"
        ],
        xiaomi: [
            "Xiaomi 14 Ultra", "Xiaomi 14 Pro", "Xiaomi 14",
            "Xiaomi 13T Pro", "Xiaomi 13T", "Xiaomi 13 Ultra", "Xiaomi 13 Pro", "Xiaomi 13",
            "Xiaomi 12 Pro", "Xiaomi 12T Pro", "Xiaomi 12T", "Xiaomi 12",
            "Xiaomi 11T Pro", "Xiaomi 11T"
        ],
        redmi: [
            "Redmi Note 13 Pro+ 5G", "Redmi Note 13 Pro", "Redmi Note 13",
            "Redmi Note 12 Pro 5G", "Redmi Note 12 Pro 4G", "Redmi Note 12",
            "Redmi Note 11 Pro", "Redmi Note 11", "Redmi 13C", "Redmi 12", "Redmi 10C"
        ],
        oppo: [
            "Oppo Find X7 Ultra", "Oppo Find N3 Flip", "Oppo Find N3", "Oppo Find X6 Pro",
            "Oppo Reno 12 Pro 5G", "Oppo Reno 12 5G", "Oppo Reno 12F",
            "Oppo Reno 11 Pro 5G", "Oppo Reno 11 5G", "Oppo Reno 10 5G",
            "Oppo A78", "Oppo A58", "Oppo A38", "Oppo A18"
        ],
        vivo: [
            "Vivo X100 Pro", "Vivo X100", "Vivo X90 Pro",
            "Vivo V30 Pro 5G", "Vivo V30 5G", "Vivo V30e",
            "Vivo V29 5G", "Vivo V27 5G", "Vivo Y36", "Vivo Y27", "Vivo Y17s"
        ],
        realme: [
            "Realme GT 5 Pro", "Realme 12 Pro+ 5G", "Realme 12+ 5G", "Realme 12 5G",
            "Realme 11 Pro+ 5G", "Realme 11", "Realme C67", "Realme C55", "Realme C53"
        ],
        google: [
            "Pixel 9 Pro XL", "Pixel 9 Pro", "Pixel 9",
            "Pixel 8 Pro", "Pixel 8", "Pixel 8a",
            "Pixel 7 Pro", "Pixel 7", "Pixel 7a",
            "Pixel 6 Pro", "Pixel 6"
        ],
        sony: [
            "Xperia 1 VI", "Xperia 1 V", "Xperia 1 IV",
            "Xperia 5 V", "Xperia 5 IV", "Xperia 10 VI", "Xperia 10 V"
        ],
        oneplus: [
            "OnePlus 12", "OnePlus 12R", "OnePlus 11", "OnePlus 10 Pro",
            "OnePlus Nord 3 5G", "OnePlus Nord CE 3"
        ],
        poco: [
            "Poco F6 Pro", "Poco F6", "Poco X6 Pro 5G", "Poco X6 5G", "Poco M6 Pro", "Poco F5"
        ],
        huawei: [
            "Huawei Pura 70 Ultra", "Huawei Pura 70 Pro", "Huawei Mate 60 Pro", "Huawei P60 Pro"
        ],
        iqoo: ["iQOO 12 Pro", "iQOO 12", "iQOO Neo 9 Pro", "iQOO Z9 Turbo"],
        nothing: ["Nothing Phone (2)", "Nothing Phone (2a)", "Nothing Phone (1)"],
        motorola: ["Moto Edge 50 Pro", "Moto Razr 50 Ultra", "Moto G84 5G"],
        nokia: ["Nokia G42 5G", "Nokia C32", "Nokia G22", "Nokia X30 5G"],
        honor: ["Honor Magic 6 Pro", "Honor 200 Pro", "Honor 90 5G"],
        infinix: ["Infinix GT 20 Pro", "Infinix Note 40 Pro", "Infinix Hot 40 Pro"],
        tecno: ["Tecno Camon 30 Pro 5G", "Tecno Pova 6 Pro", "Tecno Spark 20 Pro+"],
        zte: ["Nubia RedMagic 9 Pro+", "Nubia RedMagic 9 Pro", "Nubia Z60 Ultra"]
    },
    laptop: {
        dell: [
            "Dell XPS 13 Plus (9320)", "Dell XPS 13 (9315)", "Dell XPS 15 (9530)", "Dell XPS 17 (9730)",
            "Dell Inspiron 15 3520", "Dell Inspiron 14 5430", "Dell Inspiron 16 5630",
            "Dell Vostro 3520", "Dell Vostro 3420", "Dell Vostro 5630",
            "Dell Latitude 7420", "Dell Latitude 5430", "Dell Latitude 3440",
            "Dell G15 Gaming 5530", "Dell G15 Gaming 5520", "Dell G16 Gaming 7630",
            "Dell Alienware m16 R2", "Dell Alienware x14"
        ],
        asus: [
            "Asus TUF Gaming F15 (FX506)", "Asus TUF Gaming A15 (FA506)", "Asus TUF Dash F15",
            "Asus ROG Strix G16 (G614)", "Asus ROG Strix G15 (G513)", "Asus ROG Strix SCAR 16",
            "Asus ROG Zephyrus G14", "Asus ROG Zephyrus G16", "Asus ROG Flow X13",
            "Asus Vivobook 15 X1504", "Asus Vivobook 14 OLED", "Asus Vivobook Pro 15",
            "Asus Zenbook 14 OLED", "Asus Zenbook Duo", "Asus ExpertBook B1"
        ],
        hp: [
            "HP Pavilion 15 (eg series)", "HP Pavilion 14 (dv series)", "HP Pavilion Aero 13",
            "HP Victus 16 Gaming", "HP Victus 15 Gaming", "HP Omen 16", "HP Omen Transcend 14",
            "HP Envy x360 14", "HP Envy 16",
            "HP ProBook 450 G10", "HP ProBook 440 G10", "HP ProBook 450 G9",
            "HP EliteBook 840 G9", "HP EliteBook 840 G8", "HP 15s series"
        ],
        lenovo: [
            "Lenovo Legion 5 Pro (16IRX8)", "Lenovo Legion 5 15", "Lenovo Legion Slim 5", "Lenovo Legion 7 Pro",
            "Lenovo LOQ 15IRH8", "Lenovo LOQ 15APH8", "Lenovo IdeaPad Gaming 3",
            "Lenovo IdeaPad 3 15", "Lenovo IdeaPad 3 14", "Lenovo IdeaPad Slim 5 16", "Lenovo IdeaPad Slim 3",
            "Lenovo ThinkPad T14 Gen 4", "Lenovo ThinkPad T14 Gen 3", "Lenovo ThinkPad T480 / T490",
            "Lenovo ThinkPad X1 Carbon Gen 11", "Lenovo ThinkPad X1 Carbon Gen 10", "Lenovo ThinkPad E14 / E15",
            "Lenovo Yoga Slim 7 Pro", "Lenovo Yoga 7 14", "Lenovo ThinkBook 14 G6", "Lenovo ThinkBook 15 G5"
        ],
        acer: [
            "Acer Nitro 5 Tiger (AN515-58)", "Acer Nitro 5 (AN515-57)", "Acer Nitro V 15 (ANV15-51)",
            "Acer Aspire 7 Gaming (A715)", "Acer Aspire 5 (A515-58)", "Acer Aspire 3",
            "Acer Predator Helios 300", "Acer Predator Helios Neo 16", "Acer Swift Go 14"
        ],
        msi: [
            "MSI Bravo 15 (B7ED)", "MSI Katana 15 (B13V)", "MSI Cyborg 15 (A12V)",
            "MSI Thin GF63 (12VE)", "MSI Modern 14 (C12M)", "MSI Stealth 16 Studio"
        ],
        microsoft: [
            "Surface Laptop 6 (2024)", "Surface Laptop 5", "Surface Laptop 4",
            "Surface Laptop Studio 2", "Surface Pro 9", "Surface Pro 8"
        ],
        razer: [
            "Razer Blade 16 (2024)", "Razer Blade 15", "Razer Blade 14", "Razer Blade 18"
        ],
        lg: [
            "LG Gram 17 (2024)", "LG Gram 16 (2023)", "LG Gram 14 SuperSlim"
        ],
        samsung_pc: [
            "Galaxy Book4 Ultra", "Galaxy Book4 Pro 360", "Galaxy Book3 360"
        ],
        huawei_pc: [
            "Huawei MateBook X Pro", "Huawei MateBook 16s", "Huawei MateBook D15"
        ],
        toshiba: [
            "Dynabook Portégé X30L", "Dynabook Tecra A40", "Toshiba Satellite Pro C50"
        ]
    },
    mac: {
        macbook_pro: [
            "MacBook Pro 16\" M3 Max (2023)", "MacBook Pro 14\" M3 Pro (2023)", "MacBook Pro 14\" M3 (2023)",
            "MacBook Pro 16\" M2 (2023)", "MacBook Pro 14\" M2 (2023)", "MacBook Pro 13\" M2 (2022)",
            "MacBook Pro 16\" M1 (2021)", "MacBook Pro 14\" M1 (2021)", "MacBook Pro 13\" M1 (2020)",
            "MacBook Pro 13\" 2020 Intel", "MacBook Pro 16\" 2019 Intel", "MacBook Pro 15\" 2018-2019"
        ],
        macbook_air: [
            "MacBook Air 15\" M3 (2024)", "MacBook Air 13\" M3 (2024)",
            "MacBook Air 15\" M2 (2023)", "MacBook Air 13\" M2 (2022)",
            "MacBook Air 13\" M1 (2020)", "MacBook Air 13\" Retina (2018-2020)"
        ],
        imac: [
            "iMac 24\" M3 (2023)", "iMac 24\" M1 (2021)", "iMac 27\" Retina 5K (2020)", "iMac 21.5\" 4K (2019)"
        ],
        mac_mini: [
            "Mac Mini M2 Pro (2023)", "Mac Mini M2", "Mac Mini M1 (2020)", "Mac Mini Intel (2018)",
            "Mac Studio M2 Ultra (2023)", "Mac Studio M1 Max/Ultra"
        ],
        mac_pro: [
            "Mac Pro Apple Silicon (2023)", "Mac Pro Tower (2019)"
        ]
    },
    tablet: {
        apple_ipad: [
            "iPad Pro 13\" M4 (2024)", "iPad Pro 11\" M4 (2024)",
            "iPad Pro 12.9\" M2 (2022)", "iPad Pro 11\" M2 (2022)", "iPad Pro 12.9\" M1",
            "iPad Air 13\" M2 (2024)", "iPad Air 11\" M2 (2024)", "iPad Air 5 M1 (2022)", "iPad Air 4",
            "iPad Gen 10 10.9\" (2022)", "iPad Gen 9 10.2\" (2021)", "iPad Mini 6 (2021)"
        ],
        samsung_tab: [
            "Galaxy Tab S10 Ultra", "Galaxy Tab S10+",
            "Galaxy Tab S9 Ultra", "Galaxy Tab S9+", "Galaxy Tab S9", "Galaxy Tab S9 FE+",
            "Galaxy Tab S8 Ultra", "Galaxy Tab S8+", "Galaxy Tab S8",
            "Galaxy Tab A9+", "Galaxy Tab A9", "Galaxy Tab A8 10.5"
        ],
        xiaomi_pad: [
            "Xiaomi Pad 6S Pro 12.4", "Xiaomi Pad 6 Pro", "Xiaomi Pad 6",
            "Redmi Pad Pro 5G", "Redmi Pad SE"
        ],
        surface_pro: [
            "Surface Pro 10 (2024)", "Surface Pro 9", "Surface Pro 8", "Surface Go 4"
        ],
        lenovo_tab: [
            "Lenovo Tab Extreme 14.5", "Lenovo Tab P12 Pro", "Lenovo Tab M10 Plus Gen 3"
        ],
        huawei_matepad: [
            "Huawei MatePad Pro 13.2", "Huawei MatePad 11.5", "Huawei MatePad SE 11"
        ],
        oppo_pad: [
            "Oppo Pad 2", "Oppo Pad Air 2", "Oppo Pad Neo"
        ]
    },
    smartwatch: {
        apple_watch: [
            "Apple Watch Ultra 2 (49mm)", "Apple Watch Ultra",
            "Apple Watch Series 9 (45mm/41mm)", "Apple Watch Series 8", "Apple Watch Series 7",
            "Apple Watch SE 2 (44mm/40mm)", "Apple Watch SE (2020)"
        ],
        samsung_watch: [
            "Galaxy Watch Ultra (47mm)", "Galaxy Watch 7 (44mm/40mm)",
            "Galaxy Watch 6 Classic", "Galaxy Watch 6", "Galaxy Watch 5 Pro", "Galaxy Watch 4"
        ],
        garmin: [
            "Garmin Fenix 7 Pro", "Garmin Epix Gen 2", "Garmin Forerunner 965", "Garmin Forerunner 265",
            "Garmin Venu 3", "Garmin Instinct 2X"
        ],
        huawei_watch: [
            "Huawei Watch Ultimate", "Huawei Watch 4 Pro", "Huawei Watch GT 4", "Huawei Watch Fit 3"
        ],
        google_watch: [
            "Pixel Watch 3 (45mm)", "Pixel Watch 3 (41mm)", "Pixel Watch 2", "Pixel Watch"
        ]
    },
    pc: {
        gaming_pc: [
            "PC Gaming Core i5 13400F / RTX 3060", "PC Gaming Core i5 14400F / RTX 4060",
            "PC Gaming Core i7 13700K / RTX 4070 Super", "PC Gaming Core i9 14900K / RTX 4080",
            "PC Gaming Ryzen 5 5600 / RX 6600", "PC Gaming Ryzen 7 7800X3D / RTX 4070"
        ],
        graphic_pc: [
            "PC Render 3ds Max / V-Ray Ryzen 9 7950X", "PC Đồ Họa Core i9 14900K / RTX 4090",
            "PC Dựng Phim Premiere 4K Core i7 / 64GB", "PC Chạy Giả Lập Nox Dual Xeon"
        ],
        dell_optiplex: [
            "Dell Optiplex 7090 Micro / SFF", "Dell Optiplex 7080 / 5080", "Dell Precision 3660 Workstation"
        ],
        hp_prodesk: [
            "HP ProDesk 400 G9 SFF", "HP EliteDesk 800 G9 Mini", "HP Z2 G9 Workstation Tower"
        ],
        lenovo_thinkcentre: [
            "Lenovo ThinkCentre M70q Tiny", "Lenovo ThinkStation P360 Tower"
        ]
    }
};

// Từ điển dịch vụ sửa chữa chuyên biệt theo từng thiết bị (Chống bành trướng lĩnh vực, chuẩn 100%)
const FIXNEAR_REPAIRS_BY_DEVICE = {
    phone: [
        { id: 1, icon: "🖥️", title: "Thay màn hình hiển thị", desc: "Màn sọc chỉ, chảy mực đốm đen, liệt cảm ứng hoặc tối đen", price: "Cần cửa hàng kiểm tra và xác nhận" },
        { id: 2, icon: "💎", title: "Ép mặt kính / Kính cảm ứng", desc: "Màn cảm ứng & hiển thị tốt, chỉ nứt vỡ mặt kính bảo vệ bên ngoài", price: "Cần cửa hàng kiểm tra và xác nhận" },
        { id: 3, icon: "🔋", title: "Thay pin dung lượng cao / Pin Zin", desc: "Khắc phục pin chai dưới 80%, phồng nắp lưng, sạc không vào", price: "Cần cửa hàng kiểm tra và xác nhận" },
        { id: 4, icon: "⚡", title: "Sửa chân sạc / Cổng kết nối", desc: "Khắc phục lỏng chân Type-C, Lightning, sạc chập chờn", price: "Cần cửa hàng kiểm tra và xác nhận" },
        { id: 5, icon: "💧", title: "Cấp cứu máy vô nước / Rớt nước", desc: "Sấy khô sóng siêu âm, tẩy rỉ bo mạch, cứu sống linh kiện", price: "Cần cửa hàng kiểm tra và xác nhận" },
        { id: 6, icon: "⚠️", title: "Sửa mất nguồn / Lỗi Mainboard", desc: "Chết IC nguồn, chập tụ, kích nguồn không chạy, treo logo", price: "Cần cửa hàng kiểm tra và xác nhận" },
        { id: 7, icon: "📷", title: "Thay camera trước / sau", desc: "Camera mờ, rung giật OIS, đen thui hoặc nứt kính camera ngoài", price: "Cần cửa hàng kiểm tra và xác nhận" },
        { id: 8, icon: "🔊", title: "Loa trong / Loa ngoài rè", desc: "Loa thoại bé, loa ngoài rè chói tai khi nghe nhạc hay đàm thoại", price: "Cần cửa hàng kiểm tra và xác nhận" },
        { id: 9, icon: "🎙️", title: "Hỏng Micro (Nói không nghe)", desc: "Gọi điện thoại, quay video hoặc gửi tin nhắn thoại không thu tiếng", price: "Cần cửa hàng kiểm tra và xác nhận" },
        { id: 12, icon: "🛡️", title: "Thay khung sườn / Kính lưng", desc: "Nắn sườn kim loại cấn móp, thay vỏ sườn mới, thay nắp kính lưng", price: "Cần cửa hàng kiểm tra và xác nhận" },
        { id: 16, icon: "⚙️", title: "Chạy lại phần mềm / Cài đặt", desc: "Treo táo, treo logo bootloop, khôi phục cài đặt gốc, mở khóa sạch", price: "Cần cửa hàng kiểm tra và xác nhận" },
        { id: 17, icon: "📂", title: "Cứu dữ liệu bộ nhớ & Bo mạch", desc: "Phục hồi ảnh, danh bạ, dữ liệu quan trọng từ máy chết nguồn vô nước", price: "Cần cửa hàng kiểm tra và xác nhận" },
        { id: 18, icon: "🔍", title: "Kiểm tra chẩn đoán toàn diện", desc: "Kỹ thuật viên đo đạc linh kiện, test chuyên sâu & báo giá trước", price: "Cần cửa hàng kiểm tra và xác nhận" }
    ],
    laptop: [
        { id: 1, icon: "🖥️", title: "Thay màn hình laptop hiển thị", desc: "Màn hình nứt vỡ, sọc kẻ, nhấp nháy, tối mờ, hở sáng", price: "Cần cửa hàng kiểm tra và xác nhận" },
        { id: 10, icon: "⌨️", title: "Bàn phím liệt / Kẹt nút bấm", desc: "Thay bàn phím mới, xử lý liệt phím, kẹt phím, chập nhảy chữ", price: "Cần cửa hàng kiểm tra và xác nhận" },
        { id: 3, icon: "🔋", title: "Thay pin laptop chính hãng", desc: "Chai pin dùng dưới 1h, phồng pin đội bàn rê, báo lỗi pin", price: "Cần cửa hàng kiểm tra và xác nhận" },
        { id: 4, icon: "⚡", title: "Sửa chân sạc / Cổng nguồn DC", desc: "Lỏng chân Type-C, gãy chân kim DC Jack, cắm sạc chập chờn", price: "Cần cửa hàng kiểm tra và xác nhận" },
        { id: 12, icon: "🗜️", title: "Sửa gãy bản lề / Vỏ máy", desc: "Hàn gia cố chân ốc bản lề bung gãy, phục hồi vỏ mặt A/B/C/D", price: "Cần cửa hàng kiểm tra và xác nhận" },
        { id: 13, icon: "💾", title: "Nâng cấp ổ cứng SSD siêu tốc", desc: "Kiểm tra khả năng nâng cấp SSD NVMe / SATA theo model", price: "Cần cửa hàng kiểm tra và xác nhận" },
        { id: 14, icon: "🧠", title: "Nâng cấp bộ nhớ RAM", desc: "Nâng RAM DDR4/DDR5 8GB/16GB/32GB mở mượt tab Chrome & đồ họa", price: "Cần cửa hàng kiểm tra và xác nhận" },
        { id: 15, icon: "💨", title: "Vệ sinh máy & Tra keo tản nhiệt", desc: "Làm sạch quạt gió, tra keo tản nhiệt MX-4/Kryonaut làm mát sâu", price: "Cần cửa hàng kiểm tra và xác nhận" },
        { id: 11, icon: "🖱️", title: "Chuột cảm ứng / Trackpad", desc: "Bàn rê chuột bị đơ, loạn cảm ứng hoặc kẹt cứng không bấm được", price: "Cần cửa hàng kiểm tra và xác nhận" },
        { id: 5, icon: "💧", title: "Cấp cứu laptop vô nước", desc: "Đổ cà phê/nước vào phím, sấy khô siêu âm tẩy rỉ bo mạch", price: "Cần cửa hàng kiểm tra và xác nhận" },
        { id: 6, icon: "⚠️", title: "Sửa mất nguồn / Lỗi Mainboard", desc: "Chết IC nguồn, chập tụ, kích không lên, quạt không quay", price: "Cần cửa hàng kiểm tra và xác nhận" },
        { id: 16, icon: "💻", title: "Cài Win / Phần mềm văn phòng", desc: "Cài Win 10/11 sạch, cài full Office, Photoshop, AutoCad, VS Code", price: "Cần cửa hàng kiểm tra và xác nhận" },
        { id: 17, icon: "📂", title: "Cứu dữ liệu ổ cứng & Bo mạch", desc: "Phục hồi đồ án, tài liệu quan trọng từ máy chết nguồn, bad ổ", price: "Cần cửa hàng kiểm tra và xác nhận" },
        { id: 18, icon: "🔍", title: "Kiểm tra chẩn đoán toàn diện", desc: "Kỹ thuật viên đo đạc linh kiện, test máy & báo giá công khai trước", price: "Cần cửa hàng kiểm tra và xác nhận" }
    ],
    mac: [
        { id: 1, icon: "🖥️", title: "Thay màn hình Retina / Cáp hiển thị", desc: "Màn sọc, nứt kính, vỡ tấm nền Retina, lỗi flexgate cáp màn", price: "Cần cửa hàng kiểm tra và xác nhận" },
        { id: 3, icon: "🔋", title: "Thay pin MacBook chuẩn Apple", desc: "Pin chai, phồng đội nắp đáy, thông báo 'Service Recommended'", price: "Cần cửa hàng kiểm tra và xác nhận" },
        { id: 10, icon: "⌨️", title: "Thay bàn phím / Keycap MacBook", desc: "Liệt phím cánh bướm / Magic Keyboard, kẹt phím, chập nhảy chữ", price: "Cần cửa hàng kiểm tra và xác nhận" },
        { id: 11, icon: "🖱️", title: "Chuột Trackpad Force Touch", desc: "Liệt cảm ứng lực Force Touch, bàn rê nứt kính không click được", price: "Cần cửa hàng kiểm tra và xác nhận" },
        { id: 4, icon: "⚡", title: "Sửa cổng sạc MagSafe / Type-C", desc: "Sạc không vào, chập chân MagSafe 3, lỏng cổng Thunderbolt", price: "Cần cửa hàng kiểm tra và xác nhận" },
        { id: 6, icon: "⚠️", title: "Sửa nguồn & Logic Board", desc: "Chập tụ, chết IC CD3217, treo cáp treo táo, mất nguồn máy", price: "Cần cửa hàng kiểm tra và xác nhận" },
        { id: 12, icon: "🗜️", title: "Sửa gãy bản lề / Cân nắp máy", desc: "Bản lề rão lỏng lẻo, nắp gập kêu cót két, cấn móp vỏ nhôm", price: "Cần cửa hàng kiểm tra và xác nhận" },
        { id: 15, icon: "💨", title: "Vệ sinh máy & Tra keo tản nhiệt", desc: "Tháo bụi quạt tản nhiệt, tra keo gốm làm mát chip Apple Silicon", price: "Cần cửa hàng kiểm tra và xác nhận" },
        { id: 16, icon: "💻", title: "Cài đặt macOS & Khôi phục máy", desc: "Cài macOS Sonoma/Sequoia sạch, cài full Adobe, Office Mac", price: "Cần cửa hàng kiểm tra và xác nhận" },
        { id: 5, icon: "💧", title: "Cấp cứu MacBook vô nước", desc: "Sấy khô sóng siêu âm chống oxy hóa chip nhớ và linh kiện", price: "Cần cửa hàng kiểm tra và xác nhận" },
        { id: 17, icon: "📂", title: "Cứu dữ liệu chip nhớ NAND", desc: "Cứu tài liệu trên main Mac chết nguồn bảo mật tuyệt đối", price: "Cần cửa hàng kiểm tra và xác nhận" },
        { id: 18, icon: "🔍", title: "Kiểm tra chẩn đoán Apple Diagnostics", desc: "Đề nghị kỹ thuật viên chạy công cụ chẩn đoán phù hợp và giải thích kết quả", price: "Cần cửa hàng kiểm tra và xác nhận" }
    ],
    tablet: [
        { id: 1, icon: "🖥️", title: "Thay màn hình hiển thị LCD/OLED", desc: "Màn sọc kẻ, đốm đen, liệt cảm ứng hoặc tối mờ", price: "Cần cửa hàng kiểm tra và xác nhận" },
        { id: 2, icon: "💎", title: "Ép mặt kính / Kính cảm ứng", desc: "Màn cảm ứng & hiển thị tốt, chỉ nứt vỡ mặt kính bảo vệ ngoài", price: "Cần cửa hàng kiểm tra và xác nhận" },
        { id: 3, icon: "🔋", title: "Thay pin dung lượng cao / Pin Zin", desc: "Chai pin dùng nhanh hết, phồng pin đội màn hình cong vênh", price: "Cần cửa hàng kiểm tra và xác nhận" },
        { id: 4, icon: "⚡", title: "Sửa chân sạc Type-C / Lightning", desc: "Cắm sạc lỏng, chập chờn, không nhận phụ kiện kết nối", price: "Cần cửa hàng kiểm tra và xác nhận" },
        { id: 5, icon: "💧", title: "Cấp cứu máy vô nước / Rớt nước", desc: "Sấy khô siêu âm bo mạch, xử lý chạm chập linh kiện", price: "Cần cửa hàng kiểm tra và xác nhận" },
        { id: 6, icon: "⚠️", title: "Sửa mất nguồn / Lỗi Bo mạch", desc: "Chết IC nguồn, chập đường áp, máy nóng ran không khởi động", price: "Cần cửa hàng kiểm tra và xác nhận" },
        { id: 7, icon: "📷", title: "Thay camera / Cảm biến Face ID", desc: "Camera đục mờ, mất cảm biến Face ID hoặc rung giật", price: "Cần cửa hàng kiểm tra và xác nhận" },
        { id: 8, icon: "🔊", title: "Loa trong / Loa ngoài rè", desc: "Loa stereo rè, mất tiếng khi xem phim nghe nhạc", price: "Cần cửa hàng kiểm tra và xác nhận" },
        { id: 12, icon: "🛡️", title: "Nắn vỏ nhôm cong vênh / Cấn góc", desc: "Khung nhôm iPad bị cong do tì đè, nắn phẳng chuẩn khít", price: "Cần cửa hàng kiểm tra và xác nhận" },
        { id: 16, icon: "⚙️", title: "Cài đặt hệ điều hành / Mở khóa", desc: "Treo táo, kẹt recovery DFU, khôi phục cài đặt gốc sạch", price: "Cần cửa hàng kiểm tra và xác nhận" },
        { id: 17, icon: "📂", title: "Cứu dữ liệu bộ nhớ & Bo mạch", desc: "Phục hồi tài liệu, ảnh, ghi chú từ tablet chết nguồn", price: "Cần cửa hàng kiểm tra và xác nhận" },
        { id: 18, icon: "🔍", title: "Kiểm tra chẩn đoán toàn diện", desc: "Kiểm tra đo đạc linh kiện chuyên sâu & báo giá công khai", price: "Cần cửa hàng kiểm tra và xác nhận" }
    ],
    smartwatch: [
        { id: 1, icon: "🖥️", title: "Thay màn hình OLED Retina", desc: "Màn hình nứt vỡ tấm nền trong, sọc chỉ hoặc tối thui", price: "Cần cửa hàng kiểm tra và xác nhận" },
        { id: 2, icon: "💎", title: "Ép kính màn hình / Sapphire", desc: "Nứt vỡ kính bảo vệ ngoài, cảm ứng vuốt chạm vẫn nhạy", price: "Cần cửa hàng kiểm tra và xác nhận" },
        { id: 3, icon: "🔋", title: "Thay pin đồng hồ thông minh", desc: "Pin chai dùng chưa đến nửa ngày, pin phù đội bung mặt kính", price: "Cần cửa hàng kiểm tra và xác nhận" },
        { id: 4, icon: "⚡", title: "Sửa sạc không dây / Đế sạc", desc: "Đặt lên đế sạc không nhận điện, đồng hồ nóng ran", price: "Cần cửa hàng kiểm tra và xác nhận" },
        { id: 5, icon: "💧", title: "Cấp cứu đồng hồ vô nước", desc: "Rung giật liên tục sau khi bơi lội, sấy khô siêu âm", price: "Cần cửa hàng kiểm tra và xác nhận" },
        { id: 12, icon: "🛡️", title: "Thay vỏ titan/nhôm & Ngàm dây", desc: "Móp méo viền vỏ, trầy xước cạnh, gãy chốt cài ngàm dây đeo", price: "Cần cửa hàng kiểm tra và xác nhận" },
        { id: 6, icon: "⚠️", title: "Sửa mất nguồn / Bo mạch vi mô", desc: "Đồng hồ mất nguồn hoàn toàn, treo logo Apple/Galaxy", price: "Cần cửa hàng kiểm tra và xác nhận" },
        { id: 8, icon: "🔊", title: "Loa rè / Mất âm thanh đàm thoại", desc: "Rè loa ngoài khi nghe gọi trực tiếp trên đồng hồ", price: "Cần cửa hàng kiểm tra và xác nhận" },
        { id: 18, icon: "🔍", title: "Kiểm tra chẩn đoán toàn diện", desc: "Đo đạc kiểm tra áp suất chống nước, báo giá trước", price: "Cần cửa hàng kiểm tra và xác nhận" }
    ],
    pc: [
        { id: 6, icon: "⚠️", title: "Sửa mất nguồn / Lỗi Mainboard PC", desc: "Chết tụ, chập nguồn PSU, máy không kích nguồn được", price: "Cần cửa hàng kiểm tra và xác nhận" },
        { id: 13, icon: "💾", title: "Nâng cấp ổ cứng SSD siêu tốc", desc: "Kiểm tra khả năng nâng cấp SSD theo bo mạch và model", price: "Cần cửa hàng kiểm tra và xác nhận" },
        { id: 14, icon: "🧠", title: "Nâng cấp bộ nhớ RAM DDR4/DDR5", desc: "Nâng 16GB/32GB/64GB mở hàng trăm tab Chrome & render mượt", price: "Cần cửa hàng kiểm tra và xác nhận" },
        { id: 15, icon: "💨", title: "Vệ sinh PC & Tra keo tản nhiệt", desc: "Tháo thổi bụi case, làm sạch quạt tản nhiệt, tra keo gấu MX-4", price: "Cần cửa hàng kiểm tra và xác nhận" },
        { id: 10, icon: "⌨️", title: "Sửa cổng kết nối phím / chuột / USB", desc: "Cổng USB, Type-C, audio chập chờn hoặc không nhận tín hiệu", price: "Cần cửa hàng kiểm tra và xác nhận" },
        { id: 12, icon: "🗜️", title: "Thay vỏ case / Nắn khung sườn", desc: "Thay vỏ case gaming kính cường lực, lắp quạt tản LED RGB", price: "Cần cửa hàng kiểm tra và xác nhận" },
        { id: 16, icon: "💻", title: "Cài Win 10/11 & Phần mềm đồ họa", desc: "Cài Win sạch bản quyền, cài Office, Premiere, Photoshop, CAD", price: "Cần cửa hàng kiểm tra và xác nhận" },
        { id: 17, icon: "📂", title: "Cứu dữ liệu ổ cứng HDD / SSD / RAID", desc: "Phục hồi tài liệu công ty, đồ án từ ổ cứng bad sector", price: "Cần cửa hàng kiểm tra và xác nhận" },
        { id: 18, icon: "🔍", title: "Kiểm tra chẩn đoán toàn diện", desc: "Đo nguồn, test phần cứng chuyên sâu & báo giá công khai trước", price: "Cần cửa hàng kiểm tra và xác nhận" }
    ]
};

// Fallback tương thích ngược
const FIXNEAR_REPAIRS = FIXNEAR_REPAIRS_BY_DEVICE.phone;

// Khởi tạo trang
document.addEventListener('DOMContentLoaded', () => {
    initLiveTicker();
    initRepairWizard();
    initModalHandlers();
    checkSmartLocationOnEntry();
    // Chưa tự mở thông báo ưu đãi khi chưa có chương trình được đối soát theo chi nhánh.

    // Preserve keyboard access for legacy clickable cards while their markup is
    // progressively migrated to native buttons/links.
    document.querySelectorAll('div[onclick], span[onclick]').forEach((element) => {
        if (!element.hasAttribute('tabindex')) element.tabIndex = 0;
        if (!element.hasAttribute('role')) element.setAttribute('role', 'button');
        element.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                element.click();
            }
        });
    });

    // Dọn dẹp hash cũ (nếu có) để khi vào trang luôn hiển thị từ đầu trang xem đầy đủ Hero Banner & Wizard
    if (window.location.hash === '#fn-featured-shops-section' || window.location.hash === '#fn-featured-section') {
        history.replaceState(null, null, window.location.pathname + window.location.search);
        window.scrollTo(0, 0);
    }

    // ====== SCROLL ANIMATION OBSERVER ======
    const animateElements = document.querySelectorAll('.fn-animate-on-scroll');
    if (animateElements.length > 0) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('fn-visible');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.15 });
        animateElements.forEach(el => observer.observe(el));
    }

});

// ================= 1. REPAIR FINDER WIZARD LOGIC =================
function initRepairWizard() {
    const wizardBox = document.querySelector('.fn-wizard-box');
    if (!wizardBox) return;

    setupBrandModelSearch();
    renderRepairCards();
}

// Chuyển tầng 1.1: Chọn Loại thiết bị
function selectDeviceType(devType) {
    currentWizardState.device = devType;
    currentWizardState.brandId = '';
    currentWizardState.brand = '';
    currentWizardState.model = '';
    currentWizardState.activeSeries = 'Tất cả';

    const deviceLabels = {
        phone: 'Điện thoại',
        laptop: 'Laptop Windows',
        mac: 'MacBook / Mac',
        tablet: 'Máy tính bảng',
        smartwatch: 'Đồng hồ thông minh',
        pc: 'Máy tính bàn (PC)'
    };

    const labelEl = document.getElementById('fn-nav-device-label');
    if (labelEl) {
        labelEl.textContent = deviceLabels[devType] || 'Thiết bị';
    }
    const titleEl = document.getElementById('fn-brand-step-title');
    const subtitleEl = document.getElementById('fn-brand-step-subtitle');
    if (titleEl) titleEl.textContent = devType === 'pc' ? 'Chọn dòng PC hoặc thương hiệu' : 'Chọn thương hiệu';
    if (subtitleEl) subtitleEl.textContent = devType === 'pc'
        ? 'Chọn loại PC hoặc hãng có sẵn; bạn cũng có thể tự nhập tên máy.'
        : 'Bấm vào hãng thiết bị của bạn hoặc tự gõ tên nếu không thấy.';

    document.getElementById('fn-subview-devices').style.display = 'none';
    document.getElementById('fn-subview-brands').style.display = 'block';
    document.getElementById('fn-subview-models').style.display = 'none';

    renderBrands(devType);
}

// Quay lại tầng 1.1
function wizardBackToDevices() {
    document.getElementById('fn-subview-devices').style.display = 'block';
    document.getElementById('fn-subview-brands').style.display = 'none';
    document.getElementById('fn-subview-models').style.display = 'none';
}

const FIXNEAR_GROUP_LOGOS = {
    samsung_pc: 'samsung', huawei_pc: 'huawei',
    macbook_pro: 'apple', macbook_air: 'apple', imac: 'apple', mac_mini: 'apple', mac_pro: 'apple',
    apple_ipad: 'apple', samsung_tab: 'samsung', xiaomi_pad: 'xiaomi',
    surface_pro: 'microsoft', lenovo_tab: 'lenovo', huawei_matepad: 'huawei', oppo_pad: 'oppo',
    apple_watch: 'apple', samsung_watch: 'samsung', huawei_watch: 'huawei', google_watch: 'google',
    dell_optiplex: 'dell', hp_prodesk: 'hp', lenovo_thinkcentre: 'lenovo',
    toshiba: 'dynabook'
};

// Render thương hiệu với logo ảnh local; các nhóm thiết bị chung dùng biểu tượng minh họa.
function renderBrands(devType) {
    const container = document.getElementById('fn-brands-grid-container');
    if (!container) return;

    const brands = FIXNEAR_BRANDS[devType] || [];
    const totalCards = brands.length + 1; // gồm cả card Tự nhập

    // Tối ưu hóa UI/UX: nhóm ít card (Mac, PC, Smartwatch: 6 cards) chia đều 3 cột (3x2), không bao giờ bị lẻ card cô đơn
    if (totalCards <= 6) {
        container.className = 'fn-brands-grid fn-grid-compact';
    } else {
        container.className = 'fn-brands-grid';
    }

    let html = '';

    brands.forEach(b => {
        const logoId = FIXNEAR_GROUP_LOGOS[b.id] || b.id;
        const logoFile = window.FIXNEAR_BRAND_ASSETS?.[logoId];
        const logoMarkup = logoFile
            ? `<img class="fn-brand-img" src="assets/images/brands/${logoFile}" alt="" width="72" height="46" loading="lazy">`
            : `<span class="fn-brand-wordmark">${escapeCatalogText(b.name)}</span>`;
        html += `
            <button type="button" class="fn-brand-card" data-brand-id="${b.id}" onclick="selectBrand('${b.id}', '${b.name.replace(/'/g, "\\'")}')">
                <div class="fn-brand-logo-wrap">
                    ${logoMarkup}
                </div>
                <div class="fn-brand-name" title="${b.name}">${b.name}</div>
            </button>
        `;
    });

    html += `
        <button type="button" class="fn-brand-card fn-brand-card-custom" onclick="promptCustomBrand()">
            <div class="fn-brand-logo-wrap">
                <div class="fn-brand-badge" style="background:#fff7ed; border:1.5px dashed #ea580c; box-shadow:none;">
                    <span style="font-size: 22px; line-height: 1;">✏️</span>
                </div>
            </div>
            <div class="fn-brand-name" style="color:#ea580c;">Không chắc? Tự nhập</div>
        </button>
    `;

    container.innerHTML = html;
}

function promptCustomBrand() {
    const custom = prompt("Nhập tên hãng thiết bị của bạn (Ví dụ: Xiaomi, Dell, Asus, Lenovo...):");
    if (custom && custom.trim().length > 0) {
        selectBrand('custom', custom.trim());
    }
}

// Chuyển tầng 1.2 sang 1.3: Chọn Dòng máy (Select your model)
function selectBrand(brandId, brandName) {
    currentWizardState.brandId = brandId;
    currentWizardState.brand = brandName;
    currentWizardState.model = '';
    currentWizardState.activeSeries = 'Tất cả';

    const deviceLabels = {
        phone: 'Điện thoại',
        laptop: 'Laptop',
        mac: 'Mac',
        tablet: 'Tablet',
        smartwatch: 'Smartwatch',
        pc: 'PC'
    };

    const pathEl = document.getElementById('fn-nav-brand-path');
    if (pathEl) {
        pathEl.innerHTML = `${deviceLabels[currentWizardState.device] || 'Thiết bị'} &rsaquo; <strong>${brandName}</strong>`;
    }

    const searchInput = document.getElementById('fn-search-brand-models');
    if (searchInput) {
        searchInput.value = '';
        searchInput.placeholder = `Tìm kiếm model ${brandName}... (VD: S24, 15 Pro, XPS...)`;
    }

    document.getElementById('fn-subview-devices').style.display = 'none';
    document.getElementById('fn-subview-brands').style.display = 'none';
    document.getElementById('fn-subview-models').style.display = 'block';

    // Render thanh Tab chọn Series (Dòng máy)
    renderSeriesFilterTabs(brandId);

    // Render danh sách Model
    renderBrandModels(brandId, '', 'Tất cả');
}

// Render các Tab chọn Series (Dòng máy) giúp giao diện gọn gàng, chia nhánh thông minh
function renderSeriesFilterTabs(brandId) {
    const container = document.getElementById('fn-series-filter-container');
    if (!container) return;

    const seriesList = FIXNEAR_SERIES_BY_BRAND[brandId];
    if (!seriesList || seriesList.length <= 1) {
        container.style.display = 'none';
        container.innerHTML = '';
        return;
    }

    let html = '';
    seriesList.forEach(s => {
        const activeClass = (s === currentWizardState.activeSeries) ? 'active' : '';
        html += `
            <button type="button" class="fn-series-pill ${activeClass}" onclick="selectSeriesFilter('${s.replace(/'/g, "\\'")}')">
                ${s}
            </button>
        `;
    });

    container.innerHTML = html;
    container.style.display = 'flex';
}

function selectSeriesFilter(seriesName) {
    currentWizardState.activeSeries = seriesName;

    // Cập nhật class active cho tab
    const pills = document.querySelectorAll('.fn-series-pill');
    pills.forEach(p => {
        if (p.textContent.trim() === seriesName) {
            p.classList.add('active');
        } else {
            p.classList.remove('active');
        }
    });

    const searchInput = document.getElementById('fn-search-brand-models');
    const query = searchInput ? searchInput.value : '';
    renderBrandModels(currentWizardState.brandId, query, seriesName);
}

function wizardBackToBrands() {
    document.getElementById('fn-subview-devices').style.display = 'none';
    document.getElementById('fn-subview-brands').style.display = 'block';
    document.getElementById('fn-subview-models').style.display = 'none';
}

// Render danh sách Model với giao diện TỐI ƯU UI/UX CHỐNG CẮT CHỮ DẤU BA CHẤM (...)
const FIXNEAR_CATALOG_CACHE = {};
const FIXNEAR_CATALOG_DEVICE_MAP = {
    phone: 'phone', laptop: 'win_laptop', mac: 'macbook',
    tablet: 'tablet', smartwatch: 'smartwatch', pc: 'pc_desktop'
};
const FIXNEAR_CATALOG_BRAND_MAP = {
    redmi: 'xiaomi', poco: 'xiaomi',
    huawei: 'others', honor: 'others', nothing: 'others', infinix: 'others',
    motorola: 'others', nokia: 'others', tecno: 'others', zte: 'others',
    iqoo: 'vivo', apple_iphone: 'apple', apple_mac: 'apple', apple_ipad: 'apple',
    apple_watch: 'apple', macbook_pro: 'apple', macbook_air: 'apple',
    imac: 'apple', mac_mini: 'apple', xiaomi_pad: 'xiaomi',
    galaxy_tab: 'samsung', galaxy_watch: 'samsung', watch_other: 'others',
    tablet_other: 'others', laptop_other: 'others', pc_gaming: 'gaming',
    pc_workstation: 'workstation', pc_office: 'office', pc_aio_brand: 'oem_brand'
};

function escapeCatalogText(value) {
    return String(value).replace(/[&<>"']/g, ch => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[ch]));
}

function filterCatalogModels(models, brandId) {
    if (brandId === 'redmi') return models.filter(m => /^redmi\b/i.test(m.name));
    if (brandId === 'poco') return models.filter(m => /^poco\b/i.test(m.name));
    if (brandId === 'xiaomi') return models.filter(m => !/^(redmi|poco)\b/i.test(m.name));
    if (['huawei','honor','nothing','infinix','motorola','nokia','tecno','zte'].includes(brandId)) {
        return models.filter(m => String(m.brand || '').toLowerCase() === brandId || new RegExp('^' + brandId + '\\b', 'i').test(m.name));
    }
    if (brandId === 'iqoo') return models.filter(m => /^iqoo\b/i.test(m.name));
    return models;
}

async function renderBrandModels(brandId, query = '', seriesFilter = 'Tất cả') {
    const container = document.getElementById('fn-models-grid-container');
    if (!container) return;

    const dev = currentWizardState.device;
    const catalogDevice = FIXNEAR_CATALOG_DEVICE_MAP[dev] || dev;
    const catalogBrand = FIXNEAR_CATALOG_BRAND_MAP[brandId] || brandId;
    const cacheKey = `${catalogDevice}:${catalogBrand}`;
    container.innerHTML = '<div class="fn-catalog-status" aria-live="polite">Đang tải danh mục…</div>';

    if (!FIXNEAR_CATALOG_CACHE[cacheKey]) {
        try {
            const response = await fetch(`api/get_catalog.php?action=models&device=${encodeURIComponent(catalogDevice)}&brand=${encodeURIComponent(catalogBrand)}`);
            const payload = await response.json();
            FIXNEAR_CATALOG_CACHE[cacheKey] = payload.success && Array.isArray(payload.data) ? payload.data : [];
        } catch (error) {
            container.innerHTML = '<div class="fn-catalog-status" role="alert">Không tải được danh mục. Bạn vẫn có thể chọn “Không thấy model” và nhập mô tả.</div>';
            return;
        }
    }

    let modelsList = filterCatalogModels(FIXNEAR_CATALOG_CACHE[cacheKey], brandId);

    // Lọc theo Series nếu người dùng chọn tab cụ thể
    if (seriesFilter && seriesFilter !== 'Tất cả') {
        const sKeyword = seriesFilter.split(' ')[0].toLowerCase();
        modelsList = modelsList.filter(m => String(m.name || '').toLowerCase().includes(sKeyword));
    }

    // Lọc theo query tìm kiếm nếu có
    if (query && query.trim().length > 0) {
        const q = query.trim().toLowerCase();
        modelsList = modelsList.filter(m => String(m.name || '').toLowerCase().includes(q));
    }

    let html = '';
    if (modelsList.length === 0) {
        html = `
            <div style="grid-column: 1 / -1; padding: 24px; text-align: center; color: #64748b; background: #f8fafc; border-radius: 8px;">
                Chưa có model công khai khớp với hãng hoặc từ khóa này trong dữ liệu FixNear.<br>
                <button type="button" class="fn-btn fn-btn-primary fn-btn-sm" style="margin-top: 10px;" onclick="promptCustomModel()">
                    Nhập model hoặc mô tả thiết bị ➔
                </button>
            </div>
        `;
    } else {
        const brandWord = currentWizardState.brand.split(' ')[0];

        modelsList.forEach(model => {
            const m = String(model.name || '');
            // TỐI ƯU UI/UX: Loại bỏ tiền tố tên hãng bị lặp (Ví dụ "Lenovo Legion 5" hiển thị là "Legion 5")
            // Nút bấm gọn gàng, thoáng mắt và không bao giờ bị cắt thành dấu ba chấm (...)
            let displayLabel = m;
            if (brandWord && brandWord.length > 1) {
                const reg = new RegExp('^' + brandWord + '\\s+', 'i');
                displayLabel = m.replace(reg, '');
            }

            const safeName = escapeCatalogText(m);
            const safeLabel = escapeCatalogText(displayLabel);
            html += `
                <button type="button" class="fn-model-btn" data-model-name="${safeName}" title="${safeName}">
                    ${safeLabel}
                </button>
            `;
        });
    }

    html += `
        <button type="button" class="fn-model-btn fn-model-btn-custom" onclick="promptCustomModel()">
            ✏️ Không thấy model? Tự nhập ngay
        </button>
    `;

    container.innerHTML = html;
    container.querySelectorAll('[data-model-name]').forEach(button => {
        button.addEventListener('click', () => selectModel(button.dataset.modelName));
    });
}

function setupBrandModelSearch() {
    const input = document.getElementById('fn-search-brand-models');
    if (!input) return;

    input.addEventListener('input', () => {
        renderBrandModels(currentWizardState.brandId, input.value, currentWizardState.activeSeries);
    });

    input.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            const val = input.value.trim();
            if (val) {
                selectModel(val);
            }
        }
    });
}

function promptCustomModel() {
    const custom = prompt(`Nhập chính xác tên đời máy ${currentWizardState.brand} của bạn (VD: Note 13 Pro, ROG Strix G16...):`);
    if (custom && custom.trim().length > 0) {
        selectModel(custom.trim());
    }
}

function selectModel(modelName) {
    currentWizardState.model = modelName;

    const currentModelText = document.getElementById('fn-current-selected-model-text');
    if (currentModelText) {
        const brandBadge = currentWizardState.brand ? `[${currentWizardState.brand}] ` : '';
        currentModelText.innerHTML = `Đang chọn: <b>${brandBadge}${modelName}</b>`;
    }

    goToWizardStep(2);
}

function skipToStep2() {
    currentWizardState.model = '';
    const currentModelText = document.getElementById('fn-current-selected-model-text');
    if (currentModelText) {
        const brandBadge = currentWizardState.brand ? `[${currentWizardState.brand}] ` : '';
        currentModelText.innerHTML = `Đang chọn: <b>${brandBadge}Thiết bị chung</b>`;
    }
    goToWizardStep(2);
}

function goToWizardStep(stepNum) {
    const view1 = document.getElementById('fn-wizard-view-1');
    const view2 = document.getElementById('fn-wizard-view-2');
    const step1 = document.getElementById('fn-step-indicator-1');
    const step2 = document.getElementById('fn-step-indicator-2');

    if (!view1 || !view2) return;

    if (stepNum === 1) {
        view1.style.display = 'block';
        view2.style.display = 'none';
        if (step1) step1.classList.add('active');
        if (step2) step2.classList.remove('active');
    } else {
        view1.style.display = 'none';
        view2.style.display = 'block';
        if (step1) step1.classList.remove('active');
        if (step2) step2.classList.add('active');

        // Render đúng danh sách lỗi chuyên biệt cho thiết bị đang chọn (Chống bành trướng lĩnh vực)
        renderRepairCards(currentWizardState.device);

        const wizardBox = document.querySelector('.fn-wizard-box');
        if (wizardBox) {
            wizardBox.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }
}

function renderRepairCards(devType) {
    const container = document.getElementById('fn-repairs-grid-container');
    if (!container) return;

    const dev = devType || currentWizardState.device || 'phone';
    const repairs = FIXNEAR_REPAIRS_BY_DEVICE[dev] || FIXNEAR_REPAIRS_BY_DEVICE.phone;

    container.innerHTML = repairs.map(r => `
        <button type="button" class="fn-repair-card" onclick="selectRepairIssue(${r.id}, '${r.title.replace(/'/g, "\\'")}')">
            <div class="fn-repair-icon">${r.icon}</div>
            <div class="fn-repair-info">
                <div class="fn-repair-name">${r.title}</div>
                <div class="fn-repair-desc">${r.desc}</div>
                <div class="fn-repair-price">${r.price}</div>
            </div>
        </button>
    `).join('');
}

const SERVICE_TO_FAULT_MAP = {
    1: 'screen',
    2: 'glass-press',
    3: 'battery',
    4: 'charging-port',
    5: 'water-damage',
    6: 'mainboard',
    7: 'camera',
    8: 'speaker',
    9: 'mic',
    10: 'keyboard',
    11: 'trackpad',
    12: 'hinge',
    13: 'ssd-upgrade',
    14: 'ram-upgrade',
    15: 'thermal-cleaning',
    16: 'software',
    17: 'data-recovery',
    18: 'general-check'
};

function selectRepairIssue(serviceId, issueTitle) {
    currentWizardState.serviceId = serviceId;
    currentWizardState.issueName = issueTitle;

    let params = new URLSearchParams();
    if (currentWizardState.device) params.set('device', currentWizardState.device);
    if (currentWizardState.brand) params.set('brand', currentWizardState.brand);
    if (currentWizardState.model) params.set('model', currentWizardState.model);
    if (serviceId && serviceId > 0) params.set('service_id', serviceId);
    if (issueTitle) params.set('issue_name', issueTitle);

    const faultId = SERVICE_TO_FAULT_MAP[serviceId] || '';
    if (faultId) params.set('fault_id', faultId);

    // Điều hướng thẳng sang RESULT PAGE (shops.php - Danh sách cửa hàng trước),
    // Tuyệt đối không mở bản đồ full-screen ngay sau khi chọn lỗi
    window.location.href = 'shops.php?' + params.toString();
}

// ================= 2. LIVE TICKER SOCIAL PROOF =================
function initLiveTicker() {
    const tickerEl = document.getElementById('fn-live-ticker-text');
    if (!tickerEl) return;

    const messages = [
        "Dữ liệu cửa hàng, giá và ưu đãi có thể thay đổi — hãy xác nhận trực tiếp trước khi sửa",
        "Bảng giá theo model gồm mức ước tính; giá cuối do cửa hàng xác nhận",
        "FixNear không tự báo giá — hãy yêu cầu cửa hàng xác nhận giá trọn gói trước khi sửa",
        "Không giao máy trước khi ghi nhận tình trạng, giá trọn gói và điều kiện bảo hành",
        "Đánh giá cửa hàng do thành viên gửi và được kiểm duyệt trước khi hiển thị"
    ];

    let index = 0;
    setInterval(() => {
        index = (index + 1) % messages.length;
        tickerEl.style.opacity = '0';
        setTimeout(() => {
            tickerEl.textContent = messages[index];
            tickerEl.style.opacity = '1';
        }, 300);
    }, 4500);
}

// ================= 3. MODAL HANDLERS =================
function initModalHandlers() {
    const reportModal = document.getElementById('fn-report-modal');
    const openReportBtns = document.querySelectorAll('.fn-open-report-btn');

    if (reportModal) {
        openReportBtns.forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const shopId = btn.dataset.shopId;
                const shopName = btn.dataset.shopName;

                const shopIdInput = document.getElementById('fn-report-shop-id');
                const shopNameDisplay = document.getElementById('fn-report-shop-name');

                if (shopIdInput) shopIdInput.value = shopId;
                if (shopNameDisplay) shopNameDisplay.textContent = shopName;

                reportModal.classList.add('active');
            });
        });
    }

    // Đóng tất cả modal khi click vào bất kỳ nút .fn-close-modal
    document.querySelectorAll('.fn-close-modal').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const modal = btn.closest('.fn-modal-overlay');
            if (modal) {
                modal.classList.remove('active');
            } else {
                document.querySelectorAll('.fn-modal-overlay.active').forEach(m => m.classList.remove('active'));
            }
            document.body.style.overflow = '';
        });
    });

    // Đóng modal khi click ra vùng nền tối bên ngoài card
    document.querySelectorAll('.fn-modal-overlay').forEach(modal => {
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.classList.remove('active');
                document.body.style.overflow = '';
            }
        });
    });

    // Đóng modal khi bấm phím ESC
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            document.querySelectorAll('.fn-modal-overlay.active').forEach(m => {
                m.classList.remove('active');
                document.body.style.overflow = '';
            });
        }
    });
}

// ================= 4. GPS & CHỌN VỊ TRÍ TÙY Ý (KHÔNG CƯỠNG ÉP) =================
function openLocationModal() {
    const locModal = document.getElementById('fn-location-modal');
    if (locModal) {
        locModal.classList.add('active');
    }
}

function selectCustomLocation(lat, lng, name) {
    if (window.FixNearLocation) {
        window.FixNearLocation.setDistrict(lat, lng, name);
    } else {
        localStorage.setItem('fixnear_user_lat', lat);
        localStorage.setItem('fixnear_user_lng', lng);
        localStorage.setItem('fixnear_loc_name', name);
        document.cookie = `fixnear_lat=${lat}; path=/; max-age=86400`;
        document.cookie = `fixnear_lng=${lng}; path=/; max-age=86400`;
        document.cookie = `fixnear_loc=${encodeURIComponent(name)}; path=/; max-age=86400`;
    }

    const locModal = document.getElementById('fn-location-modal');
    if (locModal) {
        locModal.classList.remove('active');
        document.body.style.overflow = '';
    }

    // Làm sạch URL và reload để server render lại kết quả với vị trí mới
    if (window.FixNearLocation) {
        window.FixNearLocation.cleanUrlGPS();
    }
    window.location.reload();
}

function toggleGPS() {
    const hasLocation = window.FixNearLocation ? window.FixNearLocation.hasLocation() : !!localStorage.getItem('fixnear_user_lat');
    if (hasLocation) {
        // Tắt vị trí / Reset GPS
        if (window.FixNearLocation) {
            window.FixNearLocation.clear();
            window.FixNearLocation.cleanUrlGPS();
        } else {
            localStorage.removeItem('fixnear_user_lat');
            localStorage.removeItem('fixnear_user_lng');
            localStorage.removeItem('fixnear_loc_name');
            document.cookie = "fixnear_lat=; path=/; max-age=0";
            document.cookie = "fixnear_lng=; path=/; max-age=0";
            document.cookie = "fixnear_loc=; path=/; max-age=0";
        }
        window.location.reload();
    } else {
        // Kích hoạt nhận vị trí
        triggerDeviceGPS(document.getElementById('fn-gps-toggle-btn') || document.getElementById('fn-main-gps-btn'));
    }
}

function triggerDeviceGPS(btn) {
    if (!navigator.geolocation) {
        if (btn) btn.innerHTML = '❌ Trình duyệt không hỗ trợ GPS. Hãy bấm chọn 1 Quận bên dưới ⬇';
        return;
    }

    if (btn) {
        btn.innerHTML = '🛰️ Đang kết nối GPS... hãy nhấn "Cho Phép" (Allow)';
        btn.style.opacity = '0.85';
    }

    if (window.FixNearLocation) {
        window.FixNearLocation.requestGPS(
            (locData) => {
                if (btn) {
                    btn.innerHTML = '✓ Đã nhận GPS! Đang cập nhật...';
                    btn.style.background = '#16a34a';
                }
                setTimeout(() => {
                    window.FixNearLocation.cleanUrlGPS();
                    window.location.reload();
                }, 400);
            },
            (err) => {
                if (btn) {
                    btn.innerHTML = '⚠️ Chưa nhận được GPS. Vui lòng bấm chọn 1 Quận bạn đang ở bên dưới ⬇';
                    btn.style.background = '#dc2626';
                    btn.style.opacity = '1';
                }
            }
        );
    } else {
        navigator.geolocation.getCurrentPosition(
            (pos) => {
                const lat = pos.coords.latitude;
                const lng = pos.coords.longitude;
                if (btn) {
                    btn.innerHTML = '✓ Đã nhận GPS! Đang cập nhật tiệm gần bạn...';
                    btn.style.background = '#16a34a';
                }
                setTimeout(() => {
                    selectCustomLocation(lat, lng, 'Vị trí GPS của bạn');
                }, 400);
            },
            (err) => {
                if (btn) {
                    btn.innerHTML = '⚠️ Chưa nhận được GPS. Vui lòng bấm chọn 1 Quận bạn đang ở bên dưới ⬇';
                    btn.style.background = '#dc2626';
                    btn.style.opacity = '1';
                }
            },
            { timeout: 9000, enableHighAccuracy: true }
        );
    }
}

// ================= 5. KIỂM TRA ĐỊNH VỊ THÔNG MINH (CHƯA BẬT THÌ HỎI 1 LẦN DUY NHẤT, BẬT RỒI KHÔNG HỎI) =================
function checkSmartLocationOnEntry() {
    const hasLocation = window.FixNearLocation ? window.FixNearLocation.hasLocation() : (localStorage.getItem('fixnear_user_lat') || (document.cookie.includes('fixnear_lat=') && !document.cookie.includes('fixnear_lat=;')));

    // Nếu đã có vị trí -> TUYỆT ĐỐI không bật modal
    if (hasLocation) {
        return;
    }

    // Không bật modal trên các trang admin, login, register, request_repair, contact
    const p = window.location.pathname;
    if (p.includes('/admin') || p.includes('login.php') || p.includes('register.php') || p.includes('request_repair.php') || p.includes('contact.php')) {
        return;
    }

    // Kiểm tra nếu trong phiên này người dùng đã bấm đóng modal hoặc từ chối thì không hỏi lại liên tục
    if (sessionStorage.getItem('fixnear_loc_prompt_dismissed')) {
        return;
    }

    // Chỉ gợi ý nhẹ sau khi người dùng vào trang chủ lần đầu
    setTimeout(() => {
        const locModal = document.getElementById('fn-location-modal');
        if (locModal && !hasLocation) {
            locModal.classList.add('active');
            document.body.style.overflow = 'hidden';
            sessionStorage.setItem('fixnear_loc_prompt_dismissed', '1');
        }
    }, 1500);
}

// ================= 6. BANNER QUẢNG CÁO XUẤT HIỆN SAU 3S =================
function initPromoBanner3s() {
    const p = window.location.pathname;
    if (p.includes('/admin') || p.includes('login.php') || p.includes('register.php')) {
        return;
    }

    // Check if user dismissed the banner today
    const today = new Date().toISOString().slice(0, 10); // YYYY-MM-DD
    const dismissedDate = localStorage.getItem('fixnear_promo_dismissed_date');
    if (dismissedDate === today) {
        return;
    }

    setTimeout(() => {
        const promoModal = document.getElementById('fn-promo-modal');
        const locModal = document.getElementById('fn-location-modal');

        if (promoModal) {
            const showBanner = () => {
                promoModal.classList.add('active');
                document.body.style.overflow = 'hidden';
            };

            if (locModal && locModal.classList.contains('active')) {
                const interval = setInterval(() => {
                    if (!locModal.classList.contains('active')) {
                        clearInterval(interval);
                        showBanner();
                    }
                }, 800);
            } else {
                showBanner();
            }
        }
    }, 2000);
}

// ================= 7. QUẢN LÝ CỬA HÀNG YÊU THÍCH (FAVORITES) =================
function syncFavoritesUI() {
    document.querySelectorAll('.fn-fav-btn').forEach(btn => {
        const shopId = parseInt(btn.getAttribute('data-shop-id'), 10);
        const icon = btn.querySelector('.fn-fav-icon') || document.getElementById(`fav-icon-${shopId}`);
        const text = document.getElementById(`fav-text-${shopId}`);
        const isServerFav = btn.getAttribute('data-favorited') === '1';
        const isFav = isServerFav;

        if (icon) icon.textContent = isFav ? '❤️' : '🤍';
        if (text) text.textContent = isFav ? 'Đã lưu yêu thích' : 'Lưu yêu thích';
        if (isFav) btn.classList.add('active');
        else btn.classList.remove('active');
    });
}

async function toggleFavorite(event, shopId) {
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }
    shopId = parseInt(shopId, 10);
    if (!shopId) return;

    const icon = document.getElementById(`fav-icon-${shopId}`);
    const text = document.getElementById(`fav-text-${shopId}`);
    const btn = document.querySelector(`.fn-fav-btn[data-shop-id="${shopId}"]`) || document.getElementById('btn-fav-detail');

    // Lấy csrf token nếu có
    const csrfInput = document.querySelector('input[name="csrf_token"]');
    const csrfToken = csrfInput ? csrfInput.value : '';

    try {
        const res = await fetch('api/toggle_favorite.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-CSRF-Token': csrfToken
            },
            body: `shop_id=${shopId}&csrf_token=${encodeURIComponent(csrfToken)}`
        });
        const data = await res.json();
        if (res.status === 401 || !data.logged_in) {
            location.href = 'login.php?redirect=' + encodeURIComponent(location.pathname.split('/').pop() + location.search);
            return;
        }
        if (!res.ok || !data.success) throw new Error(data.message || 'Không thể lưu cửa hàng.');
        const isNowFav = Boolean(data.favorited);

        if (btn) btn.setAttribute('data-favorited', isNowFav ? '1' : '0');
        if (icon) icon.textContent = isNowFav ? '❤️' : '🤍';
        if (text) text.textContent = isNowFav ? 'Đã lưu yêu thích' : 'Lưu yêu thích';

        // Hiệu ứng nảy nhẹ khi bấm
        if (btn) {
            btn.style.transform = 'scale(1.25)';
            setTimeout(() => { btn.style.transform = ''; }, 200);
        }

    } catch (e) {
        showSimpleToast('Không thể lưu cửa hàng lúc này. Vui lòng thử lại.');
    }
}

function showSimpleToast(msg) {
    let toast = document.getElementById('fn-simple-toast');
    if (!toast) {
        toast = document.createElement('div');
        toast.id = 'fn-simple-toast';
        toast.style.cssText = 'position:fixed;bottom:24px;left:50%;transform:translateX(-50%);background:#0f172a;color:#fff;padding:10px 18px;border-radius:24px;font-size:13px;font-weight:700;z-index:9999;box-shadow:0 4px 16px rgba(0,0,0,0.25);transition:opacity 0.3s;opacity:0;pointer-events:none;';
        document.body.appendChild(toast);
    }
    toast.textContent = msg;
    toast.style.opacity = '1';
    setTimeout(() => { toast.style.opacity = '0'; }, 2200);
}

document.addEventListener('DOMContentLoaded', () => {
    syncFavoritesUI();
});
