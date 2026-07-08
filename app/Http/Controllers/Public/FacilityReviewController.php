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
        $facility = Facility::published()->forBrand($brand['category_scope'])
            ->where('is_claimed', true)
            ->where('slug', $request->route('slug'))
            ->firstOrFail();

        $familyId = session('family_user_id');
        if (! $familyId) {
            return back()->withErrors(['review' => 'Yorum yapabilmek için önce aile hesabınızla giriş yapıp bu kurumdan ücret/teklif bilgisi istemelisiniz.']);
        }

        $hasOfferRequest = OfferRequest::where('family_user_id', $familyId)->where('facility_id', $facility->id)->exists();
        if (! $hasOfferRequest) {
            return back()->withErrors(['review' => 'Yorum yapabilmek için önce bu kurumdan ücret/teklif bilgisi istemelisiniz.']);
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

        return back()->with('success', 'Yorumunuz alındı. Admin onayından sonra yayına alınacak.');
    }
}