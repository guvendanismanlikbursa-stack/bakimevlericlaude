<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * 30 Ekim 2026: kullanıcının talebi - kurum alan adını kendi panelinden yazabilsin.
 * Yanlış / henüz bağlanmamış bir alan adı kaydedilirse kurum sitesi çalışmayan
 * adrese yönlenirdi. Bu yüzden kayıttan önce https://alanadi/alan-adi-dogrulama/{kod}
 * adresi çağrılır; cevap bu uygulamadan (APP_KEY ile imzalı) geliyorsa alan adı
 * sunucumuza bağlı ve güvenlik sertifikası hazır demektir.
 */
class AlanAdiDogrulamaController extends Controller
{
    public function show(string $kod)
    {
        return response(self::imza($kod), 200)->header('Content-Type', 'text/plain; charset=UTF-8')
            ->header('X-Robots-Tag', 'noindex, nofollow');
    }

    public static function imza(string $kod): string
    {
        return hash_hmac('sha256', 'alan-adi:'.$kod, (string) config('app.key'));
    }

    /** Alan adı (https ile, geçerli sertifikayla) bu uygulamayı açıyor mu? */
    public static function bizeBagli(string $domain): bool
    {
        $kod = Str::random(32);
        try {
            $cevap = Http::timeout(8)->connectTimeout(5)->get('https://'.$domain.'/alan-adi-dogrulama/'.$kod);
        } catch (\Throwable) {
            return false;
        }

        return $cevap->ok() && hash_equals(self::imza($kod), trim($cevap->body()));
    }
}
