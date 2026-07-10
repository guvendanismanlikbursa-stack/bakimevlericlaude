<?php

namespace App\Services;

use App\Models\FacilityUser;
use App\Models\OfferRequest;

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
     * Aile bu talebe yeni bir mesaj yazdiginda, talebi gorebilen TUM kurum
     * yetkililerine (offer_request::messages() thread'i erisebilenlerle ayni
     * recipients() mantigi) bildirim gonderir.
     */
    public function notifyNewMessageFromFamily(OfferRequest $offerRequest): void
    {
        $offerRequest->loadMissing('familyUser');
        $familyName = $offerRequest->familyUser->name ?? $offerRequest->full_name;

        $this->recipients($offerRequest)->each(
            fn (FacilityUser $user) => notify_user($user, 'new_message', 'Yeni mesaj', $familyName.' size mesaj gönderdi.', [
                'offer_request_id' => $offerRequest->id,
            ])
        );
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
