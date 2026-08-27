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
            'notificationGroups' => notification_preference_groups('facility'),
        ]);
    }

    /**
     * 12 Agustos 2026: kullanicinin talebi - bilerek ANA profil formundan
     * (update()) AYRI, kendi <form>'u ve rotasi olan kucuk bir islem -
     * o formun buyuk/karmasik validasyonuna hic dokunmadan, yanlislikla
     * profil verisini bozma riski olmadan bildirim tercihlerini kaydeder.
     */
    public function updateNotifications(Request $request)
    {
        $user = FacilityUser::findOrFail(session('facility_user_id'));

        $prefs = [];
        foreach (notification_preference_groups('facility') as $group => $meta) {
            foreach ($meta['types'] as $type) {
                $prefs[$type] = [
                    'email' => $request->boolean("notifications.{$group}.email"),
                    'push' => $request->boolean("notifications.{$group}.push"),
                ];
            }
        }

        $user->update(['notification_preferences' => $prefs]);

        return back()->with('success', 'Bildirim tercihleriniz güncellendi.');
    }

    /**
     * 19 Agustos 2026: kullanicinin talebi - bkz. Family\ProfileController::
     * destroy() ayni tarihli yorum, ayni desen. DIKKAT: bu SADECE bu kurum
     * yetkilisinin KENDI hesabini (ad/e-posta/telefon) hedefler - kurumun
     * kendisi (Facility) veya varsa DIGER yetkili hesaplari BU TALEPTEN
     * ETKILENMEZ, kisisel veri sadece bu bireye ait.
     */
    public function destroy(Request $request)
    {
        $user = FacilityUser::findOrFail(session('facility_user_id'));

        $request->validate(['password' => 'required|string']);
        if (! \Illuminate\Support\Facades\Hash::check($request->password, $user->password)) {
            return back()->withErrors(['password' => 'Şifre hatalı, talep oluşturulmadı.']);
        }

        if (\App\Models\AccountDeletionRequest::where('requestable_type', FacilityUser::class)
            ->where('requestable_id', $user->id)->where('status', 'pending')->exists()) {
            return back()->with('info', 'Silme talebiniz zaten alınmış, inceleniyor.');
        }

        \App\Models\AccountDeletionRequest::create([
            'requestable_type' => FacilityUser::class,
            'requestable_id' => $user->id,
            'requested_at' => now(),
            'status' => 'pending',
        ]);

        \App\Models\Admin::all()->each(fn ($admin) => notify_user(
            $admin,
            'account_deletion_requested',
            'Hesap silme talebi',
            $user->name.' ('.$user->facility?->name.') hesabını silmek istiyor.',
        ));

        return back()->with('success', 'Hesap silme talebiniz alındı. Ekibimiz talebinizi inceleyip kısa süre içinde işleme alacak.');
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

        // 26 Agustos 2026: kullanicinin bildirdigi gercek hata (admin panelinde
        // AVIF formatinda bir gorsel yuzunden kurum kaydi hic olusturulamiyordu,
        // bkz. FacilityController ayni tarihli yorum) - sabit bir jpg/png/webp
        // listesi yerine sadece dosya/boyut kontrolu yapilir, GERCEK format
        // testi asagida ImageCompressionService'in decode denemesiyle yapilir
        // (GD'nin derlemesinin destekledigi HERHANGI bir formati kabul eder).
        $request->validate([
            'image' => 'nullable|file|max:5120',
            'images' => 'nullable|array|max:10',
            'images.*' => 'file|max:5120',
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

        // 3 Agustos 2026: bkz. FacilityClaimController ayni yorum - disk
        // yazma hatasi eskiden ('throw'=>false ile) sessizce yutulup DB'ye
        // gecerli gorunen ama gercekte var olmayan bir dosya yolu
        // yaziliyordu (admin panelinde/kurum kartinda kirik gorsel).
        // Simdi her dosya icin yazimdan sonra ayrica dogrulama yapilir;
        // BIR gorsel bile basarisiz olursa TUM istek geri alinir (hicbir
        // kurumun bazi gorselleri sessizce eksik kalmasin diye), kullaniciya
        // acik bir hata gosterilip tekrar denemesi istenir.
        $uploaded = [];
        try {
            foreach ($files as $i => $file) {
                try {
                    $path = app(ImageCompressionService::class)->store($file, 'facilities');
                } catch (\RuntimeException $e) {
                    if ($e->getMessage() === 'unsupported_image_format') {
                        throw new \InvalidArgumentException("\"{$file->getClientOriginalName()}\" desteklenmeyen veya bozuk bir görsel dosyası.");
                    }
                    throw $e;
                }
                if (! $path || ! \Illuminate\Support\Facades\Storage::disk('public')->exists($path)) {
                    throw new \RuntimeException('Gorsel diske yazildiktan sonra dogrulanamadi.');
                }
                $uploaded[] = $path;

                FacilityImage::create([
                    'facility_id' => $user->facility_id,
                    'path' => $path,
                    'sort_order' => $currentCount + $i,
                ]);
            }
        } catch (\Throwable $e) {
            foreach ($uploaded as $path) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($path);
            }
            FacilityImage::where('facility_id', $user->facility_id)->whereIn('path', $uploaded)->delete();

            \Illuminate\Support\Facades\Log::error('Kurum galeri gorseli kaydedilemedi: ' . $e->getMessage(), ['facility_id' => $user->facility_id]);
            \Sentry\captureException($e);

            $message = $e instanceof \InvalidArgumentException ? $e->getMessage() : 'Görsel(ler) yüklenirken bir sorun oluştu, lütfen tekrar deneyin.';

            return back()->withErrors(['images' => $message]);
        }

        // 19 Agustos 2026: kullanicinin talebi - hangi domain'den yuklenirse
        // yuklensin ayni anda diger 2 domain'e de kopyalanir (bkz.
        // CrossDomainImageSync). Butun batch basariyla bittikten SONRA
        // gonderiliyor - ust taraftaki hata durumunda geri alinan (silinen)
        // gorseller hic senkronize edilmemis olur.
        $imageSync = app(\App\Services\CrossDomainImageSync::class);
        foreach ($uploaded as $path) {
            $imageSync->syncStore($path);
        }

        return back()->with('success', 'Görseller eklendi.');
    }

    /**
     * 19 Agustos 2026: kullanicinin talebi - kurum yetkilisi kendi 10
     * gorselinden hangisinin ANA (kapak) gorsel oldugunu secebilsin. Bkz.
     * Admin\FacilityController::setPrimaryImage() ayni mantik.
     */
    public function setPrimaryImage(Request $request)
    {
        $user = FacilityUser::findOrFail(session('facility_user_id'));
        $image = $request->route('image');
        if (! $image instanceof FacilityImage) {
            $image = FacilityImage::findOrFail($image);
        }

        abort_unless((int) $image->facility_id === (int) $user->facility_id, 403);

        FacilityImage::where('facility_id', $user->facility_id)->update(['is_primary' => false]);
        $image->update(['is_primary' => true]);

        return back()->with('success', 'Ana görsel güncellendi.');
    }

    public function deleteImage(Request $request, \App\Services\CrossDomainImageSync $imageSync)
    {
        $user = FacilityUser::findOrFail(session('facility_user_id'));
        $image = $request->route('image');
        if (! $image instanceof FacilityImage) {
            $image = FacilityImage::findOrFail($image);
        }

        abort_unless((int) $image->facility_id === (int) $user->facility_id, 403);

        Storage::disk('public')->delete($image->path);
        $imageSync->syncDelete($image->path);
        $image->delete();

        return back()->with('success', 'Görsel silindi.');
    }

    /**
     * 19 Agustos 2026: kullanicinin talebi - "kurum panellerine yemek
     * listesi bolumu, kurum yetkilisi haftalik yemek listesinin gorselini
     * yuklesin". Galeriden ayri, TEK bir gorsel - her yeni yukleme
     * eskisinin (varsa) yerine gecer (eski dosya + cross-domain kopyalari
     * silinir), boylece kurum yetkilisi her hafta ayni yerden guncelleyebilir.
     *
     * DIKKAT (19 Agustos 2026, kullanicinin uyarisi): sahiplenilmemis
     * kurumlara toplu atanan PAYLASILAN bir ornek gorsel var
     * (facilities/demo/... - bkz. OpsController::menuImageDemoApply()).
     * Bir kurum sahiplenilip yetkilisi KENDI gercek listesini yuklerse,
     * eski deger bu paylasilan dosya olabilir - ASLA silinmemeli, aksi
     * halde ayni gorseli kullanan TUM diger on-kayitli kurumlarin
     * (3 domain'de birden) yemek listesi kirilir. Sadece kurum-basina
     * ozel (facilities/demo/ ile baslamayan) eski dosyalar silinir.
     */
    public function uploadMenuImage(Request $request, \App\Services\CrossDomainImageSync $imageSync)
    {
        $user = FacilityUser::findOrFail(session('facility_user_id'));
        $facility = $user->facility;

        $request->validate([
            'menu_image' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        $path = app(ImageCompressionService::class)->store($request->file('menu_image'), 'facilities');
        if (! $path || ! Storage::disk('public')->exists($path)) {
            \Illuminate\Support\Facades\Log::error('Yemek listesi gorseli diske yazildiktan sonra dogrulanamadi.', ['facility_id' => $facility->id]);

            return back()->withErrors(['menu_image' => 'Görsel yüklenirken bir sorun oluştu, lütfen tekrar deneyin.']);
        }

        $oldPath = $facility->menu_image_path;

        $facility->update([
            'menu_image_path' => $path,
            'menu_image_updated_at' => now(),
        ]);

        $imageSync->syncStore($path);

        if ($oldPath && ! str_starts_with($oldPath, 'facilities/demo/')) {
            Storage::disk('public')->delete($oldPath);
            $imageSync->syncDelete($oldPath);
        }

        return back()->with('success', 'Yemek listesi güncellendi.');
    }

    public function deleteMenuImage(Request $request, \App\Services\CrossDomainImageSync $imageSync)
    {
        $user = FacilityUser::findOrFail(session('facility_user_id'));
        $facility = $user->facility;

        if ($facility->menu_image_path) {
            // bkz. uploadMenuImage() DIKKAT notu - paylasilan ornek dosya
            // asla fiziksel olarak silinmez, sadece bu kurumdan kaldirilir.
            if (! str_starts_with($facility->menu_image_path, 'facilities/demo/')) {
                Storage::disk('public')->delete($facility->menu_image_path);
                $imageSync->syncDelete($facility->menu_image_path);
            }
            $facility->update(['menu_image_path' => null, 'menu_image_updated_at' => null]);
        }

        return back()->with('success', 'Yemek listesi kaldırıldı.');
    }

    // 27 Agustos 2026: kullanicinin talebi - anlasmali (is_broker_managed)
    // kurum sahibi, admin'e ek olarak KENDI panelinden de tanitim videosu
    // yukleyip yonetebilsin. Admin\FacilityController::storeUploadedVideo
    // ile AYNI kurallar (60sn, ffmpeg sikistirma, sunucu tarafi is_broker_managed
    // kontrolu) - iki taraf da ayni kurumu yonetebildigi icin mantik
    // tekrarlaniyor ama controller'lar farkli katmanlarda (admin oturumu
    // vs facility_user oturumu) oldugu icin ortak bir trait/service'e
    // cikarmak bu asamada gereksiz karmasiklik olurdu.
    public function uploadVideo(Request $request)
    {
        $user = FacilityUser::findOrFail(session('facility_user_id'));
        $facility = $user->facility;

        if (! $facility->is_broker_managed) {
            // 27 Agustos 2026: kullanicinin talebi - alan anlasmasiz kurumlara
            // da GORUNSUN (komisyonlu calismaya tesvik) ama pasif kalsin,
            // yuklemeye calisinca bu tesvik mesaji cikssin.
            return back()->withErrors(['video' => 'Video yalnızca komisyon usulü anlaşmalı kurumlar tarafından eklenebilir. Detaylı bilgi için yöneticinizle (admin) iletişime geçin.']);
        }

        $request->validate(['video' => 'required|file']);

        if ($request->file('video')->getSize() > 200 * 1024 * 1024) {
            return back()->withErrors(['video' => 'Video 200MB sınırını aşıyor, eklenemedi.']);
        }

        set_time_limit(180);

        try {
            $path = app(\App\Services\VideoCompressionService::class)->store($request->file('video'), 'facilities/videos');
        } catch (\RuntimeException $e) {
            $message = match ($e->getMessage()) {
                'ffmpeg_unavailable' => 'Video işleme aracı şu an kullanılamıyor, lütfen daha sonra tekrar deneyin.',
                'video_too_long' => 'Video 60 saniyeden uzun olamaz.',
                default => 'Desteklenmeyen veya bozuk bir video dosyası.',
            };

            return back()->withErrors(['video' => $message]);
        }

        if (! $path || ! Storage::disk('public')->exists($path)) {
            \Illuminate\Support\Facades\Log::error('Kurum videosu (kurum paneli) kaydedilemedi.', ['facility_id' => $facility->id]);

            return back()->withErrors(['video' => 'Video yüklenirken bir sorun oluştu, lütfen tekrar deneyin.']);
        }

        $oldPath = $facility->video_path;
        $facility->update(['video_path' => $path, 'video_updated_at' => now()]);

        if ($oldPath) {
            Storage::disk('public')->delete($oldPath);
        }

        return back()->with('success', 'Tanıtım videosu güncellendi.');
    }

    public function deleteVideo(Request $request)
    {
        $user = FacilityUser::findOrFail(session('facility_user_id'));
        $facility = $user->facility;

        if ($facility->video_path) {
            Storage::disk('public')->delete($facility->video_path);
            $facility->update(['video_path' => null, 'video_updated_at' => null]);
        }

        return back()->with('success', 'Tanıtım videosu kaldırıldı.');
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
