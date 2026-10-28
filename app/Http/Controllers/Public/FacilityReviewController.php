<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Facility;
use App\Models\FacilityReview;
use App\Models\FamilyUser;
use App\Models\OfferRequest;
use Illuminate\Http\Request;

// Yorum yalnizca, bu kurumdan daha once ucret/teklif bilgisi istemis (yani
// OfferRequest gondermis) giris yapmis bir aile hesabi tarafindan birakilabilir.
// Boylece hem sahiplenilmemis kurumlar hem de hic iletisime gecmemis ziyaretciler
// yorum yazamaz; ad/telefon da aile hesabindan gelir, serbest metinle uydurulamaz.
class FacilityReviewController extends Controller
{
    public function store(Request $request)
    {
        $brand = current_brand();
        // 1 Eylul 2026: anlasmali (is_broker_managed) kurumlar icin de
        // artik gercek bir OfferRequest olusabiliyor (bkz.
        // Facility::scopeAcceptsFamilyRequests()) - bu kontrol eskisi
        // gibi SADECE is_claimed kalirsa, o kurumdan gercekten teklif
        // istemis bir aile yorum birakamaz, 404 alir.
        $facility = Facility::published()->forBrand($brand['category_scope'])
            ->acceptsFamilyRequests()
            ->where('slug', $request->route('slug'))
            ->firstOrFail();
        // 28 Ekim 2026: TTB kılavuzu - sağlık kuruluşlarına hasta değerlendirmesi alınmaz.
        abort_if($facility->hidesPatientReviews(), 404);

        $familyId = session('family_user_id');
        if (! $familyId) {
            return back()->withErrors(['review' => 'Yorum yapabilmek için önce aile hesabınızla giriş yapıp bu kurumdan ücret/teklif bilgisi istemelisiniz.']);
        }

        $hasOfferRequest = OfferRequest::where('family_user_id', $familyId)->where('facility_id', $facility->id)->exists();
        if (! $hasOfferRequest) {
            return back()->withErrors(['review' => 'Yorum yapabilmek için önce bu kurumdan ücret/teklif bilgisi istemelisiniz.']);
        }

        // 15 Agustos 2026: kullanicinin "asla hata kalmamali" talebi uzerine
        // yapilan spam/kotuye kullanim denetiminde bulundu - ayni aile hesabi
        // ayni kuruma sinirsiz sayida tekrar yorum birakabiliyordu, hicbir
        // teknik engel yoktu (admin onay kuyrugu doldurulabilir, veya
        // rakip sabotaji icin ayni kisi 5+ olumsuz yorum birakabilirdi).
        if (FacilityReview::where('family_user_id', $familyId)->where('facility_id', $facility->id)->exists()) {
            return back()->withErrors(['review' => 'Bu kurum için zaten bir yorumunuz var.']);
        }

        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'body' => 'nullable|string|max:1500',
        ]);

        $family = FamilyUser::findOrFail($familyId);

        FacilityReview::create([
            'facility_id' => $facility->id,
            'family_user_id' => $family->id,
            'brand' => $brand['slug'],
            'reviewer_name' => $family->name,
            'reviewer_phone' => $family->phone,
            'rating' => $validated['rating'],
            'body' => $validated['body'] ?? null,
            'status' => 'pending',
        ]);

        // 17 Ekim 2026: kullanıcının talebi (denetim bulgusu) - yorum onay
        // bekliyordu ama admine hiçbir bildirim gitmiyordu, admin menüsünde de
        // sayaç yoktu; yorumlar fark edilmeden günlerce bekleyebiliyordu.
        // İş başvurusu/sahiplenme ile aynı desen.
        \App\Models\Admin::all()->each(fn ($admin) => notify_user(
            $admin,
            'review_submitted',
            'Yeni kurum yorumu (onay bekliyor)',
            "\"{$facility->name}\" kurumuna {$validated['rating']} yıldızlı yeni bir yorum geldi.",
            ['facility_id' => $facility->id],
        ));

        return back()->with('success', 'Yorumunuz alındı. Admin onayından sonra yayına alınacak.');
    }
}