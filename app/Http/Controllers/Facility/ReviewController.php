<?php

namespace App\Http\Controllers\Facility;

use App\Http\Controllers\Controller;
use App\Models\FacilityReview;
use App\Models\FacilityUser;
use Illuminate\Http\Request;

// 12 Agustos 2026: kullanicinin talebi - "yorumlara cevap veremiyorum,
// itibar yonetimi tek tarafli". Kurum yetkilisi kendi kurumuna yazilan
// ONAYLI yorumlara kamuya acik tek bir cevap verebilir (bkz.
// themes._shared.facilities.show'daki "Kurum Yanıtı" gosterimi).
class ReviewController extends Controller
{
    public function index()
    {
        $brand = current_brand();
        $user = FacilityUser::findOrFail(session('facility_user_id'));

        $reviews = $user->facility->approvedReviews()->latest('approved_at')->get();

        return view("themes.{$brand['theme']}.facility.reviews", compact('reviews'));
    }

    // 12 Agustos 2026: kullanicinin talebi - bu uygulamada /site/{brand}/...
    // rotalari AYNI kapali fonksiyon iki kez (gercek domain + test/yerel
    // "site/{brand}" onekiyle) kaydedildigi icin (bkz. routes/web.php
    // $siteRoutes), Laravel'in ORTUK route-model-binding'i (tip ipucuyla
    // otomatik model cozme) guvenilir calismiyor - kodun geri kalaninda
    // (bkz. Facility\MessageController::offerRequestFromRoute()) da hep
    // model ELLE, $request->route(...) uzerinden cozuluyor, ayni desen
    // burada da izleniyor.
    public function reply(Request $request)
    {
        $review = FacilityReview::findOrFail($request->route('review'));
        $user = FacilityUser::findOrFail(session('facility_user_id'));
        abort_unless($review->facility_id === $user->facility_id, 403);
        abort_unless($review->status === 'approved', 403);

        $data = $request->validate(['facility_reply' => 'required|string|max:1000']);

        $review->update([
            'facility_reply' => $data['facility_reply'],
            'facility_replied_at' => now(),
        ]);

        return back()->with('success', 'Yanıtınız yayınlandı.');
    }
}
