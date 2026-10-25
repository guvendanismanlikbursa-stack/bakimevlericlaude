<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Expert;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

// 7 Ekim 2026: kullanicinin talebi - "Doktorlar" admin bolumu, Kurumlar
// admin bolumundeki (AdminFacilityController) ayni temel islemler
// (listele/filtrele, ekle, duzenle, sil) - Expert modeli Facility'den
// bagimsiz oldugu icin bu da ayri, kucuk bir kontrolcu.
class ExpertController extends Controller
{
    private const BRANCHES = [
        'Pediatri' => 'Çocuk Doktoru',
        'Diş Hekimliği' => 'Diş Doktoru',
        'Geriatri' => 'Yaşlılık Uzmanı',
        'Fiziksel Tıp ve Rehabilitasyon' => 'Fizik Tedavi Doktoru',
        'Çocuk Nörolojisi' => 'Çocuk Beyin ve Sinir Hastalıkları',
        'Çocuk Psikiyatrisi' => 'Çocuk Gelişim Uzmanı',
        'Nöroloji' => 'Beyin ve Sinir Hastalıkları',
        'Ortopedi' => 'Kemik ve Eklem Hastalıkları',
    ];

    /**
     * 25 Ekim 2026: "Özel Doktorlar" bölümünü yayına alır / gizler. Açınca anasayfada
     * 4. kart çıkar, /uzmanlar herkese açılır; kapatınca her şey eski haline döner.
     */
    public function toggleSection(Request $request)
    {
        $live = $request->boolean('live');
        \App\Models\Setting::set('doctors_live', $live ? '1' : '0');
        log_admin_event('doctors_section_'.($live ? 'published' : 'hidden'));

        return back()->with('success', $live
            ? 'Doktorlar bölümü yayında: anasayfada "Özel Doktorlar" kartı çıktı, sayfa herkese açık.'
            : 'Doktorlar bölümü gizlendi: anasayfa kartları eski haline döndü, sayfa yine yalnızca bağlantıyı bilenlere açık.');
    }

    public function index(Request $request)
    {
        $query = Expert::query()->with('city')->withTrashed();

        if ($request->filled('q')) {
            $query->where('name', 'like', '%'.$request->q.'%');
        }
        if ($request->filled('city')) {
            $query->where('city_id', $request->city);
        }
        if ($request->filled('branch')) {
            $query->where('branch', $request->branch);
        }
        // 11 Ekim 2026: kullanicinin talebi - eksik telefon numarali
        // doktorlari bulup manuel arama yapabilmek icin.
        if ($request->filled('phone_status') && $request->phone_status === 'eksik') {
            $query->where(function ($q) {
                $q->whereNull('phone')->orWhere('phone', '')->orWhereRaw("phone REGEXP '[0-9]' = 0");
            });
        }
        // 22 Ekim 2026: internet sitesi durumu filtresi (kurumlardakiyle aynı).
        if ($request->filled('site')) {
            $query->websiteFilter($request->site);
        }
        if ($request->filled('status')) {
            if ($request->status === 'published') {
                $query->where('is_published', true)->whereNull('deleted_at');
            } elseif ($request->status === 'draft') {
                $query->where('is_published', false)->whereNull('deleted_at');
            } elseif ($request->status === 'deleted') {
                $query->whereNotNull('deleted_at');
            }
        } else {
            $query->whereNull('deleted_at');
        }

        $experts = $query->orderByDesc('id')->paginate(30)->withQueryString();
        $cities = City::orderBy('name')->get();
        $branches = self::BRANCHES;
        $totalCount = Expert::count();
        $publishedCount = Expert::where('is_published', true)->count();

        $kartStat = Expert::cardStats30($experts->pluck('id')->all());

        return view('admin.experts.index', compact('experts', 'cities', 'branches', 'totalCount', 'publishedCount', 'kartStat'));
    }

    public function create()
    {
        $cities = City::orderBy('name')->get();
        $branches = self::BRANCHES;
        $expert = new Expert();

        return view('admin.experts.form', compact('expert', 'cities', 'branches'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['slug'] = $this->uniqueSlug($data['name']);
        $data['reviews'] = null;

        Expert::create($data);

        return redirect()->route('admin.experts.index')->with('success', 'Doktor eklendi.');
    }

    public function edit(Expert $expert)
    {
        $cities = City::orderBy('name')->get();
        $branches = self::BRANCHES;

        return view('admin.experts.form', compact('expert', 'cities', 'branches'));
    }

    public function update(Request $request, Expert $expert)
    {
        $data = $this->validated($request, $expert->id);
        if ($data['name'] !== $expert->name) {
            // Isim degistiyse slug'i da guncelle ama linkler kirilmasin
            // diye SADECE isim degistiginde yeniden uretilir.
            $data['slug'] = $this->uniqueSlug($data['name'], $expert->id);
        }

        $expert->update($data);

        return redirect()->route('admin.experts.index')->with('success', 'Doktor güncellendi.');
    }

    public function destroy(Expert $expert)
    {
        $expert->delete();

        return back()->with('success', 'Doktor silindi (çöp kutusuna taşındı).');
    }

    // 7 Ekim 2026: kullanicinin acik talebi - kurumlardaki gibi "toplu
    // islem" alani. Yanlislikla silinen bir doktoru geri getirir.
    public function restore($id)
    {
        $expert = Expert::withTrashed()->findOrFail($id);
        $expert->restore();

        return back()->with('success', 'Doktor geri yüklendi.');
    }

    public function togglePublish(Expert $expert)
    {
        $expert->update(['is_published' => ! $expert->is_published]);

        return back()->with('success', $expert->is_published ? 'Doktor yayınlandı.' : 'Doktor yayından kaldırıldı.');
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:150',
            'title' => 'nullable|string|max:60',
            'branch' => 'required|string|max:100',
            'branch_label' => 'required|string|max:100',
            'city_id' => 'required|exists:cities,id',
            'district' => 'nullable|string|max:120',
            'address' => 'nullable|string|max:255',
            'clinic_name' => 'nullable|string|max:150',
            'description' => 'nullable|string|max:2000',
            'phone' => 'nullable|string|max:30',
            'google_maps_url' => 'nullable|url|max:2000',
            'rating' => 'nullable|numeric|min:0|max:5',
            'review_count' => 'nullable|integer|min:0',
            // 22 Ekim 2026: İyilik Hareketi Destekçisi bitiş tarihi (boş = destekçi değil).
            'supporter_until' => 'nullable|date',
            'website_status' => 'nullable|in:yok,sosyal,rehber,var',
            'website_url' => 'nullable|string|max:500',
        ]);
        if (array_key_exists('website_status', $data)) {
            $data['website_checked_at'] = now();
        }

        $data['is_published'] = $request->boolean('is_published');
        $data['phone_type'] = classify_phone_type($data['phone'] ?? null);

        return $data;
    }

    private function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i = 1;
        while (Expert::withTrashed()->where('slug', $slug)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $slug = $base.'-'.(++$i);
        }

        return $slug;
    }
}
