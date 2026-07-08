<?php

namespace App\Http\Middleware;

use App\Models\FamilyUser;
use Closure;
use Illuminate\Http\Request;

class FamilyAuth
{
    public function handle(Request $request, Closure $next)
    {
        $familyId = session('family_user_id');
        $family = $familyId ? FamilyUser::find($familyId) : null;

        if (! $family) {
            $request->session()->forget(['family_user_id', 'family_user_name']);
            session(['intended_url' => $request->fullUrl()]);

            return redirect(brand_route('family.login'))
                ->withErrors(['email' => 'Aile panelini görmek için kayıtlı aile hesabıyla giriş yapmalısınız.']);
        }

        if ($family->status !== 'active') {
            $request->session()->forget(['family_user_id', 'family_user_name']);

            return redirect(brand_route('family.login'))
                ->withErrors(['email' => 'Hesabınız şu anda aktif değil, lütfen yönetici ile iletişime geçin.']);
        }

        return $next($request);
    }
}