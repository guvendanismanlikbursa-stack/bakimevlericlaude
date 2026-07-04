<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BalanceLog;
use App\Models\Facility;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BalanceController extends Controller
{
    /**
     * Admin manuel olarak bakiye veya teklif hakki artirip azaltabilir.
     */
    public function adjust(Request $request, Facility $facility)
    {
        $data = $request->validate([
            'balance_delta' => 'nullable|numeric|min:-999999|max:999999',
            'credits_delta' => 'nullable|integer|min:-100000|max:100000',
            'note' => 'nullable|string|max:500',
        ]);

        $balanceDelta = (float) ($data['balance_delta'] ?? 0);
        $creditsDelta = (int) ($data['credits_delta'] ?? 0);

        if ($balanceDelta == 0 && $creditsDelta == 0) {
            return back()->withErrors(['balance' => 'Bir tutar veya hak sayisi giriniz.']);
        }

        $result = DB::transaction(function () use ($facility, $balanceDelta, $creditsDelta, $data) {
            $facility = Facility::whereKey($facility->id)->lockForUpdate()->firstOrFail();

            $newBalance = max(0, (float) $facility->balance + $balanceDelta);
            $newCredits = max(0, (int) $facility->free_quote_credits + $creditsDelta);

            $actualBalanceDelta = $newBalance - (float) $facility->balance;
            $actualCreditsDelta = $newCredits - (int) $facility->free_quote_credits;

            if ($actualBalanceDelta == 0 && $actualCreditsDelta == 0) {
                return false;
            }

            $facility->update([
                'balance' => $newBalance,
                'free_quote_credits' => $newCredits,
            ]);

            BalanceLog::create([
                'facility_id' => $facility->id,
                'type' => $actualBalanceDelta != 0 ? 'admin_adjust_balance' : 'admin_adjust_credits',
                'amount' => $actualBalanceDelta,
                'credits_amount' => $actualCreditsDelta,
                'balance_after' => $facility->balance,
                'credits_after' => $facility->free_quote_credits,
                'admin_id' => session('admin_id'),
                'note' => $data['note'] ?? 'Admin manuel düzenleme.',
            ]);

            return true;
        });

        if (! $result) {
            return back()->withErrors(['balance' => 'Bu islem mevcut bakiye/hak degerini degistirmiyor.']);
        }

        return back()->with('success', 'Kurum bakiyesi/hak sayüsü güncellendi.');
    }
}
