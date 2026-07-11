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
    ],
];
