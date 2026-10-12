@echo off
chcp 65001 > nul
cd /d "%~dp0"
title FixNear Server

echo ========================================================
echo       FIXNEAR - NỀN TẢNG TRA CỨU SỬA CHỮA MIỀN NAM
echo ========================================================
echo.

:: Tìm đường dẫn PHP
set PHP_BIN=php
where php >nul 2>&1
if errorlevel 1 (
    if exist "%LOCALAPPDATA%\Microsoft\WinGet\Packages\PHP.PHP.8.2_Microsoft.Winget.Source_8wekyb3d8bbwe\php.exe" (
        set PHP_BIN="%LOCALAPPDATA%\Microsoft\WinGet\Packages\PHP.PHP.8.2_Microsoft.Winget.Source_8wekyb3d8bbwe\php.exe"
    ) else if exist "D:\laragon\bin\php\php-8.3.26-Win32-vs16-x64\php.exe" (
        set PHP_BIN="D:\laragon\bin\php\php-8.3.26-Win32-vs16-x64\php.exe"
    ) else (
        echo [LỖI] Không tìm thấy PHP trên máy tính!
        echo Vui lòng kiểm tra lại Laragon hoặc cài đặt PHP.
        pause
        exit /b 1
    )
)

echo Đang kiểm tra cổng kết nối...
set PORT=8000
netstat -ano | findstr /R /C:":8000 " >nul 2>&1
if not errorlevel 1 (
    echo [LƯU Ý] Cổng 8000 đang bận bởi ứng dụng khác.
    echo Tự động khởi chạy trên cổng 8080 để tránh xung đột!
    set PORT=8080
)

echo ========================================================
echo Máy chủ FixNear đang chạy tại: http://localhost:%PORT%
echo Cổng quản trị Admin:            http://localhost:%PORT%/admin/
echo.
echo [!] Nhấn Ctrl + C để dừng máy chủ.
echo ========================================================
echo.

timeout /t 1 > nul
start http://localhost:%PORT%
%PHP_BIN% -S 127.0.0.1:%PORT% router.php

pause
