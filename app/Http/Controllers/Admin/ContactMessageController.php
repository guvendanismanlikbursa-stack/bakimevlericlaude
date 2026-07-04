<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use Illuminate\Http\Request;

class ContactMessageController extends Controller
{
    public function index(Request $request)
    {
        $query = ContactMessage::query();

        if ($request->filled('brand')) {
            $query->where('brand', $request->brand);
        }

        $messages = $query->latest()->paginate(20)->withQueryString();
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
        $contactMessage->delete();

        return back()->with('success', 'Mesaj silindi.');
    }
}
