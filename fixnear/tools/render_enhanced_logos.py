# -*- coding: utf-8 -*-
import os
import subprocess
from PIL import Image

BRANDS_DIR = os.path.abspath(os.path.join(os.path.dirname(__file__), '..', 'assets', 'images', 'brands'))
CHROME = r'C:\Program Files\Google\Chrome\Application\chrome.exe'

def render_svg_to_png(svg_string, out_filename, width=120, height=120):
    dest = os.path.join(BRANDS_DIR, out_filename)
    tmp_html = os.path.join(BRANDS_DIR, 'tmp_render.html')
    
    html = f'''<!DOCTYPE html>
<html>
<head>
<style>
body {{
  margin: 0;
  padding: 0;
  background: transparent;
  display: flex;
  align-items: center;
  justify-content: center;
  width: {width}px;
  height: {height}px;
}}
svg {{
  max-width: {width - 10}px;
  max-height: {height - 10}px;
  width: auto;
  height: auto;
}}
</style>
</head>
<body>
{svg_string}
</body>
</html>'''

    with open(tmp_html, 'w', encoding='utf-8') as f:
        f.write(html)
        
    cmd = [
        CHROME, '--headless=new', '--disable-gpu',
        '--default-background-color=00000000',
        f'--window-size={width},{height}',
        f'--screenshot={dest}',
        f'file:///{tmp_html.replace(os.sep, "/")}'
    ]
    subprocess.run(cmd, check=True)
    if os.path.exists(tmp_html):
        os.remove(tmp_html)
        
    im = Image.open(dest).convert('RGBA')
    im.save(dest, 'PNG')
    print(f"Rendered {out_filename}: {im.size}")

# 1. Huawei - Official Red Petal Flowers
huawei_svg = '''<svg viewBox="0 0 24 24" width="90" height="90" fill="#cf0a2c"><path d="M3.67 6.14S1.82 7.91 1.72 9.78v.35c.08 1.51 1.22 2.4 1.22 2.4 1.83 1.79 6.26 4.04 7.3 4.55 0 0 .06.03.1-.01l.02-.04v-.04C7.52 10.8 3.67 6.14 3.67 6.14zM9.65 18.6c-.02-.08-.1-.08-.1-.08l-7.38.26c.8 1.48 2.05 2.58 3.52 3.09 0 0 4.19.86 7.42-.51l.01-.01.05-.07-.03-.04c-1.89-1.27-3.49-2.64-3.49-2.64zm2.35.43c-.05 0-.08.05-.08.08l-.34 7.39c1.68.04 3.3-.39 4.67-1.24 0 0 3.39-2.58 4.2-5.96 0 0-.01-.05-.06-.05l-8.39-.22zm4.72-2.18s-.05.07-.02.1l4.98 5.48c1.33-1.03 2.3-2.39 2.82-3.92 0 0 1.22-4.11-.97-7.38 0 0-.04-.04-.07-.01l-6.74 5.73zm3.72-4.63s-.05.07-.01.1l7.07 2.22c.59-1.57.6-3.26.04-4.81 0 0-1.5-4.02-5.26-5.28 0 0-.06 0-.06.05l-1.78 7.72zM12 0C5.37 0 0 5.37 0 12c0 2.25.62 4.36 1.7 6.16l2.12-2.12C2.94 14.6 2.4 13.35 2.4 12c0-5.3 4.3-9.6 9.6-9.6s9.6 4.3 9.6 9.6c0 1.35-.54 2.6-1.42 4.04l2.12 2.12c1.08-1.8 1.7-3.91 1.7-6.16 0-6.63-5.37-12-12-12z"/></svg>'''
render_svg_to_png(huawei_svg, 'huawei.png', 120, 120)

