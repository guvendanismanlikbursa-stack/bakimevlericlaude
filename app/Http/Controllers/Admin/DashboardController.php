<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccountDeletionRequest;
use App\Models\CategoryViewSnapshot;
use App\Models\ContactMessage;
use App\Models\Facility;
use App\Models\FacilityClaim;
use App\Models\FacilityRegistration;
use App\Models\OfferRequest;
use App\Models\PlatformError;
use App\Models\SiteVisit;
use App\Models\VisitServiceRequest;
use App\Models\WalletTopup;
use Illuminate\Support\Facades\Config;

class DashboardController extends Controller
{
    public function index()
    {
        // Ortak admin panel: 3 markanin da ozetini ve bekleyen onaylari tek ekranda gosterir
        $brands = Config::get('brands.brands');

        $stats = [];
        foreach ($brands as $slug => $brand) {
            $stats[$slug] = [
                'name' => $brand['name'],
                'facilities' => Facility::forBrand($brand['category_scope'])->count(),
                'published' => Facility::forBrand($brand['category_scope'])->published()->count(),
                'claimed' => Facility::forBrand($brand['category_scope'])->claimed()->count(),
                'offer_requests' => OfferRequest::where('brand', $slug)->count(),
                'new_offer_requests' => OfferRequest::where('brand', $slug)->where('status', 'new')->count(),
                'contact_messages' => ContactMessage::where('brand', $slug)->count(),
            ];
        }

        $pendingClaims = FacilityClaim::where('status', 'pending')->count();
        $pendingTopups = WalletTopup::where('status', 'pending')->count();
        $pendingTopupsAmount = (float) WalletTopup::where('status', 'pending')->sum('amount');
        $pendingRegistrations = FacilityRegistration::where('status', 'pending')->count();
        // 27 Agustos 2026: kullanicinin bildirdigi gercek eksiklik - "Yakınımı
        // Ziyaret Et" talebi gelince admin'e mail/push gidiyordu ama panele
        // girince diger bekleyen islemler (sahiplenme/kayit/bakiye) gibi
        // GORUNMUYORDU, admin fark etmiyordu. Ayni "bekleyen islemler" kutusuna eklendi.
        $newVisitServiceRequests = VisitServiceRequest::where('status', 'yeni')->count();

        // 4 Eylul 2026: kullanicinin talebi - VisitServiceRequest icin 27
        // Agustos'ta yapilan duzeltmeyle AYNI kalip: bu iki talep turu de
        // veride zaten "bekliyor" olarak tutuluyordu ama "bekleyen islemler"
        // kutusuna hic yansimiyordu, admin ayri sayfaya girmeden fark edemiyordu.
        $unreadContactMessages = ContactMessage::where('is_read', false)->count();
        $pendingAccountDeletions = AccountDeletionRequest::where('status', 'pending')->count();

        $latestOffers = OfferRequest::with('facility')->latest()->limit(8)->get();
        $latestClaims = FacilityClaim::with('facility')->where('status', 'pending')->latest()->limit(5)->get();

        $health = $this->healthSummary();
        $categoryDemand = $this->categoryDemandSummary();

        return view('admin.dashboard', compact(
            'stats', 'latestOffers', 'pendingClaims', 'pendingTopups',
            'pendingTopupsAmount', 'pendingRegistrations', 'newVisitServiceRequests', 'latestClaims', 'health',
            'categoryDemand', 'unreadContactMessages', 'pendingAccountDeletions'
        ));
    }

