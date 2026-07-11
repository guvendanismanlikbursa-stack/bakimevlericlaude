<?php

// Canli destek sohbetinde misafirin yazdigi mesajdaki anahtar kelimelere gore
// hangi bolume (yasli-bakim/cocuk/rehabilitasyon) ilgi duydugunu tahmin eder.
// Sadece bir ONERI karti gostermek icin kullanilir (bkz. detect_chat_section()
// app/helpers.php) - hicbir zaman otomatik yonlendirme yapmaz, kullaniciyi
// zorlamaz. Bolum etiketleri config/site_content.php ile tutarli.
return [
    'yasli-bakim' => [
        'label' => 'Yaşlı Bakım',
        'keywords' => ['yaşlı', 'yasli', 'huzurevi', 'anne', 'baba', 'anneannem', 'dedem', 'büyükanne', 'buyukanne', 'yatalak', 'bakım evi', 'bakim evi'],
    ],
    'cocuk' => [
        'label' => 'Çocuk Bakım ve Eğitim',
        'keywords' => ['çocuk', 'cocuk', 'kreş', 'kres', 'anaokulu', 'bebeğim', 'bebegim', 'oğlum', 'oglum', 'kızım', 'kizim', 'gündüz bakım', 'gunduz bakim'],
    ],
    'rehabilitasyon' => [
        'label' => 'Rehabilitasyon',
        'keywords' => ['rehabilitasyon', 'fizik tedavi', 'otizm', 'engelli', 'terapi', 'felç', 'felc', 'inme', 'nörolojik', 'norolojik'],
    ],
];
