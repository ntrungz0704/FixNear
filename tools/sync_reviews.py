import json
import sys
import subprocess

sys.stdout.reconfigure(encoding='utf-8')

with open('data/reviews.json', 'r', encoding='utf-8') as f:
    reviews = json.load(f)

sql_lines = ['TRUNCATE TABLE reviews;']
for r in reviews:
    name = r['user_name'].replace("'", "''")
    dev = (r.get('device_name') or '').replace("'", "''")
    cmt = r['comment'].replace("'", "''")
    admin_rep = (r.get('admin_reply') or '').replace("'", "''")
    admin_rep_val = f"'{admin_rep}'" if admin_rep else 'NULL'
    admin_rep_at = f"'{r['admin_reply_at']}'" if r.get('admin_reply_at') else 'NULL'
    created = r.get('created_at', '2026-09-15 10:00:00')
    sql = f"INSERT INTO reviews (id, shop_id, user_name, rating, device_repaired, comment, is_verified_customer, created_at, admin_reply, admin_reply_at) VALUES ({r['id']}, {r['shop_id']}, '{name}', {r['rating']}, '{dev}', '{cmt}', 1, '{created}', {admin_rep_val}, {admin_rep_at});"
    sql_lines.append(sql)

full_sql = '\n'.join(sql_lines)
with open('sync_reviews.sql', 'w', encoding='utf-8') as f:
    f.write(full_sql)

print(f"Generated sync_reviews.sql with {len(reviews)} reviews")
res = subprocess.run([r'C:\xampp\mysql\bin\mysql.exe', '-u', 'root', '--default-character-set=utf8mb4', 'fixnear_db', '-e', full_sql], capture_output=True, text=True, encoding='utf-8')
print("MySQL stdout:", res.stdout)
if res.stderr:
    print("MySQL stderr:", res.stderr)
print("Return code:", res.returncode)
