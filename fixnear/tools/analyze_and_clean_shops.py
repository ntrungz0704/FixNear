import json
import os
import shutil
import sys
from collections import Counter

# Set standard output encoding to UTF-8 for Vietnamese characters on Windows
sys.stdout.reconfigure(encoding='utf-8')

JSON_PATH = r"d:\Kỹ năng làm việc\fixnear\data\shops.json"
XAMPP_JSON_PATH = r"C:\xampp\htdocs\fixnear\data\shops.json"

def main():
    print("=" * 60)
    print("FIXNEAR - PHÂN TÍCH VÀ LÀM SẠCH DỮ LIỆU CỬA HÀNG (SHOPS)")
    print("=" * 60)

    # 1. Đọc dữ liệu từ shops.json
    if not os.path.exists(JSON_PATH):
        print(f"[LỖI] Không tìm thấy file: {JSON_PATH}")
        sys.exit(1)

    with open(JSON_PATH, "r", encoding="utf-8") as f:
        shops = json.load(f)

    # --- BƯỚC 1: Đếm tổng số shop ban đầu ---
    total_shops_initial = len(shops)
    print(f"\n1. TỔNG SỐ CỬA HÀNG BAN ĐẦU: {total_shops_initial}")

    # --- BƯỚC 2 & 3: Thống kê theo quận/huyện ban đầu ---
    district_counts_initial = Counter(s.get("district", "Chưa phân loại") for s in shops)
    sorted_districts_initial = sorted(district_counts_initial.items(), key=lambda x: (-x[1], x[0]))
    total_districts_initial = len(district_counts_initial)

    print(f"\n2. SỐ LƯỢNG CỬA HÀNG THEO TỪNG QUẬN/HUYỆN (BAN ĐẦU):")
    for district, count in sorted_districts_initial:
        print(f"   - {district}: {count} cửa hàng")

    print(f"\n3. TỔNG SỐ QUẬN/HUYỆN DUY NHẤT BAN ĐẦU: {total_districts_initial}")

    # --- BƯỚC 4: Tìm tất cả các cửa hàng có google_rating < 4.0 ---
    low_rating_shops = [
        s for s in shops
        if float(s.get("google_rating", 0) or 0) < 4.0
    ]

    print(f"\n4. DANH SÁCH CỬA HÀNG CÓ ĐÁNH GIÁ GOOGLE < 4.0 ({len(low_rating_shops)} cửa hàng):")
    if low_rating_shops:
        for s in low_rating_shops:
            print(f"   - [ID: {s.get('id')}] {s.get('name')} | Rating: {s.get('google_rating')}")
    else:
        print("   -> Không có cửa hàng nào có google_rating < 4.0 (Tất cả cửa hàng đều đạt từ 4.4 trở lên).")

    # --- BƯỚC 5: Xóa TOÀN BỘ giá trị student_discount (đặt thành rỗng '') ---
    discount_cleared_count = 0
    for s in shops:
        if s.get("student_discount"):
            discount_cleared_count += 1
        s["student_discount"] = ""

    print(f"\n5. LÀM SẠCH GIẢM GIÁ SINH VIÊN (student_discount):")
    print(f"   - Đã xóa và đặt thành '' cho tất cả {len(shops)} cửa hàng (trong đó có {discount_cleared_count} cửa hàng có giá trị trước đó).")

    # --- BƯỚC 6: Loại bỏ các shop có google_rating < 4.0 ---
    cleaned_shops = [
        s for s in shops
        if float(s.get("google_rating", 0) or 0) >= 4.0
    ]
    removed_count = len(shops) - len(cleaned_shops)
    print(f"\n6. LOẠI BỎ CỬA HÀNG CÓ RATING < 4.0:")
    print(f"   - Số lượng cửa hàng bị loại bỏ: {removed_count}")

    # --- BƯỚC 7: Lưu dữ liệu đã làm sạch vào shops.json ---
    with open(JSON_PATH, "w", encoding="utf-8") as f:
        json.dump(cleaned_shops, f, ensure_ascii=False, indent=2)
    print(f"\n7. ĐÃ LƯU DỮ LIỆU ĐÃ LÀM SẠCH VÀO: {JSON_PATH}")

    # --- BƯỚC 8: Báo cáo số liệu cuối cùng ---
    total_shops_final = len(cleaned_shops)
    district_counts_final = Counter(s.get("district", "Chưa phân loại") for s in cleaned_shops)
    sorted_districts_final = sorted(district_counts_final.items(), key=lambda x: (-x[1], x[0]))
    total_districts_final = len(district_counts_final)

    print(f"\n8. BÁO CÁO SỐ LIỆU CUỐI CÙNG:")
    print(f"   - Tổng số cửa hàng còn lại: {total_shops_final}")
    print(f"   - Tổng số quận/huyện: {total_districts_final}")
    print(f"   - Phân bố theo quận/huyện:")
    for district, count in sorted_districts_final:
        print(f"     + {district}: {count} cửa hàng")

    # --- BƯỚC 9: Cập nhật MySQL fixnear_db ---
    print("\n9. CẬP NHẬT CƠ SỞ DỮ LIỆU MYSQL (fixnear_db):")
    try:
        import mysql.connector

        conn = mysql.connector.connect(
            host="127.0.0.1",
            user="root",
            password="",
            database="fixnear_db"
        )
        cur = conn.cursor()

        # Update student_discount = ''
        cur.execute("UPDATE shops SET student_discount = ''")
        updated_discount_rows = cur.rowcount
        print(f"   - SET student_discount = '': {updated_discount_rows} dòng bị ảnh hưởng / cập nhật.")

        # Delete google_rating < 4.0
        cur.execute("DELETE FROM shops WHERE google_rating < 4.0")
        deleted_rating_rows = cur.rowcount
        print(f"   - DELETE FROM shops WHERE google_rating < 4.0: {deleted_rating_rows} dòng bị xóa.")

        conn.commit()

        # Kiểm tra lại số lượng trong database
        cur.execute("SELECT COUNT(*) FROM shops")
        db_total_shops = cur.fetchone()[0]

        cur.execute("SELECT COUNT(DISTINCT district) FROM shops")
        db_total_districts = cur.fetchone()[0]

        cur.execute("SELECT COUNT(*) FROM shops WHERE student_discount != '' AND student_discount IS NOT NULL")
        db_shops_with_discount = cur.fetchone()[0]

        print(f"   - MySQL sau khi cập nhật: {db_total_shops} cửa hàng, {db_total_districts} quận/huyện.")
        print(f"   - Số cửa hàng còn student_discount trong DB: {db_shops_with_discount}")

        cur.close()
        conn.close()
        print("   -> Cập nhật MySQL thành công và đóng kết nối an toàn.")

    except Exception as e:
        print(f"   [LỖI MYSQL]: {e}")
        sys.exit(1)

    # --- BƯỚC 10: Sao chép file sang XAMPP ---
    print(f"\n10. SAO CHÉP DỮ LIỆU SANG XAMPP:")
    try:
        os.makedirs(os.path.dirname(XAMPP_JSON_PATH), exist_ok=True)
        shutil.copy2(JSON_PATH, XAMPP_JSON_PATH)
        print(f"   - Đã sao chép thành công sang: {XAMPP_JSON_PATH}")
        print(f"   - Kích thước file nguồn: {os.path.getsize(JSON_PATH)} bytes")
        print(f"   - Kích thước file XAMPP: {os.path.getsize(XAMPP_JSON_PATH)} bytes")
    except Exception as e:
        print(f"   [LỖI SAO CHÉP]: {e}")
        sys.exit(1)

    print("\n" + "=" * 60)
    print("HOÀN THÀNH TẤT CẢ CÁC BƯỚC THÀNH CÔNG!")
    print("=" * 60)

if __name__ == "__main__":
    main()
