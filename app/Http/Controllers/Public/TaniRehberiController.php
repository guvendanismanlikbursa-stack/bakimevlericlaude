<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Facility;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * 13 Ekim 2026: kullanicinin talebi - pazar arastirmasinda veliler/aileler
 * genel "rehabilitasyon merkezi" degil, dogrudan TANI bazli aradigi
 * (Disleksi, Otizm, Ergoterapi, Dil ve Konusma Terapisi, Duyu Butunleme)
 * tespit edildi. Platformda hangi kurumun hangi taniya baktigina dair
 * YAPILANDIRILMIS veri YOK (facility_categories sadece genis kurum TURUNU
 * tutuyor, tani degil) - bu yuzden bu sayfalar GERCEK bir filtre degil,
 * durust bir SEO/icerik sayfasi: o il/ilcedeki TUM rehabilitasyon/ozel
 * egitim kurumlarini listeler, ama sayfa basligi/govdesi dogrudan aranan
 * taniya hitap eder. Hicbir yerde "bu kurum X taniya bakar" gibi UYDURULMUS
 * bir iddia yapilmaz.
 */
class TaniRehberiController extends Controller
{
    public const TANILAR = [
        'disleksi' => [
            'title' => 'Disleksi (Özel Öğrenme Güçlüğü)',
            'intro' => 'Disleksi, okuma-yazma becerilerini etkileyen bir özel öğrenme güçlüğüdür. Erken fark edilip doğru destekle çocukların akademik ve sosyal hayatı büyük ölçüde iyileşir.',
        ],
        'otizm' => [
            'title' => 'Otizm Spektrum Bozukluğu',
            'intro' => 'Otizm spektrum bozukluğu olan çocuklar için erken ve düzenli özel eğitim desteği, iletişim ve sosyal becerilerin gelişiminde kritik rol oynar.',
        ],
        'ergoterapi' => [
            'title' => 'Ergoterapi',
            'intro' => 'Ergoterapi, günlük yaşam becerilerini (giyinme, yeme, oyun, okul aktiviteleri) geliştirmeyi hedefleyen, çocuğun bağımsızlığını artıran bir rehabilitasyon alanıdır.',
        ],
        'dil-ve-konusma-terapisi' => [
            'title' => 'Dil ve Konuşma Terapisi',
            'intro' => 'Konuşma gecikmesi, artikülasyon bozukluğu veya dil gelişimiyle ilgili güçlük yaşayan çocuklar için dil ve konuşma terapisi, iletişim becerilerini güçlendirir.',
        ],
        'duyu-butunleme' => [
            'title' => 'Duyu Bütünleme',
            'intro' => 'Duyu bütünleme terapisi, duyusal uyarılara aşırı veya az tepki veren çocukların günlük yaşamda daha rahat işlev görmesini destekler.',
        ],
    ];

