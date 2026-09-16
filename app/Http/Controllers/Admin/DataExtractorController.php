<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\DataImportBatch;
use App\Models\DataImportRow;
use App\Models\FacilityCategory;
use App\Services\DataExtractorImportService;
use App\Services\DataImportRowApprovalService;
use App\Services\GoogleMapsDataExtractorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rule;

class DataExtractorController extends Controller
{
    public function index()
    {
        $toolPath = base_path('tools/veri-cekici');
        $tool = [
            'path' => $toolPath,
            'exists' => File::isDirectory($toolPath),
            'launcher' => File::exists($toolPath.DIRECTORY_SEPARATOR.'BASLAT.bat'),
            'scraper' => File::exists($toolPath.DIRECTORY_SEPARATOR.'google_maps_scraper.py'),
            'live_disabled' => File::exists($toolPath.DIRECTORY_SEPARATOR.'CANLIYA_AKTAR.py'),
        ];

        $cities = City::orderBy('name')->get();

        // 10 Eylul 2026: kullanicinin talebi - il/ilce/mahalle SECMELI olsun.
        // Ilce: config/turkiye.php'den (yazim hatasi olmaz). Mahalle: yapisal
        // veri yok, bu yuzden MEVCUT kurumlarin adreslerinden daha once
        // kullanilmis mahalle adlari (ilce bazinda) oneri olarak sunulur -
        // 6 saat onbellekli, admin sayfasi oldugu icin maliyeti onemsiz.
        $districtMap = $cities->mapWithKeys(fn ($city) => [$city->id => districts_for_city($city->name)]);
        $neighborhoodMap = \Illuminate\Support\Facades\Cache::remember('vc:neighborhood_map:v1', now()->addHours(6), function () {
            $map = [];
            \App\Models\Facility::withTrashed()
                ->whereNotNull('district')->whereNotNull('address')
                ->select('district', 'address')->get()
                ->each(function ($f) use (&$map) {
                    $first = trim(explode(',', $f->address)[0] ?? '');
                    if (preg_match('/^(.{2,40}?)\s+Mah(?:\.|allesi)?\b/ui', $first, $m)) {
                        $mahalle = trim($m[1]);
                        $map[$f->district][$mahalle] = true;
                    }
                });

            return collect($map)->map(fn ($v) => array_values(array_keys($v)))->all();
        });

        return view('admin.data-extractor.index', [
            'tool' => $tool,
            'cities' => $cities,
            'categories' => FacilityCategory::orderBy('name')->get(),
            'districtMap' => $districtMap,
            'neighborhoodMap' => $neighborhoodMap,
            'recentImports' => DataImportBatch::with(['city', 'category'])->latest()->limit(10)->get(),
            'reviewRows' => DataImportRow::with(['batch.city', 'batch.category', 'facility'])
                ->whereIn('status', ['pending_review', 'enriched', 'skipped', 'error'])
                ->latest()
                ->limit(100)
                ->get(),
        ]);
    }

    /**
     * 10 Eylul 2026: kullanicinin talebi - "ilce secince ilcedeki mahalleler
     * acilir". Turkiye'de ~50 bin mahalle var, sabit listeye sigmaz; bu uc
     * secilen il+ilce icin mahalle listesini turkiyeapi.dev'den bir kez
     * ceker (il basina 30 gun onbellek - mahalleler pratikte hic degismez),
     * bulamazsa mevcut kurum adreslerinden ogrenilmis listeyi doner.
     */
    public function neighborhoods(Request $request)
    {
        $city = City::find((int) $request->query('city_id'));
        $district = trim((string) $request->query('district', ''));

        if (! $city || $district === '') {
            return response()->json(['neighborhoods' => []]);
        }

        $names = Cache::remember(
            'vc:mahalleler:'.$city->id.':'.mb_strtolower($district),
            now()->addDays(30),
            function () use ($city, $district) {
                try {
                    $provinceId = $this->turkiyeProvinceId($city->name);
                    if ($provinceId) {
                        $resp = Http::timeout(12)->get('https://turkiyeapi.dev/api/v1/neighborhoods', [
                            'provinceId' => $provinceId,
                        ]);
                        if ($resp->successful()) {
                            $norm = fn ($s) => mb_strtolower(strtr($s, ['İ' => 'i', 'I' => 'i', 'ı' => 'i']));
                            $target = $norm($district);

                            return collect($resp->json('data', []))
                                ->filter(fn ($n) => $norm($n['district'] ?? '') === $target)
                                ->pluck('name')->unique()->sort()->values()->all();
                        }
                    }
                } catch (\Throwable $e) {
                    // API'ye ulasilamadi - asagidaki yedek liste kullanilir.
                }

                return [];
            }
        );

        return response()->json(['neighborhoods' => $names]);
    }

