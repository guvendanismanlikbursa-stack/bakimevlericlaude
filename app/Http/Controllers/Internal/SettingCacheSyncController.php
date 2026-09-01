<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

/**
 * 1 Eylul 2026: kullanicinin bildirdigi denetimde bulunan gercek hata -
 * bakimevleri.com/bakimevibul.com/bakimeviara.com AYNI veritabanini
 * paylasiyor ama HER BIRININ KENDI AYRI dosya tabanli cache'i var
 * (CACHE_STORE=file, bkz. CrossDomainImageSync ayni kokten sorun icin
 * ayni tarihli/ayni desenli aciklama). Setting::set() SADECE kendi
 * yerel cache'ini temizliyordu - admin bir ayari (ör. banka IBAN'i,
 * teklif ucreti) bir domain'den degistirdiginde, DIGER 2 domain o
 * degeri SURESIZ eski (stale) haliyle gostermeye devam ediyordu. Bu uc,
 * FacilityImageSyncController ile AYNI paylasilan-sifre (Bearer token)
 * deseniyle, diger 2 domain'e "bu ayarin cache'ini unut" istegi atar.
 */
class SettingCacheSyncController extends Controller
{
    public function forget(Request $request): Response
    {
        $secret = (string) config('platform.ops_secret');
        $provided = (string) str($request->header('Authorization', ''))->after('Bearer ');

        if ($secret === '' || ! hash_equals($secret, $provided)) {
            abort(403);
        }

        $data = $request->validate(['key' => 'required|string|max:100']);

        Cache::forget('setting:'.$data['key']);

        return response('OK', 200);
    }
}
