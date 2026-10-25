<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Expert;
use App\Models\ExpertWebsite;
use App\Models\Facility;
use App\Models\FacilityWebsite;
use App\Models\Marketer;
use App\Models\SiteSale;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

// 25 Ekim 2026: kullanıcının talebi - kurum ve doktor sitelerinin satış takibi
// tek sayfada: kim, hangi site, başlangıç/bitiş, ücret, ödeme, notlar.
// "Siteye uygula" işaretliyse satış kaydedilince sitenin paketi ve bitiş
// tarihi de güncellenir, site yayına alınır (iki yer arasında fark oluşmasın).
class SiteSaleController extends Controller
{
    public function index(Request $request)
    {
        $q = SiteSale::query()->with('marketer');
        if ($request->filled('tur')) {
            $q->where('owner_type', $request->tur);
        }
        if ($request->filled('plan')) {
            $q->where('plan', $request->plan);
        }
        if ($request->filled('odeme')) {
            $q->where('odeme', $request->odeme);
        }
        if ($request->filled('q')) {
            $q->where('owner_name', 'like', '%'.$request->q.'%');
        }
        $bugun = now()->toDateString();
        match ($request->input('durum')) {
            'aktif' => $q->where('baslangic', '<=', $bugun)->where(fn ($w) => $w->whereNull('bitis')->orWhere('bitis', '>=', $bugun)),
            'bitiyor' => $q->whereBetween('bitis', [$bugun, now()->addDays(30)->toDateString()]),
            'bitti' => $q->where('bitis', '<', $bugun),
            default => null,
        };
        if ($request->filled('bitis_bas')) {
            $q->whereDate('bitis', '>=', $request->date('bitis_bas'));
        }
        if ($request->filled('bitis_son')) {
            $q->whereDate('bitis', '<=', $request->date('bitis_son'));
        }
        $toplamFiltre = (clone $q)->sum('ucret');
        $satislar = $q->orderByDesc('baslangic')->orderByDesc('id')->paginate(30)->withQueryString();

        $ozet = [
            'aktif' => SiteSale::where('baslangic', '<=', $bugun)->where(fn ($w) => $w->whereNull('bitis')->orWhere('bitis', '>=', $bugun))->count(),
            'bitiyor' => SiteSale::whereBetween('bitis', [$bugun, now()->addDays(30)->toDateString()])->count(),
            'bu_ay' => (float) SiteSale::where('baslangic', '>=', now()->startOfMonth()->toDateString())->sum('ucret'),
            'bu_yil' => (float) SiteSale::where('baslangic', '>=', now()->startOfYear()->toDateString())->sum('ucret'),
            'bekleyen' => (float) SiteSale::whereIn('odeme', ['bekliyor', 'kismi'])->sum('ucret'),
            'kurum' => SiteSale::where('owner_type', 'kurum')->count(),
            'doktor' => SiteSale::where('owner_type', 'doktor')->count(),
        ];

        return view('admin.site-sales.index', compact('satislar', 'ozet', 'toplamFiltre'));
    }

    public function create(Request $request)
    {
        $satis = new SiteSale([
            'owner_type' => $request->query('tur') === 'doktor' ? 'doktor' : 'kurum',
            'owner_id' => (int) $request->query('id') ?: null,
            'plan' => $request->query('plan') === 'profesyonel' ? 'profesyonel' : 'standart',
            'baslangic' => now(),
            'bitis' => now()->addYear(),
            'odeme' => 'bekliyor',
        ]);
        $sahip = $satis->owner_id ? $satis->owner() : null;
        $marketers = Marketer::orderBy('name')->get(['id', 'name']);

        return view('admin.site-sales.form', compact('satis', 'sahip', 'marketers'));
    }

    public function store(Request $request)
    {
        [$data, $sahip] = $this->dogrula($request);
        $satis = SiteSale::create($data + ['admin_id' => session('admin_id')]);
        $this->siteyeUygula($request, $satis, $sahip);
        log_admin_event('site_sale_created', $sahip, ['plan' => $satis->plan, 'ucret' => $satis->ucret]);

        return redirect()->route('admin.site-sales.index')->with('success', $satis->owner_name.': satış kaydedildi.');
    }

    public function edit(SiteSale $satis)
    {
        $sahip = $satis->owner();
        $marketers = Marketer::orderBy('name')->get(['id', 'name']);

        return view('admin.site-sales.form', compact('satis', 'sahip', 'marketers'));
    }

    public function update(Request $request, SiteSale $satis)
    {
        [$data, $sahip] = $this->dogrula($request);
        $satis->update($data);
        $this->siteyeUygula($request, $satis, $sahip);

        return redirect()->route('admin.site-sales.index')->with('success', $satis->owner_name.': satış güncellendi.');
    }

    public function destroy(SiteSale $satis)
    {
        $ad = $satis->owner_name;
        $satis->delete();

        return redirect()->route('admin.site-sales.index')->with('success', $ad.': satış kaydı silindi (site ayarlarına dokunulmadı).');
    }

    private function dogrula(Request $request): array
    {
        $data = $request->validate([
            'owner_type' => ['required', Rule::in(array_keys(SiteSale::TURLER))],
            'owner_id' => ['required', 'integer'],
            'plan' => ['required', Rule::in(array_keys(SiteSale::PLANLAR))],
            'baslangic' => ['required', 'date'],
            'bitis' => ['nullable', 'date', 'after_or_equal:baslangic'],
            'ucret' => ['required', 'numeric', 'min:0', 'max:9999999'],
            'odeme' => ['required', Rule::in(array_keys(SiteSale::ODEME))],
            'marketer_id' => ['nullable', 'integer', 'exists:marketers,id'],
            'notlar' => ['nullable', 'string', 'max:3000'],
        ], ['owner_id.required' => 'Kurum ya da doktor numarası gerekli.', 'bitis.after_or_equal' => 'Bitiş tarihi başlangıçtan önce olamaz.']);
        $sahip = $data['owner_type'] === 'doktor' ? Expert::find($data['owner_id']) : Facility::find($data['owner_id']);
        if (! $sahip) {
            throw \Illuminate\Validation\ValidationException::withMessages(['owner_id' => ($data['owner_type'] === 'doktor' ? 'Bu numarada doktor' : 'Bu numarada kurum').' bulunamadı.']);
        }
        $data['owner_name'] = $data['owner_type'] === 'doktor' ? trim(($sahip->title ? $sahip->title.' ' : '').$sahip->name) : $sahip->name;

        return [$data, $sahip];
    }

    /** Satıştaki paket ve bitiş tarihini siteye işler, siteyi yayına alır. */
    private function siteyeUygula(Request $request, SiteSale $satis, $sahip): void
    {
        if (! $request->boolean('siteye_uygula')) {
            return;
        }
        $site = $satis->owner_type === 'doktor' ? ExpertWebsite::forExpert($sahip) : FacilityWebsite::forFacility($sahip);
        $site->fill([
            'status' => 'yayinda',
            'plan' => $satis->plan,
            'pro_until' => $satis->plan === 'profesyonel' ? optional($satis->bitis)->toDateString() : null,
            'pro_yayina_aldi' => false,
        ]);
        $site->published_at ??= now();
        $site->save();
    }
}
