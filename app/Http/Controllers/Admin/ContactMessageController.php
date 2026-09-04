<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\RedirectsOutOfRangePagination;
use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use Illuminate\Http\Request;

class ContactMessageController extends Controller
{
    use RedirectsOutOfRangePagination;

    public function index(Request $request)
    {
        $query = ContactMessage::query();

        if ($request->filled('brand')) {
            $query->where('brand', $request->brand);
        }

        // 4 Eylul 2026: kullanicinin talebi - dashboard'daki "okunmamis mesaj"
        // kutusundan buraya tiklaninca dogrudan okunmamislari filtrelemek icin.
        if ($request->boolean('unread')) {
            $query->where('is_read', false);
        }

        $messages = $query->latest()->paginate(20)->withQueryString();

        if ($redirect = $this->redirectIfPageOutOfRange($request, $messages)) {
            return $redirect;
        }

        $brands = config('brands.brands');

        return view('admin.contact-messages.index', compact('messages', 'brands'));
    }

    public function markRead(ContactMessage $contactMessage)
    {
        $contactMessage->update(['is_read' => true]);

        return back();
    }

    public function destroy(ContactMessage $contactMessage)
    {
        log_admin_event('contact_message_deleted', $contactMessage, ['email' => $contactMessage->email]);

        // 12 Agustos 2026: kullanicinin talebi - daha once hic cevap
        // yazilmadan (admin_reply bos) mesaj silinirse gonderen kisi
        // sessizce hicbir sonuc alamiyordu. Zaten cevaplanmis bir mesaj
        // silinirken tekrar mail atmiyoruz (cevabi zaten aldi).
        if (! $contactMessage->admin_reply && $contactMessage->email) {
            try {
                \Illuminate\Support\Facades\Mail::to($contactMessage->email)->sendNow(new \App\Mail\ContactMessageClosedMail($contactMessage));
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Iletisim mesaji kapatma maili gonderilemedi: ' . $e->getMessage(), ['contact_message_id' => $contactMessage->id]);
                notify_admin_of_exception($e);
            }
        }

        $contactMessage->delete();

        return back()->with('success', 'Mesaj silindi.');
    }

    // 30 Temmuz 2026: admin panelinden dogrudan cevap yazip gonderebilme -
    // eskiden bu ekranin hicbir cevaplama yolu yoktu, admin kendi e-posta
    // programindan mesaji yazan kisinin adresine elle yazmak zorundaydi.
    public function reply(Request $request, ContactMessage $contactMessage)
    {
        $data = $request->validate(['body' => 'required|string|min:2']);

        $contactMessage->update([
            'admin_reply' => $data['body'],
            'replied_at' => now(),
            'is_read' => true,
        ]);

        log_admin_event('contact_message_replied', $contactMessage, ['to' => $contactMessage->email]);

        $brandName = config('brands.brands')[$contactMessage->brand]['name'] ?? $contactMessage->brand;

        try {
            \Illuminate\Support\Facades\Mail::to($contactMessage->email)->sendNow(
                new \App\Mail\ContactMessageReplyMail($contactMessage, $brandName)
            );
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Iletisim mesaji cevabi gonderilemedi: ' . $e->getMessage(), ['contact_message_id' => $contactMessage->id]);
            notify_admin_of_exception($e);

            return back()->with('error', 'Cevap kaydedildi ama e-posta gönderiminde bir sorun oluştu.');
        }

        return back()->with('success', 'Cevabınız kaydedildi ve '.$contactMessage->email.' adresine gönderildi.');
    }
}
