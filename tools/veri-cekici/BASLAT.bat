@echo off
chcp 65001 >nul 2>&1
title Google Maps Veri Cekici
color 0A

echo.
echo  ================================================
echo    Google Maps Isyeri Veri Cekici
echo    Baslatiliyor...
echo  ================================================
echo.

:: Python kontrol
python --version >nul 2>&1
if %errorlevel% neq 0 (
    echo  HATA: Python bulunamadi!
    echo.
    echo  Python indirme sayfasi aciliyor...
    echo  https://www.python.org/downloads/
    echo.
    echo  Kurulumda "Add Python to PATH" secenegini isaretleyin!
    echo.
    start https://www.python.org/downloads/
    pause
    exit /b 1
)

echo  Python bulundu.

:: Klasore git
cd /d "%~dp0"

:: check_deps.py olustur ve calistir
echo import sys > check_deps.py
echo libs = ["playwright","openpyxl","requests","bs4"] >> check_deps.py
echo missing = [] >> check_deps.py
echo for lib in libs: >> check_deps.py
echo     try: __import__(lib) >> check_deps.py
echo     except ImportError: missing.append(lib) >> check_deps.py
echo if missing: print(",".join(missing)) >> check_deps.py
echo else: print("OK") >> check_deps.py

for /f "tokens=*" %%i in ('python check_deps.py') do set DEPS=%%i

if "%DEPS%"=="OK" (
    echo  Tum kutuphaneler hazir.
    goto RUN
)

echo  Eksik kutuphaneler: %DEPS%
echo  Yukleniyor...
echo.

python -m pip install playwright openpyxl requests beautifulsoup4 --quiet --no-warn-script-location
if %errorlevel% neq 0 (
    echo  Kutaphane yuklenemedi! Internet baglantinizi kontrol edin.
    del check_deps.py >nul 2>&1
    pause
    exit /b 1
)

:: Chromium kontrol
echo  Chromium tarayici kontrol ediliyor...
python -m playwright install chromium
if %errorlevel% neq 0 (
    echo  Chromium yuklenemedi!
    del check_deps.py >nul 2>&1
    pause
    exit /b 1
)

echo.
echo  Tum kutuphaneler yuklendi!

:RUN
del check_deps.py >nul 2>&1
echo.
echo  Program aciliyor...
echo.
python google_maps_scraper.py
if %errorlevel% neq 0 (
    echo.
    echo  Program bir hatayla kapandi. Hata kodu: %errorlevel%
    pause
)
