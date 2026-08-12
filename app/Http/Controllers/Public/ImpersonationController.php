<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\Request;

// 30 Temmuz 2026: Admin\UserController::impersonateFacilityUser/-FamilyUser
// ile baslatilan gecici goruntuleme oturumunu sonlandirip admin'i kendi
// paneline geri dondurur. Bilerek facility.auth/family.auth middleware'i
// DISINDA tutuldu - goruntulenen hesap 'suspended' veya dogrulanmamis olsa
// bile (impersonation tam da boyle bir hesabi incelemek icin kullanilabilir)
// admin'in cikis yapabilmesi engellenmemeli.
class ImpersonationController extends Controller
{
    public function stop(Request $request)
    {
        $adminId = session('impersonator_admin_id');

        if (! $adminId || ! Admin::find($adminId)) {
            return redirect(brand_route('home'));
        }

        $request->session()->forget([
            'facility_user_id', 'facility_user_name',
            'family_user_id', 'family_user_name',
            'impersonator_admin_id', 'impersonator_admin_name',
        ]);
        $request->session()->regenerate();
        $request->session()->regenerateToken();
        session(['admin_id' => $adminId, 'admin_name' => Admin::find($adminId)->name]);

        return redirect()->route('admin.dashboard');
    }
}