# 2. Motorola - Official Batwing M in Circle
motorola_svg = '''<svg viewBox="0 0 100 100" width="100" height="100"><circle cx="50" cy="50" r="46" fill="#00142e"/><path d="M28 66l14-34 8 18 8-18 14 34" fill="none" stroke="#ffffff" stroke-width="7" stroke-linecap="round" stroke-linejoin="round"/></svg>'''
render_svg_to_png(motorola_svg, 'motorola.png', 120, 120)

# 3. Google - Official 4-Color 'G' Icon
google_svg = '''<svg viewBox="0 0 24 24" width="90" height="90"><path fill="#4285F4" d="M23.745 12.27c0-.7-.06-1.4-.19-2.07H12v4.51h6.6c-.29 1.52-1.14 2.8-2.4 3.65v3.03h3.88c2.27-2.09 3.665-5.17 3.665-9.12z"/><path fill="#34A853" d="M12 24c3.24 0 5.95-1.08 7.93-2.91l-3.88-3.03c-1.08.72-2.45 1.16-4.05 1.16-3.12 0-5.77-2.1-6.72-4.93H1.26v3.13C3.25 21.3 7.31 24 12 24z"/><path fill="#FBBC05" d="M5.28 14.29c-.25-.72-.38-1.49-.38-2.29s.13-1.57.38-2.29V6.58H1.26C.46 8.16 0 9.94 0 12s.46 3.84 1.26 5.42l4.02-3.13z"/><path fill="#EA4335" d="M12 4.75c1.77 0 3.35.61 4.6 1.8l3.42-3.42C17.95 1.19 15.24 0 12 0 7.31 0 3.25 2.7 1.26 6.58l4.02 3.13c.95-2.83 3.6-4.96 6.72-4.96z"/></svg>'''
render_svg_to_png(google_svg, 'google.png', 120, 120)

# 4. Samsung - Bold Iconic Blue Samsung
samsung_svg = '''<svg viewBox="0 0 160 60" width="120" height="45"><text x="80" y="42" font-family="'Arial Black', 'Helvetica Neue', Arial, sans-serif" font-weight="900" font-size="28" fill="#1428a0" text-anchor="middle" letter-spacing="1">SAMSUNG</text></svg>'''
render_svg_to_png(samsung_svg, 'samsung.png', 120, 60)

# 5. Sony - Bold Black SONY
sony_svg = '''<svg viewBox="0 0 140 50" width="110" height="40"><text x="70" y="36" font-family="Arial, Helvetica, sans-serif" font-weight="900" font-size="32" fill="#0f172a" text-anchor="middle" letter-spacing="4">SONY</text></svg>'''
render_svg_to_png(sony_svg, 'sony.png', 120, 50)

# 6. Redmi - Official Crisp Red Badge
redmi_svg = '''<svg viewBox="0 0 140 50" width="110" height="40"><text x="70" y="36" font-family="Arial, Helvetica, sans-serif" font-weight="900" font-size="30" fill="#e02020" text-anchor="middle" letter-spacing="0.5">Redmi</text></svg>'''
render_svg_to_png(redmi_svg, 'redmi.png', 120, 50)

# 7. Vivo - Official Vibrant Blue vivo
vivo_svg = '''<svg viewBox="0 0 140 50" width="110" height="40"><text x="70" y="36" font-family="Arial, Helvetica, sans-serif" font-weight="900" font-size="34" fill="#007aff" text-anchor="middle" letter-spacing="2">vivo</text></svg>'''
render_svg_to_png(vivo_svg, 'vivo.png', 120, 50)

# 8. Oppo - Official Green OPPO
oppo_svg = '''<svg viewBox="0 0 140 50" width="110" height="40"><text x="70" y="36" font-family="Arial, Helvetica, sans-serif" font-weight="900" font-size="30" fill="#008a38" text-anchor="middle" letter-spacing="2">OPPO</text></svg>'''
render_svg_to_png(oppo_svg, 'oppo.png', 120, 50)

print("ALL ENHANCED LOGOS RENDERED TO PNG SUCCESSFULLY!")
