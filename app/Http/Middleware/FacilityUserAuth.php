<?php

namespace App\Http\Middleware;

use App\Models\FacilityUser;
use Closure;
use Illuminate\Http\Request;

class FacilityUserAuth
{
    public function handle(Request $request, Closure $next)
    {
        $userId = session('facility_user_id');
        $user = $userId ? FacilityUser::find($userId) : null;

        if (! $user || $user->status !== 'active') {
            $request->session()->forget(['facility_user_id', 'facility_user_name']);

            return redirect(brand_route('facility.login'))
                ->withErrors(['email' => 'Kurum panelini yalnızca admin tarafından onaylanmış kayıtlı kurum yetkilileri görebilir.']);
        }

        if (! $user->hasVerifiedEmail()) {
            return redirect(brand_route('facility.verify-email.notice'));
        }

        return $next($request);
    }
}