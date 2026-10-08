"""End-to-end HTTP smoke test against an isolated JSON data directory."""

from http.cookiejar import CookieJar
from pathlib import Path
from urllib.parse import urlencode
from urllib.request import HTTPCookieProcessor, Request, build_opener
from urllib.error import HTTPError
import json
import os
import re
import shutil
import socket
import subprocess
import tempfile
import time


ROOT = Path(__file__).resolve().parents[1]


def csrf(html):
    match = re.search(r'name="csrf_token"\s+value="([0-9a-f]+)"', html)
    assert match, "CSRF token missing"
    return match.group(1)


def request(opener, base, path, fields=None):
    data = urlencode(fields).encode() if fields is not None else None
    try:
        response = opener.open(Request(base + path, data=data), timeout=10)
    except HTTPError as error:
        response = error
    return response.status, response.url, response.read().decode("utf-8", "replace")


def main():
    with tempfile.TemporaryDirectory(prefix="fixnear-e2e-") as temporary:
        data_dir = Path(temporary) / "data"
        data_dir.mkdir()
        for filename in ("shops.json", "services.json", "shop_services.json"):
            shutil.copy2(ROOT / "data" / filename, data_dir / filename)
        admin_password = "Test-admin-password-2026"
        admin_hash = subprocess.check_output(["php", "-r", f"echo password_hash('{admin_password}', PASSWORD_DEFAULT);"], text=True)
        (data_dir / "users.json").write_text(json.dumps([{
            "id": 1, "name": "Test Admin", "email": "admin-e2e@example.invalid",
            "phone": "", "password": admin_hash, "role": "admin"
        }]), encoding="utf-8")
        for filename in ("repair_requests.json", "reviews.json", "reports.json", "favorites.json"):
            (data_dir / filename).write_text("[]", encoding="utf-8")

        with socket.socket() as sock:
            sock.bind(("127.0.0.1", 0))
            port = sock.getsockname()[1]
        base = f"http://127.0.0.1:{port}"
        env = os.environ.copy()
        env.update({"FIXNEAR_DATA_DIR": str(data_dir), "FIXNEAR_DISABLE_MYSQL": "1", "FIXNEAR_ENV": "test"})
        process = subprocess.Popen(
            ["php", "-S", f"127.0.0.1:{port}", "-t", str(ROOT), str(ROOT / "router.php")],
            cwd=ROOT, env=env, stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL,
            creationflags=getattr(subprocess, "CREATE_NO_WINDOW", 0),
        )
        try:
            guest = build_opener(HTTPCookieProcessor(CookieJar()))
            for _ in range(50):
                try:
                    status, _, _ = request(guest, base, "/prices.php")
                    if status == 200:
                        break
                except OSError:
                    time.sleep(0.1)
            else:
                raise AssertionError("Test server did not start")

            status, _, prices = request(guest, base, "/prices.php")
            assert status == 200 and 'id="price-brand"' in prices and 'id="price-model"' in prices
            assert 'Tất cả 319 model' not in prices
            status, _, prices = request(guest, base, "/prices.php?device=phone&brand=apple&model=apple-iphone-16e&mode=estimate")
            assert status == 200 and "iPhone 16e" in prices and "13 kết quả" in prices

            status, _, form = request(guest, base, "/request_repair.php")
            assert status == 200
            status, _, invalid = request(guest, base, "/request_repair.php", {
                "csrf_token": csrf(form), "submit_request": "1", "device_type": "Điện thoại (Smartphone)",
                "brand_model": "iPhone 16e", "issue_type": "Thay pin", "district": "Quận 1",
                "customer_name": "Test Guest", "customer_email": "guest-e2e@example.invalid",
                "customer_phone": "0901234567"
            })
            assert status == 200 and "Số Zalo cần có" in invalid
            assert json.loads((data_dir / "repair_requests.json").read_text(encoding="utf-8")) == []
            status, _, result = request(guest, base, "/request_repair.php", {
                "csrf_token": csrf(form), "submit_request": "1", "device_type": "Điện thoại (Smartphone)",
                "brand_model": "iPhone 16e", "model_id": "apple-iphone-16e", "fault_id": "battery",
                "issue_type": "Thay pin", "symptom": "Pin tụt nhanh", "district": "Quận 1",
                "customer_name": "Test Guest", "customer_email": "guest-e2e@example.invalid",
                "customer_phone": "0901234567", "customer_zalo": "0901234567", "preferred_time": "Sáng mai"
            })
            requests = json.loads((data_dir / "repair_requests.json").read_text(encoding="utf-8"))
            assert status == 200 and len(requests) == 1 and requests[0]["customer_name"] == "Test Guest"
            assert requests[0]["customer_zalo"] == "0901234567"
            request_id = requests[0]["id"]
            status, _, tracking_form = request(guest, base, "/track_request.php")
            assert status == 200
            status, _, wrong_tracking = request(guest, base, "/track_request.php", {
                "csrf_token": csrf(tracking_form), "code": request_id, "phone": "0999999999"
            })
            assert status == 200 and "Không tìm thấy hồ sơ" in wrong_tracking
            status, _, correct_tracking = request(guest, base, "/track_request.php", {
                "csrf_token": csrf(tracking_form), "code": request_id, "phone": "0901234567"
            })
            assert status == 200 and "Hồ sơ " + request_id in correct_tracking

            user = build_opener(HTTPCookieProcessor(CookieJar()))
            status, _, form = request(user, base, "/register.php")
            assert status == 200
            status, url, _ = request(user, base, "/register.php", {
                "csrf_token": csrf(form), "name": "Test User", "email": "user-e2e@example.invalid",
                "phone": "0901234568", "password": "Test-user-password-2026",
                "confirm_password": "Test-user-password-2026"
            })
            assert status == 200 and url.endswith("/index.php")
            users = json.loads((data_dir / "users.json").read_text(encoding="utf-8"))
            assert len(users) == 2 and users[-1]["role"] == "user"

            status, _, home = request(user, base, "/index.php")
            assert status == 200
            request(user, base, "/logout.php", {"csrf_token": csrf(home)})
            status, _, form = request(user, base, "/login.php")
            assert status == 200
            status, url, _ = request(user, base, "/login.php", {
                "csrf_token": csrf(form), "email": "user-e2e@example.invalid", "password": "Test-user-password-2026"
            })
            assert status == 200 and url.endswith("/index.php")

            status, _, form = request(user, base, "/request_repair.php")
            assert status == 200
            status, _, _ = request(user, base, "/request_repair.php", {
                "csrf_token": csrf(form), "submit_request": "1", "device_type": "Laptop Windows",
                "brand_model": "Dell XPS", "issue_type": "Kiểm tra máy", "symptom": "Không khởi động",
                "district": "Quận 3", "customer_name": "Test User", "customer_email": "user-e2e@example.invalid",
                "customer_phone": "0901234568", "customer_zalo": "0901234569", "preferred_time": "Chiều mai"
            })
            requests = json.loads((data_dir / "repair_requests.json").read_text(encoding="utf-8"))
            member_request = next(item for item in requests if item["customer_name"] == "Test User")
            assert status == 200 and len(requests) == 2 and int(member_request["user_id"]) == users[-1]["id"]
            assert member_request["customer_zalo"] == "0901234569"
            assert member_request["id"] in request(user, base, "/track_request.php")[2]

            status, _, detail = request(user, base, "/shop_detail.php?id=1")
            assert status == 200
            status, url, _ = request(user, base, "/api/add_review.php", {
                "csrf_token": csrf(detail), "shop_id": "1", "rating": "4",
                "device_name": "iPhone 16e", "service_repaired": "Thay pin",
                "comment": "Đánh giá kiểm thử chờ quản trị duyệt"
            })
            reviews = json.loads((data_dir / "reviews.json").read_text(encoding="utf-8"))
            assert status == 200 and "review_added" in url and len(reviews) == 1 and reviews[0]["is_hidden"] is True
            assert "Đánh giá kiểm thử chờ quản trị duyệt" not in request(guest, base, "/shop_detail.php?id=1")[2]

            admin = build_opener(HTTPCookieProcessor(CookieJar()))
            status, _, form = request(admin, base, "/login.php")
            assert status == 200
            status, url, _ = request(admin, base, "/login.php", {
                "csrf_token": csrf(form), "email": "admin-e2e@example.invalid", "password": admin_password
            })
            assert status == 200 and url.endswith("/admin/index.php")
            status, _, live_response = request(admin, base, "/api/admin_live.php")
            live_before = json.loads(live_response)
            assert status == 200 and live_before["pending_requests"] == 2 and live_before["pending_reviews"] == 1
            assert request(guest, base, "/api/admin_live.php")[0] == 403
            status, _, dashboard = request(admin, base, "/admin/requests.php")
            assert status == 200 and request_id in dashboard and "0901234569" in dashboard
            status, _, contact_form = request(guest, base, "/contact.php")
            assert status == 200
            status, _, contact_result = request(guest, base, "/contact.php", {
                "csrf_token": csrf(contact_form), "send_contact": "1", "name": "Test Guest",
                "phone": "0901234567", "email": "guest-e2e@example.invalid",
                "type": "Đóng góp ý kiến cải tiến tính năng website",
                "message": "Kiểm thử biểu mẫu liên hệ không cần đăng nhập."
            })
            assert status == 200 and "Kiểm thử biểu mẫu liên hệ" in json.dumps(json.loads((data_dir / "contact_messages.json").read_text(encoding="utf-8")), ensure_ascii=False)
            assert "Test Guest" in request(admin, base, "/admin/contacts.php")[2]
            status, _, _ = request(admin, base, "/admin/requests.php", {
                "csrf_token": csrf(dashboard), "id": request_id, "status": "contacted"
            })
            requests = json.loads((data_dir / "repair_requests.json").read_text(encoding="utf-8"))
            assert status == 200 and next(item for item in requests if item["id"] == request_id)["status"] == "contacted"
            status, _, review_page = request(admin, base, "/admin/reviews.php")
            assert status == 200 and "Đánh giá kiểm thử chờ quản trị duyệt" in review_page
            status, _, _ = request(admin, base, "/admin/reviews.php", {
                "csrf_token": csrf(review_page), "action": "toggle_review", "review_id": str(reviews[0]["id"])
            })
            assert status == 200 and "Đánh giá kiểm thử chờ quản trị duyệt" in request(guest, base, "/shop_detail.php?id=1")[2]
            status, _, review_page = request(admin, base, "/admin/reviews.php")
            status, _, _ = request(admin, base, "/admin/reviews.php", {
                "csrf_token": csrf(review_page), "action": "reply_review",
                "review_id": str(reviews[0]["id"]), "reply_text": "Phản hồi quản trị kiểm thử"
            })
            assert status == 200 and "Phản hồi quản trị kiểm thử" in request(guest, base, "/shop_detail.php?id=1")[2]
            status, _, live_response = request(admin, base, "/api/admin_live.php")
            live_after = json.loads(live_response)
            assert status == 200 and live_after["pending_requests"] == 1 and live_after["pending_reviews"] == 0
            assert live_after["revision"] != live_before["revision"]
            print("PASS: prices, required contacts, guest/member booking, registration/login, guest contact, admin status, review moderation/reply and public sync")
        finally:
            process.terminate()
            try:
                process.wait(timeout=5)
            except subprocess.TimeoutExpired:
                process.kill()
                process.wait(timeout=5)


if __name__ == "__main__":
    main()
