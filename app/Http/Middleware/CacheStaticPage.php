<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

// Sabit/herkese acik sayfalar (rehber, SSS, footer sayfalari...) icin misafir ziyaretcilere
// HTML onbellegi: 1 saat taze + "son bilinen iyi" kopya. Veritabani/sunucu hatasi (5xx) olursa
// son iyi kopya gosterilir. CSRF jetonu her gosterimde ziyaretcinin kendi jetonuyla degistirilir.
class CacheStaticPage
{
    private const FRESH_MINUTES = 60;
    private const TOKEN_PLACEHOLDER = '__CSRF_TOKEN__';

    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isMethod('GET') || $this->hasPersonalState($request)) {
            return $next($request);
        }

        $key = 'static-page:v1:'.md5($request->getHost().'|'.$request->getRequestUri());

        $fresh = Cache::get($key);
        if (is_string($fresh)) {
            return $this->htmlResponse($fresh);
        }

        $response = $next($request);

        if ($response->getStatusCode() === 200 && $this->isPlainHtml($response) && $this->hasCsrfToken($request)) {
            $html = str_replace(csrf_token(), self::TOKEN_PLACEHOLDER, (string) $response->getContent());
            Cache::put($key, $html, now()->addMinutes(self::FRESH_MINUTES));
            Cache::forever($key.':stale', $html);
        } elseif ($response->getStatusCode() >= 500) {
            $stale = Cache::get($key.':stale');
            if (is_string($stale)) {
                return $this->htmlResponse($stale);
            }
        }

        return $response;
    }

    private function hasPersonalState(Request $request): bool
    {
        if ($request->query('debug') !== null || ! $request->hasSession()) {
            return true;
        }
        $s = $request->session();
        foreach (['family_user_id', 'facility_user_id', 'admin_id', 'impersonator_admin_id', 'errors', '_old_input'] as $k) {
            if ($s->has($k)) {
                return true;
            }
        }
        foreach ((array) $s->get('_flash.old', []) as $_) {
            return true;
        }

        return false;
    }

    private function isPlainHtml(Response $response): bool
    {
        return str_contains((string) $response->headers->get('Content-Type'), 'text/html')
            && ! $response->headers->has('Location');
    }

    private function hasCsrfToken(Request $request): bool
    {
        return (string) csrf_token() !== '';
    }

    private function htmlResponse(string $html): Response
    {
        return response(str_replace(self::TOKEN_PLACEHOLDER, csrf_token(), $html), 200, ['Content-Type' => 'text/html; charset=UTF-8']);
    }
}
