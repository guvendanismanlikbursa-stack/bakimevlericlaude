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
            'quote_price_override' => 'nullable|numeric|min:0',
            'clear_quote_price_override' => 'nullable|boolean',
        ]);

        $balanceDelta = (float) ($data['balance_delta'] ?? 0);
        $creditsDelta = (int) ($data['credits_delta'] ?? 0);
        $overrideChanged = false;

        if ($request->boolean('clear_quote_price_override')) {
            $facility->update(['quote_price_override' => null]);
            $overrideChanged = true;
        } elseif ($request->filled('quote_price_override')) {
            $facility->update(['quote_price_override' => $data['quote_price_override']]);
            $overrideChanged = true;
        }

        if ($balanceDelta == 0 && $creditsDelta == 0) {
            return $overrideChanged
                ? back()->with('success', 'Kuruma özel teklif ücreti güncellendi.')
                : back()->withErrors(['balance' => 'Bir tutar veya hak sayisi giriniz.']);
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

        return back()->with('success', 'Kurum bakiyesi/hak sayısı güncellendi.');
    }

    /**
     * 25 Agustos 2026: kullanicinin talebi - "Bakiye / Hak Gecmisi"
     * tablosundaki tek tek kayitlar (ör. yanlislikla iki kez eklenen
     * sahiplenme bonusu) dogrudan duzenlenebilsin/silinebilsin istendi.
     * Silme, o kaydin etkisini (tutar/hak) mevcut bakiyeden geri cikartir;
     * duzenleme, eski ile yeni deger arasindaki farki mevcut bakiyeye
     * uygular. Ikisi de negatife dusmeyi engeller (adjust() ile ayni kural).
     */
    public function updateLog(Request $request, Facility $facility, BalanceLog $balanceLog)
    {
        abort_if($balanceLog->facility_id !== $facility->id, 404);

        $data = $request->validate([
            'amount' => 'nullable|numeric|min:-999999|max:999999',
            'credits_amount' => 'nullable|integer|min:-100000|max:100000',
            'note' => 'nullable|string|max:500',
        ]);

        $newAmount = (float) ($data['amount'] ?? 0);
        $newCredits = (int) ($data['credits_amount'] ?? 0);

        DB::transaction(function () use ($facility, $balanceLog, $newAmount, $newCredits, $data) {
            $locked = Facility::whereKey($facility->id)->lockForUpdate()->firstOrFail();

            $amountDelta = $newAmount - (float) $balanceLog->amount;
            $creditsDelta = $newCredits - (int) $balanceLog->credits_amount;

            $newBalance = max(0, (float) $locked->balance + $amountDelta);
            $newFacilityCredits = max(0, (int) $locked->free_quote_credits + $creditsDelta);

            $locked->update([
                'balance' => $newBalance,
                'free_quote_credits' => $newFacilityCredits,
            ]);

            $balanceLog->update([
                'amount' => $newAmount,
                'credits_amount' => $newCredits,
                'balance_after' => $newBalance,
                'credits_after' => $newFacilityCredits,
                'note' => $data['note'] ?? $balanceLog->note,
            ]);
        });

        return back()->with('success', 'Hareket kaydı güncellendi.');
    }

    public function destroyLog(Facility $facility, BalanceLog $balanceLog)
    {
        abort_if($balanceLog->facility_id !== $facility->id, 404);

        DB::transaction(function () use ($facility, $balanceLog) {
            $locked = Facility::whereKey($facility->id)->lockForUpdate()->firstOrFail();

            $locked->update([
                'balance' => max(0, (float) $locked->balance - (float) $balanceLog->amount),
                'free_quote_credits' => max(0, (int) $locked->free_quote_credits - (int) $balanceLog->credits_amount),
            ]);

            $balanceLog->delete();
        });

        return back()->with('success', 'Hareket kaydı silindi ve bakiye/hak buna göre güncellendi.');
    }
}
