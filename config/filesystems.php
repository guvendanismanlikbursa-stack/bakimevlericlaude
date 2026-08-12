<?php

return [
    'default' => env('FILESYSTEM_DISK', 'local'),
    'disks' => [
        // 3 Agustos 2026: 'throw' => false, gercek bir disk yazma hatasini
        // (izin, gecici I/O sorunu vb.) TAMAMEN SESSIZCE yutuyordu - bir
        // sahiplenme basvurusunda tam olarak bu oldu: dosya yolu DB'ye
        // yazildi ("basarili" gibi gorundu) ama dosyanin kendisi diske hic
        // yazilmadi, hicbir hata/log/Sentry kaydi olusmadi, admin panelinde
        // kirik gorsel olarak ortaya cikti. throw=>true ile artik boyle bir
        // yazma hatasi gercek bir exception firlatir (Sentry'ye dusup log'a
        // yazilir) - ayrica cagiran kontrolculerde (FacilityClaimController,
        // Facility\WalletController, Facility\SubscriptionController) dosyanin
        // GERCEKTEN diske yazildigi ayrica dogrulanip, basarisizsa kullaniciya
        // acik bir hata gosterilip DB kaydi hic olusturulmuyor.
        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => true,
            'throw' => true,
        ],
        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
            'throw' => true,
        ],
    ],
    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],
];
