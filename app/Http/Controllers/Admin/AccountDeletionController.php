<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccountDeletionRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

// 19 Agustos 2026: kullanicinin talebi - KVKK "silme hakki" icin aile/kurum
// yetkilisi kendi panelinden hesap silme TALEBINDE bulunabiliyor (bkz.
// Family\ProfileController::destroy(), Facility\ProfileController::destroy()),
// ama gercek silme (kisisel veri anonimlestirme) islemini burada BIR ADMIN
// onaylayip yapar - platformun diger tum "kullanici talebi -> admin onayi"
// akislariyla (sahiplenme, kayit, bakiye yukleme) ayni desen.
class AccountDeletionController extends Controller
{
    public function index(Request $request)
    {
        $query = AccountDeletionRequest::with('requestable', 'processedBy');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        } else {
            $query->where('status', 'pending');
        }

        $requests = $query->latest('requested_at')->paginate(20)->withQueryString();

        return view('admin.account-deletions.index', compact('requests'));
    }

    public function approve(AccountDeletionRequest $accountDeletionRequest)
    {
        abort_if($accountDeletionRequest->status !== 'pending', 400, 'Bu talep zaten işlenmiş.');

        $user = $accountDeletionRequest->requestable;
        abort_if(! $user, 404, 'Hesap zaten silinmiş/bulunamadı.');

        $user->update([
            'name' => 'Silinmiş Kullanıcı',
            'email' => 'silinmis-'.$user->id.'-'.time().'@silinmis.local',
            'phone' => null,
            'password' => Hash::make(Str::random(40)),
            'status' => 'deleted',
            'avatar_url' => null,
            'google_id' => null,
        ]);

        $accountDeletionRequest->update([
            'status' => 'completed',
            'processed_at' => now(),
            'processed_by' => session('admin_id'),
        ]);

        log_admin_event('account_deletion_approved', $accountDeletionRequest, [
            'requestable_type' => $accountDeletionRequest->requestable_type,
            'requestable_id' => $accountDeletionRequest->requestable_id,
        ]);

        return back()->with('success', 'Hesap silindi (kişisel veriler anonimleştirildi).');
    }

    public function reject(Request $request, AccountDeletionRequest $accountDeletionRequest)
    {
        abort_if($accountDeletionRequest->status !== 'pending', 400, 'Bu talep zaten işlenmiş.');

        $data = $request->validate(['admin_note' => 'nullable|string|max:1000']);

        $accountDeletionRequest->update([
            'status' => 'rejected',
            'processed_at' => now(),
            'processed_by' => session('admin_id'),
            'admin_note' => $data['admin_note'] ?? null,
        ]);

        log_admin_event('account_deletion_rejected', $accountDeletionRequest);

        return back()->with('success', 'Talep reddedildi, hesap silinmedi.');
    }
}
