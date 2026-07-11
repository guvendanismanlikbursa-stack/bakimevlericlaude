<?php

// 3 site ayni kurum envanterini paylasiyor (bilinclitercih: maksimum talep
// toplama - Google'da arayan kisi 3 siteyi de gorup istedigi/uygun oldugu
// yerden fiyat sorabilsin). Ayni kurum sayfasinin 3 farkli domainde birebir
// ayni icerik olarak indexlenmesini (duplicate content) onlemek icin, her
// markanin kendi editoryal "sesi" kurum sayfasina benzersiz bir cerceve
// (intro cumlesi + meta description eki) ekler. Ham kurum verisi (isim,
// adres, fiyat, gorseller) ayni kalir; etrafindaki editoryal metin farklidir.
return [
    'bakimevibul' => [
        'facility_intro' => 'bakimevibul.com üzerinden :category arayışınızı hızlıca karşılaştırın; :location bölgesindeki diğer alternatiflerle birlikte değerlendirip en uygun teklifi seçin.',
        'meta_suffix' => ':location bölgesinde hızlı karşılaştırma ve ücretsiz teklif alma imkanı.',
        // Rehber/fiyat sayfaları icin: coğrafya+kategori bazli, birden fazla
        // sablon arasindan URL'e gore deterministik secilir (bkz. guide_page_content()
        // app/helpers.php). Hizli/pratik-karsilastirma tonu.
        'guide_intro_templates' => [
            'bakimevibul.com üzerinden :location bölgesindeki :category seçeneklerini karşılaştırın; :count kurum arasından size en uygun teklifi hızlıca bulun.',
            ':location bölgesinde :category arayışınızı bakimevibul.com ile hızlandırın — :count kayıtlı kurumu tek ekranda inceleyip aynı anda birden fazla teklif isteyebilirsiniz.',
            'bakimevibul.com\'da :location için listelenen :count :category arasından, bütçenize ve ihtiyacınıza en uygun olanı birkaç dakikada karşılaştırın.',
            ':location bölgesindeki :category kararınızı bakimevibul.com\'un karşılaştırma araçlarıyla verin; :count kurumun güncel bilgilerine tek yerden ulaşın.',
        ],
    ],
    'bakimeviara' => [
        'facility_intro' => 'Bu profili, :location bölgesinde arayış içindeki aileler için hazırladığımız karar kriterleriyle birlikte inceleyin; görüşme öncesi soracağınız soruları netleştirin.',
        'meta_suffix' => 'Aileler için hazırlanmış karar kriterleriyle :location bölgesinde değerlendirin.',
        'guide_intro_templates' => [
            ':location bölgesinde :category arayan aileler için hazırladığımız karar kriterleriyle, buradaki :count kurumu birlikte değerlendirin.',
            'bakimeviara.com, :location için :count :category seçeneğini aileler açısından önemli kriterlere göre bir araya getirdi; görüşme öncesi soracağınız soruları netleştirin.',
            ':location bölgesindeki :category kararınızı verirken yalnız değilsiniz — bakimeviara.com\'daki :count kurum profilini, aile deneyimlerine göre hazırlanan kriterlerle inceleyebilirsiniz.',
            'Sevdikleriniz için :location bölgesinde doğru :category\'yi seçmek önemli bir karar; bakimeviara.com\'daki :count kurumu bu gözle karşılaştırın.',
        ],
    ],
    'bakimevleri' => [
        'facility_intro' => ':category kararınızı bakimevleri.com’un kapsamlı bilgi merkezi kriterleriyle, doğrulanmış bilgilerle destekleyin.',
        'meta_suffix' => 'Kapsamlı bilgi merkezi kriterleriyle :location bölgesinde değerlendirin.',
        'guide_intro_templates' => [
            ':location bölgesindeki :category kararınızı bakimevleri.com\'un kapsamlı bilgi merkezinde yer alan :count kurumun doğrulanmış bilgileriyle destekleyin.',
            'bakimevleri.com, :location için :count :category hakkında detaylı ve güncel bilgiyi tek bir kaynakta topladı; kararınızı bilgiye dayalı verin.',
            ':location bölgesindeki :count :category seçeneğini bakimevleri.com\'un kapsamlı kriterleriyle inceleyip en doğru kararı verin.',
            'bakimevleri.com\'daki :location bölgesi :category rehberi, :count kurumun bilgilerini bir araya getirerek karar sürecinizi kolaylaştırır.',
        ],
    ],
];
