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

        // 21 Temmuz 2026: dekont finansal bilgi iceriyor - 'public' yerine
        // 'local' diskte, sadece admin.documents.show route'u uzerinden servis edilir.
        // 3 Agustos 2026: bkz. FacilityClaimController ayni yorum - yazimdan
        // sonra dosyanin gercekten var oldugu dogrulanir, basarisizsa
        // topup hic olusturulmaz.
        try {
            $path = $request->file('receipt')->store('dekontlar', 'local');
            if (! $path || ! \Illuminate\Support\Facades\Storage::disk('local')->exists($path)) {
                throw new \RuntimeException('Dekont diske yazildiktan sonra dogrulanamadi.');
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Paket satin alma dekontu kaydedilemedi: ' . $e->getMessage());
            \Sentry\captureException($e);

            return back()->withErrors(['receipt' => 'Dekont yüklenirken bir sorun oluştu, lütfen tekrar deneyin.'])->withInput();
        }

        // 21 Temmuz 2026: bonus_quote_credits (ve paket adi) burada, satin alma
        // aninda dondurulur - amount (fiyat) zaten oyle davraniyordu. Admin
        // onaydan ONCE paketi duzenler/silerse kurum yine talep ettigi kredi
        // miktarini alir, canli paket verisine bagimli kalmaz (bkz. migration).
        WalletTopup::create([
            'facility_id' => $user->facility_id,
            'facility_user_id' => $user->id,
            'subscription_package_id' => $package->id,
            'amount' => $package->price,
            'bonus_quote_credits_snapshot' => $package->bonus_quote_credits,
            'package_name_snapshot' => $package->name,
            'receipt_path' => $path,
            'note' => "Paket talebi: {$package->name}",
            'status' => 'pending',
        ]);

        return back()->with('success', 'Paket talebiniz alindi, admin onayindan sonra bakiyenize islenecek.');
    }
}
