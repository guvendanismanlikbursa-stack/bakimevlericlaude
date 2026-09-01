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

        // 1 Eylul 2026: kullanicinin bildirdigi gercek hata - kurumlar 3
        // markada da AYNI envanteri paylasiyor (bkz. config/brands.php,
        // category_scope UCU DE BIREBIR AYNI - bu kontrolun zaten hicbir
        // ayirt edici etkisi yok), yani bir aile bu kurumu HANGI siteden
        // (bakimevleri/bakimevibul/bakimeviara) bulup teklif isterse istesin
        // AYNI kuruma ulasiyor. Ama bu sorgular 'brand' = su an goruntulenen
        // site'ye gore filtrelendigi icin, kurum yetkilisi HANGI siteden
        // giris yaptiysa SADECE o sitedeki talepleri goruyordu - diger 2
        // siteden gelen GERCEK teklif talepleri panelde HIC gorunmuyordu
        // (bkz. ayni tarihli MessageController/QuoteController duzeltmesi -
        // orada erisim tamamen 403 ile engelleniyordu). Artik kurumun TUM
        // markalardaki talepleri tek panelde birlesik gorunur.
        $alreadyQuotedIds = $facility->quotes()->pluck('offer_request_id');

        $directRequests = OfferRequest::where('facility_id', $facility->id)
            ->with(['familyUser', 'city', 'category', 'quotes' => fn ($q) => $q->where('facility_id', $facility->id), 'messages'])
            ->latest()
            ->get();

        $broadcastLeads = collect();

        if ($facilityInBrandScope) {
            $broadcastLeads = OfferRequest::whereNull('facility_id')
                ->where('city_id', $facility->city_id)
                ->where('facility_category_id', $facility->facility_category_id)
                ->whereNotIn('id', $alreadyQuotedIds)
                ->with(['familyUser', 'city', 'category', 'quotes', 'messages'])
                ->latest()
                ->get();
        }

        $sentQuotes = $facility->quotes()
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

        // 13 Agustos 2026: kullanicinin talebi - "hangi gorselim daha cok
        // ilgi cekiyor goremiyorum" - bkz. Public\FacilityImageController,
        // themes._shared.partials.image-lightbox'taki sayac artirma.
        $topImages = $facility->images()->where('views_count', '>', 0)->orderByDesc('views_count')->limit(3)->get();

        return view("themes.{$brand['theme']}.facility.dashboard", compact(
            'user',
            'facility',
            'directRequests',
            'broadcastLeads',
            'sentQuotes',
            'facilityInBrandScope',
            'stats',
            'performance',
            'trend',
            'topImages'
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