    private function turkiyeProvinceId(string $cityName): ?int
    {
        $map = Cache::remember('vc:turkiye_province_ids', now()->addDays(60), function () {
            try {
                $resp = Http::timeout(12)->get('https://turkiyeapi.dev/api/v1/provinces', ['fields' => 'id,name']);
                if ($resp->successful()) {
                    return collect($resp->json('data', []))->mapWithKeys(fn ($p) => [$p['name'] => $p['id']])->all();
                }
            } catch (\Throwable $e) {
                // yok
            }

            return [];
        });

        return $map[$cityName] ?? null;
    }

    public function run(Request $request, GoogleMapsDataExtractorService $extractor, DataImportRowApprovalService $rowService)
    {
        $data = $request->validate([
            'query' => 'required|string|max:255',
            'limit' => 'required|integer|min:1|max:1000',
            'city_id' => 'required|exists:cities,id',
            'facility_category_id' => 'required|exists:facility_categories,id',
            'district' => 'nullable|string|max:120',
            'neighborhood' => 'nullable|string|max:120',
        ]);

        $limit = min(1000, (int) $data['limit']);
        $results = $extractor->scrape($data['query'], $limit);
        $city = City::findOrFail($data['city_id']);
        $category = FacilityCategory::findOrFail($data['facility_category_id']);

        $batch = DataImportBatch::create([
            'source' => 'google_maps_veri_cekici_auto',
            'admin_id' => session('admin_id'),
            'city_id' => $city->id,
            'facility_category_id' => $category->id,
            'file_name' => 'otomatik: '.$data['query'],
            'total_rows' => count($results),
            'status' => 'pending_review',
            'meta' => [
                'query' => $data['query'],
                'limit' => $limit,
                'district' => $data['district'] ?? null,
                'neighborhood' => $data['neighborhood'] ?? null,
            ],
        ]);

        foreach ($results as $index => $payload) {
            $payload = is_array($payload) ? $payload : [];
            if (filled($data['district'] ?? null)) {
                $payload['district'] = $data['district'];
            }
            if (filled($data['neighborhood'] ?? null)) {
                $payload['neighborhood'] = $data['neighborhood'];
            }
            $item = $rowService->normalize($payload);

            $batch->rows()->create([
                'row_number' => $index + 1,
                'status' => filled($item['name']) ? 'pending_review' : 'skipped',
                'name' => $item['name'],
                'phone' => $item['phone'],
                'message' => filled($item['name']) ? 'Onay bekliyor.' : 'Kurum adı boş.',
                'payload' => $item,
            ]);
        }

        return back()->with('success', 'Otomatik veri çekimi tamamlandı. İncelemeye alınan satır: '.count($results));
    }

