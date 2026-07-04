<?php

$esc = static fn ($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');

$sections = [
    'yasli-bakim' => [
        'label' => 'Yaşlı bakım',
        'title_label' => 'Yaşlı Bakım',
        'need' => 'güvenli yaşam, düzenli sağlık takibi, sosyal destek ve aileyle açık iletişim',
        'audience' => 'anne, baba veya yakınları için bakım alternatifi arayan aileler',
        'checks' => ['Hemşire ve doktor erişimi', 'Oda düzeni ve hijyen', 'Beslenme ve ilaç takibi', 'Ziyaret ve iletişim rutini', 'Sosyal etkinlik planı'],
        'questions' => [
            ['Kurum seçerken ilk neye bakmalıyım', 'Önce sağlık ihtiyacı, günlük destek seviyesi, personel yeterliliği ve aileye raporlama düzeni birlikte değerlendirilmelidir.'],
            ['Fiyat karşılaştırması tek başına yeterli mi', 'Hayır. Fiyata dahil hizmetler, ek bakım ücretleri, oda tipi ve sağlık takibi kapsamı birlikte okunmalıdır.'],
            ['Ziyaret planı neden önemli', 'Düzenli ziyaret imkanı, ailenin güven duygusunu artırır ve kurumla iletişimi daha sağlıklı tutar.'],
        ],
        'topics' => [
            'rehberi' => ['kind' => 'guide', 'name' => 'Kurum seçme rehberi', 'summary' => 'Bakım ihtiyacı, güvenlik, personel, iletişim ve toplam maliyet başlıklarını birlikte değerlendirin.'],
            'fiyat-karsilastirma' => ['kind' => 'pricing', 'name' => 'Fiyat ve hizmet karşılaştırması', 'summary' => 'Aylık ücret, ek bakım kalemleri, oda tipi ve dahil hizmetleri doğru okumak için kontrol listesi.'],
            'ziyaret-kontrol-listesi' => ['kind' => 'visit', 'name' => 'Ziyaret ve ön görüşme listesi', 'summary' => 'Kurumu gezerken oda, hijyen, yemek, sağlık takibi ve aile bilgilendirmesi için sorulacak sorular.'],
            'saglik-takibi' => ['kind' => 'care', 'name' => 'Sağlık takibi ve günlük bakım', 'summary' => 'İlaç düzeni, doktor erişimi, acil durum planı ve günlük yaşam desteği nasıl sorgulanmalı'],
            'soru-cevap' => ['kind' => 'faq', 'name' => 'Soru-cevap dosyası', 'summary' => 'Ailelerin yaşlı bakım sürecinde en sık sorduğu sorulara kısa ve uygulanabilir cevaplar.'],
        ],
    ],
    'cocuk' => [
        'label' => 'çocuk bakım ve eğitim',
        'title_label' => 'Çocuk Bakım ve Eğitim',
        'need' => 'yaş grubuna uygun eğitim, güvenli ortam, gelişim takibi ve aileyle düzenli iletişim',
        'audience' => 'kreş, anaokulu, gündüz bakım veya özel eğitim merkezi arayan aileler',
        'checks' => ['Yaş grubu uyumu', 'Sınıf mevcudu', 'Rehberlik ve gelişim takibi', 'Servis, yemek ve güvenlik düzeni', 'Aile bilgilendirme sıklığı'],
        'questions' => [
            ['Kreş ve anaokulu seçerken en kritik konu nedir', 'Çocuğun yaşına uygun program, sınıf mevcudu, öğretmen sürekliliği ve güvenlik uygulamaları birlikte incelenmelidir.'],
            ['Özel eğitim merkezinde nelere bakılır', 'Branş uzmanlığı, bireysel plan, seans takibi, aile bilgilendirmesi ve raporlama düzeni önemlidir.'],
            ['Servis ve yemek bilgisi neden filtrede olmalı', 'Ailenin günlük düzenini doğrudan etkilediği için karar öncesi netleşmesi gerekir.'],
        ],
        'topics' => [
            'rehberi' => ['kind' => 'guide', 'name' => 'Kurum seçme rehberi', 'summary' => 'Kreş, anaokulu ve çocuk bakım merkezlerini güvenlik, eğitim programı ve iletişim düzeniyle karşılaştırın.'],
            'egitim-programi' => ['kind' => 'program', 'name' => 'Eğitim programı nasıl okunur', 'summary' => 'Yaş grubu, oyun, uyku, yemek, rehberlik ve gelişim takibi başlıklarını netleştirin.'],
            'guvenlik-servis-yemek' => ['kind' => 'safety', 'name' => 'Güvenlik, servis ve yemek kontrolü', 'summary' => 'Giriş-çıkış, servis, beslenme ve acil durum süreçleri için ailelerin sorması gerekenler.'],
            'ozel-egitim-destegi' => ['kind' => 'care', 'name' => 'Özel eğitim ve gelişim desteği', 'summary' => 'Bireysel plan, uzman kadro, raporlama ve aile katılımı nasıl değerlendirilir'],
            'soru-cevap' => ['kind' => 'faq', 'name' => 'Soru-cevap dosyası', 'summary' => 'Çocuk bakım ve eğitim kurumları hakkında ailelerden gelen pratik sorular.'],
        ],
    ],
    'rehabilitasyon' => [
        'label' => 'rehabilitasyon',
        'title_label' => 'Rehabilitasyon',
        'need' => 'uzman değerlendirmesi, terapi planı, düzenli ölçümleme ve ev programı takibi',
        'audience' => 'fizik tedavi, nörolojik rehabilitasyon veya özel terapi desteği arayan kullanıcılar',
        'checks' => ['Uzman kadro ve branşlar', 'Seans süresi ve sıklığı', 'Cihaz ve terapi ekipmanı', 'Raporlama ve ev programı', 'İlerleme ölçüm yöntemi'],
        'questions' => [
            ['Rehabilitasyon merkezi seçerken ne sorulmalı', 'Tanı ve hedefe uygun uzmanlık, seans planı, ekipman, ölçümleme ve raporlama düzeni sorulmalıdır.'],
            ['Her fizik tedavi merkezi aynı hizmeti mi verir', 'Hayır. Ortopedik, nörolojik, pediatrik veya konuşma terapisi gibi alanlar farklı uzmanlık gerektirir.'],
            ['Evde takip gerekli mi', 'Bazı süreçlerde ev egzersizi ve ara takip tedavi başarısını artırır; kurumun bunu nasıl yönettiği sorulmalıdır.'],
        ],
        'topics' => [
            'rehberi' => ['kind' => 'guide', 'name' => 'Merkez seçme rehberi', 'summary' => 'Uzmanlık alanı, seans planı, ekipman, raporlama ve tedavi hedeflerini birlikte değerlendirin.'],
            'terapi-plani' => ['kind' => 'program', 'name' => 'Terapi planı ve seans düzeni', 'summary' => 'Seans sıklığı, süre, hedefler ve ilerleme ölçümünün nasıl takip edildiğini öğrenin.'],
            'uzman-kadro-ekipman' => ['kind' => 'safety', 'name' => 'Uzman kadro ve ekipman kontrolü', 'summary' => 'Fizyoterapist, terapist, cihaz, egzersiz alanı ve branş yeterliliğini sorgulayın.'],
            'ev-programi-takip' => ['kind' => 'care', 'name' => 'Ev programı ve takip süreci', 'summary' => 'Merkez dışı egzersizlerin, ara kontrollerin ve aile bilgilendirmesinin nasıl yürüdüğünü inceleyin.'],
            'soru-cevap' => ['kind' => 'faq', 'name' => 'Soru-cevap dosyası', 'summary' => 'Rehabilitasyon süreci hakkında en sık sorulan sorulara anlaşılır cevaplar.'],
        ],
    ],
];

$brands = [
    'bakimevibul' => [
        'voice' => 'Hızlı karşılaştırma',
        'home_title' => 'İhtiyacınıza uygun kurumu adım adım bulun',
        'home_intro' => 'bakimevibul.com, ailelerin çok sayıda kurum arasında kaybolmadan il, ilçe, kurum türü, hizmet ve bütçe bilgisiyle hızlı karşılaştırma yapması için tasarlandı.',
        'article_prefix' => 'Karşılaştırma rehberi',
        'faq_prefix' => 'Pratik soru cevap',
        'body_opening' => 'Bu içerik, seçenekleri hızlı eleyip doğru kurumlarla görüşmek isteyen kullanıcılar için kısa ve uygulanabilir bir kontrol yapısı sunar.',
        'cta' => 'Listeye geçmeden önce ihtiyaç, konum, hizmet kapsamı ve bütçe aralığını netleştirin; ardından aynı kriterlerle kurumları karşılaştırın.',
    ],
    'bakimeviara' => [
        'voice' => 'Aile odaklı karar desteği',
        'home_title' => 'Doğru kararı daha sakin ve bilinçli verin',
        'home_intro' => 'bakimeviara.com, ailelerin sadece liste görmesini değil; kurum seçerken hangi soruları sorması gerektiğini, hangi ayrıntıların günlük yaşamı etkilediğini anlamasını sağlar.',
        'article_prefix' => 'Aileler için seçim rehberi',
        'faq_prefix' => 'Ailelerin sık sorduğu sorular',
        'body_opening' => 'Bu yazı, ailelerin görüşme öncesi kaygılarını azaltmak ve karar sürecini daha anlaşılır hale getirmek için hazırlanmıştır.',
        'cta' => 'Kurumla görüşürken günlük rutin, iletişim dili, güvenlik ve aileye bilgi verme düzenini mutlaka birlikte değerlendirin.',
    ],
    'bakimevleri' => [
        'voice' => 'Kapsamlı bilgi merkezi',
        'home_title' => 'Bakım ve rehabilitasyon kararlarını bilgiyle yönetin',
        'home_intro' => 'bakimevleri.com, bakım, çocuk gelişimi ve rehabilitasyon alanlarında karar vermeden önce okunması gereken kriterleri, kontrol listelerini ve soru-cevap içeriklerini bir araya getirir.',
        'article_prefix' => 'Bilgi merkezi',
        'faq_prefix' => 'Uzman cevapları',
        'body_opening' => 'Bu içerik, karar sürecini daha sistemli yönetmek isteyen kullanıcılar için kriterleri, risk noktalarını ve görüşme başlıklarını bir araya getirir.',
        'cta' => 'Karar verirken yalnızca ilan bilgisine değil, hizmet standardı, uzmanlık, ölçümleme ve yazılı süreç netliğine de bakın.',
    ],
];

$topicTemplates = [
    'guide' => [
        'sections' => [
            ['Önce ihtiyacı tanımlayın', 'Günlük destek ihtiyacı, beklenen hizmet türü, konum, bütçe ve özel hassasiyetler yazılı hale getirilmelidir. Bu hazırlık kurumlarla aynı çerçevede görüşmeyi sağlar.'],
            ['Kriterleri birlikte değerlendirin', 'Tek bir iyi özellik karar için yeterli değildir. Güvenlik, uzmanlık, iletişim, raporlama, fiziksel koşullar ve toplam maliyet birlikte puanlanmalıdır.'],
            ['Görüşme sonrası not alın', 'Her görüşmeden sonra artı-eksi notları çıkarın. Aynı sorulara verilen cevapları yan yana koymak, duygusal ve acele kararları azaltır.'],
        ],
    ],
    'pricing' => [
        'sections' => [
            ['Fiyatın içine neler dahil', 'Aylık ücretin hangi hizmetleri kapsadığı, ek ücretlerin ne zaman doğduğu ve ödeme dönemleri görüşme sırasında açıkça sorulmalıdır.'],
            ['Ucuz teklif neden yanıltabilir', 'Düşük fiyat bazı hizmetlerin dışarıda bırakıldığı anlamına gelebilir. Bu nedenle fiyat, hizmet kapsamı ve ihtiyaç uyumu birlikte okunmalıdır.'],
            ['Teklifleri aynı tabloya koyun', 'Konum, hizmetler, ek ücretler, ziyaret imkanı, iletişim düzeni ve toplam maliyet aynı tabloda karşılaştırılmalıdır.'],
        ],
    ],
    'visit' => [
        'sections' => [
            ['İlk izlenimi yapılandırın', 'Kuruma girerken karşılama, temizlik, güvenlik, ortam düzeni ve personelin iletişim dili not edilmelidir.'],
            ['Günlük rutini sorun', 'Yemek, dinlenme, etkinlik, sağlık veya eğitim takibi gibi günlük işleyiş başlıkları somut örneklerle anlatılmalıdır.'],
            ['Belge ve süreçleri netleştirin', 'Sözleşme, ücret kalemleri, izin, ziyaret, raporlama ve acil durum prosedürleri yazılı olarak görülmelidir.'],
        ],
    ],
    'program' => [
        'sections' => [
            ['Program hedefi ne', 'Uygulanan programın hangi yaş, ihtiyaç veya tedavi hedefine göre hazırlandığı açık biçimde anlatılmalıdır.'],
            ['Takip nasıl yapılıyor', 'Gelişim, ilerleme veya seans sonuçları düzenli notlarla izlenmeli ve aileye anlaşılır şekilde aktarılmalıdır.'],
            ['Esneklik var mı', 'İhtiyaç değiştiğinde planın nasıl güncellendiği ve kimin karar verdiği öğrenilmelidir.'],
        ],
    ],
    'safety' => [
        'sections' => [
            ['Güvenlik sadece kapı kontrolü değildir', 'Giriş-çıkış, acil durum, personel yeterliliği, hijyen, ekipman ve risk yönetimi bir bütün olarak ele alınmalıdır.'],
            ['Sorumluluklar yazılı olmalı', 'Kimin hangi durumda aileye haber vereceği, hangi kayıtların tutulduğu ve hangi belgelerin paylaşıldığı net olmalıdır.'],
            ['Ortamı yerinde görün', 'Fotoğraf veya ilan bilgisi yeterli değildir. Mümkünse kurum alanları yerinde görülmeli ve sorular doğrudan sorulmalıdır.'],
        ],
    ],
    'care' => [
        'sections' => [
            ['Kişiye özel takip', 'Standart hizmet listesi kadar kişinin ihtiyacına göre yapılan uyarlamalar da önemlidir. Planın nasıl kişiselleştirildiği sorulmalıdır.'],
            ['Raporlama düzeni', 'Aileye veya kullanıcıya hangi sıklıkla bilgi verildiği, ilerlemenin nasıl ölçüldüğü ve sorunların nasıl aktarıldığı bilinmelidir.'],
            ['Süreklilik', 'Personel değişimi, randevu aksaması veya özel durumlarda sürecin nasıl devam edeceği önceden öğrenilmelidir.'],
        ],
    ],
    'faq' => [
        'sections' => [],
    ],
];

$output = ['brands' => []];

foreach ($brands as $brandSlug => $brandCopy) {
    foreach ($sections as $sectionSlug => $section) {
        $articles = [];

        foreach ($section['topics'] as $topicSuffix => $topic) {
            $slug = $sectionSlug . '-' . $topicSuffix;
            $isFaq = $topic['kind'] === 'faq';
            $prefix = $isFaq ? $brandCopy['faq_prefix'] : $brandCopy['article_prefix'];
            $title = $prefix . ': ' . $topic['name'] . ' - ' . $section['title_label'];

            $articles[] = [
                'title' => $title,
                'slug' => $slug,
                'summary' => $topic['summary'],
            ];

            $checksHtml = '<ul>' . implode('', array_map(fn ($item) => '<li>' . $esc($item) . '</li>', $section['checks'])) . '</ul>';

            if ($isFaq) {
                $faqHtml = implode('', array_map(fn ($qa) => '<h2>' . $esc($qa[0]) . '</h2><p>' . $esc($qa[1]) . '</p>', $section['questions']));
                $body = '<p><strong>' . $esc($brandCopy['body_opening']) . '</strong></p>'
                    . '<p>Bu soru-cevap sayfası ' . $esc($section['audience']) . ' için hazırlanmıştır. Amaç, karar öncesi en sık karıştırılan başlıkları kısa ve net şekilde açıklamaktır.</p>'
                    . $faqHtml
                    . '<h2>Teklif almadan önce ne hazırlamalıyım</h2><p>İl, ilçe, yaklaşık bütçe, ihtiyaç duyulan hizmetler ve varsa özel sağlık, eğitim veya terapi beklentileri net olmalıdır. Bu bilgiler kurumların daha doğru dönüş yapmasını sağlar.</p>'
                    . '<h2>Birden fazla kurumla görüşmek gerekir mi</h2><p>Evet. En az üç kurumdan bilgi almak, fiyat-hizmet dengesini ve kurumların iletişim kalitesini daha iyi görmenizi sağlar.</p>';
            } else {
                $template = $topicTemplates[$topic['kind']] ?? $topicTemplates['guide'];
                $body = '<p><strong>' . $esc($brandCopy['home_intro']) . '</strong></p>'
                    . '<p>' . $esc($brandCopy['body_opening']) . ' Bu sayfada ' . $esc($section['label']) . ' alanında ' . $esc($topic['name']) . ' başlığına odaklanılır.</p>'
                    . '<h2>Kimler için önemli</h2><p>' . $esc(ucfirst($section['audience'])) . ' bu içerikteki başlıkları kullanarak görüşme öncesi daha net bir kontrol listesi oluşturabilir.</p>';

                foreach ($template['sections'] as $block) {
                    $body .= '<h2>' . $esc($block[0]) . '</h2><p>' . $esc($block[1]) . '</p>';
                }

                $body .= '<h2>Kontrol listesi</h2>' . $checksHtml
                    . '<h2>Son karar öncesi</h2><p>' . $esc($brandCopy['cta']) . '</p>';
            }

            $output['brands'][$brandSlug]['pages'][$slug] = [
                'title' => $title,
                'body' => $body,
            ];
        }

        $output['brands'][$brandSlug]['sections'][$sectionSlug] = [
            'headline' => $brandCopy['home_title'],
            'intro' => $brandCopy['home_intro'] . ' Bu bölümde ' . $section['label'] . ' için ' . $section['need'] . ' başlıkları öne çıkarılır.',
            'audience' => ucfirst($section['audience']) . ' için hazırlanmıştır.',
            'checks' => $section['checks'],
            'articles' => $articles,
            'faq_preview' => $section['questions'],
        ];
    }
}

return $output;