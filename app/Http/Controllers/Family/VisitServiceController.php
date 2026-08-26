<?php

namespace App\Http\Controllers\Family;

use App\Http\Controllers\Controller;
use App\Models\Facility;
use App\Models\FamilyUser;
use App\Models\VisitServiceRequest;
use Illuminate\Http\Request;

// 26 Agustos 2026: kullanicinin talebi - "bakim takip ziyareti" (kurumda
// yatan yakinina periyodik ziyaret+rapor) hizmeti icin aile paneli basvurusu.
// Mevcut 'Ziyaret Talebi' (VisitRequestController) ile KARISTIRILMAMALI - o,
// kurumu henuz incelemek isteyen ADAY aileler icindir, bu ise kurumda zaten
// yakini olan bir aile icindir. Fiyatlandirma/randevu admin tarafindan
// TELEFONLA/manuel yurutulur (bkz. Admin\VisitServiceController) - bu
// asamada yazilim sadece talebi tasir.
class VisitServiceController extends Controller
{
    public function index()
    {
        $family = FamilyUser::findOrFail(session('family_user_id'));
        $requests = $family->visitServiceRequests()->with(['facility', 'reports'])->get();

        return view("themes.".current_brand()['theme'].".family.visit-service-index", compact('requests'));
    }

    public function create(Request $request)
    {
        $facilities = Facility::whereHas('category', fn ($q) => $q->where('brand_scope', 'yasli-bakim'))
            ->where('allows_visit_service', true)
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);

        $selectedFacilityId = null;
        if ($request->filled('facility')) {
            $selectedFacilityId = $facilities->firstWhere('slug', $request->query('facility'))?->id;
        }

        return view("themes.".current_brand()['theme'].".family.visit-service-create", compact('facilities', 'selectedFacilityId'));
    }

    public function store(Request $request)
    {
        $family = FamilyUser::findOrFail(session('family_user_id'));
        $brand = current_brand();

        $data = $request->validate([
            'facility_id' => 'required|integer|exists:facilities,id',
            'patient_name' => 'required|string|max:150',
            'patient_age' => 'nullable|integer|min:0|max:120',
            'patient_condition' => 'nullable|string|max:1000',
            'desired_frequency' => 'nullable|string|max:60',
            'phone' => 'required|string|max:30',
        ]);

        // 26 Agustos 2026: kullanicinin talebi - SADECE yasli-bakim bolumunde
        // VE admin'in acikca izin verdigi kurumlar secilebilir (kurumun
        // onayi olmadan ucuncu bir ziyaretci gonderilmemeli). Sahiplenme
        // durumundan (is_claimed/is_broker_managed) BAGIMSIZ - bkz.
        // migration ayni tarihli yorum. Formda listelenmeyen bir facility_id
        // gonderilirse (elle degistirme) sunucu tarafinda da reddedilir.
        $facility = Facility::whereHas('category', fn ($q) => $q->where('brand_scope', 'yasli-bakim'))
            ->where('allows_visit_service', true)
            ->findOrFail($data['facility_id']);

        $visitServiceRequest = VisitServiceRequest::create([
            'family_user_id' => $family->id,
            'facility_id' => $facility->id,
            'brand' => $brand['slug'],
            'patient_name' => $data['patient_name'],
            'patient_age' => $data['patient_age'] ?? null,
            'patient_condition' => $data['patient_condition'] ?? null,
            'desired_frequency' => $data['desired_frequency'] ?? null,
            'phone' => $data['phone'],
            'status' => 'yeni',
        ]);

        \App\Models\Admin::all()->each(fn ($admin) => notify_user(
            $admin,
            'visit_service_request_submitted',
            'Yeni bakım takip ziyareti talebi',
            "\"{$facility->name}\" kurumundaki {$data['patient_name']} için yeni bir ziyaret talebi geldi.",
            ['visit_service_request_id' => $visitServiceRequest->id],
        ));

        return redirect(brand_route('family.visit-service.index'))
            ->with('success', 'Talebiniz alındı, ekibimiz sizinle iletişime geçecek.');
    }
}
