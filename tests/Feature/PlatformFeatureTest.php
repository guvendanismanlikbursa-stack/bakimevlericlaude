<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\BalanceLog;
use App\Models\City;
use App\Models\DataImportBatch;
use App\Models\DataImportRow;
use App\Models\Facility;
use App\Models\FacilityCategory;
use App\Models\FacilityClaim;
use App\Models\FacilityImage;
use App\Models\FacilityReview;
use App\Models\FacilityUser;
use App\Models\FamilyUser;
use App\Models\PlatformNotification;
use App\Models\Message;
use App\Models\OfferRequest;
use App\Models\Quote;
use App\Models\VisitRequest;
use App\Mail\FacilityClaimApprovedMail;
use App\Mail\FacilityEmailVerificationMail;
use App\Models\WalletTopup;
use App\Services\FacilityImportImageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class PlatformFeatureTest extends TestCase
{
    use RefreshDatabase;

    private City $city;
    private FacilityCategory $elderlyCategory;
    private FacilityCategory $childCategory;
    private FacilityCategory $rehabCategory;
    private Facility $elderlyFacility;
    private Facility $childFacility;
    private Facility $rehabFacility;
    private Facility $rehabFacilityClaimed;
    private FamilyUser $family;
    private FacilityUser $facilityUser;
    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->city = City::firstOrCreate(['slug' => 'istanbul'], ['name' => 'Istanbul']);
        $this->elderlyCategory = FacilityCategory::create(['name' => 'Yasli Bakim Evi', 'slug' => 'yasli-bakim-evi', 'brand_scope' => 'yasli-bakim']);
        $this->childCategory = FacilityCategory::create(['name' => 'Ozel Egitim Merkezi', 'slug' => 'ozel-egitim-merkezi', 'brand_scope' => 'ozel-egitim']);
        $this->rehabCategory = FacilityCategory::create(['name' => 'Fizik Tedavi', 'slug' => 'fizik-tedavi', 'brand_scope' => 'fizik-tedavi']);

        $this->elderlyFacility = $this->facility('Yasli Kurum', $this->elderlyCategory, true);
        $this->childFacility = $this->facility('Cocuk Kurum', $this->childCategory, true);
        $this->rehabFacility = $this->facility('Rehab Kurum', $this->rehabCategory, false);
        $this->rehabFacilityClaimed = $this->facility('Rehab Kurum Onayli', $this->rehabCategory, true);

        $this->family = FamilyUser::create([
            'registered_brand' => 'bakimevibul',
            'name' => 'Demo Aile',
            'email' => 'aile@test.local',
            'phone' => '05550000000',
            'password' => Hash::make('Aile12345!'),
        ]);

        $this->facilityUser = FacilityUser::create([
            'facility_id' => $this->childFacility->id,
            'name' => 'Demo Kurum',
            'email' => 'kurum@test.local',
            'phone' => '05551111111',
            'password' => Hash::make('Kurum12345!'),
            'must_change_password' => false,
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        $this->admin = Admin::create([
            'name' => 'Admin',
            'email' => 'admin@test.local',
            'password' => Hash::make('Admin12345!'),
            'role' => 'superadmin',
        ]);
    }

    public function test_public_sites_and_auth_panels_open(): void
    {
        $this->get('/')->assertOk()->assertSee('bakimevibul.com');
        $this->get('/site/bakimeviara/')->assertOk()->assertSee('bakimeviara.com');
        $this->get('/site/bakimevleri/')->assertOk()->assertSee('bakimevleri.com');
        $this->get('/admin/giris')->assertOk()->assertSee('Ortak Admin Panel');
    }

    public function test_family_account_is_global_but_dashboard_is_brand_scoped(): void
    {
        OfferRequest::create($this->offerData('bakimevibul', $this->elderlyCategory, 'Bul Talep'));
        OfferRequest::create($this->offerData('bakimeviara', $this->childCategory, 'Ara Talep'));

        $this->withSession(['family_user_id' => $this->family->id, 'family_user_name' => $this->family->name])
            ->get('/site/bakimevibul/aile/panel')
            ->assertOk()
            ->assertSee('Aile Paneli')
            ->assertSee('Toplam Talep')
            ->assertSee('Gelen Teklif')
            ->assertSee('Yasli Bakim Evi')
            ->assertDontSee('Ara Talep');

        $this->withSession(['family_user_id' => $this->family->id, 'family_user_name' => $this->family->name])
            ->get('/site/bakimeviara/aile/panel')
            ->assertOk()
            ->assertSee('Aile Paneli')
            ->assertSee('Ozel Egitim Merkezi')
            ->assertDontSee('Bul Talep');
    }

    public function test_each_site_accepts_all_three_main_service_sections(): void
    {
        $this->get('/site/bakimevibul/?bolum=yasli-bakim')
            ->assertOk()
            ->assertSeeText('Yaşlı Bakım')
            ->assertSeeText('Çocuk')
            ->assertSeeText('Rehabilitasyon');

        $this->withSession(['family_user_id' => $this->family->id])
            ->post('/site/bakimevibul/teklif-talebi', [
                'city_id' => $this->city->id,
                'facility_category_id' => $this->childCategory->id,
                'full_name' => 'Cocuk Kapsam',
                'phone' => '05550000000',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('offer_requests', [
            'brand' => 'bakimevibul',
            'facility_category_id' => $this->childCategory->id,
        ]);
    }

    public function test_offer_quote_accept_and_messages_end_to_end(): void
    {
        $request = OfferRequest::create($this->offerData('bakimeviara', $this->childCategory, 'Cocuk Talep'));

        $this->withSession(['facility_user_id' => $this->facilityUser->id, 'facility_user_name' => $this->facilityUser->name])
            ->post('/site/bakimeviara/kurum-panel/talep/'.$request->id.'/teklif-ver', [
                'price' => 12000,
                'price_period' => 'monthly',
                'message' => 'Uygunuz.',
            ])
            ->assertRedirect();

        $quote = Quote::where('offer_request_id', $request->id)->firstOrFail();
        $this->assertSame($this->childFacility->id, $quote->facility_id);

        $this->withSession(['family_user_id' => $this->family->id, 'family_user_name' => $this->family->name])
            ->post('/site/bakimeviara/aile/teklif/'.$quote->id.'/kabul-et')
            ->assertRedirect();

        $this->assertSame('accepted', $quote->fresh()->status);
        $this->assertSame($quote->id, $request->fresh()->accepted_quote_id);

        $this->withSession(['family_user_id' => $this->family->id])
            ->post('/site/bakimeviara/aile/talep/'.$request->id.'/mesajlar', ['body' => 'Merhaba'])
            ->assertRedirect();

        $this->withSession(['facility_user_id' => $this->facilityUser->id])
            ->post('/site/bakimeviara/kurum-panel/talep/'.$request->id.'/mesajlar', ['body' => 'Merhaba, sizi arayalim.'])
            ->assertRedirect();

        $this->assertSame(2, Message::where('offer_request_id', $request->id)->count());
    }

    public function test_facility_cannot_quote_request_from_another_brand(): void
    {
        $request = OfferRequest::create($this->offerData('bakimevleri', $this->rehabCategory, 'Rehab Talep'));

        $this->withSession(['facility_user_id' => $this->facilityUser->id])
            ->post('/site/bakimevleri/kurum-panel/talep/'.$request->id.'/teklif-ver', [
                'price' => 9000,
                'price_period' => 'monthly',
            ])
            ->assertForbidden();
    }

    public function test_panels_are_hidden_and_require_valid_registered_users(): void
    {
        $this->get('/site/bakimevleri/')
            ->assertOk()
            ->assertSee('Aile Girişi')
            ->assertSee('Kurum Girişi')
            ->assertDontSee('Aile Panelim')
            ->assertDontSee('Kurum Panelim');

        $this->get('/site/bakimevleri/aile/panel')
            ->assertRedirect('/site/bakimevleri/aile/giris');

        $this->get('/site/bakimevleri/kurum-panel/panel')
            ->assertRedirect('/site/bakimevleri/kurum-panel/giris');

        $this->withSession(['family_user_id' => 999999, 'family_user_name' => 'Silinmis Aile'])
            ->get('/site/bakimevleri/aile/panel')
            ->assertRedirect('/site/bakimevleri/aile/giris');

        $inactive = FacilityUser::create([
            'facility_id' => $this->elderlyFacility->id,
            'name' => 'Pasif Kurum',
            'email' => 'pasif@test.local',
            'phone' => '05553333333',
            'password' => Hash::make('Kurum12345!'),
            'must_change_password' => false,
            'status' => 'pending',
        ]);

        $this->withSession(['facility_user_id' => $inactive->id, 'facility_user_name' => $inactive->name])
            ->get('/site/bakimevleri/kurum-panel/panel')
            ->assertRedirect('/site/bakimevleri/kurum-panel/giris');
    }

    public function test_engagement_pages_open_and_facility_actions_are_visible(): void
    {
        $this->get('/site/bakimevibul/karar-sihirbazi?bolum=yasli-bakim')
            ->assertOk()
            ->assertSee('name="city"', false)
            ->assertSee('/site/bakimevibul/kurumlar', false);

        $this->get('/site/bakimeviara/karar-sihirbazi?bolum=cocuk')
            ->assertOk()
            ->assertSee('name="service"', false);

        $this->get('/site/bakimevleri/karsilastir')
            ->assertOk()
            ->assertSee('board-empty', false);

        // Favoriler sayfasi artik sadece giris yapmis ailelere acik.
        $this->withSession(['family_user_id' => $this->family->id])
            ->get('/site/bakimevleri/favoriler')
            ->assertOk()
            ->assertSee('board-favorites', false);

        $this->get('/site/bakimevleri/kurumlar?bolum=rehabilitasyon')
            ->assertOk()
            ->assertSee('js-engagement-toggle', false)
            ->assertSee('data-mode="compare"', false)
            ->assertSee('data-mode="favorites"', false);
    }

    public function test_location_guide_and_map_panel_open(): void
    {
        $this->get('/site/bakimevleri/rehber/rehabilitasyon/istanbul')
            ->assertOk()
            ->assertSee('İl / ilçe rehberi')
            ->assertSee('Rehabilitasyon');

        $this->get('/site/bakimevleri/kurumlar?bolum=rehabilitasyon&city=istanbul')
            ->assertOk()
            ->assertSee('Konum görünümü')
            ->assertSee('istanbul', false);

        $this->get('/site/bakimevleri/kurumlar/'.$this->rehabFacility->slug)
            ->assertOk()
            ->assertSee('Detaylı kontrol listesi')
            ->assertSee('Terapi planını sor');
    }

    public function test_reviews_and_visit_requests_can_be_created_and_managed(): void
    {
        $this->post('/site/bakimevleri/kurumlar/'.$this->rehabFacilityClaimed->slug.'/ziyaret-talebi', [
            'full_name' => 'Ziyaret Talep',
            'phone' => '05555555555',
            'email' => 'ziyaret@test.local',
            'preferred_day' => 'Hafta içi',
            'preferred_time' => 'Sabah',
            'message' => 'Kurum ziyareti istiyoruz.',
        ])->assertRedirect();

        $visit = VisitRequest::firstOrFail();
        $this->assertSame('new', $visit->status);

        OfferRequest::create(array_merge(
            $this->offerData('bakimevleri', $this->rehabCategory, 'Rehab icin bilgi'),
            ['facility_id' => $this->rehabFacilityClaimed->id]
        ));

        $this->withSession(['family_user_id' => $this->family->id])
            ->post('/site/bakimevleri/kurumlar/'.$this->rehabFacilityClaimed->slug.'/yorum', [
                'rating' => 5,
                'body' => 'Kurumla gorustuk, bilgi aldik.',
            ])->assertRedirect();

        $review = FacilityReview::firstOrFail();
        $this->assertSame('pending', $review->status);
        $this->assertSame($this->family->id, $review->family_user_id);
        $this->assertSame($this->family->name, $review->reviewer_name);

        $this->withSession(['admin_id' => $this->admin->id])
            ->get('/admin/yorumlar')
            ->assertOk()
            ->assertSee('Yorumlar');

        $this->withSession(['admin_id' => $this->admin->id])
            ->put('/admin/yorumlar/'.$review->id, ['status' => 'approved'])
            ->assertRedirect();

        $this->assertSame('approved', $review->fresh()->status);

        $this->withSession(['admin_id' => $this->admin->id])
            ->get('/admin/ziyaret-talepleri')
            ->assertOk()
            ->assertSee('Ziyaret Talepleri');

        $this->withSession(['admin_id' => $this->admin->id])
            ->put('/admin/ziyaret-talepleri/'.$visit->id, ['status' => 'contacted'])
            ->assertRedirect();

        $this->assertSame('contacted', $visit->fresh()->status);

        $this->get('/site/bakimevleri/kurumlar/'.$this->rehabFacilityClaimed->slug)
            ->assertOk()
            ->assertSee('Veri kalite skoru')
            ->assertSee('Kurum yorumları')
            ->assertSee('Ziyaret / randevu talebi');
    }

    public function test_unclaimed_facility_blocks_offer_visit_question_and_review_requests(): void
    {
        $this->post('/site/bakimevleri/kurumlar/'.$this->rehabFacility->slug.'/ziyaret-talebi', [
            'full_name' => 'Ziyaret Talep',
            'phone' => '05555555555',
        ])->assertNotFound();

        $this->post('/site/bakimevleri/kurumlar/'.$this->rehabFacility->slug.'/kontenjan-sor', [
            'full_name' => 'Soran',
            'phone' => '05555555556',
        ])->assertNotFound();

        $this->post('/site/bakimevleri/kurumlar/'.$this->rehabFacility->slug.'/soru-sor', [
            'question' => 'Boş yer var mı?',
        ])->assertNotFound();

        $this->post('/site/bakimevleri/teklif-talebi', [
            'facility_id' => $this->rehabFacility->id,
            'full_name' => 'Talep Eden',
            'phone' => '05555555557',
        ])->assertNotFound();

        $this->withSession(['family_user_id' => $this->family->id])
            ->post('/site/bakimevleri/kurumlar/'.$this->rehabFacility->slug.'/yorum', [
                'rating' => 5,
                'body' => 'Yorum.',
            ])->assertNotFound();

        $this->assertSame(0, VisitRequest::count());
        $this->assertSame(0, FacilityReview::count());
        $this->assertSame(0, OfferRequest::count());

        $this->get('/site/bakimevleri/kurumlar/'.$this->rehabFacility->slug)
            ->assertOk()
            ->assertDontSee('data-mode="compare"', false)
            ->assertDontSee('Ücret / Teklif Bilgisi Al')
            ->assertSee('Bu kurum henüz sahiplenilmedi');
    }

    public function test_review_requires_login_and_prior_offer_request_even_for_claimed_facility(): void
    {
        $this->post('/site/bakimevleri/kurumlar/'.$this->rehabFacilityClaimed->slug.'/yorum', [
            'rating' => 5,
            'body' => 'Giris yapmadan yorum.',
        ])->assertSessionHasErrors('review');

        $this->withSession(['family_user_id' => $this->family->id])
            ->post('/site/bakimevleri/kurumlar/'.$this->rehabFacilityClaimed->slug.'/yorum', [
                'rating' => 5,
                'body' => 'Teklif talebi olmadan yorum.',
            ])->assertSessionHasErrors('review');

        $this->assertSame(0, FacilityReview::count());
    }

    public function test_sitemap_and_profile_quality_surfaces_are_visible(): void
    {
        // Sitemap artik marka basina uretilir (Host header'a gore); her marka
        // sadece kendi gercek alan adiyla uretilmis URL'leri icermeli.
        $this->get('http://bakimevleri.com/sitemap.xml')
            ->assertOk()
            ->assertHeader('content-type', 'application/xml; charset=UTF-8')
            ->assertSee('bakimevleri.com/rehber/rehabilitasyon/istanbul', false)
            ->assertSee('bakimevleri.com/kurumlar/'.$this->rehabFacility->slug, false)
            ->assertDontSee('bakimevibul.com', false);

        $this->get('http://bakimevleri.com/robots.txt')
            ->assertOk()
            ->assertSee('Sitemap: http://bakimevleri.com/sitemap.xml', false);

        $this->withSession(['admin_id' => $this->admin->id])
            ->get('/admin/kurumlar')
            ->assertOk()
            ->assertSee('Profil Kalitesi')
            ->assertSee('/100');

        $this->withSession(['facility_user_id' => $this->facilityUser->id])
            ->get('/site/bakimeviara/kurum-panel/profil')
            ->assertOk()
            ->assertSee('Profil kalite puani')
            ->assertSee('alan tamamlandi');

        $this->withSession(['family_user_id' => $this->family->id])
            ->get('/site/bakimevibul/aile/panel')
            ->assertOk()
            ->assertSee('Karar Merkezi')
            ->assertSee('Karsilastirma')
            ->assertSee('Favori listeniz');
    }
    public function test_admin_can_create_update_and_delete_facility(): void
    {
        $payload = [
            'name' => 'Yeni Kurum',
            'city_id' => $this->city->id,
            'facility_category_id' => $this->elderlyCategory->id,
            'district' => 'Merkez',
            'address' => 'Adres',
            'phone' => '02120000000',
            'description' => 'Aciklama',
            'capacity' => 20,
            'price_min' => 1000,
            'price_max' => 2000,
            'services_raw' => 'bakim, doktor',
            'is_published' => '1',
        ];

        $this->withSession(['admin_id' => $this->admin->id, 'admin_name' => $this->admin->name])
            ->post('/admin/kurumlar', $payload)
            ->assertRedirect();

        $facility = Facility::where('name', 'Yeni Kurum')->firstOrFail();

        $this->withSession(['admin_id' => $this->admin->id])
            ->put('/admin/kurumlar/'.$facility->id, array_merge($payload, ['name' => 'Yeni Kurum Guncel']))
            ->assertRedirect();

        $this->assertDatabaseHas('facilities', ['id' => $facility->id, 'name' => 'Yeni Kurum Guncel']);

        Storage::fake('public');
        Storage::disk('public')->put('facilities/yeni-kurum.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/p9sAAAAASUVORK5CYII='));
        FacilityImage::create(['facility_id' => $facility->id, 'path' => 'facilities/yeni-kurum.png', 'sort_order' => 0]);

        $this->withSession(['admin_id' => $this->admin->id])
            ->delete('/admin/kurumlar/'.$facility->id)
            ->assertRedirect();

        $this->assertSoftDeleted('facilities', ['id' => $facility->id]);
        $archivedFiles = Storage::disk('public')->allFiles('silinenler');
        $this->assertNotEmpty(preg_grep('/kurum\.json$/', $archivedFiles));
        $this->assertNotEmpty(preg_grep('/gorseller\/yeni-kurum\.png$/', $archivedFiles));
    }

    public function test_import_image_pool_attaches_section_gallery_images(): void
    {
        Storage::fake('public');

        $pool = storage_path('framework/testing/import-images/orn.cocuk');
        if (! is_dir($pool)) {
            mkdir($pool, 0777, true);
        }
        file_put_contents($pool.'/cocuk-demo.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/p9sAAAAASUVORK5CYII='));

        config(['platform.import_image_pool_path' => dirname($pool), 'platform.import_image_count' => 1]);

        $attached = app(FacilityImportImageService::class)->attachRandomImages($this->childFacility, $this->childCategory);

        $this->assertSame(1, $attached);
        $image = $this->childFacility->images()->firstOrFail();
        $this->assertStringStartsWith('facilities/demo/'.$this->childCategory->id.'/', $image->path);
        Storage::disk('public')->assertExists($image->path);
    }

    public function test_import_image_pool_finds_images_nested_in_subfolders(): void
    {
        // Gercek gorsel havuzu klasorleri (orn. "kurum gorselleri/bakimevi/1/…")
        // dosyalari bir alt klasore dagitiyor; havuz taramasi sadece ust
        // seviyeye bakarsa hicbir gorsel bulunamaz. Bu, tam da o duruma
        // dair bir regresyon testi.
        Storage::fake('public');

        $pool = storage_path('framework/testing/nested-images/orn.yasli-bakim/alt-klasor');
        if (! is_dir($pool)) {
            mkdir($pool, 0777, true);
        }
        file_put_contents($pool.'/nested-demo.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/p9sAAAAASUVORK5CYII='));

        config(['platform.import_image_pool_path' => dirname(dirname($pool)), 'platform.import_image_count' => 1]);

        $attached = app(FacilityImportImageService::class)->attachRandomImages($this->elderlyFacility, $this->elderlyCategory);

        $this->assertSame(1, $attached);
    }




    public function test_admin_reviews_autofills_and_approves_extracted_rows(): void
    {
        Storage::fake('public');

        $pool = storage_path('framework/testing/approval-images/orn.rehabilitasyon');
        if (! is_dir($pool)) {
            mkdir($pool, 0777, true);
        }
        for ($i = 1; $i <= 5; $i++) {
            file_put_contents($pool.'/rehab-'.$i.'.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/p9sAAAAASUVORK5CYII='));
        }
        config(['platform.import_image_pool_path' => dirname($pool), 'platform.import_image_count' => 5]);

        $batch = DataImportBatch::create([
            'source' => 'google_maps_veri_cekici_auto',
            'admin_id' => $this->admin->id,
            'city_id' => $this->city->id,
            'facility_category_id' => $this->rehabCategory->id,
            'file_name' => 'otomatik: fizik tedavi test',
            'total_rows' => 1,
            'status' => 'pending_review',
        ]);

        $row = DataImportRow::create([
            'data_import_batch_id' => $batch->id,
            'row_number' => 1,
            'status' => 'pending_review',
            'name' => 'Veri Cekici Rehab Merkezi',
            'phone' => '02240000001',
            'payload' => [
                'name' => 'Veri Cekici Rehab Merkezi',
                'category' => 'Fizik Tedavi',
                'address' => 'Test adres',
                'district' => 'Merkez',
                'phone' => '02240000001',
                'email' => 'rehab@test.local',
                'rating' => '4,7',
            ],
        ]);

        $this->withSession(['admin_id' => $this->admin->id])
            ->post('/admin/veri-cekici/satir/'.$row->id.'/otomatik-doldur')
            ->assertRedirect();

        $this->assertSame('enriched', $row->fresh()->status);
        $this->assertNotEmpty($row->fresh()->payload['description'] ?? null);

        $this->withSession(['admin_id' => $this->admin->id])
            ->post('/admin/veri-cekici/satir/'.$row->id.'/onayla', ['is_published' => 1])
            ->assertRedirect();

        $facility = Facility::where('name', 'Veri Cekici Rehab Merkezi')->firstOrFail();
        $this->assertFalse($facility->is_claimed);
        $this->assertSame('google_maps_veri_cekici', $facility->source);
        $this->assertSame(5, $facility->images()->count());
        $this->assertDatabaseHas('rehab_facility_details', ['facility_id' => $facility->id]);

        $this->get('/site/bakimevleri/kurumlar?bolum=rehabilitasyon&pre_registered=1')
            ->assertOk()
            ->assertSee('Veri Cekici Rehab Merkezi')
            ->assertSee('Sahiplen');

        $this->get('/site/bakimevleri')
            ->assertOk()
            ->assertSee('On Kayitli Kurumlar')
            ->assertSee('Veri Cekici Rehab Merkezi');
    }

    public function test_admin_can_edit_extracted_row_before_approval(): void
    {
        $batch = DataImportBatch::create([
            'source' => 'google_maps_veri_cekici_auto',
            'admin_id' => $this->admin->id,
            'city_id' => $this->city->id,
            'facility_category_id' => $this->rehabCategory->id,
            'file_name' => 'otomatik: duzenleme testi',
            'total_rows' => 1,
            'status' => 'pending_review',
        ]);

        $row = DataImportRow::create([
            'data_import_batch_id' => $batch->id,
            'row_number' => 1,
            'status' => 'pending_review',
            'name' => 'Eski Isim Merkezi',
            'phone' => '02240000002',
            'payload' => [
                'name' => 'Eski Isim Merkezi',
                'address' => 'Eski adres',
                'district' => 'Merkez',
                'phone' => '02240000002',
            ],
        ]);

        $this->withSession(['admin_id' => $this->admin->id])
            ->get('/admin/veri-cekici/satir/'.$row->id)
            ->assertOk()
            ->assertSee('name="name"', false)
            ->assertSee('name="price_min"', false)
            ->assertSee('name="price_max"', false);

        $this->withSession(['admin_id' => $this->admin->id])
            ->put('/admin/veri-cekici/satir/'.$row->id, [
                'name' => 'Duzeltilmis Isim Merkezi',
                'address' => 'Yeni adres 12',
                'district' => 'Merkez',
                'phone' => '02240000009',
                'email' => 'duzeltilmis@test.local',
                'price_min' => 5000,
                'price_max' => 9000,
                'rating' => '4,5',
                'description' => 'Admin tarafindan duzeltilen aciklama.',
            ])
            ->assertRedirect();

        $row->refresh();
        $this->assertSame('Duzeltilmis Isim Merkezi', $row->name);
        $this->assertSame('02240000009', $row->phone);
        $this->assertSame('pending_review', $row->status);
        $this->assertSame('Yeni adres 12', $row->payload['address']);
        $this->assertSame('9000', (string) $row->payload['price_max']);

        // price_min doluyken price_max daha kucukse reddedilmeli, diger alanlar da kaydedilmemeli.
        $this->withSession(['admin_id' => $this->admin->id])
            ->put('/admin/veri-cekici/satir/'.$row->id, [
                'name' => 'Bu Kaydedilmemeli',
                'price_min' => 9000,
                'price_max' => 1000,
            ])
            ->assertSessionHasErrors('price_max');

        $this->assertSame('Duzeltilmis Isim Merkezi', $row->fresh()->name);
    }

    public function test_data_extractor_row_approval_rejects_near_duplicate_facility(): void
    {
        // Ayni telefon (farkli bicimde yazilmis) ve ayni isim (farkli
        // bosluk/buyuk-kucuk harfle) baska bir ilce/kategori aramasindan
        // tekrar gelirse onaylanmamali — "ayni kurumun birden fazla kaydi
        // asla olmamali" garantisi.
        $existing = $this->facility('Ata Huzurevi', $this->elderlyCategory, false);
        $existing->update(['phone' => '02121234567', 'district' => 'Kadikoy']);

        $batch = DataImportBatch::create([
            'source' => 'google_maps_veri_cekici_auto',
            'admin_id' => $this->admin->id,
            'city_id' => $this->city->id,
            'facility_category_id' => $this->elderlyCategory->id,
            'file_name' => 'otomatik: mukerrer test',
            'total_rows' => 1,
            'status' => 'pending_review',
        ]);

        // Ayni telefon, sadece format farkli (bosluk/tire), ayni isim farkli bosluklu.
        $duplicateRow = DataImportRow::create([
            'data_import_batch_id' => $batch->id,
            'row_number' => 1,
            'status' => 'pending_review',
            'name' => 'ATA  Huzurevi',
            'phone' => '0212 123 45 67',
            'payload' => [
                'name' => 'ATA  Huzurevi',
                'address' => 'Farkli arama sonucu adresi',
                'district' => 'Kadikoy',
                'phone' => '0212 123 45 67',
            ],
        ]);

        $this->withSession(['admin_id' => $this->admin->id])
            ->post('/admin/veri-cekici/satir/'.$duplicateRow->id.'/onayla', ['is_published' => 1])
            ->assertRedirect();

        // Reddedilmeli: ayni isimde/telefonda ikinci bir Facility olusmamali.
        $this->assertSame(1, Facility::where('phone', '02121234567')->count());
        $this->assertSame('skipped', $duplicateRow->fresh()->status);
    }

    public function test_admin_data_extractor_page_is_available(): void
    {
        $this->withSession(['admin_id' => $this->admin->id, 'admin_name' => $this->admin->name])
            ->get('/admin/veri-cekici')
            ->assertOk()
            ->assertSee('Veri Cekici')
            ->assertSee('Excel Import')
            ->assertSee('Canli API')
            ->assertSee('Devre disi');
    }


    public function test_facility_profile_saves_filter_features_and_section_details(): void
    {
        $this->withSession(['facility_user_id' => $this->facilityUser->id])
            ->put('/site/bakimeviara/kurum-panel/profil', [
                'name' => $this->childFacility->name,
                'city_id' => $this->city->id,
                'district' => 'Kadikoy',
                'address' => 'Cocuk kurum adresi',
                'phone' => '02121112233',
                'description' => 'Cocuk bakimi ve ozel egitim alaninda detayli kurum aciklamasi.',
                'capacity' => 36,
                'price_min' => 10000,
                'price_max' => 18000,
                'services' => ['Yaş grubu', 'Oyun alanı', 'Rehberlik servisi'],
                'services_raw' => 'Drama atölyesi',
                'section_details' => [
                    'yas-araligi' => '3-6 yaş',
                    'sinif-mevcudu' => '12 öğrenci',
                    'egitim-programi' => 'Montessori destekli karma program',
                ],
            ])->assertRedirect();

        $facility = $this->childFacility->fresh();
        $this->assertContains('Yaş grubu', $facility->services);
        $this->assertContains('Drama atölyesi', $facility->services);

        $details = \App\Models\ChildFacilityDetail::where('facility_id', $facility->id)->firstOrFail()->details;
        $this->assertSame('3-6 yaş', $details['yas-araligi']);
        $this->assertSame('12 öğrenci', $details['sinif-mevcudu']);
        $this->assertSame('Montessori destekli karma program', $details['egitim-programi']);
    }

    public function test_facility_gallery_accepts_max_ten_images(): void
    {
        Storage::fake('public');

        $images = [];
        for ($i = 1; $i <= 10; $i++) {
            $images[] = $this->fakePngUpload('galeri-'.$i.'.png');
        }

        $this->withSession(['facility_user_id' => $this->facilityUser->id])
            ->post('/site/bakimeviara/kurum-panel/profil/gorsel', ['images' => $images])
            ->assertRedirect();

        $this->assertSame(10, $this->childFacility->images()->count());

        $this->withSession(['facility_user_id' => $this->facilityUser->id])
            ->post('/site/bakimeviara/kurum-panel/profil/gorsel', ['images' => [$this->fakePngUpload('fazla.png')]])
            ->assertSessionHasErrors('images');

        $this->assertSame(10, $this->childFacility->images()->count());

        $this->withSession(['facility_user_id' => $this->facilityUser->id])
            ->get('/site/bakimeviara/kurum-panel/profil')
            ->assertOk()
            ->assertSee('Kurum Galerisi')
            ->assertSee('10/10 görsel yüklü')
            ->assertSee('10 görsel limiti doldu');

        $this->get('/site/bakimeviara/kurumlar/'.$this->childFacility->slug)
            ->assertOk()
            ->assertSee('Fotoğraf galerisi')
            ->assertSee('10/10 görsel');
    }

    public function test_claim_and_wallet_upload_flows_accept_real_png_files(): void
    {
        Storage::fake('public');
        Mail::fake();

        $this->post('/site/bakimevleri/kurumlar/'.$this->rehabFacility->slug.'/sahiplen', [
            'applicant_name' => 'Yetkili',
            'applicant_email' => 'yetkili@test.local',
            'applicant_phone' => '05552222222',
            'document' => $this->fakePngUpload('ruhsat.png'),
        ])->assertRedirect();

        $claim = FacilityClaim::firstOrFail();
        Storage::disk('public')->assertExists($claim->document_path);

        $this->withSession(['admin_id' => $this->admin->id])
            ->post('/admin/sahiplenme-basvurulari/'.$claim->id.'/onayla')
            ->assertRedirect();

        $this->assertSame('approved', $claim->fresh()->status);
        $this->assertTrue($this->rehabFacility->fresh()->is_claimed);

        $user = FacilityUser::where('email', 'yetkili@test.local')->firstOrFail();

        Mail::assertQueued(FacilityEmailVerificationMail::class, fn ($mail) => $mail->user->email === $user->email);

        $verificationUrl = \Illuminate\Support\Facades\URL::temporarySignedRoute(
            'brand.facility.verify-email',
            now()->addMinutes(60),
            ['brand' => 'bakimevleri', 'id' => $user->id, 'hash' => sha1($user->email)]
        );

        $this->withSession(['facility_user_id' => $user->id])->get($verificationUrl)->assertRedirect();

        $this->assertNotNull($user->fresh()->email_verified_at);

        $this->withSession(['facility_user_id' => $user->id])
            ->post('/site/bakimevleri/kurum-panel/bakiyem', [
                'amount' => 500,
                'receipt' => $this->fakePngUpload('dekont.png'),
            ])
            ->assertRedirect();

        $topup = WalletTopup::firstOrFail();
        Storage::disk('public')->assertExists($topup->receipt_path);

        $this->withSession(['admin_id' => $this->admin->id])
            ->post('/admin/bakiye-yuklemeleri/'.$topup->id.'/onayla')
            ->assertRedirect();

        $this->assertSame('approved', $topup->fresh()->status);
        $this->assertSame(500.0, (float) $this->rehabFacility->fresh()->balance);
        $this->assertGreaterThan(0, BalanceLog::where('facility_id', $this->rehabFacility->id)->count());
    }

    public function test_facility_claim_approval_requires_email_verification_before_panel_access(): void
    {
        Mail::fake();

        $claim = FacilityClaim::create([
            'facility_id' => $this->rehabFacility->id,
            'brand' => 'bakimevleri',
            'applicant_name' => 'Yetkili',
            'applicant_email' => 'dogrulama@test.local',
            'applicant_phone' => '05552222222',
            'document_path' => 'claims/ruhsat.png',
            'status' => 'pending',
        ]);

        $this->withSession(['admin_id' => $this->admin->id])
            ->post('/admin/sahiplenme-basvurulari/'.$claim->id.'/onayla')
            ->assertRedirect();

        $user = FacilityUser::where('email', 'dogrulama@test.local')->firstOrFail();
        $this->assertNull($user->email_verified_at);

        // Admin, kurumun kendi markasiyla ilgisiz bir istekten (admin paneli)
        // onayladigi icin, mailin marka adi/linki mevcut request baglamindan
        // degil DOGRUDAN basvurunun kendi $claim->brand alanindan gelmeli.
        Mail::assertQueued(FacilityEmailVerificationMail::class, function ($mail) {
            return $mail->user->email === 'dogrulama@test.local'
                && $mail->brandName === 'bakimevleri.com'
                && str_contains($mail->verificationUrl, 'bakimevleri');
        });

        Mail::assertQueued(FacilityClaimApprovedMail::class, fn ($mail) => str_contains($mail->loginUrl, 'bakimevleri'));

        $user->update(['password' => Hash::make('Kurum12345!')]);

        $this->post('/site/bakimevleri/kurum-panel/giris', [
            'email' => 'dogrulama@test.local',
            'password' => 'Kurum12345!',
        ])->assertRedirect('/site/bakimevleri/kurum-panel/email-dogrulama');
    }

    public function test_nearby_facilities_use_real_coordinates_when_available(): void
    {
        // Istanbul merkez civari koordinat.
        $this->childFacility->update(['lat' => 41.0082, 'lng' => 28.9784]);
        // Koordinatsiz kurum (rehabFacility) mesafe hesabina hic girmemeli.

        $this->get('/site/bakimeviara/kurumlar?bolum=cocuk&lat=41.01&lng=28.98')
            ->assertOk()
            ->assertSee('Size En Yakın Kurumlar', false)
            ->assertSee($this->childFacility->name);
    }

    public function test_nearby_locate_endpoint_appends_coordinates_when_facilities_have_lat_lng(): void
    {
        $this->childFacility->update(['lat' => 41.0082, 'lng' => 28.9784]);

        $response = $this->postJson('/site/bakimeviara/yakinimdaki-kurumlar', [
            'lat' => 41.01,
            'lng' => 28.98,
        ]);

        $response->assertOk();
        $this->assertTrue($response->json('ok'));
        $this->assertStringContainsString('lat=', $response->json('redirect_url'));
    }

    public function test_search_filters_are_logged_and_aggregated_on_most_searched_page(): void
    {
        $this->get('/site/bakimeviara/kurumlar?bolum=cocuk&city=istanbul&category='.$this->childCategory->slug)->assertOk();
        $this->get('/site/bakimeviara/kurumlar?bolum=cocuk&city=istanbul&category='.$this->childCategory->slug)->assertOk();

        $this->assertSame(1, \App\Models\SearchQuery::count());
        $this->assertSame(2, \App\Models\SearchQuery::first()->count);

        $this->get('/site/bakimeviara/en-cok-aranan-bolgeler')
            ->assertOk()
            ->assertSee('En Çok Aranan Bölgeler')
            ->assertSee($this->city->name)
            ->assertSee('2 arama', false);
    }

    public function test_turkiye_map_is_rendered_on_stats_page(): void
    {
        $this->get('/site/bakimevleri/istatistikler')
            ->assertOk()
            ->assertSee('js-turkiye-harita', false)
            ->assertSee('<g id="istanbul">', false)
            ->assertSee('<g id="izmir"', false);
    }

    public function test_facility_claim_records_applicant_distance_when_location_shared(): void
    {
        Storage::fake('public');

        // rehabFacility'yi Istanbul merkezine, basvuruyu da hemen yakinina koyuyoruz.
        $this->rehabFacility->update(['lat' => 41.0082, 'lng' => 28.9784]);

        $this->post('/site/bakimevleri/kurumlar/'.$this->rehabFacility->slug.'/sahiplen', [
            'applicant_name' => 'Yetkili',
            'applicant_email' => 'konumlu@test.local',
            'applicant_phone' => '05552223344',
            'document' => $this->fakePngUpload('ruhsat2.png'),
            'lat' => 41.01,
            'lng' => 28.98,
        ])->assertRedirect();

        $claim = FacilityClaim::where('applicant_email', 'konumlu@test.local')->firstOrFail();

        $this->assertNotNull($claim->applicant_city_name);
        $this->assertNotNull($claim->distance_km);
        $this->assertLessThan(5, $claim->distance_km);

        $this->withSession(['admin_id' => $this->admin->id])
            ->get('/admin/sahiplenme-basvurulari/'.$claim->id)
            ->assertOk()
            ->assertSee('km');
    }

    public function test_facility_claim_still_succeeds_without_location(): void
    {
        Storage::fake('public');

        $this->post('/site/bakimevleri/kurumlar/'.$this->rehabFacility->slug.'/sahiplen', [
            'applicant_name' => 'Yetkili',
            'applicant_email' => 'konumsuz@test.local',
            'applicant_phone' => '05552223355',
            'document' => $this->fakePngUpload('ruhsat3.png'),
        ])->assertRedirect();

        $claim = FacilityClaim::where('applicant_email', 'konumsuz@test.local')->firstOrFail();
        $this->assertNull($claim->applicant_city_name);
        $this->assertNull($claim->distance_km);
    }

    public function test_whatsapp_click_is_tracked_and_visible_in_admin(): void
    {
        $this->postJson('/site/bakimeviara/whatsapp-tiklama', [
            'page_url' => 'http://localhost:8000/site/bakimeviara/',
            'lat' => 41.01,
            'lng' => 28.98,
        ])->assertOk()->assertJson(['ok' => true]);

        $this->assertSame(1, \App\Models\WhatsappClick::count());
        $click = \App\Models\WhatsappClick::first();
        $this->assertSame('bakimeviara', $click->brand);
        $this->assertNotNull($click->city_name);

        $this->withSession(['admin_id' => $this->admin->id])
            ->get('/admin/whatsapp-tiklamalari')
            ->assertOk()
            ->assertSee('WhatsApp Tıklamaları')
            ->assertSee($click->city_name);
    }

    public function test_admin_can_update_whatsapp_settings_and_button_reflects_them(): void
    {
        $bankFields = [];
        foreach (array_keys(config('brands.brands')) as $slug) {
            $bankFields["bank_name_{$slug}"] = 'Test Banka';
            $bankFields["bank_account_holder_{$slug}"] = 'Test AŞ';
            $bankFields["bank_iban_{$slug}"] = 'TR000000000000000000000000';
        }

        $this->withSession(['admin_id' => $this->admin->id])
            ->put('/admin/ayarlar', array_merge($bankFields, [
                'quote_price' => 250,
                'price_tier_standart_min' => 15000,
                'price_tier_premium_min' => 30000,
                'price_tier_ultra_min' => 50000,
                'whatsapp_number' => '905001234567',
                'whatsapp_message' => 'TESTMESAJI12345 {marka}',
            ]))->assertRedirect();

        $this->assertSame('905001234567', \App\Models\Setting::get('whatsapp_number'));

        $this->get('/site/bakimeviara/')
            ->assertOk()
            ->assertSee('905001234567', false)
            ->assertSee('TESTMESAJI12345', false);
    }

    public function test_pre_registered_facility_full_lifecycle_to_claimed(): void
    {
        Storage::fake('public');
        Mail::fake();

        // 1) Veri cekiciden gelmis gibi bir on kayitli kurum.
        $preRegistered = Facility::create([
            'name' => 'Uskudar Ornek Huzurevi',
            'slug' => 'uskudar-ornek-huzurevi',
            'city_id' => $this->city->id,
            'facility_category_id' => $this->elderlyCategory->id,
            'district' => 'Uskudar',
            'address' => 'Test adres',
            'phone' => '02161112233',
            'description' => 'Google Maps veri cekiciden on kayit.',
            'capacity' => 20,
            'price_min' => null,
            'price_max' => null,
            'services' => ['bakim'],
            'is_published' => true,
            'is_featured' => false,
            'is_claimed' => false,
            'source' => 'google_maps_veri_cekici',
            'free_quote_credits' => 0,
            'balance' => 0,
        ]);

        // 2) Ana sayfada/listede "Ön Kayıtlı" etiketi gorunmeli, "Onaylı" gorunmemeli.
        $listing = $this->get('/site/bakimevibul/kurumlar?bolum=yasli-bakim');
        $listing->assertOk()
            ->assertSee('Uskudar Ornek Huzurevi')
            ->assertSee('Ön Kayıtlı', false);

        // 3) Admin panelinde "Ön Kayıtlı Kurumlar" filtresinde gorunmeli, "Onaylı Kurumlar" filtresinde gorunmemeli.
        $this->withSession(['admin_id' => $this->admin->id])
            ->get('/admin/kurumlar?claim_status=unclaimed')
            ->assertOk()
            ->assertSee('Uskudar Ornek Huzurevi');

        $this->withSession(['admin_id' => $this->admin->id])
            ->get('/admin/kurumlar?claim_status=claimed')
            ->assertOk()
            ->assertDontSee('Uskudar Ornek Huzurevi');

        // 4) Sahiplenme basvurusu formu acilmali (on kayitli oldugu icin 404 vermemeli).
        $this->get('/site/bakimevibul/kurumlar/uskudar-ornek-huzurevi/sahiplen')
            ->assertOk()
            ->assertSee('Sahiplen');

        // 5) Basvuru gonderilir.
        $this->post('/site/bakimevibul/kurumlar/uskudar-ornek-huzurevi/sahiplen', [
            'applicant_name' => 'Yetkili Kisi',
            'applicant_email' => 'yetkili.uskudar@test.local',
            'applicant_phone' => '05551119922',
            'document' => $this->fakePngUpload('ruhsat-uskudar.png'),
        ])->assertRedirect();

        $claim = FacilityClaim::where('applicant_email', 'yetkili.uskudar@test.local')->firstOrFail();
        $this->assertSame('pending', $claim->status);

        // 6) Admin basvuruyu onaylar.
        $this->withSession(['admin_id' => $this->admin->id])
            ->post('/admin/sahiplenme-basvurulari/'.$claim->id.'/onayla')
            ->assertRedirect();

        // 7) Tek seferlik sifre e-postasi kuyruga alinmis olmali.
        Mail::assertQueued(FacilityClaimApprovedMail::class, function ($mail) {
            return $mail->hasTo('yetkili.uskudar@test.local');
        });

        $facilityUser = FacilityUser::where('email', 'yetkili.uskudar@test.local')->firstOrFail();
        $this->assertTrue($facilityUser->must_change_password);

        // 8) Kurum artik sahiplenilmis olmali.
        $preRegistered->refresh();
        $this->assertTrue($preRegistered->is_claimed);
        $this->assertSame('approved', $claim->fresh()->status);

        // 9) Admin panelinde artik "Onaylı Kurumlar" filtresinde gorunmeli, "Ön Kayıtlı" filtresinde gorunmemeli.
        $this->withSession(['admin_id' => $this->admin->id])
            ->get('/admin/kurumlar?claim_status=claimed')
            ->assertOk()
            ->assertSee('Uskudar Ornek Huzurevi')
            ->assertSee('Sahiplenilmiş', false);

        $this->withSession(['admin_id' => $this->admin->id])
            ->get('/admin/kurumlar?claim_status=unclaimed')
            ->assertOk()
            ->assertDontSee('Uskudar Ornek Huzurevi');

        // 10) Ana sayfada/listede artik "Onaylı" etiketi gorunmeli.
        // Not: sayfada her zaman "On Kayitli Kurumlar" filtre linki bulundugu icin
        // sayfa-genelinde assertDontSee('On Kayitli') kullanilmiyor; asil kontrol
        // bu kurumun karti icin dogru ("Onaylı") etiketin gorunmesidir.
        $updatedListing = $this->get('/site/bakimevibul/kurumlar?bolum=yasli-bakim');
        $updatedListing->assertOk()
            ->assertSee('Uskudar Ornek Huzurevi')
            ->assertSee('Onaylı', false);
    }

    public function test_facility_card_shows_google_attribution_for_scraped_rating(): void
    {
        // Veri cekiciden gelen (Google Maps kaynakli) bir puan: kartta "(Google)"
        // etiketiyle gosterilmeli — aksi halde ziyaretci bunun platform
        // yorumlarindan mi yoksa Google'dan mi geldigini ayirt edemez.
        $this->childFacility->update([
            'rating' => 4.6,
            'source' => 'google_maps_veri_cekici',
        ]);

        $this->get('/site/bakimeviara/kurumlar?bolum=cocuk')
            ->assertOk()
            ->assertSee('4.6', false)
            ->assertSee('(Google)', false);

        // Puani olmayan (0) bir kurum icin sahte "★ 0.0" gosterilmemeli.
        $this->rehabFacility->update(['rating' => 0, 'source' => null]);

        $this->get('/site/bakimevleri/kurumlar?bolum=rehabilitasyon')
            ->assertOk()
            ->assertDontSee('★ 0.0', false);
    }

    public function test_approved_facility_card_shows_incele_fiyat_al_karsilastir_toplu_fiyat_al(): void
    {
        $this->get('/site/bakimeviara/kurumlar?bolum=cocuk')
            ->assertOk()
            ->assertSee('İncele')
            ->assertSee('Fiyat Al')
            ->assertSee('Karşılaştır')
            ->assertSee('Toplu Fiyat Al')
            ->assertSee('data-mode="bulk-quote"', false)
            ->assertSee('#teklif-talebi', false);
    }

    public function test_facility_gets_notified_when_new_offer_request_is_created(): void
    {
        $this->withSession(['family_user_id' => $this->family->id])
            ->post('/site/bakimeviara/teklif-talebi', [
                'facility_id' => $this->childFacility->id,
                'full_name' => 'Bildirim Testi',
                'phone' => '05551230000',
            ])->assertRedirect();

        $notification = PlatformNotification::where('notifiable_type', FacilityUser::class)
            ->where('notifiable_id', $this->facilityUser->id)
            ->where('type', 'offer_request')
            ->first();

        $this->assertNotNull($notification);
    }

    public function test_admin_sees_offer_request_form_details_and_quoted_price(): void
    {
        $request = OfferRequest::create($this->offerData('bakimeviara', $this->childCategory, 'Ihtiyac detayi mesaji'));
        $request->update(['patient_name' => 'Ayse Yenge', 'care_for' => 'anne-baba']);

        Quote::create([
            'offer_request_id' => $request->id,
            'facility_id' => $this->childFacility->id,
            'facility_user_id' => $this->facilityUser->id,
            'price' => 15750,
            'price_period' => 'monthly',
            'status' => 'pending',
        ]);

        $response = $this->withSession(['admin_id' => $this->admin->id])->get('/admin/teklif-talepleri');
        $response->assertOk()
            ->assertSee('Ayse Yenge')
            ->assertSee('anne-baba')
            ->assertSee('15.750 TL', false)
            ->assertSee($this->childFacility->name);
    }

    public function test_admin_facilities_index_filters_by_city_district_and_category(): void
    {
        $this->withSession(['admin_id' => $this->admin->id])
            ->get('/admin/kurumlar')
            ->assertOk()
            ->assertSee($this->elderlyFacility->name)
            ->assertSee($this->childFacility->name)
            ->assertSee('name="city"', false)
            ->assertSee('name="district"', false)
            ->assertSee('name="category"', false);

        $this->withSession(['admin_id' => $this->admin->id])
            ->get('/admin/kurumlar?category='.$this->childCategory->slug)
            ->assertOk()
            ->assertSee($this->childFacility->name)
            ->assertDontSee($this->elderlyFacility->name);

        $this->withSession(['admin_id' => $this->admin->id])
            ->get('/admin/kurumlar?city='.$this->city->slug.'&district=Merkez')
            ->assertOk()
            ->assertSee($this->childFacility->name)
            ->assertSee($this->elderlyFacility->name);

        $this->withSession(['admin_id' => $this->admin->id])
            ->get('/admin/kurumlar?city='.$this->city->slug.'&district=Baska-Bir-Ilce')
            ->assertOk()
            ->assertDontSee($this->childFacility->name)
            ->assertDontSee($this->elderlyFacility->name);

        // "On Kayitli Kurumlar" ekraninda ayni filtreler claim_status ile birlikte calisir.
        $this->withSession(['admin_id' => $this->admin->id])
            ->get('/admin/kurumlar?claim_status=unclaimed&category='.$this->rehabCategory->slug)
            ->assertOk()
            ->assertSee($this->rehabFacility->name);
    }

    public function test_bulk_quote_request_creates_shared_batch_and_notifies_each_facility(): void
    {
        $this->post('/site/bakimeviara/toplu-teklif-talebi', [
            'facility_ids' => [$this->childFacility->id, $this->elderlyFacility->id],
            'full_name' => 'Toplu Talep Eden',
            'phone' => '05559998877',
        ])->assertRedirect('/site/bakimeviara/aile/kayit');

        // Giris yapilmamis, talep henuz olusmamis olmali.
        $this->assertSame(0, OfferRequest::where('full_name', 'Toplu Talep Eden')->count());

        $this->post('/site/bakimeviara/aile/kayit', [
            'name' => 'Toplu Talep Eden',
            'email' => 'toplutalep@test.local',
            'phone' => '05559998877',
            'password' => 'Sifre12345!',
            'password_confirmation' => 'Sifre12345!',
            'consent' => '1',
        ])->assertRedirect();

        $requests = OfferRequest::where('full_name', 'Toplu Talep Eden')->get();
        $this->assertCount(2, $requests);
        $this->assertNotNull($requests->first()->batch_id);
        $this->assertSame(1, $requests->pluck('batch_id')->unique()->count());
        $this->assertEqualsCanonicalizing(
            [$this->childFacility->id, $this->elderlyFacility->id],
            $requests->pluck('facility_id')->all()
        );

        $notification = PlatformNotification::where('notifiable_type', FacilityUser::class)
            ->where('notifiable_id', $this->facilityUser->id)
            ->where('type', 'offer_request')
            ->first();
        $this->assertNotNull($notification);
    }

    public function test_bulk_quote_rejects_more_than_five_facilities(): void
    {
        $extra = collect(range(1, 5))->map(fn ($i) => $this->facility('Ekstra Kurum '.$i, $this->childCategory, true));
        $ids = $extra->pluck('id')->push($this->childFacility->id)->all();

        $this->withSession(['family_user_id' => $this->family->id])
            ->post('/site/bakimeviara/toplu-teklif-talebi', [
                'facility_ids' => $ids,
                'full_name' => 'Cok Kurum Secen',
                'phone' => '05550001122',
            ])->assertSessionHasErrors('facility_ids');
    }

    private function facility(string $name, FacilityCategory $category, bool $claimed): Facility
    {
        return Facility::create([
            'name' => $name,
            'slug' => Str::slug($name),
            'city_id' => $this->city->id,
            'facility_category_id' => $category->id,
            'district' => 'Merkez',
            'address' => 'Adres',
            'phone' => '02120000000',
            'description' => 'Aciklama',
            'capacity' => 20,
            'price_min' => 1000,
            'price_max' => 2000,
            'services' => ['bakim'],
            'is_published' => true,
            'is_featured' => false,
            'is_claimed' => $claimed,
            'claimed_at' => $claimed ? now() : null,
            'free_quote_credits' => $claimed ? 5 : 0,
            'balance' => 0,
        ]);
    }

    private function offerData(string $brand, FacilityCategory $category, string $message): array
    {
        return [
            'brand' => $brand,
            'family_user_id' => $this->family->id,
            'city_id' => $this->city->id,
            'facility_category_id' => $category->id,
            'full_name' => $this->family->name,
            'phone' => $this->family->phone,
            'email' => $this->family->email,
            'message' => $message,
            'status' => 'new',
        ];
    }

    public function test_admin_message_review_screen_shows_thread_hidden_from_main_list(): void
    {
        $request = OfferRequest::create($this->offerData('bakimeviara', $this->childCategory, 'Sikayet konusu talep'));

        Message::create([
            'offer_request_id' => $request->id,
            'sender_type' => 'family',
            'sender_id' => $this->family->id,
            'body' => 'Kurumdan hala cevap alamadim.',
        ]);
        Message::create([
            'offer_request_id' => $request->id,
            'sender_type' => 'facility',
            'sender_id' => $this->childFacility->id,
            'body' => 'Merhaba, hemen donus yapiyoruz.',
        ]);

        $this->withSession(['admin_id' => $this->admin->id])
            ->get('/admin/teklif-talepleri')
            ->assertOk()
            ->assertDontSee('Kurumdan hala cevap alamadim.')
            ->assertSee('Şikayet / Mesajları İncele');

        $this->withSession(['admin_id' => $this->admin->id])
            ->get("/admin/teklif-talepleri/{$request->id}/mesajlar")
            ->assertOk()
            ->assertSee('Kurumdan hala cevap alamadim.')
            ->assertSee('Merhaba, hemen donus yapiyoruz.');
    }

    public function test_admin_can_suspend_and_reactivate_family_account_from_complaint_review(): void
    {
        $request = OfferRequest::create($this->offerData('bakimeviara', $this->childCategory, 'Sikayet talebi'));

        $this->assertSame('active', $this->family->fresh()->status);

        $this->withSession(['admin_id' => $this->admin->id])
            ->post("/admin/teklif-talepleri/{$request->id}/aile-durumu")
            ->assertRedirect();

        $this->assertSame('suspended', $this->family->fresh()->status);

        $this->withSession(['family_user_id' => $this->family->id, 'family_user_name' => $this->family->name])
            ->get('/site/bakimeviara/aile/panel')
            ->assertRedirect('/site/bakimeviara/aile/giris');

        $this->post('/site/bakimeviara/aile/giris', [
            'email' => $this->family->email,
            'password' => 'Aile12345!',
        ])->assertSessionHasErrors('email');

        $this->withSession(['admin_id' => $this->admin->id])
            ->post("/admin/teklif-talepleri/{$request->id}/aile-durumu")
            ->assertRedirect();

        $this->assertSame('active', $this->family->fresh()->status);

        $this->post('/site/bakimeviara/aile/giris', [
            'email' => $this->family->email,
            'password' => 'Aile12345!',
        ])->assertRedirect();
    }

    public function test_admin_can_suspend_and_reactivate_facility_account_from_complaint_review(): void
    {
        $request = OfferRequest::create($this->offerData('bakimeviara', $this->childCategory, 'Sikayet talebi'));
        $request->update(['facility_id' => $this->childFacility->id]);

        $this->withSession(['admin_id' => $this->admin->id])
            ->post("/admin/teklif-talepleri/{$request->id}/kurum-durumu")
            ->assertRedirect();

        $this->assertSame('suspended', $this->facilityUser->fresh()->status);

        $this->withSession(['facility_user_id' => $this->facilityUser->id])
            ->get('/site/bakimeviara/kurum-panel/panel')
            ->assertRedirect('/site/bakimeviara/kurum-panel/giris');

        $this->withSession(['admin_id' => $this->admin->id])
            ->post("/admin/teklif-talepleri/{$request->id}/kurum-durumu")
            ->assertRedirect();

        $this->assertSame('active', $this->facilityUser->fresh()->status);
    }
}
