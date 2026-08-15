<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Facility;
use App\Models\FacilityCategory;
use App\Models\FamilyUser;
use App\Models\OfferRequest;
use App\Services\OfferRequestNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class OfferRequestController extends Controller
{
    private const BULK_LIMIT = 5;

    /**
     * Hem tek kuruma dogrudan talep hem de sehir/kategorideki tum kurumlara
     * yayin talebi buradan gelir. Giris yoksa bilgiler oturumda saklanir.
     */
    public function store(Request $request, OfferRequestNotificationService $notifier)
    {
        // 15 Agustos 2026: honeypot - bkz. partials/honeypot.blade.php.
        // Ayri validate() cagrisi bilerek - $validated dogrudan
        // OfferRequest::create()'e gectigi icin 'website' anahtarinin
        // karismasi istenmiyor.
        $request->validate(['website' => 'max:0']);

        $validated = $request->validate([
            'facility_id' => 'nullable|integer|exists:facilities,id',
            'city_id' => 'nullable|integer|exists:cities,id',
            'facility_category_id' => 'nullable|integer|exists:facility_categories,id',
            'full_name' => 'required|string|max:120',
            'phone' => 'required|string|max:30',
            'email' => 'nullable|email|max:150',
            'patient_name' => 'nullable|string|max:120',
            'care_for' => 'nullable|string|max:60',
            'message' => 'nullable|string|max:2000',
        ]);

        $brand = current_brand();
        $validated['brand'] = $brand['slug'];

        if (! empty($validated['facility_id'])) {
            $facility = Facility::with('category')->published()->forBrand($brand['category_scope'])
                ->where('is_claimed', true)
                ->findOrFail($validated['facility_id']);
            $validated['city_id'] = $facility->city_id;
            $validated['facility_category_id'] = $facility->facility_category_id;
        } else {
            abort_unless(! empty($validated['city_id']) && ! empty($validated['facility_category_id']), 422);

            $category = FacilityCategory::findOrFail($validated['facility_category_id']);
            abort_unless(in_array($category->brand_scope, $brand['category_scope'], true), 404);
        }

        if ($familyId = session('family_user_id')) {
            if ($redirect = $this->blockIfUnverified($familyId)) {
                return $redirect;
            }

            $validated['family_user_id'] = $familyId;
            $offerRequest = OfferRequest::create($validated);
            $notifier->notify($offerRequest);

            return redirect(brand_route('family.dashboard'))->with('success', 'Talebiniz kuruma iletildi. Kurum yanıt verdiğinde burada bildirim alacak, teklifi inceleyip mesajlaşmaya başlayabileceksiniz.');
        }

        session(['pending_offer_request' => $validated]);

        return redirect(brand_route('family.register'))
            ->with('info', 'Ucret/teklif bilgisi alabilmek icin once ucretsiz bir aile hesabi olusturmaniz gerekiyor. Bilgileriniz kaybolmayacak.');
    }

    /**
     * "Toplu Fiyat Al": aile en fazla BULK_LIMIT kurumu tek seferde
     * secip ayni forma tek cevap yazarak hepsine talep gonderir. Her
     * kurum icin AYRI bir offer_requests satiri olusur (aile panelinde
     * bagimsiz gorunurler — bilincli tercih), ama hepsi ayni batch_id'yi
     * tasir (admin panelinde grup olarak gosterilebilsin diye).
     */
    public function storeBulk(Request $request, OfferRequestNotificationService $notifier)
    {
        // 15 Agustos 2026: honeypot - bkz. partials/honeypot.blade.php.
        $request->validate(['website' => 'max:0']);

        $validated = $request->validate([
            'facility_ids' => 'required|array|min:1|max:'.self::BULK_LIMIT,
            'facility_ids.*' => 'integer|distinct|exists:facilities,id',
            'full_name' => 'required|string|max:120',
            'phone' => 'required|string|max:30',
            'email' => 'nullable|email|max:150',
            'patient_name' => 'nullable|string|max:120',
            'care_for' => 'nullable|string|max:60',
            'message' => 'nullable|string|max:2000',
        ]);

        $brand = current_brand();
        $facilities = Facility::published()->forBrand($brand['category_scope'])
            ->where('is_claimed', true)
            ->whereIn('id', $validated['facility_ids'])
            ->get();

        abort_if($facilities->isEmpty(), 422, 'Secili kurumlar bulunamadi.');

        $payload = [
            'brand' => $brand['slug'],
            'full_name' => $validated['full_name'],
            'phone' => $validated['phone'],
            'email' => $validated['email'] ?? null,
            'patient_name' => $validated['patient_name'] ?? null,
            'care_for' => $validated['care_for'] ?? null,
            'message' => $validated['message'] ?? null,
            'facility_ids' => $facilities->pluck('id')->all(),
        ];

        if ($familyId = session('family_user_id')) {
            if ($redirect = $this->blockIfUnverified($familyId)) {
                return $redirect;
            }

            $this->createBulkRequests($payload, $familyId, $notifier);

            return redirect(brand_route('family.dashboard'))->with('success', 'Talebiniz '.count($payload['facility_ids']).' kuruma iletildi. Yanıt veren kurumları panelinizden karşılaştırabilir, dilediğinizle mesajlaşmaya başlayabilirsiniz.');
        }

        session(['pending_bulk_offer_request' => $payload]);

        return redirect(brand_route('family.register'))
            ->with('info', 'Ucret/teklif bilgisi alabilmek icin once ucretsiz bir aile hesabi olusturmaniz gerekiyor. Bilgileriniz kaybolmayacak.');
    }

    /**
     * 21 Temmuz 2026: kayit olur olmaz olusturulan ILK talep (bkz.
     * Family\AuthController::afterLogin, session('pending_offer_request'))
     * BILEREK dogrulama beklemiyor - donusumu kirmamak icin. Ama zaten
     * oturum acmis bir aile buradan YENI bir talep daha gonderiyorsa
     * (veya toplu talep), sahte/yaziml hatali e-postayla sinirsiz kurum
     * kredisi tuketilmesini onlemek icin e-posta dogrulanmis olmali -
     * kurum panelindeki (FacilityUserAuth) ayni kuralla tutarli.
     */
    private function blockIfUnverified(int $familyId)
    {
        $family = FamilyUser::find($familyId);

        if ($family && ! $family->hasVerifiedEmail()) {
            return redirect(brand_route('family.verify-email.notice'))
                ->with('info', 'Yeni bir teklif talebi gönderebilmek için önce e-posta adresinizi doğrulamanız gerekiyor.');
        }

        return null;
    }

    public static function createBulkRequests(array $payload, int $familyId, OfferRequestNotificationService $notifier): array
    {
        $categoryScope = config('brands.brands')[$payload['brand']]['category_scope'] ?? [];
        $batchId = (string) Str::uuid();
        $created = [];

        foreach ($payload['facility_ids'] as $facilityId) {
            // Form doldurma ile hesap olusturma arasinda kurum
            // sahiplenmeden cikmis/yayindan kaldirilmis olabilir; kayit
            // anindan once burada tekrar dogrulaniyor.
            $facility = Facility::published()->where('is_claimed', true)->find($facilityId);
            if (! $facility || ! in_array($facility->category?->brand_scope, $categoryScope, true)) {
                continue;
            }

            $offerRequest = OfferRequest::create([
                'facility_id' => $facility->id,
                'city_id' => $facility->city_id,
                'facility_category_id' => $facility->facility_category_id,
                'family_user_id' => $familyId,
                'brand' => $payload['brand'],
                'full_name' => $payload['full_name'],
                'phone' => $payload['phone'],
                'email' => $payload['email'] ?? null,
                'patient_name' => $payload['patient_name'] ?? null,
                'care_for' => $payload['care_for'] ?? null,
                'message' => $payload['message'] ?? null,
                'batch_id' => $batchId,
            ]);

            $notifier->notify($offerRequest);
            $created[] = $offerRequest;
        }

        return $created;
    }
}