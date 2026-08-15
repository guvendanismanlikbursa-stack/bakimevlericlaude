<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function create()
    {
        $brand = app('currentBrand');

        return view("themes.{$brand['theme']}.contact");
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:120',
            'email' => 'required|email|max:150',
            'subject' => 'nullable|string|max:200',
            'message' => 'required|string|max:3000',
            // 15 Agustos 2026: honeypot - bkz. partials/honeypot.blade.php
            'website' => 'max:0',
        ]);
        unset($validated['website']);

        $brand = app('currentBrand');
        $validated['brand'] = $brand['slug'];

        $message = ContactMessage::create($validated);

        // 28 Temmuz 2026: guvenlik taramasi sirasinda bulundu - bu form
        // sadece veritabanina yaziyordu, admin'e HICBIR gercek zamanli
        // bildirim (mail/uygulama-ici/push) gitmiyordu; admin farkina
        // varmak icin elle /admin/mesajlar sayfasini ziyaret etmek zorundaydi.
        // Diger basvuru formlariyla (sahiplenme, kurum kaydi) ayni desen.
        \App\Models\Admin::all()->each(fn ($admin) => notify_user(
            $admin,
            'contact_message_submitted',
            'Yeni iletişim mesajı',
            $message->name.' size bir mesaj gönderdi.',
        ));

        return back()->with('success', 'Mesajınız iletildi, teşekkür ederiz.');
    }
}
