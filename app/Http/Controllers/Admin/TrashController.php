<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Facility;
use App\Models\FacilityClaim;
use App\Models\FacilityRegistration;
use App\Models\OfferRequest;
use App\Models\WalletTopup;
use Illuminate\Http\Request;

// canliyaal projesinden tasindi: "Cop Kutusu" — soft-delete edilmis
// kayitlari tek ekrandan listeleyip geri yukleme / kalici silme imkani verir.
class TrashController extends Controller
{
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

        return view('admin.trash.index', [
            'items' => $items,
            'activeType' => $activeType,
            'types' => self::LABELS,
        ]);
    }

    public function restore(Request $request, string $type, int $id)
    {
        abort_unless(array_key_exists($type, self::TYPES), 404);

        $modelClass = self::TYPES[$type];
        $item = $modelClass::onlyTrashed()->findOrFail($id);
        $item->restore();

        log_admin_event('trash_restored', $item, ['type' => $type]);

        return back()->with('success', self::LABELS[$type].' geri yuklendi.');
    }

    public function forceDestroy(Request $request, string $type, int $id)
    {
        abort_unless(array_key_exists($type, self::TYPES), 404);

        $modelClass = self::TYPES[$type];
        $item = $modelClass::onlyTrashed()->findOrFail($id);

        log_admin_event('trash_force_deleted', $item, ['type' => $type]);
        $item->forceDelete();

        return back()->with('success', self::LABELS[$type].' kalici olarak silindi.');
    }
}
