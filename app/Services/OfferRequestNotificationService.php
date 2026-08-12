<?php

namespace App\Services;

use App\Models\FacilityUser;
use App\Models\OfferRequest;
use App\Models\Quote;
use Illuminate\Support\Collection;

/**
 * Yeni bir teklif talebi (dogrudan tek kuruma veya sehir/kategoriye yayin)
 * olusunca ilgili kurum(lar)in panelinde bildirim cikmasi icin kullanilir.
 * Talep her zaman kurum panelinde (dashboard sorgusuyla) zaten gorunur; bu
 * servis SADECE push-tarzi bildirimi (bell/rozet) ekler.
 */
class OfferRequestNotificationService
{
    public function notify(OfferRequest $offerRequest): void
    {
        $offerRequest->loadMissing('facility', 'category');

        $title = 'Yeni ücret/teklif talebi';
        $body = $offerRequest->full_name.' bir ücret/teklif talebi gönderdi.';

        $this->recipients($offerRequest)->each(
            fn (FacilityUser $user) => notify_user($user, 'offer_request', $title, $body, [
                'offer_request_id' => $offerRequest->id,
            ])
        );
    }

    /**
     * Aile bu talebe yeni bir mesaj yazdiginda bildirim gonderir. 16 Temmuz
     * 2026 oncesi burada da recipients() (sehir/kategori eslesen TUM kurumlar)
     * kullaniliyordu - yayin talebine teklif veren rakip kurumlarin ayni
     * mesaj thread'ini gorebildigi hatayla ayni kokten: SADECE dogrudan
     * talebin tek kurumuna veya (yayin talebiyse) KABUL EDILEN teklifin
     * sahibi kuruma bildirim gitmeli, teklif vermis/vermemis diger kurumlara degil.
     */
    public function notifyNewMessageFromFamily(OfferRequest $offerRequest): void
    {
        $offerRequest->loadMissing('familyUser', 'acceptedQuote.facility');
        $familyName = $offerRequest->familyUser->name ?? $offerRequest->full_name;

        $recipientFacilityId = $offerRequest->facility_id ?? $offerRequest->acceptedQuote?->facility_id;
        if (! $recipientFacilityId) {
            return;
        }

        FacilityUser::where('facility_id', $recipientFacilityId)->get()->each(
            fn (FacilityUser $user) => notify_user($user, 'new_message', 'Yeni mesaj', $familyName.' size mesaj gönderdi.', [
                'offer_request_id' => $offerRequest->id,
            ])
        );
    }

    // 21 Temmuz 2026: aile bir teklifi kabul ettiginde ne kazanan kuruma
    // ("mesajlasma acildi, iletisime gecebilirsiniz") ne de kaybeden
    // kurumlara ("talep baska kurumca karsilandi") HICBIR bildirim gitmiyordu
    // - kurum panele bakmadan haberi olmuyordu. Diger bildirim akislariyla
    // ayni desen: sadece o kurumun tum yetkililerine.
    public function notifyQuoteAccepted(Quote $quote): void
    {
        FacilityUser::where('facility_id', $quote->facility_id)->get()->each(
            fn (FacilityUser $user) => notify_user($user, 'quote_accepted', 'Teklifiniz kabul edildi', 'Aile teklifinizi kabul etti, mesajlaşma ekranından iletişime geçebilirsiniz.', [
                'offer_request_id' => $quote->offer_request_id,
            ])
        );
    }

    public function notifyQuotesDeclined(Collection $declinedQuotes): void
    {
        $declinedQuotes->each(function (Quote $quote) {
            FacilityUser::where('facility_id', $quote->facility_id)->get()->each(
                fn (FacilityUser $user) => notify_user($user, 'quote_declined', 'Talep başka kurum tarafından karşılandı', 'Aile bu talep için başka bir kurumun teklifini kabul etti.')
            );
        });
    }

    public function recipients(OfferRequest $offerRequest)
    {
        if ($offerRequest->facility_id) {
            return FacilityUser::where('facility_id', $offerRequest->facility_id)->get();
        }

        // Yayin (broadcast) talebi: sehir+kategori eslesen, sahiplenilmis
        // TUM kurumlarin yetkilileri — dashboard'da zaten ayni sekilde
        // gorunuyorlar (bkz. Facility\DashboardController::$broadcastLeads).
        if (! $offerRequest->city_id || ! $offerRequest->facility_category_id) {
            return collect();
        }

        return FacilityUser::whereHas('facility', function ($query) use ($offerRequest) {
            $query->where('city_id', $offerRequest->city_id)
                ->where('facility_category_id', $offerRequest->facility_category_id)
                ->where('is_claimed', true);
        })->get();
    }
}
