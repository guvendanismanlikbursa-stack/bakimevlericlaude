<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FacilityReview;
use Illuminate\Http\Request;

class FacilityReviewController extends Controller
{
    public function index(Request $request)
    {
        $query = FacilityReview::with('facility.city', 'facility.category')->latest();

        if ($request->filled('brand')) {
            $query->where('brand', $request->brand);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $reviews = $query->paginate(20)->withQueryString();
        $brands = config('brands.brands');

        return view('admin.reviews.index', compact('reviews', 'brands'));
    }

    public function update(Request $request, FacilityReview $review)
    {
        $validated = $request->validate(['status' => 'required|in:pending,approved,rejected']);
        $review->update([
            'status' => $validated['status'],
            'approved_at' => $validated['status'] === 'approved' ? now() : null,
        ]);

        return back()->with('success', 'Yorum durumu güncellendi.');
    }

    public function destroy(FacilityReview $review)
    {
        $review->delete();

        return back()->with('success', 'Yorum silindi.');
    }
}