<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Public\Concerns\FiltersFacilities;
use App\Models\City;
use App\Models\Facility;
use App\Models\FacilityCategory;
use App\Models\SearchQuery;
use App\Services\GeoLookupService;
use Illuminate\Http\Request;

class FacilityController extends Controller
{
    use FiltersFacilities;

    public function index(Request $request, GeoLookupService $geo)
    {
        $brand = current_brand();
        $sections = service_sections();
        $activeSection = $request->query('bolum') ? active_service_section($request->query('bolum'), $brand) : null;
        $scope = $activeSection ? $activeSection['scopes'] : $brand['category_scope'];

        $cities = City::orderBy('name')->get();
        $categories = FacilityCategory::whereIn('brand_scope', $scope)->orderBy('name')->get();
        $districtsByCity = turkey_provinces();
        $sectionServices = $activeSection
            ? $activeSection['features']
            : collect($sections)->flatMap(fn ($section) => $section['features'])->unique()->values()->all();

        // Bolum secilmeden bu sayfaya gelinirse (ana nav'daki "Kurumları Bul"
        // linki gibi) tum kurumlari tek listede gostermek istemiyoruz;
        // kullanici once bir bolum secmeli. Agir sorguyu calistirmadan
        // erken donuyoruz, view sadece bolum secim ekranini render eder.
        if (! $activeSection) {
            return view("themes.{$brand['theme']}.facilities.index", compact(
                'sections',
                'activeSection',
                'cities',
                'categories',
                'districtsByCity',
                'sectionServices'
            ));
        }

        $query = $this->filteredQuery($request, $scope, $brand['category_scope'])->with(['city', 'category', 'images']);

        // 12 Agustos 2026: kullanicinin talebi - her sayfada tutarli 21
        // kurum (3 sutunlu gride tam sigan bir sayi), fazlasi icin normal
        // sayfalama linkleri.
        $perPage = 21;
        $facilities = $query->orderByDesc('is_featured')->orderByDesc('rating')->paginate($perPage)->withQueryString();

        // 14 Agustos 2026: menzil disi bir sayfaya gidilirse (eski bir
        // yer imi/paylasilan link, filtre degisiminden sonra sayfa sayisi
        // azalmis olabilir) "kriterlere uygun kurum bulunamadi" yanlis
        // mesaji goruniyordu - oysa filtreye uyan kurum GERCEKTEN var,
        // sadece o sayfada degil. Son gecerli sayfaya yonlendiriyoruz.
        if ($facilities->isEmpty() && $facilities->total() > 0 && $facilities->currentPage() > $facilities->lastPage()) {
            return redirect($request->fullUrlWithQuery(['page' => $facilities->lastPage()]));
        }

        // 12 Agustos 2026 (2): isim aramasi (`q`) tum bolumlerde arandigi
        // icin, "kac kurum hangi bolumden" dagilimini de hesaplayip sonuc
        // parcasina gonderiyoruz (bkz. FiltersFacilities::sectionBreakdown).
        $sectionBreakdown = $this->sectionBreakdown($request, $brand['category_scope']);

        // 12 Agustos 2026: anlik filtreleme (kullanici her harf yazdikca/
        // secim degistikce AJAX ile sonuc guncellenmesi) - sayfa yenilenmeden
        // sadece kart+sayfalama parcasini dondurur, bolgesel dagilim gibi
        // agir aggregate sorgulari ve arama loglamasini atlar (her tus
        // basisinda gereksiz yuk bindirmemek icin).
        if ($request->ajax()) {
            return response()->json([
                'count' => $facilities->total(),
                'html' => view('themes._shared.facilities._results', compact('facilities', 'activeSection', 'sectionBreakdown'))->render(),
            ]);
        }

        // "Harita mantiginda bolgesel dagilim" TUM filtrelenmis sonuclar
        // uzerinden hesaplanmali, sadece o anki sayfadaki 9 kayittan degil;
        // bu yuzden aggregate icin filtreyi tekrar (sayfalama olmadan) kurup
        // sehir+ilce bazinda ayri bir count sorgusu calistiriyoruz.
        $regionGroups = $this->filteredQuery($request, $scope, $brand['category_scope'])
            ->join('cities', 'cities.id', '=', 'facilities.city_id')
            ->selectRaw('cities.name as city_name, cities.slug as city_slug, facilities.district, count(*) as total')
            ->groupBy('cities.name', 'cities.slug', 'facilities.district')
            ->orderByDesc('total')
            ->limit(9)
            ->get();

        // "En cok aranan kurumlar/bolgeler" icin: site serbest metin arama
        // kutusu sunmuyor, bu yuzden gercek "arama" burada il/kategori/hizmet
        // filtresi secilmis olmasidir. Bos filtreyle sadece goz atma
        // (pre_registered, sayfalama vb.) loglanmaz.
        if ($request->filled('city') || $request->filled('category') || $request->filled('service')) {
            SearchQuery::record(
                $brand['slug'],
                $request->filled('city') ? $cities->firstWhere('slug', $request->city)?->id : null,
                $request->filled('category') ? $categories->firstWhere('slug', $request->category)?->id : null,
                $request->filled('service') ? $request->service : null,
            );
        }

        $nearbyFacilities = [];
        if ($request->filled('lat') && $request->filled('lng') && is_numeric($request->lat) && is_numeric($request->lng)) {
            $candidates = Facility::discoverable()
                ->forBrand($scope)
                ->whereNotNull('lat')->whereNotNull('lng')
                ->with(['city', 'category', 'images'])
                ->get();

            $nearbyFacilities = $geo->nearestFacilities($candidates, (float) $request->lat, (float) $request->lng);
        }

        return view("themes.{$brand['theme']}.facilities.index", compact(
            'facilities',
            'cities',
            'categories',
            'sections',
            'activeSection',
            'districtsByCity',
            'sectionServices',
            'nearbyFacilities',
            'regionGroups',
            'sectionBreakdown'
        ));
    }

