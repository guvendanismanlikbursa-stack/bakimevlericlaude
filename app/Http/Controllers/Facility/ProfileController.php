<?php

namespace App\Http\Controllers\Facility;

use App\Http\Controllers\Controller;
use App\Models\ChildFacilityDetail;
use App\Models\City;
use App\Models\ElderlyFacilityDetail;
use App\Models\FacilityImage;
use App\Models\FacilityUser;
use App\Models\RehabFacilityDetail;
use App\Services\ImageCompressionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    private const MAX_GALLERY_IMAGES = 10;

    public function edit()
    {
        $brand = current_brand();
        $user = FacilityUser::with(
            'facility.images',
            'facility.category',
            'facility.elderlyDetail',
            'facility.childDetail',
            'facility.rehabDetail'
        )->findOrFail(session('facility_user_id'));

        $facility = $user->facility;
        $cities = City::orderBy('name')->get();
        $serviceSection = service_section_for_scope($facility->category->brand_scope);
        $sectionDetailFields = $this->sectionDetailFields($serviceSection);
        $sectionDetails = $this->sectionDetailRecord($facility, $serviceSection['slug'] ?? null)?->details ?? [];

        return view("themes.{$brand['theme']}.facility.profile", [
            'user' => $user,
            'facility' => $facility,
            'cities' => $cities,
            'serviceSection' => $serviceSection,
            'profileQuality' => $facility->profileQuality(),
            'sectionDetailFields' => $sectionDetailFields,
            'sectionDetails' => $sectionDetails,
        ]);
    }

    public function update(Request $request)
    {
        $user = FacilityUser::with('facility.category')->findOrFail(session('facility_user_id'));
        $facility = $user->facility;

        $data = $request->validate([
            'name' => 'required|string|max:180',
            'city_id' => 'required|exists:cities,id',
            'district' => 'nullable|string|max:120',
            'address' => 'nullable|string|max:500',
            'phone' => 'nullable|string|max:30',
            'description' => 'nullable|string|max:5000',
            'capacity' => 'nullable|integer|min:0',
            'price_min' => 'nullable|numeric|min:0',
            'price_max' => 'nullable|numeric|min:0|gte:price_min',
            'services_raw' => 'nullable|string',
            'services' => 'nullable|array',
            'services.*' => 'nullable|string|max:120',
            'section_details' => 'nullable|array',
            'section_details.*' => 'nullable|string|max:1000',
        ]);

        $sectionDetailsInput = $data['section_details'] ?? [];
        unset($data['section_details']);

        $data['services'] = collect(array_merge(
            explode(',', $data['services_raw'] ?? ''),
            $request->input('services', [])
        ))->map(fn ($s) => trim($s))->filter()->unique()->values()->all();
        unset($data['services_raw']);

        $facility->update($data);

        $serviceSection = service_section_for_scope($facility->category->brand_scope);
        $allowedKeys = collect($this->sectionDetailFields($serviceSection))->pluck('key')->all();
        $details = collect($sectionDetailsInput)
            ->only($allowedKeys)
            ->map(fn ($value) => is_string($value) ? trim($value) : $value)
            ->filter(fn ($value) => filled($value))
            ->all();

        $detailModel = $this->sectionDetailModel($serviceSection['slug'] ?? null);
        if ($detailModel) {
            $detailModel::updateOrCreate(
                ['facility_id' => $facility->id],
                ['details' => $details]
            );
        }

        return back()->with('success', 'Kurum bilgileriniz güncellendi.');
    }

    public function uploadImage(Request $request)
    {
        $user = FacilityUser::findOrFail(session('facility_user_id'));
        $currentCount = FacilityImage::where('facility_id', $user->facility_id)->count();

        $request->validate([
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'images' => 'nullable|array|max:10',
            'images.*' => 'image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        $files = collect($request->file('images', []));
        if ($request->file('image')) {
            $files->push($request->file('image'));
        }

        if ($files->isEmpty()) {
            throw ValidationException::withMessages(['images' => 'En az bir görsel seçmelisiniz.']);
        }

        if ($currentCount + $files->count() > self::MAX_GALLERY_IMAGES) {
            $remaining = max(0, self::MAX_GALLERY_IMAGES - $currentCount);
            throw ValidationException::withMessages(['images' => "En fazla 10 görsel eklenebilir. Kalan yükleme hakkı: {$remaining}."]);
        }

        foreach ($files as $i => $file) {
            $path = app(ImageCompressionService::class)->store($file, 'facilities');

            FacilityImage::create([
                'facility_id' => $user->facility_id,
                'path' => $path,
                'sort_order' => $currentCount + $i,
            ]);
        }

        return back()->with('success', 'Görseller eklendi.');
    }

    public function deleteImage(Request $request)
    {
        $user = FacilityUser::findOrFail(session('facility_user_id'));
        $image = $request->route('image');
        if (! $image instanceof FacilityImage) {
            $image = FacilityImage::findOrFail($image);
        }

        abort_unless($image->facility_id === $user->facility_id, 403);

        Storage::disk('public')->delete($image->path);
        $image->delete();

        return back()->with('success', 'Görsel silindi.');
    }

    private function sectionDetailFields(?array $serviceSection): array
    {
        return collect($serviceSection['profile_fields'] ?? [])
            ->map(fn ($label) => [
                'key' => Str::slug($label),
                'label' => $label,
            ])
            ->values()
            ->all();
    }

    private function sectionDetailRecord($facility, ?string $sectionSlug)
    {
        return match ($sectionSlug) {
            'yasli-bakim' => $facility->elderlyDetail,
            'cocuk' => $facility->childDetail,
            'rehabilitasyon' => $facility->rehabDetail,
            default => null,
        };
    }

    private function sectionDetailModel(?string $sectionSlug): ?string
    {
        return match ($sectionSlug) {
            'yasli-bakim' => ElderlyFacilityDetail::class,
            'cocuk' => ChildFacilityDetail::class,
            'rehabilitasyon' => RehabFacilityDetail::class,
            default => null,
        };
    }
}
