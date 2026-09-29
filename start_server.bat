@echo off
chcp 65001 > nul
title FixNear Quick Launcher
cd /d "%~dp0fixnear"
call start_server.bat
