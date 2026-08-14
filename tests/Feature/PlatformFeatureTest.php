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
use App\Mail\FacilityStaffInvitedMail;
use App\Mail\FacilityWelcomeMail;
use App\Mail\FamilyEmailVerificationMail;
use App\Mail\FamilyWelcomeMail;
use App\Mail\NotificationMail;
use App\Models\FacilityDailyStat;
use App\Models\WalletTopup;
use App\Services\FacilityImportImageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
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
            // 3 Agustos 2026: 21 Temmuz 2026'da eklenen blockIfUnverified()/
            // hasVerifiedEmail() korumalari (teklif talebi + teklif kabul +
            // kurum panel girisi) dogrulanmamis bu fixture'i sessizce
            // engelliyordu - offer_requests hic olusmuyor, quote hic kabul
            // edilmiyordu, testler yanlislikla "bildirim/akis calismiyor"
            // gibi gorunuyordu. Gercek kullanicilar dogrulanmis e-postayla
            // panele erisir; fixture da bunu yansitmali.
            'email_verified_at' => now(),
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

    public function test_admin_correct_password_requires_2fa_code_before_dashboard_access(): void
    {
        // 30 Temmuz 2026: kullanici talebiyle 2FA GECICI olarak devre disi
        // birakildi (bkz. Admin\AuthController::login() ayni tarihli yorum) -
        // login() artik dogrudan admin_id set edip /admin'e yonlendiriyor,
        // /admin/giris/dogrula adimina hic ugramiyor. Kod/route'lar
        // dokunulmadan duruyor, 2FA tekrar acildiginda bu test de geri
        // aktif edilmeli.
        $this->markTestSkipped('2FA kullanici talebiyle gecici olarak devre disi (bkz. Admin\\AuthController::login() 30 Temmuz 2026 yorumu).');

        $this->post('/admin/giris', ['email' => 'admin@test.local', 'password' => 'Admin12345!'])
            ->assertRedirect('/admin/giris/dogrula');

        $this->assertNull(session('admin_id'));
        $this->assertNotNull(session('admin_2fa_pending_id'));

        $this->admin->refresh();
        $this->assertNotNull($this->admin->two_factor_code);
        $this->assertNotNull($this->admin->two_factor_expires_at);
    }

    public function test_admin_can_complete_login_with_correct_2fa_code(): void
    {
        $this->markTestSkipped('2FA kullanici talebiyle gecici olarak devre disi (bkz. Admin\\AuthController::login() 30 Temmuz 2026 yorumu).');

        $this->post('/admin/giris', ['email' => 'admin@test.local', 'password' => 'Admin12345!']);
        $this->admin->refresh();

        // Kod hash'lenerek saklandigi icin dogrudan DB'den okunamaz;
        // sendLoginCode() private oldugundan Reflection ile gercek kodu
        // yakalamak yerine, bilinen bir kodu manuel set edip test ediyoruz.
        $this->admin->update(['two_factor_code' => Hash::make('123456'), 'two_factor_expires_at' => now()->addMinutes(10)]);

        $this->post('/admin/giris/dogrula', ['code' => '123456'])
            ->assertRedirect('/admin');

        $this->assertSame($this->admin->id, session('admin_id'));
        $this->assertNull(session('admin_2fa_pending_id'));

        $this->admin->refresh();
        $this->assertNull($this->admin->two_factor_code);
    }

    public function test_admin_2fa_rejects_wrong_code(): void
    {
        $this->markTestSkipped('2FA kullanici talebiyle gecici olarak devre disi (bkz. Admin\\AuthController::login() 30 Temmuz 2026 yorumu).');

        $this->post('/admin/giris', ['email' => 'admin@test.local', 'password' => 'Admin12345!']);
        $this->admin->update(['two_factor_code' => Hash::make('123456'), 'two_factor_expires_at' => now()->addMinutes(10)]);

        $this->post('/admin/giris/dogrula', ['code' => '000000'])
            ->assertSessionHasErrors('code');

        $this->assertNull(session('admin_id'));
    }

    public function test_admin_2fa_rejects_expired_code(): void
    {
        $this->markTestSkipped('2FA kullanici talebiyle gecici olarak devre disi (bkz. Admin\\AuthController::login() 30 Temmuz 2026 yorumu).');

        $this->post('/admin/giris', ['email' => 'admin@test.local', 'password' => 'Admin12345!']);
        $this->admin->update(['two_factor_code' => Hash::make('123456'), 'two_factor_expires_at' => now()->subMinute()]);

        $this->post('/admin/giris/dogrula', ['code' => '123456'])
            ->assertSessionHasErrors('code');

        $this->assertNull(session('admin_id'));
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

    public function test_family_can_view_and_update_profile_page(): void
    {
        // 12 Temmuz 2026'da panel denetiminde bulundu: aile hesabinin kendi
        // bilgilerini (ad/telefon/sifre) duzenleyebilecegi hicbir sayfa yoktu.
        $this->withSession(['family_user_id' => $this->family->id])
            ->get('/site/bakimevibul/aile/profil')
            ->assertOk()
            ->assertSee('Hesap Bilgilerim')
            ->assertSee($this->family->email);

        $this->withSession(['family_user_id' => $this->family->id])
            ->put('/site/bakimevibul/aile/profil', [
                'name' => 'Guncellenmis Isim',
                'phone' => '05559998877',
            ])
            ->assertRedirect();

        $this->family->refresh();
        $this->assertSame('Guncellenmis Isim', $this->family->name);
        $this->assertSame('05559998877', $this->family->phone);
    }

    public function test_family_can_change_password_via_profile_page(): void
    {
        $this->withSession(['family_user_id' => $this->family->id])
            ->put('/site/bakimevibul/aile/profil', [
                'name' => $this->family->name,
                'phone' => $this->family->phone,
                'password' => 'YeniSifre123!',
                'password_confirmation' => 'YeniSifre123!',
            ])
            ->assertRedirect();

        $this->family->refresh();
        $this->assertTrue(Hash::check('YeniSifre123!', $this->family->password));
    }

    public function test_logout_is_reachable_from_a_non_panel_page_while_logged_in(): void
    {
        // 12 Temmuz 2026'da panel denetiminde bulundu: "Cikis Yap" sadece
        // dashboard sayfasinin icindeydi - baska bir sayfaya gecen bir
        // kullanicinin cikis yapmanin yolu yoktu.
        $this->withSession(['family_user_id' => $this->family->id])
            ->get('/site/bakimevibul/kurumlar')
            ->assertOk()
            ->assertSee('Çıkış Yap', false);
    }

    public function test_facility_dashboard_links_to_notifications_and_password_change(): void
    {
        $this->withSession(['facility_user_id' => $this->facilityUser->id])
            ->get('/site/bakimeviara/kurum-panel/panel')
            ->assertOk()
            ->assertSee('/site/bakimeviara/kurum-panel/bildirimler', false)
            ->assertSee('/site/bakimeviara/kurum-panel/sifre-degistir', false);
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

        // 13 Agustos 2026: "data-mode=compare" genel metnini tum sayfada aramak
        // yanlisti - ayni kategoride bulunan sahiplenilmis "Rehab Kurum Onayli"
        // (bkz. rehabFacilityClaimed) "Benzer Kurumlar" bolumunde MESRU sekilde
        // kendi Karsilastir butonuyla goruntuleniyor. Asil kontrol edilmesi
        // gereken, SADECE rehabFacility'nin KENDI butonunun gizli olmasi.
        $this->get('/site/bakimevleri/kurumlar/'.$this->rehabFacility->slug)
            ->assertOk()
            ->assertDontSee('data-mode="compare" data-id="'.$this->rehabFacility->id.'"', false)
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
        // 12 Agustos 2026: kullanicinin talebi - 3 marka artik birbiriyle
        // cakismayan 3 ayri bolume sahip (kopya icerik riskini onlemek
        // icin), bu yuzden sitemap de SADECE markanin kendi bolumunu
        // icerir; bakimevleri artik yasli-bakim'in evi (rehabilitasyon
        // degil) - bkz. config/brands.php.
        $this->get('http://bakimevleri.com/sitemap.xml')
            ->assertOk()
            ->assertHeader('content-type', 'application/xml; charset=UTF-8')
            ->assertSee('bakimevleri.com/rehber/yasli-bakim/istanbul', false)
            ->assertSee('bakimevleri.com/kurumlar/'.$this->elderlyFacility->slug, false)
            ->assertDontSee('bakimevleri.com/rehber/rehabilitasyon/', false)
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
            ->assertSee('Profil kalite puanı')
            ->assertSee('alan tamamlandı');

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

        // 12 Agustos 2026: kullanicinin talebi - 3 marka artik birbiriyle
        // cakismayan 3 ayri varsayilan bolume sahip (bkz. config/brands.php);
        // bakimevleri'nin varsayilani rehabilitasyon'dan yasli-bakim'a
        // tasindi, bu yuzden burada da bolum acikca belirtiliyor.
        $this->get('/site/bakimevleri?bolum=rehabilitasyon')
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
        // Claim belgesi ve dekont 'local' diskine yaziliyor (bkz.
        // Public\FacilityClaimController / Facility\WalletController), 'public'
        // diskine degil - sadece 'public' fake'lemek gercek dosya sistemine
        // yazmaya calisip test ortaminda "dosya bulunamadi" hatasi veriyordu.
        Storage::fake('public');
        Storage::fake('local');
        Mail::fake();

        $this->post('/site/bakimevleri/kurumlar/'.$this->rehabFacility->slug.'/sahiplen', [
            'applicant_name' => 'Yetkili',
            'applicant_email' => 'yetkili@test.local',
            'applicant_phone' => '05552222222',
            'document' => $this->fakePngUpload('ruhsat.png'),
        ])->assertRedirect();

        $claim = FacilityClaim::firstOrFail();
        Storage::disk('local')->assertExists($claim->document_path);

        $this->withSession(['admin_id' => $this->admin->id])
            ->post('/admin/sahiplenme-basvurulari/'.$claim->id.'/onayla')
            ->assertRedirect();

        $this->assertSame('approved', $claim->fresh()->status);
        $this->assertTrue($this->rehabFacility->fresh()->is_claimed);

        $user = FacilityUser::where('email', 'yetkili@test.local')->firstOrFail();

        Mail::assertSent(FacilityEmailVerificationMail::class, fn ($mail) => $mail->user->email === $user->email);

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
        Storage::disk('local')->assertExists($topup->receipt_path);

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
        Mail::assertSent(FacilityEmailVerificationMail::class, function ($mail) {
            return $mail->user->email === 'dogrulama@test.local'
                && $mail->brandName === 'bakimevleri.com'
                && str_contains($mail->verificationUrl, 'bakimevleri');
        });

        Mail::assertSent(FacilityClaimApprovedMail::class, fn ($mail) => str_contains($mail->loginUrl, 'bakimevleri'));

        // must_change_password=true oldugu icin (bkz. FacilityClaimController::
        // approve()), giris sonrasi ONCE sifre degistirme ekranina duser -
        // bkz. Facility\AuthController::login() 30 Temmuz 2026 yorumu:
        // "sifre degistirme, e-posta dogrulamasindan ONCE kontrol edilir".
        $this->assertTrue($user->must_change_password);
        $user->update(['password' => Hash::make('Kurum12345!')]);

        $this->post('/site/bakimevleri/kurum-panel/giris', [
            'email' => 'dogrulama@test.local',
            'password' => 'Kurum12345!',
        ])->assertRedirect('/site/bakimevleri/kurum-panel/sifre-degistir');
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
                'facility_invitation_message' => 'Test davet mesaji {marka}',
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
        Mail::assertSent(FacilityClaimApprovedMail::class, function ($mail) {
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

    public function test_facility_listing_page_does_not_n_plus_one_query(): void
    {
        // 12 Temmuz 2026'da kod incelemesinde bulundu: FacilityController::index()
        // ->with(['city','category','images']) eksikti, facility-card.blade.php
        // (city/category/images'e erisiyor) sayfadaki HER kart icin 3 ayri sorgu
        // tetikliyordu (9 kartlik bir sayfada +27 sorgu). Duzeltildi; bu test
        // sorgu sayisinin kart adediyle BIRLIKTE artmadigini kalici olarak korur.
        for ($i = 0; $i < 9; $i++) {
            $this->facility("N1 Test Kurum {$i}", $this->elderlyCategory, true);
        }

        $queryCount = 0;
        \Illuminate\Support\Facades\DB::listen(function () use (&$queryCount) {
            $queryCount++;
        });

        $this->get('/site/bakimevleri/kurumlar?bolum=yasli-bakim')->assertOk();

        // Eager loading olmadan (city+category+images, 10 kart icin) 30'un
        // uzerinde sorgu olurdu; duzeltmeyle sayfa basina sabit, dusuk bir
        // sorgu sayisinda kalmali - kart adedinden BAGIMSIZ.
        $this->assertLessThan(25, $queryCount, "Beklenenden fazla sorgu ({$queryCount}) - N+1 geri gelmis olabilir.");
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

    public function test_family_registration_sends_verification_and_welcome_mail(): void
    {
        Mail::fake();

        $this->post('/site/bakimevibul/aile/kayit', [
            'name' => 'Yeni Aile',
            'email' => 'yeniaile@test.local',
            'phone' => '05559990000',
            'password' => 'Sifre12345!',
            'password_confirmation' => 'Sifre12345!',
            'consent' => '1',
        ])->assertRedirect();

        $family = FamilyUser::where('email', 'yeniaile@test.local')->firstOrFail();

        Mail::assertSent(FamilyEmailVerificationMail::class, fn ($mail) => $mail->family->is($family));
        Mail::assertSent(FamilyWelcomeMail::class, fn ($mail) => $mail->family->is($family));
    }

    public function test_quote_submission_notifies_family_in_app_and_by_mail(): void
    {
        Mail::fake();

        $request = OfferRequest::create($this->offerData('bakimeviara', $this->childCategory, 'Bildirim Talep'));
        $request->update(['family_user_id' => $this->family->id]);

        $this->withSession(['facility_user_id' => $this->facilityUser->id, 'facility_user_name' => $this->facilityUser->name])
            ->post('/site/bakimeviara/kurum-panel/talep/'.$request->id.'/teklif-ver', [
                'price' => 9500,
                'price_period' => 'monthly',
                'message' => 'Musait yerimiz var.',
            ])
            ->assertRedirect();

        $notification = PlatformNotification::where('notifiable_type', FamilyUser::class)
            ->where('notifiable_id', $this->family->id)
            ->where('type', 'quote_received')
            ->first();
        $this->assertNotNull($notification);

        Mail::assertSent(NotificationMail::class, fn ($mail) => $mail->title === $notification->title);
    }

    public function test_guide_page_content_is_deterministic_and_varies_by_input(): void
    {
        $brand = config('brands.brands.bakimevibul');

        $first = guide_page_content($brand, 'İstanbul', null, 'Huzurevi', 12);
        $second = guide_page_content($brand, 'İstanbul', null, 'Huzurevi', 12);
        $this->assertSame($first['intro'], $second['intro'], 'Ayni girdi her zaman ayni metni uretmeli (deterministik secim).');

        $differentCategory = guide_page_content($brand, 'İstanbul', null, 'Kreş ve Anaokulu', 12);
        $this->assertNotSame($first['intro'], $differentCategory['intro'], 'Farkli kategori en azindan sayaç/kategori metnini degistirmeli.');

        $differentCount = guide_page_content($brand, 'İstanbul', null, 'Huzurevi', 99);
        $this->assertStringContainsString('12', $first['intro']);
        $this->assertStringContainsString('99', $differentCount['intro']);
    }

    public function test_location_guide_renders_dynamic_guide_intro_and_category_description(): void
    {
        $response = $this->get('/site/bakimeviara/rehber/yasli-bakim/'.$this->city->slug.'/kategori/'.$this->elderlyCategory->slug);
        $response->assertOk();

        // guide_page_content() ciktisi (marka sesi + gercek kurum sayisi enjekte
        // edilmis paragraf) artik sayfada olmali - eskiden sadece 9 sabit
        // (marka x bolum) varyanttan biri gorunuyordu, coğrafya/kategoriye gore
        // hic degismiyordu.
        $brand = config('brands.brands.bakimeviara');
        $facilityCount = Facility::published()->where('facility_category_id', $this->elderlyCategory->id)->where('city_id', $this->city->id)->count();
        $expectedIntro = guide_page_content($brand, $this->city->name, null, $this->elderlyCategory->name, $facilityCount)['intro'];

        $response->assertSee($expectedIntro, false);
        $response->assertSee($this->elderlyCategory->seo_description, false);
    }

    public function test_price_guide_supports_city_district_category_route(): void
    {
        // districts_for_city() gercek Turkiye il/ilce listesine (config/turkiye.php)
        // gore calisiyor - test fixture'indaki "Istanbul" (ASCII) bu listede
        // eslesmiyor, bu yuzden gercek yazimla ("İstanbul") ayri bir city
        // olusturup gercek bir ilcesiyle (Adalar) test ediyoruz.
        $realCity = City::firstOrCreate(['slug' => 'istanbul-gercek'], ['name' => 'İstanbul']);
        $this->facility('Adalar Cocuk Merkezi', $this->childCategory, true)->update(['city_id' => $realCity->id, 'district' => 'Adalar']);

        $this->get('/site/bakimeviara/fiyat-rehberi/cocuk/'.$realCity->slug.'/kategori/'.$this->childCategory->slug.'/adalar')
            ->assertOk();
    }

    public function test_guest_can_start_chat_send_message_and_poll_for_reply(): void
    {
        \App\Models\ChatWorkingHour::query()->update(['is_active' => true, 'open_time' => '00:00:00', 'close_time' => '23:59:59']);
        Mail::fake();

        $start = $this->postJson('/site/bakimeviara/destek/baslat', [
            'intent' => 'sohbet',
            'operator_gender_preference' => 'kadin',
            'guest_name' => 'Ayşe',
            'guest_age' => 47,
        ])->assertOk()->json();

        $this->assertNotEmpty($start['guest_token']);
        $this->assertTrue($start['is_online']);
        $threadId = $start['thread_id'];

        // Test istemcisi hep yerel/ozel IP (127.0.0.1) kullandigi icin
        // sehir tespiti burada calismaz (IpGeoLookupService bunu bilerek
        // atlar - bkz. ayri test_ip_geo_lookup_service_* testleri).
        $this->assertDatabaseHas('chat_threads', [
            'id' => $threadId,
            'guest_name' => 'Ayşe',
            'guest_age' => 47,
        ]);

        $send = $this->postJson("/site/bakimeviara/destek/{$threadId}/mesaj", [
            'guest_token' => $start['guest_token'],
            'body' => 'Annem icin huzurevi ariyorum.',
        ])->assertOk()->json();

        $this->assertSame('Annem icin huzurevi ariyorum.', $send['message']['body']);
        $this->assertSame('yasli-bakim', $send['suggested_section']['slug']);

        // Yeni thread acildiginda admin(ler)e bildirim gitmis olmali
        $this->assertDatabaseHas('platform_notifications', [
            'notifiable_type' => \App\Models\Admin::class,
            'notifiable_id' => $this->admin->id,
            'type' => 'chat_message',
        ]);

        // Admin yanitlar
        \App\Models\ChatMessage::create([
            'chat_thread_id' => $threadId, 'sender_type' => 'admin', 'sender_admin_id' => $this->admin->id,
            'body' => 'Merhaba, size nasil yardimci olabilirim?',
        ]);

        $poll = $this->getJson("/site/bakimeviara/destek/{$threadId}/mesajlar?after_id={$send['message']['id']}&guest_token={$start['guest_token']}")
            ->assertOk()->json();

        $this->assertCount(1, $poll['messages']);
        $this->assertSame('admin', $poll['messages'][0]['sender_type']);
    }

    public function test_chat_thread_is_isolated_per_brand(): void
    {
        Http::fake(['ip-api.com/*' => Http::response(['status' => 'fail'])]);
        $start = $this->postJson('/site/bakimeviara/destek/baslat', ['intent' => 'sohbet'])->assertOk()->json();

        // Ayni thread'e baska bir marka uzerinden erisim 403 vermeli.
        $this->postJson("/site/bakimevleri/destek/{$start['thread_id']}/mesaj", [
            'guest_token' => $start['guest_token'],
            'body' => 'Deneme',
        ])->assertForbidden();
    }

    public function test_chat_send_rejects_wrong_guest_token(): void
    {
        Http::fake(['ip-api.com/*' => Http::response(['status' => 'fail'])]);
        $start = $this->postJson('/site/bakimeviara/destek/baslat', ['intent' => 'sohbet'])->assertOk()->json();

        $this->postJson("/site/bakimeviara/destek/{$start['thread_id']}/mesaj", [
            'guest_token' => 'yanlis-token',
            'body' => 'Deneme',
        ])->assertForbidden();
    }

    public function test_chat_shows_offline_message_outside_working_hours(): void
    {
        Http::fake(['ip-api.com/*' => Http::response(['status' => 'fail'])]);
        \App\Models\ChatWorkingHour::query()->update(['is_active' => false]);
        \App\Models\Setting::set('chat_offline_message', 'Test cevrimdisi mesaji.');

        $start = $this->postJson('/site/bakimeviara/destek/baslat', ['intent' => 'sohbet'])->assertOk()->json();

        $this->assertFalse($start['is_online']);
        $this->assertSame('Test cevrimdisi mesaji.', $start['offline_message']);
    }

    public function test_admin_can_view_and_reply_to_chat_thread(): void
    {
        Http::fake(['ip-api.com/*' => Http::response(['status' => 'fail'])]);
        $start = $this->postJson('/site/bakimeviara/destek/baslat', ['intent' => 'temsilci'])->assertOk()->json();
        $this->postJson("/site/bakimeviara/destek/{$start['thread_id']}/mesaj", [
            'guest_token' => $start['guest_token'],
            'body' => 'Yardim lazim.',
        ])->assertOk();

        $this->withSession(['admin_id' => $this->admin->id])
            ->get('/admin/canli-sohbet')
            ->assertOk()
            ->assertSee('Yardim lazim.', false);

        $this->withSession(['admin_id' => $this->admin->id])
            ->post("/admin/canli-sohbet/{$start['thread_id']}/yanitla", ['body' => 'Merhaba, yardimci oluyorum.'])
            ->assertRedirect();

        $this->assertDatabaseHas('chat_messages', [
            'chat_thread_id' => $start['thread_id'],
            'sender_type' => 'admin',
            'sender_admin_id' => $this->admin->id,
            'body' => 'Merhaba, yardimci oluyorum.',
        ]);
    }

    public function test_ip_geo_lookup_service_returns_city_for_public_ip(): void
    {
        Http::fake(['ip-api.com/*' => Http::response(['status' => 'success', 'city' => 'Bursa'])]);

        $service = new \App\Services\IpGeoLookupService();

        $this->assertSame('Bursa', $service->cityFromIp('8.8.8.8'));
    }

    public function test_ip_geo_lookup_service_skips_private_and_local_ips(): void
    {
        $service = new \App\Services\IpGeoLookupService();

        $this->assertNull($service->cityFromIp('127.0.0.1'));
        $this->assertNull($service->cityFromIp('192.168.1.5'));
        $this->assertNull($service->cityFromIp(null));
    }

    public function test_switching_chat_intent_creates_separate_thread_with_own_history(): void
    {
        Http::fake(['ip-api.com/*' => Http::response(['status' => 'fail'])]);
        // Once "sohbet" niyetiyle baslayip mesaj yazar
        $sohbet = $this->postJson('/site/bakimeviara/destek/baslat', ['intent' => 'sohbet'])->assertOk()->json();
        $this->postJson("/site/bakimeviara/destek/{$sohbet['thread_id']}/mesaj", [
            'guest_token' => $sohbet['guest_token'],
            'body' => 'Sohbet mesaji',
        ])->assertOk();

        // Ayni misafir (ayni guest_token) "dertlesme" niyetine gecer
        $dertlesme = $this->postJson('/site/bakimeviara/destek/baslat', [
            'guest_token' => $sohbet['guest_token'],
            'intent' => 'dertlesme',
        ])->assertOk()->json();

        // Farkli bir thread olmali, ve sohbet mesaji burada GORUNMEMELI
        $this->assertNotSame($sohbet['thread_id'], $dertlesme['thread_id']);
        $this->assertSame($sohbet['guest_token'], $dertlesme['guest_token']);
        $this->assertEmpty($dertlesme['messages']);

        $this->postJson("/site/bakimeviara/destek/{$dertlesme['thread_id']}/mesaj", [
            'guest_token' => $dertlesme['guest_token'],
            'body' => 'Dertlesme mesaji',
        ])->assertOk();

        // "sohbet"e geri donunce hala sadece kendi mesaji gorunmeli
        $resumeSohbet = $this->postJson('/site/bakimeviara/destek/baslat', [
            'guest_token' => $sohbet['guest_token'],
            'intent' => 'sohbet',
        ])->assertOk()->json();

        $this->assertSame($sohbet['thread_id'], $resumeSohbet['thread_id']);
        $this->assertCount(1, $resumeSohbet['messages']);
        $this->assertSame('Sohbet mesaji', $resumeSohbet['messages'][0]['body']);
    }

    public function test_admin_sees_sibling_threads_for_same_guest(): void
    {
        Http::fake(['ip-api.com/*' => Http::response(['status' => 'fail'])]);
        $sohbet = $this->postJson('/site/bakimeviara/destek/baslat', ['intent' => 'sohbet'])->assertOk()->json();
        $dertlesme = $this->postJson('/site/bakimeviara/destek/baslat', [
            'guest_token' => $sohbet['guest_token'],
            'intent' => 'dertlesme',
        ])->assertOk()->json();

        $this->withSession(['admin_id' => $this->admin->id])
            ->get("/admin/canli-sohbet/{$sohbet['thread_id']}")
            ->assertOk()
            ->assertSee(route('admin.chat.show', $dertlesme['thread_id']), false);
    }

    public function test_detect_chat_section_matches_keywords_and_ignores_unrelated_text(): void
    {
        $this->assertSame('yasli-bakim', detect_chat_section('Annem icin huzurevi ariyorum')['slug']);
        $this->assertSame('cocuk', detect_chat_section('3 yasindaki cocugum icin kres bakiyorum')['slug']);
        $this->assertSame('rehabilitasyon', detect_chat_section('Felc sonrasi fizik tedavi lazim')['slug']);
        $this->assertNull(detect_chat_section('Merhaba nasilsiniz'));
    }

    public function test_family_google_login_signs_in_existing_account_by_email(): void
    {
        Socialite::fake('google', SocialiteUser::fake([
            'id' => 'g-existing-1',
            'name' => 'Demo Aile',
            'email' => $this->family->email,
            'avatar' => 'https://example.com/avatar.jpg',
        ]));

        $this->get('/site/bakimevibul/aile/google-callback')
            ->assertRedirect('/site/bakimevibul/aile/panel');

        $this->assertSame($this->family->id, session('family_user_id'));
        $this->family->refresh();
        $this->assertSame('g-existing-1', $this->family->google_id);
        $this->assertSame('https://example.com/avatar.jpg', $this->family->avatar_url);
    }

    public function test_family_google_signup_requires_phone_and_consent_then_creates_account(): void
    {
        Socialite::fake('google', SocialiteUser::fake([
            'id' => 'g-new-1',
            'name' => 'Yeni Google Aile',
            'email' => 'yeni-google-aile@test.local',
            'avatar' => 'https://example.com/new-avatar.jpg',
        ]));

        $this->get('/site/bakimevibul/aile/google-callback')
            ->assertRedirect('/site/bakimevibul/aile/google-tamamla');

        $this->assertNull(session('family_user_id'));
        $this->assertIsArray(session('family_google_pending'));

        $this->get('/site/bakimevibul/aile/google-tamamla')->assertOk()->assertSee('Yeni Google Aile');

        $this->post('/site/bakimevibul/aile/google-tamamla', [
            'phone' => '05551234567',
            'consent' => '1',
        ])->assertRedirect('/site/bakimevibul/aile/panel');

        $family = FamilyUser::where('email', 'yeni-google-aile@test.local')->firstOrFail();
        $this->assertSame('g-new-1', $family->google_id);
        $this->assertSame('https://example.com/new-avatar.jpg', $family->avatar_url);
        $this->assertSame('05551234567', $family->phone);
        $this->assertNotNull($family->consent_accepted_at);
        $this->assertNotNull($family->email_verified_at);
        $this->assertSame($family->id, session('family_user_id'));
        $this->assertNull(session('family_google_pending'));
    }

    public function test_family_google_signup_without_consent_is_rejected(): void
    {
        Socialite::fake('google', SocialiteUser::fake([
            'id' => 'g-new-2',
            'name' => 'Rizasiz Aile',
            'email' => 'rizasiz-aile@test.local',
        ]));

        $this->get('/site/bakimevibul/aile/google-callback');

        $this->post('/site/bakimevibul/aile/google-tamamla', [
            'phone' => '05551234567',
        ])->assertSessionHasErrors('consent');

        $this->assertNull(FamilyUser::where('email', 'rizasiz-aile@test.local')->first());
    }

    public function test_facility_google_login_signs_in_existing_active_account_by_email(): void
    {
        Socialite::fake('google', SocialiteUser::fake([
            'id' => 'g-facility-1',
            'name' => 'Demo Kurum',
            'email' => $this->facilityUser->email,
            'avatar' => 'https://example.com/facility-avatar.jpg',
        ]));

        $this->get('/site/bakimevibul/kurum-panel/google-callback')
            ->assertRedirect('/site/bakimevibul/kurum-panel/panel');

        $this->assertSame($this->facilityUser->id, session('facility_user_id'));
        $this->facilityUser->refresh();
        $this->assertSame('g-facility-1', $this->facilityUser->google_id);
        $this->assertSame('https://example.com/facility-avatar.jpg', $this->facilityUser->avatar_url);
    }

    public function test_facility_google_login_rejects_unmatched_email_without_creating_account(): void
    {
        Socialite::fake('google', SocialiteUser::fake([
            'id' => 'g-facility-unknown',
            'name' => 'Bilinmeyen Kurum',
            'email' => 'kayitsiz-kurum@test.local',
        ]));

        $this->get('/site/bakimevibul/kurum-panel/google-callback')
            ->assertRedirect('/site/bakimevibul/kurum-panel/giris');

        $this->assertNull(session('facility_user_id'));
        $this->assertNull(FacilityUser::where('email', 'kayitsiz-kurum@test.local')->first());
    }

    public function test_facility_google_login_rejects_suspended_account(): void
    {
        $this->facilityUser->update(['status' => 'suspended']);

        Socialite::fake('google', SocialiteUser::fake([
            'id' => 'g-facility-suspended',
            'name' => 'Askidaki Kurum',
            'email' => $this->facilityUser->email,
        ]));

        $this->get('/site/bakimevibul/kurum-panel/google-callback')
            ->assertRedirect('/site/bakimevibul/kurum-panel/giris');

        $this->assertNull(session('facility_user_id'));
    }

    public function test_facility_registration_google_prefill_redirects_with_name_and_email(): void
    {
        Socialite::fake('google', SocialiteUser::fake([
            'id' => 'g-facility-1',
            'name' => 'Kurum Yetkilisi',
            'email' => 'yetkili@test.local',
        ]));

        $response = $this->get('/site/bakimevibul/kurum-kaydi/google-callback');
        $response->assertRedirect();
        $this->assertStringContainsString('/site/bakimevibul/kurum-kaydi?', $response->headers->get('Location'));
        $this->assertStringContainsString('applicant_google_name=Kurum', $response->headers->get('Location'));
        $this->assertStringContainsString('applicant_google_email=yetkili%40test.local', $response->headers->get('Location'));
    }

    public function test_health_check_endpoint_reports_ok(): void
    {
        $this->get('/_saglik')
            ->assertOk()
            ->assertJson(['status' => 'ok'])
            ->assertJsonPath('checks.database', 'ok')
            ->assertJsonPath('checks.socialite', 'ok');
    }

    public function test_ops_endpoint_is_disabled_without_secret_configured(): void
    {
        config(['platform.ops_secret' => '']);

        $this->postJson('/_ops/migrate', [], ['Authorization' => 'Bearer anything'])
            ->assertForbidden();
    }

    public function test_ops_endpoint_rejects_wrong_secret(): void
    {
        config(['platform.ops_secret' => 'dogru-sifre']);

        $this->postJson('/_ops/migrate', [], ['Authorization' => 'Bearer yanlis-sifre'])
            ->assertForbidden();
    }

    public function test_ops_endpoint_rejects_unknown_action_even_with_correct_secret(): void
    {
        config(['platform.ops_secret' => 'dogru-sifre']);

        $this->postJson('/_ops/rm-rf', [], ['Authorization' => 'Bearer dogru-sifre'])
            ->assertNotFound();
    }

    public function test_ops_endpoint_runs_cache_refresh_with_correct_secret(): void
    {
        config(['platform.ops_secret' => 'dogru-sifre']);

        $this->postJson('/_ops/cache-refresh', [], ['Authorization' => 'Bearer dogru-sifre'])
            ->assertOk();
    }

    public function test_ops_endpoint_runs_package_discover_with_correct_secret(): void
    {
        config(['platform.ops_secret' => 'dogru-sifre']);

        $this->postJson('/_ops/package-discover', [], ['Authorization' => 'Bearer dogru-sifre'])
            ->assertOk();
    }

    public function test_admin_can_set_ministry_verification_badge_and_it_shows_on_card_and_detail(): void
    {
        $payload = [
            'name' => $this->elderlyFacility->name,
            'city_id' => $this->elderlyFacility->city_id,
            'facility_category_id' => $this->elderlyFacility->facility_category_id,
            'ministry_verification' => 'verified',
            // Gercek admin formunda checkbox onceden isaretli geldigi icin
            // (bkz. form.blade.php @checked(...)) normal bir kaydetmede bu
            // her zaman gonderilir; yoksa update() is_published'i false'a ceker.
            'is_published' => '1',
        ];

        $this->withSession(['admin_id' => $this->admin->id])
            ->put('/admin/kurumlar/'.$this->elderlyFacility->id, $payload)
            ->assertRedirect();

        $this->assertDatabaseHas('facilities', [
            'id' => $this->elderlyFacility->id,
            'ministry_verification' => 'verified',
        ]);

        $this->get('/site/bakimevleri/kurumlar?bolum=yasli-bakim')
            ->assertSee('Bakanlık: Doğrulandı (Özel)');

        $this->get('/site/bakimevleri/kurumlar/'.$this->elderlyFacility->slug)
            ->assertSee('Bakanlık: Doğrulandı (Özel)');
    }

    public function test_family_unread_notification_count_endpoint(): void
    {
        $this->withSession(['family_user_id' => $this->family->id])
            ->get('/site/bakimevibul/aile/bildirimler/sayi')
            ->assertOk()
            ->assertJson(['count' => 0]);

        PlatformNotification::create([
            'notifiable_type' => FamilyUser::class,
            'notifiable_id' => $this->family->id,
            'type' => 'quote_received',
            'title' => 'Yeni teklif',
        ]);

        $this->withSession(['family_user_id' => $this->family->id])
            ->get('/site/bakimevibul/aile/bildirimler/sayi')
            ->assertOk()
            ->assertJson(['count' => 1]);
    }

    public function test_facility_unread_notification_count_endpoint(): void
    {
        $this->withSession(['facility_user_id' => $this->facilityUser->id])
            ->get('/site/bakimevibul/kurum-panel/bildirimler/sayi')
            ->assertOk()
            ->assertJson(['count' => 0]);

        PlatformNotification::create([
            'notifiable_type' => FacilityUser::class,
            'notifiable_id' => $this->facilityUser->id,
            'type' => 'offer_request',
            'title' => 'Yeni talep',
        ]);

        $this->withSession(['facility_user_id' => $this->facilityUser->id])
            ->get('/site/bakimevibul/kurum-panel/bildirimler/sayi')
            ->assertOk()
            ->assertJson(['count' => 1]);
    }

    public function test_family_dashboard_includes_notification_reminder_script_but_public_pages_dont(): void
    {
        $this->withSession(['family_user_id' => $this->family->id])
            ->get('/site/bakimevibul/aile/panel')
            ->assertOk()
            ->assertSee('js-notify-reminder-wrap', false);

        $this->flushSession();

        $this->get('/site/bakimevibul/')
            ->assertOk()
            ->assertDontSee('js-notify-reminder-wrap', false);
    }

    public function test_analytics_scripts_only_render_when_ids_configured(): void
    {
        config(['services.google_analytics.id' => null, 'services.meta_pixel.id' => null]);
        $this->get('/site/bakimevibul/')
            ->assertOk()
            ->assertDontSee('googletagmanager.com/gtag', false)
            ->assertDontSee('fbevents.js', false);

        config(['services.google_analytics.id' => 'G-TEST123', 'services.meta_pixel.id' => '123456789']);
        $this->get('/site/bakimevibul/')
            ->assertOk()
            ->assertSee('googletagmanager.com/gtag/js?id=G-TEST123', false)
            ->assertSee('fbevents.js', false)
            ->assertSee("fbq('init', \"123456789\")", false);
    }

    public function test_ops_endpoint_runs_log_tail_with_correct_secret(): void
    {
        config(['platform.ops_secret' => 'dogru-sifre']);

        $this->postJson('/_ops/log-tail', [], ['Authorization' => 'Bearer dogru-sifre'])
            ->assertOk();
    }

    public function test_ops_endpoint_runs_sentry_test_with_correct_secret(): void
    {
        config(['platform.ops_secret' => 'dogru-sifre']);

        // DSN test ortaminda bos - komut basarisiz donse bile (gonderecek DSN
        // yok) uc nokta cokmemeli, sadece komutun ciktisini dondurmeli.
        $this->postJson('/_ops/sentry-test', [], ['Authorization' => 'Bearer dogru-sifre'])
            ->assertOk();
    }

    public function test_ops_endpoint_reports_queue_status_with_correct_secret(): void
    {
        config(['platform.ops_secret' => 'dogru-sifre']);

        $this->postJson('/_ops/queue-status', [], ['Authorization' => 'Bearer dogru-sifre'])
            ->assertOk()
            ->assertSee('bekleyen=0');
    }

    public function test_ops_endpoint_runs_queue_work_and_processes_pending_jobs(): void
    {
        config(['platform.ops_secret' => 'dogru-sifre', 'queue.default' => 'database']);

        // 3 Agustos 2026: notify_user() artik mail'i sendNow ile senkron
        // gonderdigi icin (kuyruga hic girmiyor) is kuyrugunu doldurmuyor -
        // /_ops/queue-work'un GERCEKTEN bekleyen bir isi isleyip
        // dusurdugunu test etmek icin dogrudan bir dummy is kuyruklaniyor.
        dispatch(function () {
            \Illuminate\Support\Facades\Log::info('queue-work testi: dummy is calisti');
        });
        $this->assertDatabaseCount('jobs', 1);

        $this->postJson('/_ops/queue-work', [], ['Authorization' => 'Bearer dogru-sifre'])
            ->assertOk();

        $this->assertDatabaseCount('jobs', 0);
        $this->assertDatabaseCount('failed_jobs', 0);
    }

    public function test_ops_endpoint_queue_test_dispatches_a_real_queued_job(): void
    {
        config(['platform.ops_secret' => 'dogru-sifre', 'queue.default' => 'database']);

        $this->postJson('/_ops/queue-test', [], ['Authorization' => 'Bearer dogru-sifre'])
            ->assertOk()
            ->assertSee('bekleyen=1');

        $this->assertDatabaseCount('jobs', 1);
    }

    // 12 Agustos 2026: kullanicinin talebi uzerine eklenen yeni ozellikler
    // icin testler (yorum cevaplama, ekip yonetimi, bildirim tercihleri,
    // mesajlasma polling, yorum daveti, gunluk performans anlik goruntusu).

    public function test_facility_owner_can_reply_to_approved_review_and_it_shows_publicly(): void
    {
        $review = FacilityReview::create([
            'facility_id' => $this->childFacility->id,
            'family_user_id' => $this->family->id,
            'brand' => 'bakimeviara',
            'reviewer_name' => $this->family->name,
            'rating' => 5,
            'body' => 'Harika bir kurum.',
            'status' => 'approved',
            'approved_at' => now(),
        ]);

        $this->withSession(['facility_user_id' => $this->facilityUser->id])
            ->get('/site/bakimeviara/kurum-panel/yorumlar')
            ->assertOk()
            ->assertSee('Harika bir kurum.');

        $this->withSession(['facility_user_id' => $this->facilityUser->id])
            ->post('/site/bakimeviara/kurum-panel/yorumlar/'.$review->id.'/cevapla', [
                'facility_reply' => 'Teşekkür ederiz!',
            ])->assertRedirect();

        $this->assertSame('Teşekkür ederiz!', $review->fresh()->facility_reply);
        $this->assertNotNull($review->fresh()->facility_replied_at);

        $this->get('/site/bakimeviara/kurumlar/'.$this->childFacility->slug)
            ->assertOk()
            ->assertSee('Kurum Yanıtı')
            ->assertSee('Teşekkür ederiz!');
    }

    public function test_facility_cannot_reply_to_another_facilitys_review(): void
    {
        $review = FacilityReview::create([
            'facility_id' => $this->elderlyFacility->id,
            'family_user_id' => $this->family->id,
            'brand' => 'bakimevleri',
            'reviewer_name' => $this->family->name,
            'rating' => 4,
            'status' => 'approved',
            'approved_at' => now(),
        ]);

        // $this->facilityUser childFacility'ye ait, elderlyFacility'ye degil.
        $this->withSession(['facility_user_id' => $this->facilityUser->id])
            ->post('/site/bakimevleri/kurum-panel/yorumlar/'.$review->id.'/cevapla', [
                'facility_reply' => 'Yetkisiz cevap',
            ])->assertForbidden();

        $this->assertNull($review->fresh()->facility_reply);
    }

    public function test_facility_owner_can_add_and_remove_staff_member(): void
    {
        Mail::fake();

        $this->assertSame('owner', $this->facilityUser->role);

        $this->withSession(['facility_user_id' => $this->facilityUser->id])
            ->post('/site/bakimeviara/kurum-panel/ekip', [
                'name' => 'Yeni Personel',
                'email' => 'personel@test.local',
            ])->assertRedirect();

        $staff = FacilityUser::where('email', 'personel@test.local')->firstOrFail();
        $this->assertSame('staff', $staff->role);
        $this->assertSame($this->childFacility->id, $staff->facility_id);
        $this->assertTrue($staff->must_change_password);

        Mail::assertSent(FacilityStaffInvitedMail::class, fn ($mail) => $mail->hasTo('personel@test.local'));

        // FacilityUserAuth middleware'i e-posta dogrulanmamis her istegi
        // dogrulama sayfasina yonlendirir (bkz. o middleware) - burada rol
        // bazli yetki mantigini test ettigimiz icin (e-posta dogrulama
        // akisini degil) personeli elle dogrulanmis sayiyoruz.
        $staff->update(['email_verified_at' => now()]);

        // Personel ekip yonetimi ekranina giremez (sadece owner).
        $this->withSession(['facility_user_id' => $staff->id])
            ->get('/site/bakimeviara/kurum-panel/ekip')
            ->assertForbidden();

        // Ama panelin geri kalanina (dashboard) erisebilir.
        $this->withSession(['facility_user_id' => $staff->id])
            ->get('/site/bakimeviara/kurum-panel/panel')
            ->assertOk();

        $this->withSession(['facility_user_id' => $this->facilityUser->id])
            ->delete('/site/bakimeviara/kurum-panel/ekip/'.$staff->id)
            ->assertRedirect();

        $this->assertNull(FacilityUser::find($staff->id));
    }

    public function test_staff_cannot_manage_team(): void
    {
        $staff = FacilityUser::create([
            'facility_id' => $this->childFacility->id,
            'role' => 'staff',
            'name' => 'Personel',
            'email' => 'personel2@test.local',
            'password' => Hash::make('Personel12345!'),
            'must_change_password' => false,
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        $this->withSession(['facility_user_id' => $staff->id])
            ->post('/site/bakimeviara/kurum-panel/ekip', ['name' => 'X', 'email' => 'x@test.local'])
            ->assertForbidden();
    }

    public function test_family_can_disable_email_channel_for_a_notification_type(): void
    {
        Mail::fake();

        $this->withSession(['family_user_id' => $this->family->id])
            ->put('/site/bakimeviara/aile/profil/bildirim-tercihleri', [
                'notifications' => ['quotes' => ['push' => '1']], // email alani gonderilmedi = checkbox isaretsiz
            ])->assertRedirect();

        $this->family->refresh();
        $this->assertFalse($this->family->notification_preferences['quote_received']['email']);
        $this->assertTrue($this->family->notification_preferences['quote_received']['push']);

        $request = OfferRequest::create($this->offerData('bakimeviara', $this->childCategory, 'Tercih Testi'));
        $request->update(['family_user_id' => $this->family->id]);

        $this->withSession(['facility_user_id' => $this->facilityUser->id])
            ->post('/site/bakimeviara/kurum-panel/talep/'.$request->id.'/teklif-ver', [
                'price' => 9000,
                'price_period' => 'monthly',
            ]);

        // Uygulama-ici bildirim yine olusmali, sadece e-posta kanali susturulmali.
        $this->assertDatabaseHas('platform_notifications', [
            'notifiable_type' => FamilyUser::class,
            'notifiable_id' => $this->family->id,
            'type' => 'quote_received',
        ]);
        Mail::assertNotSent(NotificationMail::class);
    }

    public function test_facility_message_poll_returns_only_messages_after_given_id(): void
    {
        $request = OfferRequest::create($this->offerData('bakimeviara', $this->childCategory, 'Poll Testi'));
        $request->update(['facility_id' => $this->childFacility->id]);

        $first = Message::create(['offer_request_id' => $request->id, 'sender_type' => 'family', 'sender_id' => $this->family->id, 'body' => 'Birinci mesaj']);
        $second = Message::create(['offer_request_id' => $request->id, 'sender_type' => 'family', 'sender_id' => $this->family->id, 'body' => 'İkinci mesaj']);

        $response = $this->withSession(['facility_user_id' => $this->facilityUser->id])
            ->getJson('/site/bakimeviara/kurum-panel/talep/'.$request->id.'/mesajlar/yeni?after_id='.$first->id);

        $response->assertOk();
        $this->assertCount(1, $response->json('messages'));
        $this->assertSame('İkinci mesaj', $response->json('messages.0.body'));
        $this->assertSame($second->id, $response->json('messages.0.id'));
    }

    public function test_facility_message_store_returns_json_message_when_ajax_requested(): void
    {
        $request = OfferRequest::create($this->offerData('bakimeviara', $this->childCategory, 'AJAX Testi'));
        $request->update(['facility_id' => $this->childFacility->id]);

        $response = $this->withSession(['facility_user_id' => $this->facilityUser->id])
            ->postJson('/site/bakimeviara/kurum-panel/talep/'.$request->id.'/mesajlar', ['body' => 'Merhaba']);

        $response->assertOk();
        $this->assertSame('Merhaba', $response->json('message.body'));
        $this->assertSame('facility', $response->json('message.sender_type'));
    }

    public function test_review_invitation_command_notifies_eligible_family_only_once(): void
    {
        Mail::fake();

        $request = OfferRequest::create($this->offerData('bakimeviara', $this->childCategory, 'Davet Testi'));
        $quote = Quote::create([
            'offer_request_id' => $request->id,
            'facility_id' => $this->childFacility->id,
            'facility_user_id' => $this->facilityUser->id,
            'price' => 5000,
            'price_period' => 'monthly',
            'status' => 'accepted',
        ]);

        // Eloquent update() 'updated_at'i otomatik simdiki zamana cektigi
        // icin (3 gunluk esigi test edebilmek amaciyla) DB::table ile
        // dogrudan gecmis bir tarih yaziyoruz.
        DB::table('offer_requests')->where('id', $request->id)->update([
            'accepted_quote_id' => $quote->id,
            'family_user_id' => $this->family->id,
            'updated_at' => now()->subDays(5),
        ]);

        $this->artisan('reviews:invite-families')->assertSuccessful();

        $this->assertDatabaseHas('platform_notifications', [
            'notifiable_type' => FamilyUser::class,
            'notifiable_id' => $this->family->id,
            'type' => 'review_invite',
        ]);
        $this->assertNotNull($request->fresh()->review_invited_at);

        $countBefore = PlatformNotification::count();
        $this->artisan('reviews:invite-families');
        $this->assertSame($countBefore, PlatformNotification::count(), 'Ayni talebe ikinci kez davet gitmemeli.');
    }

    public function test_review_invitation_command_skips_family_that_already_reviewed(): void
    {
        $request = OfferRequest::create($this->offerData('bakimeviara', $this->childCategory, 'Zaten Yorumlu'));
        $quote = Quote::create([
            'offer_request_id' => $request->id,
            'facility_id' => $this->childFacility->id,
            'facility_user_id' => $this->facilityUser->id,
            'price' => 5000,
            'price_period' => 'monthly',
            'status' => 'accepted',
        ]);
        DB::table('offer_requests')->where('id', $request->id)->update([
            'accepted_quote_id' => $quote->id,
            'family_user_id' => $this->family->id,
            'updated_at' => now()->subDays(5),
        ]);

        FacilityReview::create([
            'facility_id' => $this->childFacility->id,
            'family_user_id' => $this->family->id,
            'brand' => 'bakimeviara',
            'reviewer_name' => $this->family->name,
            'rating' => 5,
            'status' => 'pending',
        ]);

        $this->artisan('reviews:invite-families');

        $this->assertDatabaseMissing('platform_notifications', [
            'notifiable_type' => FamilyUser::class,
            'notifiable_id' => $this->family->id,
            'type' => 'review_invite',
        ]);
        $this->assertNotNull($request->fresh()->review_invited_at);
    }

    public function test_snapshot_command_records_daily_stats_only_for_claimed_facilities(): void
    {
        $this->childFacility->update(['views_count' => 42, 'favorites_count' => 3]);

        $this->artisan('facility:snapshot-daily-stats')->assertSuccessful();

        // 'date' sutunu sqlite'da tam datetime string olarak saklanabiliyor
        // (ör. "2026-08-12 00:00:00") - assertDatabaseHas'in tam metin
        // eslesmesi yerine model uzerinden (cast'lenmis) dogruluyoruz.
        $stat = FacilityDailyStat::where('facility_id', $this->childFacility->id)
            ->whereDate('date', now()->toDateString())
            ->first();

        $this->assertNotNull($stat);
        $this->assertSame(42, $stat->views_count);
        $this->assertSame(3, $stat->favorites_count);

        $this->assertDatabaseMissing('facility_daily_stats', ['facility_id' => $this->rehabFacility->id]);
    }

    public function test_facility_dashboard_shows_performance_trend_with_lead_value(): void
    {
        FacilityDailyStat::create([
            'facility_id' => $this->childFacility->id,
            'date' => now()->subDays(10)->toDateString(),
            'views_count' => 10,
            'favorites_count' => 0,
            'offer_requests_count' => 1,
            'quotes_sent_count' => 1,
            'quotes_accepted_count' => 0,
        ]);
        FacilityDailyStat::create([
            'facility_id' => $this->childFacility->id,
            'date' => now()->toDateString(),
            'views_count' => 25,
            'favorites_count' => 1,
            'offer_requests_count' => 2,
            'quotes_sent_count' => 2,
            'quotes_accepted_count' => 1,
        ]);

        $request = OfferRequest::create($this->offerData('bakimeviara', $this->childCategory, 'Deger Testi'));
        Quote::create([
            'offer_request_id' => $request->id,
            'facility_id' => $this->childFacility->id,
            'facility_user_id' => $this->facilityUser->id,
            'price' => 7500,
            'price_period' => 'monthly',
            'status' => 'accepted',
        ]);

        $this->withSession(['facility_user_id' => $this->facilityUser->id])
            ->get('/site/bakimeviara/kurum-panel/panel')
            ->assertOk()
            ->assertSee('Performans Trendi')
            ->assertSee('7.500')
            ->assertSee('Bu ay kabul edilen tekliflerin toplam değeri');
    }

    public function test_marking_a_gallery_image_as_viewed_increments_its_counter_and_shows_on_dashboard(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('facilities/gorsel-test.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/p9sAAAAASUVORK5CYII='));
        $image = FacilityImage::create(['facility_id' => $this->childFacility->id, 'path' => 'facilities/gorsel-test.png', 'sort_order' => 0]);

        $this->postJson('/site/bakimeviara/kurumlar/'.$this->childFacility->slug.'/gorsel/'.$image->id.'/goruntulendi')
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->assertSame(1, $image->fresh()->views_count);

        // Baska bir kuruma ait gorsel icin 404 donmeli (kurum-gorsel eslesmesi kontrolu).
        $this->postJson('/site/bakimevleri/kurumlar/'.$this->elderlyFacility->slug.'/gorsel/'.$image->id.'/goruntulendi')
            ->assertNotFound();

        $this->withSession(['facility_user_id' => $this->facilityUser->id])
            ->get('/site/bakimeviara/kurum-panel/panel')
            ->assertOk()
            ->assertSee('En Çok İlgi Gören Görselleriniz')
            ->assertSee('1 görüntülenme');
    }

    public function test_admin_facilities_filter_form_has_no_duplicate_claim_status_field(): void
    {
        $response = $this->withSession(['admin_id' => $this->admin->id])->get('/admin/kurumlar?claim_status=claimed');

        $response->assertOk();
        $this->assertSame(1, substr_count($response->getContent(), 'name="claim_status"'));
    }

    public function test_data_quality_page_detects_and_fixes_issues(): void
    {
        $kresCategory = FacilityCategory::create(['name' => 'Kreş ve Anaokulu', 'slug' => 'kres-ve-anaokulu', 'brand_scope' => 'cocuk-bakim']);
        $miscategorized = $this->facility('Merkez Kreş ve Anaokulu', $this->elderlyCategory, false);
        // 13 Agustos 2026: SQLite'in LOWER() fonksiyonu 'Ö' gibi ASCII-disi
        // Turkce buyuk harfleri kucultemiyor (yalnizca test ortami - gercek
        // MySQL/MariaDB'de sorun yok), bu yuzden aranan kelime kucuk harfle.
        $suspiciousOwnership = Facility::create([
            'name' => 'Şehir özel Bakım Vakfı', 'slug' => 'sehir-ozel-bakim-vakfi', 'city_id' => $this->city->id,
            'facility_category_id' => $this->elderlyCategory->id, 'district' => 'Merkez', 'address' => 'Adres',
            'phone' => '02120000001', 'description' => 'Aciklama', 'capacity' => 10, 'price_min' => 1000, 'price_max' => 2000,
            'services' => ['bakim'], 'is_published' => true, 'is_claimed' => false, 'ownership_type' => 'kamu',
        ]);
        $badName = $this->facility('  Fazla   Boşluklu İsim  ', $this->elderlyCategory, false);

        $response = $this->withSession(['admin_id' => $this->admin->id])->get('/admin/veri-denetimi');
        $response->assertOk()
            ->assertSee('Merkez Kreş ve Anaokulu')
            ->assertSee('Şehir özel Bakım Vakfı')
            ->assertSee('Fazla');

        $this->withSession(['admin_id' => $this->admin->id])
            ->post('/admin/veri-denetimi/kategori-duzelt')
            ->assertRedirect();
        $this->assertSame('kres-ve-anaokulu', $miscategorized->fresh()->category->slug);

        $this->withSession(['admin_id' => $this->admin->id])
            ->post('/admin/veri-denetimi/sahiplik-duzelt', ['id' => $suspiciousOwnership->id, 'type' => 'ozel'])
            ->assertRedirect();
        $this->assertSame('ozel', $suspiciousOwnership->fresh()->ownership_type);

        $this->withSession(['admin_id' => $this->admin->id])
            ->post('/admin/veri-denetimi/isim-duzelt')
            ->assertRedirect();
        $this->assertSame('Fazla Boşluklu İsim', $badName->fresh()->name);
    }

    public function test_claim_without_document_cannot_be_approved_until_document_added(): void
    {
        Storage::fake('local');
        Mail::fake();

        $this->post('/site/bakimevleri/kurumlar/'.$this->rehabFacility->slug.'/sahiplen', [
            'applicant_name' => 'Belgesiz Yetkili',
            'applicant_email' => 'belgesiz@test.local',
            'applicant_phone' => '05553334444',
        ])->assertRedirect();

        $claim = FacilityClaim::firstOrFail();
        $this->assertNull($claim->document_path);
        $this->assertSame('claimed', $this->rehabFacility->fresh()->invitation_status);

        // Belge olmadan onay kesinlikle reddedilmeli (suistimal koruması).
        $this->withSession(['admin_id' => $this->admin->id])
            ->post('/admin/sahiplenme-basvurulari/'.$claim->id.'/onayla')
            ->assertStatus(400);
        $this->assertSame('pending', $claim->fresh()->status);
        $this->assertFalse($this->rehabFacility->fresh()->is_claimed);

        // Admin belgeyi sonradan ekleyebilir, ardindan onay calisir.
        $this->withSession(['admin_id' => $this->admin->id])
            ->post('/admin/sahiplenme-basvurulari/'.$claim->id.'/belge-yukle', [
                'document' => $this->fakePngUpload('sonradan-eklenen.png'),
            ])->assertRedirect();
        $this->assertNotNull($claim->fresh()->document_path);

        $this->withSession(['admin_id' => $this->admin->id])
            ->post('/admin/sahiplenme-basvurulari/'.$claim->id.'/onayla')
            ->assertRedirect();
        $this->assertSame('approved', $claim->fresh()->status);
        $this->assertTrue($this->rehabFacility->fresh()->is_claimed);
    }

    public function test_expire_undocumented_claims_command_deletes_stale_claims_and_reverts_status(): void
    {
        $this->post('/site/bakimevleri/kurumlar/'.$this->rehabFacility->slug.'/sahiplen', [
            'applicant_name' => 'Eski Belgesiz',
            'applicant_email' => 'eski.belgesiz@test.local',
            'applicant_phone' => '05559998877',
        ])->assertRedirect();

        $claim = FacilityClaim::firstOrFail();
        $claim->forceFill(['created_at' => now()->subHours(30)])->save();
        $this->assertSame('claimed', $this->rehabFacility->fresh()->invitation_status);

        $this->artisan('claims:expire-undocumented')->assertSuccessful();

        $this->assertDatabaseMissing('facility_claims', ['id' => $claim->id]);
        // rehabFacility fixture telefonu '02120000000' (sabit hat) - bu yuzden
        // geri donus 'landline_only' olur, 'not_started' degil.
        $this->assertSame('landline_only', $this->rehabFacility->fresh()->invitation_status);
        $this->assertFalse($this->rehabFacility->fresh()->is_claimed);
    }

    public function test_claim_approved_during_active_campaign_grants_featured_status(): void
    {
        Storage::fake('local');
        Mail::fake();

        $this->assertFalse($this->rehabFacility->is_featured);
        $this->assertTrue(facility_featured_campaign_active(), 'Bu test yalnizca kampanya son tarihinden ONCE anlamlidir.');

        $this->post('/site/bakimevleri/kurumlar/'.$this->rehabFacility->slug.'/sahiplen', [
            'applicant_name' => 'Yetkili One Cikan',
            'applicant_email' => 'onecikan@test.local',
            'applicant_phone' => '05557778899',
            'document' => $this->fakePngUpload('ruhsat-onecikan.png'),
        ])->assertRedirect();

        $claim = FacilityClaim::where('applicant_email', 'onecikan@test.local')->firstOrFail();

        $this->withSession(['admin_id' => $this->admin->id])
            ->post('/admin/sahiplenme-basvurulari/'.$claim->id.'/onayla')
            ->assertRedirect();

        $this->assertTrue($this->rehabFacility->fresh()->is_featured);
    }

    // 14 Agustos 2026: kullanicinin talebi - "one cikan" ucretsiz rozeti
    // artik "yil sonu" gibi belirsiz/uzak degil, GERCEK bir son tarihe
    // bagli (bkz. facility_featured_campaign_deadline()). Bu tarihten
    // SONRA onaylanan basvurular otomatik one cikarilmamali.
    public function test_claim_approved_after_campaign_deadline_does_not_grant_featured_status(): void
    {
        Storage::fake('local');
        Mail::fake();

        \Illuminate\Support\Carbon::setTestNow(facility_featured_campaign_deadline()->addDay());

        $this->post('/site/bakimevleri/kurumlar/'.$this->rehabFacility->slug.'/sahiplen', [
            'applicant_name' => 'Yetkili Kampanya Sonrasi',
            'applicant_email' => 'kampanyasonrasi@test.local',
            'applicant_phone' => '05557778800',
            'document' => $this->fakePngUpload('ruhsat-kampanyasonrasi.png'),
        ])->assertRedirect();

        $claim = FacilityClaim::where('applicant_email', 'kampanyasonrasi@test.local')->firstOrFail();

        $this->withSession(['admin_id' => $this->admin->id])
            ->post('/admin/sahiplenme-basvurulari/'.$claim->id.'/onayla')
            ->assertRedirect();

        $this->assertTrue($this->rehabFacility->fresh()->is_claimed);
        $this->assertFalse($this->rehabFacility->fresh()->is_featured);

        \Illuminate\Support\Carbon::setTestNow();
    }

    public function test_featured_facility_card_shows_premium_ribbon(): void
    {
        // 14 Agustos 2026: kullanicinin talebi - "Benzer Kurumlar" artik
        // buyuk facility-card.blade.php degil, kucuk yatay mini-kart
        // kullaniyor (bkz. facilities/show.blade.php) - kurdele yerine
        // kucuk "Öne Çıkan" etiketi gosteriyor. rehabFacilityClaimed'i
        // one cikan yapip AYNI kategorideki rehabFacility'nin sayfasinda
        // "Benzer Kurumlar" icinde gorunmesini test ediyoruz.
        $this->rehabFacilityClaimed->update(['is_featured' => true]);

        $response = $this->get('/site/bakimevleri/kurumlar/'.$this->rehabFacility->slug);
        $response->assertOk()->assertSee('Öne Çıkan');

        // childFacility'nin "Benzer Kurumlar" bolumunde one cikan hicbir
        // kurum yok (farkli kategori) - etiket hic gorunmemeli.
        $response2 = $this->get('/site/bakimevleri/kurumlar/'.$this->elderlyFacility->slug);
        $response2->assertOk()->assertDontSee('Öne Çıkan');
    }

    public function test_featured_facility_own_detail_page_shows_badge(): void
    {
        // 14 Agustos 2026: kullanicinin talebi - "kurum inceleme alaninda
        // bir fark olmadi" sikayeti uzerine, kurumun KENDI detay
        // sayfasinin ust basligina da (facilities/show.blade.php) bir
        // "Öne Çıkan Kurum" rozeti eklendi - onceden bu sayfada is_featured
        // icin hicbir gorsel isaret yoktu.
        $this->rehabFacilityClaimed->update(['is_featured' => true]);

        $this->get('/site/bakimevleri/kurumlar/'.$this->rehabFacilityClaimed->slug)
            ->assertOk()->assertSee('Öne Çıkan Kurum');

        $this->get('/site/bakimevleri/kurumlar/'.$this->rehabFacility->slug)
            ->assertOk()->assertDontSee('Öne Çıkan Kurum');
    }

    public function test_home_featured_section_paginates_six_per_page(): void
    {
        // 14 Agustos 2026: kullanicinin talebi - anasayfadaki "Öne
        // Çıkanlar" bolumu sinirsiz uzamamali, 6'sar sayfalanmali (bkz.
        // HomeController::index() - 'featured_page' parametresi).
        $names = [];
        for ($i = 1; $i <= 7; $i++) {
            $name = "Rehab One Cikan {$i}";
            $names[] = $name;
            $this->facility($name, $this->rehabCategory, true)->update(['is_featured' => true]);
        }

        $page1Body = $this->get('/site/bakimevleri/?bolum=rehabilitasyon')->assertOk()->getContent();
        $onPage1 = collect($names)->filter(fn ($n) => str_contains($page1Body, $n))->count();
        $this->assertSame(6, $onPage1, 'Anasayfada bir sayfada tam 6 one cikan kurum gorunmeli.');

        $page2Body = $this->get('/site/bakimevleri/?bolum=rehabilitasyon&featured_page=2')->assertOk()->getContent();
        $onPage2 = collect($names)->filter(fn ($n) => str_contains($page2Body, $n))->count();
        $this->assertSame(1, $onPage2, 'Kalan 1 one cikan kurum 2. sayfada gorunmeli.');
    }

    // 14 Agustos 2026: kullanicinin bildirdigi canli hata - 3 marka ayni
    // veritabanini paylastigi ve gunluk kontrolu ayni saatte tetikledigi
    // icin baska bir surec ayni "qatest-daily-*" kaydini az once
    // olusturmus olabilir. Bu test, ensureClaimedFacility() bu kaydi
    // ILK kez cagrildiginda (kendi "var mi" kontrolunden ONCE, baska
    // bir domainin surecince olusturulmus gibi) DB'de zaten bulup hata
    // firlatmadan guncelledigini dogrular.
    public function test_check_user_flows_ensure_claimed_facility_handles_row_created_by_another_process(): void
    {
        DB::table('facilities')->insert([
            'name' => 'QATEST Daily Racetest Claimed', 'slug' => 'qatest-daily-racetest-claimed',
            'city_id' => $this->city->id, 'facility_category_id' => $this->elderlyCategory->id,
            'ownership_type' => 'ozel', 'address' => 'Test', 'phone' => '05320000001', 'phone_type' => 'mobile',
            'is_published' => true, 'is_claimed' => false, 'invitation_status' => 'pending',
            'free_quote_credits' => 0, 'balance' => 0, 'source' => 'qa_test',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $command = new \App\Console\Commands\CheckUserFlows();
        $reflection = new \ReflectionClass($command);
        $reflection->getProperty('qaCityId')->setAccessible(true);
        $reflection->getProperty('qaCityId')->setValue($command, $this->city->id);
        $reflection->getProperty('qaCategoryId')->setAccessible(true);
        $reflection->getProperty('qaCategoryId')->setValue($command, $this->elderlyCategory->id);

        $method = $reflection->getMethod('ensureClaimedFacility');
        $method->setAccessible(true);
        $slug = $method->invoke($command, 'racetest');

        $this->assertSame('qatest-daily-racetest-claimed', $slug);
        $this->assertSame(1, DB::table('facilities')->where('slug', $slug)->count());
        $this->assertTrue((bool) DB::table('facilities')->where('slug', $slug)->value('is_claimed'));
    }

    public function test_platform_error_plain_explanation_translates_known_patterns(): void
    {
        $raceError = \App\Models\PlatformError::create([
            'source' => 'exception', 'title' => 'Illuminate\\Database\\UniqueConstraintViolationException — bakimeviara.com',
            'message' => "Hata: Illuminate\\Database\\UniqueConstraintViolationException\nCheckUserFlows.php satirinda olustu",
            'context' => ['exception_class' => 'Illuminate\\Database\\UniqueConstraintViolationException'],
        ]);
        $explanation = $raceError->plainExplanation();
        $this->assertStringContainsString('otomatik test kontrolü', $explanation['summary']);

        $unknownError = \App\Models\PlatformError::create([
            'source' => 'exception', 'title' => 'TumuyleBilinmeyenBirHata — bakimevleri.com',
            'message' => 'Hata: TumuyleBilinmeyenBirHata', 'context' => ['exception_class' => 'TumuyleBilinmeyenBirHata'],
        ]);
        $unknownExplanation = $unknownError->plainExplanation();
        $this->assertStringContainsString('çözemedim', $unknownExplanation['detail']);

        $response = $this->withSession(['admin_id' => $this->admin->id])->get('/admin/hatalar');
        $response->assertOk()
            ->assertSee('otomatik test kontrolü')
            ->assertSee('Sistemde teknik bir hata oluştu')
            ->assertSee('Teknik detay');
    }

    // 14 Agustos 2026: kullanicinin talebi - "hizli yanit veren kurum"
    // rozeti. En az 3 ornek + ortalama 2 saatin (120 dk) altinda yanit
    // sart (bkz. Facility::hasFastResponseBadge(),
    // App\Console\Commands\CalculateFacilityResponseTime).
    public function test_fast_response_badge_shows_after_calculating_response_time(): void
    {
        for ($i = 1; $i <= 3; $i++) {
            $req = OfferRequest::create($this->offerData('bakimevleri', $this->rehabCategory, "Yanit Talebi {$i}"));
            $req->forceFill(['created_at' => now()->subMinutes(200)])->save();

            $quote = Quote::create([
                'offer_request_id' => $req->id,
                'facility_id' => $this->rehabFacilityClaimed->id,
                'facility_user_id' => $this->facilityUser->id,
                'price' => 1000,
                'price_period' => 'monthly',
                'status' => 'pending',
            ]);
            $quote->forceFill(['created_at' => now()->subMinutes(170)])->save(); // 30 dk yanit suresi
        }

        $this->artisan('facility:calculate-response-time')->assertSuccessful();

        $this->rehabFacilityClaimed->refresh();
        $this->assertTrue($this->rehabFacilityClaimed->hasFastResponseBadge());
        $this->assertSame(30, $this->rehabFacilityClaimed->avg_response_minutes);

        $this->get('/site/bakimevleri/kurumlar/'.$this->rehabFacilityClaimed->slug)
            ->assertOk()->assertSee('Hızlı Yanıt');
    }

    public function test_fast_response_badge_hidden_with_insufficient_samples(): void
    {
        // Sadece 1 ornek (min 3 sart) - hizli olsa bile rozet kazanmamali.
        $req = OfferRequest::create($this->offerData('bakimevleri', $this->rehabCategory, 'Tek Yanit Talebi'));
        $req->forceFill(['created_at' => now()->subMinutes(200)])->save();

        $quote = Quote::create([
            'offer_request_id' => $req->id,
            'facility_id' => $this->rehabFacilityClaimed->id,
            'facility_user_id' => $this->facilityUser->id,
            'price' => 1000,
            'price_period' => 'monthly',
            'status' => 'pending',
        ]);
        $quote->forceFill(['created_at' => now()->subMinutes(190)])->save();

        $this->artisan('facility:calculate-response-time')->assertSuccessful();

        $this->rehabFacilityClaimed->refresh();
        $this->assertFalse($this->rehabFacilityClaimed->hasFastResponseBadge());
        $this->assertNull($this->rehabFacilityClaimed->avg_response_minutes);

        $this->get('/site/bakimevleri/kurumlar/'.$this->rehabFacilityClaimed->slug)
            ->assertOk()->assertDontSee('Hızlı Yanıt');
    }

    // 14 Agustos 2026: kullanicinin talebi - "kayitli arama" ozelligi.
    // Aile bir aramayi kaydedip, kriterlere uyan yeni bir kurum
    // eklendiginde bildirim alabilsin (bkz. Family\SavedSearchController,
    // App\Console\Commands\NotifyFamilySavedSearches).
    public function test_family_can_save_and_delete_search(): void
    {
        $this->withSession(['family_user_id' => $this->family->id])
            ->post('/site/bakimevleri/aile/kayitli-aramalar', [
                'section_slug' => 'rehabilitasyon',
                'city' => $this->city->slug,
            ])->assertRedirect();

        $search = \App\Models\FamilySavedSearch::where('family_user_id', $this->family->id)->firstOrFail();
        $this->assertSame($this->city->name, str($search->label)->after(' · ')->toString());

        $this->withSession(['family_user_id' => $this->family->id])
            ->get('/site/bakimevleri/aile/panel')
            ->assertOk()->assertSee($search->label);

        $this->withSession(['family_user_id' => $this->family->id])
            ->delete('/site/bakimevleri/aile/kayitli-aramalar/'.$search->id)
            ->assertRedirect();

        $this->assertDatabaseMissing('family_saved_searches', ['id' => $search->id]);
    }

    public function test_saved_search_notifies_family_of_new_matching_facility(): void
    {
        $checkpoint = now();

        $search = \App\Models\FamilySavedSearch::create([
            'family_user_id' => $this->family->id,
            'brand' => 'bakimevleri',
            'section_slug' => 'rehabilitasyon',
            'filters' => ['city' => $this->city->slug],
            'label' => 'Fizik Tedavi ve Rehabilitasyon · '.$this->city->name,
            'last_checked_at' => $checkpoint,
        ]);

        // last_checked_at'ten ONCE olusturulmus kurum (rehabFacilityClaimed,
        // setUp'ta olusturuldu) bildirime konu OLMAMALI.
        $this->rehabFacilityClaimed->forceFill(['created_at' => $checkpoint->copy()->subDay()])->save();

        // last_checked_at'ten SONRA eklenen kurum bildirime konu OLMALI.
        $newFacility = $this->facility('Yeni Rehab Merkezi', $this->rehabCategory, false);
        $newFacility->forceFill(['created_at' => $checkpoint->copy()->addMinute()])->save();

        $this->artisan('family:notify-saved-searches')->assertSuccessful();

        $this->assertDatabaseHas('platform_notifications', [
            'notifiable_type' => \App\Models\FamilyUser::class,
            'notifiable_id' => $this->family->id,
            'type' => 'saved_search_match',
        ]);

        $notification = \App\Models\PlatformNotification::where('notifiable_id', $this->family->id)
            ->where('type', 'saved_search_match')->firstOrFail();
        $this->assertStringContainsString($newFacility->name, $notification->body);
        $this->assertStringNotContainsString($this->rehabFacilityClaimed->name, $notification->body);

        $search->refresh();
        $this->assertNotNull($search->last_checked_at);
        $this->assertTrue($search->last_checked_at->greaterThan(now()->subMinute()));
    }

    // 14 Agustos 2026: kullanicinin talebi - "kaliteli bir site" icin ozel,
    // markali 404/500/403 sayfalari. 500.blade.php bilerek layouts.brand'i
    // extend ETMIYOR (bkz. o dosyadaki yorum) - bu test, view'in DB'ye hic
    // dokunmadan (herhangi bir Facade/model cagrisi olmadan) render
    // edilebildigini dogrular.
    public function test_custom_error_pages_render_with_brand_styling(): void
    {
        $this->get('/site/bakimevleri/kurumlar/olmayan-bir-kurum-slug-qqzz')
            ->assertNotFound()
            ->assertSee('Aradığınız sayfa bulunamadı')
            ->assertSee('Ana Sayfaya Dön');

        $this->get('/site/bakimeviara/kurumlar/olmayan-bir-kurum-slug-qqzz')
            ->assertNotFound()
            ->assertSee('bakimeviara.com');
    }

    public function test_error_pages_render_standalone_views_directly(): void
    {
        // 500/403 sayfalarini gercek bir HTTP hatasi tetiklemeden, dogrudan
        // view olarak render ederek DB baglantisi olmadan da calistiklarini
        // dogrular (500.blade.php'nin ana amaci budur).
        $this->assertStringContainsString('Sistemde teknik bir hata oluştu', view('errors.500')->render());
        $this->assertStringContainsString('Bu sayfaya erişim yetkiniz yok', view('errors.403')->render());
    }
}
