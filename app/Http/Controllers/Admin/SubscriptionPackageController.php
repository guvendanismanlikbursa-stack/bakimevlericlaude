<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPackage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SubscriptionPackageController extends Controller
{
    // 18 Ekim 2026: kullanıcının talebi - İyilik Hareketi üyelik paketlerinin
    // her ayrıntısı (fiyat, süre, rozet, ücretsiz ay yazısı, mesaj, ek haklar,
    // satıldığı il) admin panelinden düzenlenebilir.
    private const MEMBERSHIP_RULES = [
        'kind' => 'nullable|in:credit,membership',
        'list_price' => 'nullable|numeric|min:0',
        'months' => 'nullable|integer|min:1|max:60',
        'badge_label' => 'nullable|string|max:80',
        'badge_emoji' => 'nullable|string|max:16',
        'free_months_label' => 'nullable|string|max:80',
        'tagline' => 'nullable|string|max:255',
        'features' => 'nullable|string|max:12000',
        // 22 Ekim 2026: kullanıcının talebi - Ekonomik/Standart/Premium üyelik ayrıntıları.
        'audience' => 'nullable|string|max:1000',
        'closing_text' => 'nullable|string|max:1000',
        'photo_limit' => 'nullable|integer|min:1|max:1000',
        'can_upload_video' => 'boolean',
        'only_city_slug' => 'nullable|string|max:80|exists:cities,slug',
        'is_premium' => 'boolean',
    ];

    private function normalizeMembershipFields(Request $request, array $data): array
    {
        $data['kind'] = $data['kind'] ?? 'credit';
        $data['is_premium'] = $request->boolean('is_premium');
        $data['can_upload_video'] = $request->boolean('can_upload_video');
        if ($data['kind'] === 'membership') {
            $data['months'] = $data['months'] ?? 1;
        }

        return $data;
    }

    public function index(Request $request)
    {
        $packages = SubscriptionPackage::orderByRaw("CASE WHEN kind = 'membership' THEN 0 ELSE 1 END")->orderBy('sort_order')->orderBy('price')->get();
        // 1 Eylul 2026: kullanicinin talebi uzerine yapilan denetimde
        // bulunan gercek eksik - update() route'u/aksiyonu zaten calisir
        // durumdaydi ama ekranda hic "Duzenle" linki/formu yoktu (ör. bir
        // paketin fiyatini duzeltmek icin tek yol silip yeniden eklemekti,
        // bu da sira/gecmisi bozuyordu). AYNI ContentPageController::index()
        // deseni uygulandi.
        $editingPackage = $request->filled('edit') ? SubscriptionPackage::find($request->query('edit')) : null;

        $cities = \App\Models\City::orderBy('name')->get(['slug', 'name']);
        $membership = [
            'live' => membership_live(),
            'impact_patients' => \App\Models\Setting::get('membership_impact_patients'),
            'impact_period' => \App\Models\Setting::get('membership_impact_period'),
            'office_image' => \App\Models\Setting::get('membership_office_image'),
            'launch_notice' => membership_launch_notice(),
            'active_members' => \App\Models\Facility::where('is_subscribed', true)->whereNotNull('subscription_package_id')
                ->whereDate('subscription_paid_until', '>=', now()->toDateString())->count(),
        ];

        return view('admin.packages.index', compact('packages', 'editingPackage', 'cities', 'membership'));
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
        ] + self::MEMBERSHIP_RULES);
        $data = $this->normalizeMembershipFields($request, $data);
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
        ] + self::MEMBERSHIP_RULES);
        $data = $this->normalizeMembershipFields($request, $data);
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

    /**
     * Programı yayına alır / yayından kaldırır. Yayına alındığı anda, ödemesi
     * güncel ve panel hesabı olan mevcut üyeler sahipli yapılır (bkz.
     * Facility::grantOwnerFeaturesForMembership()).
     */
    public function toggleProgram(Request $request)
    {
        $live = $request->boolean('live');
        \App\Models\Setting::set('membership_live', $live ? '1' : '0');

        $granted = 0;
        if ($live) {
            \App\Models\Facility::where('is_subscribed', true)->whereNotNull('subscription_package_id')
                ->whereDate('subscription_paid_until', '>=', now()->toDateString())
                ->where('is_claimed', false)->where('is_broker_managed', false)
                ->get()->each(function ($facility) use (&$granted) {
                    $facility->grantOwnerFeaturesForMembership();
                    if ($facility->fresh()->is_claimed) {
                        $granted++;
                    }
                });
        }
        Cache::forget('subscription_packages:active');
        log_admin_event($live ? 'membership_program_published' : 'membership_program_unpublished', null, ['granted_claims' => $granted]);

        return back()->with('success', $live
            ? 'İyilik Hareketi yayına alındı: sayfa, rozetler, panel paketleri ve ücretsiz teklif kuralı artık 3 sitede aktif.'.($granted ? " {$granted} üye kurum sahipli yapıldı." : '')
            : 'İyilik Hareketi yayından kaldırıldı: tüm yeni özellikler gizlendi.');
    }

    /**
     * Şeffaflık sayacı ve hastane ofisi görseli.
     */
    public function updateImpact(Request $request)
    {
        $data = $request->validate([
            'impact_patients' => 'nullable|integer|min:0|max:100000',
            'impact_period' => 'nullable|string|max:60',
            'office_image' => 'nullable|mimes:jpg,jpeg,png,webp|max:6144',
            'remove_office_image' => 'boolean',
            'launch_notice' => 'nullable|string|max:200',
        ]);
        // Boş bırakılırsa açık bir 'gizli' işareti saklanır (boş/NULL değer varsayılan yazıya düşerdi).
        \App\Models\Setting::set('membership_launch_notice', filled($data['launch_notice'] ?? null) ? $data['launch_notice'] : '__gizli__');

        // 18 Ekim 2026: kullanıcının talebi - "bakım saati diye bir şeye ihtiyaç yok", yalnızca hasta sayısı.
        foreach (['impact_patients', 'impact_period'] as $key) {
            \App\Models\Setting::set('membership_'.$key, $data[$key] ?? null);
        }

        if ($request->boolean('remove_office_image')) {
            \App\Models\Setting::set('membership_office_image', null);
        } elseif ($request->hasFile('office_image')) {
            // facilities/ klasörü 3 sitede ortak (diğer iki sitede ana siteye
            // symlink) - görsel hangi domainden yüklenirse yüklensin 3'ünde görünür.
            $path = $request->file('office_image')->store('facilities/membership', 'public');
            if (! $path || ! \Illuminate\Support\Facades\Storage::disk('public')->exists($path)) {
                return back()->withErrors(['office_image' => 'Görsel kaydedilemedi, lütfen tekrar deneyin.']);
            }
            \App\Models\Setting::set('membership_office_image', $path);
        }

        log_admin_event('membership_impact_updated', null, $request->only(['impact_patients', 'impact_period']));

        return back()->with('success', 'Şeffaflık bilgileri kaydedildi.');
    }
}
