<?php

namespace App\Http\Controllers\Facility;

use App\Http\Controllers\Controller;
use App\Models\FacilityUser;
use App\Models\Message;
use App\Models\OfferRequest;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function index(Request $request)
    {
        $brand = current_brand();
        $offerRequest = $this->offerRequestFromRoute($request);
        $user = FacilityUser::findOrFail(session('facility_user_id'));

        abort_unless($offerRequest->brand === $brand['slug'], 403);
        abort_unless($this->canAccessThread($offerRequest, $user->facility_id), 403);

        $offerRequest->load(['messages', 'familyUser']);

        return view("themes.{$brand['theme']}.facility.thread", compact('offerRequest'));
    }

    public function store(Request $request)
    {
        $brand = current_brand();
        $offerRequest = $this->offerRequestFromRoute($request);
        $user = FacilityUser::findOrFail(session('facility_user_id'));

        abort_unless($offerRequest->brand === $brand['slug'], 403);
        abort_unless($this->canAccessThread($offerRequest, $user->facility_id), 403);

        $data = $request->validate(['body' => 'required|string|max:2000']);

        Message::create([
            'offer_request_id' => $offerRequest->id,
            'sender_type' => 'facility',
            'sender_id' => $user->facility_id,
            'body' => $data['body'],
        ]);

        return back();
    }

    private function canAccessThread(OfferRequest $offerRequest, int $facilityId): bool
    {
        return $offerRequest->facility_id === $facilityId
            || $offerRequest->quotes()->where('facility_id', $facilityId)->exists();
    }

    private function offerRequestFromRoute(Request $request): OfferRequest
    {
        $value = $request->route('offerRequest');

        return $value instanceof OfferRequest ? $value : OfferRequest::findOrFail($value);
    }
}