    /**
     * Ana sayfa ve kurum listesindeki filtre formlari, secim yapildikca
     * (Filtrele'ye basmadan) kac kurum eslestigini gostermek icin bu
     * endpoint'i cagirir. Sorgu mantigi index() ile birebir aynidir.
     *
     * 12 Agustos 2026: kullanicinin talebi - sadece sayi degil, filtreye
     * uyan ilk birkac kurumun KENDISI de (kucuk bir onizleme karti seti)
     * anlik gorunmeli, "Kurumları listele"ye basmadan once - bkz.
     * location-filter-script.blade.php'deki JS render.
     */
    public function count(Request $request)
    {
        $brand = current_brand();
        $activeSection = $request->query('bolum') ? active_service_section($request->query('bolum'), $brand) : null;
        $scope = $activeSection ? $activeSection['scopes'] : $brand['category_scope'];

        $query = $this->filteredQuery($request, $scope);
        $count = (clone $query)->count();

        $preview = (clone $query)
            ->with(['city', 'category', 'images'])
            ->orderByDesc('is_featured')
            ->orderByDesc('rating')
            ->limit(6)
            ->get()
            ->map(fn (Facility $facility) => [
                'name' => $facility->name,
                'url' => brand_route('facilities.show', ['slug' => $facility->slug]),
                'image' => facility_card_image($facility, $activeSection),
                'city' => $facility->city->name ?? '',
                'category' => $facility->category->name ?? '',
                'price' => $facility->price_min ? number_format($facility->price_min, 0, ',', '.').' TL' : 'Fiyat iste',
                'rating' => (float) $facility->rating,
                'is_claimed' => (bool) $facility->is_claimed,
            ]);

        return response()->json([
            'count' => $count,
            'preview' => $preview,
        ]);
    }

    public function show(Request $request)
    {
        $brand = current_brand();
        $slug = $request->route('slug');

        $baseQuery = Facility::published()->forBrand($brand['category_scope']);

        $facility = (clone $baseQuery)
            ->where('slug', $slug)
            ->with(['city', 'category', 'images', 'approvedReviews', 'answeredQuestions'])
            ->withAvg('approvedReviews', 'rating')
            ->first();

        if (! $facility) {
            // 17 Agustos 2026: kullanicinin talebi - kurum ismi degisince
            // slug de yenileniyor (bkz. Admin\FacilityController::update()),
            // eski adres Google'da indekslenmis/paylasilmis olabilir. Yeni
            // slug'la bulunamazsa eski slug'a bakip 301 ile dogru adrese
            // yonlendirir - sessizce 404 vermek yerine.
            $redirectTarget = (clone $baseQuery)->where('old_slug', $slug)->first();
            if ($redirectTarget) {
                return redirect(brand_route('facilities.show', ['slug' => $redirectTarget->slug]), 301);
            }

            abort(404);
        }

        // "En cok goruntulenen kurumlar" ve performans sayfasi icin sayac.
        // 17 Agustos 2026: kullanicinin acik talebi - onceden ayni tarayici
        // oturumunda ayni kurum 24 saatte yalnizca bir kez sayiliyordu
        // (dedup); kullanici bunu ISTEMEDIGINI bildirdi ("cok gorunmesi
        // icin her inceleme sayfasina tiklama goruntuleme olarak
        // islenmeli", "google botlari da dahil olmali") - dedup TAMAMEN
        // kaldirildi. Admin oturumu istisnasi da (session('admin_id'))
        // KALDIRILDI - kullanici admin oturumuyla test ederken kendi
        // ziyaretinin sayilmamasini "calismiyor" olarak yasadi; artik
        // KOSULSUZ, istisnasiz her istek sayilir.
        {
            $facility->increment('views_count');
            // GUVENLIK: try/catch ile sarmalandi - bu ikincil/analitik bir
            // yazma islemi, sayfanin asil yuklenmesini engellememeli
            // (canli olayda tam olarak yasandi: bir deploy'un dosya
            // yukleme ile migration adimlari arasindaki birkac saniyelik
            // pencerede gercek bir ziyaretci bu satirda 500 aldi).
            try {
                $facility->engagementEvents()->create(['type' => 'view']);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Goruntulenme olayi kaydedilemedi: '.$e->getMessage(), ['facility_id' => $facility->id]);
            }
        }

        $serviceSection = service_section_for_scope($facility->category?->brand_scope);

        $related = Facility::discoverable()
            ->forBrand($serviceSection['scopes'] ?? $brand['category_scope'])
            ->where('facility_category_id', $facility->facility_category_id)
            ->where('id', '!=', $facility->id)
            ->with(['city', 'category', 'images'])
            ->orderByRaw('CASE WHEN city_id = ? THEN 0 ELSE 1 END', [$facility->city_id])
            ->limit(3)
            ->get();

        // 17 Agustos 2026: kullanicinin "geri gelince hala eski sayi
        // yaziyor" bildirdigi sikayeti icin ek guvenlik - tarayicinin
        // (ozellikle geri/ileri tusu ile) bu sayfayi kendi onbellegi/
        // bfcache'inden GERCEK bir sunucu istegi yapmadan gostermesini
        // engeller, her ziyarette gercekten taze veri gelir.
        return response()
            ->view("themes.{$brand['theme']}.facilities.show", compact('facility', 'related', 'serviceSection'))
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache');
    }
}