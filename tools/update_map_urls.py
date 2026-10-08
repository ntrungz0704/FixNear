import json
import urllib.parse
import sys

sys.stdout.reconfigure(encoding='utf-8')

json_path = r'C:\Users\ntrun\.gemini\antigravity\scratch\fixnear\data\shops.json'

with open(json_path, 'r', encoding='utf-8') as f:
    shops = json.load(f)

updated_count = 0
for s in shops:
    lat = s.get('latitude')
    lng = s.get('longitude')
    name = s.get('name')
    if lat and lng and name:
        enc_name = urllib.parse.quote(name)
        # Direct pinpoint Google Maps link with exact name pin, no competitor ad search results
        s['map_url'] = f"https://maps.google.com/?q=loc:{lat}+{lng}+({enc_name})"
        updated_count += 1

with open(json_path, 'w', encoding='utf-8') as f:
    json.dump(shops, f, ensure_ascii=False, indent=2)

print(f"Updated {updated_count} shops with clean pinpoint Google Maps URLs.")
