<?php

use App\Http\Middleware\ResolveBrand;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Sentry\Laravel\Integration as SentryIntegration;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            ResolveBrand::class,
        ]);

        $middleware->alias([
            'admin.auth' => \App\Http\Middleware\AdminAuth::class,
            'family.auth' => \App\Http\Middleware\FamilyAuth::class,
            'facility.auth' => \App\Http\Middleware\FacilityUserAuth::class,
            'track.visit' => \App\Http\Middleware\TrackSiteVisit::class,
        ]);

        // /_ops/{action} tarayici formu degil, deploy script'inin Bearer
        // token ile cagirdigi bir uc (bkz. OpsController) - CSRF cerez/
        // oturum tabanli oldugu icin buraya hic uygulanmiyor. Onceden bu
        // istisna yoktu, CSRF hatasi 419'a donusup bizim "nazik geri donus"
        // kancamiz (asagida) sessizce anasayfaya yonlendiriyordu - deploy
        // script'i bunu "basarili" saniyordu ama gercekte hicbir komut
        // calismiyordu.
        $middleware->validateCsrfTokens(except: ['_ops/*']);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // config('sentry.dsn') bos ise SDK zaten no-op kalir (bkz.
        // config/sentry.php) - .env'de SENTRY_LARAVEL_DSN set edilmeden bu
        // satirin hicbir etkisi yok, yani devreye alma tamamen opsiyonel.
        SentryIntegration::handles($exceptions);

        $exceptions->report(function (\Throwable $e) {
            notify_admin_of_exception($e);
        });

        // Oturum/CSRF token'i suresi dolunca (419) cıplak hata sayfasi yerine
        // ayni formu Turkce bir uyariyla tekrar gosteriyoruz - ozellikle
        // e-posta/push bildirimindeki eski bir linki tiklayip uzun sure sonra
        // giris yapmaya calisan kullanicilar icin (bkz. admin girisi). Not:
        // Laravel TokenMismatchException'i render'a gelmeden ONCE genel bir
        // HttpException(419)'a ceviriyor (bkz. Handler::prepareException()),
        // bu yuzden tip-bazli render() closure'i hicbir zaman eslesmiyordu -
        // dogru yer, nihai response'un durum koduna bakan respond() kancasi.
        $exceptions->respond(function (\Symfony\Component\HttpFoundation\Response $response, \Throwable $e, \Illuminate\Http\Request $request) {
            if ($response->getStatusCode() === 419 && ! $request->expectsJson()) {
                return back()->withInput($request->except('password'))
                    ->with('error', 'Oturumunuz zaman aşımına uğradı (sayfa uzun süre açık kalmış olabilir). Lütfen tekrar deneyin.');
            }

            return $response;
        });
    })->create();
