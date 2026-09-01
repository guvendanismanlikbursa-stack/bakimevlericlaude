<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\FacilityUser;
use App\Models\FamilyUser;
use App\Models\PushSubscription;
use Illuminate\Http\Request;

// Push abonelikleri oturuma gore otomatik cozumlenir: hangi session key'i
// doluysa (family_user_id / facility_user_id / admin_id) abonelik o modele
// baglanir. Boylece tek bir uc nokta 3 kullanici tipini de destekler.
class PushSubscriptionController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'endpoint' => 'required|string',
            'keys.p256dh' => 'required|string',
            'keys.auth' => 'required|string',
        ]);

        $subscribable = $this->currentSubscribable();

        if (! $subscribable) {
            return response()->json(['ok' => false], 401);
        }

        PushSubscription::updateOrCreate(
            ['endpoint_hash' => hash('sha256', $data['endpoint'])],
            [
                'subscribable_type' => get_class($subscribable),
                'subscribable_id' => $subscribable->getKey(),
                'endpoint' => $data['endpoint'],
                'public_key' => $data['keys']['p256dh'],
                'auth_token' => $data['keys']['auth'],
                // 30 Temmuz 2026: admin gercek cihazinda push bildirimi hic
                // gormedigini bildirdi - kok neden bulundu: burada TUM yeni
                // aboneliklere sabit olarak eski/artik kullanilmayan "aesgcm"
                // sifreleme semasi (draft-httpbis-encryption-encoding-01)
                // yaziliyordu. Modern tarayicilar (Chrome ~56+'dan beri,
                // yillardir) push mesajlarini SADECE guncel standart olan
                // "aes128gcm" (RFC 8291) ile cozebiliyor. FCM bu opak sifreli
                // veriyi icerigine hic bakmadan iletiyor ("basarili" donuyor),
                // ama tarayici/isletim sistemi kendi tarafinda cozemedigi icin
                // bildirimi HICBIR HATA/LOG BIRAKMADAN sessizce dusuruyordu -
                // bu yuzden gonderim "basarili" gorunuyordu ama hicbir zaman
                // ekranda/seste bir sey cikmiyordu.
                'content_encoding' => 'aes128gcm',
                'user_agent' => substr((string) $request->userAgent(), 0, 255),
            ]
        );

        return response()->json(['ok' => true]);
    }

    public function destroy(Request $request)
    {
        // 1 Eylul 2026: kullanicinin bildirdigi denetimde bulunan gercek
        // hata - store() ile FARKLI olarak burada HICBIR sahiplik/oturum
        // kontrolu yoktu. endpoint degerini bilen HERHANGI bir istemci
        // (oturum acik olsun olmasin, kendi aboneligi olsun olmasin) o
        // kaydi silebiliyordu. store() ile AYNI kural: once oturumdan
        // gercek kullanici cozulur, silme SADECE o kullaniciya ait kayda
        // uygulanir.
        $data = $request->validate(['endpoint' => 'required|string']);

        $subscribable = $this->currentSubscribable();

        if (! $subscribable) {
            return response()->json(['ok' => false], 401);
        }

        PushSubscription::where('endpoint_hash', hash('sha256', $data['endpoint']))
            ->where('subscribable_type', get_class($subscribable))
            ->where('subscribable_id', $subscribable->getKey())
            ->delete();

        return response()->json(['ok' => true]);
    }

    // 30 Temmuz 2026: gercek kok neden burada bulundu (log-tail teshis
    // kaydiyla dogrulandi) - bir cihazda/tarayicida daha once (ör. admin'in
    // "Panelde Gör" ile bir kurum yetkilisini goruntulemesi sirasinda, ya da
    // dogrudan kurum-panel girisiyle) olusmus ESKI bir facility_user_id/
    // family_user_id session anahtari, o kullanici SONRADAN silinmis/
    // gecersiz olsa bile session'da KALICI olarak duruyordu (hicbir login()
    // akisi digger rollerin session anahtarlarini temizlemiyordu). Bu
    // fonksiyon ilk DOLU anahtari bulunca hemen onu donduruyordu - o ID
    // artik gecersizse (silinmis kullanici) bile FacilityUser::find() null
    // donup fonksiyon da null donuyordu, GERCEKTEN GECERLI olan admin_id'ye
    // hic bakmadan. Artik gecersiz/silinmis bir ID'yi atlayip bir SONRAKI
    // rolu de kontrol ediyor.
    private function currentSubscribable(): FamilyUser|FacilityUser|Admin|null
    {
        if ($id = session('family_user_id')) {
            if ($family = FamilyUser::find($id)) {
                return $family;
            }
        }
        if ($id = session('facility_user_id')) {
            if ($facilityUser = FacilityUser::find($id)) {
                return $facilityUser;
            }
        }
        if ($id = session('admin_id')) {
            if ($admin = Admin::find($id)) {
                return $admin;
            }
        }

        return null;
    }
}
