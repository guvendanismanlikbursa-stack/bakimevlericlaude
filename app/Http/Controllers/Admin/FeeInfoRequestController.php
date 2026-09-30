<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FacilityInterestLead;
use App\Models\OfferRequest;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

// 30 Eylul 2026: kullanicinin talebi - "ilgi ve talep ayni sey, neden iki
// ayri sayfa/isim var". Teklif Talebi (OfferRequest) ve Kurum Ilgi Talebi
// (FacilityInterestLead) ARKA PLANDA hala iki ayri tablo (davranislari
// farkli: biri gercek mesajlasma/teklif surecine bagli, digeri sadece
// satis amacli bir kayit) ama ADMIN GOZUNDE artik TEK bir "Ücret ve Bilgi
// Talepleri" listesi olarak birlesiyor. Sahipli kurumlarin talepleri
// (zaten dogrudan kurumun kendi paneline dusuyor) burada SADECE
// goruntulenir, aksiyon (WhatsApp gonder / sil) alinamaz.
class FeeInfoRequestController extends Controller
{
    public function index(Request $request)
    {
        $scope = $request->query('scope', 'tumu');

        $offerRequests = OfferRequest::with('facility.city')->whereNotNull('facility_id')->latest()->limit(500)->get()
            ->map(fn (OfferRequest $r) => $this->normalizeOffer($r));

        $interestLeads = FacilityInterestLead::with('facility.city')->latest()->limit(500)->get()
            ->map(fn (FacilityInterestLead $l) => $this->normalizeLead($l));

        $all = $offerRequests->concat($interestLeads)
            ->filter(fn ($row) => $row !== null)
            ->when($scope === 'anlasmali', fn ($c) => $c->where('kurum_durumu', 'anlasmali'))
            ->when($scope === 'on_kayitli', fn ($c) => $c->where('kurum_durumu', 'on_kayitli'))
            ->when($scope === 'sahipli', fn ($c) => $c->where('kurum_durumu', 'sahipli'))
            ->sortByDesc('created_at')
            ->values();

        // Filtre uygulanmadan ONCEKI toplam sayilar (sekme basliklarinda
        // "12" gibi rakamlar) her zaman TAM listeden hesaplanir, $all
        // zaten $scope'a gore filtrelenmis olabilir.
        $tamListe = $offerRequests->concat($interestLeads)->filter(fn ($row) => $row !== null);
        $sayilar = [
            'tumu' => $tamListe->count(),
            'anlasmali' => $tamListe->where('kurum_durumu', 'anlasmali')->count(),
            'on_kayitli' => $tamListe->where('kurum_durumu', 'on_kayitli')->count(),
            'sahipli' => $tamListe->where('kurum_durumu', 'sahipli')->count(),
        ];

        $page = (int) $request->query('page', 1);
        $perPage = 30;
        $items = new LengthAwarePaginator(
            $all->forPage($page, $perPage)->values(),
            $all->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('admin.fee-info-requests.index', ['items' => $items, 'scope' => $scope, 'sayilar' => $sayilar]);
    }

    public function sendWhatsapp(Request $request, string $type, int $id)
    {
        [$model, $facility] = $this->findRecord($type, $id);
        if (! $model || ! $facility) {
            return back()->with('error', 'Kayıt bulunamadı.');
        }
        abort_if($facility->is_claimed && ! $facility->is_broker_managed, 403, 'Sahipli kurum talepleri buradan yönetilemez.');

        $mesaj = $this->whatsappMessage($model, $facility);
        $url = facility_whatsapp_url_with_message($facility, $mesaj);
        if (! $url) {
            return back()->with('error', 'Kurumun geçerli bir cep telefonu numarası yok, WhatsApp gönderilemedi.');
        }

        $model->update(['sent_to_facility_at' => now()]);
        log_admin_event('fee_info_request_sent_whatsapp', $model, ['type' => $type]);

        return redirect($url);
    }

    public function destroy(string $type, int $id)
    {
        [$model, $facility] = $this->findRecord($type, $id);
        if (! $model) {
            return back()->with('error', 'Kayıt bulunamadı.');
        }
        abort_if($facility && $facility->is_claimed && ! $facility->is_broker_managed, 403, 'Sahipli kurum talepleri buradan silinemez.');

        $model->delete();

        return back()->with('success', 'Talep silindi.');
    }

    private function findRecord(string $type, int $id): array
    {
        if ($type === 'teklif') {
            $model = OfferRequest::with('facility')->find($id);
        } elseif ($type === 'ilgi') {
            $model = FacilityInterestLead::with('facility')->find($id);
        } else {
            $model = null;
        }

        return [$model, $model?->facility];
    }

    private function normalizeOffer(OfferRequest $r): ?array
    {
        if (! $r->facility) {
            return null;
        }

        return [
            'tur' => 'teklif',
            'id' => $r->id,
            'kurum' => $r->facility,
            'kurum_durumu' => $this->kurumDurumu($r->facility),
            'full_name' => $r->full_name,
            'phone' => $r->phone,
            'email' => $r->email,
            'message' => $r->message,
            'created_at' => $r->created_at,
            'sent_to_facility_at' => $r->sent_to_facility_at,
        ];
    }

    private function normalizeLead(FacilityInterestLead $l): ?array
    {
        if (! $l->facility) {
            return null;
        }

        return [
            'tur' => 'ilgi',
            'id' => $l->id,
            'kurum' => $l->facility,
            'kurum_durumu' => $this->kurumDurumu($l->facility),
            'full_name' => $l->full_name,
            'phone' => $l->phone,
            'email' => $l->email,
            'message' => $l->message,
            'created_at' => $l->created_at,
            'sent_to_facility_at' => $l->sent_to_facility_at,
        ];
    }

    private function kurumDurumu(\App\Models\Facility $facility): string
    {
        if ($facility->is_broker_managed) {
            return 'anlasmali';
        }

        return $facility->is_claimed ? 'sahipli' : 'on_kayitli';
    }

    private function whatsappMessage($model, \App\Models\Facility $facility): string
    {
        $tur = $facility->is_broker_managed ? 'Anlaşmalı kurum' : 'Ön kayıtlı kurum';
        $satirlar = [
            "Merhaba, \"{$facility->name}\" için bakimevleri.com üzerinden yeni bir ücret/bilgi talebi geldi. ({$tur})",
            'Ad Soyad: '.$model->full_name,
            'Telefon: '.$model->phone,
        ];
        if ($model->message) {
            $satirlar[] = 'Mesaj: '.$model->message;
        }

        return implode("\n", $satirlar);
    }
}
