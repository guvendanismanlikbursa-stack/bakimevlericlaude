<?php

return [
    // Production'da kritik (beklenmeyen) hatalar olustugunda otomatik e-posta
    // uyarisi gonderilecek adres. Bkz. bootstrap/app.php withExceptions + notify_admin_of_exception().
    'admin_alert_email' => env('ADMIN_ALERT_EMAIL', 'guvendanismanlikbursa@gmail.com'),

    // Yeni sahiplenilen kuruma tanınan ücretsiz teklif hakkı sayısı
    'free_claim_credits' => 5,

    // Çcretsiz hak bitince, her teklif gÇnderiminde bakiyeden dÇÇÇlecek tutar (TL).
    // Admin panel > Ayarlar'dan genel olarak, ya da kurum duzenleme sayfasindan tek
    // bir kurum icin ozel olarak (quote_price_override) degistirilebilir.
    'default_quote_price' => 250,

    // Admin panelde duzenlenebilir varsayilan banka hesap bilgisi (settings tablosu
    // bos ise kullanilir). Her marka/site kendi banka hesabini gosterebilsin diye
    // marka slug'ina gore ayrı ayrı tutulur.
    'default_bank_info' => [
        'bakimevibul' => [
            'bank_name' => 'Örnek Banka',
            'account_holder' => 'Bakım Platformu A.Ş.',
            'iban' => 'TR00 0000 0000 0000 0000 0000 00',
        ],
        'bakimeviara' => [
            'bank_name' => 'Örnek Banka',
            'account_holder' => 'Bakım Platformu A.Ş.',
            'iban' => 'TR00 0000 0000 0000 0000 0000 00',
        ],
        'bakimevleri' => [
            'bank_name' => 'Örnek Banka',
            'account_holder' => 'Bakım Platformu A.Ş.',
            'iban' => 'TR00 0000 0000 0000 0000 0000 00',
        ],
    ],

    // Yuzen WhatsApp butonu icin varsayilan numara/mesaj (settings tablosu bos ise
    // kullanilir). Admin panel > Ayarlar'dan kod degistirmeden guncellenebilir.
    'default_whatsapp' => [
        'number' => env('WHATSAPP_NUMBER', '908503087991'),
        'message' => 'Merhaba, {marka} üzerinden bakım kurumları hakkında bilgi almak istiyorum.',
    ],

    // Veri çekici ile oluşturulan ön kayıtlı kurumlara atanacak demo görsel havuzu.
    // Varsayilan yol makineden makineye tasinabilir olsun diye proje icindeki
    // storage/app dizinine goreli; belirli bir gelistiricinin kisisel klasorune
    // (ozel kullanici profili) bagimli degil. Baska bir makinede/veri cekici
    // akisinda calismasi icin .env > IMPORT_IMAGE_POOL_PATH ile override edilebilir.
    'import_image_pool_path' => env('IMPORT_IMAGE_POOL_PATH', storage_path('app/import-image-pool')),
    'import_image_count' => (int) env('IMPORT_IMAGE_COUNT', 5),

    // Ücretlendirme segmenti (Ekonomik / Standart / Premium / Ultra Premium)
    // price_min bu esiklerden hangi araliga dusuyorsa o segment atanir.
    // Admin panel > Ayarlar'dan degistirilebilir (bkz. Setting price_tier_*).
    'default_price_tiers' => [
        'standart_min' => 15000,
        'premium_min' => 30000,
        'ultra_min' => 50000,
    ],

    // Public form'lar icin dakika basina istek siniri (IP bazli).
    // Trafiginize gore bu degerleri deploy gerektirmeden degistirebilirsiniz;
    // AppServiceProvider bunlari RateLimiter::for() ile otomatik kaydeder,
    // routes/web.php icinde throttle:{isim} olarak kullanilir.
    'throttle' => [
        // Ucretsiz/hafif aksiyonlar: favori sayaci, yakinimdaki kurumlar
        'public-light' => (int) env('THROTTLE_PUBLIC_LIGHT', 60),
        // Orta hacimli formlar: teklif talebi, iletisim, yorum, ziyaret, kontenjan
        'public-form' => (int) env('THROTTLE_PUBLIC_FORM', 20),
        // Dusuk hacim beklenen, istismara daha hassas formlar: soru sor, sahiplenme basvurusu
        'public-sensitive' => (int) env('THROTTLE_PUBLIC_SENSITIVE', 10),
        // Kimlik dogrulama: giris/kayit denemeleri
        'auth-attempt' => (int) env('THROTTLE_AUTH_ATTEMPT', 5),
        'auth-register' => (int) env('THROTTLE_AUTH_REGISTER', 10),
    ],
];
