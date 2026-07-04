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
use App\Models\Message;
use App\Models\OfferRequest;
use App\Models\Quote;
use App\Models\VisitRequest;
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
            ->assertDontSee('Aile Girişi')
            ->assertDontSee('Kurum Girişi')
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

        $this->get('/site/bakimevleri/favoriler')
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
        $this->post('/site/bakimevleri/kurumlar/'.$this->rehabFacility->slug.'/yorum', [
            'reviewer_name' => 'Ziyaretci',
            'reviewer_phone' => '05554444444',
            'rating' => 5,
            'body' => 'Kurumla gorustuk, bilgi aldik.',
        ])->assertRedirect();

        $review = FacilityReview::firstOrFail();
        $this->assertSame('pending', $review->status);

        $this->post('/site/bakimevleri/kurumlar/'.$this->rehabFacility->slug.'/ziyaret-talebi', [
            'full_name' => 'Ziyaret Talep',
            'phone' => '05555555555',
            'email' => 'ziyaret@test.local',
            'preferred_day' => 'Hafta içi',
            'preferred_time' => 'Sabah',
            'message' => 'Kurum ziyareti istiyoruz.',
        ])->assertRedirect();

        $visit = VisitRequest::firstOrFail();
        $this->assertSame('new', $visit->status);

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

        $this->get('/site/bakimevleri/kurumlar/'.$this->rehabFacility->slug)
            ->assertOk()
            ->assertSee('Veri kalite skoru')
            ->assertSee('Kurum yorumları')
            ->assertSee('Ziyaret / randevu talebi');
    }

    public function test_sitemap_and_profile_quality_surfaces_are_visible(): void
    {
        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertHeader('content-type', 'application/xml; charset=UTF-8')
            ->assertSee('/site/bakimevleri', false)
            // Rehber sayfalari markanin gercek alan adiyla (bakimevleri.com) uretilir,
            // /site/{brand} prefix'i sadece localhost/test erisimi icindir.
            ->assertSee('bakimevleri.com/rehber/rehabilitasyon/istanbul', false)
            ->assertSee('/site/bakimevleri/kurumlar/'.$this->rehabFacility->slug, false);

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
        $this->assertStringStartsWith('facilities/imported/', $image->path);
        Storage::disk('public')->assertExists($image->path);
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
            ->assertSee('10/10 g&ouml;rsel y&uuml;kl&uuml;', false)
            ->assertSee('10 g&ouml;rsel limiti doldu', false);

        $this->get('/site/bakimeviara/kurumlar/'.$this->childFacility->slug)
            ->assertOk()
            ->assertSee('Foto&#287;raf galerisi', false)
            ->assertSee('10/10 g&ouml;rsel', false);
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

        Mail::assertQueued(FacilityEmailVerificationMail::class, fn ($mail) => $mail->user->email === 'dogrulama@test.local');

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

        $this->get('/site/bakimeviara/kurumlar?lat=41.01&lng=28.98')
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
        $this->get('/site/bakimeviara/kurumlar?city=istanbul&category='.$this->childCategory->slug)->assertOk();
        $this->get('/site/bakimeviara/kurumlar?city=istanbul&category='.$this->childCategory->slug)->assertOk();

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
            ->assertSee('data-il="istanbul"', false);
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
        $this->withSession(['admin_id' => $this->admin->id])
            ->put('/admin/ayarlar', [
                'bank_name' => 'Test Banka',
                'bank_account_holder' => 'Test AŞ',
                'bank_iban' => 'TR000000000000000000000000',
                'quote_price' => 50,
                'price_tier_standart_min' => 15000,
                'price_tier_premium_min' => 30000,
                'price_tier_ultra_min' => 50000,
                'whatsapp_number' => '905001234567',
                'whatsapp_message' => 'TESTMESAJI12345 {marka}',
            ])->assertRedirect();

        $this->assertSame('905001234567', \App\Models\Setting::get('whatsapp_number'));

        $this->get('/site/bakimeviara/')
            ->assertOk()
            ->assertSee('905001234567', false)
            ->assertSee('TESTMESAJI12345', false);
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
}
