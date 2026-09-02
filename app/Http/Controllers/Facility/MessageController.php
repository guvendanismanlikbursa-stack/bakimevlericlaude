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

        // 1 Eylul 2026: kullanicinin bildirdigi gercek hata - bkz.
        // Facility\DashboardController ayni tarihli yorum. Talebin
        // markasi ile kurum yetkilisinin SU AN goruntuledigi site AYNI
        // olmak ZORUNDA degil - kurum 3 markada da AYNI envanteri
        // paylasiyor. Bu kontrol, aile BASKA bir siteden yazdiysa
        // (facility_id zaten dogru kuruma sabit oldugu icin, tek gercek
        // yetki kurali canAccessThread()) kurum yetkilisini kendi
        // mesajina 403 ile erisemez hale getiriyordu.
        abort_unless($this->canAccessThread($offerRequest, $user->facility_id), 403);

        $offerRequest->load(['messages', 'familyUser']);

        return view("themes.{$brand['theme']}.facility.thread", compact('offerRequest'));
    }

    public function store(Request $request)
    {
        $offerRequest = $this->offerRequestFromRoute($request);
        $user = FacilityUser::findOrFail(session('facility_user_id'));

        // 1 Eylul 2026: kullanicinin bildirdigi gercek hata - bkz.
        // Facility\DashboardController ayni tarihli yorum. Talebin
        // markasi ile kurum yetkilisinin SU AN goruntuledigi site AYNI
        // olmak ZORUNDA degil - kurum 3 markada da AYNI envanteri
        // paylasiyor. Bu kontrol, aile BASKA bir siteden yazdiysa
        // (facility_id zaten dogru kuruma sabit oldugu icin, tek gercek
        // yetki kurali canAccessThread()) kurum yetkilisini kendi
        // mesajina 403 ile erisemez hale getiriyordu.
        abort_unless($this->canAccessThread($offerRequest, $user->facility_id), 403);

        $data = $request->validate(['body' => 'required|string|max:2000']);

        // 2 Eylul 2026: kullanicinin talebi - "aileler beni devre disi
        // birakmasin". Bir teklif kabul edilmeden once (bkz.
        // message_contains_contact_info() helpers.php) telefon/e-posta/
        // WhatsApp paylasimi engellenir - Airbnb/Upwork'un kullandigi ayni
        // yontem. Kabulden SONRA serbest birakilir, cunku o noktada zaten
        // kurumla anlasma sozlesmesindeki koruma suresi maddesi devrede.
        if ($offerRequest->accepted_quote_id === null && message_contains_contact_info($data['body'])) {
            $errorMessage = 'İletişim bilgisi (telefon, e-posta, WhatsApp vb.) paylaşımı, bir teklif kabul edilene kadar platform kurallarına aykırıdır. Lütfen mesajlaşmaya buradan devam edin.';

            if ($request->wantsJson()) {
                return response()->json(['message' => $errorMessage], 422);
            }

            return back()->withErrors(['body' => $errorMessage])->withInput();
        }

        $message = Message::create([
            'offer_request_id' => $offerRequest->id,
            'sender_type' => 'facility',
            'sender_id' => $user->facility_id,
            'body' => $data['body'],
        ]);

        notify_user(
            $offerRequest->familyUser,
            'new_message',
            'Yeni mesaj',
            $user->facility->name.' size mesaj gönderdi.',
            ['offer_request_id' => $offerRequest->id]
        );

        if ($request->wantsJson()) {
            return response()->json(['message' => $this->serializeMessage($message)]);
        }

        return back();
    }

    /**
     * 12 Agustos 2026: kullanicinin talebi - "mesajlasma canli hissetmiyor,
     * sayfayi yenilemem gerekiyor". Gercek WebSocket paylasimli hosting'de
     * kurulamadigi icin (bkz. CheckUserFlows/backup ile ayni kisit), guest
     * destek sohbetinde zaten kullanilan ayni "after_id ile kisa araliklarla
     * yoklama (polling)" deseni burada da uygulanir - bkz.
     * support-chat-widget.blade.php pollUrlTemplate mantigi.
     */
    public function poll(Request $request)
    {
        $offerRequest = $this->offerRequestFromRoute($request);
        $user = FacilityUser::findOrFail(session('facility_user_id'));

        // 1 Eylul 2026: kullanicinin bildirdigi gercek hata - bkz.
        // Facility\DashboardController ayni tarihli yorum. Talebin
        // markasi ile kurum yetkilisinin SU AN goruntuledigi site AYNI
        // olmak ZORUNDA degil - kurum 3 markada da AYNI envanteri
        // paylasiyor. Bu kontrol, aile BASKA bir siteden yazdiysa
        // (facility_id zaten dogru kuruma sabit oldugu icin, tek gercek
        // yetki kurali canAccessThread()) kurum yetkilisini kendi
        // mesajina 403 ile erisemez hale getiriyordu.
        abort_unless($this->canAccessThread($offerRequest, $user->facility_id), 403);

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

    // 16 Temmuz 2026: yayin (broadcast) taleplerde ayni talebe teklif veren
    // BIRDEN FAZLA rakip kurum, mesajlar tablosunda kuruma ozel ayrim
    // olmadigi icin AYNI thread'i gorup birbirinin aileyle yazismasini
    // okuyabiliyordu. Doğrudan talepte (facility_id zaten tek kuruma sabit)
    // risk yok, mesajlasma serbest kalir. Yayin talebinde ise aile TEK bir
    // teklifi kabul edene kadar mesajlasma tamamen kapali; kabul edildikten
    // sonra SADECE kabul edilen teklifin sahibi kurum erisebilir - aile
     // panelindeki "Mesajlar" linkinin zaten varsaydigi kural artik
    // kontrolcude de zorunlu kilinmis oluyor.
    private function canAccessThread(OfferRequest $offerRequest, int $facilityId): bool
    {
        if ($offerRequest->facility_id === $facilityId) {
            return true;
        }

        return $offerRequest->accepted_quote_id !== null
            && $offerRequest->quotes()->where('id', $offerRequest->accepted_quote_id)->where('facility_id', $facilityId)->exists();
    }

    private function offerRequestFromRoute(Request $request): OfferRequest
    {
        $value = $request->route('offerRequest');

        return $value instanceof OfferRequest ? $value : OfferRequest::findOrFail($value);
    }
}