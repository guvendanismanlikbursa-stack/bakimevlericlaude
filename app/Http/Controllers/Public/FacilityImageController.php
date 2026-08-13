<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Facility;
use App\Models\FacilityImage;
use Illuminate\Http\Request;

// 13 Agustos 2026: kullanicinin talebi - "hangi gorselim daha cok ilgi
// cekiyor goremiyorum". Ziyaretci galeride bir gorseli buyutup actiginda
// (bkz. themes._shared.partials.image-lightbox'taki PhotoSwipe 'change'
// dinleyicisi) bu uc nokta cagrilir, kurum panelindeki performans
// bolumunde "en cok ilgi goren gorselleriniz" olarak gosterilir.
class FacilityImageController extends Controller
{
    // 13 Agustos 2026: bu uygulamada /site/{brand}/... rotalari ayni kapali
    // fonksiyon iki kez (brand'siz + {brand} onekiyle) kaydedildigi icin
    // (bkz. routes/web.php $siteRoutes), SADECE ortuk route-model-binding
    // degil, ISIMLI SCALAR route parametreleri de (ör. "string $slug")
    // METOD IMZASINDA tutulursa yanlis route parametresiyle (bu durumda
    // {brand} degeriyle) eslesebiliyor - canli testte yakalandi ({slug}
    // yerine "bakimeviara" geldi). Bu yuzden kod tabanindaki TUM benzer
    // controller'lar (bkz. FacilityController::show, LocationGuideController)
    // route parametrelerini metod imzasinda TUTMAZ, hepsini $request->route()
    // ile elle okur - burada da ayni kurala uyuluyor.
    public function markViewed(Request $request)
    {
        $brand = current_brand();
        $facility = Facility::published()->forBrand($brand['category_scope'])->where('slug', $request->route('slug'))->first();
        $image = FacilityImage::find($request->route('image'));

        if (! $facility || ! $image || $image->facility_id !== $facility->id) {
            return response()->json(['ok' => false], 404);
        }

        $image->increment('views_count');

        return response()->json(['ok' => true]);
    }
}
