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

            $package = $topup->subscription_package_id ? $topup->subscriptionPackage : null;
            if ($package && $package->bonus_quote_credits > 0) {
                $facility->increment('free_quote_credits', $package->bonus_quote_credits);
            }

            $facility->refresh();

            BalanceLog::create([
                'facility_id' => $facility->id,
                'type' => 'topup_approved',
                'amount' => $topup->amount,
                'credits_amount' => $package->bonus_quote_credits ?? 0,
                'balance_after' => $facility->balance,
                'credits_after' => $facility->free_quote_credits,
                'admin_id' => session('admin_id'),
                'note' => $package ? "Paket onaylandi: {$package->name}" : 'Havale dekontu onaylandi.',
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

        $facilityUser = $topup->facility_user_id ? \App\Models\FacilityUser::find($topup->facility_user_id) : null;
        notify_user($facilityUser, 'topup_rejected', 'Bakiye yükleme talebiniz reddedildi', $data['admin_note'] ?? null);

        return back()->with('success', 'Bakiye yukleme talebi reddedildi.');
    }
}