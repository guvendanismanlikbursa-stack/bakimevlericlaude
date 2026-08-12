<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\FacilityClaimApprovedMail;
use App\Models\BalanceLog;
use App\Models\FacilityClaim;
use App\Models\FacilityUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class FacilityClaimController extends Controller
{
    public function index(Request $request)
    {
        $query = FacilityClaim::with('facility');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        } else {
            $query->where('status', 'pending');
        }

        $claims = $query->latest()->paginate(15)->withQueryString();

        return view('admin.claims.index', compact('claims'));
    }

    public function show(FacilityClaim $claim)
    {
        $claim->load('facility');

        return view('admin.claims.show', compact('claim'));
    }

    public function approve(Request $request, FacilityClaim $claim)
    {
        $temporaryPassword = Str::password(14);
        $freeCredits = (int) config('platform.free_claim_credits', 5);

        $mailPayload = DB::transaction(function () use ($claim, $temporaryPassword, $freeCredits) {
            $claim = FacilityClaim::whereKey($claim->id)->lockForUpdate()->firstOrFail();
            // 12 Agustos 2026: kullanicinin talebi - admin daha once
            // reddettigi bir basvuruyu gerekirse (ör. basvuran ek belge
            // gonderdiyse) sonradan onaylayabilmeli; sadece zaten ONAYLANMIS
            // bir basvurunun tekrar islenmesini engelliyoruz.
            abort_if($claim->status === 'approved', 400, 'Bu basvuru zaten onaylanmis.');

            $facility = $claim->facility()->lockForUpdate()->firstOrFail();
            abort_if($facility->is_claimed, 400, 'Bu kurum zaten sahiplenilmis.');

            if (FacilityUser::where('email', $claim->applicant_email)->exists()) {
                return ['error' => 'Bu e-posta zaten bir kurum hesabina ait. Baska bir basvuru/e-posta gerekiyor.'];
            }

            if ($error = email_taken_by_other_account_type($claim->applicant_email)) {
                return ['error' => $error];
            }

            $facilityUser = FacilityUser::create([
                'facility_id' => $facility->id,
                'name' => $claim->applicant_name,
                'email' => $claim->applicant_email,
                'phone' => $claim->applicant_phone,
                'password' => Hash::make($temporaryPassword),
                'must_change_password' => true,
                'status' => 'active',
                'email_verified_at' => null,
                'signup_lat' => $claim->applicant_lat,
                'signup_lng' => $claim->applicant_lng,
                'signup_city_name' => $claim->applicant_city_name,
                'signup_ip' => $claim->applicant_ip,
            ]);

            $facility->update([
                'is_claimed' => true,
                'claimed_at' => now(),
                'free_quote_credits' => (int) $facility->free_quote_credits + $freeCredits,
                'invitation_status' => 'approved',
                'invitation_status_at' => now(),
            ]);

            // 10 Agustos 2026: kurum sahiplenildiginde artik gercek bir
            // sahibi var - veri cekiciden/on-kayittan kalma 'facilities/
            // demo/...' sablon gorselleri (gercek dosyasi hic olmayan,
            // "ÖRNEKTİR" filigranli placeholder'lar) burada yanilticidir,
            // sahiplenme onaylanir onaylanmaz otomatik temizlenir. Sadece
            // path prefix'i kesin eslesirse siler - kurumun kendi yukledigi
            // HICBIR gercek gorsele (facilities/RANDOM.webp) dokunmaz.
            \App\Models\FacilityImage::where('facility_id', $facility->id)
                ->where('path', 'like', 'facilities/demo/%')
                ->delete();

            BalanceLog::create([
                'facility_id' => $facility->id,
                'type' => 'claim_bonus_credits',
                'amount' => 0,
                'credits_amount' => $freeCredits,
                'balance_after' => $facility->balance,
                'credits_after' => $facility->free_quote_credits,
                'admin_id' => session('admin_id'),
                'note' => 'Sahiplenme onayi bonus hakki.',
            ]);

            $claim->update([
                'status' => 'approved',
                'reviewed_by' => session('admin_id'),
                'reviewed_at' => now(),
            ]);

            return [
                'facility' => $facility,
                'email' => $claim->applicant_email,
                'password' => $temporaryPassword,
                'login_url' => $this->facilityLoginUrl($claim->brand),
            ];
        });

        if (isset($mailPayload['error'])) {
            return back()->withErrors(['email' => $mailPayload['error']]);
        }

        // Mail::queue kullanildi: QUEUE_CONNECTION=sync iken aninda,
        // ileride database/redis queue'ya gecilince arka planda gonderilir.
        // Boylece admin onay islemi SMTP gecikmesine takilmaz.
        try {
            Mail::to($mailPayload['email'])->sendNow(
                new FacilityClaimApprovedMail($mailPayload['facility'], $mailPayload['email'], $mailPayload['password'], $mailPayload['login_url'])
            );
        } catch (\Throwable $e) {
            Log::warning('Sahiplenme onay maili gonderilemedi: ' . $e->getMessage(), ['facility_id' => $mailPayload['facility']->id]);
        }

        try {
            Mail::to($mailPayload['email'])->sendNow(
                new \App\Mail\FacilityWelcomeMail($mailPayload['facility'], $mailPayload['email'], config("brands.brands.{$claim->brand}.name", $claim->brand), $mailPayload['login_url'])
            );
        } catch (\Throwable $e) {
            Log::warning('Kurum hos geldin maili gonderilemedi: ' . $e->getMessage(), ['facility_id' => $mailPayload['facility']->id]);
        }

        \App\Http\Controllers\Facility\EmailVerificationController::send(
            \App\Models\FacilityUser::where('email', $mailPayload['email'])->firstOrFail(),
            config("brands.brands.{$claim->brand}")
        );

        log_admin_event('facility_claim_approved', $claim, ['facility_id' => $mailPayload['facility']->id]);

        $facilityUser = \App\Models\FacilityUser::where('email', $mailPayload['email'])->first();
        notify_user($facilityUser, 'claim_approved', 'Sahiplenme başvurunuz onaylandı', 'Kurum hesabınız aktifleşti, giriş bilgileri e-posta ile gönderildi.');

        // 30 Temmuz 2026: gecici sifre SADECE mail icine gomuluyordu, admin
        // paneli mail gecikirse/gitmezse (bkz. Gmail SMTP gecikme sorunu)
        // hicbir yerde goremiyordu - basvuru sahibine telefonla vb. manuel
        // iletebilecegi bir yol yoktu. Artik onay ekraninda da gosteriliyor.
        return redirect()->route('admin.claims.index')->with('success', "Başvuru onaylandı, giriş bilgileri e-posta ile gönderildi. E-posta ulaşmazsa şu bilgileri kullanıcıya siz iletebilirsiniz — E-posta: {$mailPayload['email']} / Geçici şifre: {$mailPayload['password']}");
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

    public function reject(Request $request, FacilityClaim $claim)
    {
        $data = $request->validate(['admin_note' => 'nullable|string|max:1000']);

        DB::transaction(function () use ($claim, $data) {
            $claim = FacilityClaim::whereKey($claim->id)->lockForUpdate()->firstOrFail();
            abort_if($claim->status !== 'pending', 400, 'Bu basvuru zaten islenmis.');

            $claim->update([
                'status' => 'rejected',
                'admin_note' => $data['admin_note'] ?? null,
                'reviewed_by' => session('admin_id'),
                'reviewed_at' => now(),
            ]);
        });

        log_admin_event('facility_claim_rejected', $claim, ['admin_note' => $data['admin_note'] ?? null]);

        // 28 Temmuz 2026: red edilen basvuru sahibinin henuz bir kullanici
        // hesabi yok (hesap sadece onayda aciliyor), bu yuzden notify_user()
        // kullanilamaz - basvuru reddedildiginde applicant_email'e DOGRUDAN
        // mail atilan tek nokta burasi. Onceden bu uc hicbir bildirim
        // gondermiyordu, basvuru sahibi reddedildigini hicbir zaman ogrenmiyordu.
        try {
            Mail::to($claim->applicant_email)->sendNow(new \App\Mail\FacilityClaimRejectedMail($claim));
        } catch (\Throwable $e) {
            Log::warning('Sahiplenme red maili gonderilemedi: ' . $e->getMessage(), ['claim_id' => $claim->id]);
        }

        return back()->with('success', 'Basvuru reddedildi.');
    }
}