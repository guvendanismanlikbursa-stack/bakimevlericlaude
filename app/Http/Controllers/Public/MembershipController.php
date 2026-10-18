<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Facility;
use App\Models\Setting;
use App\Models\SubscriptionPackage;

// 18 Ekim 2026: kullanıcının talebi - "İyilik Hareketi" üyelik programı.
// İki sayfa:
//  - /iyilik-hareketi: herkese açık bilgilendirme (fiyat YOK).
//  - /kurum-teklifi/{token}: kuruma özel, tahmin edilemeyen linkli teklif
//    sayfası (fiyatlar + kurumun GERÇEK istatistikleri; artırılmış
//    görüntülenme sayıları bilerek kullanılmaz). Google'a her zaman kapalı.
// Program yayına alınana kadar (membership_live()) ikisi de yalnızca admin
// oturumunda açılır (önizleme), diğer herkese 404.
class MembershipController extends Controller
{
    public function show(\Illuminate\Http\Request $request)
    {
        // 18 Ekim 2026: kullanıcının bildirdiği gerçek sorun - önizleme
        // yalnızca admin oturumu olan AYNI tarayıcıda açılıyordu; admin'e
        // telefondaki uygulamadan girildiğinde link başka tarayıcıda 404
        // veriyordu. Admin panelinden üretilen süreli imzalı link
        // (?expires=&signature=) her tarayıcıda önizleme açar.
        abort_unless(membership_viewable() || $request->hasValidSignature(), 404);

        $brand = current_brand();
        $packages = SubscriptionPackage::active()->membership()->orderBy('sort_order')->orderBy('price')->get();

        return view('themes._shared.membership', [
            'brand' => $brand,
            'packages' => $packages,
            'impact' => $this->impact(),
            'isPreview' => ! membership_live(),
            'memberCount' => Facility::where('is_subscribed', true)->whereNotNull('subscription_package_id')
                ->whereDate('subscription_paid_until', '>=', now()->toDateString())->count(),
        ]);
    }

    public function offer(\Illuminate\Http\Request $request)
    {
        // Rota hem düz hem /site/{brand} önekli kayıtlı - parametre konumla
        // değil adıyla okunur (bkz. Facility\SubscriptionController::store()).
        $token = (string) $request->route('token');
        $signedPreview = $request->hasValidSignature();
        abort_unless(membership_viewable() || $signedPreview, 404);
        abort_if(strlen($token) < 32, 404);

        $facility = Facility::with(['city', 'category', 'subscriptionPackage'])->where('offer_token', $token)->firstOrFail();

        // Admin önizlemesi "açıldı" sayılmaz - admin ekranındaki "linki açtı mı"
        // bilgisi yalnızca kurumun gerçek açılışlarını gösterir.
        if (! session('admin_id') && ! $signedPreview) {
            $facility->forceFill([
                'offer_link_opened_at' => now(),
                'offer_link_open_count' => (int) $facility->offer_link_open_count + 1,
            ])->saveQuietly();
        }

        $since = now()->subDays(30);
        $events = $facility->engagementEvents()->where('created_at', '>=', $since)
            ->selectRaw('type, count(*) as c')->groupBy('type')->pluck('c', 'type');

        $stats = [
            'real_views' => (int) ($events['real_view'] ?? 0),
            'contact_clicks' => (int) ($events['phone_click'] ?? 0) + (int) ($events['whatsapp_click'] ?? 0),
            'price_requests' => \App\Models\FacilityInterestLead::where('facility_id', $facility->id)->where('created_at', '>=', $since)->count()
                + $facility->offerRequests()->where('created_at', '>=', $since)->count(),
        ];

        $packages = SubscriptionPackage::active()->membership()->orderBy('sort_order')->orderBy('price')->get()
            ->filter(fn ($p) => $p->isAvailableForFacility($facility))->values();

        return view('themes._shared.membership-offer', [
            'brand' => current_brand(),
            'facility' => $facility,
            'packages' => $packages,
            'stats' => $stats,
            'impact' => $this->impact(),
            'isBursa' => ($facility->city->slug ?? null) === 'bursa',
            'hasPanelAccount' => $facility->facilityUsers()->exists(),
            'isPreview' => ! membership_live(),
        ]);
    }

    /**
     * Şeffaflık sayacı - admin panelinden girilir; boşsa sayfada gösterilmez.
     */
    private function impact(): ?array
    {
        $patients = Setting::get('membership_impact_patients');
        $hours = Setting::get('membership_impact_hours');
        $office = Setting::get('membership_office_image');

        return [
            'patients' => $patients !== null && $patients !== '' ? (int) $patients : null,
            'hours' => $hours !== null && $hours !== '' ? (int) $hours : null,
            'period' => Setting::get('membership_impact_period') ?: 'Bu ay',
            'office_image_url' => $office ? facility_asset($office) : null,
        ];
    }
}
