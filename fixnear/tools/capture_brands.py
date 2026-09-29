import os
import time
import subprocess

# We can launch chrome headless and take screenshot
chrome = r'C:\Program Files\Google\Chrome\Application\chrome.exe'
out_png = os.path.abspath('brand_screenshot.png')

# Let's create a test HTML that loads index.php with selectDeviceType('phone') triggered or tests the brand grid
test_html = os.path.abspath('test_brands_view.html')
with open(test_html, 'w', encoding='utf-8') as f:
    f.write('''<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<link rel="stylesheet" href="assets/css/style.css">
<style>
body { background: #f8fafc; padding: 40px; font-family: sans-serif; }
.container { max-width: 900px; margin: 0 auto; background: #fff; border-radius: 20px; padding: 30px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
</style>
</head>
<body>
<div class="container">
    <h3 style="text-align: center; margin-bottom: 20px; font-weight: 900;">Chọn thương hiệu (Select your brand)</h3>
    <div class="fn-brands-grid" id="fn-brands-grid-container"></div>
</div>
<script src="assets/js/main.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    renderBrands('phone');
});
</script>
</body>
</html>''')

cmd = [
    chrome, '--headless=new', '--disable-gpu',
    '--window-size=1200,900',
    f'--screenshot={out_png}',
    f'file:///{test_html.replace(os.sep, "/")}'
]
subprocess.run(cmd, check=True)
print("Screenshot captured successfully! File:", out_png, "Size:", os.path.getsize(out_png))

if os.path.exists(test_html):
    os.remove(test_html)
