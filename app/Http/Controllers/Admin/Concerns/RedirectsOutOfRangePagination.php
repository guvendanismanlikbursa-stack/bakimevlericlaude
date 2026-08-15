<?php

namespace App\Http\Controllers\Admin\Concerns;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

// 15 Agustos 2026: kullanicinin "asla hata kalmamali" talebi uzerine yapilan
// denetimde bulundu - Public\FacilityController::index() / PriceGuideController
// icin daha once duzeltilen "menzil disi sayfa numarasina gidilince yanlis
// 'sonuc yok' mesaji" hatasi, admin panelindeki HICBIR liste ekraninda
// (18 dosya taranmis) engellenmiyordu. En somut ornek: Cop Kutusu'nda son
// sayfadaki tek kaydi geri yukleyip/silip back() ile ayni sayfaya donunce
// (artik bos olan) o sayfa "bu kategoride kayit yok" gosterebiliyordu,
// oysa onceki sayfalarda hala kayit vardi.
trait RedirectsOutOfRangePagination
{
    private function redirectIfPageOutOfRange(Request $request, LengthAwarePaginator $paginator): ?RedirectResponse
    {
        if ($paginator->isEmpty() && $paginator->total() > 0 && $paginator->currentPage() > $paginator->lastPage()) {
            return redirect($request->fullUrlWithQuery(['page' => $paginator->lastPage()]));
        }

        return null;
    }
}
