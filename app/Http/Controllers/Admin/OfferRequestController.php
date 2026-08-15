<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\RedirectsOutOfRangePagination;
use App\Http\Controllers\Controller;
use App\Models\FacilityUser;
use App\Models\FamilyUser;
use App\Models\OfferRequest;
use Illuminate\Http\Request;

class OfferRequestController extends Controller
{
    use RedirectsOutOfRangePagination;

    public function index(Request $request)
    {
        // Admin liste ekraninda sadece basvurunun kendisini (form) ve
        // kurumun verdigi cevabi (quotes) gorur; aile/kurum arasindaki
        // mesajlasma burada gosterilmez. Sikayet durumunda admin, asagidaki
        // showMessages() ile TEK bir talebin mesaj gecmisini ayrica
        // inceleyip gerekirse aile/kurum hesabini askiya alabilir.
        $query = OfferRequest::with(['facility', 'quotes.facility']);

        if ($request->filled('brand')) {
            $query->where('brand', $request->brand);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->boolean('bulk_only')) {
            $query->whereNotNull('batch_id');
        }

        $requests = $query->latest()->paginate(20)->withQueryString();

        if ($redirect = $this->redirectIfPageOutOfRange($request, $requests)) {
            return $redirect;
        }

        $brands = config('brands.brands');

        return view('admin.offer-requests.index', compact('requests', 'brands'));
    }

    public function update(Request $request, OfferRequest $offerRequest)
    {
        $request->validate(['status' => 'required|in:new,contacted,closed']);
        $oldStatus = $offerRequest->status;
        $offerRequest->update(['status' => $request->status]);
        log_admin_event('offer_request_status_changed', $offerRequest, ['old_status' => $oldStatus, 'new_status' => $request->status]);

        // 12 Agustos 2026: kullanicinin talebi - talep admin tarafindan
        // kapatildiginda gonderen kisi haberdar olmali. "yeni"/"gorusuldu"
        // durumlari zaten kurumla dogrudan mesajlasma/teklif surecinin bir
        // parcasi oldugu icin (aile zaten sistemden haberdar), sadece
        // KAPANIS icin ayrica mail atiyoruz.
        if ($request->status === 'closed' && $oldStatus !== 'closed') {
            $email = $offerRequest->email ?: $offerRequest->familyUser?->email;
            if ($email) {
                try {
                    \Illuminate\Support\Facades\Mail::to($email)->sendNow(new \App\Mail\OfferRequestClosedMail($offerRequest));
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning('Teklif talebi kapatma maili gonderilemedi: ' . $e->getMessage(), ['offer_request_id' => $offerRequest->id]);
                    notify_admin_of_exception($e);
                }
            }
        }

        return back()->with('success', 'Durum güncellendi.');
    }

    /**
     * Sikayet/anlasmazlik incelemesi icin: bu TEK talebin aile-kurum mesaj
     * gecmisi + varsa taraflari askiya alma aksiyonlari. Liste ekraninda
     * BUNA link vermek disinda mesajlar hicbir yerde otomatik gosterilmez.
     */
    public function showMessages(OfferRequest $offerRequest)
    {
        $offerRequest->loadMissing(['facility.facilityUsers', 'familyUser', 'quotes.facility', 'messages']);

        return view('admin.offer-requests.messages', compact('offerRequest'));
    }

    public function suspendFamily(OfferRequest $offerRequest)
    {
        $family = $offerRequest->familyUser;
        abort_unless($family, 404);

        $newStatus = $family->status === 'active' ? 'suspended' : 'active';
        $family->update(['status' => $newStatus]);

        log_admin_event(
            $newStatus === 'suspended' ? 'family_user_suspended' : 'family_user_reactivated',
            $family,
            ['offer_request_id' => $offerRequest->id]
        );

        return back()->with('success', $newStatus === 'suspended' ? 'Aile hesabı askıya alındı.' : 'Aile hesabı yeniden aktifleştirildi.');
    }

    public function suspendFacility(OfferRequest $offerRequest)
    {
        $offerRequest->loadMissing('facility.facilityUsers');
        $facility = $offerRequest->facility;
        abort_unless($facility, 404);

        $newStatus = $facility->facilityUsers->firstWhere('status', 'active') ? 'suspended' : 'active';
        FacilityUser::where('facility_id', $facility->id)->update(['status' => $newStatus]);

        log_admin_event(
            $newStatus === 'suspended' ? 'facility_users_suspended' : 'facility_users_reactivated',
            $facility,
            ['offer_request_id' => $offerRequest->id]
        );

        return back()->with('success', $newStatus === 'suspended' ? 'Kurum yetkilisi hesabı/hesapları askıya alındı.' : 'Kurum yetkilisi hesabı/hesapları yeniden aktifleştirildi.');
    }
}
