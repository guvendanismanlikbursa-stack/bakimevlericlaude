<?php

namespace App\Http\Controllers\Family;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Public\OfferRequestController;
use App\Mail\FamilyWelcomeMail;
use App\Models\Facility;
use App\Models\FacilityCategory;
use App\Models\FamilyUser;
use App\Models\OfferRequest;
use App\Services\GeoLookupService;
use App\Services\OfferRequestNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AuthController extends Controller
{
    public function showRegister()
    {
        $brand = app('currentBrand');

        return view("themes.{$brand['theme']}.family.register");
    }

    public function register(Request $request, GeoLookupService $geo)
    {
        $brand = app('currentBrand');

        $data = $request->validate([
            'name' => 'required|string|max:120',
            'email' => 'required|email|max:150|unique:family_users,email',
            'phone' => 'required|string|max:30',
            'password' => 'required|string|min:8|confirmed',
            'consent' => 'required|accepted',
            'signup_lat' => 'nullable|numeric|between:-90,90',
            'signup_lng' => 'nullable|numeric|between:-180,180',
        ], [
            'consent.required' => 'Açık rıza metnini onaylamadan hesap oluşturamazsınız.',
            'consent.accepted' => 'Açık rıza metnini onaylamadan hesap oluşturamazsınız.',
        ]);

        $signupCityName = null;
        if ($request->filled('signup_lat') && $request->filled('signup_lng')) {
            $nearest = $geo->nearestCity((float) $data['signup_lat'], (float) $data['signup_lng']);
            $signupCityName = $nearest['city'] ?? null;
        }

        $family = FamilyUser::create([
            'registered_brand' => $brand['slug'],
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'password' => Hash::make($data['password']),
            'consent_accepted_at' => now(),
            'consent_ip' => $request->ip(),
            'signup_lat' => $data['signup_lat'] ?? null,
            'signup_lng' => $data['signup_lng'] ?? null,
            'signup_city_name' => $signupCityName,
        ]);

        session(['family_user_id' => $family->id, 'family_user_name' => $family->name]);

        EmailVerificationController::send($family, $brand);

        try {
            Mail::to($family->email)->queue(new FamilyWelcomeMail($family, $brand['name'], brand_route('family.dashboard')));
        } catch (\Throwable $e) {
            Log::warning('Aile hos geldin maili gonderilemedi: ' . $e->getMessage(), ['family_id' => $family->id]);
        }

        return $this->afterLogin($brand);
    }

    public function showLogin()
    {
        $brand = app('currentBrand');

        return view("themes.{$brand['theme']}.family.login");
    }

    public function login(Request $request)
    {
        $brand = app('currentBrand');

        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $family = FamilyUser::where('email', $credentials['email'])->first();

        if (! $family || ! Hash::check($credentials['password'], $family->password)) {
            return back()->withErrors(['email' => 'E-posta veya şifre hatalı.'])->onlyInput('email');
        }

        if ($family->status !== 'active') {
            return back()->withErrors(['email' => 'Hesabınız şu anda aktif değil, lütfen yönetici ile iletişime geçin.']);
        }

        $request->session()->regenerate();
        $request->session()->regenerateToken();
        session(['family_user_id' => $family->id, 'family_user_name' => $family->name]);

        return $this->afterLogin($brand);
    }

    public function logout(Request $request)
    {
        $request->session()->forget(['family_user_id', 'family_user_name']);
        $request->session()->regenerate();
        $request->session()->regenerateToken();

        return redirect(brand_route('home'));
    }

    /**
     * Giriş/kayıt sonrası: eğer oturumda yarım kalmış bir "ücret talebi"
     * (tekli veya toplu) varsa onu simdi olusturup aile panele oyle
     * yonlendirir; yoksa direkt panel. Form doldurulmasi ile hesap
     * olusturulmasi arasinda zaman gectigi icin (kullanici mail/kayit
     * ekranini gec tamamlayabilir), kurumun hala yayinda/sahiplenilmis ve
     * kategorisinin markaya uygun oldugu burada TEKRAR dogrulanir; aksi
     * halde talep sessizce olusturulmaz.
     */
    private function afterLogin(array $brand)
    {
        $notifier = app(OfferRequestNotificationService::class);

        if ($pending = session('pending_offer_request')) {
            session()->forget('pending_offer_request');
            $pending['family_user_id'] = session('family_user_id');

            if ($this->pendingOfferRequestIsStillValid($pending)) {
                $offerRequest = OfferRequest::create($pending);
                $notifier->notify($offerRequest);

                return redirect(brand_route('family.dashboard'))->with('success', 'Hesabınız oluşturuldu ve talebiniz iletildi. Uygun kurumlardan teklif gelmeye başlayacak.');
            }

            return redirect(brand_route('family.dashboard'))->with('info', 'Hesabınız oluşturuldu, ancak talep ettiğiniz kurum bu sırada güncellenmiş görünüyor. Lütfen kurumu tekrar arayıp talebinizi yeniden gönderin.');
        }

        if ($pendingBulk = session('pending_bulk_offer_request')) {
            session()->forget('pending_bulk_offer_request');
            $created = OfferRequestController::createBulkRequests($pendingBulk, session('family_user_id'), $notifier);

            return redirect(brand_route('family.dashboard'))->with('success', 'Hesabınız oluşturuldu ve talepleriniz '.count($created).' kuruma iletildi. Teklifler geldikçe panelinizde görünecek.');
        }

        if ($intended = session('intended_url')) {
            session()->forget('intended_url');

            return redirect($intended);
        }

        return redirect(brand_route('family.dashboard'));
    }

    private function pendingOfferRequestIsStillValid(array $pending): bool
    {
        $categoryScope = config('brands.brands')[$pending['brand']]['category_scope'] ?? [];

        if (! empty($pending['facility_id'])) {
            $facility = Facility::published()->where('is_claimed', true)->find($pending['facility_id']);

            return $facility && in_array($facility->category?->brand_scope, $categoryScope, true);
        }

        if (empty($pending['facility_category_id'])) {
            return false;
        }

        $category = FacilityCategory::find($pending['facility_category_id']);

        return $category && in_array($category->brand_scope, $categoryScope, true);
    }
}
