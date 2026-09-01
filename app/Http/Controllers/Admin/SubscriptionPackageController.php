<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPackage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SubscriptionPackageController extends Controller
{
    public function index(Request $request)
    {
        $packages = SubscriptionPackage::orderBy('sort_order')->orderBy('price')->get();
        // 1 Eylul 2026: kullanicinin talebi uzerine yapilan denetimde
        // bulunan gercek eksik - update() route'u/aksiyonu zaten calisir
        // durumdaydi ama ekranda hic "Duzenle" linki/formu yoktu (ör. bir
        // paketin fiyatini duzeltmek icin tek yol silip yeniden eklemekti,
        // bu da sira/gecmisi bozuyordu). AYNI ContentPageController::index()
        // deseni uygulandi.
        $editingPackage = $request->filled('edit') ? SubscriptionPackage::find($request->query('edit')) : null;

        return view('admin.packages.index', compact('packages', 'editingPackage'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:150',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'bonus_quote_credits' => 'nullable|integer|min:0',
            'duration_days' => 'nullable|integer|min:1',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);
        $data['is_active'] = $request->boolean('is_active', true);
        $data['bonus_quote_credits'] = $data['bonus_quote_credits'] ?? 0;
        $data['sort_order'] = $data['sort_order'] ?? 0;

        $package = SubscriptionPackage::create($data);
        log_admin_event('subscription_package_created', $package);
        Cache::forget('subscription_packages:active');

        return back()->with('success', 'Paket eklendi.');
    }

    public function update(Request $request, SubscriptionPackage $package)
    {
        $data = $request->validate([
            'name' => 'required|string|max:150',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'bonus_quote_credits' => 'nullable|integer|min:0',
            'duration_days' => 'nullable|integer|min:1',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);
        $data['is_active'] = $request->boolean('is_active', true);
        $data['bonus_quote_credits'] = $data['bonus_quote_credits'] ?? 0;
        $data['sort_order'] = $data['sort_order'] ?? $package->sort_order;

        $package->update($data);
        log_admin_event('subscription_package_updated', $package);
        Cache::forget('subscription_packages:active');

        return back()->with('success', 'Paket guncellendi.');
    }

    public function destroy(SubscriptionPackage $package)
    {
        log_admin_event('subscription_package_deleted', $package, ['name' => $package->name]);
        $package->delete();
        Cache::forget('subscription_packages:active');

        return back()->with('success', 'Paket silindi.');
    }
}
