@echo off
chcp 65001 > nul
title FixNear Server
echo ========================================================
echo       FIXNEAR - NỀN TẢNG TRA CỨU SỬA CHỮA MIỀN NAM
echo ========================================================
echo Đang kiểm tra cổng kết nối...
set PORT=8080
netstat -ano | findstr /R /C:":8000 " >nul
if errorlevel 1 (
    set PORT=8000
) else (
    echo [LƯU Ý] Cổng 8000 đang bận bởi ứng dụng khác.
    echo Tự động khởi chạy trên cổng %PORT% để tránh xung đột!
)
echo ========================================================
echo Máy chủ FixNear đang chạy tại: http://localhost:%PORT%
echo Cổng quản trị Admin: http://localhost:%PORT%/admin/
echo Nhấn Ctrl + C để dừng máy chủ.
echo ========================================================
timeout /t 1 > nul
start http://localhost:%PORT%
php -S 127.0.0.1:%PORT% router.php
pause
