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
            ->whereNull('facilities.deleted_at')
            ->where('facilities.is_claimed', false)
            ->where('facilities.is_broker_managed', false)
            ->where(function ($q) {
                $q->where('facilities.is_subscribed', false)->orWhere('facilities.subscription_paid_until', '<', now()->startOfDay());
            })
            ->where('facilities.name', 'not like', 'QATEST%')
            ->with(['city', 'category'])
            // 6 Kasım 2026: kullanıcının talebi (yıllık üyelik satışı, plan A+D) - telefonda söylenecek
            // rakamlar artık son 30 günün GERÇEK ilgisinden gelir: tarayıcı doğrulamalı ziyaret (js),
            // sunucu tarafı tekil ziyaret (rv), Ara / WhatsApp / Yol tarifi tıklamaları ve talepler.
            ->leftJoinSub(
                DB::table('facility_engagement_events')
                    ->where('created_at', '>=', now()->subDays(30))
                    ->whereIn('type', ['js_view', 'real_view', 'phone_click', 'whatsapp_click', 'directions_click', 'phone_intent', 'whatsapp_intent'])
                    ->selectRaw("facility_id, sum(type = 'js_view') as ilgi_js, sum(type = 'real_view') as ilgi_rv, sum(type = 'phone_click') as ilgi_ara, sum(type = 'whatsapp_click') as ilgi_wa, sum(type = 'directions_click') as ilgi_yol, sum(type in ('phone_intent', 'whatsapp_intent')) as ilgi_istek")
                    ->groupBy('facility_id'),
                'ilgi', 'ilgi.facility_id', '=', 'facilities.id'
            )
            ->leftJoinSub(
                DB::table('facility_interest_leads')->where('created_at', '>=', now()->subDays(30))
                    ->selectRaw('facility_id, count(*) as ilgi_talep')->groupBy('facility_id'),
                'talep', 'talep.facility_id', '=', 'facilities.id'
            )
            ->where(fn ($q) => $q->where('ilgi.ilgi_rv', '>', 0)->orWhere('ilgi.ilgi_js', '>', 0)->orWhere('ilgi.ilgi_istek', '>', 0)->orWhere('talep.ilgi_talep', '>', 0))
            ->selectRaw('facilities.*, ilgi.ilgi_js, ilgi.ilgi_rv, ilgi.ilgi_ara, ilgi.ilgi_wa, ilgi.ilgi_yol, ilgi.ilgi_istek, talep.ilgi_talep, '.($periodColumn ? "CASE WHEN views_count > {$periodColumn} THEN views_count - {$periodColumn} ELSE 0 END" : 'views_count').' as period_views');

        if ($request->filled('city')) {
            $query->whereHas('city', fn ($q) => $q->where('slug', $request->city));
        }
        if ($request->filled('q')) {
            $query->whereTrLike('facilities.name', $request->string('q'));
        }
        if ($request->filled('kategori')) {
            $query->whereHas('category', fn ($q) => $q->where('slug', $request->kategori));
        }

        // Varsayılan: gerçek ilgi (önce talep ve iletişim tıklamaları, sonra ziyaret). "Görüntülenme" eski bot dahil sayaç.
        if ($request->query('sirala') === 'goruntulenme') {
            $query->orderByDesc('period_views');
        } else {
            $query->orderByRaw('coalesce(talep.ilgi_talep, 0) * 10 + coalesce(ilgi.ilgi_istek, 0) * 6 + coalesce(ilgi.ilgi_ara, 0) * 5 + coalesce(ilgi.ilgi_wa, 0) * 5 + coalesce(ilgi.ilgi_yol, 0) * 3 desc')
                ->orderByRaw('coalesce(ilgi.ilgi_js, 0) desc')
                ->orderByRaw('coalesce(ilgi.ilgi_rv, 0) desc');
        }
        $facilities = $query->paginate(50)->withQueryString();

        $facilities->getCollection()->transform(function ($f) use ($memberCounts) {
            $key = "{$f->city_id}|{$f->district}|{$f->facility_category_id}";
            $f->rakip_uye_sayisi = (int) ($memberCounts[$key] ?? 0);

            return $f;
        });

        $cities = \App\Models\City::orderBy('name')->get();
        $kategoriler = \App\Models\FacilityCategory::orderBy('name')->get(['slug', 'name']);

        return view('admin.sales-opportunities.index', compact('facilities', 'cities', 'period', 'kategoriler'));
    }
}
