<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Facility;
use App\Models\FacilityCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

class EngagementController extends Controller
{
    public function wizard(Request $request)
    {
        $brand = current_brand();
        $sections = service_sections();
        $activeSection = active_service_section($request->query('bolum'), $brand);
        $cities = City::orderBy('name')->get();
        $scope = $activeSection['scopes'] ?: $brand['category_scope'];
        $categories = FacilityCategory::whereIn('brand_scope', $scope)->orderBy('name')->get();
        $districtMap = $cities->mapWithKeys(fn ($city) => [$city->slug => districts_for_city($city->name)]);
        $sectionServices = $activeSection['features'] ?? [];

        return view("themes.{$brand['theme']}.engagement.wizard", compact(
            'brand',
            'sections',
            'activeSection',
            'cities',
            'categories',
            'districtMap',
            'sectionServices'
        ));
    }

    public function compare(Request $request)
    {
        return $this->board('compare');
    }

    public function favorites(Request $request)
    {
        return $this->board('favorites');
    }

    public function bulkQuote(Request $request)
    {
        return $this->board('bulk-quote');
    }

    /**
     * Favoriler tarayicida localStorage'da tutulur (kisisel liste), ama
     * "Kurum Performans Sayfasi"nda goruntulenecek toplam favori sayisi icin
     * bu sayac kullanilir. Ayni tarayicinin ayni kurumu birden fazla kez
     * favoriye ekleyip cikarmasi sayaci sismesin diye uzun omurlu bir
     * cerez ile "bu tarayici bu kurumu daha once favoriledi mi" kontrolu
     * yapilir: ilk eklemede +1 sayilir, sonraki ekleme/cikarmalar sayaci
     * degistirmez (kac FARKLI tarayicinin ilgi gosterdiginin yaklasik
     * bir olcusudur, "su an favoride olan sayisi" degildir).
     */
    /**
     * 17 Agustos 2026: kullanicinin talebi - sahiplenilmemis kurum
     * sayfasindaki dogrudan "Kurumu Ara"/"WhatsApp" butonlarina tiklanmasini
     * kaydeder (bkz. FacilityEngagementEvent, Facility::engagementStats30d).
     * Ayni ziyaretcinin ayni gun icinde tekrar tekrar tiklamasi sayiyi
     * sismesin diye goruntulenme sayaciyla AYNI desen (oturumda 24 saat
     * tekillestirme) kullanilir.
     */
    public function trackContactClick(Request $request)
    {
        // 17 Agustos 2026: kullanicinin bildirdigi hata - marka-onekli
        // (site/{brand}/...) rotalarda controller metoduna ekstra bir
        // "string $slug" parametresi tanimlamak, Laravel'in bu projedeki
        // rota parametrelerini (brand/slug) YANLIS SIRAYLA baglamasina yol
        // aciyordu ($slug degiskeni gercekte 'bakimeviara' (marka) degerini
        // aliyordu, testle kanitlandi). Ayni sebeple kardes
        // FacilityImageController::markViewed() de $request->route(...) ile
        // okuyor - ayni, kanitlanmis calisan desen burada da kullanildi.
        $slug = $request->route('slug');
        $data = $request->validate(['type' => 'required|in:phone_click,whatsapp_click']);

        $brand = current_brand();
        $facility = Facility::published()->forBrand($brand['category_scope'])->where('slug', $slug)->firstOrFail();

        $sessionKey = "{$data['type']}_{$facility->id}";
        $lastAt = session($sessionKey);

        if (! $lastAt || now()->diffInHours($lastAt) >= 24) {
            // GUVENLIK: bkz. FacilityController::show ayni tarihli yorum -
            // bu ikincil analitik yazma basarisiz olsa bile butonun kendi
            // islevini (tel:/wa.me acilmasi) engellememeli.
            try {
                $facility->engagementEvents()->create(['type' => $data['type']]);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Iletisim tiklama olayi kaydedilemedi: '.$e->getMessage(), ['facility_id' => $facility->id]);
            }
            session([$sessionKey => now()]);
        }

        return response()->json(['ok' => true]);
    }

    public function toggleFavoriteCount(Request $request)
    {
        // 17 Agustos 2026: bkz. trackContactClick() ayni tarihli yorum - ayni
        // parametre-baglama hatasindan (marka-onekli rotada ekstra "string
        // $slug" parametresi) kaynakli, ayni cozum uygulandi.
        $slug = $request->route('slug');
        $brand = current_brand();
        $facility = Facility::published()->forBrand($brand['category_scope'])->where('slug', $slug)->firstOrFail();

        if ($request->input('action') !== 'remove') {
            $cookieName = "fav_seen_{$facility->id}";

            if (! $request->cookie($cookieName)) {
                $facility->increment('favorites_count');
                Cookie::queue(Cookie::forever($cookieName, '1'));
            }
        }

        return response()->json(['ok' => true]);
    }

    private function board(string $mode)
    {
        $brand = current_brand();
        $facilities = Facility::discoverable()
            ->forBrand($brand['category_scope'])
            ->with(['city', 'category', 'images'])
            ->orderByDesc('is_featured')
            ->orderByDesc('rating')
            ->limit(80)
            ->get();

        // 7 Eylul 2026: kullanicinin talebi - gercek fiyat sadece giris
        // yapmis ailelere gorunmeli. Bu veri JS tarafindan sayfa kaynagina
        // GOMULU JSON olarak render edildigi icin, Blade'de gizlemek
        // YETERSIZ olurdu (sayfa kaynaginda hala okunabilir kalirdi) -
        // fiyat CONTROLLER seviyesinde, JSON'a hic konmadan degistirilir.
        $familyLoggedIn = (bool) session('family_user_id');

        $facilitiesForJs = $facilities->map(function (Facility $facility) use ($familyLoggedIn) {
            $section = service_section_for_scope($facility->category?->brand_scope);
            $image = $facility->primaryImage();

            return [
                'id' => $facility->id,
                'name' => $facility->name,
                'slug' => $facility->slug,
                'city' => $facility->city?->name,
                'district' => $facility->district,
                'category' => $facility->category?->name,
                'section' => $section['title'] ?? null,
                // 15 Agustos 2026: kullanicinin "asla hata kalmamali" talebi
                // uzerine yapilan denetimde bulundu - daha once duzeltilen
                // "★ 0.0" gosterim hatasinin (rating=0 iken sanki gercek bir
                // puanmis gibi gorunmesi) bu Karsilastir/Favoriler sayfasinda
                // TEKRARI - burasi tek istisna olarak rating>0 kontrolu
                // yapmadan formatliyordu. null donup JS tarafinda "Puan yok"
                // gosteriliyor (bkz. board.blade.php).
                'rating' => $facility->rating > 0 ? number_format((float) $facility->rating, 1) : null,
                'price_min' => $familyLoggedIn
                    ? ($facility->price_min ? number_format($facility->price_min, 0, ',', '.') . ' TL' : 'Fiyat iste')
                    : '🔒 Fiyat için giriş yapın',
                'capacity' => $facility->capacity ?: '-',
                'services' => array_slice($facility->services ?? [], 0, 5),
                'description' => $facility->description,
                'image' => $image ? facility_asset($image->path) : null,
                'url' => brand_route('facilities.show', ['slug' => $facility->slug]),
            ];
        })->values();

        return view("themes.{$brand['theme']}.engagement.{$mode}", compact('brand', 'facilitiesForJs'));
    }
}