<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Facility;
use App\Models\JobApplication;
use Illuminate\Http\Request;

// 26 Agustos 2026: kullanicinin talebi - gercek bir sahiplenme
// basvurusunda basvuran yanlislikla CV gonderince dogan fikir: "Burada
// çalışmak istiyorum" basvurusu, sadece sahiplenilmis veya aracilik
// (is_broker_managed) kurumlarda ve SADECE yasli-bakim/cocuk bolumlerinde
// (rehabilitasyon HARIC - kullanicinin acik talebi) gosterilir. Basvurular
// admin panelinden kuruma MANUEL olarak WhatsApp ile iletilir (bkz.
// Admin\JobApplicationController) - facility_claims ile ayni "basvuru ->
// admin gorur -> iletir" deseni, ama daha basit (belge/onay yok).
class JobApplicationController extends Controller
{
    public function create(Request $request)
    {
        $brand = current_brand();
        $facility = $this->facilityForRequest($request, $brand['category_scope']);
        $this->abortUnlessEligible($facility);

        return view("themes.{$brand['theme']}.job-application", compact('facility'));
    }

    public function store(Request $request)
    {
        $brand = current_brand();
        $facility = $this->facilityForRequest($request, $brand['category_scope']);
        $this->abortUnlessEligible($facility);

        $data = $request->validate([
            'applicant_name' => 'required|string|max:120',
            'applicant_age' => 'nullable|integer|min:16|max:90',
            'applicant_location' => 'nullable|string|max:120',
            'applicant_phone' => 'required|string|max:30',
            'applicant_email' => 'nullable|email|max:150',
            'experience' => 'nullable|string|max:1000',
            'desired_position' => 'nullable|string|max:150',
            'consent' => 'required|accepted',
            // 15 Agustos 2026 deseni: honeypot - bkz. partials/honeypot.blade.php
            'website' => 'max:0',
        ], [
            'consent.required' => 'Açık rıza metnini onaylamadan başvuru gönderemezsiniz.',
            'consent.accepted' => 'Açık rıza metnini onaylamadan başvuru gönderemezsiniz.',
        ]);

        JobApplication::create([
            'facility_id' => $facility->id,
            'brand' => $brand['slug'],
            'applicant_name' => $data['applicant_name'],
            'applicant_age' => $data['applicant_age'] ?? null,
            'applicant_location' => $data['applicant_location'] ?? null,
            'applicant_phone' => $data['applicant_phone'],
            'applicant_email' => $data['applicant_email'] ?? null,
            'experience' => $data['experience'] ?? null,
            'desired_position' => $data['desired_position'] ?? null,
            'status' => 'yeni',
            'consent_accepted_at' => now(),
            'consent_ip' => $request->ip(),
        ]);

        \App\Models\Admin::all()->each(fn ($admin) => notify_user(
            $admin,
            'job_application_submitted',
            'Yeni iş başvurusu',
            "\"{$facility->name}\" kurumuna yeni bir iş başvurusu geldi.",
            ['facility_id' => $facility->id],
        ));

        return redirect(brand_route('facilities.show', ['slug' => $facility->slug]))
            ->with('success', 'Başvurunuz alındı! Bilgileriniz incelenip kurumla paylaşılacaktır.');
    }

    private function abortUnlessEligible(Facility $facility): void
    {
        abort_unless($facility->is_claimed || $facility->is_broker_managed, 404);
        $facility->loadMissing('category');
        abort_if($facility->category?->brand_scope === 'rehabilitasyon', 404);
    }

    private function facilityForRequest(Request $request, array $categoryScope): Facility
    {
        return Facility::where('slug', $request->route('slug'))
            ->forBrand($categoryScope)
            ->firstOrFail();
    }
}
