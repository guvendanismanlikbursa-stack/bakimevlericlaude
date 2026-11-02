<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\RedirectsOutOfRangePagination;
use App\Http\Controllers\Controller;
use App\Models\FamilyUser;
use App\Models\SiteVisit;
use Illuminate\Http\Request;

// "Sitelere giris sayilari" ve "kayit olan ailelerin konumlari" - tek ekran.
class SiteStatsController extends Controller
{
    use RedirectsOutOfRangePagination;

    public function index(Request $request)
    {
        $brands = config('brands.brands');
        $today = now()->toDateString();
        $last7 = now()->subDays(6)->toDateString();
        $last30 = now()->subDays(29)->toDateString();
        $last90 = now()->subDays(89)->toDateString();
        // 26 Ekim 2026: kullanıcının talebi - 3 ay / 6 ay / 1 yıl dönemleri de.
        $last180 = now()->subDays(179)->toDateString();
        $last365 = now()->subDays(364)->toDateString();

        $visitStats = [];
        foreach ($brands as $slug => $brand) {
            $visitStats[$slug] = [
                'name' => $brand['name'],
                'today' => (int) SiteVisit::where('brand', $slug)->where('visit_date', $today)->value('count'),
                'last_7_days' => (int) SiteVisit::where('brand', $slug)->where('visit_date', '>=', $last7)->sum('count'),
                'last_30_days' => (int) SiteVisit::where('brand', $slug)->where('visit_date', '>=', $last30)->sum('count'),
                'last_90_days' => (int) SiteVisit::where('brand', $slug)->where('visit_date', '>=', $last90)->sum('count'),
                'last_180_days' => (int) SiteVisit::where('brand', $slug)->where('visit_date', '>=', $last180)->sum('count'),
                'last_365_days' => (int) SiteVisit::where('brand', $slug)->where('visit_date', '>=', $last365)->sum('count'),
                'all_time' => (int) SiteVisit::where('brand', $slug)->sum('count'),
            ];
            // 1 Kasım 2026: gerçek ziyaretçi (betiği çalıştıran tarayıcı) sayıları; bot dahil sayılar yukarıda korunur.
            foreach (['today' => $today, 'last_7_days' => $last7, 'last_30_days' => $last30, 'last_90_days' => $last90, 'last_180_days' => $last180, 'last_365_days' => $last365, 'all_time' => null] as $k => $bas) {
                $q = SiteVisit::where('brand', $slug);
                if ($bas) {
                    $q->where('visit_date', '>=', $bas);
                }
                $visitStats[$slug]['real'][$k] = (int) $q->sum('real_count');
            }
            // Olağan dışı bot yoğunluğu: bugünkü bot dahil giriş, önceki 7 günün ortalamasının 2 katını geçerse.
            $onceki = SiteVisit::where('brand', $slug)->whereBetween('visit_date', [now()->subDays(7)->toDateString(), now()->subDay()->toDateString()])->pluck('count');
            $ort = $onceki->count() ? $onceki->avg() : 0;
            $visitStats[$slug]['kat'] = $ort > 0 ? round($visitStats[$slug]['today'] / $ort, 1) : null;
            $visitStats[$slug]['engel_bugun'] = \Illuminate\Support\Facades\Schema::hasTable('bot_engel_kayitlari')
                ? (int) \Illuminate\Support\Facades\DB::table('bot_engel_kayitlari')->where('tarih', $today)->where('marka', $slug)->sum('sayi') : 0;
        }
        $gercekBaslangic = SiteVisit::where('real_count', '>', 0)->min('visit_date');
        // 1 Kasım 2026: saatlik bot gözcüsünün bugünkü şüpheli ağları (firma bazında) ve engel listesi.
        $supheliFirmalar = collect();
        $engelliListe = collect();
        $gozcuSon = null;
        if (\Illuminate\Support\Facades\Schema::hasTable('supheli_aglar')) {
            $satirlar = \Illuminate\Support\Facades\DB::table('supheli_aglar')->where('tarih', $today)->get();
            $gozcuSon = $satirlar->max('updated_at');
            $engelliAsn = \Illuminate\Support\Facades\DB::table('engelli_aglar')->where('aktif', true)->whereNotNull('asn')->pluck('asn')->map(fn ($a) => (int) $a)->all();
            $kalici = collect((array) config('bot_kalkani.engelli_aglar', []))->pluck('ad')->implode(' ');
            $supheliFirmalar = $satirlar->groupBy(fn ($r) => $r->asn ? 'AS'.$r->asn : 'ag:'.$r->ag)->map(function ($g, $k) use ($engelliAsn, $kalici) {
                $asn = $g->first()->asn ? (int) $g->first()->asn : null;

                return (object) [
                    'anahtar' => $k, 'asn' => $asn, 'firma' => $g->first()->firma, 'ag' => $g->first()->ag, 'ag_sayisi' => $g->count(),
                    'supheli' => $g->sum('supheli'), 'bot' => $g->sum('bot'), 'insan' => $g->sum('insan'), 'engellenen' => $g->sum('engellenen'),
                    'ip' => $g->sum('ip_sayisi'), 'markalar' => $g->pluck('markalar')->implode(','), 'ua' => $g->sortByDesc('istek')->first()->ornek_ua,
                    'korumali' => $asn ? (\App\Support\BotGozcu::KORUMALI[$asn] ?? null) : null,
                    'engelli' => $asn && (in_array($asn, $engelliAsn, true) || str_contains($kalici, '(AS'.$asn.')')),
                ];
            })->sortByDesc(fn ($f) => $f->supheli + $f->bot)->values()->take(25);
            $engelliListe = \Illuminate\Support\Facades\DB::table('engelli_aglar')->where('aktif', true)->orderByDesc('id')->get();
        }
        // 1 Kasım 2026: engellenen botlar (son 7 gün) - saldırılar görünür kalsın.
        $botEngel = \Illuminate\Support\Facades\Schema::hasTable('bot_engel_kayitlari')
            ? \Illuminate\Support\Facades\DB::table('bot_engel_kayitlari')->where('tarih', '>=', $last7)
                ->selectRaw('anahtar, neden, sum(sayi) as sayi, max(son_gorulme) as son, min(tarih) as ilk, group_concat(distinct marka) as markalar')
                ->groupBy('anahtar', 'neden')
                // 2 Kasım 2026: kullanıcının talebi - liste çok uzuyordu; en son engellenen en üstte,
                // sayfa başına 10 kayıt (ayrı sayfa adı: aile listesinin sayfalamasını etkilemez).
                ->orderByDesc('son')->paginate(10, ['*'], 'bot_sayfa')->withQueryString()->fragment('engellenen-botlar')
            : collect();
        $botEngelGunluk = \Illuminate\Support\Facades\Schema::hasTable('bot_engel_kayitlari')
            ? \Illuminate\Support\Facades\DB::table('bot_engel_kayitlari')->where('tarih', '>=', $last7)->selectRaw('tarih, sum(sayi) as sayi')->groupBy('tarih')->orderBy('tarih')->pluck('sayi', 'tarih')
            : collect();

        // 5 Ekim 2026: kullanicinin talebi - trend grafiginde 90 gunluk secenek
        // de olsun, bu yuzden sorgu artik 90 gune cikti (30/7/1 gorunumler
        // JS tarafinda bu ayni veriden dilimlenir, ekstra sorgu gerekmez).
        $dailySeries = SiteVisit::where('visit_date', '>=', $last365)
            ->orderBy('visit_date')
            ->get()
            ->groupBy(fn ($row) => $row->visit_date->toDateString());

        $familiesQuery = FamilyUser::query();
        if ($request->filled('brand')) {
            $familiesQuery->where('registered_brand', $request->brand);
        }
        if ($request->filled('has_location')) {
            $request->has_location === '1'
                ? $familiesQuery->whereNotNull('signup_lat')
                : $familiesQuery->whereNull('signup_lat');
        }

        $families = (clone $familiesQuery)->latest()->paginate(25)->withQueryString();

        if ($redirect = $this->redirectIfPageOutOfRange($request, $families)) {
            return $redirect;
        }

        $cityCounts = (clone $familiesQuery)
            ->whereNotNull('signup_city_name')
            ->selectRaw('signup_city_name, count(*) as total')
            ->groupBy('signup_city_name')
            ->orderByDesc('total')
            ->get();

        $totalFamilies = (clone $familiesQuery)->count();
        $withLocation = (clone $familiesQuery)->whereNotNull('signup_lat')->count();
        $verifiedCount = (clone $familiesQuery)->whereNotNull('email_verified_at')->count();

        // 26 Ekim 2026: ziyaretçi türleri (sunucu erişim kayıtlarından günlük özet - bkz. TrafikOzet).
        $donemler = ['1' => 'Bugün', 'dun' => 'Dün', '7' => 'Son 7 gün', '30' => 'Son 30 gün', '90' => 'Son 3 ay', '180' => 'Son 6 ay', '365' => 'Son 1 yıl'];
        $donem = array_key_exists((string) $request->query('donem'), $donemler) ? (string) $request->query('donem') : '7';
        [$donemBas, $donemSon] = $donem === 'dun'
            ? [now()->subDay()->toDateString(), now()->subDay()->toDateString()]
            : [now()->subDays((int) $donem - 1)->toDateString(), $today];
        $trafikSatir = \Illuminate\Support\Facades\Schema::hasTable('trafik_gunluk')
            ? \Illuminate\Support\Facades\DB::table('trafik_gunluk')->whereBetween('tarih', [$donemBas, $donemSon])
                ->selectRaw('marka, tur, sum(istek) as istek, sum(ip) as ip')->groupBy('marka', 'tur')->get()
            : collect();
        $trafik = [];
        foreach ($trafikSatir as $r) {
            $trafik[$r->marka][$r->tur] = ['istek' => (int) $r->istek, 'ip' => (int) $r->ip];
        }
        $trafikBaslangic = \Illuminate\Support\Facades\Schema::hasTable('trafik_gunluk') ? \Illuminate\Support\Facades\DB::table('trafik_gunluk')->min('tarih') : null;
        $trafikGunluk = \Illuminate\Support\Facades\Schema::hasTable('trafik_gunluk')
            ? \Illuminate\Support\Facades\DB::table('trafik_gunluk')->whereBetween('tarih', [$donemBas, $donemSon])
                ->selectRaw('tarih, tur, sum(istek) as istek')->groupBy('tarih', 'tur')->orderBy('tarih')->get()->groupBy('tarih')
            : collect();

        return view('admin.site-stats.index', compact(
            'brands', 'visitStats', 'dailySeries', 'families', 'cityCounts', 'totalFamilies', 'withLocation', 'verifiedCount',
            'donemler', 'donem', 'trafik', 'trafikBaslangic', 'trafikGunluk', 'donemBas', 'donemSon',
            'gercekBaslangic', 'botEngel', 'botEngelGunluk', 'supheliFirmalar', 'engelliListe', 'gozcuSon'
        ));
    }

    public function showFamily(FamilyUser $familyUser)
    {
        $familyUser->loadMissing(['offerRequests' => fn ($q) => $q->latest()->with('facility')]);

        return view('admin.site-stats.family-show', ['family' => $familyUser]);
    }
}
