<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\City;
use App\Models\Facility;
use App\Models\FacilityCategory;
use App\Models\FacilityMarketingNote;
use App\Models\FacilityUser;
use App\Models\Marketer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

// 27 Eylul 2026: kullanicinin talebi - pazarlamaci CRM'i. Admin/kurum/aile
// oturumlarindan bagimsiz, telefon+sifre ile giren, SADECE CRM'e erisebilen,
// kurum SILEMEYEN (sadece not ekleyebilen) dar yetkili bir rol.
class MarketerCrmTest extends TestCase
{
    use RefreshDatabase;

    private City $city;

    private FacilityCategory $category;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->city = City::firstOrCreate(['slug' => 'bursa'], ['name' => 'Bursa']);
        $this->category = FacilityCategory::firstOrCreate(['slug' => 'huzurevi'], ['name' => 'Huzurevi', 'brand_scope' => 'yasli-bakim']);
        $this->admin = Admin::firstOrCreate(['email' => 'admin@test.local'], [
            'name' => 'Admin', 'password' => Hash::make('Admin12345!'), 'role' => 'superadmin',
        ]);
    }

    private function facility(string $name, array $over = []): Facility
    {
        return Facility::create(array_merge([
            'name' => $name,
            'slug' => \Illuminate\Support\Str::slug($name),
            'city_id' => $this->city->id,
            'facility_category_id' => $this->category->id,
            'district' => 'Merkez',
            'address' => 'Adres',
            'phone' => '05321234567',
            'phone_type' => 'mobile',
            'description' => 'Açıklama',
            'is_published' => true,
            'is_claimed' => false,
            'is_broker_managed' => false,
            'is_subscribed' => false,
        ], $over));
    }

    private function marketer(string $phone = '05559998877'): Marketer
    {
        return Marketer::create([
            'name' => 'Pazarlamacı Ayşe', 'phone' => $phone, 'password' => Hash::make('Pazar12345!'), 'is_active' => true,
        ]);
    }

    public function test_marketer_can_login_with_various_phone_formats_and_reach_crm(): void
    {
        $this->marketer('0532 111 22 33');
        $this->facility('CRM Kurum 1');

        foreach (['0532 111 22 33', '5321112233', '+90 532 111 22 33', '90 532 111 22 33'] as $phoneInput) {
            $this->post('/pazarlamaci/giris', ['phone' => $phoneInput, 'password' => 'Pazar12345!'])
                ->assertRedirect(route('marketer.crm.index'));

            $this->get('/pazarlamaci/crm')->assertOk()->assertSee('CRM Kurum 1');
            $this->post('/pazarlamaci/cikis');
        }
    }

    public function test_marketer_login_fails_with_wrong_password_or_inactive_account(): void
    {
        $m = $this->marketer();

        $this->post('/pazarlamaci/giris', ['phone' => $m->phone, 'password' => 'yanlis'])
            ->assertSessionHasErrors('phone');

        $m->update(['is_active' => false]);
        $this->post('/pazarlamaci/giris', ['phone' => $m->phone, 'password' => 'Pazar12345!'])
            ->assertSessionHasErrors('phone');
    }

    public function test_crm_is_inaccessible_without_login_and_marketer_cannot_reach_admin_panel(): void
    {
        $this->get('/pazarlamaci/crm')->assertRedirect(route('marketer.login'));

        $m = $this->marketer();
        $this->post('/pazarlamaci/giris', ['phone' => $m->phone, 'password' => 'Pazar12345!']);

        // Pazarlamaci oturumuyla admin paneline girilemez - admin.auth
        // middleware'i sadece 'admin_id' session anahtarina bakar, marketer_id'yi
        // tanimaz, giris ekranina yönlendirir.
        $this->get('/admin/kurumlar')->assertRedirect(route('admin.login'));
    }

    public function test_crm_list_only_shows_eligible_facilities_with_phone(): void
    {
        $m = $this->marketer();
        $this->post('/pazarlamaci/giris', ['phone' => $m->phone, 'password' => 'Pazar12345!']);

        $eligible = $this->facility('Uygun Kurum');
        $claimed = $this->facility('Sahiplenilmiş Kurum', ['is_claimed' => true]);
        $broker = $this->facility('Anlaşmalı Kurum', ['is_broker_managed' => true]);
        $subscribed = $this->facility('Abone Kurum', ['is_subscribed' => true]);
        $noPhone = $this->facility('Telefonsuz Kurum', ['phone' => null, 'phone_type' => 'none']);

        $html = $this->get('/pazarlamaci/crm')->assertOk()->getContent();

        $this->assertStringContainsString('Uygun Kurum', $html);
        foreach (['Sahiplenilmiş Kurum', 'Anlaşmalı Kurum', 'Abone Kurum', 'Telefonsuz Kurum'] as $bad) {
            $this->assertStringNotContainsString($bad, $html, "CRM listesinde görünmemesi gereken kurum göründü: {$bad}");
        }
    }

    public function test_marketer_can_add_note_and_brochure_flag_but_cannot_delete_facility(): void
    {
        $m = $this->marketer();
        $this->post('/pazarlamaci/giris', ['phone' => $m->phone, 'password' => 'Pazar12345!']);
        $facility = $this->facility('Not Alınacak Kurum');

        $this->post("/pazarlamaci/crm/{$facility->id}/not", [
            'status' => 'interested', 'note' => 'İlgileniyor, dekont bekleniyor.', 'brochure_requested' => '1',
        ])->assertRedirect();

        $facility->refresh();
        $this->assertSame('interested', $facility->marketing_status);
        $this->assertTrue($facility->marketing_brochure_requested);
        $this->assertSame(1, FacilityMarketingNote::where('facility_id', $facility->id)->count());
        $note = FacilityMarketingNote::first();
        $this->assertSame($m->id, $note->marketer_id);
        $this->assertSame('İlgileniyor, dekont bekleniyor.', $note->note);

        // Pazarlamacinin kuruma erisiminde bir "sil" rotasi hic yok -
        // olmayan bir route'a POST/DELETE atmak 404 doner, silme imkani yok.
        $this->assertFalse(\Illuminate\Support\Facades\Route::has('marketer.crm.destroy'));
        $this->assertDatabaseHas('facilities', ['id' => $facility->id, 'deleted_at' => null]);
    }

    public function test_marketer_can_set_thinking_and_subscribed_statuses(): void
    {
        $m = $this->marketer();
        $this->post('/pazarlamaci/giris', ['phone' => $m->phone, 'password' => 'Pazar12345!']);
        $facility = $this->facility('Düşünecek Kurum');

        $this->post("/pazarlamaci/crm/{$facility->id}/not", ['status' => 'thinking'])->assertRedirect();
        $this->assertSame('thinking', $facility->fresh()->marketing_status);

        $this->post("/pazarlamaci/crm/{$facility->id}/not", ['status' => 'subscribed'])->assertRedirect();
        $this->assertSame('subscribed', $facility->fresh()->marketing_status);
    }

    public function test_marketer_can_schedule_a_callback_and_it_is_not_cleared_by_a_later_note_without_one(): void
    {
        $m = $this->marketer();
        $this->post('/pazarlamaci/giris', ['phone' => $m->phone, 'password' => 'Pazar12345!']);

        $soon = $this->facility('Yakın Randevu Kurumu');
        $later = $this->facility('Uzak Randevu Kurumu');
        $noCallback = $this->facility('Randevusuz Kurum');

        $this->post("/pazarlamaci/crm/{$later->id}/not", [
            'status' => 'called', 'callback_at' => now()->addDays(5)->format('Y-m-d\TH:i'),
        ])->assertRedirect();
        $this->post("/pazarlamaci/crm/{$soon->id}/not", [
            'status' => 'called', 'callback_at' => now()->addDay()->format('Y-m-d\TH:i'),
        ])->assertRedirect();
        $this->post("/pazarlamaci/crm/{$noCallback->id}/not", ['status' => 'called'])->assertRedirect();

        $this->assertNotNull($soon->fresh()->marketing_callback_at);
        $this->assertNull($noCallback->fresh()->marketing_callback_at);

        // callback sekmesi sadece randevulu kurumlari, EN YAKIN once olacak sekilde listeler.
        $html = $this->get('/pazarlamaci/crm?group=callback')->assertOk()->getContent();
        $this->assertStringContainsString('Yakın Randevu Kurumu', $html);
        $this->assertStringContainsString('Uzak Randevu Kurumu', $html);
        $this->assertStringNotContainsString('Randevusuz Kurum', $html);
        $this->assertLessThan(strpos($html, 'Uzak Randevu Kurumu'), strpos($html, 'Yakın Randevu Kurumu'));

        // Randevu var iken, callback_at girilmeden yeni bir not eklenirse
        // mevcut randevu SILINMEMELI (yanlislikla kaybolmasin).
        $this->post("/pazarlamaci/crm/{$soon->id}/not", ['status' => 'interested'])->assertRedirect();
        $this->assertNotNull($soon->fresh()->marketing_callback_at);
    }

    public function test_marketer_can_notify_admin_and_admin_sees_all_marketer_conversations(): void
    {
        Mail::fake();
        $m1 = $this->marketer('05551110001');
        $m2 = Marketer::create(['name' => 'Pazarlamacı Bora', 'phone' => '05551110002', 'password' => Hash::make('Pazar12345!'), 'is_active' => true]);

        $this->post('/pazarlamaci/giris', ['phone' => $m1->phone, 'password' => 'Pazar12345!']);
        $facility = $this->facility('Bildirim Kurumu');
        $this->post("/pazarlamaci/crm/{$facility->id}/not", ['status' => 'interested', 'note' => 'Yarın tekrar aranacak.']);

        // Ayni notu/durumu diger pazarlamaci ANINDA gorur - notlar kurumun
        // KENDISINDE tutulur, pazarlamaci bazinda AYRI bir silo YOKTUR.
        $this->post('/pazarlamaci/cikis');
        $this->post('/pazarlamaci/giris', ['phone' => $m2->phone, 'password' => 'Pazar12345!']);
        $html = $this->get('/pazarlamaci/crm?group=interested')->assertOk()->getContent();
        $this->assertStringContainsString('Bildirim Kurumu', $html);
        $this->assertStringContainsString('Yarın tekrar aranacak.', $html);

        $this->post("/pazarlamaci/crm/{$facility->id}/admine-bildir")->assertRedirect();
        $this->assertDatabaseHas('platform_notifications', [
            'type' => 'marketer_facility_forwarded', 'notifiable_type' => \App\Models\Admin::class, 'notifiable_id' => $this->admin->id,
        ]);

        // Admin bunu panelin ICINDE de gormeli (sadece mail degil), okundu
        // isaretleyip kuruma tiklayabilmeli.
        $this->post('/admin/giris', ['email' => 'admin@test.local', 'password' => 'Admin12345!']);
        $notifHtml = $this->get('/admin/bildirimler')->assertOk()->getContent();
        $this->assertStringContainsString('Bildirim Kurumu', $notifHtml);
        $notification = \App\Models\PlatformNotification::where('type', 'marketer_facility_forwarded')->firstOrFail();
        $this->assertNull($notification->read_at);
        $this->post("/admin/bildirimler/{$notification->id}/okundu")->assertRedirect();
        $this->assertNotNull($notification->fresh()->read_at);

        // Admin, hangi pazarlamacinin hangi kurumla ne konustugunu gorebilir.
        $this->post('/admin/giris', ['email' => 'admin@test.local', 'password' => 'Admin12345!']);
        $adminHtml = $this->get('/admin/pazarlamaci-gorusmeleri')->assertOk()->getContent();
        $this->assertStringContainsString('Bildirim Kurumu', $adminHtml);
        $this->assertStringContainsString('Pazarlamacı Ayşe', $adminHtml);

        $filtered = $this->get('/admin/pazarlamaci-gorusmeleri?marketer='.$m2->id)->assertOk()->getContent();
        $this->assertStringNotContainsString('Bildirim Kurumu', $filtered);
    }

    public function test_marketer_manifest_is_scoped_to_marketer_area(): void
    {
        $json = json_decode($this->get('/pazarlamaci/manifest.json')->assertOk()->getContent(), true);
        $this->assertStringContainsString('/pazarlamaci', $json['scope']);
        $this->assertSame(route('marketer.login'), $json['start_url']);
    }

    public function test_admin_can_create_toggle_and_reset_marketer_password(): void
    {
        $this->post('/admin/giris', ['email' => 'admin@test.local', 'password' => 'Admin12345!']);

        $this->post('/admin/pazarlamacilar', ['name' => 'Yeni Pazarlamacı', 'phone' => '05551112233'])
            ->assertRedirect();

        $marketer = Marketer::where('phone', '05551112233')->firstOrFail();
        $this->assertTrue($marketer->is_active);

        $this->post("/admin/pazarlamacilar/{$marketer->id}/durum")->assertRedirect();
        $this->assertFalse($marketer->fresh()->is_active);

        $oldHash = $marketer->fresh()->password;
        $this->post("/admin/pazarlamacilar/{$marketer->id}/sifre-sifirla")->assertRedirect();
        $this->assertNotSame($oldHash, $marketer->fresh()->password);
    }

    public function test_duplicate_marketer_phone_is_rejected(): void
    {
        $this->post('/admin/giris', ['email' => 'admin@test.local', 'password' => 'Admin12345!']);
        $this->marketer('05551112233');

        $this->post('/admin/pazarlamacilar', ['name' => 'İkinci', 'phone' => '0555 111 22 33'])
            ->assertSessionHasErrors('phone');
    }

    public function test_facility_user_can_login_with_email_or_phone(): void
    {
        $facility = $this->facility('Giriş Testi Kurumu');
        FacilityUser::create([
            'facility_id' => $facility->id, 'name' => 'Yetkili', 'email' => 'yetkili@test.local',
            'phone' => '0533 444 55 66', 'password' => Hash::make('Kurum12345!'),
            'must_change_password' => false, 'status' => 'active', 'email_verified_at' => now(),
        ]);

        $this->post('/site/bakimevleri/kurum-panel/giris', ['login' => 'yetkili@test.local', 'password' => 'Kurum12345!'])
            ->assertRedirect('/site/bakimevleri/kurum-panel/panel');
        $this->post('/site/bakimevleri/kurum-panel/cikis');

        $this->post('/site/bakimevleri/kurum-panel/giris', ['login' => '05334445566', 'password' => 'Kurum12345!'])
            ->assertRedirect('/site/bakimevleri/kurum-panel/panel');
    }

    public function test_admin_subscribe_with_panel_account_creates_account_and_sets_subscription(): void
    {
        Mail::fake();
        $this->post('/admin/giris', ['email' => 'admin@test.local', 'password' => 'Admin12345!']);
        $facility = $this->facility('CRM Abone Kurumu');

        $this->post("/admin/kurumlar/{$facility->id}/abone-yap-ve-panel-ac", [
            'plan' => 'quarterly', 'amount' => 4000,
            'applicant_name' => 'Yetkili Kişi', 'applicant_email' => 'crm-abone@test.local', 'applicant_phone' => '05551234567',
        ])->assertRedirect();

        $facility->refresh();
        $this->assertTrue($facility->is_subscribed);
        $this->assertSame('quarterly', $facility->subscription_plan);
        $this->assertFalse($facility->is_claimed);
        $user = FacilityUser::where('email', 'crm-abone@test.local')->first();
        $this->assertNotNull($user);
        $this->assertSame($facility->id, $user->facility_id);
        $this->assertTrue($user->must_change_password);
    }

    public function test_subscribe_with_panel_account_refuses_when_account_already_exists(): void
    {
        $this->post('/admin/giris', ['email' => 'admin@test.local', 'password' => 'Admin12345!']);
        $facility = $this->facility('Zaten Hesaplı Kurum');
        FacilityUser::create([
            'facility_id' => $facility->id, 'name' => 'Mevcut', 'email' => 'mevcut@test.local',
            'phone' => '05550001122', 'password' => Hash::make('x'), 'status' => 'active',
        ]);

        $this->post("/admin/kurumlar/{$facility->id}/abone-yap-ve-panel-ac", [
            'plan' => 'monthly', 'applicant_name' => 'X', 'applicant_email' => 'yeni@test.local', 'applicant_phone' => '05559998877',
        ])->assertStatus(400);
    }
}
