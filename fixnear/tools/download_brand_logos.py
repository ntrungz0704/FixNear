# -*- coding: utf-8 -*-
"""
Download high-resolution official transparent PNG logos for all tech brands.
Sources: Wikimedia Commons API with fallback to SimpleIcons / search.
Saves to assets/images/brands/<brand_id>.png
"""

import os
import sys
import json
import urllib.request
import urllib.parse
from PIL import Image

sys.stdout.reconfigure(encoding='utf-8')

TARGET_DIR = os.path.abspath(os.path.join(os.path.dirname(__file__), '..', 'assets', 'images', 'brands'))
os.makedirs(TARGET_DIR, exist_ok=True)

BRAND_FILES = {
    'apple': ['File:Apple_logo_black.svg', 'File:Apple_logo_grey.svg'],
    'samsung': ['File:Samsung_logo.svg', 'File:Samsung_wordmark.svg'],
    'xiaomi': ['File:Xiaomi_logo_(2021-).svg', 'File:Xiaomi_logo.svg'],
    'oppo': ['File:OPPO_Logo_2019.svg', 'File:OPPO_LOGO_2019.svg'],
    'redmi': ['File:Redmi_2019_logo.svg', 'File:Redmi_Logo.svg'],
    'vivo': ['File:Vivo_logo_2019.svg', 'File:Vivo_Logo.svg'],
    'realme': ['File:Realme_logo.svg'],
    'google': ['File:Google_%22G%22_logo.svg', 'File:Google_%22G%22_Logo.svg'],
    'sony': ['File:Sony_logo.svg'],
    'huawei': ['File:Huawei_logo.svg', 'File:Huawei_Logo.svg'],
    'oneplus': ['File:OnePlus_logo.svg'],
    'poco': ['File:POCO_logo.svg'],
    'iqoo': ['File:IQOO_logo.svg'],
    'honor': ['File:Honor_logo.svg', 'File:Honor_2018_logo.svg'],
    'nothing': ['File:Nothing_Technology_logo.svg', 'File:Nothing_logo.svg'],
    'nokia': ['File:Nokia_wordmark.svg', 'File:Nokia_2023_logo.svg'],
    'motorola': ['File:Motorola_new_logo.svg', 'File:Motorola_M_symbol_blue.svg'],
    'infinix': ['File:Infinix_logo.svg'],
    'tecno': ['File:Tecno_Mobile_logo.svg', 'File:Tecno_logo.svg'],
    'zte': ['File:ZTE_logo.svg'],
    'dell': ['File:Dell_logo_2016.svg', 'File:Dell_Logo.svg'],
    'hp': ['File:HP_logo_630.svg', 'File:HP_Logo.svg'],
    'lenovo': ['File:Lenovo_logo_2015.svg', 'File:Lenovo_Logo.svg'],
    'asus': ['File:Asus_logo.svg', 'File:Asus_Logo.svg'],
    'acer': ['File:Acer_2011.svg', 'File:Acer_logo.svg'],
    'msi': ['File:MSI_Logo.svg', 'File:MSI_logo.svg'],
    'microsoft': ['File:Microsoft_logo_(2012).svg', 'File:Microsoft_Logo.svg'],
    'razer': ['File:Razer_logo.svg', 'File:Razer_wordmark.svg'],
    'lg': ['File:LG_logo_(2015).svg', 'File:LG_Logo.svg'],
    'garmin': ['File:Garmin_logo.svg']
}

HEADERS = {'User-Agent': 'FixNearApp/1.0 (contact@fixnear.vn)'}

def fetch_wiki_thumb(title, width=160):
    try:
        url = f"https://en.wikipedia.org/w/api.php?action=query&titles={urllib.parse.quote(title)}&prop=imageinfo&iiprop=url&iiurlwidth={width}&format=json"
        req = urllib.request.Request(url, headers=HEADERS)
        with urllib.request.urlopen(req, timeout=8) as resp:
            data = json.loads(resp.read().decode('utf-8'))
            pages = data['query']['pages']
            for _, p in pages.items():
                if 'imageinfo' in p and len(p['imageinfo']) > 0:
                    return p['imageinfo'][0].get('thumburl') or p['imageinfo'][0].get('url')
    except Exception as e:
        pass
    return None

def search_wiki_file(query, width=160):
    try:
        search_url = f"https://en.wikipedia.org/w/api.php?action=query&list=search&srsearch={urllib.parse.quote(query + ' logo')}&srnamespace=6&format=json"
        req = urllib.request.Request(search_url, headers=HEADERS)
        with urllib.request.urlopen(req, timeout=8) as resp:
            data = json.loads(resp.read().decode('utf-8'))
            results = data['query']['search']
            if results:
                title = results[0]['title']
                return fetch_wiki_thumb(title, width)
    except Exception as e:
        pass
    return None

def download_png(brand_key):
    dest_path = os.path.join(TARGET_DIR, f"{brand_key}.png")
    if os.path.exists(dest_path) and os.path.getsize(dest_path) > 500:
        print(f"[EXISTS] {brand_key} -> {dest_path}")
        return True

    # 1. Try predefined titles
    thumb_url = None
    titles = BRAND_FILES.get(brand_key, [])
    for t in titles:
        thumb_url = fetch_wiki_thumb(t)
        if thumb_url:
            break

    # 2. Try search
    if not thumb_url:
        thumb_url = search_wiki_file(brand_key)

    if thumb_url:
        try:
            req = urllib.request.Request(thumb_url, headers=HEADERS)
            with urllib.request.urlopen(req, timeout=10) as resp:
                data = resp.read()
                with open(dest_path, 'wb') as f:
                    f.write(data)
                
                # Standardize with PIL: ensure RGBA mode
                try:
                    im = Image.open(dest_path)
                    im = im.convert('RGBA')
                    im.save(dest_path, 'PNG')
                except Exception:
                    pass

                print(f"[OK] {brand_key}: {len(data)} bytes from {thumb_url[:60]}...")
                return True
        except Exception as e:
            print(f"[FAIL DL] {brand_key}: {e}")
    else:
        print(f"[NOT FOUND] {brand_key}")
    return False

def main():
    print(f"Target Directory: {TARGET_DIR}")
    success_count = 0
    for b in BRAND_FILES:
        ok = download_png(b)
        if ok:
            success_count += 1
    print(f"\nDone: {success_count}/{len(BRAND_FILES)} brand logos ready.")

if __name__ == '__main__':
    main()
