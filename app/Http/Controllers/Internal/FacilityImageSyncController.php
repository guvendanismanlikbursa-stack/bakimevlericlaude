<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

/**
 * 19 Agustos 2026: kullanicinin talebi - bakimevleri.com/bakimevibul.com/
 * bakimeviara.com AYNI veritabanini paylasiyor ama HER BIRININ KENDI AYRI
 * dosya deposu var (canlida dogrulandi: bir domain'de yuklenen gercek kurum
 * gorseli diger 2 domain'de kirik link/404 oluyordu). Kullanicinin acik
 * talebi: "hangi siteden yuklenirse yuklensin AYNI ANDA tum sitelere
 * yuklenmeli, silinince tum sitelerden silinmeli" - bu uc, TAM OLARAK bunu
 * saglar. Bir domain uzerinde gercek kullanici (admin veya kurum yetkilisi)
 * bir gorsel yukleyince/silince, ayni istek icinde (bkz. CrossDomainImageSync)
 * bu uc uzerinden DIGER 2 domain'e ayni dosya kopyalanir/silinir - artik
 * HERHANGI bir domain'de HANGI kullanici rolu olursa olsun ayni davranir.
 *
 * GUVENLIK: /_ops/{action} ile AYNI paylasilan-sifre (Bearer token) deseni;
 * ayrica yol (path) sadece bilinen kurum gorseli formatiyla eslesirse kabul
 * edilir - disaridan keyfi bir dosya yoluna yazma/silme YAPILAMAZ.
 */
class FacilityImageSyncController extends Controller
{
    // ImageCompressionService::store()/storeFromLocalFile() tarafindan
    // uretilen TUM gercek yol bicimleriyle eslesir (bkz. o servis).
    //
    // 26 Agustos 2026: kullanicinin "admin panelinde hata gorunuyor"
    // bildirimiyle bulunan gercek hata - bu desen sadece DUZ (facilities/
    // {rastgele-32-karakter}.webp) yuklemeleri kabul ediyordu, 'veri cekici'
    // demo gorsel havuzunun kullandigi ALT KLASORLU yollari (ör.
    // facilities/demo/{kategori_id}/{n}.webp, facilities/demo/menu-sample-
    // source.webp) TANIMIYORDU. Admin panelinden bir demo gorseli silinince
    // (bkz. Admin\FacilityController::deleteImage()) diger 2 domain'e
    // senkron silme istegi bu regex'e takilip 422 ile reddediliyor, admin'e
    // "Hatalar" ekraninda gercek bir hata olarak dusuyordu - dosyanin
    // KENDISI silinen domain'de dogru silinse de DIGER 2 domain'de kirik
    // kaliyordu. Simdi demo/ alt klasor yapisi da acikca kabul edilir.
    private const PATH_PATTERN = '/^facilities\/(demo\/[A-Za-z0-9_\-]{1,60}(\/[A-Za-z0-9_\-]{1,60})?|[A-Za-z0-9_\-]{6,60})\.(webp|jpg|jpeg|png)$/';

    public function store(Request $request): Response
    {
        $this->authorize($request);

        $data = $request->validate([
            'path' => 'required|string',
            'file' => 'required|file|max:20480',
        ]);

        if (! preg_match(self::PATH_PATTERN, $data['path'])) {
            abort(422, 'Gecersiz yol.');
        }

        Storage::disk('public')->put($data['path'], file_get_contents($request->file('file')->getRealPath()));

        return response('OK', 200);
    }

    public function destroy(Request $request): Response
    {
        $this->authorize($request);

        $data = $request->validate(['path' => 'required|string']);

        if (! preg_match(self::PATH_PATTERN, $data['path'])) {
            abort(422, 'Gecersiz yol.');
        }

        Storage::disk('public')->delete($data['path']);

        return response('OK', 200);
    }

    private function authorize(Request $request): void
    {
        $secret = (string) config('platform.ops_secret');
        $provided = (string) str($request->header('Authorization', ''))->after('Bearer ');

        if ($secret === '' || ! hash_equals($secret, $provided)) {
            abort(403);
        }
    }
}
