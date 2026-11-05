<?php

// 5 Kasım 2026: rehber yazılarının konuları, site site. Her site kendi bölümünün yazılarını taşır;
// aynı yazı iki sitede yayınlanmaz (kopya içerik).
return [
    // 5 Kasım 2026: kurum ve şehir rehberi sayfalarında gösterilecek ilgili yazılar.
    // Kurum türü (brand_scope) => [yazıların bulunduğu site, sırayla kullanılacak konular].
    'kurum_eslesme' => [
        'yasli-bakim' => ['bakimevibul', ['huzurevi-ve-bakimevi', 'haklar-ve-destekler', 'aile-ve-bakim-veren']],
        'cocuk-bakim' => ['bakimevleri', ['kres-ve-anaokulu-secimi', 'uyum-ve-gelisim', 'kayit-ve-ucretler']],
        'ozel-egitim' => ['bakimeviara', ['ozel-egitim', 'merkez-secimi', 'aile-destegi']],
        'rehabilitasyon' => ['bakimeviara', ['rehabilitasyon-sureci', 'merkez-secimi', 'raporlar-ve-haklar']],
        'fizik-tedavi' => ['bakimeviara', ['rehabilitasyon-sureci', 'merkez-secimi', 'raporlar-ve-haklar']],
    ],

    'kategoriler' => [
        'bakimevibul' => [
            'huzurevi-ve-bakimevi' => 'Huzurevi ve Bakımevi',
            'evde-bakim' => 'Evde Bakım',
            'alzheimer-ve-demans' => 'Alzheimer ve Demans',
            'haklar-ve-destekler' => 'Haklar ve Destekler',
            'gunluk-yasam' => 'Günlük Yaşam ve Güvenlik',
            'aile-ve-bakim-veren' => 'Aile ve Bakım Veren',
        ],
        'bakimevleri' => [
            'kres-ve-anaokulu-secimi' => 'Kreş ve Anaokulu Seçimi',
            'uyum-ve-gelisim' => 'Uyum ve Gelişim',
            'beslenme-ve-gunluk-bakim' => 'Beslenme ve Günlük Bakım',
            'oyun-ve-etkinlik' => 'Oyun ve Etkinlik',
            'ebeveynlik' => 'Ebeveynlik',
            'kayit-ve-ucretler' => 'Kayıt ve Ücretler',
        ],
        'bakimeviara' => [
            'rehabilitasyon-sureci' => 'Rehabilitasyon Süreci',
            'ozel-egitim' => 'Özel Eğitim',
            'raporlar-ve-haklar' => 'Raporlar ve Haklar',
            'merkez-secimi' => 'Merkez Seçimi',
            'aile-destegi' => 'Aile Desteği',
            'evde-gunluk-yasam' => 'Evde Günlük Yaşam',
        ],
    ],
];
