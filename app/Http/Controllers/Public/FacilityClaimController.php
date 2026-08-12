<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Facility;
use App\Models\FacilityClaim;
use App\Services\GeoLookupService;
use Illuminate\Http\Request;

class FacilityClaimController extends Controller
{
    public function create(Request $request)
    {
        $brand = current_brand();
        $facility = $this->facilityForRequest($request, $brand['category_scope']);

        abort_if($facility->is_claimed, 404, 'Bu kurum zaten sahiplenilmis.');

        return view("themes.{$brand['theme']}.facility-claim", compact('facility'));
    }

    public function store(Request $request, GeoLookupService $geo)
    {
        $brand = current_brand();
        $facility = $this->facilityForRequest($request, $brand['category_scope']);

        abort_if($facility->is_claimed, 404, 'Bu kurum zaten sahiplenilmis.');

        $data = $request->validate([
            'applicant_name' => 'required|string|max:120',
            'applicant_email' => 'required|email|max:150',
            'applicant_phone' => 'required|string|max:30',
            'note' => 'nullable|string|max:1000',
            'document' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120',
            'lat' => 'nullable|numeric|between:-90,90',
            'lng' => 'nullable|numeric|between:-180,180',
        ]);

        if ($error = email_taken_by_other_account_type($data['applicant_email'])) {
            return back()->withErrors(['applicant_email' => $error])->withInput();
        }

        // 21 Temmuz 2026: kimlik/ruhsat belgesi hassas - 'public' yerine
        // 'local' (storage/app/private) diskte, sadece admin.documents.show
        // route'u uzerinden servis edilir (bkz. Admin/DocumentController).
        //
        // 3 Agustos 2026: bir gercek basvuruda dosya yolu DB'ye yazildi ama
        // dosyanin kendisi diske hic yazilmadi - 'throw'=>false sessizce
        // yutuyordu, admin panelinde kirik gorsel olarak ortaya cikti.
        // config/filesystems.php artik throw=>true (gercek hatalarda
        // exception firlatir), burada da yazimdan SONRA dosyanin gercekten
        // var oldugu ayrica dogrulanir - basarisizsa basvuru hic
        // OLUSTURULMAZ, kullaniciya acik bir hata gosterilip tekrar
        // denemesi istenir.
        try {
            $path = $request->file('document')->store('claims', 'local');
            if (! $path || ! \Illuminate\Support\Facades\Storage::disk('local')->exists($path)) {
                throw new \RuntimeException('Belge diske yazildiktan sonra dogrulanamadi.');
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Sahiplenme basvurusu belgesi kaydedilemedi: ' . $e->getMessage());
            \Sentry\captureException($e);

            return back()->withErrors(['document' => 'Belge yüklenirken bir sorun oluştu, lütfen tekrar deneyin.'])->withInput();
        }

        // Tarayici konumu izne bagli ve zorunlu degil (Nearby ile ayni
        // model: izin verilmezse basvuru yine de tamamlanir, sadece bu
        // alanlar bos kalir). Doluysa: admin'e sahtecilik kontrolu icin
        // basvuranin kuruma yaklasik uzakligi gosterilir.
        $applicantLat = $data['lat'] ?? null;
        $applicantLng = $data['lng'] ?? null;
        $cityName = null;
        $distanceKm = null;

        if ($applicantLat !== null && $applicantLng !== null) {
            $cityName = $geo->nearestCity($applicantLat, $applicantLng)['city'] ?? null;

            if ($facility->lat !== null && $facility->lng !== null) {
                $distanceKm = round($geo->haversine($applicantLat, $applicantLng, (float) $facility->lat, (float) $facility->lng), 1);
            }
        }

        FacilityClaim::create([
            'facility_id' => $facility->id,
            'brand' => $brand['slug'],
            'applicant_name' => $data['applicant_name'],
            'applicant_email' => $data['applicant_email'],
            'applicant_phone' => $data['applicant_phone'],
            'document_path' => $path,
            'note' => $data['note'] ?? null,
            'status' => 'pending',
            'applicant_lat' => $applicantLat,
            'applicant_lng' => $applicantLng,
            'applicant_city_name' => $cityName,
            'distance_km' => $distanceKm,
            'applicant_ip' => $request->ip(),
        ]);

        if (! in_array($facility->invitation_status, ['approved'], true)) {
            $facility->update(['invitation_status' => 'claimed', 'invitation_status_at' => now()]);
        }

        \App\Models\Admin::all()->each(fn ($admin) => notify_user(
            $admin,
            'claim_submitted',
            'Yeni sahiplenme başvurusu',
            $facility->name.' için yeni bir sahiplenme başvurusu geldi.',
        ));

        return redirect(brand_route('facilities.show', ['slug' => $facility->slug]))
            ->with('success', 'Başvurunuz alındı! Ekibimiz belgenizi inceleyip onaylayacak, ardından giriş bilgileriniz e-postanıza gönderilecek.');
    }

    private function facilityForRequest(Request $request, array $categoryScope): Facility
    {
        return Facility::where('slug', $request->route('slug'))
            ->forBrand($categoryScope)
            ->firstOrFail();
    }
}