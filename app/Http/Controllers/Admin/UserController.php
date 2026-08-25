<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\RedirectsOutOfRangePagination;
use App\Http\Controllers\Controller;
use App\Models\FacilityUser;
use App\Models\FamilyUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

// Kayitli ailelerin ve kurum yetkililerinin (sahiplenen/kayit olan kisiler)
// platform genelinde tek ekrandan aranip/goruntulenip askiya alinabilmesi
// icin - eskiden aile listesi Site Istatistikleri'nin icine gomulu, kurum
// yetkilileri ise hic global listelenmiyordu (sadece o kurumun duzenleme
// sayfasinda goruluyordu).
class UserController extends Controller
{
    use RedirectsOutOfRangePagination;

    public function families(Request $request)
    {
        $query = FamilyUser::query();

        if ($request->filled('brand')) {
            $query->where('registered_brand', $request->brand);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('q')) {
            $search = $request->q;
            $query->where(function ($w) use ($search) {
                $w->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $families = $query->latest()->paginate(25)->withQueryString();

        if ($redirect = $this->redirectIfPageOutOfRange($request, $families)) {
            return $redirect;
        }

        $brands = config('brands.brands');

        if ($request->ajax()) {
            // 18 Agustos 2026: bkz. Admin\FacilityController::index() ayni
            // tarihli yorum - tarayici GERI tusunda ham JSON gorunmesini
            // onlemek icin bu AJAX yaniti onbelleklenmez.
            return response()->json([
                'html' => view('admin.users._families-results', compact('families', 'brands'))->render(),
            ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        }

        return view('admin.users.families', compact('families', 'brands'));
    }

    public function toggleFamilyStatus(FamilyUser $familyUser)
    {
        $newStatus = $familyUser->status === 'active' ? 'suspended' : 'active';
        $familyUser->update(['status' => $newStatus]);

        log_admin_event(
            $newStatus === 'suspended' ? 'family_user_suspended' : 'family_user_reactivated',
            $familyUser
        );

        return back()->with('success', $newStatus === 'suspended' ? 'Aile hesabı askıya alındı.' : 'Aile hesabı yeniden aktifleştirildi.');
    }

    public function facilityUsers(Request $request)
    {
        // 17 Agustos 2026: bkz. Admin\VisitRequestController ayni tarihli
        // yorum - kurum silinince bagli yetkili hesaplari (zaten "suspended"
        // yapiliyordu ama listede kaliyordu) burada gorunmemeli. Kurum
        // geri yuklenince (Cop Kutusu) tekrar gorunur olur.
        $query = FacilityUser::whereHas('facility')->with('facility.category', 'facility.city');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('email_status')) {
            $request->email_status === 'verified'
                ? $query->whereNotNull('email_verified_at')
                : $query->whereNull('email_verified_at');
        }
        if ($request->filled('city')) {
            $cityId = $request->city;
            $query->whereHas('facility', fn ($f) => $f->where('city_id', $cityId));
        }
        if ($request->filled('category')) {
            $categoryId = $request->category;
            $query->whereHas('facility', fn ($f) => $f->where('facility_category_id', $categoryId));
        }
        if ($request->filled('q')) {
            $search = $request->q;
            $query->where(function ($w) use ($search) {
                $w->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhereHas('facility', fn ($f) => $f->where('name', 'like', "%{$search}%"));
            });
        }

        $facilityUsers = $query->latest()->paginate(25)->withQueryString();

        if ($redirect = $this->redirectIfPageOutOfRange($request, $facilityUsers)) {
            return $redirect;
        }

        if ($request->ajax()) {
            // 18 Agustos 2026: bkz. Admin\FacilityController::index() ayni
            // tarihli yorum - tarayici GERI tusunda ham JSON gorunmesini
            // onlemek icin bu AJAX yaniti onbelleklenmez.
            return response()->json([
                'html' => view('admin.users._facility-users-results', compact('facilityUsers'))->render(),
            ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        }

        $cities = \App\Models\City::orderBy('name')->get();
        $categories = \App\Models\FacilityCategory::orderBy('name')->get();

        return view('admin.users.facility-users', compact('facilityUsers', 'cities', 'categories'));
    }

    public function toggleFacilityUserStatus(FacilityUser $facilityUser)
    {
        $newStatus = $facilityUser->status === 'active' ? 'suspended' : 'active';
        $facilityUser->update(['status' => $newStatus]);

        log_admin_event(
            $newStatus === 'suspended' ? 'facility_user_suspended' : 'facility_user_reactivated',
            $facilityUser
        );

        return back()->with('success', $newStatus === 'suspended' ? 'Kurum yetkilisi hesabı askıya alındı.' : 'Kurum yetkilisi hesabı yeniden aktifleştirildi.');
    }

    // 30 Temmuz 2026: onay mailindeki gecici sifre mail gecikirse/hic
    // gitmezse (bkz. Gmail SMTP gecikme sorunu) admin'in bunu goremedigi,
    // basvuru sahibine manuel (telefon/whatsapp) iletebilecegi bir yolu
    // olmadigi bulundu. Bu aksiyon istendigi an (sadece onay aninda degil,
    // sonradan da) yeni bir gecici sifre uretip admin ekraninda gosterir -
    // must_change_password tekrar true yapilir ki kullanici ilk girişte
    // kendi sifresini belirlesin.
    public function resetFacilityUserPassword(FacilityUser $facilityUser)
    {
        // 25 Agustos 2026: bkz. Facility\TeamController::invite() ayni
        // tarihli yorum - sembolsuz sifre, mailde tam secilebilir/kopyalanabilir.
        $temporaryPassword = Str::password(14, symbols: false);

        $facilityUser->update([
            'password' => Hash::make($temporaryPassword),
            'must_change_password' => true,
        ]);

        log_admin_event('facility_user_password_reset', $facilityUser);

        // 25 Agustos 2026: kullanicinin bildirdigi gercek hata - bu mailin
        // "Kurum Paneline Git" butonu HER ZAMAN sabit route('facility.login')
        // (varsayilan/ilk marka, bakimevibul) linkine gidiyordu, kurumun
        // GERCEKTE hangi siteden basvurdugunun onemi yoktu - ör. bakimevleri
        // uzerinden sahiplenilmis bir kurumun yetkilisi butona tiklayinca
        // bakimevibul'e dusuyordu. facility_login_brand_slug() (bkz.
        // app/helpers.php ayni tarihli yorum) sahiplenme/kayit basvurusundaki
        // GERCEK markayi bulur.
        $loginUrl = facility_brand_login_url(facility_login_brand_slug($facilityUser->facility));

        try {
            \Illuminate\Support\Facades\Mail::to($facilityUser->email)->sendNow(
                new \App\Mail\FacilityPasswordManuallyResetMail($facilityUser, $temporaryPassword, $loginUrl)
            );
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Kurum sifre sifirlama (admin) maili gonderilemedi: ' . $e->getMessage(), ['facility_user_id' => $facilityUser->id]);
            notify_admin_of_exception($e);
        }

        return back()->with('success', "Yeni geçici şifre oluşturuldu ve e-postayla gönderildi. E-posta ulaşmazsa şu bilgileri kullanıcıya siz iletebilirsiniz — E-posta: {$facilityUser->email} / Geçici şifre: {$temporaryPassword}");
    }

    // 30 Temmuz 2026: admin bir kurum/aile hesabinin panelini "onlarin
    // gozuyle" gormesi/kullanmasi gerekebiliyor (ör. bir sikayeti dogrulamak,
    // bir sorunu tekrar etmek). Admin'in kendi oturumunu SILMEZ - admin_id/
    // admin_name'i impersonator_admin_* altina tasir ki ImpersonationController::
    // stop() bunlari geri yukleyip admin paneline donebilsin. Kurum/aile
    // panelinde goruntulenen sari uyari cubugu (bkz. layouts/brand.blade.php)
    // bu oturumun gecici oldugunu her zaman gosterir.
    public function impersonateFacilityUser(FacilityUser $facilityUser)
    {
        log_admin_event('facility_user_impersonation_start', $facilityUser);

        session([
            'impersonator_admin_id' => session('admin_id'),
            'impersonator_admin_name' => session('admin_name'),
        ]);
        session()->forget(['admin_id', 'admin_name']);
        session()->regenerate();
        session()->regenerateToken();
        session(['facility_user_id' => $facilityUser->id, 'facility_user_name' => $facilityUser->name]);

        return redirect(brand_route('facility.dashboard'));
    }

    // 30 Temmuz 2026: kurum yetkilisi hesaplari (ozellikle deneme/duplike/
    // "ön kayıt"a döndürülmüş kurumlardan kalan askıya alınmış hesaplar)
    // Kurum Yetkilileri listesinde birikip karisikliga yol aciyordu, admin
    // bunlari kalici olarak silebilmek istedi. GUVENLIK: facility_users'a
    // referans veren tum FK'ler (quotes, wallet_topups, messages) migration'da
    // zaten nullOnDelete() - bu satiri silmek baska hicbir kaydi bozmaz/
    // silmez, sadece o kayitlardaki facility_user_id alani null'a doner.
    public function destroyFacilityUser(FacilityUser $facilityUser)
    {
        log_admin_event('facility_user_deleted', $facilityUser, ['email' => $facilityUser->email, 'name' => $facilityUser->name]);
        $facilityUser->delete();

        return back()->with('success', 'Kurum yetkilisi hesabı kalıcı olarak silindi.');
    }

    public function impersonateFamilyUser(FamilyUser $familyUser)
    {
        log_admin_event('family_user_impersonation_start', $familyUser);

        session([
            'impersonator_admin_id' => session('admin_id'),
            'impersonator_admin_name' => session('admin_name'),
        ]);
        session()->forget(['admin_id', 'admin_name']);
        session()->regenerate();
        session()->regenerateToken();
        session(['family_user_id' => $familyUser->id, 'family_user_name' => $familyUser->name]);

        return redirect(brand_route('family.dashboard'));
    }
}
