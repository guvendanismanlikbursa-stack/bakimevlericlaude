<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\RedirectsOutOfRangePagination;
use App\Http\Controllers\Controller;
use App\Models\FamilyUser;
use App\Models\SiteVisit;
use Illuminate\Http\Request;

// "Sitelere giris sayilari" ve "kayit olan ailelerin konumlari" - tek ekran.
class SiteStatsController extends Controller
{
    use RedirectsOutOfRangePagination;

    public function index(Request $request)
    {
        $brands = config('brands.brands');
        $today = now()->toDateString();
        $last7 = now()->subDays(6)->toDateString();
        $last30 = now()->subDays(29)->toDateString();
        $last90 = now()->subDays(89)->toDateString();
        // 26 Ekim 2026: kullanıcının talebi - 3 ay / 6 ay / 1 yıl dönemleri de.
        $last180 = now()->subDays(179)->toDateString();
        $last365 = now()->subDays(364)->toDateString();

        $visitStats = [];
        foreach ($brands as $slug => $brand) {
            $visitStats[$slug] = [
                'name' => $brand['name'],
                'today' => (int) SiteVisit::where('brand', $slug)->where('visit_date', $today)->value('count'),
                'last_7_days' => (int) SiteVisit::where('brand', $slug)->where('visit_date', '>=', $last7)->sum('count'),
                'last_30_days' => (int) SiteVisit::where('brand', $slug)->where('visit_date', '>=', $last30)->sum('count'),
                'last_90_days' => (int) SiteVisit::where('brand', $slug)->where('visit_date', '>=', $last90)->sum('count'),
                'last_180_days' => (int) SiteVisit::where('brand', $slug)->where('visit_date', '>=', $last180)->sum('count'),
                'last_365_days' => (int) SiteVisit::where('brand', $slug)->where('visit_date', '>=', $last365)->sum('count'),
                'all_time' => (int) SiteVisit::where('brand', $slug)->sum('count'),
            ];
        }

        // 5 Ekim 2026: kullanicinin talebi - trend grafiginde 90 gunluk secenek
        // de olsun, bu yuzden sorgu artik 90 gune cikti (30/7/1 gorunumler
        // JS tarafinda bu ayni veriden dilimlenir, ekstra sorgu gerekmez).
        $dailySeries = SiteVisit::where('visit_date', '>=', $last365)
            ->orderBy('visit_date')
            ->get()
            ->groupBy(fn ($row) => $row->visit_date->toDateString());

        $familiesQuery = FamilyUser::query();
        if ($request->filled('brand')) {
            $familiesQuery->where('registered_brand', $request->brand);
        }
        if ($request->filled('has_location')) {
            $request->has_location === '1'
                ? $familiesQuery->whereNotNull('signup_lat')
                : $familiesQuery->whereNull('signup_lat');
        }

        $families = (clone $familiesQuery)->latest()->paginate(25)->withQueryString();

        if ($redirect = $this->redirectIfPageOutOfRange($request, $families)) {
            return $redirect;
        }

        $cityCounts = (clone $familiesQuery)
            ->whereNotNull('signup_city_name')
            ->selectRaw('signup_city_name, count(*) as total')
            ->groupBy('signup_city_name')
            ->orderByDesc('total')
            ->get();

        $totalFamilies = (clone $familiesQuery)->count();
        $withLocation = (clone $familiesQuery)->whereNotNull('signup_lat')->count();
        $verifiedCount = (clone $familiesQuery)->whereNotNull('email_verified_at')->count();

        // 26 Ekim 2026: ziyaretçi türleri (sunucu erişim kayıtlarından günlük özet - bkz. TrafikOzet).
        $donemler = ['1' => 'Bugün', 'dun' => 'Dün', '7' => 'Son 7 gün', '30' => 'Son 30 gün', '90' => 'Son 3 ay', '180' => 'Son 6 ay', '365' => 'Son 1 yıl'];
        $donem = array_key_exists((string) $request->query('donem'), $donemler) ? (string) $request->query('donem') : '7';
        [$donemBas, $donemSon] = $donem === 'dun'
            ? [now()->subDay()->toDateString(), now()->subDay()->toDateString()]
            : [now()->subDays((int) $donem - 1)->toDateString(), $today];
        $trafikSatir = \Illuminate\Support\Facades\Schema::hasTable('trafik_gunluk')
            ? \Illuminate\Support\Facades\DB::table('trafik_gunluk')->whereBetween('tarih', [$donemBas, $donemSon])
                ->selectRaw('marka, tur, sum(istek) as istek, sum(ip) as ip')->groupBy('marka', 'tur')->get()
            : collect();
        $trafik = [];
        foreach ($trafikSatir as $r) {
            $trafik[$r->marka][$r->tur] = ['istek' => (int) $r->istek, 'ip' => (int) $r->ip];
        }
        $trafikBaslangic = \Illuminate\Support\Facades\Schema::hasTable('trafik_gunluk') ? \Illuminate\Support\Facades\DB::table('trafik_gunluk')->min('tarih') : null;
        $trafikGunluk = \Illuminate\Support\Facades\Schema::hasTable('trafik_gunluk')
            ? \Illuminate\Support\Facades\DB::table('trafik_gunluk')->whereBetween('tarih', [$donemBas, $donemSon])
                ->selectRaw('tarih, tur, sum(istek) as istek')->groupBy('tarih', 'tur')->orderBy('tarih')->get()->groupBy('tarih')
            : collect();

        return view('admin.site-stats.index', compact(
            'brands', 'visitStats', 'dailySeries', 'families', 'cityCounts', 'totalFamilies', 'withLocation', 'verifiedCount',
            'donemler', 'donem', 'trafik', 'trafikBaslangic', 'trafikGunluk', 'donemBas', 'donemSon'
        ));
    }

    public function showFamily(FamilyUser $familyUser)
    {
        $familyUser->loadMissing(['offerRequests' => fn ($q) => $q->latest()->with('facility')]);

        return view('admin.site-stats.family-show', ['family' => $familyUser]);
    }
}
