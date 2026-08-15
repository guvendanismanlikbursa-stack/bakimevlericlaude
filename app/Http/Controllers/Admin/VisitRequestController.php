<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\RedirectsOutOfRangePagination;
use App\Http\Controllers\Controller;
use App\Models\VisitRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class VisitRequestController extends Controller
{
    use RedirectsOutOfRangePagination;

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

        if ($redirect = $this->redirectIfPageOutOfRange($request, $visitRequests)) {
            return $redirect;
        }

        $brands = config('brands.brands');

        return view('admin.visit-requests.index', compact('visitRequests', 'brands'));
    }

    public function update(Request $request, VisitRequest $visitRequest)
    {
        $validated = $request->validate(['status' => 'required|in:new,contacted,completed,cancelled']);
        $wasCancelled = $visitRequest->status === 'cancelled';
        $visitRequest->update($validated);

        // 12 Agustos 2026: kullanicinin talebi - admin bir talebi iptal
        // ederken (gercek dunyada kurumla temas kurulmus "tamamlandi"
        // durumundan farkli olarak) talep sahibi baska hicbir kanaldan
        // haberdar olmuyordu, sessizce kapatiliyordu. Sadece YENI iptalde
        // gonderilir (zaten iptalliyi tekrar iptal etmek ikinci mail atmaz).
        if ($validated['status'] === 'cancelled' && ! $wasCancelled && $visitRequest->email) {
            try {
                Mail::to($visitRequest->email)->sendNow(new \App\Mail\VisitRequestCancelledMail($visitRequest));
            } catch (\Throwable $e) {
                Log::warning('Ziyaret talebi iptal maili gonderilemedi: ' . $e->getMessage(), ['visit_request_id' => $visitRequest->id]);
                notify_admin_of_exception($e);
            }
        }

        return back()->with('success', 'Ziyaret talebi güncellendi.');
    }

    public function destroy(VisitRequest $visitRequest)
    {
        $visitRequest->delete();

        return back()->with('success', 'Ziyaret talebi silindi.');
    }
}