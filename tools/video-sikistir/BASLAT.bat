@echo off
chcp 65001 >nul 2>&1
setlocal enabledelayedexpansion
title Video Sikistirici - Kurum Tanitim Videosu
color 0A

echo.
echo  ================================================
echo    Video Sikistirici
echo    Kurum tanitim videonuzu kucultur
echo  ================================================
echo.

:: ffmpeg kontrol
where ffmpeg >nul 2>&1
if %errorlevel% neq 0 (
    echo  HATA: ffmpeg bulunamadi!
    echo.
    echo  ffmpeg indirme sayfasi aciliyor, indirip kurduktan sonra
    echo  bu programi tekrar calistirin.
    echo.
    start https://www.gyan.dev/ffmpeg/builds/
    pause
    exit /b 1
)

echo  ffmpeg bulundu.
cd /d "%~dp0"

if not exist "girdi" mkdir girdi
if not exist "cikti" mkdir cikti

set FOUND=0
for %%F in (girdi\*.mp4 girdi\*.mov girdi\*.avi girdi\*.mkv girdi\*.webm girdi\*.m4v) do (
    set FOUND=1
)

if "%FOUND%"=="0" (
    echo.
    echo  "girdi" klasorunde hic video bulunamadi.
    echo.
    echo  Once kucultmek istediginiz video dosyasini "girdi" klasorune
    echo  kopyalayin ^(surukleyip birakabilirsiniz^), sonra bu programi
    echo  tekrar calistirin.
    echo.
    explorer girdi
    pause
    exit /b 0
)

echo.
echo  Videolar sikistiriliyor, bu birkac dakika surebilir...
echo  ^(60 saniyeden uzun videolar otomatik olarak ilk 60 saniyeye kirpilir -
echo  sitenin kendi kurali da bu.^)
echo.

for %%F in (girdi\*.mp4 girdi\*.mov girdi\*.avi girdi\*.mkv girdi\*.webm girdi\*.m4v) do (
    echo  Isleniyor: %%~nxF
    ffmpeg -y -i "%%F" -t 60 -vf "scale=720:-2:force_original_aspect_ratio=decrease" -c:v libx264 -preset medium -crf 30 -c:a aac -b:a 96k -ac 2 -movflags +faststart "cikti\%%~nF.mp4" -loglevel error

    if exist "cikti\%%~nF.mp4" (
        for %%A in ("cikti\%%~nF.mp4") do (
            set /a BOYUT_MB=%%~zA/1048576
            echo   Tamamlandi: cikti\%%~nF.mp4 ^(yaklasik !BOYUT_MB! MB^)
        )
    ) else (
        echo   HATA: %%~nxF islenemedi - dosya bozuk veya desteklenmeyen bir format olabilir.
    )
    echo.
)

echo  ================================================
echo    Tum videolar tamamlandi!
echo    Kucultulmus dosyalar "cikti" klasorunde.
echo    Bu dosyalari admin panelinden normal sekilde,
echo    "Tanitim Videosu" alanindan yukleyebilirsiniz.
echo  ================================================
echo.
explorer cikti
pause
