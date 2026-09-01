<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Facility;
use App\Models\VisitRequest;
use Illuminate\Http\Request;

class VisitRequestController extends Controller
{
    public function store(Request $request)
    {
        // 15 Agustos 2026: honeypot - bkz. partials/honeypot.blade.php.
        // Ayri validate() cagrisi bilerek - $validated dogrudan
        // VisitRequest::create()'e gectigi icin 'website' anahtarinin
        // karismasi istenmiyor.
        $request->validate(['website' => 'max:0']);

        $brand = current_brand();
        $facility = Facility::published()->forBrand($brand['category_scope'])
            ->acceptsFamilyRequests()
            ->where('slug', $request->route('slug'))
            ->firstOrFail();

        $validated = $request->validate([
            'full_name' => 'required|string|max:120',
            'phone' => 'required|string|max:30',
            'email' => 'nullable|email|max:150',
            'preferred_day' => 'nullable|string|max:50',
            'preferred_time' => 'nullable|string|max:50',
            'message' => 'nullable|string|max:1500',
        ]);

        $validated['brand'] = $brand['slug'];
        $validated['facility_id'] = $facility->id;
        $validated['type'] = 'ziyaret';
        $validated['status'] = 'new';

        VisitRequest::create($validated);

        $this->notifyFacility($facility, 'Yeni ziyaret/randevu talebi', $validated['full_name'].' bir ziyaret/randevu talebi gönderdi.');

        return back()->with('success', 'Ziyaret/randevu talebiniz alındı. Kurum veya platform ekibi sizinle iletişime geçecek.');
    }

    /**
     * "Kontenjan Sor" — tek tikla, ziyaret formu doldurmadan bos yer/kontenjan
     * durumunu sormayi saglayan hafif form. Ayni visit_requests tablosunu
     * type=kontenjan ile kullanir; admin ve kurum panelinde ayirt edilebilir.
     */
    public function storeAvailability(Request $request)
    {
        // 15 Agustos 2026: honeypot - bkz. partials/honeypot.blade.php.
        $request->validate(['website' => 'max:0']);

        $brand = current_brand();
        $facility = Facility::published()->forBrand($brand['category_scope'])
            ->acceptsFamilyRequests()
            ->where('slug', $request->route('slug'))
            ->firstOrFail();

        $validated = $request->validate([
            'full_name' => 'required|string|max:120',
            'phone' => 'required|string|max:30',
        ]);

        $visitRequest = VisitRequest::create([
            'facility_id' => $facility->id,
            'brand' => $brand['slug'],
            'type' => 'kontenjan',
            'full_name' => $validated['full_name'],
            'phone' => $validated['phone'],
            'message' => 'Boş yer / kontenjan durumu soruldu.',
            'status' => 'new',
        ]);

        $this->notifyFacility($facility, 'Kontenjan / boş yer sorusu', $validated['full_name'].' kontenjan durumunuzu soruyor. Telefon: '.$validated['phone']);

        return back()->with('success', 'Sorunuz kuruma iletildi, en kısa sürede sizinle iletişime geçecekler.');
    }

    // 27 Agustos 2026: kullanicinin "anlaşmalı kurumlardan aile randevu
    // istediğinde admine geliyor değil mi" sorusuyla bulunan gercek eksiklik -
    // bu bildirim eskiden HER ZAMAN kurumun kendi hesabina gidiyordu, anlasmali
    // (is_broker_managed) kurumlar icin de. Artik OfferRequestNotificationService
    // ile AYNI kurali kullanir (bkz. notify_facility_or_broker_admins()
    // helpers.php) - anlasmali kurumun kendi hesabina hicbir bildirim gitmez.
    private function notifyFacility(Facility $facility, string $title, string $body): void
    {
        notify_facility_or_broker_admins(
            $facility,
            'visit_request', $title, $body,
            'broker_visit_request', "Anlaşmalı kurum: {$title}", "\"{$facility->name}\" kurumu için: {$body}"
        );
    }
}