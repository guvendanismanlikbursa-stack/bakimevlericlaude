<?php

namespace App\Http\Controllers\Facility;

use App\Http\Controllers\Controller;
use App\Models\FacilityDailyStat;
use App\Models\FacilityUser;
use App\Models\OfferRequest;
use App\Models\Quote;

class DashboardController extends Controller
{
    public function index()
    {
        $brand = current_brand();
        $user = FacilityUser::with('facility.category', 'facility.city')->findOrFail(session('facility_user_id'));
        $facility = $user->facility;
        $facilityInBrandScope = $facility->isInBrandScope($brand['category_scope']);

        $alreadyQuotedIds = $facility->quotes()
            ->whereHas('offerRequest', fn ($q) => $q->where('brand', $brand['slug']))
            ->pluck('offer_request_id');

        $directRequests = OfferRequest::where('brand', $brand['slug'])
            ->where('facility_id', $facility->id)
            ->with(['familyUser', 'city', 'category', 'quotes' => fn ($q) => $q->where('facility_id', $facility->id), 'messages'])
            ->latest()
            ->get();

        $broadcastLeads = collect();

        if ($facilityInBrandScope) {
            $broadcastLeads = OfferRequest::where('brand', $brand['slug'])
                ->whereNull('facility_id')
                ->where('city_id', $facility->city_id)
                ->where('facility_category_id', $facility->facility_category_id)
                ->whereNotIn('id', $alreadyQuotedIds)
                ->with(['familyUser', 'city', 'category', 'quotes', 'messages'])
                ->latest()
                ->get();
        }

        $sentQuotes = $facility->quotes()
            ->whereHas('offerRequest', fn ($q) => $q->where('brand', $brand['slug']))
            ->with('offerRequest.familyUser', 'offerRequest.city', 'offerRequest.category')
            ->latest()
            ->get();

        $stats = [
            'direct_requests' => $directRequests->count(),
            'broadcast_leads' => $broadcastLeads->count(),
            'sent_quotes' => $sentQuotes->count(),
            'accepted_quotes' => $sentQuotes->where('status', 'accepted')->count(),
            'pending_quotes' => $sentQuotes->where('status', 'pending')->count(),
            'message_threads' => $sentQuotes->pluck('offer_request_id')->unique()->count(),
        ];

        $performance = $facility->performanceSummary();
        $trend = $this->performanceTrend($facility);

        return view("themes.{$brand['theme']}.facility.dashboard", compact(
            'user',
            'facility',
            'directRequests',
            'broadcastLeads',
            'sentQuotes',
            'facilityInBrandScope',
            'stats',
            'performance',
            'trend'
        ));
    }

    /**
     * 12 Agustos 2026: kullanicinin talebi - "gecen aya gore nasilim
     * goremiyorum, para harcayip sonucunu goremiyorum". Son 30 gunun
     * gunluk anlik goruntusunden (bkz. SnapshotFacilityDailyStats) basit
     * bir trend + bu ay kabul edilen tekliflerin GERCEK toplam degerini
     * (Quote.price - kurumun kendi verdigi fiyat) hesaplar.
     */
    private function performanceTrend($facility): array
    {
        $daily = FacilityDailyStat::where('facility_id', $facility->id)
            ->where('date', '>=', now()->subDays(30)->toDateString())
            ->orderBy('date')
            ->get();

        $thisWeek = $daily->where('date', '>=', now()->subDays(7)->toDateString());
        $lastWeek = $daily->whereBetween('date', [now()->subDays(14)->toDateString(), now()->subDays(8)->toDateString()]);

        $viewsThisWeek = (int) ($thisWeek->last()?->views_count - $thisWeek->first()?->views_count ?? 0);
        $viewsLastWeek = (int) ($lastWeek->last()?->views_count - $lastWeek->first()?->views_count ?? 0);

        $leadValueThisMonth = Quote::where('facility_id', $facility->id)
            ->where('status', 'accepted')
            ->where('updated_at', '>=', now()->startOfMonth())
            ->sum('price');

        $dailyDeltas = [];
        $previousViews = null;
        foreach ($daily as $row) {
            $dailyDeltas[] = [
                'date' => $row->date->format('d.m'),
                'views_delta' => $previousViews === null ? 0 : max(0, $row->views_count - $previousViews),
            ];
            $previousViews = $row->views_count;
        }

        return [
            'daily_deltas' => array_slice($dailyDeltas, -14),
            'views_this_week' => max(0, $viewsThisWeek),
            'views_last_week' => max(0, $viewsLastWeek),
            'offer_requests_this_week' => $thisWeek->sum('offer_requests_count'),
            'offer_requests_last_week' => $lastWeek->sum('offer_requests_count'),
            'lead_value_this_month' => (float) $leadValueThisMonth,
            'has_data' => $daily->isNotEmpty(),
        ];
    }
}