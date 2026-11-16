<?php

return [
    'mail' => [
        'driver' => env('MAIL_MAILER', 'log'),
    ],

    'vapid' => [
        'public_key' => env('VAPID_PUBLIC_KEY'),
        'private_key' => env('VAPID_PRIVATE_KEY'),
        'subject' => env('VAPID_SUBJECT'),
    ],

    // Canli sohbet widget'inda "Google ile devam et" (isim otomatik doldurma)
    // icin - bkz. GoogleChatAuthController. redirect, callback route'una
    // sabit deger olarak isaret eder; gercek yonlendirme markaya gore
    // /destek/google-callback altinda ele alinir (bkz. routes/web.php).
    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        // Socialite'in Manager'i bu anahtarin varligini zorunlu tutuyor;
        // gercek deger her cagride ->redirectUrl() ile markaya gore
        // ezildigi icin burasi sadece bir yer tutucu.
        'redirect' => '/destek/google-callback',
    ],

    // Analitik (ikisi de ucretsiz, ikisi de opsiyonel) - ID tanimlanmadigi
    // surece layouts/brand.blade.php hicbir script render etmez.
    'google_analytics' => [
        'id' => env('GA4_MEASUREMENT_ID'),
        // 15 Kasım 2026: admin paneline Analytics verisi çekmek için (mülk kimliği + hizmet hesabı dosyası).
        'property_id' => env('GA4_PROPERTY_ID'),
        // 16 Kasım 2026: Analytics ayarı "Europe/Istanbul" görünse de saatlik veri fiilen ABD Pasifik saatiyle geliyor.
        // Kanıt: 15 Kasım'da Google'dan gelen ziyaretlerin sunucu kaydındaki (+0300) saat dağılımı, GA dateHour dağılımının
        // 11 saat ilerisi (akşam 17-23 yoğunluğu GA'da 06-12'de). Saatlik veriler bu dilimden Türkiye saatine çevrilir.
        'timezone' => env('GA4_TIMEZONE', 'America/Los_Angeles'),
        'credentials' => env('GOOGLE_SERVICE_ACCOUNT_JSON'),
        'ca_bundle' => env('GOOGLE_CA_BUNDLE'),
        'ids' => [
            'bakimevibul' => env('GA4_MEASUREMENT_ID_BAKIMEVIBUL'),
            'bakimeviara' => env('GA4_MEASUREMENT_ID_BAKIMEVIARA'),
            'bakimevleri' => env('GA4_MEASUREMENT_ID_BAKIMEVLERI'),
        ],
    ],
    'google_search_console' => [
        'verification' => [
            'bakimevibul' => env('GOOGLE_SEARCH_CONSOLE_BAKIMEVIBUL'),
            'bakimeviara' => env('GOOGLE_SEARCH_CONSOLE_BAKIMEVIARA'),
            'bakimevleri' => env('GOOGLE_SEARCH_CONSOLE_BAKIMEVLERI'),
        ],
    ],
    'meta_pixel' => [
        'id' => env('META_PIXEL_ID'),
    ],
];

