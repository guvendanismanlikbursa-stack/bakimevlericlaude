<?php

namespace App\Http\Controllers\Family;

use App\Http\Controllers\Controller;
use App\Models\FamilyUser;
use App\Models\Message;
use App\Models\OfferRequest;
use App\Services\OfferRequestNotificationService;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function index(Request $request)
    {
        $brand = current_brand();
        $offerRequest = $this->offerRequestFromRoute($request);
        $family = FamilyUser::findOrFail(session('family_user_id'));

        abort_unless($offerRequest->family_user_id === $family->id, 403);
        abort_unless($offerRequest->brand === $brand['slug'], 403);

        $offerRequest->load(['messages', 'facility', 'quotes.facility']);

        return view("themes.{$brand['theme']}.family.thread", compact('offerRequest'));
    }

    public function store(Request $request, OfferRequestNotificationService $notifier)
    {
        $brand = current_brand();
        $offerRequest = $this->offerRequestFromRoute($request);
        $family = FamilyUser::findOrFail(session('family_user_id'));

        abort_unless($offerRequest->family_user_id === $family->id, 403);
        abort_unless($offerRequest->brand === $brand['slug'], 403);

        $data = $request->validate(['body' => 'required|string|max:2000']);

        Message::create([
            'offer_request_id' => $offerRequest->id,
            'sender_type' => 'family',
            'sender_id' => $family->id,
            'body' => $data['body'],
        ]);

        $notifier->notifyNewMessageFromFamily($offerRequest);

        return back();
    }

    private function offerRequestFromRoute(Request $request): OfferRequest
    {
        $value = $request->route('offerRequest');

        return $value instanceof OfferRequest ? $value : OfferRequest::findOrFail($value);
    }
}