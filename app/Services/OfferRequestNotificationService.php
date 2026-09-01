<?php

namespace App\Services;

use App\Models\Facility;
use App\Models\FacilityUser;
use App\Models\OfferRequest;
use App\Models\Quote;
use Illuminate\Support\Collection;

/**
 * Yeni bir teklif talebi (dogrudan tek kuruma veya sehir/kategoriye yayin)
 * olusunca ilgili kurum(lar)in panelinde bildirim cikmasi icin kullanilir.
 * Talep her zaman kurum panelinde (dashboard sorgusuyla) zaten gorunur; bu
 * servis SADECE push-tarzi bildirimi (bell/rozet) ekler.
 *
 * 26 Agustos 2026: kullanicinin acik talebi - anlasmali (aracilik,
 * is_broker_managed) kurumlarin panelini kurumun kendisi degil ADMIN
 * takip ediyor, bu yuzden bu kurumlarin KENDI hesabina (FacilityUser)
 * ARTIK HICBIR bildirim gitmiyor - ilgili TUM bildirim turleri (yeni
 * teklif, aileden mesaj, teklif kabul/red) bunun yerine TUM admin'lere
 * gider, tiklaninca dogrudan o kurumun paneline atlar (bkz.
 * Admin\BrokerController::quickJump(), notification_action_url()
 * 'broker_*' case'leri).
 */
class OfferRequestNotificationService
{
    public function notify(OfferRequest $offerRequest): void
    {
        $offerRequest->loadMissing('facility', 'category');

        $title = 'Yeni ücret/teklif talebi';
        $body = $offerRequest->full_name.' bir ücret/teklif talebi gönderdi.';

        // 1 Eylul 2026: kullanicinin bildirdigi ("her yerde mantik hatasi
        // var, detayli incele") denetimde bulunan gercek hata - dogrudan
        // (facility_id'li) bir talep anlasmali-ama-sahiplenilmemis bir
        // kuruma gelince, eski kod bu kurumun FacilityUser hesabi olmadigi
        // icin (recipients() SADECE FacilityUser doner) HEM kendi hesabina
        // HEM admin'e HICBIR bildirim gondermiyordu - talep sessizce
        // kayboluyordu (sadece admin panelinden elle bakilirsa fark edilirdi).
        // Dogrudan tek-kurum talebi icin artik ayni genel kural (bkz.
        // notify_facility_or_broker_admins() helpers.php) kullanilir.
        if ($offerRequest->facility_id && $offerRequest->facility) {
            notify_facility_or_broker_admins(
                $offerRequest->facility,
                'offer_request', $title, $body,
                'broker_offer_request', 'Anlaşmalı kurum: yeni talep', "\"{$offerRequest->facility->name}\" için yeni bir ücret/teklif talebi geldi.",
                ['offer_request_id' => $offerRequest->id]
            );

            return;
        }

        // Yayin (broadcast) talebi: recipients() zaten SADECE is_claimed=true
        // kurumlarin FacilityUser hesaplarini doner (bkz. asagidaki metodun
        // yorumu) - anlasmali-ama-sahiplenilmemis kurumlar zaten yayin
        // talebi alicisi degil, bu dal degismedi.
        $this->recipients($offerRequest)->each(fn (FacilityUser $user) => notify_user($user, 'offer_request', $title, $body, [
            'offer_request_id' => $offerRequest->id,
        ]));
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
        $offerRequest->loadMissing('familyUser', 'facility', 'acceptedQuote.facility');
        $familyName = $offerRequest->familyUser->name ?? $offerRequest->full_name;

        $facility = $offerRequest->facility_id ? $offerRequest->facility : $offerRequest->acceptedQuote?->facility;
        if (! $facility) {
            return;
        }

        $this->notifyFacilityOrBrokerAdmins(
            $facility,
            'new_message', 'Yeni mesaj', $familyName.' size mesaj gönderdi.',
            'broker_new_message', 'Anlaşmalı kurum: yeni mesaj', "\"{$facility->name}\" kurumuna {$familyName} yeni bir mesaj gönderdi.",
            $offerRequest->id
        );
    }

    // 21 Temmuz 2026: aile bir teklifi kabul ettiginde ne kazanan kuruma
    // ("mesajlasma acildi, iletisime gecebilirsiniz") ne de kaybeden
    // kurumlara ("talep baska kurumca karsilandi") HICBIR bildirim gitmiyordu
    // - kurum panele bakmadan haberi olmuyordu. Diger bildirim akislariyla
    // ayni desen: sadece o kurumun tum yetkililerine.
    public function notifyQuoteAccepted(Quote $quote): void
    {
        $quote->loadMissing('facility');
        if (! $quote->facility) {
            return;
        }

        $this->notifyFacilityOrBrokerAdmins(
            $quote->facility,
            'quote_accepted', 'Teklifiniz kabul edildi', 'Aile teklifinizi kabul etti, mesajlaşma ekranından iletişime geçebilirsiniz.',
            'broker_quote_accepted', 'Anlaşmalı kurum: teklif kabul edildi', "Aile \"{$quote->facility->name}\" kurumunun teklifini kabul etti, mesajlaşma ekranından iletişime geçebilirsiniz.",
            $quote->offer_request_id
        );
    }

    public function notifyQuotesDeclined(Collection $declinedQuotes): void
    {
        $declinedQuotes->each(function (Quote $quote) {
            $quote->loadMissing('facility');
            if (! $quote->facility) {
                return;
            }

            $this->notifyFacilityOrBrokerAdmins(
                $quote->facility,
                'quote_declined', 'Talep başka kurum tarafından karşılandı', 'Aile bu talep için başka bir kurumun teklifini kabul etti.',
                'broker_quote_declined', 'Anlaşmalı kurum: talep başka kurumca karşılandı', "Aile \"{$quote->facility->name}\" kurumuna gönderdiği talep için başka bir kurumun teklifini kabul etti.",
                $quote->offer_request_id
            );
        });
    }

    /**
     * Tek bir kurumu hedefleyen bildirimler icin ortak yonlendirme: kurum
     * anlasmali (is_broker_managed) DEGILSE kendi yetkililerine, ANLASMALI
     * ise kendisi yerine TUM admin'lere gider. 27 Agustos 2026: bu mantik
     * artik notify_facility_or_broker_admins() (bkz. helpers.php) ortak
     * fonksiyonuna tasindi - VisitRequestController da AYNI kurali kullanir.
     */
    private function notifyFacilityOrBrokerAdmins(
        Facility $facility,
        string $facilityUserType, string $facilityTitle, string $facilityBody,
        string $brokerType, string $brokerTitle, string $brokerBody,
        ?int $offerRequestId = null
    ): void {
        notify_facility_or_broker_admins(
            $facility,
            $facilityUserType, $facilityTitle, $facilityBody,
            $brokerType, $brokerTitle, $brokerBody,
            ['offer_request_id' => $offerRequestId]
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