    /**
     * 29 Agustos 2026: kullanicinin talebi - "kullanicilar hangi kurum
     * turunu en cok ariyor" sorusuna panelde dogrudan cevap: kurum
     * turune (bolume) gore GERCEK goruntulenme dagilimi ve yuzdesi.
     * Harici tahmin degil, platformun kendi verisi.
     *
     * 6 Eylul 2026: kullanicinin bildirdigi gercek gozlem - "yuzdeler hic
     * degismiyor". Sebep: views_count TUM ZAMANLARIN toplami, zaten cok
     * buyumus bu sayiya bir gunluk artis yuzdeyi neredeyse hic kipirdatmiyor.
     * Artik CategoryViewSnapshot (bkz. SnapshotCategoryViews komutu ayni
     * tarihli yorum) kullanilarak SON 30 GUNDE GERCEKTEN KAZANILAN
     * goruntulenme farki hesaplanir - bu, guncel "hangi bolume ilgi artti"
     * sorusuna gercekten cevap verir. Henuz 30 gunluk gecmis birikmediyse
     * (ozellik yeni eklendi), durustce "veri birikiyor" durumuna duser.
     */
    private function categoryDemandSummary(): array
    {
        $currentRows = Facility::query()
            ->join('facility_categories', 'facility_categories.id', '=', 'facilities.facility_category_id')
            ->whereNull('facilities.deleted_at')
            ->where('facilities.is_published', true)
            ->selectRaw('facility_categories.brand_scope, sum(facilities.views_count) as toplam')
            ->groupBy('facility_categories.brand_scope')
            ->pluck('toplam', 'brand_scope');

        $cutoff = now()->subDays(30)->toDateString();
        $oldestSnapshotDate = CategoryViewSnapshot::min('date');
        $hasTrendData = $oldestSnapshotDate !== null && $oldestSnapshotDate <= $cutoff;

        $baselineRows = collect();
        if ($hasTrendData) {
            // Her bolum icin 30 gun ONCEYE en yakin (o tarihten once/esit,
            // en yeni) anlik goruntuyu bulur - komut her gece calismasa/
            // atlasa bile en yakin gecerli degeri kullanir.
            $baselineRows = CategoryViewSnapshot::where('date', '<=', $cutoff)
                ->selectRaw('brand_scope, total_views')
                ->whereIn('id', function ($q) use ($cutoff) {
                    $q->selectRaw('max(id)')
                        ->from('category_view_snapshots')
                        ->where('date', '<=', $cutoff)
                        ->groupBy('brand_scope');
                })
                ->pluck('total_views', 'brand_scope');
        }

        $bySection = [];
        foreach ($currentRows as $scope => $count) {
            $title = service_section_for_scope($scope)['title'] ?? $scope;
            $value = $hasTrendData
                ? max(0, (int) $count - (int) ($baselineRows[$scope] ?? 0))
                : (int) $count;
            $bySection[$title] = ($bySection[$title] ?? 0) + $value;
        }
        arsort($bySection);

        $total = array_sum($bySection) ?: 1;
        $daysSinceFirstSnapshot = $oldestSnapshotDate ? (int) \Illuminate\Support\Carbon::parse($oldestSnapshotDate)->diffInDays(now()) : 0;
        $daysUntilTrend = $hasTrendData ? 0 : max(0, 30 - $daysSinceFirstSnapshot);

        return [
            'has_trend_data' => $hasTrendData,
            'days_until_trend' => max(0, $daysUntilTrend),
            'rows' => collect($bySection)->map(fn ($count, $title) => [
                'title' => $title,
                'count' => $count,
                'percent' => round($count / $total * 100, 1),
            ])->values()->all(),
        ];
    }

    /**
     * 14 Agustos 2026: kullanicinin talebi - "isletme sagligi ozeti", admin
     * her seferinde birkac ayri sayfaya girmeden GUNLUK trendi (ziyaret,
     * yeni basvuru, yeni talep, yeni hata) tek ekranda gorsun.
     */
    private function healthSummary(): array
    {
        $since = now()->subDays(13)->startOfDay();
        $dates = collect(range(0, 13))->map(fn ($i) => now()->subDays(13 - $i)->toDateString());

        $visitsByDate = SiteVisit::where('visit_date', '>=', $since->toDateString())
            ->selectRaw('visit_date, sum(count) as total')
            ->groupBy('visit_date')->pluck('total', 'visit_date');

        $claimsByDate = FacilityClaim::where('created_at', '>=', $since)
            ->selectRaw('DATE(created_at) as d, count(*) as total')
            ->groupBy('d')->pluck('total', 'd');

        $offersByDate = OfferRequest::where('created_at', '>=', $since)
            ->selectRaw('DATE(created_at) as d, count(*) as total')
            ->groupBy('d')->pluck('total', 'd');

        $errorsByDate = PlatformError::where('created_at', '>=', $since)
            ->selectRaw('DATE(created_at) as d, count(*) as total')
            ->groupBy('d')->pluck('total', 'd');

        $series = fn ($byDate) => $dates->map(fn ($d) => (int) ($byDate[$d] ?? 0))->all();

        $visits = $series($visitsByDate);
        $claims = $series($claimsByDate);
        $offers = $series($offersByDate);
        $errors = $series($errorsByDate);

        $weekSum = fn (array $vals, bool $last) => array_sum($last ? array_slice($vals, -7) : array_slice($vals, 0, 7));

        return [
            'dates' => $dates->map(fn ($d) => \Illuminate\Support\Carbon::parse($d)->format('d.m'))->all(),
            'visits' => ['daily' => $visits, 'this_week' => $weekSum($visits, true), 'last_week' => $weekSum($visits, false)],
            'claims' => ['daily' => $claims, 'this_week' => $weekSum($claims, true), 'last_week' => $weekSum($claims, false)],
            'offers' => ['daily' => $offers, 'this_week' => $weekSum($offers, true), 'last_week' => $weekSum($offers, false)],
            'errors' => ['daily' => $errors, 'this_week' => $weekSum($errors, true), 'last_week' => $weekSum($errors, false)],
            'unresolved_errors' => PlatformError::whereNull('resolved_at')->count(),
        ];
    }
}
