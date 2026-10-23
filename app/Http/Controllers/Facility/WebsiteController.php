<?php

namespace App\Http\Controllers\Facility;

use App\Http\Controllers\Controller;
use App\Models\FacilityUser;
use App\Models\FacilityWebsite;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

// 23 Ekim 2026: kullanıcının talebi - kurum panelinde "Web Sitem".
// Sitesi olmayan kurum, kendi bilgilerinden hazırlanmış sitesinin
// önizlemesini görür; sitesi olan kurum "ikinci siteniz" teşvikini görür.
// Kurum başlık, alt yazı, hakkımızda, öne çıkan fotoğraf, renk ve görünen
// bölümleri kendisi düzenler. Yayına alma ve alan adı yalnızca admin'dedir.
class WebsiteController extends Controller
{
    public function index()
    {
        $user = FacilityUser::findOrFail(session('facility_user_id'));
        $facility = $user->facility->load(['images', 'subscriptionPackage']);
        $site = FacilityWebsite::forFacility($facility);
        $images = $facility->images->reject(fn ($img) => str_starts_with((string) $img->path, 'facilities/demo/'))->values();
        $hasSitePackage = $facility->isMember() && (bool) $facility->subscriptionPackage?->includesWebsite();

        return view('themes._shared.facility.website', compact('user', 'facility', 'site', 'images', 'hasSitePackage'));
    }

    public function update(Request $request)
    {
        $user = FacilityUser::findOrFail(session('facility_user_id'));
        $facility = $user->facility;
        $site = FacilityWebsite::forFacility($facility);

        $data = self::validateContent($request, $facility->id);
        $changed = $site->applyChanges($data, 'kurum', $user->id, $user->name ?: $user->phone);

        return redirect(brand_route('facility.website.index'))
            ->with('success', $changed ? 'Siteniz güncellendi.' : 'Değişiklik yapılmadı.');
    }

    /**
     * Kurum paneli ve admin panelinde ortak içerik doğrulaması.
     */
    public static function validateContent(Request $request, int $facilityId): array
    {
        $data = $request->validate([
            'hero_title' => 'nullable|string|max:150',
            'hero_subtitle' => 'nullable|string|max:255',
            'about_text' => 'nullable|string|max:5000',
            'hero_image_id' => ['nullable', 'integer', Rule::exists('facility_images', 'id')->where('facility_id', $facilityId)],
            'theme' => ['required', Rule::in(array_keys(FacilityWebsite::THEMES))],
            'sections' => 'nullable|array',
            'sections.*' => 'in:1',
        ]);
        $acik = array_keys($data['sections'] ?? []);
        $data['sections'] = collect(array_keys(FacilityWebsite::SECTIONS))->mapWithKeys(fn ($k) => [$k => in_array($k, $acik, true)])->all();
        foreach (['hero_title', 'hero_subtitle', 'about_text'] as $alan) {
            $data[$alan] = isset($data[$alan]) && trim((string) $data[$alan]) !== '' ? trim((string) $data[$alan]) : null;
        }
        $data['hero_image_id'] = $data['hero_image_id'] ?? null;

        return $data;
    }
}
