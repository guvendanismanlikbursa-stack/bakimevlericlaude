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

        $path = $request->file('receipt')->store('topups', 'public');

        WalletTopup::create([
            'facility_id' => $user->facility_id,
            'facility_user_id' => $user->id,
            'amount' => $data['amount'],
            'receipt_path' => $path,
            'note' => $data['note'] ?? null,
            'status' => 'pending',
        ]);

        return back()->with('success', 'Bakiye yükleme talebiniz alındı, admin onayından sonra bakiyenize yansıyacak.');
    }
}
