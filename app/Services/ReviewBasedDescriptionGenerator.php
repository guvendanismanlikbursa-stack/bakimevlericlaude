<?php

namespace App\Services;

use App\Models\Facility;

// 30 Eylul 2026: kullanicinin talebi - "yorum eklendigi zaman otomatik
// olarak yoruma gore aciklama yapmali" (once bu mantik SADECE OpsController::
// generateDescriptionsFromReviews() icinde, elle /_ops cagrisiyla tetiklenen
// bir tek fonksiyondu). Ayni mantik buraya tasindi ki hem eski elle-cagirma
// (Ops action, geriye donuk tam ayni cikti formatiyla) HEM DE yeni
// App\Console\Commands\GenerateDescriptionsFromReviews (gunluk otomatik
// calisan zamanlanmis gorev) AYNI kodu kullansin - iki kopyanin
// birbirinden sapma riski olmasin.
class ReviewBasedDescriptionGenerator
{
    // 7 Ekim 2026: kullanicinin acik talebi - "3 yildiz ve uzeri yorumlar
    // da dahil edilecek". Varsayilan artik 3 (eskiden 5) - gunluk otomatik
    // komut da (hicbir parametre vermeden cagirdigi icin) bu yeni
    // varsayilani otomatik kullanir, bu KASITLI (kullanicinin net talebi
    // TUM siteyi kapsiyor, sadece tek seferlik bir cagriyi degil).
    private const VARSAYILAN_MIN_RATING = 3;

    // 7 Ekim 2026: kullanicinin acik talebi - "uygun yorumu yok ise veya
    // hic yorum yok ise kurumda sadece ucret ve bilgi talep formuna
    // yonlendirme yazisi yazilacak". Eskiden boyle kurumlar TAMAMEN
    // atlaniyordu (hic aciklama/yonlendirme almiyorlardi - bkz. asagidaki
    // $facilitiesCtaOnly sorgusu, ESKIDEN YOKTU).
    //
    // 13 Ekim 2026: kullanicinin talebi - "thin content/kopya icerik
    // riskine karsi 3 yerine 10-15 varyasyon, degiskenlere (ilce/kategori)
    // gore". Sabit 3 metinden, {ilce}/{kategori} yer tutuculu 12 varyasyona
    // cikarildi - artik AYNI havuzdaki iki kurum bile farkli ilce/kategori
    // kombinasyonuyla farkli metin alir, gercek bir anti-kopya-icerik etkisi.
    private const SADECE_CTA_KALIPLARI = [
        'Bu kurum için henüz yeterli sayıda Google değerlendirmesi bulunmuyor. {ilce} bölgesindeki bu {kategori} hakkında güncel fiyat bilgisi ve uygunluk durumu için aşağıdaki formu doldurarak hızlıca bilgi talep edebilirsiniz.',
        'Bu kurum hakkında şu an yeterli yorum bulunmamaktadır. {il} {ilce}\'deki bu {kategori} için fiyat ve kontenjan bilgisi almak isterseniz, aşağıdaki formu kullanarak kuruma doğrudan ulaşabilirsiniz.',
        'Bu kurum için henüz bir değerlendirme paylaşılmamış. {ilce} bölgesindeki {kategori} seçenekleri arasında yer alan bu kurumdan detaylı bilgi ve güncel fiyat almak için formu doldurmanız yeterlidir.',
        '{ilce}\'deki bu {kategori} için platformumuzda henüz bir Google değerlendirmesi yer almıyor. Yine de aşağıdaki formu doldurarak kurumdan doğrudan bilgi ve fiyat talep edebilirsiniz.',
        'Bu {kategori}, {il} {ilce}\'de hizmet vermektedir ancak henüz yeterli sayıda değerlendirmeye sahip değil. Kontenjan ve fiyat bilgisi için aşağıdaki formu doldurarak kuruma ulaşabilirsiniz.',
        '{ilce} bölgesindeki bu kurum için henüz yorum bulunmuyor. {kategori} ihtiyacınızla ilgili güncel bilgi almak için aşağıdaki formu doldurmanız yeterli, kurum sizinle iletişime geçecektir.',
        'Bu kurumla ilgili henüz bir Google yorumu paylaşılmamış. {il} {ilce}\'deki bu {kategori} hakkında fiyat ve uygunluk bilgisi için aşağıdaki formu kullanabilirsiniz.',
        'Platformumuzda bu kurum için şu an yeterli değerlendirme yok. {ilce}\'deki bu {kategori} ile ilgili detaylı bilgi almak isterseniz aşağıdaki formu doldurmanız yeterlidir.',
        'Bu {kategori} henüz yeterli sayıda Google değerlendirmesi almamış. {il} {ilce} bölgesinde hizmet veren bu kurumdan fiyat teklifi almak için aşağıdaki formu doldurabilirsiniz.',
        '{ilce}\'de yer alan bu {kategori} için henüz bir değerlendirme bulunmuyor. Güncel fiyat ve kontenjan durumunu öğrenmek için aşağıdaki formu doldurarak kuruma ulaşabilirsiniz.',
        'Bu kurum hakkında platformumuzda henüz yorum yok. {il} {ilce}\'deki bu {kategori} ile ilgili bilgi almak için aşağıdaki formu doldurmanız yeterli, size dönüş yapılacaktır.',
        'Henüz değerlendirilmemiş bir kurum. {ilce} bölgesindeki bu {kategori} hakkında fiyat ve detaylı bilgi için aşağıdaki formu doldurarak doğrudan kuruma ulaşabilirsiniz.',
    ];

