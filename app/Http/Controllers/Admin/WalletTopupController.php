<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BalanceLog;
use App\Models\WalletTopup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WalletTopupController extends Controller
{
    public function index(Request $request)
    {
        $query = WalletTopup::with('facility');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        } else {
            $query->where('status', 'pending');
        }

        $topups = $query->latest()->paginate(15)->withQueryString();

        return view('admin.topups.index', compact('topups'));
    }

    public function approve(WalletTopup $topup)
    {
        DB::transaction(function () use ($topup) {
            $topup = WalletTopup::whereKey($topup->id)->lockForUpdate()->firstOrFail();
            abort_if($topup->status !== 'pending', 400, 'Bu talep zaten islenmis.');

            $facility = $topup->facility()->lockForUpdate()->firstOrFail();
            $facility->increment('balance', $topup->amount);

            // 21 Temmuz 2026: canli $package->bonus_quote_credits yerine satin
            // alma aninda dondurulmus snapshot okunuyor - admin onaydan once
            // paketi duzenlerse/silerse kurum vaat edilenden farkli/sifir kredi
            // almasin diye (bkz. migration + Facility/SubscriptionController).
            $bonusCredits = $topup->bonus_quote_credits_snapshot ?? 0;
            if ($bonusCredits > 0) {
                $facility->increment('free_quote_credits', $bonusCredits);
            }

            $facility->refresh();

            BalanceLog::create([
                'facility_id' => $facility->id,
                'type' => 'topup_approved',
                'amount' => $topup->amount,
                'credits_amount' => $bonusCredits,
                'balance_after' => $facility->balance,
                'credits_after' => $facility->free_quote_credits,
                'admin_id' => session('admin_id'),
                'note' => $topup->package_name_snapshot ? "Paket onaylandi: {$topup->package_name_snapshot}" : 'Havale dekontu onaylandi.',
            ]);

            $topup->update([
                'status' => 'approved',
                'reviewed_by' => session('admin_id'),
                'reviewed_at' => now(),
            ]);
        });

        log_admin_event('wallet_topup_approved', $topup, ['facility_id' => $topup->facility_id, 'amount' => $topup->amount]);

        $facilityUser = $topup->facility_user_id ? \App\Models\FacilityUser::find($topup->facility_user_id) : null;
        notify_user($facilityUser, 'topup_approved', 'Bakiye yükleme onaylandı', number_format($topup->amount, 2, ',', '.').'₺ hesabınıza işlendi.');

        return back()->with('success', 'Bakiye yukleme onaylandi, kurum bakiyesine islendi.');
    }

    public function reject(Request $request, WalletTopup $topup)
    {
        $data = $request->validate(['admin_note' => 'nullable|string|max:1000']);

        DB::transaction(function () use ($topup, $data) {
            $topup = WalletTopup::whereKey($topup->id)->lockForUpdate()->firstOrFail();
            abort_if($topup->status !== 'pending', 400, 'Bu talep zaten islenmis.');

            $topup->update([
                'status' => 'rejected',
                'admin_note' => $data['admin_note'] ?? null,
                'reviewed_by' => session('admin_id'),
                'reviewed_at' => now(),
            ]);
        });

        log_admin_event('wallet_topup_rejected', $topup, ['admin_note' => $data['admin_note'] ?? null]);

        // 21 Temmuz 2026: admin gerekce yazmadan reddederse kurum bos govdeli
        // bir bildirim aliyordu - "neden reddedildi" hicbir zaman anlasilmiyordu.
        $facilityUser = $topup->facility_user_id ? \App\Models\FacilityUser::find($topup->facility_user_id) : null;
        notify_user($facilityUser, 'topup_rejected', 'Bakiye yükleme talebiniz reddedildi', $data['admin_note'] ?? 'Detay belirtilmedi, sorularınız için bize ulaşabilirsiniz.');

        return back()->with('success', 'Bakiye yukleme talebi reddedildi.');
    }
}