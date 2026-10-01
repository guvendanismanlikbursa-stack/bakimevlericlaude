<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Public\Concerns\FiltersFacilities;
use App\Models\City;
use App\Models\Facility;
use App\Models\FacilityCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class HomeController extends Controller
{
    use FiltersFacilities;

    public function index(Request $request)
    {
        $brand = current_brand();
        $sections = service_sections();
        $activeSection = active_service_section($request->query('bolum'), $brand);
        $sectionScopes = $activeSection['scopes'];

        // 14 Agustos 2026: kullanicinin talebi - anasayfada "Öne Çıkanlar"
        // sayisi arttikca liste uzayip gitmemeli, 12'şer 12'şer sayfalanmali
        // (2., 3. sayfa...) - 21 Eylul 2026: kart grid'i 4 sutuna cikinca
        // (md:grid-cols-4) 6, sadece 1.5 satir doldurup 2. satiri yarim
        // birakiyordu; 12 = tam 3 satir. Ayri bir 'featured_page' parametresi
        // kullaniliyor ki asagidaki $filteredFacilities->paginate()
        // (varsayilan 'page' parametresi) ile cakismasin.
        // 11 Eylul 2026: kullanicinin bildirdigi tekrarlayan "Too many
        // connections" hatasi - anasayfa TUM sitenin en cok ziyaret edilen
        // sayfasi ama her acilisinda 7-8 ayri sorgu calistiriyordu (öne
        // cikanlar + sahiplenilmis + on kayitli + 3 ayri sayac). Bu blok
        // her ziyaretcide/bottа AYNI sonucu urettigi icin 10 dakikalik kisa
        // sureli onbellege alindi - veri en fazla 10 dakika eskiyebilir,
        // karsiliginda anasayfadaki DB yuku neredeyse sifira iner. Filtre
        // sonuclari (asagidaki $isFiltering blogu) bu onbellegin DISINDA,
        // her zaman canli kalir.
        $homeCacheKey = 'home:sections:v3:'.md5(implode('|', [
            $brand['slug'], $activeSection['slug'], (string) $request->query('featured_page', 1),
        ]));

        $homeData = Cache::remember($homeCacheKey, now()->addMinutes(10), function () use ($sectionScopes, $brand) {
            // 30 Eylul 2026: kullanicinin talebi - "hepsi ayni kart tipinde,
            // tek liste, sira: Anlaşmalı > Öne Çıkan > Önerilen > Ön Kayıtlı".
            // Onceden 4 AYRI sorgu/bolum vardi (featured/featuredOnly/
            // claimedFacilities/preRegistered, her biri kendi basligi/CTA'si
            // ile) - hepsi TEK bir paylasilan oncelik sirasiyla (bkz.
            // Facility::scopeOrderByListingPriority) TEK listeye birlesti.
            // Anasayfada SAYFALAMA YOK - sadece ilk 12 kurum (2 sutun x 6
            // sira) gosterilir, devami icin "Kurumlar" (filtre) sayfasina
            // yonlendiren bir link var (bkz. home/_results.blade.php).
            $facilities = Facility::discoverable()
                ->forBrand($sectionScopes)
                ->with(['city', 'category', 'images'])->withCount(['approvedReviews', 'googleReviews'])
                ->orderByListingPriority()
                ->orderByDesc('id')
                ->limit(12)
                ->get();

            // 12 Agustos 2026: kullanicinin talebi - anasayfa "maksimum premium"
            // seviyeye tasinirken guven veren gercek, canli rakamlar eklendi
            // (Hakkimizda sayfasindaki ayni yaklasim).
            $facilityCount = Facility::discoverable()->forBrand($brand['category_scope'])->count();
            $cityCount = Facility::discoverable()->forBrand($brand['category_scope'])
                ->join('cities', 'cities.id', '=', 'facilities.city_id')
                ->distinct('cities.id')->count('cities.id');
            $claimedCount = Facility::discoverable()->forBrand($brand['category_scope'])->where('is_claimed', true)->count();

            return compact('facilities', 'facilityCount', 'cityCount', 'claimedCount');
        });

        ['facilities' => $facilities, 'facilityCount' => $facilityCount, 'cityCount' => $cityCount, 'claimedCount' => $claimedCount] = $homeData;

        // 14 Eylul 2026: kullanicinin talebi - anlaşmalı (is_broker_managed)
        // kurumlari il secilmemisse Turkiye genelinde, il secilmisse SADECE
        // o ile ait pin'lerle haritada gosterir. Su an cok az sayida
        // (birkaç düzine) anlaşmalı kurum oldugu icin bu sorgu cok hafif -
        // ayrica onbelleklemeye gerek yok, her zaman canli/guncel kalir ki
        // yeni bir kurum anlaşmalı yapildiginda haritada ANINDA gorunsun.
        $brokerCitySlug = $request->query('city');
        $brokerFacilities = Facility::discoverable()
            ->forBrand($sectionScopes)
            ->where('is_broker_managed', true)
            ->whereNotNull('lat')->whereNotNull('lng')
            ->when($brokerCitySlug, fn ($q) => $q->whereHas('city', fn ($c) => $c->where('slug', $brokerCitySlug)))
            ->with('city:id,name,slug')
            ->get(['id', 'name', 'slug', 'lat', 'lng', 'city_id']);

        // 22 Eylul 2026: kullanicinin talebi - bir il SECILDIGINDE harita
        // sadece anlaşmalı degil, o ildeki KAYITLI (ön kayıt + sahipli +
        // anlaşmalı) TUM kurumlari 3 farkli renk tonunda gostermeli. Il
        // SECILMEMISKEN eski davranis (sadece anlaşmalı, Turkiye geneli)
        // AYNEN korunur - kullanicinin "cok karisiklik olur mu" endisesi
        // icin, ulke genelinde binlerce pin BIRDEN asla gosterilmez, sadece
        // bir il secilince o ile ozel gorunur (bkz. broker-map.blade.php).
        $registeredMapFacilities = collect();
        if ($brokerCitySlug) {
            $registeredMapFacilities = Facility::discoverable()
                ->forBrand($sectionScopes)
                ->whereNotNull('lat')->whereNotNull('lng')
                ->whereHas('city', fn ($c) => $c->where('slug', $brokerCitySlug))
                ->with('city:id,name,slug')
                ->get(['id', 'name', 'slug', 'lat', 'lng', 'city_id', 'is_claimed', 'is_broker_managed']);
        }

        $categories = FacilityCategory::cachedAll()->whereIn('brand_scope', $sectionScopes)->values();
        $cities = City::cachedAll();
        $districtsByCity = turkey_provinces();
        $sectionServices = $activeSection['features'];

        // 12 Agustos 2026: kullanicinin acik talebi - filtre formu
        // doldurulup gonderildiginde ayni sayfada, "Bilgi merkezi/Makale ve
        // SSS" tanitim bolumunun YERINDE gercek filtrelenmis sonuclar
        // gorunmeli (daha once bu form dogrudan /kurumlar sayfasina
        // gidiyordu - artik ayni sayfaya GET ile submit edilip burada
        // yakalaniyor, bkz. home.blade.php form action'i).
        $isFiltering = $this->hasActiveFilters($request);
        $filteredFacilities = null;
        $sectionBreakdown = [];

        if ($isFiltering) {
            // 30 Eylul 2026: kullanicinin talebi - 3 ayri filtrelenmis blok
            // (Önerilen/Öne Çıkan/Diğer) TEK listeye birlesti, ayni paylasilan
            // oncelik sirasiyla (bkz. Facility::scopeOrderByListingPriority).
            $filteredFacilities = $this->filteredQuery($request, $sectionScopes, $brand['category_scope'])
                ->with(['city', 'category', 'images'])->withCount(['approvedReviews', 'googleReviews'])
                ->orderByListingPriority()
                ->paginate(24)
                ->withQueryString();

            // 12 Agustos 2026 (2): isim aramasi tum bolumlerde arandigi
            // icin "kac kurum hangi bolumden" dagilimi (bkz. FiltersFacilities).
            $sectionBreakdown = $this->sectionBreakdown($request, $brand['category_scope']);
        }

        // 12 Agustos 2026: anlik filtreleme - metin kutusunda her tus
        // vurusunda, secimlerde degisince sayfa yenilenmeden sadece sonuc
        // blogunu (filtre sonucu VEYA filtrelenmemis "Bilgi merkezi" hali)
        // dondurur - facilities/index.blade.php'deki ayni mekanizma.
        if ($request->ajax()) {
            // 18 Agustos 2026: bkz. Public\FacilityController::index() ayni
            // tarihli yorum - tarayici GERI tusunda ham JSON gorunmesini
            // onlemek icin bu AJAX yaniti onbelleklenmez.
            return response()->json([
                'html' => view("themes.{$brand['theme']}.home._results", compact(
                    'activeSection',
                    'isFiltering',
                    'filteredFacilities',
                    'facilities',
                    'brokerFacilities',
                    'registeredMapFacilities',
                    'sectionBreakdown'
                ))->render(),
            ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        }

        return view("themes.{$brand['theme']}.home", compact(
            'facilities',
            'brokerFacilities',
            'registeredMapFacilities',
            'categories',
            'cities',
            'sections',
            'activeSection',
            'districtsByCity',
            'sectionServices',
            'isFiltering',
            'filteredFacilities',
            'facilityCount',
            'cityCount',
            'claimedCount',
            'sectionBreakdown'
        ));
    }
}
