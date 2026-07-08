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
            abort_if($claim->status !== 'pending', 400, 'Bu basvuru zaten islenmis.');

            $facility = $claim->facility()->lockForUpdate()->firstOrFail();
            abort_if($facility->is_claimed, 400, 'Bu kurum zaten sahiplenilmis.');

            if (FacilityUser::where('email', $claim->applicant_email)->exists()) {
                return ['error' => 'Bu e-posta zaten bir kurum hesabina ait. Baska bir basvuru/e-posta gerekiyor.'];
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
            ]);

            $facility->update([
                'is_claimed' => true,
                'claimed_at' => now(),
                'free_quote_credits' => (int) $facility->free_quote_credits + $freeCredits,
                'invitation_status' => 'approved',
                'invitation_status_at' => now(),
            ]);

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
        Mail::to($mailPayload['email'])->queue(
            new FacilityClaimApprovedMail($mailPayload['facility'], $mailPayload['email'], $mailPayload['password'], $mailPayload['login_url'])
        );

        \App\Http\Controllers\Facility\EmailVerificationController::send(
            \App\Models\FacilityUser::where('email', $mailPayload['email'])->firstOrFail(),
            config("brands.brands.{$claim->brand}")
        );

        log_admin_event('facility_claim_approved', $claim, ['facility_id' => $mailPayload['facility']->id]);

        $facilityUser = \App\Models\FacilityUser::where('email', $mailPayload['email'])->first();
        notify_user($facilityUser, 'claim_approved', 'Sahiplenme başvurunuz onaylandı', 'Kurum hesabınız aktifleşti, giriş bilgileri e-posta ile gönderildi.');

        return redirect()->route('admin.claims.index')->with('success', 'Basvuru onaylandi, giris bilgileri e-posta ile gonderildi.');
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

        return back()->with('success', 'Basvuru reddedildi.');
    }
}