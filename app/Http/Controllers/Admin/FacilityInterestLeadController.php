<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\RedirectsOutOfRangePagination;
use App\Http\Controllers\Controller;
use App\Models\FacilityInterestLead;
use Illuminate\Http\Request;

// 20 Eylul 2026: kullanicinin talebi - on kayitli kurumlardaki "Bilgi ve
// Fiyat Talep Et" formundan gelen kayitlarin listesi. Amac: admin'in
// kurumlari anlasmali olmaya ikna ederken elinde somut talep verisi
// olmasi ("bakin, sitemizden size zaten X aile soru soruyor").
class FacilityInterestLeadController extends Controller
{
    use RedirectsOutOfRangePagination;

    public function index(Request $request)
    {
        $query = FacilityInterestLead::with('facility.city');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $leads = $query->latest()->paginate(30)->withQueryString();

        if ($redirect = $this->redirectIfPageOutOfRange($request, $leads)) {
            return $redirect;
        }

        return view('admin.facility-interest-leads.index', compact('leads'));
    }

    public function update(Request $request, FacilityInterestLead $facilityInterestLead)
    {
        $request->validate(['status' => 'required|in:yeni,iletisime_gecildi,anlasmali_oldu,ilgilenmiyor']);
        $facilityInterestLead->update(['status' => $request->status]);

        return back()->with('success', 'Durum güncellendi.');
    }
}
