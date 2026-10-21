<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Organization;
use Illuminate\Http\Request;

// 7 Ekim 2026: "Organizasyon Şirketleri" - Facility VE Expert'ten tamamen
// bagimsiz, ExpertController ile AYNI desen (il/ilce filtresi, akordiyon-
// liste). Faz 1: SADECE goruntuleme, hicbir yazma/form islemi yok.
class OrganizationController extends Controller
{
    // 7 Ekim 2026: Expert'teki PUBLICLY_VISIBLE ile ayni mantik - icinde
    // gercek kurum olana kadar ziyaretciye GORUNMEYECEK (hicbir menude
    // linklenmiyor, robots noindex). Kullanici onaylayinca true yapilir.
    private const PUBLICLY_VISIBLE = false;

    // 11 Ekim 2026: kullanicinin talebi - ilk tasarimdaki (dogum gunu/kina/
    // dugun/nisan/sunnet/mezuniyet) kategoriler tamamen VARSAYIMDI, gercek
    // veri gelmeden once uydurulmustu. Bursa'dan gelen ilk GERCEK veri
    // taraninca bunlarin hicbiri Google'in kendi kategorileriyle eslesmedi -
    // gercek kategoriler (Google Haritalar'in "Kategori" alani) yerine
    // kondu. "olmayan kategori varsa olustur" talebiyle TUMU gercek veriden.
    // 21 Ekim 2026: kullanıcının kararı - sadece bakım/çocuk/rehabilitasyon
    // bölümleriyle ilgili türler kaldı; düğün odaklı türler, çiçekçi ve
    // etkinlik mekânı çıkarıldı (bkz. OpsController organizationTypePrune).
    private const SERVICE_TYPES = [
        'organizasyon-sirketi' => ['title' => 'Organizasyon Şirketi', 'label' => 'Organizasyon Şirketi'],
        'parti-malzemeleri-magazasi' => ['title' => 'Parti Malzemeleri Mağazası', 'label' => 'Parti Malzemeleri'],
        'fotograf-studyosu' => ['title' => 'Fotoğraf Stüdyosu', 'label' => 'Fotoğraf Stüdyosu'],
        'acik-hava-etkinlik-organizatoru' => ['title' => 'Açık Hava Etkinliği Organizatörü', 'label' => 'Açık Hava Etkinliği'],
        'kostum-kiralama' => ['title' => 'Kostüm Kiralama Hizmeti', 'label' => 'Kostüm Kiralama'],
        'fotografci' => ['title' => 'Fotoğrafçı', 'label' => 'Fotoğrafçı'],
    ];

    public function index(Request $request)
    {
        $cities = City::orderBy('name')->get();
        $districtMap = $cities->mapWithKeys(fn ($city) => [$city->slug => districts_for_city($city->name)]);
        $serviceTypes = self::SERVICE_TYPES;
        $activeOrgSlug = $request->query('firma');
        $activeOrg = $activeOrgSlug
            ? Organization::where('slug', $activeOrgSlug)->where('is_published', true)->with('city')->first()
            : null;
        $publiclyVisible = self::PUBLICLY_VISIBLE;

        $hasFilter = $request->filled('city') || $request->filled('district') || $request->filled('service_type') || $request->filled('search') || $request->filled('min_rating');
        $organizations = null;

        if ($hasFilter) {
            $query = Organization::query()->where('is_published', true)->with('city');

            if ($request->filled('city')) {
                $query->whereHas('city', fn ($c) => $c->where('slug', $request->city));
            }
            if ($request->filled('district')) {
                $query->where('district', $request->district);
            }
            if ($request->filled('service_type') && isset(self::SERVICE_TYPES[$request->service_type])) {
                $query->where(function ($q) use ($request) {
                    $q->where('service_type_label', self::SERVICE_TYPES[$request->service_type]['label'])
                        ->orWhere('service_type', self::SERVICE_TYPES[$request->service_type]['title']);
                });
            }
            if ($request->filled('min_rating')) {
                $query->where('rating', '>=', (float) $request->min_rating);
            }
            if ($request->filled('search')) {
                $term = $request->search;
                $query->where(function ($q) use ($term) {
                    $q->where('name', 'like', '%'.$term.'%')
                        ->orWhere('service_type', 'like', '%'.$term.'%')
                        ->orWhere('service_type_label', 'like', '%'.$term.'%');
                });
            }

            $organizations = $query->orderBy('name')->paginate(24)->withQueryString();
        }

        if ($request->ajax()) {
            return response()->json([
                'count' => $organizations?->total() ?? 0,
                'html' => view('themes._shared.organizations._results', compact('organizations', 'activeOrgSlug'))->render(),
            ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        }

        return view('themes._shared.organizations.index', compact('organizations', 'cities', 'districtMap', 'serviceTypes', 'activeOrgSlug', 'activeOrg', 'publiclyVisible', 'hasFilter'));
    }
}
