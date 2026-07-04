<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Facility;
use App\Models\FacilityCategory;
use App\Models\OfferRequest;
use Illuminate\Http\Request;

class OfferRequestController extends Controller
{
    /**
     * Hem tek kuruma dogrudan talep hem de sehir/kategorideki tum kurumlara
     * yayin talebi buradan gelir. Giris yoksa bilgiler oturumda saklanir.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'facility_id' => 'nullable|integer|exists:facilities,id',
            'city_id' => 'nullable|integer|exists:cities,id',
            'facility_category_id' => 'nullable|integer|exists:facility_categories,id',
            'full_name' => 'required|string|max:120',
            'phone' => 'required|string|max:30',
            'email' => 'nullable|email|max:150',
            'patient_name' => 'nullable|string|max:120',
            'care_for' => 'nullable|string|max:60',
            'message' => 'nullable|string|max:2000',
        ]);

        $brand = current_brand();
        $validated['brand'] = $brand['slug'];

        if (! empty($validated['facility_id'])) {
            $facility = Facility::with('category')->published()->forBrand($brand['category_scope'])->findOrFail($validated['facility_id']);
            $validated['city_id'] = $facility->city_id;
            $validated['facility_category_id'] = $facility->facility_category_id;
        } else {
            abort_unless(! empty($validated['city_id']) && ! empty($validated['facility_category_id']), 422);

            $category = FacilityCategory::findOrFail($validated['facility_category_id']);
            abort_unless(in_array($category->brand_scope, $brand['category_scope'], true), 404);
        }

        if ($familyId = session('family_user_id')) {
            $validated['family_user_id'] = $familyId;
            OfferRequest::create($validated);

            return redirect(brand_route('family.dashboard'))->with('success', 'Talebiniz olusturuldu, uygun kurumlardan teklif gelmeye baslayacak.');
        }

        session(['pending_offer_request' => $validated]);

        return redirect(brand_route('family.register'))
            ->with('info', 'Ucret/teklif bilgisi alabilmek icin once ucretsiz bir aile hesabi olusturmaniz gerekiyor. Bilgileriniz kaybolmayacak.');
    }
}