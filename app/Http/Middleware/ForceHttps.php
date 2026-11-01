<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * 1 Kasım 2026: kullanıcının güvenlik talebi - HTTP ile gelen istekler kalıcı
 * olarak HTTPS'e yönlendirilir. (.htaccess'te denendi ama LiteSpeed rewrite
 * aşamasında %{HTTPS}/%{SERVER_PORT} değerlerini doğru vermiyordu; PHP düzeyinde
 * ise $request->secure() http/https'i doğru ayırıyor - canlı test ile doğrulandı:
 * http'de HTTPS değişkeni boş + SERVER_PORT=80, https'de HTTPS=on + 443.)
 *
 * Yalnızca production'da çalışır (yerel/test ortamı http'dir). SSL sertifikası
 * yenilemesi http üzerinden /.well-known/acme-challenge adresine geldiği için
 * o yol hariç tutulur - yoksa sertifika yenilemesi bozulabilir.
 */
class ForceHttps
{
    public function handle(Request $request, Closure $next)
    {
        if (! $request->secure()
            && app()->environment('production')
            && ! $request->is('.well-known/*')) {
            return redirect()->to('https://'.$request->getHost().$request->getRequestUri(), 301);
        }

        return $next($request);
    }
}