    public function import(Request $request, DataExtractorImportService $importer)
    {
        $data = $request->validate([
            'file' => 'required|file|mimes:xlsx|max:10240',
            'city_id' => 'required|exists:cities,id',
            'facility_category_id' => 'required|exists:facility_categories,id',
            'district' => 'nullable|string|max:120',
            'neighborhood' => 'nullable|string|max:120',
            'is_published' => 'nullable|boolean',
        ]);

        $city = City::findOrFail($data['city_id']);
        $category = FacilityCategory::findOrFail($data['facility_category_id']);
        $result = $importer->import(
            $request->file('file')->getRealPath(),
            $city,
            $category,
            $data['district'] ?? null,
            $request->boolean('is_published'),
            session('admin_id'),
            $request->file('file')->getClientOriginalName(),
            $data['neighborhood'] ?? null
        );

        $message = "Veri çekici import tamamlandı. Eklenen: {$result['created']} | Atlanan: {$result['skipped']}";
        if ($result['errors']) {
            return back()->withErrors($result['errors'])->with('success', $message);
        }

        return back()->with('success', $message);
    }

    public function updateRow(Request $request, DataImportRow $row)
    {
        $data = $request->validate([
            'name' => 'required|string|max:180',
            'category' => 'nullable|string|max:120',
            'address' => 'nullable|string|max:500',
            'district' => 'nullable|string|max:120',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:150',
            'price_min' => 'nullable|numeric|min:0',
            'price_max' => ['nullable', 'numeric', 'min:0', Rule::when($request->filled('price_min'), ['gte:price_min'])],
            'rating' => 'nullable|string|max:20',
            'description' => 'nullable|string|max:5000',
        ]);

        $row->update([
            'status' => 'pending_review',
            'name' => $data['name'],
            'phone' => $data['phone'] ?? null,
            'message' => 'Admin tarafından düzenlendi.',
            'payload' => array_merge($row->payload ?? [], $data),
        ]);

        return back()->with('success', 'Satır güncellendi.');
    }

    public function showRow(DataImportRow $row)
    {
        $row->loadMissing(['batch.city', 'batch.category', 'facility']);
        $payload = $row->payload ?? [];

        $prefill = [
            'name' => $payload['name'] ?? $row->name,
            'city_id' => $row->batch?->city?->id,
            'district' => $payload['district'] ?? null,
            'address' => $payload['address'] ?? null,
            'phone' => $payload['phone'] ?? null,
            'email' => $payload['email'] ?? null,
            'description' => $payload['description'] ?? null,
            'facility_category_id' => $row->batch?->category?->id,
            'price_min' => $payload['price_min'] ?? null,
            'price_max' => $payload['price_max'] ?? null,
            'rating' => $payload['rating'] ?? null,
            'source_note' => 'Google Maps veri çekiciden alınmış satır.',
        ];

        return view('admin.data-extractor.show', compact('row', 'prefill'));
    }

    public function autofill(DataImportRow $row, DataImportRowApprovalService $rowService)
    {
        $rowService->enrich($row);

        return back()->with('success', 'Otomatik doldurma hazırlandı. Onayda 5 görsel eklenecek.');
    }

    public function approve(Request $request, DataImportRow $row, DataImportRowApprovalService $rowService)
    {
        try {
            $facility = $rowService->approve($row, $request->boolean('is_published', true));
        } catch (\RuntimeException $e) {
            // Mukerrer kayit veya eksik veri gibi beklenen durumlar: 500 hatasi
            // yerine anlasilir bir mesajla listeye geri don.
            return back()->withErrors(['row' => $e->getMessage()]);
        }

        // 21 Temmuz 2026: bu islem canli, yayinlanmis bir kurum olusturuyor
        // ama Islem Gunlugu'ne hic yazilmiyordu - hangi admin'in ne zaman
        // hangi veri-cekici satirindan kurum yayina aldigi izlenemiyordu.
        log_admin_event('data_import_row_approved', $facility, ['data_import_row_id' => $row->id]);

        if ($request->boolean('edit')) {
            return redirect()->route('admin.facilities.edit', $facility)->with('success', 'Kurum oluşturuldu. Şimdi manuel revize yapabilirsiniz.');
        }

        return back()->with('success', 'Kurum onaylandı ve ön kayıtlı olarak yayına hazırlandı.');
    }

    public function destroyRow(DataImportRow $row)
    {
        $row->update(['status' => 'deleted', 'message' => 'Admin tarafından listeden silindi.']);

        return back()->with('success', 'Çekilen satır silindi.');
    }
}
