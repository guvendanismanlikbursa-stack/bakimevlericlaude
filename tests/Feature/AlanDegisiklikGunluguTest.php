<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\AdminEvent;
use App\Models\City;
use App\Models\Facility;
use App\Models\FacilityCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

// 26 Kasım 2026: kurum alanı değişince işlem günlüğüne eski → yeni yazılır.
class AlanDegisiklikGunluguTest extends TestCase
{
    use RefreshDatabase;

    private function kurum(): Facility
    {
        $city = City::firstOrCreate(['slug' => 'bursa'], ['name' => 'Bursa']);
        $cat = FacilityCategory::create(['name' => 'Huzurevi', 'slug' => 'huzurevi', 'brand_scope' => 'yasli-bakim']);

        return Facility::create(['name' => 'Deneme Kurum', 'slug' => 'deneme-kurum', 'city_id' => $city->id, 'facility_category_id' => $cat->id, 'district' => 'Nilüfer', 'address' => 'Adres', 'phone' => '02240000000', 'is_published' => true, 'is_claimed' => false, 'services' => ['bakim']]);
    }

    public function test_admin_degisikligi_eski_ve_yeni_degerle_kaydedilir_ve_listede_gorunur(): void
    {
        $admin = Admin::create(['name' => 'Yönetici', 'email' => 'y@test.local', 'password' => Hash::make('Admin12345!'), 'role' => 'superadmin']);
        $f = $this->kurum();
        session(['admin_id' => $admin->id]);

        $f->update(['phone' => '02241111111', 'is_published' => false, 'views_count' => 99]);

        $olay = AdminEvent::where('event_type', 'facility_alan_degisti')->sole();
        $this->assertSame($admin->id, $olay->admin_id);
        $this->assertSame($f->id, $olay->entity_id);
        $this->assertSame(['02240000000', '02241111111'], $olay->detail_json['degisiklikler']['phone']);
        $this->assertArrayHasKey('is_published', $olay->detail_json['degisiklikler']);
        $this->assertArrayNotHasKey('views_count', $olay->detail_json['degisiklikler']);

        $html = $this->withSession(['admin_id' => $admin->id])
            ->get('/admin/islem-gunlugu?entity_type=Facility&entity_id='.$f->id)->assertOk()->getContent();
        $this->assertStringContainsString('Telefon:', $html);
        $this->assertStringContainsString('02241111111', $html);
        $this->assertStringContainsString('Evet', $html);
    }

    public function test_oturum_yokken_ve_sadece_sayac_degisince_kayit_yazilmaz(): void
    {
        $f = $this->kurum();
        $f->update(['phone' => '02242222222']);
        session(['admin_id' => 1]);
        $f->update(['views_count' => 5]);

        $this->assertSame(0, AdminEvent::count());
    }
}
