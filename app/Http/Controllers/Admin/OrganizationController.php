<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

// 7 Ekim 2026: "Organizasyon Şirketleri" admin bolumu - Admin\ExpertController
// ile BIREBIR AYNI desen, Organization modeli icin.
class OrganizationController extends Controller
{
    // 11 Ekim 2026: Public\OrganizationController ile AYNI (gercek Bursa
    // verisinden gelen Google kategorileri) - bkz. o dosyadaki ayni tarihli yorum.
    private const SERVICE_TYPES = [
        'Organizasyon Şirketi' => 'Organizasyon Şirketi',
        'Düğün Salonu' => 'Düğün Salonu',
        'Etkinlik Mekânı' => 'Etkinlik Mekânı',
        'Parti Malzemeleri Mağazası' => 'Parti Malzemeleri',
        'Çiçekçi' => 'Çiçekçi',
        'Fotoğraf Stüdyosu' => 'Fotoğraf Stüdyosu',
        'Düğün Hizmeti' => 'Düğün Hizmeti',
        'Açık Hava Etkinliği Organizatörü' => 'Açık Hava Etkinliği',
        'Kostüm Kiralama Hizmeti' => 'Kostüm Kiralama',
        'Düğün Planlamacısı' => 'Düğün Planlamacısı',
        'Fotoğrafçı' => 'Fotoğrafçı',
    ];

    public function index(Request $request)
    {
        $query = Organization::query()->with('city')->withTrashed();

        if ($request->filled('q')) {
            $query->where('name', 'like', '%'.$request->q.'%');
        }
        if ($request->filled('city')) {
            $query->where('city_id', $request->city);
        }
        if ($request->filled('service_type')) {
            $query->where('service_type', $request->service_type);
        }
        if ($request->filled('min_rating')) {
            $query->where('rating', '>=', (float) $request->min_rating);
        }
        // 11 Ekim 2026: kullanicinin talebi - eksik telefonlu firmalari
        // bulup manuel arama yapabilmek icin.
        if ($request->filled('phone_status') && $request->phone_status === 'eksik') {
            $query->where(function ($q) {
                $q->whereNull('phone')->orWhere('phone', '')->orWhereRaw("phone REGEXP '[0-9]' = 0");
            });
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

        $organizations = $query->orderByDesc('id')->paginate(30)->withQueryString();
        $cities = City::orderBy('name')->get();
        $serviceTypes = self::SERVICE_TYPES;
        $totalCount = Organization::count();
        $publishedCount = Organization::where('is_published', true)->count();

        return view('admin.organizations.index', compact('organizations', 'cities', 'serviceTypes', 'totalCount', 'publishedCount'));
    }

    public function create()
    {
        $cities = City::orderBy('name')->get();
        $serviceTypes = self::SERVICE_TYPES;
        $organization = new Organization();

        return view('admin.organizations.form', compact('organization', 'cities', 'serviceTypes'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['slug'] = $this->uniqueSlug($data['name']);
        $data['reviews'] = null;

        Organization::create($data);

        return redirect()->route('admin.organizations.index')->with('success', 'Organizasyon şirketi eklendi.');
    }

    public function edit(Organization $organization)
    {
        $cities = City::orderBy('name')->get();
        $serviceTypes = self::SERVICE_TYPES;

        return view('admin.organizations.form', compact('organization', 'cities', 'serviceTypes'));
    }

    public function update(Request $request, Organization $organization)
    {
        $data = $this->validated($request, $organization->id);
        if ($data['name'] !== $organization->name) {
            $data['slug'] = $this->uniqueSlug($data['name'], $organization->id);
        }

        $organization->update($data);

        return redirect()->route('admin.organizations.index')->with('success', 'Organizasyon şirketi güncellendi.');
    }

    public function destroy(Organization $organization)
    {
        $organization->delete();

        return back()->with('success', 'Organizasyon şirketi silindi (çöp kutusuna taşındı).');
    }

    public function restore($id)
    {
        $organization = Organization::withTrashed()->findOrFail($id);
        $organization->restore();

        return back()->with('success', 'Organizasyon şirketi geri yüklendi.');
    }

    public function togglePublish(Organization $organization)
    {
        $organization->update(['is_published' => ! $organization->is_published]);

        return back()->with('success', $organization->is_published ? 'Yayınlandı.' : 'Yayından kaldırıldı.');
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:150',
            'title' => 'nullable|string|max:60',
            'service_type' => 'required|string|max:100',
            'service_type_label' => 'required|string|max:100',
            'city_id' => 'required|exists:cities,id',
            'district' => 'nullable|string|max:120',
            'address' => 'nullable|string|max:255',
            'clinic_name' => 'nullable|string|max:150',
            'phone' => 'nullable|string|max:30',
            'google_maps_url' => 'nullable|url|max:2000',
            'rating' => 'nullable|numeric|min:0|max:5',
            'review_count' => 'nullable|integer|min:0',
        ]);

        $data['is_published'] = $request->boolean('is_published');
        $data['phone_type'] = classify_phone_type($data['phone'] ?? null);

        return $data;
    }

    private function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i = 1;
        while (Organization::withTrashed()->where('slug', $slug)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $slug = $base.'-'.(++$i);
        }

        return $slug;
    }
}
