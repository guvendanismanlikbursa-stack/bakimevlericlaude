<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FamilyUser;
use App\Models\SiteVisit;
use Illuminate\Http\Request;

// "Sitelere giris sayilari" ve "kayit olan ailelerin konumlari" - tek ekran.
class SiteStatsController extends Controller
{
    public function index(Request $request)
    {
        $brands = config('brands.brands');
        $today = now()->toDateString();
        $last7 = now()->subDays(6)->toDateString();
        $last30 = now()->subDays(29)->toDateString();

        $visitStats = [];
        foreach ($brands as $slug => $brand) {
            $visitStats[$slug] = [
                'name' => $brand['name'],
                'today' => (int) SiteVisit::where('brand', $slug)->where('visit_date', $today)->value('count'),
                'last_7_days' => (int) SiteVisit::where('brand', $slug)->where('visit_date', '>=', $last7)->sum('count'),
                'last_30_days' => (int) SiteVisit::where('brand', $slug)->where('visit_date', '>=', $last30)->sum('count'),
                'all_time' => (int) SiteVisit::where('brand', $slug)->sum('count'),
            ];
        }

        $dailySeries = SiteVisit::where('visit_date', '>=', $last30)
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

        $cityCounts = (clone $familiesQuery)
            ->whereNotNull('signup_city_name')
            ->selectRaw('signup_city_name, count(*) as total')
            ->groupBy('signup_city_name')
            ->orderByDesc('total')
            ->get();

        $totalFamilies = (clone $familiesQuery)->count();
        $withLocation = (clone $familiesQuery)->whereNotNull('signup_lat')->count();
        $verifiedCount = (clone $familiesQuery)->whereNotNull('email_verified_at')->count();

        return view('admin.site-stats.index', compact(
            'brands', 'visitStats', 'dailySeries', 'families', 'cityCounts', 'totalFamilies', 'withLocation', 'verifiedCount'
        ));
    }

    public function showFamily(FamilyUser $familyUser)
    {
        $familyUser->loadMissing(['offerRequests' => fn ($q) => $q->latest()->with('facility')]);

        return view('admin.site-stats.family-show', ['family' => $familyUser]);
    }
}
