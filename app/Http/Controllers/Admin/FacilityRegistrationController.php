<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\FacilityRegistrationApprovedMail;
use App\Mail\FacilityRegistrationRevisionRequestedMail;
use App\Models\BalanceLog;
use App\Models\Facility;
use App\Models\FacilityRegistration;
use App\Models\FacilityUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class FacilityRegistrationController extends Controller
{
    public function index(Request $request)
    {
        $query = FacilityRegistration::with(['category', 'city']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        } else {
            $query->where('status', 'pending');
        }

        $registrations = $query->latest()->paginate(15)->withQueryString();

        return view('admin.registrations.index', compact('registrations'));
    }

    public function show(FacilityRegistration $registration)
    {
        $registration->load(['category', 'city']);

        return view('admin.registrations.show', compact('registration'));
    }

    public function approve(Request $request, FacilityRegistration $registration)
    {
        $temporaryPassword = Str::password(14);
        $freeCredits = (int) config('platform.free_claim_credits', 5);

        $mailPayload = DB::transaction(function () use ($registration, $temporaryPassword, $freeCredits) {
            $registration = FacilityRegistration::whereKey($registration->id)->lockForUpdate()->firstOrFail();
            // 12 Agustos 2026: kullanicinin talebi - admin daha once
            // reddettigi bir kayit basvurusunu gerekirse sonradan
            // onaylayabilmeli; sadece zaten ONAYLANMIS bir basvurunun
            // tekrar islenmesini engelliyoruz.
            abort_if($registration->status === 'approved', 400, 'Bu basvuru zaten onaylanmis.');

            if (FacilityUser::where('email', $registration->applicant_email)->exists()) {
                return ['error' => 'Bu e-posta zaten bir kurum hesabina ait. Baska bir basvuru/e-posta gerekiyor.'];
            }

            if ($error = email_taken_by_other_account_type($registration->applicant_email)) {
                return ['error' => $error];
            }

            $facility = Facility::create([
                'name' => $registration->name,
                'slug' => $this->uniqueSlug($registration->name),
                'city_id' => $registration->city_id,
                'facility_category_id' => $registration->facility_category_id,
                'district' => $registration->district,
                'address' => $registration->address,
                'phone' => $registration->phone,
                'description' => $registration->description,
                'capacity' => $registration->capacity,
                'price_min' => $registration->price_min,
                'price_max' => $registration->price_max,
                'is_published' => true,
                'is_claimed' => true,
                'claimed_at' => now(),
                'balance' => 0,
                'free_quote_credits' => $freeCredits,
                'invitation_status' => 'approved',
                'invitation_status_at' => now(),
                'source' => 'self_registered',
            ]);

            $facilityUser = FacilityUser::create([
                'facility_id' => $facility->id,
                'name' => $registration->applicant_name,
                'email' => $registration->applicant_email,
                'phone' => $registration->applicant_phone,
                'password' => Hash::make($temporaryPassword),
                'must_change_password' => true,
                'status' => 'active',
                'email_verified_at' => null,
                'signup_lat' => $registration->applicant_lat,
                'signup_lng' => $registration->applicant_lng,
                'signup_city_name' => $registration->applicant_city_name,
                'signup_ip' => $registration->applicant_ip,
            ]);

            BalanceLog::create([
                'facility_id' => $facility->id,
                'type' => 'registration_bonus_credits',
                'amount' => 0,
                'credits_amount' => $freeCredits,
                'balance_after' => $facility->balance,
                'credits_after' => $facility->free_quote_credits,
                'admin_id' => session('admin_id'),
                'note' => 'Kurum kaydi onayi bonus hakki.',
            ]);

            $registration->update([
                'status' => 'approved',
                'reviewed_by' => session('admin_id'),
                'reviewed_at' => now(),
            ]);

            return [
                'facility' => $facility,
                'email' => $registration->applicant_email,
                'password' => $temporaryPassword,
                'login_url' => $this->facilityLoginUrl($registration->brand),
            ];
        });

        if (isset($mailPayload['error'])) {
            return back()->withErrors(['email' => $mailPayload['error']]);
        }

        try {
            Mail::to($mailPayload['email'])->sendNow(
                new FacilityRegistrationApprovedMail($mailPayload['facility'], $mailPayload['email'], $mailPayload['password'], $mailPayload['login_url'])
            );
        } catch (\Throwable $e) {
            Log::warning('Kurum kaydi onay maili gonderilemedi: ' . $e->getMessage(), ['facility_id' => $mailPayload['facility']->id]);
        }

        try {
            Mail::to($mailPayload['email'])->sendNow(
                new \App\Mail\FacilityWelcomeMail($mailPayload['facility'], $mailPayload['email'], config("brands.brands.{$registration->brand}.name", $registration->brand), $mailPayload['login_url'])
            );
        } catch (\Throwable $e) {
            Log::warning('Kurum hos geldin maili gonderilemedi: ' . $e->getMessage(), ['facility_id' => $mailPayload['facility']->id]);
        }

        $facilityUser = FacilityUser::where('email', $mailPayload['email'])->firstOrFail();

        \App\Http\Controllers\Facility\EmailVerificationController::send(
            $facilityUser,
            config("brands.brands.{$registration->brand}")
        );

        log_admin_event('facility_registration_approved', $registration, ['facility_id' => $mailPayload['facility']->id]);

        notify_user($facilityUser, 'registration_approved', 'Kurum kaydınız onaylandı', 'Kurum hesabınız aktifleşti, giriş bilgileri e-posta ile gönderildi.');

        // 30 Temmuz 2026: FacilityClaimController::approve() ile ayni gerekce
        // - mail gecikirse/gitmezse admin gecici sifreyi manuel iletebilsin.
        return redirect()->route('admin.registrations.index')->with('success', "Başvuru onaylandı, giriş bilgileri e-posta ile gönderildi. E-posta ulaşmazsa şu bilgileri kullanıcıya siz iletebilirsiniz — E-posta: {$mailPayload['email']} / Geçici şifre: {$mailPayload['password']}");
    }

    public function requestRevision(Request $request, FacilityRegistration $registration)
    {
        $data = $request->validate(['admin_note' => 'required|string|max:1000']);

        $payload = DB::transaction(function () use ($registration, $data) {
            $registration = FacilityRegistration::whereKey($registration->id)->lockForUpdate()->firstOrFail();
            abort_if($registration->status !== 'pending', 400, 'Bu basvuru zaten islenmis.');

            $registration->update([
                'status' => 'revision_requested',
                'admin_note' => $data['admin_note'],
                'reviewed_by' => session('admin_id'),
                'reviewed_at' => now(),
            ]);

            return $registration;
        });

        $editUrl = $this->facilityRegistrationEditUrl($payload);

        try {
            Mail::to($payload->applicant_email)->sendNow(
                new FacilityRegistrationRevisionRequestedMail($payload, $data['admin_note'], $editUrl)
            );
        } catch (\Throwable $e) {
            Log::warning('Kurum kaydi duzeltme talebi maili gonderilemedi: ' . $e->getMessage(), ['registration_id' => $payload->id]);
        }

        log_admin_event('facility_registration_revision_requested', $payload, ['admin_note' => $data['admin_note']]);

        return back()->with('success', 'Basvuru sahibinden duzeltme istendi, e-posta gonderildi.');
    }

    // 12 Agustos 2026: kullanicinin talebi - "talep onaylansa da red edilse
    // de mutlaka basvuru sahibinin mailine bildirim gitmeli". Onceden
    // kurum kayit basvurusunu reddetmenin tek yolu "Sil" (destroy()) idi -
    // sessizce siliyordu, basvuru sahibi asla haberdar olmuyordu. Artik
    // FacilityClaimController::reject() ile birebir ayni desen: durum
    // 'rejected' olarak kaydedilir, sebep varsa not düşülür, mail atılır.
    public function reject(Request $request, FacilityRegistration $registration)
    {
        $data = $request->validate(['reject_note' => 'nullable|string|max:1000']);

        DB::transaction(function () use ($registration, $data) {
            $registration = FacilityRegistration::whereKey($registration->id)->lockForUpdate()->firstOrFail();
            abort_if($registration->status !== 'pending', 400, 'Bu basvuru zaten islenmis.');

            $registration->update([
                'status' => 'rejected',
                'admin_note' => $data['reject_note'] ?? null,
                'reviewed_by' => session('admin_id'),
                'reviewed_at' => now(),
            ]);
        });

        log_admin_event('facility_registration_rejected', $registration, ['admin_note' => $data['reject_note'] ?? null]);

        try {
            Mail::to($registration->applicant_email)->sendNow(new \App\Mail\FacilityRegistrationRejectedMail($registration));
        } catch (\Throwable $e) {
            Log::warning('Kurum kaydi red maili gonderilemedi: ' . $e->getMessage(), ['registration_id' => $registration->id]);
        }

        return back()->with('success', 'Basvuru reddedildi, basvuru sahibine e-posta gonderildi.');
    }

    public function destroy(FacilityRegistration $registration)
    {
        $registration->delete();

        log_admin_event('facility_registration_deleted', $registration);

        return back()->with('success', 'Basvuru silindi.');
    }

    /**
     * Admin, kurumun kendi gercek marka domain'inden farkli bir domain'den
     * (admin paneli) basvuru onaylayabilir; bu yuzden giris linki CURRENT
     * request'in host'una degil, hedef markanin kendi yapilandirilmis
     * domain'ine gore uretilir. Local/testing'de gercek .com domain'ler DNS'te
     * cozulmedigi icin (bkz. config/brands.php domains[0]), bu ortamlarda
     * mevcut /site/{brand} test-modu route'una geri dusulur.
     */
    private function facilityLoginUrl(string $brand): string
    {
        $domain = config("brands.brands.{$brand}.domains.0");

        if (app()->environment(['local', 'testing']) || ! $domain || ! str_ends_with($domain, '.com')) {
            return route('brand.facility.login', ['brand' => $brand]);
        }

        return 'https://'.$domain.'/kurum-panel/giris';
    }

    private function facilityRegistrationEditUrl(FacilityRegistration $registration): string
    {
        $slug = $registration->brand;
        $domain = config("brands.brands.{$slug}.domains.0");
        $params = ['registration' => $registration->id, 'hash' => sha1($registration->applicant_email)];

        if (! app()->environment(['local', 'testing']) && $domain && str_ends_with($domain, '.com')) {
            $previousRoot = URL::to('/');
            URL::forceRootUrl('https://'.$domain);
            $editUrl = URL::temporarySignedRoute('facility-registration.edit', now()->addDays(14), $params);
            URL::forceRootUrl($previousRoot);

            return $editUrl;
        }

        $params['brand'] = $slug;

        return URL::temporarySignedRoute('brand.facility-registration.edit', now()->addDays(14), $params);
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
