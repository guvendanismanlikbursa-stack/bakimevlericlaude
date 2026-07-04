<?php

namespace App\Http\Controllers\Facility;

use App\Http\Controllers\Controller;
use App\Models\FacilityUser;
use App\Models\SubscriptionPackage;
use App\Models\WalletTopup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

// canliyaal projesinden tasindi: paket katalogu. Satin alma, mevcut
// bakiye yukleme (dekont onay) akisina baglanir; admin onayladiginda
// paketin tutari + bonus teklif hakki kuruma islenir.
class SubscriptionController extends Controller
{
    public function index()
    {
        $user = FacilityUser::with('facility')->findOrFail(session('facility_user_id'));

        // Paket katalogu nadiren degisir; admin panelde CRUD yapilinca temizlenir.
        $packages = Cache::remember('subscription_packages:active', 3600, function () {
            return SubscriptionPackage::active()->orderBy('sort_order')->orderBy('price')->get();
        });

        $myTopups = WalletTopup::where('facility_id', $user->facility_id)
            ->whereNotNull('subscription_package_id')
            ->latest()
            ->limit(10)
            ->get();

        return view('themes._shared.facility.packages', compact('user', 'packages', 'myTopups'));
    }

    public function store(Request $request, SubscriptionPackage $package)
    {
        abort_unless($package->is_active, 404);

        $user = FacilityUser::findOrFail(session('facility_user_id'));

        $data = $request->validate([
            'receipt' => 'required|image|max:4096',
        ]);

        $path = $request->file('receipt')->store('dekontlar', 'public');

        WalletTopup::create([
            'facility_id' => $user->facility_id,
            'facility_user_id' => $user->id,
            'subscription_package_id' => $package->id,
            'amount' => $package->price,
            'receipt_path' => $path,
            'note' => "Paket talebi: {$package->name}",
            'status' => 'pending',
        ]);

        return back()->with('success', 'Paket talebiniz alindi, admin onayindan sonra bakiyenize islenecek.');
    }
}
