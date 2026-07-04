<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VisitRequest;
use Illuminate\Http\Request;

class VisitRequestController extends Controller
{
    public function index(Request $request)
    {
        $query = VisitRequest::with('facility.city', 'facility.category')->latest();

        if ($request->filled('brand')) {
            $query->where('brand', $request->brand);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $visitRequests = $query->paginate(20)->withQueryString();
        $brands = config('brands.brands');

        return view('admin.visit-requests.index', compact('visitRequests', 'brands'));
    }

    public function update(Request $request, VisitRequest $visitRequest)
    {
        $validated = $request->validate(['status' => 'required|in:new,contacted,completed,cancelled']);
        $visitRequest->update($validated);

        return back()->with('success', 'Ziyaret talebi güncellendi.');
    }

    public function destroy(VisitRequest $visitRequest)
    {
        $visitRequest->delete();

        return back()->with('success', 'Ziyaret talebi silindi.');
    }
}