<?php

return [
    'name' => env('APP_NAME', 'BakimPlatform'),
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
