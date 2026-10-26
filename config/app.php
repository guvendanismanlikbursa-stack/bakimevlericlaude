<?php

return [
    'name' => env('APP_NAME', 'BakimPlatform'),

    // 26 Ekim 2026: yedek (bekleme) sunucusu - açıkken bütün sayfalar arama motorlarına kapalıdır
    // (X-Robots-Tag noindex + robots.txt Disallow). Canlı sunucuda kapalı; geçiş günü VDS'te kapatılır.
    'bekleme' => (bool) env('SITE_BEKLEME', false),
    'env' => env('APP_ENV', 'production'),
    'debug' => (bool) env('APP_DEBUG', false),
    'url' => env('APP_URL', 'http://localhost'),
    'timezone' => env('APP_TIMEZONE', 'Europe/Istanbul'),
    'locale' => 'tr',
    'fallback_locale' => 'en',
    'faker_locale' => 'tr_TR',
    'cipher' => 'AES-256-CBC',
    'key' => env('APP_KEY'),
    'maintenance' => ['driver' => 'file'],
];
