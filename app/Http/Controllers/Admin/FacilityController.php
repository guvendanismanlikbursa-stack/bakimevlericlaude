<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Facility;
use App\Models\FacilityCategory;
use App\Models\FacilityImage;
use App\Services\FacilityArchiveService;
use App\Services\ImageCompressionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class FacilityController extends Controller
{
    private const MAX_GALLERY_IMAGES = 10;
    public function index(Request $request)
    {
        $query = $this->filteredQuery($request, includeCategory: true)->with(['city', 'category', 'images']);

        $facilities = $query->latest()->paginate(15)->withQueryString();
        $brands = config('brands.brands');
        $cities = City::orderBy('name')->get();
        $categories = FacilityCategory::orderBy('name')->get();
        $districtMap = $cities->mapWithKeys(fn ($city) => [$city->slug => districts_for_city($city->name)]);
        $ownershipTypes = ['ozel' => 'Özel', 'kamu' => 'Kamu', 'belediye' => 'Belediye', 'vakif' => 'Vakıf'];

        // Secilen kategori disindaki tum filtreler uygulanmis haliyle, kategori
        // bazinda kirilim: admin "Tum Kategoriler" secili iken bile bu filtreye
        // (il/ilce/marka/kurulus turu/sahiplenme) uyan kurumlarin kategoriye gore
        // dagilimini gorebilsin.
        $categoryBreakdown = $this->filteredQuery($request, includeCategory: false)
            ->selectRaw('facility_category_id, count(*) as total')
            ->groupBy('facility_category_id')
            ->get()
            ->mapWithKeys(fn ($row) => [$categories->firstWhere('id', $row->facility_category_id)?->name ?? 'Diğer' => $row->total])
            ->sortByDesc(fn ($count) => $count)
            ->all();

        return view('admin.facilities.index', compact('facilities', 'brands', 'cities', 'categories', 'districtMap', 'ownershipTypes', 'categoryBreakdown'));
    }

    private function filteredQuery(Request $request, bool $includeCategory)
    {
        $query = Facility::query();

        if ($request->filled('brand')) {
            $scope = config("brands.brands.{$request->brand}.category_scope", []);
            $query->forBrand($scope);
        }

        if ($request->filled('claim_status')) {
            $query->where('is_claimed', $request->claim_status === 'claimed');
        }

        if ($request->filled('city')) {
            $query->whereHas('city', fn ($q) => $q->where('slug', $request->city));
        }

        if ($request->filled('district')) {
            $query->where('district', $request->district);
        }

        if ($includeCategory && $request->filled('category')) {
            $query->whereHas('category', fn ($q) => $q->where('slug', $request->category));
        }

        if ($request->filled('ownership_type')) {
            $query->where('ownership_type', $request->ownership_type);
        }

        return $query;
    }

    public function create(Request $request)
    {
        $cities = City::orderBy('name')->get();
        $categories = FacilityCategory::orderBy('name')->get();
        $serviceSections = service_sections();

        $facility = new Facility($request->only([
            'name',
            'city_id',
            'district',
            'address',
            'lat',
            'lng',
            'phone',
            'email',
            'description',
            'capacity',
            'price_min',
            'price_max',
            'cover_image',
            'facility_category_id',
        ]));

        return view('admin.facilities.form', [
            'facility' => $facility,
            'cities' => $cities,
            'categories' => $categories,
            'serviceSections' => $serviceSections,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $data['slug'] = $this->uniqueSlug($data['name']);
        $data['services'] = $this->parseServices($request->input('services_raw'), $request->input('services', []));
        $data['is_published'] = $request->boolean('is_published');
        $data['is_featured'] = $request->boolean('is_featured');
        $data['is_claimed'] = false;

        $facility = Facility::create($data);

        $this->storeUploadedImages($request, $facility);

        return redirect()->route('admin.facilities.edit', $facility)->with('success', 'Kurum ön kayıt olarak eklendi. Şimdi demo görseller ekleyebilirsiniz.');
    }

    public function edit(Facility $facility)
    {
        $cities = City::orderBy('name')->get();
        $categories = FacilityCategory::orderBy('name')->get();
        $serviceSections = service_sections();
        $facility->load(['images', 'facilityUsers', 'claims' => fn ($q) => $q->latest(), 'balanceLogs', 'category']);

        return view('admin.facilities.form', compact('facility', 'cities', 'categories', 'serviceSections'));
    }

    public function update(Request $request, Facility $facility)
    {
        $data = $this->validateData($request, $facility->id);

        if ($data['name'] !== $facility->name) {
            $data['slug'] = $this->uniqueSlug($data['name'], $facility->id);
        }

        $data['services'] = $this->parseServices($request->input('services_raw'), $request->input('services', []));
        $data['is_published'] = $request->boolean('is_published');
        $data['is_featured'] = $request->boolean('is_featured');

        $facility->update($data);

        $this->storeUploadedImages($request, $facility);

        return redirect()->route('admin.facilities.edit', $facility)->with('success', 'Kurum güncellendi.');
    }

    public function destroy(Facility $facility, FacilityArchiveService $archiveService)
    {
        $archivePath = $archiveService->archiveBeforeDelete($facility);
        $facility->delete();

        return back()->with('success', 'Kurum silindi ve silinenler arşivine taşındı: '.$archivePath);
    }

    public function revertToPreRegistered(Facility $facility)
    {
        $facility->update([
            'is_claimed' => false,
            'claimed_at' => null,
            'source' => 'google_maps_veri_cekici',
        ]);

        return back()->with('success', 'Kurum ön kayıtlı hale getirildi.');
    }

    public function deleteImage(FacilityImage $image)
    {
        Storage::disk('public')->delete($image->path);
        $image->delete();

        return back()->with('success', 'Görsel silindi.');
    }

    private function storeUploadedImages(Request $request, Facility $facility): void
    {
        if (! $request->hasFile('images')) {
            return;
        }

        $files = collect($request->file('images', []));
        $start = $facility->images()->count();

        if ($start + $files->count() > self::MAX_GALLERY_IMAGES) {
            $remaining = max(0, self::MAX_GALLERY_IMAGES - $start);
            throw ValidationException::withMessages(['images' => "Bir kurum için en fazla 10 görsel eklenebilir. Kalan yükleme hakkı: {$remaining}."]);
        }

        foreach ($files as $i => $file) {
            $path = app(ImageCompressionService::class)->store($file, 'facilities');
            FacilityImage::create([
                'facility_id' => $facility->id,
                'path' => $path,
                'sort_order' => $start + $i,
            ]);
        }
    }

    private function validateData(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'name' => 'required|string|max:180',
            'city_id' => 'required|exists:cities,id',
            'facility_category_id' => 'required|exists:facility_categories,id',
            'district' => 'nullable|string|max:120',
            'address' => 'nullable|string|max:500',
            'lat' => 'nullable|numeric|between:-90,90',
            'lng' => 'nullable|numeric|between:-180,180',
            'phone' => 'nullable|string|max:30',
            'description' => 'nullable|string|max:5000',
            'capacity' => 'nullable|integer|min:0',
            'price_min' => 'nullable|numeric|min:0',
            // gte:price_min sadece price_min de doluysa uygulanir; aksi halde
            // admin sadece price_max girdiginde (price_min bos) dogrulama
            // tum formu reddedip diger tum alanlardaki degisiklikleri de
            // kaydetmeden geri donduruyordu.
            'price_max' => ['nullable', 'numeric', 'min:0', Rule::when($request->filled('price_min'), ['gte:price_min'])],
            'cover_image' => 'nullable|string|max:255',
            'images' => 'nullable|array|max:10',
            'images.*' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'services_raw' => 'nullable|string',
            'services' => 'nullable|array',
            'services.*' => 'nullable|string|max:120',
        ]);
    }

    private function parseServices(?string $raw, array $selected = []): array
    {
        return collect(array_merge(explode(',', $raw ?? ''), $selected))
            ->map(fn ($s) => trim($s))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i = 1;

        while (Facility::where('slug', $slug)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }
}