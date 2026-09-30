<?php

namespace App\Http\Middleware;

use App\Models\Marketer;
use Closure;
use Illuminate\Http\Request;

// 27 Eylul 2026: kullanicinin talebi - pazarlamaci sadece CRM rotalarina
// girebilmeli, admin/kurum/aile oturumlarindan tamamen ayri bir oturum
// anahtari (marketer_id) kullanir - bkz. AdminAuth/FacilityUserAuth ayni desen.
// 28 Eylul 2026: hesap devre disi birakildiysa ACIK OTURUM da hemen dusurulur
// (login anindaki is_active kontrolu tek basina yetmiyordu).
class MarketerAuth
{
    public function handle(Request $request, Closure $next)
    {
        $marketerId = session('marketer_id');
        if (! $marketerId) {
            return redirect()->route('marketer.login');
        }

        $marketer = Marketer::find($marketerId);
        if (! $marketer || ! $marketer->is_active) {
            if ($marketer) {
                $marketer->update(['is_logged_in' => false]);
            }
            $request->session()->forget(['marketer_id', 'marketer_name']);

            return redirect()->route('marketer.login')->withErrors(['phone' => 'Hesabınız devre dışı bırakılmış, yönetici ile iletişime geçin.']);
        }

        return $next($request);
    }
}
