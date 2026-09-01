<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\FacilityCategory;
use App\Models\FacilityRegistration;
use App\Services\FacilityDuplicateDetector;
use App\Services\GeoLookupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class FacilityRegistrationController extends Controller
{
    public function create(Request $request)
    {
        $brand = current_brand();
        $categories = FacilityCategory::whereIn('brand_scope', $brand['category_scope'])->orderBy('name')->get();
        $cities = City::orderBy('name')->get();

        return view("themes.{$brand['theme']}.facility-register", compact('categories', 'cities'));
    }

    public function store(Request $request, GeoLookupService $geo, FacilityDuplicateDetector $duplicateDetector)
    {
        // 15 Agustos 2026: honeypot - bkz. partials/honeypot.blade.php.
        // Ayri validate() cagrisi bilerek - $data spread ile create()'e
        // gectigi icin 'website' anahtarinin $data'ya karismasi istenmiyor.
        $request->validate(['website' => 'max:0']);

        $brand = current_brand();
        $data = $this->validateData($request, $brand);

        if ($error = email_taken_by_other_account_type($data['applicant_email'])) {
            return back()->withErrors(['applicant_email' => $error])->withInput();
        }

        // 19 Agustos 2026: kullanicinin talebi - kurum kendi kendine kayit
        // olurken, ayni kurum sistemde zaten (on-kayitli olarak, ör. Veri
        // Cekici'den) varsa mukerrer kayit olusmasin. Ayni eslestirme
        // mantigini (bkz. FacilityDuplicateDetector) Veri Cekici onayindan
        // buraya da tasidik. Sahiplenilmemis eslesme bulunursa kullaniciyi
        // yeni bir bekleyen basvuru yerine DOGRUDAN o kaydin sahiplenme
        // formuna yonlendiriyoruz - panele cok daha hizli erisir. Zaten
        // sahiplenilmis bir eslesme varsa (baskasi yonetiyor) acik bir
        // hata mesaji gosterilir.
        $city = City::find($data['city_id']);
        if ($city && $duplicate = $duplicateDetector->findDuplicate($data['phone'] ?? null, $data['name'] ?? null, $data['address'] ?? null, $city)) {
            if ($duplicate->is_claimed) {
                return back()->withErrors([
                    'name' => 'Bu kurum sistemimizde zaten kayıtlı ve başka bir yetkili tarafından yönetiliyor görünüyor. Bu sizin kurumunuzsa lütfen bizimle iletişime geçin.',
                ])->withInput();
            }

            return redirect(brand_route('facility-claim.create', $duplicate->slug))
                ->with('info', 'Kurumunuz "'.$duplicate->name.'" adıyla sistemimizde zaten ön-kayıtlı görünüyor. Yeni bir başvuru oluşturmak yerine bu kaydı sahiplenerek panelinize hemen erişebilirsiniz.');
        }

        $applicantLat = $data['lat'] ?? null;
        $applicantLng = $data['lng'] ?? null;
        unset($data['lat'], $data['lng'], $data['consent']);
        $cityName = ($applicantLat !== null && $applicantLng !== null)
            ? ($geo->nearestCity($applicantLat, $applicantLng)['city'] ?? null)
            : null;

        $registration = FacilityRegistration::create([
            ...$data,
            'brand' => $brand['slug'],
            'status' => 'pending',
            'applicant_lat' => $applicantLat,
            'applicant_lng' => $applicantLng,
            'applicant_city_name' => $cityName,
            'applicant_ip' => $request->ip(),
            'consent_accepted_at' => now(),
            'consent_ip' => $request->ip(),
        ]);

        \App\Models\Admin::all()->each(fn ($admin) => notify_user(
            $admin,
            'registration_submitted',
            'Yeni kurum kaydı başvurusu',
            $registration->name.' için yeni bir kurum kaydı başvurusu geldi.',
        ));

        return redirect(brand_route('facility-registration.received'))
            ->with('success', 'Kurum kaydı başvurunuz alındı. Admin incelemesinden sonra e-posta ile bilgilendirileceksiniz.');
    }

    public function received(Request $request)
    {
        $brand = current_brand();

        return view("themes.{$brand['theme']}.facility-register-received");
    }

    public function edit(Request $request, FacilityRegistration $registration, string $hash)
    {
        $this->authorizeEdit($registration, $hash);

        $brand = current_brand();
        $categories = FacilityCategory::whereIn('brand_scope', $brand['category_scope'])->orderBy('name')->get();
        $cities = City::orderBy('name')->get();

        return view("themes.{$brand['theme']}.facility-register-edit", compact('registration', 'categories', 'cities'));
    }

    public function update(Request $request, FacilityRegistration $registration, string $hash, GeoLookupService $geo)
    {
        $this->authorizeEdit($registration, $hash);

        $brand = current_brand();
        $data = $this->validateData($request, $brand);

        if ($error = email_taken_by_other_account_type($data['applicant_email'])) {
            return back()->withErrors(['applicant_email' => $error])->withInput();
        }

        $applicantLat = $data['lat'] ?? null;
        $applicantLng = $data['lng'] ?? null;
        unset($data['lat'], $data['lng'], $data['consent']);
        $cityName = ($applicantLat !== null && $applicantLng !== null)
            ? ($geo->nearestCity($applicantLat, $applicantLng)['city'] ?? null)
            : null;

        DB::transaction(function () use ($registration, $data, $applicantLat, $applicantLng, $cityName, $request) {
            $registration = FacilityRegistration::whereKey($registration->id)->lockForUpdate()->firstOrFail();
            $this->authorizeEdit($registration, sha1($registration->applicant_email));

            $registration->update([
                ...$data,
                'status' => 'pending',
                'admin_note' => null,
                'reviewed_by' => null,
                'reviewed_at' => null,
                'applicant_lat' => $applicantLat,
                'applicant_lng' => $applicantLng,
                'applicant_city_name' => $cityName,
                'applicant_ip' => $request->ip(),
                // 1 Eylul 2026: kullanicinin bildirdigi gercek eksik -
                // store()'da set edilen consent_accepted_at/consent_ip,
                // duzeltme sonrasi YENIDEN onaylanan riza icin burada hic
                // guncellenmiyordu.
                'consent_accepted_at' => now(),
                'consent_ip' => $request->ip(),
            ]);

            log_admin_event('facility_registration_resubmitted', $registration);
        });

        return redirect(brand_route('facility-registration.received'))
            ->with('success', 'Başvurunuz güncellenip tekrar admin incelemesine gönderildi.');
    }

    private function authorizeEdit(FacilityRegistration $registration, string $hash): void
    {
        abort_unless(hash_equals(sha1($registration->applicant_email), $hash), 403);
        abort_if($registration->status !== 'revision_requested', 403, 'Bu başvuru zaten güncellenmiş veya işlenmiş.');
    }

    private function validateData(Request $request, array $brand): array
    {
        return $request->validate([
            'name' => 'required|string|max:180',
            'facility_category_id' => [
                'required',
                Rule::exists('facility_categories', 'id')->whereIn('brand_scope', $brand['category_scope']),
            ],
            'city_id' => 'required|exists:cities,id',
            'district' => 'nullable|string|max:120',
            'address' => 'nullable|string|max:500',
            'phone' => 'nullable|string|max:30',
            'description' => 'nullable|string|max:5000',
            'capacity' => 'nullable|integer|min:0',
            'price_min' => 'nullable|numeric|min:0',
            'price_max' => 'nullable|numeric|min:0|gte:price_min',
            'applicant_name' => 'required|string|max:120',
            'applicant_email' => 'required|email|max:150',
            'applicant_phone' => 'required|string|max:30',
            'lat' => 'nullable|numeric|between:-90,90',
            'lng' => 'nullable|numeric|between:-180,180',
            // 19 Agustos 2026: kullanicinin bildirdigi gercek eksik - aile
            // kaydinda KVKK acik riza onay kutusu vardi, burada yoktu -
            // kisisel veri (ad/e-posta/telefon) onaysiz toplaniyordu.
            'consent' => 'required|accepted',
        ], [
            'consent.required' => 'Açık rıza metnini onaylamadan başvuru gönderemezsiniz.',
            'consent.accepted' => 'Açık rıza metnini onaylamadan başvuru gönderemezsiniz.',
        ]);
    }
}
