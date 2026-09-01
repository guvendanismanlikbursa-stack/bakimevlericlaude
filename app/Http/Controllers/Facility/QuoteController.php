<?php

namespace App\Http\Controllers\Facility;

use App\Http\Controllers\Controller;
use App\Models\Facility;
use App\Models\FacilityUser;
use App\Models\OfferRequest;
use App\Models\Quote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QuoteController extends Controller
{
    public function store(Request $request)
    {
        $brand = current_brand();
        $offerRequest = $this->offerRequestFromRoute($request);
        $user = FacilityUser::with('facility.category')->findOrFail(session('facility_user_id'));

        // 1 Eylul 2026: kullanicinin bildirdigi gercek hata - bkz.
        // Facility\DashboardController ayni tarihli yorum. Talebin markasi
        // ile kurum yetkilisinin SU AN goruntuledigi site AYNI olmak
        // ZORUNDA degil - kurum 3 markada da AYNI envanteri paylasiyor,
        // ustelik category_scope ZATEN her markada birebir ayni (bkz.
        // config/brands.php) - bu ikinci kontrol de hicbir zaman false
        // donmuyordu, sadece brand esitligi gercek engeldi. Aile BASKA bir
        // siteden teklif istediyse, kurum yetkilisi kendine gelen bu
        // talebe ASLA teklif veremiyordu (403).
        abort_unless($user->facility->isInBrandScope($brand['category_scope']), 403);

        // 28 Temmuz 2026: canli uctan uca testte bulundu - FacilityUser::
        // facility_id modelde INTEGER'a cast edilmiyordu (MySQL/PDO bunu
        // string doner), oysa OfferRequest::facility_id ZATEN cast'li
        // (bkz. o modeldeki 13 Temmuz 2026 yorumu - ayni hata sinifi
        // family_user_id icin orada once bulunup duzeltilmisti). Sonuc:
        // asagidaki === her zaman int(6844) === string("6844") gibi
        // FALSE donuyordu - kurum yetkilisi DOGRUDAN kendisine gelen HICBIR
        // teklif talebine asla fiyat teklifi veremiyordu (403). Kesin cozum
        // FacilityUser modeline facility_id icin 'integer' cast eklemek
        // (bkz. o dosyadaki degisiklik) - burada da (int) ile kesin garanti
        // altina aliniyor, iki taraftan biri gelecekte tekrar bozulursa bile.
        $eligible = (int) $offerRequest->facility_id === (int) $user->facility_id
            || (is_null($offerRequest->facility_id)
                && (int) $offerRequest->city_id === (int) $user->facility->city_id
                && (int) $offerRequest->facility_category_id === (int) $user->facility->facility_category_id);

        abort_unless($eligible, 403);

        if ($offerRequest->accepted_quote_id || $offerRequest->status === 'closed') {
            return back()->withErrors(['quote' => 'Bu talep artik teklif kabul etmiyor.']);
        }

        if ($offerRequest->quotes()->where('facility_id', $user->facility_id)->exists()) {
            return back()->withErrors(['quote' => 'Bu talebe zaten bir teklif gonderdiniz.']);
        }

        $data = $request->validate([
            'price' => 'required|numeric|min:0|max:99999999',
            'price_period' => 'required|in:monthly,one_time',
            'message' => 'nullable|string|max:2000',
        ]);

        try {
            DB::transaction(function () use ($offerRequest, $user, $data, $brand) {
                $lockedOfferRequest = OfferRequest::where('id', $offerRequest->id)->lockForUpdate()->firstOrFail();
                $facility = Facility::where('id', $user->facility_id)->lockForUpdate()->firstOrFail();

                if (! $facility->isInBrandScope($brand['category_scope'])) {
                    abort(403);
                }

                if ($lockedOfferRequest->quotes()->where('facility_id', $facility->id)->exists()) {
                    throw new \InvalidArgumentException('Bu talebe zaten bir teklif gonderdiniz.');
                }

                if (! $facility->canSendQuote()) {
                    throw new \InvalidArgumentException('Ucretsiz teklif hakkiniz bitti ve bakiyeniz yetersiz. Lutfen bakiye yukleyin.');
                }

                Quote::create([
                    'offer_request_id' => $lockedOfferRequest->id,
                    'facility_id' => $facility->id,
                    'facility_user_id' => $user->id,
                    'price' => $data['price'],
                    'price_period' => $data['price_period'],
                    'message' => $data['message'] ?? null,
                ]);

                $facility->chargeForQuote();
            });
        } catch (\InvalidArgumentException $exception) {
            return back()->withErrors(['quote' => $exception->getMessage()]);
        }

        notify_user(
            $offerRequest->familyUser,
            'quote_received',
            'Yeni bir teklif aldınız',
            $user->facility->name.' talebinize teklif gönderdi.',
            ['offer_request_id' => $offerRequest->id]
        );

        return back()->with('success', 'Teklifiniz aileye iletildi.');
    }

    private function offerRequestFromRoute(Request $request): OfferRequest
    {
        $value = $request->route('offerRequest');

        return $value instanceof OfferRequest ? $value : OfferRequest::findOrFail($value);
    }
}