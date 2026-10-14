<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Expert;
use Illuminate\Http\Request;

// 7 Ekim 2026: "Özel Doktorlar" (doktorlar) - Facility'den tamamen bagimsiz,
// ayri bir kontrolcu. Mevcut kurum listeleme/filtreleme kodunun hicbirine
// dokunulmadan, ayni desenler (il/ilce filtresi, akordiyon-liste) yeniden
// uygulanir. Faz 1: SADECE goruntuleme, hicbir yazma/form islemi yok.
class ExpertController extends Controller
{
    // 7 Ekim 2026: kullanicinin talebi - ilk asamada (veri cekiciler mesgul
    // oldugu icin) sadece birkac demo kayitla test edilecek, gercek
    // ziyaretciye GORUNMEYECEK (hicbir menude linklenmiyor, robots noindex).
    // Kullanici onaylayinca bu sabit kaldirilir/true yapilir.
    private const PUBLICLY_VISIBLE = false;

    private const BRANCHES = [
        'pediatri' => ['title' => 'Pediatri', 'label' => 'Çocuk Doktoru'],
        'dis-hekimligi' => ['title' => 'Diş Hekimliği', 'label' => 'Diş Doktoru'],
        'geriatri' => ['title' => 'Geriatri', 'label' => 'Yaşlılık Uzmanı'],
        'fiziksel-tip-ve-rehabilitasyon' => ['title' => 'Fiziksel Tıp ve Rehabilitasyon', 'label' => 'Fizik Tedavi Doktoru'],
        'cocuk-norolojisi' => ['title' => 'Çocuk Nörolojisi', 'label' => 'Çocuk Beyin ve Sinir Hastalıkları'],
        'cocuk-psikiyatrisi' => ['title' => 'Çocuk Psikiyatrisi', 'label' => 'Çocuk Gelişim Uzmanı'],
        'noroloji' => ['title' => 'Nöroloji', 'label' => 'Beyin ve Sinir Hastalıkları'],
        'ortopedi' => ['title' => 'Ortopedi', 'label' => 'Kemik ve Eklem Hastalıkları'],
    ];

    public function index(Request $request)
    {
        $cities = City::orderBy('name')->get();
        $districtMap = $cities->mapWithKeys(fn ($city) => [$city->slug => districts_for_city($city->name)]);
        $branches = self::BRANCHES;
        $activeExpertSlug = $request->query('uzman');
        // 7 Ekim 2026: kullanicinin talebi - SEO/paylasim icin, bir doktor
        // karti acik haldeyken (?uzman=slug) sayfa basligi/aciklamasi/
        // canonical'i o doktoru yansitmali - aksi halde paylasilan link
        // Google'da/sosyal medyada hep ayni genel "Özel Doktorlar" basligini
        // gosterir, o doktora ozel degeri olmaz.
        $activeExpert = $activeExpertSlug
            ? Expert::where('slug', $activeExpertSlug)->where('is_published', true)->with('city')->first()
            : null;
        $publiclyVisible = self::PUBLICLY_VISIBLE;

        // 7 Ekim 2026: kullanicinin talebi - kullanici hicbir filtre/arama
        // yapmadan sayfaya ilk geldiginde RASTGELE doktorlar gosterilmesin,
        // bunun yerine bolumu tanitan bir bilgilendirme gorunsun. Sadece
        // en az bir filtre (il/ilce/brans/isim aramasi) uygulaninca
        // sorgu calisir.
        $hasFilter = $request->filled('city') || $request->filled('district') || $request->filled('branch') || $request->filled('search') || $request->filled('min_rating');
        $experts = null;

        if ($hasFilter) {
            $query = Expert::query()->where('is_published', true)->with('city');

            if ($request->filled('city')) {
                $query->whereHas('city', fn ($c) => $c->where('slug', $request->city));
            }
            if ($request->filled('district')) {
                $query->where('district', $request->district);
            }
            if ($request->filled('branch') && isset(self::BRANCHES[$request->branch])) {
                // 7 Ekim 2026: kullanicinin acik talebi uzerine eklenen
                // min_rating filtresiyle BIRLIKTE calisirken kapsayici bir
                // parantez icine alinmasi SART - sarilmamis bir orWhere()
                // SQL'de "AND" den once gelen butun kosullari degil, SADECE
                // en yakinindaki kosulu "OR" ile birlestirir, bu da
                // is_published/min_rating gibi diger filtrelerin yanlislikla
                // atlanmasina (veya yayinlanmamis kayitlarin sizmasina)
                // yol acabilirdi.
                $query->where(function ($q) use ($request) {
                    $q->where('branch_label', self::BRANCHES[$request->branch]['label'])
                        ->orWhere('branch', self::BRANCHES[$request->branch]['title']);
                });
            }
            // 7 Ekim 2026: kullanicinin talebi - Google puanina gore filtre
            // (ör. "4.5 ve üzeri").
            if ($request->filled('min_rating')) {
                $query->where('rating', '>=', (float) $request->min_rating);
            }
            // 7 Ekim 2026: kullanicinin talebi - isimle anlik (her harfte)
            // arama, kurum listesindeki ayni davranis (bkz. _results AJAX).
            // 7 Ekim 2026 (2): kullanicinin ek talebi - arama kutusuna
            // doktor ismi VEYA brans yazarak da filtrelenebilsin (ör.
            // "pediatri" yazinca o branstaki doktorlar da cikmali).
            if ($request->filled('search')) {
                $term = $request->search;
                $query->where(function ($q) use ($term) {
                    $q->where('name', 'like', '%'.$term.'%')
                        ->orWhere('branch', 'like', '%'.$term.'%')
                        ->orWhere('branch_label', 'like', '%'.$term.'%');
                });
            }

            $experts = $query->orderBy('name')->paginate(24)->withQueryString();
        }

        if ($request->ajax()) {
            return response()->json([
                'count' => $experts?->total() ?? 0,
                'html' => view('themes._shared.experts._results', compact('experts', 'activeExpertSlug'))->render(),
            ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        }

        return view('themes._shared.experts.index', compact('experts', 'cities', 'districtMap', 'branches', 'activeExpertSlug', 'activeExpert', 'publiclyVisible', 'hasFilter'));
    }
}
