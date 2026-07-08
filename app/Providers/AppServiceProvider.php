<?php

namespace App\Providers;

use App\Models\FacilityClaim;
use App\Models\FacilityRegistration;
use App\Models\FacilityUser;
use App\Models\FamilyUser;
use App\Models\WalletTopup;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        $this->registerRateLimiters();

        // Aktif markayi tum public view'larda kullanilabilir hale getir
        View::composer('themes.*', function ($view) {
            if (app()->bound('currentBrand')) {
                $view->with('brand', app('currentBrand'));
            }
        });

        View::composer('layouts.brand', function ($view) {
            if (app()->bound('currentBrand')) {
                $view->with('brand', app('currentBrand'));
            }

            // canliyaal projesinden tasindi: header'da okunmamis bildirim rozeti
            $unread = 0;
            if (session('facility_user_id')) {
                $unread = FacilityUser::find(session('facility_user_id'))?->notifications()->unread()->count() ?? 0;
            } elseif (session('family_user_id')) {
                $unread = FamilyUser::find(session('family_user_id'))?->notifications()->unread()->count() ?? 0;
            }
            $view->with('unreadNotificationsCount', $unread);
        });

        // Admin layout'a bekleyen onay sayilarini ekle (sidebar badge'leri icin)
        View::composer('admin.*', function ($view) {
            $view->with('pendingClaimsCount', FacilityClaim::where('status', 'pending')->count());
            $view->with('pendingTopupsCount', WalletTopup::where('status', 'pending')->count());
            $view->with('pendingRegistrationsCount', FacilityRegistration::where('status', 'pending')->count());
        });
    }

    /**
     * Public formlar icin hiz siniri tanimlari. Degerler koddan degil
     * config/platform.php > throttle icinden okunur; boylece trafiginize
     * gore limitleri kod deploy etmeden, tek dosyadan ayarlayabilirsiniz.
     */
    private function registerRateLimiters(): void
    {
        foreach (config('platform.throttle', []) as $name => $perMinute) {
            RateLimiter::for($name, fn ($request) => Limit::perMinute($perMinute)->by($request->ip()));
        }
    }
}
