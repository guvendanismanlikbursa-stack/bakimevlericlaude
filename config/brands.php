<?php

$allCategoryScopes = ['yasli-bakim', 'cocuk-bakim', 'ozel-egitim', 'rehabilitasyon', 'fizik-tedavi'];

return [
    'default' => env('BRAND_DEFAULT', 'bakimevibul'),

    'service_sections' => [
        'yasli-bakim' => [
            'slug' => 'yasli-bakim',
            'title' => 'Yaşlı Bakım',
            // 24 Agustos 2026: kullanicinin bildirdigi gercek hata - il/ilce
            // rehber sayfalarinin basligi/H1/meta'si "title" alanini ("Yasli
            // Bakim") kullaniyordu, ama kimse "yasli bakim" diye aramiyor -
            // gercek arama terimi "bakimevi"/"bakimevleri" (bkz. rehber
            // sayfasi title uretimi, location-guide.blade.php). Bu alan SEO
            // baslik/H1/meta uretiminde 'title' yerine kullanilir.
            'seo_title' => 'Bakımevi',
            'short_title' => 'Yaşlı',
            'icon' => 'elderly-care',
            'scopes' => ['yasli-bakim'],
            'hero_title' => 'Bakımevi, huzurevi ve yaşlı bakım seçeneklerini güvenle karşılaştırın',
            'hero_subtitle' => 'Bakımevi, huzurevi, Alzheimer bakımı ve yaşlı yaşam merkezlerini tek ekranda inceleyin; aile ihtiyacınıza uygun kurumlardan teklif alın.',
            'search_label' => 'Bakımevi, huzurevi veya yaşlı bakım merkezi ara',
            'hero_image' => '/images/hero-yasli-bakim.webp',
            // 24 Agustos 2026: kullanicinin PageSpeed Insights ile bildirdigi
            // gercek hata - anasayfadaki kucuk "bolum karti" (bkz. home.blade.php
            // section-card-beam) bu buyuk (tam genislik banner icin olan)
            // gorseli kucuk bir kutuda gosterip binlerce KB israf ediyordu.
            // Bu, sadece o kucuk kart icin onceden kucultulmus ayri bir dosya.
            'hero_image_card' => '/images/hero-yasli-bakim-card.webp',
            'theme' => ['primary' => '#1e6f5c', 'secondary' => '#f4a259', 'soft' => '#ecfdf5'],
            'features' => ['7/24 hemşire', 'Doktor kontrolü', 'Alzheimer bakımı', 'Demans bakımı', 'Palyatif bakım', 'Gündüz bakım', 'Tam zamanlı bakım', 'Fizik tedavi', 'Fiziksel aktivite', 'Diyetisyen', 'Sosyal etkinlik', 'Özel oda', 'Bahçe alanı'],
            'profile_fields' => ['Kapasite', 'Oda tipleri', 'Hemşire/doktor desteği', 'Demans/Alzheimer bakımı', 'Ziyaret saatleri', 'Beslenme planı'],
            'advisor_concerns' => [
                ['key' => 'has_dementia', 'label' => 'Demans/Alzheimer var', 'keywords' => ['demans' => 'Demans/Alzheimer bakımı', 'alzheimer' => 'Demans/Alzheimer bakımı']],
                ['key' => 'is_bedridden', 'label' => 'Yatalak', 'keywords' => ['yatalak' => 'Yatalak hasta bakımı', '7/24' => '7/24 hemşire desteği', 'hemşire' => '7/24 hemşire desteği']],
                ['key' => 'needs_physio', 'label' => 'Fizik tedavi gerekiyor', 'keywords' => ['fizik tedavi' => 'Fizik tedavi', 'fizyoterap' => 'Fizik tedavi']],
            ],
        ],
        'cocuk' => [
            'slug' => 'cocuk',
            'title' => 'Çocuk',
            // bkz. yasli-bakim bolumundeki 24 Agustos 2026 yorumu - ayni sebep.
            'seo_title' => 'Kreş',
            'short_title' => 'Çocuk',
            'icon' => 'child-care',
            'scopes' => ['cocuk-bakim', 'ozel-egitim'],
            'hero_title' => 'Kreş, anaokulu, gündüz bakımevi ve özel eğitim kurumlarını bulun',
            'hero_subtitle' => 'Kreş, gündüz bakım evi, anaokulu ve özel eğitim merkezlerini yaş grubu, şehir ve hizmetlere göre karşılaştırın.',
            'search_label' => 'Kreş, anaokulu veya özel eğitim merkezi ara',
            'hero_image' => '/images/hero-cocuk.webp',
            // bkz. yasli-bakim bolumundeki 24 Agustos 2026 yorumu - ayni sebep.
            'hero_image_card' => '/images/hero-cocuk-card.webp',
            'theme' => ['primary' => '#5b3a8e', 'secondary' => '#ffd166', 'soft' => '#faf5ff'],
            'features' => ['Yaş grubu', 'Oyun alanı', 'Rehberlik servisi', 'Servis imkanı', 'Yemek programı', 'Uyku odası', 'Özel eğitim', 'Dil/atölye programı'],
            'profile_fields' => ['Yaş aralığı', 'Sınıf mevcudu', 'Eğitim programı', 'Rehberlik/psikolog', 'Servis güzergahı', 'Yemek/uyku düzeni'],
            'advisor_concerns' => [
                ['key' => 'needs_special_education', 'label' => 'Özel eğitim ihtiyacı var', 'keywords' => ['özel eğitim' => 'Özel eğitim', 'otizm' => 'Otizm/gelişimsel destek', 'gelişim' => 'Gelişimsel destek']],
                ['key' => 'needs_daycare', 'label' => 'Kreş/gündüz bakım gerekiyor', 'keywords' => ['kreş' => 'Kreş hizmeti', 'gündüz bakım' => 'Gündüz bakım']],
                ['key' => 'needs_guidance', 'label' => 'Rehberlik/psikolog desteği gerekiyor', 'keywords' => ['rehberlik' => 'Rehberlik servisi', 'psikolog' => 'Psikolog desteği']],
            ],
        ],
        'rehabilitasyon' => [
            'slug' => 'rehabilitasyon',
            'title' => 'Rehabilitasyon',
            // bkz. yasli-bakim bolumundeki 24 Agustos 2026 yorumu - bu bolumde
            // "title" zaten aranan terimle ayni oldugu icin degisiklik yok.
            'seo_title' => 'Rehabilitasyon Merkezi',
            'short_title' => 'Rehab',
            'icon' => 'rehab-care',
            'scopes' => ['rehabilitasyon', 'fizik-tedavi'],
            'hero_title' => 'Rehabilitasyon ve fizik tedavi merkezlerini karşılaştırın',
            'hero_subtitle' => 'Fizik tedavi, nörolojik rehabilitasyon, özel terapi ve bakım desteklerini şehir/kategori bazında bulun.',
            'search_label' => 'Rehabilitasyon veya fizik tedavi merkezi ara',
            'hero_image' => '/images/hero-rehabilitasyon.webp',
            // bkz. yasli-bakim bolumundeki 24 Agustos 2026 yorumu - ayni sebep.
            'hero_image_card' => '/images/hero-rehabilitasyon-card.webp',
            'theme' => ['primary' => '#0b5d8c', 'secondary' => '#e63946', 'soft' => '#eaf4fb'],
            'features' => ['Fizyoterapist', 'Nörolojik rehabilitasyon', 'Ortopedik rehabilitasyon', 'Hidroterapi', 'Ergoterapi', 'Konuşma terapisi', 'Evde takip', 'Cihaz desteği'],
            'profile_fields' => ['Terapi branşları', 'Uzman kadro', 'Seans süresi', 'Cihaz/ekipman', 'Raporlama', 'Evde hizmet'],
            'advisor_concerns' => [
                ['key' => 'needs_physio', 'label' => 'Fizik tedavi gerekiyor', 'keywords' => ['fizik tedavi' => 'Fizik tedavi', 'fizyoterap' => 'Fizik tedavi']],
                ['key' => 'needs_neuro_rehab', 'label' => 'Nörolojik rehabilitasyon gerekiyor', 'keywords' => ['nörolojik' => 'Nörolojik rehabilitasyon', 'inme' => 'İnme sonrası rehabilitasyon', 'felç' => 'Felç sonrası rehabilitasyon']],
                ['key' => 'needs_speech_therapy', 'label' => 'Konuşma/dil terapisi gerekiyor', 'keywords' => ['konuşma terapisi' => 'Konuşma terapisi', 'dil terapisi' => 'Dil terapisi']],
            ],
        ],
    ],

    'brands' => [
        // 12 Agustos 2026: kullanicinin talebi - 3 marka artik BIRBIRIYLE
        // CAKISMAYAN 3 ayri varsayilan bolume sahip (yasli-bakim/cocuk/
        // rehabilitasyon), her biri kendi anahtar kelime kumesinde "bayrak
        // site" oluyor - toplamda tum SEO yuzeyini 3 site arasinda bolusup
        // topluyoruz (bkz. bakimevleri'nin default_section'i asagida).
        'bakimevibul' => [
            'slug' => 'bakimevibul',
            'name' => 'bakimevibul.com',
            'tagline' => 'Kreş, anaokulu ve çocuk bakım kurumlarını güvenle bul',
            'theme' => 'bakimevibul',
            'domains' => ['bakimevibul.com', 'www.bakimevibul.com', 'bakimevibul.test', 'bakimevibul.local', 'localhost:8000'],
            'primary_color' => '#1e6f5c',
            'secondary_color' => '#f4a259',
            'category_scope' => $allCategoryScopes,
            'default_section' => 'cocuk',
            'logo_text' => 'bakimevibul.com',
        ],
        'bakimeviara' => [
            'slug' => 'bakimeviara',
            'name' => 'bakimeviara.com',
            'tagline' => 'Rehabilitasyon ve fizik tedavi merkezlerini karşılaştır',
            'theme' => 'bakimeviara',
            'domains' => ['bakimeviara.com', 'www.bakimeviara.com', 'bakimeviara.test', 'bakimeviara.local'],
            'primary_color' => '#5b3a8e',
            'secondary_color' => '#ffd166',
            'category_scope' => $allCategoryScopes,
            'default_section' => 'rehabilitasyon',
            'logo_text' => 'bakimeviara.com',
        ],
        'bakimevleri' => [
            'slug' => 'bakimevleri',
            'name' => 'bakimevleri.com',
            // 12 Agustos 2026: kullanicinin talebi - bu alan adi tam olarak
            // "bakimevi/bakimevleri" kelimesini tasidigi halde varsayilan
            // bolum 'rehabilitasyon' idi; anasayfa basligi/aciklamasi hic
            // "bakimevi" gecmiyordu. Bu, sitenin kendi ana anahtar kelimesiyle
            // (Google'in en cok agirlik verdigi kok sayfada) dogrudan
            // celisiyordu - "bakimevi/bakimevleri/huzurevi" aramalarinda
            // cikmama sorununun en olasi nedeniydi.
            'tagline' => 'Bakımevi, huzurevi ve yaşlı bakım merkezlerini güvenle karşılaştırın',
            'theme' => 'bakimevleri',
            'domains' => ['bakimevleri.com', 'www.bakimevleri.com', 'bakimevleri.test', 'bakimevleri.local'],
            'primary_color' => '#0b5d8c',
            'secondary_color' => '#e63946',
            'category_scope' => $allCategoryScopes,
            'default_section' => 'yasli-bakim',
            'logo_text' => 'bakimevleri.com',
        ],
    ],
];

