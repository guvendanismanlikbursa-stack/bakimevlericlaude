<?php

/*
 * 9 Kasım 2026 (kullanıcı onayı): fiyat sayfalarında yalnızca RESMİ ve kaynağı belli ücret bilgisi.
 * Uydurma/tahmini il fiyatı yazılmaz. Yeni yılın tabloları yayımlandıkça (her yıl Ocak) güncellenir.
 *
 * - yasli_bakim: il bazlı, Valilik ücret tespit komisyonunun belirlediği özel huzurevi ve yaşlı bakım
 *   merkezi aylık taban–tavan ücretleri (KDV hariç). Yalnız tablosunu kamuya açık yayımlayan iller.
 * - meb_ozel_egitim: MEB'in raporlu bireyler için özel eğitim ve rehabilitasyon merkezlerine ödediği
 *   aylık destek tutarı (ülke geneli, KDV hariç).
 */
return [
    'yil' => 2026,

    'yasli_bakim' => [
        'istanbul' => [
            'taban' => 20250.00,
            'tavan' => 106616.25,
            'kaynak' => 'İstanbul Aile ve Sosyal Hizmetler İl Müdürlüğü',
            'url' => 'https://www.aile.gov.tr/istanbul/duyurular/2026-yili-ozel-huzurevleri-ile-huzurevi-ve-yasli-bakim-merkezleri-ucretleri/',
        ],
        'ankara' => [
            'taban' => 14260.84,
            'tavan' => 86280.19,
            'kaynak' => 'Ankara Aile ve Sosyal Hizmetler İl Müdürlüğü',
            'url' => 'https://www.aile.gov.tr/ankara/duyurular/ozel-huzurevleri-ve-yasli-bakim-merkezlerinde-2026-yili-ucretleri/',
        ],
        'manisa' => [
            'taban' => 9846.13,
            'tavan' => 74104.50,
            'kaynak' => 'Manisa Aile ve Sosyal Hizmetler İl Müdürlüğü',
            'url' => 'https://www.aile.gov.tr/manisa/duyurular/2026-yili-ozel-huzurevleri-ve-yasli-bakim-merkezleri-ve-ozel-bakim-merkezleri-ucret-tablosu/',
        ],
    ],

    'meb_ozel_egitim' => [
        'bireysel' => 7748.00,
        'bireysel_saat' => 8,
        'grup' => 2168.00,
        'grup_saat' => 4,
        'kaynak' => 'Resmî Gazete, 27 Ocak 2026 tarihli ve 33150 sayılı tebliğ',
    ],
];