    /**
     * 13 Ekim 2026: {ilce}/{il}/{kategori} yer tutuculari GERCEK kurum
     * verisiyle doldurulur - OpsController'daki elle-duzeltme yolunun da
     * AYNI havuzu/mantigi kullanmasi icin public (DRY, iki kopya riski olmasin).
     */
    public static function ctaMetni(Facility $facility): string
    {
        $kalip = self::SADECE_CTA_KALIPLARI[$facility->id % count(self::SADECE_CTA_KALIPLARI)];

        return strtr($kalip, [
            '{ilce}' => $facility->district ?: ($facility->city->name ?? 'bu bölge'),
            '{il}' => $facility->city->name ?? '',
            '{kategori}' => mb_strtolower($facility->category->name ?? 'kurum'),
        ]);
    }

    /**
     * @return array{son_id:int, islenen:int, guncellenen:int, guncellenen_idler:array<int>, atlanan:array<string>}
     */
    /**
     * 13 Ekim 2026: kullanicinin talebi - "kalan 43 sablon aciklamali kurumu
     * hallet, sahiplenmiste olsa olumlu yorumlarina gore aciklama ekle".
     * $facilityIds verilirse: (1) afterId/cityId sayfalamasi yerine TAM
     * OLARAK bu ID'ler islenir, (2) is_claimed/is_broker_managed guvenlik
     * filtresi KALDIRILIR - caller zaten BILEREK bu kesin kurumlari sectigi
     * icin (gunluk otomatik gorevin genis taramasindaki guvenlik amacli
     * disarida birakma burada anlamsiz).
     */
    public function run(int $limit, int $afterId, int $minRating = self::VARSAYILAN_MIN_RATING, ?int $cityId = null, ?array $facilityIds = null): array
    {
        $odakCumleleri = [
            'yasli-bakim' => [
                'huzurevi ortamında güvenli bakım, düzenli sağlık takibi ve zengin sosyal yaşam imkanları sunmaktadır',
                'yaşlı bireylerin huzurlu, güvenli ve sosyal açıdan aktif bir yaşam sürmesini hedeflemektedir',
                'sağlık takibi ve günlük yaşam desteğini bir arada sunan bir bakım anlayışına sahiptir',
            ],
            'cocuk' => [
                'çocuğunuzun gelişimini destekleyen güvenli bir ortam ve düzenli veli iletişimi sunmaktadır',
                'çocukların güvenli, eğlenceli ve gelişim odaklı bir ortamda vakit geçirmesini önceliklendirmektedir',
                'deneyimli bir kadro eşliğinde çocuğunuzun sosyal ve bilişsel gelişimini desteklemeyi amaçlamaktadır',
            ],
            'rehabilitasyon' => [
                'uzman kadrosu ve kişiye özel terapi planlarıyla rehabilitasyon sürecinde yanınızda olmaktadır',
                'her danışanına özel hazırlanan tedavi planlarıyla rehabilitasyon hizmeti sunmaktadır',
                'alanında deneyimli uzmanlarıyla düzenli takip ve raporlama esasına dayalı bir hizmet vermektedir',
            ],
        ];

        $girisKaliplari = [
            '{isim}, {ilce}/{il} bölgesinde {kategori} alanında hizmet veren bir kurumdur ve {odak}.',
            '{ilce}, {il}\'de faaliyet gösteren {isim}, {kategori} alanında {odak}.',
            '{il} {ilce}\'de bulunan {isim}, ailelere {kategori} hizmeti sunmakta ve {odak}.',
            '{isim} isimli kurum, {ilce}/{il} bölgesinde {kategori} alanında hizmet vermekte, bu doğrultuda {odak}.',
        ];

        $puanKaliplari = [
            'Google Haritalar üzerinde {puanMetni}{yorumSayisi} değerlendirme ile takip edilmektedir.',
            'Kurum, Google\'daki profilinde {puanMetni}{yorumSayisi} yorum almıştır.',
            '{yorumSayisi} aile tarafından Google üzerinden değerlendirilen kurumun {puanMetni2} bulunmaktadır.',
        ];

        $alintiKaliplari1 = [
            'Bir veli yorumunda şunları paylaşmış: "{alinti}"',
            'Ailelerden biri deneyimini şöyle aktarmış: "{alinti}"',
            'Kurumu tercih eden bir aile şu ifadeleri kullanmış: "{alinti}"',
            'Yorumlardan birinde şöyle denilmiş: "{alinti}"',
        ];

        $alintiKaliplari2 = [
            'Başka bir yorumda ise şu satırlar dikkat çekiyor: "{alinti}"',
            'Bir başka veli de şunları belirtmiş: "{alinti}"',
            'Farklı bir değerlendirmede şu ifadelere yer verilmiş: "{alinti}"',
        ];

        $tekYorumYedek = [
            'Kurumu tercih eden aileler, aldıkları hizmetten memnuniyetlerini yorumlarına yansıtmaktadır.',
            'Google\'daki değerlendirmelerde ailelerin genel eğilimi olumlu yöndedir.',
        ];

        $kapanisKaliplari = [
            'Bu tür geri bildirimler, kurumu değerlendirirken size somut bir fikir verebilir.',
            'Bu paylaşımlar, kurum hakkında karar verirken size yol gösterici olabilir.',
            'Gerçek aile deneyimleri, kurumu tanımanıza yardımcı olabilir.',
        ];

        $ctaKaliplari = [
            'Güncel fiyat bilgisi ve uygunluk durumu için aşağıdaki formu doldurarak bu kurumdan hızlıca bilgi talep edebilirsiniz.',
            'Fiyat ve kontenjan bilgisi almak için aşağıdaki formu kullanarak kuruma doğrudan ulaşabilirsiniz.',
            'Detaylı bilgi ve güncel fiyat için aşağıdaki formu doldurmanız yeterlidir, kurum sizinle iletişime geçecektir.',
            'Aşağıdaki formu doldurarak bu kurumdan fiyat ve bilgi talebinde bulunabilir, size dönüş yapılmasını sağlayabilirsiniz.',
        ];

        $facilities = Facility::query()
            ->when($facilityIds, fn ($q) => $q->whereIn('id', $facilityIds), function ($q) use ($afterId, $cityId) {
                $q->where('is_claimed', false)
                    ->where('is_broker_managed', false)
                    ->where('id', '>', $afterId)
                    ->when($cityId, fn ($qq) => $qq->where('city_id', $cityId));
            })
            ->whereHas('googleReviews', fn ($q) => $q->where('rating', '>=', $minRating)->whereNotNull('body')->where('body', '!=', ''))
            ->with(['googleReviews', 'category', 'city'])
            ->orderBy('id')
            ->limit($facilityIds ? count($facilityIds) : $limit)
            ->get();

        $updated = 0;
        $skipped = [];
        $guncellenenIdler = [];
        $sonIslenenId = $afterId;

        $kisalt = function (string $s, int $max = 200) {
            $s = trim(preg_replace('/\s+/', ' ', $s));

            return mb_strlen($s) > $max ? mb_substr($s, 0, $max).'…' : $s;
        };

        foreach ($facilities as $facility) {
            $sonIslenenId = $facility->id;

            if (! $facilityIds && ($facility->is_claimed || $facility->is_broker_managed)) {
                $skipped[] = "#{$facility->id} {$facility->name}: artik on kayitli degil, atlandi";
                continue;
            }

            $besYildizlilar = $facility->googleReviews
                ->filter(fn ($r) => $r->rating >= $minRating && filled($r->body) && mb_strlen(trim($r->body)) >= 25)
                ->sortByDesc('rating')
                ->values();

            if ($besYildizlilar->isEmpty()) {
                $skipped[] = "#{$facility->id} {$facility->name}: uygun (yeterince uzun) {$minRating}+ yildizli yorum yok";
                continue;
            }

            // 30 Eylul 2026: kullanicinin talebi - bu artik zamanlanmis gorevle
            // OTOMATIK calisiyor (bkz. App\Console\Commands\
            // GenerateDescriptionsFromReviews), gunde bir kez TUM uygun
            // kurumlari tarayacak. Ayni kurumu, ayni en iyi yorumla HER GUN
            // yeniden yazmamak icin: en iyi yorumun (kisaltilmis) metni zaten
            // mevcut aciklamada geciyorsa bu kurum degismis sayilmaz, atlanir.
            // Daha iyi/yeni bir yorum gelirse (en iyi yorum degisirse) metin
            // artik eslesmez ve dogal olarak yeniden uretilir.
            $enIyiAlinti = $kisalt($besYildizlilar[0]->body);
            if (str_contains((string) $facility->description, $enIyiAlinti)) {
                $skipped[] = "#{$facility->id} {$facility->name}: aciklama zaten guncel (en iyi yorum degismedi)";
                continue;
            }

            $sec = fn (array $havuz) => $havuz[$facility->id % count($havuz)];

            $section = service_section_for_scope($facility->category->brand_scope ?? '');
            $sectionSlug = $section['slug'] ?? null;
            $odakHavuzu = $odakCumleleri[$sectionSlug] ?? ['alanında güvenilir ve titiz bir hizmet sunmaktadır'];
            $odak = $sec($odakHavuzu);

            $yorumSayisi = $facility->googleReviews->count();
            $puanMetni = $facility->rating > 0 ? number_format((float) $facility->rating, 1, ',', '.').'/5 puan ve ' : '';
            $puanMetni2 = $facility->rating > 0 ? number_format((float) $facility->rating, 1, ',', '.').'/5 puanı' : 'olumlu bir profili';

            $degistir = fn (string $kalip, array $yer) => strtr($kalip, $yer);

            $satirlar = [];
            $satirlar[] = $degistir($sec($girisKaliplari), [
                '{isim}' => $facility->name,
                '{ilce}' => $facility->district ?: ($facility->city->name ?? ''),
                '{il}' => $facility->city->name ?? '',
                '{kategori}' => $facility->category->name ?? '',
                '{odak}' => $odak,
            ]);

            $satirlar[] = $degistir($sec($puanKaliplari), [
                '{puanMetni}' => $puanMetni,
                '{puanMetni2}' => $puanMetni2,
                '{yorumSayisi}' => $yorumSayisi,
            ]);

            $satirlar[] = $degistir($sec($alintiKaliplari1), [
                '{alinti}' => $enIyiAlinti,
            ]);

            if ($besYildizlilar->count() > 1) {
                $satirlar[] = $degistir($sec($alintiKaliplari2), [
                    '{alinti}' => $kisalt($besYildizlilar[1]->body),
                ]);
            } else {
                $satirlar[] = $sec($tekYorumYedek);
            }

            $satirlar[] = $sec($kapanisKaliplari);
            $satirlar[] = $sec($ctaKaliplari);

            $facility->update(['description' => fix_locative_suffix(implode("\n\n", $satirlar))]);
            $updated++;
            $guncellenenIdler[] = $facility->id;
        }

        // 7 Ekim 2026: kullanicinin acik talebi - "uygun yorumu yok ise
        // veya hic yorum yok ise kurumda sadece ucret ve bilgi talep
        // formuna yonlendirme yazisi yazilacak" + "yorumu olanlarda da bu
        // yonlendirme mutlaka olmali". Yukaridaki ana sorgu SADECE
        // $minRating+ yorumu olan kurumlari getiriyor - digerleri (yorumu
        // olmayan VEYA hepsi dusuk puanli olan) hic ele alinmiyordu.
        //
        // ONEMLI KESIF: ilk versiyonda bu ikinci gecis SADECE acikca BOS
        // (NULL/'') aciklamali kurumlari hedefliyordu - ama DataExtractorImportService
        // ve DataImportRowApprovalService import SIRASINDA otomatik bir
        // "... Google Maps verilerinden tespit edilmiştir." metni
        // yaziyordu (bkz. o iki servisin ayni tarihli yorumu, oraya da CTA
        // eklendi) - yani aciklama BOS DEGILDI, sadece yonlendirme CUMLESI
        // EKSIKTI. "formu doldur..." ibaresi (TUM CTA kaliplarinda - hem
        // asagidaki hem $ctaKaliplari'nda - ORTAK gecen tek sabit metin)
        // aramak, hangi aciklamanin zaten yonlendirme icerdigini hangisinin
        // icermedigini AYIRT EDER.
        $ctaMarker = 'formu doldur';
        $ctaOnlyFacilities = Facility::query()
            ->when($facilityIds, fn ($q) => $q->whereIn('id', $facilityIds), function ($q) use ($afterId, $cityId) {
                $q->where('is_claimed', false)
                    ->where('is_broker_managed', false)
                    ->where('id', '>', $afterId)
                    ->when($cityId, fn ($qq) => $qq->where('city_id', $cityId));
            })
            ->where(fn ($q) => $q->whereNull('description')->orWhere('description', 'not like', "%{$ctaMarker}%"))
            ->whereDoesntHave('googleReviews', fn ($q) => $q->where('rating', '>=', $minRating)->whereNotNull('body')->where('body', '!=', ''))
            ->with(['city', 'category'])
            ->orderBy('id')
            ->limit($facilityIds ? count($facilityIds) : $limit)
            ->get(['id', 'name', 'description', 'city_id', 'facility_category_id', 'district']);

        $ctaOnlyUpdated = 0;
        $ctaOnlyIdler = [];
        foreach ($ctaOnlyFacilities as $facility) {
            $sonIslenenId = max($sonIslenenId, $facility->id);
            $cta = self::ctaMetni($facility);
            // Mevcut aciklama (ör. "... Google Maps verilerinden tespit
            // edilmiştir.") VARSA korunur, CTA sadece SONUNA eklenir -
            // mevcut aciklamanin ustune YAZILMAZ. Aciklama hic yoksa
            // (NULL/bos) CTA metni TEK BASINA aciklama olur.
            $yeniAciklama = filled($facility->description)
                ? trim($facility->description)."\n\n".$cta
                : $cta;
            $facility->update(['description' => $yeniAciklama]);
            $ctaOnlyUpdated++;
            $ctaOnlyIdler[] = $facility->id;
        }

        return [
            'son_id' => $sonIslenenId,
            'islenen' => $facilities->count() + $ctaOnlyFacilities->count(),
            'guncellenen' => $updated,
            'guncellenen_idler' => $guncellenenIdler,
            'guncellenen_sadece_cta' => $ctaOnlyUpdated,
            'guncellenen_sadece_cta_idler' => $ctaOnlyIdler,
            'atlanan' => $skipped,
        ];
    }
}
