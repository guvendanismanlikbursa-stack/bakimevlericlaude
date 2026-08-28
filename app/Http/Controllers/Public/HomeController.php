<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Public\Concerns\FiltersFacilities;
use App\Models\City;
use App\Models\Facility;
use App\Models\FacilityCategory;
use Illuminate\Http\Request;

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
        // sayisi arttikca liste uzayip gitmemeli, 6'sar 6'sar sayfalanmali
        // (2., 3. sayfa...). Ayri bir 'featured_page' parametresi
        // kullaniliyor ki asagidaki $filteredFacilities->paginate()
        // (varsayilan 'page' parametresi) ile cakismasin.
        $featured = Facility::discoverable()
            ->forBrand($sectionScopes)
            ->where('is_featured', true)
            ->with(['city', 'category', 'images'])
            ->orderByDesc('rating')
            ->orderByDesc('id')
            ->paginate(6, ['*'], 'featured_page')
            ->withQueryString();

        // 28 Agustos 2026: kullanicinin talebi - "sahiplenilmis kurumlar"
        // (is_claimed=true ama Öne Çıkanlar'da zaten gosterilenler haric)
        // Öne Çıkanlar ile Ön Kayıtlı Kurumlar arasinda ayri bir bolum.
        $claimedFacilities = Facility::discoverable()
            ->forBrand($sectionScopes)
            ->where('is_claimed', true)
            ->where('is_featured', false)
            ->with(['city', 'category', 'images'])
            ->latest('claimed_at')
            ->limit(6)
            ->get();

        // 28 Agustos 2026: kullanicinin talebi - Öne Çıkanlar, Sahiplenilmiş
        // Kurumlar ve Ön Kayıtlı Kurumlar birbirinin YERINE GECMEZ - hangisinin
        // kendi verisi varsa o gorunur, digerlerinin doluluk durumundan
        // BAGIMSIZ (once denenen "sadece digerleri boşsa goster" yedek
        // mantigi kaldirildi).
        $preRegistered = Facility::discoverable()
            ->forBrand($sectionScopes)
            ->where('is_claimed', false)
            ->where('source', 'google_maps_veri_cekici')
            ->with(['city', 'category', 'images'])
            ->latest()
            ->limit(12)
            ->get();

        $categories = FacilityCategory::whereIn('brand_scope', $sectionScopes)->orderBy('name')->get();
        $cities = City::orderBy('name')->get();
        $districtsByCity = turkey_provinces();
        $sectionServices = $activeSection['features'];

        // 12 Agustos 2026: kullanicinin talebi - anasayfa "maksimum premium"
        // seviyeye tasinirken guven veren gercek, canli rakamlar eklendi
        // (Hakkimizda sayfasindaki ayni yaklasim).
        $facilityCount = Facility::discoverable()->forBrand($brand['category_scope'])->count();
        $cityCount = Facility::discoverable()->forBrand($brand['category_scope'])
            ->join('cities', 'cities.id', '=', 'facilities.city_id')
            ->distinct('cities.id')->count('cities.id');
        $claimedCount = Facility::discoverable()->forBrand($brand['category_scope'])->where('is_claimed', true)->count();

        // 12 Agustos 2026: kullanicinin acik talebi - filtre formu
        // doldurulup gonderildiginde ayni sayfada, "Bilgi merkezi/Makale ve
        // SSS" tanitim bolumunun YERINDE gercek filtrelenmis sonuclar
        // gorunmeli (daha once bu form dogrudan /kurumlar sayfasina
        // gidiyordu - artik ayni sayfaya GET ile submit edilip burada
        // yakalaniyor, bkz. home.blade.php form action'i).
        $isFiltering = $this->hasActiveFilters($request);
        $filteredFacilities = null;
        $filteredFeatured = null;
        $sectionBreakdown = [];

        if ($isFiltering) {
            $baseQuery = $this->filteredQuery($request, $sectionScopes, $brand['category_scope'])->with(['city', 'category', 'images']);

            $filteredFeatured = (clone $baseQuery)->where('is_featured', true)->limit(3)->get();

            $filteredFacilities = $baseQuery
                ->when($filteredFeatured->isNotEmpty(), fn ($q) => $q->whereNotIn('id', $filteredFeatured->pluck('id')))
                ->orderByDesc('rating')
                ->paginate(21)
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
                    'filteredFeatured',
                    'featured',
                    'claimedFacilities',
                    'preRegistered',
                    'sectionBreakdown'
                ))->render(),
            ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        }

        return view("themes.{$brand['theme']}.home", compact(
            'featured',
            'claimedFacilities',
            'preRegistered',
            'categories',
            'cities',
            'sections',
            'activeSection',
            'districtsByCity',
            'sectionServices',
            'isFiltering',
            'filteredFacilities',
            'filteredFeatured',
            'facilityCount',
            'cityCount',
            'claimedCount',
            'sectionBreakdown'
        ));
    }
}
