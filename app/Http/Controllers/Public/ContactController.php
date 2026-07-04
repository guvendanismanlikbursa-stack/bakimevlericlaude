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
        ]);

        $brand = app('currentBrand');
        $validated['brand'] = $brand['slug'];

        ContactMessage::create($validated);

        return back()->with('success', 'Mesajınız iletildi, teşekkür ederiz.');
    }
}