    public function show(Request $request)
    {
        $taniSlug = $request->route('taniSlug');
        $tani = self::TANILAR[$taniSlug] ?? null;
        abort_if(! $tani, 404);

        $brand = current_brand();
        $citySlug = $request->route('citySlug');
        $districtSlug = $request->route('districtSlug');

        $city = City::findBySlugCached($citySlug) ?? abort(404);
        $districts = districts_for_city($city->name);
        $districtName = null;
        if ($districtSlug) {
            foreach ($districts as $candidate) {
                if (Str::slug($candidate) === $districtSlug) {
                    $districtName = $candidate;
                    break;
                }
            }
            abort_if(! $districtName, 404);
        }

        // ozel-egitim + rehabilitasyon + fizik-tedavi: FamilyNeedSurvey::
        // categoryScopes()'teki "ozel_egitim_rehabilitasyon" ile AYNI scope
        // grubu - platformda bu tani basliklarina en yakin gercek kategori kumesi.
        $scopes = ['ozel-egitim', 'rehabilitasyon', 'fizik-tedavi'];

        // 13 Ekim 2026: kullanicinin acik uyarisi - "kopya icerik olmamali".
        // Ayni il/ilce icin 5 farkli tani sayfasi, platformda tani-bazli
        // yapilandirilmis veri olmadigi icin AYNI kurum kumesini listeler -
        // bu zaten bu kod tabaninda location-guide/price-guide sayfalarinda
        // YASANMIS ve COZULMUS bir sorun (bkz. LocationGuideController
        // ayni tarihli yorumlar). AYNI cozum burada da uygulanir: (1) her
        // sayfaya GERCEK, o sayfaya OZGU veri (fiyat araligi + gercek
        // kurum sayisiyla SSS) eklenir, (2) kurum SIRASI tani'ya gore
        // degisir (orderByListingPriority TEK bir global sira verir - 5
        // kardes sayfa ayni 12 karti ayni sirada gosterirdi; tani+kurum
        // id'sine gore deterministik ama FARKLI bir sira kullanilir).
        $cacheKey = 'tani-rehberi:v2:'.md5(implode('|', [$brand['slug'], $taniSlug, $city->id, $districtName ?? '']));
        $data = Cache::remember($cacheKey, now()->addHours(6), function () use ($scopes, $city, $districtName, $taniSlug) {
            $query = Facility::discoverable()
                ->forBrand($scopes)
                ->where('city_id', $city->id)
                ->with(['city', 'category', 'images']);

            if ($districtName) {
                $query->where('district', $districtName);
            }

            $facilityCount = (clone $query)->count();
            $all = $query->orderByListingPriority()->limit(60)->get();
            $facilities = $all->sortBy(fn ($f) => crc32($taniSlug.'|'.$f->id))->take(12)->values();

            $priceQuery = Facility::discoverable()->forBrand($scopes)
                ->where('city_id', $city->id)
                ->whereNotNull('price_min')->where('price_min', '>', 0);
            if ($districtName) {
                $priceQuery->where('district', $districtName);
            }
            $priceStats = $priceQuery->selectRaw('MIN(price_min) as min_price, MAX(COALESCE(price_max, price_min)) as max_price, COUNT(*) as priced_count')->first();
            $priceRange = ($priceStats && $priceStats->priced_count && $priceStats->min_price) ? [
                'min' => (float) $priceStats->min_price,
                'max' => (float) $priceStats->max_price,
                'priced_count' => (int) $priceStats->priced_count,
            ] : null;

            return compact('facilityCount', 'facilities', 'priceRange');
        });

        $nearDistricts = collect($districts)->take(18)->values();
        $location = trim(($districtName ? $districtName.', ' : '').$city->name);
        $faq = [];
        if ($data['priceRange']) {
            $min = number_format($data['priceRange']['min'], 0, ',', '.');
            $max = number_format($data['priceRange']['max'], 0, ',', '.');
            $faq[] = [
                "{$location} bölgesinde {$tani['title']} desteği veren kurumların fiyatları ne kadar?",
                $data['priceRange']['min'] === $data['priceRange']['max']
                    ? "{$location} bölgesinde özel eğitim/rehabilitasyon kurumlarında aylık ücret platformumuzdaki güncel verilere göre {$min} TL civarındadır."
                    : "{$location} bölgesinde fiyat bilgisi paylaşan {$data['priceRange']['priced_count']} kurumda aylık ücretler {$min} TL ile {$max} TL arasında değişmektedir.",
            ];
        }
        if ($data['facilityCount'] > 0) {
            $faq[] = [
                "{$location} bölgesinde kaç özel eğitim/rehabilitasyon kurumu var?",
                "Şu anda platformumuzda {$location} bölgesinde {$data['facilityCount']} adet özel eğitim ve rehabilitasyon kurumu listelenmektedir. {$tani['title']} alanında hizmet verip vermediklerini öğrenmek için kuruma doğrudan ulaşabilirsiniz.",
            ];
        }

        return view('themes._shared.tani-rehberi', [
            'brand' => $brand,
            'tani' => $tani,
            'taniSlug' => $taniSlug,
            'city' => $city,
            'districtName' => $districtName,
            'nearDistricts' => $nearDistricts,
            'facilities' => $data['facilities'],
            'facilityCount' => $data['facilityCount'],
            'priceRange' => $data['priceRange'],
            'faq' => $faq,
        ]);
    }
}
