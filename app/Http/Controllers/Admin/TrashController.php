<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\RedirectsOutOfRangePagination;
use App\Http\Controllers\Controller;
use App\Models\Facility;
use App\Models\FacilityClaim;
use App\Models\FacilityRegistration;
use App\Models\OfferRequest;
use App\Models\WalletTopup;
use App\Services\FacilityCascadeService;
use Illuminate\Http\Request;

// canliyaal projesinden tasindi: "Cop Kutusu" — soft-delete edilmis
// kayitlari tek ekrandan listeleyip geri yukleme / kalici silme imkani verir.
class TrashController extends Controller
{
    use RedirectsOutOfRangePagination;

    private const TYPES = [
        'facility' => Facility::class,
        'offer-request' => OfferRequest::class,
        'wallet-topup' => WalletTopup::class,
        'facility-claim' => FacilityClaim::class,
        'facility-registration' => FacilityRegistration::class,
    ];

    private const LABELS = [
        'facility' => 'Kurum',
        'offer-request' => 'Teklif Talebi',
        'wallet-topup' => 'Bakiye Yuklemesi',
        'facility-claim' => 'Sahiplenme Basvurusu',
        'facility-registration' => 'Kurum Kayit Basvurusu',
    ];

    public function index(Request $request)
    {
        $activeType = $request->get('type', 'facility');
        abort_unless(array_key_exists($activeType, self::TYPES), 404);

        $modelClass = self::TYPES[$activeType];
        $items = $modelClass::onlyTrashed()->latest('deleted_at')->paginate(20)->withQueryString();

        if ($redirect = $this->redirectIfPageOutOfRange($request, $items)) {
            return $redirect;
        }

        return view('admin.trash.index', [
            'items' => $items,
            'activeType' => $activeType,
            'types' => self::LABELS,
        ]);
    }

    public function restore(Request $request, string $type, int $id, FacilityCascadeService $cascadeService)
    {
        abort_unless(array_key_exists($type, self::TYPES), 404);

        $modelClass = self::TYPES[$type];
        $item = $modelClass::onlyTrashed()->findOrFail($id);
        $item->restore();

        // 17 Agustos 2026: bkz. FacilityCascadeService ayni tarihli yorum -
        // kurum geri yuklenince bagli sahiplenme/teklif/bakiye kayitlari da
        // (kurumla BIRLIKTE trashed olanlar) geri yuklenir.
        if ($type === 'facility') {
            $cascadeService->restoreRelated($item);
        }

        // 15 Agustos 2026: kullanicinin "asla hata kalmamali" talebi uzerine
        // yapilan denetimde bulundu - onceden burada FacilityController::
        // destroy()'un askiya aldigi TUM yetkilileri kosulsuz 'active'
        // yapiyorduk (kurum silinince yetkilisi otomatik askiya alinir
        // deseniyle "simetrik" olsun diye). SORUN: ayni status alani
        // Admin\UserController::toggleFacilityUserStatus() ile admin'in
        // kotuye kullanim/sikayet nedeniyle KASITLI banladigi hesaplar icin
        // de kullaniliyor - ikisini ayirt eden bir alan yok. Bu yuzden:
        // "kurum silindigi icin askiya alinan" ile "admin karariyla
        // banlanan" ayni gorunuyordu, kurum geri yuklenince BILEREK
        // banlanmis bir hesap da sessizce tekrar aktif oluyordu. Otomatik
        // aktive etme kaldirildi - admin gerekirse Kullanicilar ekranindan
        // tek tikla elle aktive edebilir, ama YANLISLIKLA banli birini
        // aktive etme riski ortadan kalkti.
        $facilityUsersNote = '';
        if ($type === 'facility') {
            $suspendedCount = \App\Models\FacilityUser::where('facility_id', $item->id)->where('status', 'suspended')->count();
            if ($suspendedCount > 0) {
                $facilityUsersNote = " Bu kuruma bagli {$suspendedCount} askidaki yetkili hesabi VAR - kasitli banlanmis olabilecekleri icin otomatik aktive edilmedi, gerekirse Kullanicilar ekranindan elle aktive edin.";
            }
        }

        log_admin_event('trash_restored', $item, ['type' => $type]);

        return back()->with('success', self::LABELS[$type].' geri yuklendi.'.$facilityUsersNote);
    }

    public function forceDestroy(Request $request, string $type, int $id, FacilityCascadeService $cascadeService)
    {
        abort_unless(array_key_exists($type, self::TYPES), 404);

        $modelClass = self::TYPES[$type];
        $item = $modelClass::onlyTrashed()->findOrFail($id);

        // 15 Agustos 2026: kullanicinin "asla hata kalmamali" talebi uzerine
        // yapilan denetimde bulundu - kalici silme sadece DB kaydini
        // (facility_images tablosu FK cascadeOnDelete ile otomatik silinir)
        // kaldiriyordu, storage/'daki GERCEK dosyalar (kurum gorselleri,
        // sahiplenme belgesi, dekont) hic silinmiyordu - suresiz oksuz
        // kaliyor, hem gereksiz disk sisiyor hem kisisel veri (kimlik
        // belgesi/dekont) "kalici silindi" denildigi halde diskte kaliyordu.
        $this->deletePhysicalFiles($type, $item);

        // 17 Agustos 2026: bkz. FacilityCascadeService ayni tarihli yorum -
        // kurum kalici silinince bagli sahiplenme/teklif/bakiye kayitlari
        // (dosyalari dahil) da kalici silinir.
        if ($type === 'facility') {
            $cascadeService->forceDeleteRelated($item);
        }

        log_admin_event('trash_force_deleted', $item, ['type' => $type]);
        $item->forceDelete();

        return back()->with('success', self::LABELS[$type].' kalici olarak silindi.');
    }

    private function deletePhysicalFiles(string $type, $item): void
    {
        if ($type === 'facility') {
            $imageSync = app(\App\Services\CrossDomainImageSync::class);
            foreach ($item->images as $image) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($image->path);
                $imageSync->syncDelete($image->path);
            }
        } elseif ($type === 'facility-claim' && $item->document_path) {
            \Illuminate\Support\Facades\Storage::disk('local')->delete($item->document_path);
        } elseif ($type === 'wallet-topup' && $item->receipt_path) {
            \Illuminate\Support\Facades\Storage::disk('local')->delete($item->receipt_path);
        }
    }
}
