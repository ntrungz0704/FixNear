@echo off
chcp 65001 > nul
cd /d "%~dp0"
title FixNear Server

echo ========================================================
echo       FIXNEAR - NEN TANG TRA CUU SUA CHUA MIEN NAM
echo ========================================================
echo.

:: Tim duong dan PHP
set PHP_BIN=php
where php >nul 2>nul
if %errorlevel% neq 0 (
    if exist "D:\laragon\bin\php\php-8.3.26-Win32-vs16-x64\php.exe" (
        set PHP_BIN="D:\laragon\bin\php\php-8.3.26-Win32-vs16-x64\php.exe"
    ) else (
        echo [LOI] Khong tim thay PHP tren may tinh!
        echo Vui long kiem tra lai Laragon hoac cai dat PHP.
        pause
        exit /b 1
    )
)

set PORT=8000
echo May chu FixNear dang chay tai: http://localhost:%PORT%
echo Cong quan tri Admin:            http://localhost:%PORT%/admin/
echo.
echo [!] Nhan Ctrl + C de dung may chu.
echo ========================================================
echo.

start http://localhost:%PORT%
%PHP_BIN% -S 127.0.0.1:%PORT% router.php

pause
