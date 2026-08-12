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
        abort_unless($this->canAccessThread($offerRequest), 403);

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
        abort_unless($this->canAccessThread($offerRequest), 403);

        // 21 Temmuz 2026: FacilityUserAuth ile ayni kural - dogrulanmamis
        // e-postali bir aile sinirsiz mesaj gonderip kurumu mesgul edebilirdi.
        if (! $family->hasVerifiedEmail()) {
            return redirect(brand_route('family.verify-email.notice'))
                ->with('info', 'Mesaj gönderebilmek için önce e-posta adresinizi doğrulamanız gerekiyor.');
        }

        $data = $request->validate(['body' => 'required|string|max:2000']);

        $message = Message::create([
            'offer_request_id' => $offerRequest->id,
            'sender_type' => 'family',
            'sender_id' => $family->id,
            'body' => $data['body'],
        ]);

        $notifier->notifyNewMessageFromFamily($offerRequest);

        if ($request->wantsJson()) {
            return response()->json(['message' => $this->serializeMessage($message)]);
        }

        return back();
    }

    /**
     * 12 Agustos 2026: bkz. Facility\MessageController::poll() ayni yorum -
     * "canli hissetmeyen" mesajlasma icin polling tabanli yenileme.
     */
    public function poll(Request $request)
    {
        $brand = current_brand();
        $offerRequest = $this->offerRequestFromRoute($request);
        $family = FamilyUser::findOrFail(session('family_user_id'));

        abort_unless($offerRequest->family_user_id === $family->id, 403);
        abort_unless($offerRequest->brand === $brand['slug'], 403);
        abort_unless($this->canAccessThread($offerRequest), 403);

        $afterId = (int) $request->query('after_id', 0);
        $messages = $offerRequest->messages()->where('id', '>', $afterId)->orderBy('id')->get();

        return response()->json([
            'messages' => $messages->map(fn ($m) => $this->serializeMessage($m))->all(),
        ]);
    }

    private function serializeMessage(Message $message): array
    {
        return [
            'id' => $message->id,
            'sender_type' => $message->sender_type,
            'body' => $message->body,
            'created_at' => $message->created_at->format('d.m.Y H:i'),
        ];
    }

    // 16 Temmuz 2026: dogrudan talepte (tek kurum) her zaman serbest;
    // yayin talebinde ise bir teklif kabul edilene kadar mesajlasma kapali -
    // bkz. Facility\MessageController::canAccessThread() ayni kural.
    private function canAccessThread(OfferRequest $offerRequest): bool
    {
        return $offerRequest->facility_id !== null || $offerRequest->accepted_quote_id !== null;
    }

    private function offerRequestFromRoute(Request $request): OfferRequest
    {
        $value = $request->route('offerRequest');

        return $value instanceof OfferRequest ? $value : OfferRequest::findOrFail($value);
    }
}