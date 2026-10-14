<?php

namespace App\Http\Controllers\Facility;

use App\Http\Controllers\Controller;
use App\Models\CareAdvisorLead;
use App\Models\CareAdvisorLeadSend;
use App\Models\FacilityUser;

// 13 Ekim 2026: kullanicinin talebi - "Karar Sihirbazi/Bakim Danismani
// doldurulunca, bu talep havuzuna yalnizca premium uye olan kurumlar
// erisebilmeli ve aileye teklif verebilmeli. Bu, ucretli uyeligin en
// buyuk satici gucu olur." Erisim, platformda ZATEN var olan "aktif
// abonelik" tanimiyla (is_subscribed + subscription_paid_until gecmemis -
// bkz. Facility::scopeOrderByListingPriority ayni tarihli yorum) kontrol
// edilir, yeni bir "premium" kavrami icat edilmez.
class CareAdvisorLeadController extends Controller
{
    public function index()
    {
        $user = FacilityUser::findOrFail(session('facility_user_id'));
        $facility = $user->facility;

        $isPremium = $facility->isActiveSubscriber();

        $leads = collect();
        if ($isPremium) {
            // 13 Ekim 2026: CareAdvisorMatchService'teki (aile->kurum yonu)
            // AYNI sert filtre (il + kurum turu), ters yonde (kurum->aile).
            // 13 Ekim 2026 (3): kullanicinin talebi - Pusula artik telefon/isim
            // yazilmasa da anket verisi olarak kaydediliyor (bkz. ayni tarihli
            // CareAdvisorController::results() yorumu). Kurumlara SADECE
            // gercekten ulasilabilir (telefonu olan) kayitlar gorunmeli.
            $leads = CareAdvisorLead::where('city_id', $facility->city_id)
                ->whereNotNull('phone')
                ->where(function ($q) use ($facility) {
                    $q->whereNull('facility_category_id')->orWhere('facility_category_id', $facility->facility_category_id);
                })
                ->where('created_at', '>=', now()->subDays(60))
                ->with('category')
                ->latest()
                ->limit(30)
                ->get();

            $sentCounts = CareAdvisorLeadSend::where('facility_id', $facility->id)
                ->whereIn('care_advisor_lead_id', $leads->pluck('id'))
                ->get()
                ->groupBy('care_advisor_lead_id')
                ->map->count();

            $leads->each(fn ($lead) => $lead->sent_count = $sentCounts[$lead->id] ?? 0);
        }

        return view('themes._shared.facility.care-advisor-leads', compact('facility', 'isPremium', 'leads'));
    }

    public function whatsapp(CareAdvisorLead $lead)
    {
        $user = FacilityUser::findOrFail(session('facility_user_id'));
        $facility = $user->facility;

        abort_unless($facility->isActiveSubscriber(), 403, 'Bu özellik Premium üyelere özeldir.');
        abort_unless((int) $lead->city_id === (int) $facility->city_id, 403);

        $message = "Merhaba, {$facility->name} olarak bakimevleri.com üzerinden iletişim bilgilerinizi gördük. "
            ."İhtiyacınızla ilgili size detaylı bilgi verebiliriz, uygun musunuz?";

        $digits = normalize_whatsapp_number($lead->phone);
        abort_if(! $digits, 404, 'Geçerli bir telefon numarası yok.');

        CareAdvisorLeadSend::create(['care_advisor_lead_id' => $lead->id, 'facility_id' => $facility->id]);

        return redirect()->away('https://wa.me/'.$digits.'?text='.rawurlencode($message));
    }
}
