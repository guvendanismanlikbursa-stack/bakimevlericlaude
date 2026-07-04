#!/bin/bash
clear
echo ""
echo "  ================================================"
echo "    Google Maps Isyeri Veri Cekici"
echo "    Baslatiliyor..."
echo "  ================================================"
echo ""

# Python3 kontrol
if ! command -v python3 &>/dev/null; then
    echo "  HATA: Python3 bulunamadi!"
    echo "  https://www.python.org/downloads/"
    read -p "  Cikmak icin Enter..." 
    exit 1
fi

echo "  Python: $(python3 --version)"
echo ""

# Dizine gec
cd "$(dirname "$0")"

# Kutuphaneleri kontrol et
echo "  Kutuphaneler kontrol ediliyor..."

check_lib() {
    python3 -c "import $1" 2>/dev/null
    return $?
}

NEED_INSTALL=0
check_lib playwright  || NEED_INSTALL=1
check_lib openpyxl    || NEED_INSTALL=1
check_lib requests    || NEED_INSTALL=1
check_lib bs4         || NEED_INSTALL=1

if [ $NEED_INSTALL -eq 1 ]; then
    echo "  Eksik kutuphaneler yukleniyor..."
    pip3 install playwright openpyxl requests beautifulsoup4 --quiet
    if [ $? -ne 0 ]; then
        pip3 install playwright openpyxl requests beautifulsoup4 --quiet --break-system-packages
    fi
    echo "  Chromium indiriliyor (ilk kurulum ~150MB)..."
    python3 -m playwright install chromium
fi

echo ""
echo "  Tum kutuphaneler hazir!"
echo "  Program aciliyor..."
echo ""

python3 google_maps_scraper.py

if [ $? -ne 0 ]; then
    echo ""
    echo "  Program bir hatayla kapandi."
    read -p "  Cikmak icin Enter..."
fi
