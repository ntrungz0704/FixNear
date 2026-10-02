/**
 * FixNear - Main Client Logic
 * Chuẩn hóa quy trình tra cứu theo RepairBookings (Device -> Brand -> Model -> Repair)
 * Biểu tượng Vector SVG chính hãng 100%, Bộ lọc Dòng máy (Series Tabs) chống cắt chữ
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

// Từ điển Thương hiệu với Biểu tượng Vector SVG Chính Hãng Chuẩn 100%
const FIXNEAR_BRANDS = {
    phone: [
        {
            id: "apple",
            name: "Apple",
            logo: `<div class="fn-brand-badge" style="background:#0f172a;"><svg viewBox="0 0 170 170" width="24" height="24" fill="#ffffff"><path d="M150.37 130.25c-2.45 5.66-5.35 10.87-8.71 15.66-4.58 6.53-8.33 11.05-11.22 13.56-4.48 4.12-9.28 6.23-14.42 6.35-3.69 0-8.14-1.05-13.32-3.18-5.19-2.12-9.97-3.17-14.34-3.17-4.58 0-9.49 1.05-14.75 3.17-5.26 2.13-9.5 3.24-12.74 3.35-4.35.13-9.16-1.9-14.42-6.08-3.69-3.04-7.67-7.81-11.96-14.34-5.67-8.6-10.15-18.49-13.43-29.68-3.28-11.18-4.92-21.84-4.92-31.97 0-14.35 3.63-26.06 10.88-35.13 7.25-9.08 16.32-13.73 27.22-13.97 5.1 0 10.63 1.48 16.6 4.44 5.97 2.95 10.05 4.49 12.23 4.62 1.95 0 6.24-1.63 12.87-4.9 6.63-3.26 12.37-4.66 17.21-4.18 13.06.98 23.41 5.75 31.06 14.32-11.53 6.96-17.18 16.64-16.94 29.04.24 9.9 4.13 18.23 11.66 24.99 7.53 6.75 16.51 10.62 26.94 11.6-2.52 7.62-5.46 15.02-8.81 22.21zM119.22 33.15c0-7.39 2.68-14.28 8.04-20.67 5.36-6.39 12.01-10.45 19.95-12.18.33 1.2.49 2.29.49 3.28 0 7.39-2.79 14.33-8.36 20.83-5.57 6.5-12.39 10.46-20.45 11.89-.11-1.09-.17-2.17-.17-3.15z"/></svg></div>`
        },
        {
            id: "samsung",
            name: "Samsung",
            logo: `<div class="fn-brand-badge" style="background:#0057b8;"><svg viewBox="0 0 100 100" width="28" height="28"><path d="M35 63c3 4 8 7 15 7 9 0 14-5 14-11 0-7-5-10-14-12l-5-1c-6-1-10-4-10-9 0-6 5-11 13-11 6 0 11 3 14 6l-3 4c-3-3-7-5-11-5-6 0-9 3-9 7 0 4 3 7 10 8l5 1c7 2 14 5 14 13 0 7-6 13-18 13-8 0-14-4-18-9l3-4z" fill="#fff"/></svg></div>`
        },
        {
            id: "xiaomi",
            name: "Xiaomi",
            logo: `<div class="fn-brand-badge" style="background:#ff6900;"><svg viewBox="0 0 100 100" width="26" height="26"><path d="M26 30v40h10V46l10 14 10-14v24h10V30H56l-8 12-8-12H26zm48 0v40h10V30H74z" fill="#fff"/></svg></div>`
        },
        {
            id: "oppo",
            name: "Oppo",
            logo: `<div class="fn-brand-badge" style="background:#008a38;"><svg viewBox="0 0 100 100" width="32" height="32"><text x="50" y="58" font-family="Arial, sans-serif" font-weight="900" font-size="20" fill="#fff" text-anchor="middle" letter-spacing="1">OPPO</text></svg></div>`
        },
        {
            id: "redmi",
            name: "Redmi",
            logo: `<div class="fn-brand-badge" style="background:#e02020;"><svg viewBox="0 0 100 100" width="30" height="30"><text x="50" y="58" font-family="Arial, sans-serif" font-weight="900" font-size="20" fill="#fff" text-anchor="middle">Redmi</text></svg></div>`
        },
        {
            id: "vivo",
            name: "Vivo",
            logo: `<div class="fn-brand-badge" style="background:#007aff;"><svg viewBox="0 0 100 100" width="30" height="30"><text x="50" y="58" font-family="Arial, sans-serif" font-weight="900" font-size="22" fill="#fff" text-anchor="middle" letter-spacing="1">vivo</text></svg></div>`
        },
        {
            id: "realme",
            name: "Realme",
            logo: `<div class="fn-brand-badge" style="background:#ffc915;"><svg viewBox="0 0 100 100" width="30" height="30"><text x="50" y="59" font-family="Arial, sans-serif" font-weight="900" font-size="18" fill="#000" text-anchor="middle">realme</text></svg></div>`
        },
        {
            id: "google",
            name: "Google Pixel",
            logo: `<div class="fn-brand-badge" style="background:#ffffff; border:1.5px solid #e2e8f0; border-radius:50%;"><svg viewBox="0 0 100 100" width="24" height="24"><path d="M78 51c0-2-.2-4-.6-6H50v12h16c-.7 3.6-2.8 6.7-5.9 8.8v7.3h9.5C75.2 68 78 60 78 51z" fill="#4285f4"/><path d="M50 79c8 0 14.7-2.6 19.6-7.2l-9.5-7.3c-2.7 1.8-6.1 2.9-10.1 2.9-7.8 0-14.4-5.3-16.7-12.4H23.5v7.6C28.4 72.3 38.5 79 50 79z" fill="#34a853"/><path d="M33.3 55c-.6-1.8-.9-3.7-.9-5.7s.3-3.9.9-5.7V36H23.5C21.6 39.8 20.5 44 20.5 49.3s1.1 9.5 3 13.3L33.3 55z" fill="#fbbc05"/><path d="M50 34c4.3 0 8.2 1.5 11.3 4.4l8.5-8.5C64.6 25.2 57.9 22.5 50 22.5c-11.5 0-21.6 6.7-26.5 16.5l9.8 7.6C35.6 39.3 42.2 34 50 34z" fill="#ea4335"/></svg></div>`
        },
        {
            id: "sony",
            name: "Sony",
            logo: `<div class="fn-brand-badge" style="background:#0f172a;"><svg viewBox="0 0 100 100" width="30" height="30"><text x="50" y="58" font-family="Arial, sans-serif" font-weight="900" font-size="19" fill="#fff" text-anchor="middle" letter-spacing="2">SONY</text></svg></div>`
        },
        {
            id: "huawei",
            name: "Huawei",
            logo: `<div class="fn-brand-badge" style="background:#111827;"><svg viewBox="0 0 100 100" width="28" height="28"><path d="M50 22c-3 8-3 16 0 24 3-8 3-16 0-24zm14 5c-6 6-9 14-8 22 7-4 13-11 15-18-2-2-4-3-7-4zm-28 0c-3 1-5 2-7 4 2 7 8 14 15 18 1-8-2-16-8-22zm39 17c-8 3-14 9-16 17 8-1 16-5 21-11-1-3-3-5-5-6zm-50 0c-2 1-4 3-5 6 5 6 13 10 21 11-2-8-8-14-16-17zm46 17c-8 0-16 4-20 11 8 2 16 0 23-4 0-3-1-5-3-7zm-42 0c-2 2-3 4-3 7 7 4 15 6 23 4-4-7-12-11-20-11z" fill="#cf0a2c"/></svg></div>`
        },
        {
            id: "oneplus",
            name: "OnePlus",
            logo: `<div class="fn-brand-badge" style="background:#eb0028;"><svg viewBox="0 0 100 100" width="26" height="26"><rect x="22" y="22" width="56" height="56" rx="8" fill="none" stroke="#fff" stroke-width="5"/><path d="M42 34v32m-8-24l8-8" stroke="#fff" stroke-width="6" stroke-linecap="round"/><path d="M60 45v14m-7-7h14" stroke="#fff" stroke-width="5" stroke-linecap="round"/></svg></div>`
        },
        {
            id: "poco",
            name: "Poco",
            logo: `<div class="fn-brand-badge" style="background:#ffd200;"><svg viewBox="0 0 100 100" width="30" height="30"><text x="50" y="59" font-family="Arial, sans-serif" font-weight="900" font-size="20" fill="#000" text-anchor="middle" letter-spacing="1">POCO</text></svg></div>`
        },
        {
            id: "iqoo",
            name: "iQOO",
            logo: `<div class="fn-brand-badge" style="background:#111827;"><svg viewBox="0 0 100 100" width="30" height="30"><text x="50" y="58" font-family="Arial, sans-serif" font-weight="900" font-size="22" fill="#f5a623" text-anchor="middle">iQOO</text></svg></div>`
        },
        {
            id: "honor",
            name: "Honor",
            logo: `<div class="fn-brand-badge" style="background:#0071ce;"><svg viewBox="0 0 100 100" width="30" height="30"><text x="50" y="58" font-family="Arial, sans-serif" font-weight="900" font-size="18" fill="#fff" text-anchor="middle" letter-spacing="1">HONOR</text></svg></div>`
        },
        {
            id: "nothing",
            name: "Nothing",
            logo: `<div class="fn-brand-badge" style="background:#0f172a;"><svg viewBox="0 0 100 100" width="30" height="30"><text x="50" y="58" font-family="monospace" font-weight="900" font-size="14" fill="#fff" text-anchor="middle" letter-spacing="2">NOTHING</text></svg></div>`
        },
        {
            id: "nokia",
            name: "Nokia",
            logo: `<div class="fn-brand-badge" style="background:#124191;"><svg viewBox="0 0 100 100" width="30" height="30"><text x="50" y="58" font-family="Arial, sans-serif" font-weight="900" font-size="17" fill="#fff" text-anchor="middle" letter-spacing="1">NOKIA</text></svg></div>`
        },
        {
            id: "motorola",
            name: "Motorola",
            logo: `<div class="fn-brand-badge" style="background:#00142e; border-radius:50%;"><svg viewBox="0 0 100 100" width="28" height="28"><circle cx="50" cy="50" r="44" fill="none" stroke="#fff" stroke-width="4"/><path d="M30 65l14-30 6 16 6-16 14 30" fill="none" stroke="#fff" stroke-width="6" stroke-linecap="round" stroke-linejoin="round"/></svg></div>`
        },
        {
            id: "infinix",
            name: "Infinix",
            logo: `<div class="fn-brand-badge" style="background:#00c48c;"><svg viewBox="0 0 100 100" width="30" height="30"><text x="50" y="58" font-family="Arial, sans-serif" font-weight="900" font-size="17" fill="#fff" text-anchor="middle">Infinix</text></svg></div>`
        },
        {
            id: "tecno",
            name: "Tecno",
            logo: `<div class="fn-brand-badge" style="background:#0046be;"><svg viewBox="0 0 100 100" width="30" height="30"><text x="50" y="58" font-family="Arial, sans-serif" font-weight="900" font-size="18" fill="#fff" text-anchor="middle">TECNO</text></svg></div>`
        },
        {
            id: "zte",
            name: "ZTE / Nubia",
            logo: `<div class="fn-brand-badge" style="background:#005aaa;"><svg viewBox="0 0 100 100" width="30" height="30"><text x="50" y="58" font-family="Arial, sans-serif" font-weight="900" font-size="20" fill="#fff" text-anchor="middle">ZTE</text></svg></div>`
        }
    ],
    laptop: [
        {
            id: "dell",
            name: "Dell",
            logo: `<div class="fn-brand-badge" style="background:#0076ce;"><svg viewBox="0 0 100 100" width="30" height="30"><circle cx="50" cy="50" r="42" fill="none" stroke="#fff" stroke-width="5"/><text x="50" y="58" font-family="'Arial Black', sans-serif" font-weight="900" font-size="16" fill="#fff" text-anchor="middle">DELL</text></svg></div>`
        },
        {
            id: "asus",
            name: "Asus",
            logo: `<div class="fn-brand-badge" style="background:#00539b;"><svg viewBox="0 0 100 100" width="32" height="32"><text x="50" y="58" font-family="'Arial Black', Impact, sans-serif" font-weight="900" font-size="17" fill="#fff" text-anchor="middle" letter-spacing="1">ASUS</text></svg></div>`
        },
        {
            id: "hp",
            name: "HP",
            logo: `<div class="fn-brand-badge" style="background:#0096d6; border-radius:50%;"><svg viewBox="0 0 100 100" width="30" height="30"><circle cx="50" cy="50" r="44" fill="#0096d6"/><path d="M41 24l-9 52h8l3-18h9c8 0 14-5 15-13 1-8-3-13-11-13H41zm11 8c4 0 6 2 5 6-.8 4-4 6-7 6h-6l2-12h7zM64 37l-7 39h8l2-11h8c8 0 13-5 15-13 1-7-3-13-10-13H64zm12 7c3 0 5 2 4 6-.8 4-3 6-7 6h-5l2-12h6z" fill="#fff"/></svg></div>`
        },
        {
            id: "lenovo",
            name: "Lenovo",
            logo: `<div class="fn-brand-badge" style="background:#e2231a;"><svg viewBox="0 0 100 100" width="32" height="32"><text x="50" y="58" font-family="Arial, sans-serif" font-weight="900" font-size="15" fill="#fff" text-anchor="middle">lenovo</text></svg></div>`
        },
        {
            id: "acer",
            name: "Acer",
            logo: `<div class="fn-brand-badge" style="background:#111827;"><svg viewBox="0 0 100 100" width="32" height="32"><text x="50" y="58" font-family="'Trebuchet MS', Arial, sans-serif" font-weight="900" font-size="22" fill="#83b81a" text-anchor="middle">acer</text></svg></div>`
        },
        {
            id: "msi",
            name: "MSI",
            logo: `<div class="fn-brand-badge" style="background:#111827; border:1px solid #e11d48;"><svg viewBox="0 0 100 100" width="30" height="30"><path d="M50 18L26 28v26c0 18 10 30 24 36 14-6 24-18 24-36V28L50 18z" fill="#dc2626"/><text x="50" y="57" font-family="'Arial Black', sans-serif" font-weight="900" font-size="13" fill="#fff" text-anchor="middle">MSI</text></svg></div>`
        },
        {
            id: "lg",
            name: "LG (Gram)",
            logo: `<div class="fn-brand-badge" style="background:#a50034; border-radius:50%;"><svg viewBox="0 0 100 100" width="28" height="28"><circle cx="50" cy="50" r="42" fill="none" stroke="#fff" stroke-width="5"/><circle cx="36" cy="40" r="4.5" fill="#fff"/><path d="M46 32v24h18" fill="none" stroke="#fff" stroke-width="5" stroke-linecap="round"/><path d="M68 62c-4 5-11 8-18 8-12 0-21-9-21-21 0-10 7-18 16-20" fill="none" stroke="#fff" stroke-width="5" stroke-linecap="round"/></svg></div>`
        },
        {
            id: "microsoft",
            name: "Microsoft (Surface)",
            logo: `<div class="fn-brand-badge" style="background:#1e293b;"><svg viewBox="0 0 100 100" width="26" height="26"><rect x="25" y="25" width="22" height="22" fill="#f25022"/><rect x="53" y="25" width="22" height="22" fill="#7fba00"/><rect x="25" y="53" width="22" height="22" fill="#00a4ef"/><rect x="53" y="53" width="22" height="22" fill="#ffb900"/></svg></div>`
        },
        {
            id: "razer",
            name: "Razer",
            logo: `<div class="fn-brand-badge" style="background:#000000;"><svg viewBox="0 0 100 100" width="28" height="28"><path d="M50 20c-5 5-8 12-7 19 1 7 7 12 14 13-4 5-10 8-16 8-4 0-8-1-11-4 4 7 12 11 20 11 11 0 20-8 21-19 1-11-6-21-16-24l-5-4zm-14 8c-7 2-12 8-13 15-1 8 4 15 11 18-2-5-1-11 2-15 4-4 9-6 15-5-2-4-6-8-11-11l-4-2zm28 0l-4 2c-5 3-9 7-11 11 6-1 11 1 15 5 3 4 4 10 2 15 7-3 12-10 11-18-1-7-6-13-13-15z" fill="#00ff00"/></svg></div>`
        },
        {
            id: "samsung_pc",
            name: "Samsung (Galaxy Book)",
            logo: `<div class="fn-brand-badge" style="background:#0057b8;"><svg viewBox="0 0 100 100" width="28" height="28"><path d="M35 63c3 4 8 7 15 7 9 0 14-5 14-11 0-7-5-10-14-12l-5-1c-6-1-10-4-10-9 0-6 5-11 13-11 6 0 11 3 14 6l-3 4c-3-3-7-5-11-5-6 0-9 3-9 7 0 4 3 7 10 8l5 1c7 2 14 5 14 13 0 7-6 13-18 13-8 0-14-4-18-9l3-4z" fill="#fff"/></svg></div>`
        },
        {
            id: "huawei_pc",
            name: "Huawei (MateBook)",
            logo: `<div class="fn-brand-badge" style="background:#111827;"><svg viewBox="0 0 100 100" width="28" height="28"><path d="M50 22c-3 8-3 16 0 24 3-8 3-16 0-24zm14 5c-6 6-9 14-8 22 7-4 13-11 15-18-2-2-4-3-7-4zm-28 0c-3 1-5 2-7 4 2 7 8 14 15 18 1-8-2-16-8-22zm39 17c-8 3-14 9-16 17 8-1 16-5 21-11-1-3-3-5-5-6zm-50 0c-2 1-4 3-5 6 5 6 13 10 21 11-2-8-8-14-16-17zm46 17c-8 0-16 4-20 11 8 2 16 0 23-4 0-3-1-5-3-7zm-42 0c-2 2-3 4-3 7 7 4 15 6 23 4-4-7-12-11-20-11z" fill="#cf0a2c"/></svg></div>`
        },
        {
            id: "toshiba",
            name: "Toshiba (Dynabook)",
            logo: `<div class="fn-brand-badge" style="background:#dc2626;"><svg viewBox="0 0 100 100" width="30" height="30"><text x="50" y="58" font-family="'Arial Black', Impact, sans-serif" font-weight="900" font-size="14" fill="#fff" text-anchor="middle" letter-spacing="0.5">TOSHIBA</text></svg></div>`
        }
    ],
    mac: [
        {
            id: "macbook_pro",
            name: "MacBook Pro",
            logo: `<div class="fn-brand-badge" style="background:linear-gradient(135deg, #0f172a, #334155);"><svg viewBox="0 0 48 48" width="26" height="26" fill="none" stroke="#fff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="8" y="7" width="32" height="23" rx="2.5" fill="#1e293b"/><path d="M4 35h40a1.5 1.5 0 0 0 1.5-1.5v-1H2.5v1A1.5 1.5 0 0 0 4 35z" fill="#475569"/><path d="M21 32.5h6" stroke="#94a3b8" stroke-width="2"/><circle cx="24" cy="18" r="3" fill="#ea580c"/></svg></div>`
        },
        {
            id: "macbook_air",
            name: "MacBook Air",
            logo: `<div class="fn-brand-badge" style="background:linear-gradient(135deg, #1e293b, #0ea5e9);"><svg viewBox="0 0 48 48" width="26" height="26" fill="none" stroke="#fff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 10l28 5v16l-28-5z" fill="#0284c7" fill-opacity="0.3"/><rect x="8" y="9" width="32" height="22" rx="2" fill="#0f172a"/><path d="M4 34h40l-2 2H6z" fill="#38bdf8"/><path d="M24 16v8" stroke="#38bdf8" stroke-width="2"/></svg></div>`
        },
        {
            id: "imac",
            name: "iMac",
            logo: `<div class="fn-brand-badge" style="background:linear-gradient(135deg, #0284c7, #2563eb);"><svg viewBox="0 0 48 48" width="26" height="26" fill="none" stroke="#fff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="6" y="6" width="36" height="26" rx="3" fill="#0f172a"/><rect x="6" y="27" width="36" height="5" fill="#38bdf8"/><path d="M24 32v10m-8 0h16" stroke="#fff" stroke-width="3"/></svg></div>`
        },
        {
            id: "mac_mini",
            name: "Mac Mini & Studio",
            logo: `<div class="fn-brand-badge" style="background:linear-gradient(135deg, #334155, #64748b);"><svg viewBox="0 0 48 48" width="26" height="26" fill="none" stroke="#fff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="8" y="14" width="32" height="20" rx="4" fill="#0f172a"/><line x1="8" y1="28" x2="40" y2="28" stroke="#94a3b8" stroke-width="1.5"/><circle cx="24" cy="21" r="2.5" fill="#38bdf8"/><circle cx="36" cy="21" r="1.5" fill="#22c55e"/></svg></div>`
        },
        {
            id: "mac_pro",
            name: "Mac Pro",
            logo: `<div class="fn-brand-badge" style="background:linear-gradient(135deg, #0f172a, #ea580c);"><svg viewBox="0 0 48 48" width="26" height="26" fill="none" stroke="#fff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="12" y="6" width="24" height="34" rx="4" fill="#1e293b"/><circle cx="20" cy="14" r="2" fill="#ea580c"/><circle cx="28" cy="14" r="2" fill="#ea580c"/><circle cx="20" cy="22" r="2" fill="#ea580c"/><circle cx="28" cy="22" r="2" fill="#ea580c"/><circle cx="20" cy="30" r="2" fill="#ea580c"/><circle cx="28" cy="30" r="2" fill="#ea580c"/><path d="M12 42h6m12 0h6M12 6h6m12 0h6" stroke="#fff" stroke-width="2"/></svg></div>`
        }
    ],
    tablet: [
        {
            id: "apple_ipad",
            name: "Apple iPad",
            logo: `<div class="fn-brand-badge" style="background:#0f172a;"><svg viewBox="0 0 170 170" width="24" height="24" fill="#ffffff"><path d="M150.37 130.25c-2.45 5.66-5.35 10.87-8.71 15.66-4.58 6.53-8.33 11.05-11.22 13.56-4.48 4.12-9.28 6.23-14.42 6.35-3.69 0-8.14-1.05-13.32-3.18-5.19-2.12-9.97-3.17-14.34-3.17-4.58 0-9.49 1.05-14.75 3.17-5.26 2.13-9.5 3.24-12.74 3.35-4.35.13-9.16-1.9-14.42-6.08-3.69-3.04-7.67-7.81-11.96-14.34-5.67-8.6-10.15-18.49-13.43-29.68-3.28-11.18-4.92-21.84-4.92-31.97 0-14.35 3.63-26.06 10.88-35.13 7.25-9.08 16.32-13.73 27.22-13.97 5.1 0 10.63 1.48 16.6 4.44 5.97 2.95 10.05 4.49 12.23 4.62 1.95 0 6.24-1.63 12.87-4.9 6.63-3.26 12.37-4.66 17.21-4.18 13.06.98 23.41 5.75 31.06 14.32-11.53 6.96-17.18 16.64-16.94 29.04.24 9.9 4.13 18.23 11.66 24.99 7.53 6.75 16.51 10.62 26.94 11.6-2.52 7.62-5.46 15.02-8.81 22.21zM119.22 33.15c0-7.39 2.68-14.28 8.04-20.67 5.36-6.39 12.01-10.45 19.95-12.18.33 1.2.49 2.29.49 3.28 0 7.39-2.79 14.33-8.36 20.83-5.57 6.5-12.39 10.46-20.45 11.89-.11-1.09-.17-2.17-.17-3.15z"/></svg></div>`
        },
        {
            id: "samsung_tab",
            name: "Samsung Galaxy Tab",
            logo: `<div class="fn-brand-badge" style="background:#0057b8;"><svg viewBox="0 0 100 100" width="28" height="28"><path d="M35 63c3 4 8 7 15 7 9 0 14-5 14-11 0-7-5-10-14-12l-5-1c-6-1-10-4-10-9 0-6 5-11 13-11 6 0 11 3 14 6l-3 4c-3-3-7-5-11-5-6 0-9 3-9 7 0 4 3 7 10 8l5 1c7 2 14 5 14 13 0 7-6 13-18 13-8 0-14-4-18-9l3-4z" fill="#fff"/></svg></div>`
        },
        {
            id: "xiaomi_pad",
            name: "Xiaomi Pad",
            logo: `<div class="fn-brand-badge" style="background:#ff6900;"><svg viewBox="0 0 100 100" width="26" height="26"><path d="M26 30v40h10V46l10 14 10-14v24h10V30H56l-8 12-8-12H26zm48 0v40h10V30H74z" fill="#fff"/></svg></div>`
        },
        {
            id: "surface_pro",
            name: "Surface Pro",
            logo: `<div class="fn-brand-badge" style="background:#1e293b;"><svg viewBox="0 0 100 100" width="28" height="28"><rect x="25" y="25" width="22" height="22" fill="#f25022"/><rect x="53" y="25" width="22" height="22" fill="#7fba00"/><rect x="25" y="53" width="22" height="22" fill="#00a4ef"/><rect x="53" y="53" width="22" height="22" fill="#ffb900"/></svg></div>`
        },
        {
            id: "lenovo_tab",
            name: "Lenovo Tab",
            logo: `<div class="fn-brand-badge" style="background:#e2231a;"><svg viewBox="0 0 100 100" width="30" height="30"><rect x="18" y="32" width="64" height="36" fill="#fff"/><text x="50" y="58" font-family="Arial, sans-serif" font-weight="900" font-size="16" fill="#e2231a" text-anchor="middle">Lenovo</text></svg></div>`
        },
        {
            id: "huawei_matepad",
            name: "Huawei MatePad",
            logo: `<div class="fn-brand-badge" style="background:#111827;"><svg viewBox="0 0 100 100" width="28" height="28"><path d="M50 22c-3 8-3 16 0 24 3-8 3-16 0-24zm14 5c-6 6-9 14-8 22 7-4 13-11 15-18-2-2-4-3-7-4zm-28 0c-3 1-5 2-7 4 2 7 8 14 15 18 1-8-2-16-8-22zm39 17c-8 3-14 9-16 17 8-1 16-5 21-11-1-3-3-5-5-6zm-50 0c-2 1-4 3-5 6 5 6 13 10 21 11-2-8-8-14-16-17zm46 17c-8 0-16 4-20 11 8 2 16 0 23-4 0-3-1-5-3-7zm-42 0c-2 2-3 4-3 7 7 4 15 6 23 4-4-7-12-11-20-11z" fill="#cf0a2c"/></svg></div>`
        },
        {
            id: "oppo_pad",
            name: "Oppo Pad",
            logo: `<div class="fn-brand-badge" style="background:#008a38;"><svg viewBox="0 0 100 100" width="30" height="30"><text x="50" y="58" font-family="Arial, sans-serif" font-weight="900" font-size="20" fill="#fff" text-anchor="middle">OPPO</text></svg></div>`
        }
    ],
    smartwatch: [
        {
            id: "apple_watch",
            name: "Apple Watch",
            logo: `<div class="fn-brand-badge" style="background:#0f172a;"><svg viewBox="0 0 170 170" width="24" height="24" fill="#ffffff"><path d="M150.37 130.25c-2.45 5.66-5.35 10.87-8.71 15.66-4.58 6.53-8.33 11.05-11.22 13.56-4.48 4.12-9.28 6.23-14.42 6.35-3.69 0-8.14-1.05-13.32-3.18-5.19-2.12-9.97-3.17-14.34-3.17-4.58 0-9.49 1.05-14.75 3.17-5.26 2.13-9.5 3.24-12.74 3.35-4.35.13-9.16-1.9-14.42-6.08-3.69-3.04-7.67-7.81-11.96-14.34-5.67-8.6-10.15-18.49-13.43-29.68-3.28-11.18-4.92-21.84-4.92-31.97 0-14.35 3.63-26.06 10.88-35.13 7.25-9.08 16.32-13.73 27.22-13.97 5.1 0 10.63 1.48 16.6 4.44 5.97 2.95 10.05 4.49 12.23 4.62 1.95 0 6.24-1.63 12.87-4.9 6.63-3.26 12.37-4.66 17.21-4.18 13.06.98 23.41 5.75 31.06 14.32-11.53 6.96-17.18 16.64-16.94 29.04.24 9.9 4.13 18.23 11.66 24.99 7.53 6.75 16.51 10.62 26.94 11.6-2.52 7.62-5.46 15.02-8.81 22.21zM119.22 33.15c0-7.39 2.68-14.28 8.04-20.67 5.36-6.39 12.01-10.45 19.95-12.18.33 1.2.49 2.29.49 3.28 0 7.39-2.79 14.33-8.36 20.83-5.57 6.5-12.39 10.46-20.45 11.89-.11-1.09-.17-2.17-.17-3.15z"/></svg></div>`
        },
        {
            id: "samsung_watch",
            name: "Galaxy Watch",
            logo: `<div class="fn-brand-badge" style="background:#0057b8;"><svg viewBox="0 0 100 100" width="28" height="28"><path d="M35 63c3 4 8 7 15 7 9 0 14-5 14-11 0-7-5-10-14-12l-5-1c-6-1-10-4-10-9 0-6 5-11 13-11 6 0 11 3 14 6l-3 4c-3-3-7-5-11-5-6 0-9 3-9 7 0 4 3 7 10 8l5 1c7 2 14 5 14 13 0 7-6 13-18 13-8 0-14-4-18-9l3-4z" fill="#fff"/></svg></div>`
        },
        {
            id: "garmin",
            name: "Garmin",
            logo: `<div class="fn-brand-badge" style="background:#007cc3;"><svg viewBox="0 0 100 100" width="28" height="28"><polygon points="50,24 76,74 24,74" fill="#fff"/></svg></div>`
        },
        {
            id: "huawei_watch",
            name: "Huawei Watch",
            logo: `<div class="fn-brand-badge" style="background:#111827;"><svg viewBox="0 0 100 100" width="28" height="28"><path d="M50 22c-3 8-3 16 0 24 3-8 3-16 0-24zm14 5c-6 6-9 14-8 22 7-4 13-11 15-18-2-2-4-3-7-4zm-28 0c-3 1-5 2-7 4 2 7 8 14 15 18 1-8-2-16-8-22zm39 17c-8 3-14 9-16 17 8-1 16-5 21-11-1-3-3-5-5-6zm-50 0c-2 1-4 3-5 6 5 6 13 10 21 11-2-8-8-14-16-17zm46 17c-8 0-16 4-20 11 8 2 16 0 23-4 0-3-1-5-3-7zm-42 0c-2 2-3 4-3 7 7 4 15 6 23 4-4-7-12-11-20-11z" fill="#cf0a2c"/></svg></div>`
        },
        {
            id: "google_watch",
            name: "Pixel Watch",
            logo: `<div class="fn-brand-badge" style="background:#ffffff; border:1.5px solid #e2e8f0; border-radius:50%;"><svg viewBox="0 0 100 100" width="24" height="24"><path d="M78 51c0-2-.2-4-.6-6H50v12h16c-.7 3.6-2.8 6.7-5.9 8.8v7.3h9.5C75.2 68 78 60 78 51z" fill="#4285f4"/><path d="M50 79c8 0 14.7-2.6 19.6-7.2l-9.5-7.3c-2.7 1.8-6.1 2.9-10.1 2.9-7.8 0-14.4-5.3-16.7-12.4H23.5v7.6C28.4 72.3 38.5 79 50 79z" fill="#34a853"/><path d="M33.3 55c-.6-1.8-.9-3.7-.9-5.7s.3-3.9.9-5.7V36H23.5C21.6 39.8 20.5 44 20.5 49.3s1.1 9.5 3 13.3L33.3 55z" fill="#fbbc05"/><path d="M50 34c4.3 0 8.2 1.5 11.3 4.4l8.5-8.5C64.6 25.2 57.9 22.5 50 22.5c-11.5 0-21.6 6.7-26.5 16.5l9.8 7.6C35.6 39.3 42.2 34 50 34z" fill="#ea4335"/></svg></div>`
        }
    ],
    pc: [
        {
            id: "gaming_pc",
            name: "PC Gaming",
            logo: `<div class="fn-brand-badge" style="background:#111827;"><svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="#ea580c" stroke-width="2"><line x1="6" y1="12" x2="10" y2="12"/><line x1="8" y1="10" x2="8" y2="14"/><circle cx="15" cy="13" r="1" fill="#ea580c"/><circle cx="18" cy="11" r="1" fill="#ea580c"/><rect x="2" y="6" width="20" height="12" rx="4"/></svg></div>`
        },
        {
            id: "graphic_pc",
            name: "PC Đồ Họa 3D",
            logo: `<div class="fn-brand-badge" style="background:#0284c7;"><svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="#fff" stroke-width="2"><path d="M12 2l10 6.5v7L12 22 2 15.5v-7L12 2z"/><path d="M12 22v-6.5M22 8.5l-10 7L2 8.5"/></svg></div>`
        },
        {
            id: "dell_optiplex",
            name: "Dell Optiplex",
            logo: `<div class="fn-brand-badge" style="background:#007db8;"><svg viewBox="0 0 100 100" width="28" height="28"><path d="M22 35h12c6 0 10 4 10 10s-4 10-10 10H22V35zm7 15h5c3 0 5-2 5-5s-2-5-5-5h-5v10zm18-15h16v5H54v4h11v5H54v6h13v5H47V35zm23 0h7v25h-7V35zm10 0h7v25h-7V35z" fill="#fff"/></svg></div>`
        },
        {
            id: "hp_prodesk",
            name: "HP ProDesk",
            logo: `<div class="fn-brand-badge" style="background:#0096d6; border-radius:50%;"><svg viewBox="0 0 100 100" width="28" height="28"><path d="M43 25l-9 50h8l3-17h9c8 0 14-5 15-13 1-8-3-13-11-13H43zm11 8c4 0 6 2 5 6-.8 4-4 6-7 6h-6l2-12h7z" fill="#fff"/></svg></div>`
        },
        {
            id: "lenovo_thinkcentre",
            name: "Lenovo ThinkCentre",
            logo: `<div class="fn-brand-badge" style="background:#e2231a;"><svg viewBox="0 0 100 100" width="28" height="28"><rect x="18" y="32" width="64" height="36" fill="#fff"/><text x="50" y="58" font-family="Arial, sans-serif" font-weight="900" font-size="16" fill="#e2231a" text-anchor="middle">Lenovo</text></svg></div>`
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
    initPromoBanner3s();

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

    // ====== COUNTER ANIMATION ======
    const statNumbers = document.querySelectorAll('.fn-stat-number');
    statNumbers.forEach(el => {
        const target = parseInt(el.textContent);
        if (isNaN(target)) return;
        let current = 0;
        const increment = Math.ceil(target / 40);
        const suffix = el.querySelector('span') ? el.querySelector('span').textContent : '';
        const timer = setInterval(() => {
            current += increment;
            if (current >= target) {
                current = target;
                clearInterval(timer);
            }
            el.innerHTML = current + (suffix ? '<span>' + suffix + '</span>' : '');
        }, 30);
    });
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

// Render các thương hiệu thuộc loại thiết bị với Logo Vector SVG chính hãng căn giữa chuẩn đẹp 100%
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
        html += `
            <button type="button" class="fn-brand-card" onclick="selectBrand('${b.id}', '${b.name.replace(/'/g, "\\'")}')">
                <div class="fn-brand-logo-wrap">
                    ${b.logo}
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
        "Điểm Google chỉ được hiển thị khi có Place ID và ngày đối soát",
        "FixNear không tự báo giá — hãy yêu cầu cửa hàng xác nhận giá trọn gói trước khi sửa",
        "Không giao máy trước khi ghi nhận tình trạng, giá trọn gói và điều kiện bảo hành",
        "FixNear không công khai nhận xét mẫu hoặc nhận xét Google không có nguồn"
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
function getLocalFavorites() {
    try {
        const stored = localStorage.getItem('fixnear_favorites');
        return stored ? JSON.parse(stored) : [];
    } catch (e) {
        return [];
    }
}

function setLocalFavorites(favs) {
    try {
        localStorage.setItem('fixnear_favorites', JSON.stringify(favs));
    } catch (e) {}
}

function syncFavoritesUI() {
    const localFavs = getLocalFavorites();
    document.querySelectorAll('.fn-fav-btn').forEach(btn => {
        const shopId = parseInt(btn.getAttribute('data-shop-id'), 10);
        const icon = btn.querySelector('.fn-fav-icon') || document.getElementById(`fav-icon-${shopId}`);
        const text = document.getElementById(`fav-text-${shopId}`);
        const isServerFav = btn.getAttribute('data-favorited') === '1';
        const isFav = isServerFav || localFavs.includes(shopId);

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
        
        let localFavs = getLocalFavorites();
        let isNowFav = false;

        if (data.logged_in) {
            isNowFav = Boolean(data.favorited);
            if (isNowFav) {
                if (!localFavs.includes(shopId)) localFavs.push(shopId);
            } else {
                localFavs = localFavs.filter(id => id !== shopId);
            }
            setLocalFavorites(localFavs);
        } else {
            // Khách vãng lai: toggle localStorage
            if (localFavs.includes(shopId)) {
                localFavs = localFavs.filter(id => id !== shopId);
                isNowFav = false;
            } else {
                localFavs.push(shopId);
                isNowFav = true;
            }
            setLocalFavorites(localFavs);
        }

        if (btn) btn.setAttribute('data-favorited', isNowFav ? '1' : '0');
        if (icon) icon.textContent = isNowFav ? '❤️' : '🤍';
        if (text) text.textContent = isNowFav ? 'Đã lưu yêu thích' : 'Lưu yêu thích';
        
        // Hiệu ứng nảy nhẹ khi bấm
        if (btn) {
            btn.style.transform = 'scale(1.25)';
            setTimeout(() => { btn.style.transform = ''; }, 200);
        }

        // Thông báo nhẹ toast nếu chưa đăng nhập
        if (!data.logged_in && isNowFav) {
            showSimpleToast('❤️ Đã lưu vào mục yêu thích trình duyệt!');
        }
    } catch (e) {
        // Fallback offline / network error
        let localFavs = getLocalFavorites();
        let isNowFav = false;
        if (localFavs.includes(shopId)) {
            localFavs = localFavs.filter(id => id !== shopId);
            isNowFav = false;
        } else {
            localFavs.push(shopId);
            isNowFav = true;
        }
        setLocalFavorites(localFavs);
        if (icon) icon.textContent = isNowFav ? '❤️' : '🤍';
        if (text) text.textContent = isNowFav ? 'Đã lưu yêu thích' : 'Lưu yêu thích';
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

