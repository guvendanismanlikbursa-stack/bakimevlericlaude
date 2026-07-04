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

        $bankInfo = [
            'bank_name' => Setting::get('bank_name', config('platform.default_bank_info.bank_name')),
            'account_holder' => Setting::get('bank_account_holder', config('platform.default_bank_info.account_holder')),
            'iban' => Setting::get('bank_iban', config('platform.default_bank_info.iban')),
        ];

        $quotePrice = Setting::get('quote_price', config('platform.default_quote_price'));

        return view("themes.{$brand['theme']}.facility.wallet", compact('facility', 'topups', 'logs', 'bankInfo', 'quotePrice'));
    }

    public function store(Request $request)
    {
        $user = FacilityUser::findOrFail(session('facility_user_id'));

        $data = $request->validate([
            'amount' => 'required|numeric|min:1',
            'note' => 'nullable|string|max:500',
            'receipt' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120',
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
