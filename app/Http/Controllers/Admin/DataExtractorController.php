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
use Illuminate\Support\Facades\File;

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

        return view('admin.data-extractor.index', [
            'tool' => $tool,
            'cities' => City::orderBy('name')->get(),
            'categories' => FacilityCategory::orderBy('name')->get(),
            'recentImports' => DataImportBatch::with(['city', 'category'])->latest()->limit(10)->get(),
            'reviewRows' => DataImportRow::with(['batch.city', 'batch.category', 'facility'])
                ->whereIn('status', ['pending_review', 'enriched', 'skipped', 'error'])
                ->latest()
                ->limit(100)
                ->get(),
        ]);
    }

    public function run(Request $request, GoogleMapsDataExtractorService $extractor, DataImportRowApprovalService $rowService)
    {
        $data = $request->validate([
            'query' => 'required|string|max:255',
            'limit' => 'required|integer|min:1|max:1000',
            'city_id' => 'required|exists:cities,id',
            'facility_category_id' => 'required|exists:facility_categories,id',
            'district' => 'nullable|string|max:120',
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
            ],
        ]);

        foreach ($results as $index => $payload) {
            $payload = is_array($payload) ? $payload : [];
            if (filled($data['district'] ?? null)) {
                $payload['district'] = $data['district'];
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
            $request->file('file')->getClientOriginalName()
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
