<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Localhost'ta 3 siteyi tek sunucudan ayirt etmenin yolu:
 *  1) Gercek host eslesirse (config/brands.php -> domains) onu kullan
 *  2) Yoksa /site/{brand}/... route prefix'inden oku (bkz routes/web.php)
 *  3) Yoksa ?brand=bakimeviara query ile gecici test
 *  4) Hicbiri yoksa config('brands.default')
 */
class ResolveBrand
{
    public function handle(Request $request, Closure $next)
    {
        $brands = config('brands.brands');
        $host = $request->getHttpHost();

        $resolved = null;

        foreach ($brands as $slug => $brand) {
            if (in_array($host, $brand['domains'], true)) {
                $resolved = $slug;
                break;
            }
        }

        if (! $resolved && $request->route('brand')) {
            if (! isset($brands[$request->route('brand')])) {
                abort(404);
            }
            $resolved = $request->route('brand');
        }

        if (! $resolved && app()->environment('local') && $request->query('brand') && isset($brands[$request->query('brand')])) {
            $resolved = $request->query('brand');
        }

        if (! $resolved) {
            $resolved = config('brands.default');
        }

        $brand = $brands[$resolved];
        app()->instance('currentBrand', $brand);

        return $next($request);
    }
}
