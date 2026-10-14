<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Facility;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * 11 Ekim 2026: kullanicinin talebi - telefonla arama yaparken kurumlara
 * "siz bu ay X kez goruntulendiniz ama hic uye degilsiniz" / "bolgenizde
 * zaten X kurum uye oldu" gibi SOMUT, KISISELLESTIRILMIS rakamlar
 * soyleyebilmek icin. Statik bir Excel export DEGIL - admin her actiginda
 * CANLI veriyle yeniden hesaplanir (goruntulenme sayilari/uyelikler surekli
 * degistigi icin).
 */
class SalesOpportunitiesController extends Controller
{
    public function index(Request $request)
    {
        // Ayni il+ilce+kategoride "uye" sayilan (sahipli, anlasmali veya
        // aktif abone) kurum sayisini TEK bir gruplu sorguyla hesaplar -
        // asagida satir basina N+1 sorgu yerine bu haritadan okunur.
        $memberCounts = DB::table('facilities')
            ->whereNull('deleted_at')
            ->where(function ($q) {
                $q->where('is_claimed', true)
                    ->orWhere('is_broker_managed', true)
                    ->orWhere(function ($sq) {
                        $sq->where('is_subscribed', true)->where('subscription_paid_until', '>=', now()->startOfDay());
                    });
            })
            ->select('city_id', 'district', 'facility_category_id', DB::raw('count(*) as c'))
            ->groupBy('city_id', 'district', 'facility_category_id')
            ->get()
            ->mapWithKeys(fn ($r) => ["{$r->city_id}|{$r->district}|{$r->facility_category_id}" => $r->c]);

        // 11 Ekim 2026: kullanicinin talebi - "hafta/ay/ceyrek/toplam"
        // secimi. Haftalik/aylik/ceyreklik GERCEK (boosted_views_count
        // EKLENMEDEN - bkz. admin "views_min" filtresindeki ayni tarihli
        // "gercek goruntulenme" ilkesi, telefonda soylenen rakam ASLA
        // suni artirilmis olmamali) delta ile hesaplanir. Kayan (rolling)
        // 7/30/90 gun DEGIL, takvim haftasi/ayi/ceyregi - gunluk gecmis
        // tablosu olmadigi icin bu teknik olarak mumkun degil (bkz.
        // views_count_at_quarter_start migration'indaki ayni tarihli yorum).
        $period = in_array($request->query('period'), ['hafta', 'ay', 'ceyrek', 'toplam'], true) ? $request->query('period') : 'ay';
        $periodColumn = match ($period) {
            'hafta' => 'views_count_at_week_start',
            'ay' => 'views_count_at_month_start',
            'ceyrek' => 'views_count_at_quarter_start',
            default => null,
        };
        $query = Facility::query()
            ->whereNull('deleted_at')
            ->where('is_claimed', false)
            ->where('is_broker_managed', false)
            ->where(function ($q) {
                $q->where('is_subscribed', false)->orWhere('subscription_paid_until', '<', now()->startOfDay());
            })
            ->where('views_count', '>', 0)
            ->where('name', 'not like', 'QATEST%')
            ->with(['city', 'category'])
            ->selectRaw('facilities.*, '.($periodColumn ? "GREATEST(views_count - {$periodColumn}, 0)" : 'views_count').' as period_views');

        if ($request->filled('city')) {
            $query->whereHas('city', fn ($q) => $q->where('slug', $request->city));
        }
        if ($request->filled('q')) {
            $query->where('name', 'like', '%'.$request->string('q').'%');
        }

        $facilities = $query->orderByDesc('period_views')->paginate(50)->withQueryString();

        $facilities->getCollection()->transform(function ($f) use ($memberCounts) {
            $key = "{$f->city_id}|{$f->district}|{$f->facility_category_id}";
            $f->rakip_uye_sayisi = (int) ($memberCounts[$key] ?? 0);

            return $f;
        });

        $cities = \App\Models\City::orderBy('name')->get();

        return view('admin.sales-opportunities.index', compact('facilities', 'cities', 'period'));
    }
}
