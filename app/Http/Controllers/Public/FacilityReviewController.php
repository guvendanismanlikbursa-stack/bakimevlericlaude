<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Facility;
use App\Models\FacilityReview;
use Illuminate\Http\Request;

class FacilityReviewController extends Controller
{
    public function store(Request $request)
    {
        $brand = current_brand();
        $facility = Facility::published()->forBrand($brand['category_scope'])->where('slug', $request->route('slug'))->firstOrFail();

        $validated = $request->validate([
            'reviewer_name' => 'required|string|max:120',
            'reviewer_phone' => 'nullable|string|max:30',
            'rating' => 'required|integer|min:1|max:5',
            'body' => 'nullable|string|max:1500',
        ]);

        $validated['brand'] = $brand['slug'];
        $validated['facility_id'] = $facility->id;
        $validated['status'] = 'pending';

        FacilityReview::create($validated);

        return back()->with('success', 'Yorumunuz alındı. Admin onayından sonra yayına alınacak.');
    }
}