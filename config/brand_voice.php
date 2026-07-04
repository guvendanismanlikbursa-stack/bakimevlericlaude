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
    ],
    'bakimeviara' => [
        'facility_intro' => 'Bu profili, :location bölgesinde arayış içindeki aileler için hazırladığımız karar kriterleriyle birlikte inceleyin; görüşme öncesi soracağınız soruları netleştirin.',
        'meta_suffix' => 'Aileler için hazırlanmış karar kriterleriyle :location bölgesinde değerlendirin.',
    ],
    'bakimevleri' => [
        'facility_intro' => ':category kararınızı bakimevleri.com’un kapsamlı bilgi merkezi kriterleriyle, doğrulanmış bilgilerle destekleyin.',
        'meta_suffix' => 'Kapsamlı bilgi merkezi kriterleriyle :location bölgesinde değerlendirin.',
    ],
];
