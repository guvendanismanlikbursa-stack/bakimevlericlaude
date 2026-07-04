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

        abort_unless($offerRequest->brand === $brand['slug'], 403);
        abort_unless($user->facility->isInBrandScope($brand['category_scope']), 403);

        $eligible = $offerRequest->facility_id === $user->facility_id
            || (is_null($offerRequest->facility_id)
                && $offerRequest->city_id === $user->facility->city_id
                && $offerRequest->facility_category_id === $user->facility->facility_category_id);

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

        return back()->with('success', 'Teklifiniz aileye iletildi.');
    }

    private function offerRequestFromRoute(Request $request): OfferRequest
    {
        $value = $request->route('offerRequest');

        return $value instanceof OfferRequest ? $value : OfferRequest::findOrFail($value);
    }
}