<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\RedirectsOutOfRangePagination;
use App\Http\Controllers\Controller;
use App\Models\FacilityReview;
use Illuminate\Http\Request;

class FacilityReviewController extends Controller
{
    use RedirectsOutOfRangePagination;

    public function index(Request $request)
    {
        // 17 Agustos 2026: bkz. Admin\VisitRequestController ayni tarihli
        // yorum - kurum silinince bagli yorumlar burada gorunmemeli.
        $query = FacilityReview::whereHas('facility')->with('facility.city', 'facility.category')->latest();

        if ($request->filled('brand')) {
            $query->where('brand', $request->brand);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $reviews = $query->paginate(20)->withQueryString();

        if ($redirect = $this->redirectIfPageOutOfRange($request, $reviews)) {
            return $redirect;
        }

        $brands = config('brands.brands');

        return view('admin.reviews.index', compact('reviews', 'brands'));
    }

    public function update(Request $request, FacilityReview $review)
    {
        $validated = $request->validate(['status' => 'required|in:pending,approved,rejected']);
        $oldStatus = $review->status;
        $review->update([
            'status' => $validated['status'],
            'approved_at' => $validated['status'] === 'approved' ? now() : null,
        ]);

        // 12 Agustos 2026: kullanicinin talebi - admin bir karar verince
        // (onay/red farketmeksizin) icerigi gonderen kisi mutlaka haberdar
        // olmali. Ayni duruma tekrar kaydetmek ikinci mail atmaz.
        if ($oldStatus !== $validated['status']) {
            $facilityName = $review->facility->name ?? 'kurum';
            if ($validated['status'] === 'approved') {
                notify_user($review->familyUser, 'review_approved', 'Yorumunuz yayınlandı', "\"{$facilityName}\" için yazdığınız yorum onaylanıp yayına alındı.");
            } elseif ($validated['status'] === 'rejected') {
                notify_user($review->familyUser, 'review_rejected', 'Yorumunuz yayınlanmadı', "\"{$facilityName}\" için yazdığınız yorum inceleme sonucunda yayınlanmadı.");
            }
        }

        return back()->with('success', 'Yorum durumu güncellendi.');
    }

    public function destroy(FacilityReview $review)
    {
        $facilityName = $review->facility->name ?? 'kurum';
        log_admin_event('facility_review_deleted', $review, ['body' => $review->body]);
        notify_user($review->familyUser, 'review_removed', 'Yorumunuz kaldırıldı', "\"{$facilityName}\" için yazdığınız yorum platformdan kaldırıldı.");
        $review->delete();

        return back()->with('success', 'Yorum silindi.');
    }
}