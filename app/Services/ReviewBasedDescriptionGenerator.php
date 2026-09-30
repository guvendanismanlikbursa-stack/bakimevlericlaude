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
    /**
     * @return array{son_id:int, islenen:int, guncellenen:int, guncellenen_idler:array<int>, atlanan:array<string>}
     */
    public function run(int $limit, int $afterId): array
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

        $facilities = Facility::where('is_claimed', false)
            ->where('is_broker_managed', false)
            ->where('id', '>', $afterId)
            ->whereHas('googleReviews', fn ($q) => $q->where('rating', 5)->whereNotNull('body')->where('body', '!=', ''))
            ->with(['googleReviews', 'category', 'city'])
            ->orderBy('id')
            ->limit($limit)
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

            if ($facility->is_claimed || $facility->is_broker_managed) {
                $skipped[] = "#{$facility->id} {$facility->name}: artik on kayitli degil, atlandi";
                continue;
            }

            $besYildizlilar = $facility->googleReviews
                ->filter(fn ($r) => $r->rating === 5 && filled($r->body) && mb_strlen(trim($r->body)) >= 25)
                ->values();

            if ($besYildizlilar->isEmpty()) {
                $skipped[] = "#{$facility->id} {$facility->name}: uygun (yeterince uzun) 5 yildizli yorum yok";
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

        return [
            'son_id' => $sonIslenenId,
            'islenen' => $facilities->count(),
            'guncellenen' => $updated,
            'guncellenen_idler' => $guncellenenIdler,
            'atlanan' => $skipped,
        ];
    }
}
