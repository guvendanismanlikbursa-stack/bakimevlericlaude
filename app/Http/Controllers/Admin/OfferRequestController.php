<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OfferRequest;
use Illuminate\Http\Request;

class OfferRequestController extends Controller
{
    public function index(Request $request)
    {
        $query = OfferRequest::with('facility');

        if ($request->filled('brand')) {
            $query->where('brand', $request->brand);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $requests = $query->latest()->paginate(20)->withQueryString();
        $brands = config('brands.brands');

        return view('admin.offer-requests.index', compact('requests', 'brands'));
    }

    public function update(Request $request, OfferRequest $offerRequest)
    {
        $request->validate(['status' => 'required|in:new,contacted,closed']);
        $offerRequest->update(['status' => $request->status]);

        return back()->with('success', 'Durum güncellendi.');
    }
}
