<?php

namespace App\Http\Controllers\Facility;

use App\Http\Controllers\Controller;
use App\Models\FacilityUser;
use App\Models\Setting;
use App\Models\WalletTopup;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function index()
    {
        $brand = app('currentBrand');
        $user = FacilityUser::with('facility')->findOrFail(session('facility_user_id'));
        $facility = $user->facility;

        $topups = $facility->walletTopups()->latest()->get();
        $logs = $facility->balanceLogs()->limit(30)->get();

        $slug = $brand['slug'];
        $bankInfo = [
            'bank_name' => Setting::get("bank_name_{$slug}", config("platform.default_bank_info.{$slug}.bank_name")),
            'account_holder' => Setting::get("bank_account_holder_{$slug}", config("platform.default_bank_info.{$slug}.account_holder")),
            'iban' => Setting::get("bank_iban_{$slug}", config("platform.default_bank_info.{$slug}.iban")),
        ];

        $quotePrice = $facility->effectiveQuotePrice();

        return view("themes.{$brand['theme']}.facility.wallet", compact('facility', 'topups', 'logs', 'bankInfo', 'quotePrice'));
    }

    public function store(Request $request)
    {
        $user = FacilityUser::findOrFail(session('facility_user_id'));

        $data = $request->validate([
            'amount' => 'required|numeric|min:1',
            'note' => 'nullable|string|max:500',
            'receipt' => 'required|file|mimes:jpg,jpeg,png,webp,pdf|max:8192',
        ]);

        // 21 Temmuz 2026: dekont finansal bilgi iceriyor - 'public' yerine
        // 'local' diskte, sadece admin.documents.show route'u uzerinden servis edilir.
        // 3 Agustos 2026: bkz. FacilityClaimController ayni yorum - yazimdan
        // sonra dosyanin gercekten var oldugu dogrulanir, basarisizsa
        // topup hic olusturulmaz.
        try {
            $path = $request->file('receipt')->store('topups', 'local');
            if (! $path || ! \Illuminate\Support\Facades\Storage::disk('local')->exists($path)) {
                throw new \RuntimeException('Dekont diske yazildiktan sonra dogrulanamadi.');
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Bakiye yukleme dekontu kaydedilemedi: ' . $e->getMessage());
            \Sentry\captureException($e);

            return back()->withErrors(['receipt' => 'Dekont yüklenirken bir sorun oluştu, lütfen tekrar deneyin.'])->withInput();
        }

        WalletTopup::create([
            'facility_id' => $user->facility_id,
            'facility_user_id' => $user->id,
            'amount' => $data['amount'],
            'receipt_path' => $path,
            'note' => $data['note'] ?? null,
            'status' => 'pending',
        ]);

        \App\Models\Admin::all()->each(fn ($admin) => notify_user(
            $admin,
            'topup_requested',
            'Yeni bakiye yükleme talebi',
            $user->facility->name.' '.number_format($data['amount'], 2, ',', '.').' TL bakiye yükleme talebi gönderdi.',
        ));

        return back()->with('success', 'Bakiye yükleme talebiniz alındı, admin onayından sonra bakiyenize yansıyacak.');
    }
}
