<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Facility;
use App\Models\FacilityInterestLead;
use Illuminate\Http\Request;

// 20 Eylul 2026: kullanicinin talebi - bkz. migration'daki ayni tarihli
// yorum. SADECE on kayitli (sahiplenilmemis VE anlasmali olmayan)
// kurumlarda gorunen "Bilgi ve Fiyat Talep Et" formu.
class FacilityInterestLeadController extends Controller
{
    public function store(Request $request)
    {
        $request->validate(['website' => 'max:0']);

        $validated = $request->validate([
            'facility_id' => 'required|integer|exists:facilities,id',
            'full_name' => 'required|string|max:120',
            'phone' => 'required|string|max:30',
            'email' => 'nullable|email|max:150',
            'message' => 'nullable|string|max:1000',
        ]);

        $brand = current_brand();
        $facility = Facility::published()->forBrand($brand['category_scope'])->findOrFail($validated['facility_id']);

        $lead = FacilityInterestLead::create([
            'facility_id' => $facility->id,
            'brand' => $brand['slug'],
            'full_name' => $validated['full_name'],
            'phone' => $validated['phone'],
            'email' => $validated['email'] ?? null,
            'message' => $validated['message'] ?? null,
        ]);

        // 30 Eylul 2026: kullanicinin bildirdigi gercek hata - bu talepler
        // ADMIN'E HICBIR sekilde bildirilmiyordu (ne panel bildirimi ne
        // mail), admin gunlerce panele girmezse hic haberi olmuyordu.
        // notify_user() hem uygulama-ici bildirimi hem maili tek cagriyla
        // yapar (bkz. helpers.php).
        \App\Models\Admin::all()->each(fn ($admin) => notify_user(
            $admin, 'facility_interest_lead', 'Yeni kurum ilgi talebi',
            "\"{$facility->name}\" (ön kayıtlı kurum) için {$lead->full_name} bilgi/fiyat talebi gönderdi.",
            ['facility_id' => $facility->id, 'lead_id' => $lead->id]
        ));

        return back()->with('success', 'Talebiniz alındı, en kısa sürede size dönüş yapılacaktır.');
    }
}
