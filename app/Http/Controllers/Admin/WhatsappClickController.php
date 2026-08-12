<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WhatsappClick;
use Illuminate\Http\Request;

class WhatsappClickController extends Controller
{
    public function index(Request $request)
    {
        $query = WhatsappClick::query();

        if ($request->filled('brand')) {
            $query->where('brand', $request->brand);
        }

        $clicks = $query->latest()->paginate(30)->withQueryString();
        $brands = config('brands.brands');

        return view('admin.whatsapp-clicks.index', compact('clicks', 'brands'));
    }

    public function destroy(WhatsappClick $whatsappClick)
    {
        log_admin_event('whatsapp_click_deleted', $whatsappClick);
        $whatsappClick->delete();

        return back()->with('success', 'Kayıt silindi.');
    }
}
