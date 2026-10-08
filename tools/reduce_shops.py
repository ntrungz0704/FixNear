import json
import sys
from collections import defaultdict
import datetime

# Use UTF-8 for Windows output
sys.stdout.reconfigure(encoding='utf-8')

data_file = r"d:\Kỹ năng làm việc\fixnear\data\shops.json"

with open(data_file, 'r', encoding='utf-8') as f:
    shops = json.load(f)

districts = defaultdict(list)
for shop in shops:
    dist_name = shop.get('district', 'Unknown')
    districts[dist_name].append(shop)

filtered_shops = []
for dist, dist_shops in districts.items():
    dist_shops.sort(key=lambda x: float(x.get('google_rating', 0) or 0), reverse=True)
    filtered_shops.extend(dist_shops[:4])

stats_discount = []
for i, shop in enumerate(filtered_shops):
    shop['id'] = i + 1
    
    is_verified = shop.get('is_verified', False)
    if 'Điện Thoại Vui' in shop.get('name', '') and is_verified:
        shop['student_discount'] = 'Chương trình Back to School: Miễn phí vệ sinh + giảm giá tiền công (xuất trình thẻ SV)'
        stats_discount.append(shop['name'])
    else:
        shop['student_discount'] = ''

with open(data_file, 'w', encoding='utf-8') as f:
    json.dump(filtered_shops, f, ensure_ascii=False, indent=2)

print(f"Total shops: {len(filtered_shops)}")
print("Shops per district:")
dist_counts = defaultdict(int)
for s in filtered_shops:
    dist_counts[s.get('district', 'Unknown')] += 1
for d, count in sorted(dist_counts.items()):
    print(f"  {d}: {count}")

print(f"\nShops with student discount: {len(stats_discount)}")
for name in stats_discount:
    print(f"  - {name}")

sql_lines = [
    "USE fixnear_db;",
    "SET FOREIGN_KEY_CHECKS = 0;",
    "TRUNCATE TABLE shops;",
]

for s in filtered_shops:
    def esc(val):
        if val is None:
            return "NULL"
        if isinstance(val, bool):
            return "1" if val else "0"
        if isinstance(val, (int, float)):
            return str(val)
        if isinstance(val, list):
            val = ",".join(val)
        return "'" + str(val).replace("'", "''") + "'"
    
    fields = ['id', 'name', 'address', 'ward', 'district', 'phone', 'opening_hours', 'map_url', 'latitude', 'longitude', 'description', 'google_rating', 'google_reviews_count', 'devices', 'is_verified', 'allows_onsite_watch', 'requires_component_signing', 'student_discount', 'image']
    
    vals = [esc(s.get(f)) for f in fields]
    sql_lines.append(f"INSERT INTO shops ({', '.join(fields)}) VALUES ({', '.join(vals)});")

sql_lines.append("SET FOREIGN_KEY_CHECKS = 1;")

with open(r"d:\Kỹ năng làm việc\fixnear\tools\update_db.sql", 'w', encoding='utf-8') as f:
    f.write("\n".join(sql_lines))

print("\nSaved SQL to update_db.sql")
