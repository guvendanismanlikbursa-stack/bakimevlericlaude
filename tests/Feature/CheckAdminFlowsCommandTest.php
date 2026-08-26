<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\City;
use App\Models\Facility;
use App\Models\FacilityCategory;
use App\Models\PlatformError;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

// 26 Agustos 2026: kullanicinin talebi - "her bolumde ki her ozellik mutlaka
// farkli senaryolarla test edilmeli" (bkz. App\Console\Commands\CheckAdminFlows
// ayni tarihli yorum). Bu dosya iki seyi dogrular: (1) komut normal
// kosullarda hatasiz calisiyor mu, (2) komut GERCEKTEN bir regresyonu
// yakalayabiliyor mu - sadece "hep basarili donuyor" bir kontrol degersizdir,
// bu yuzden bilerek eski (hatali) davranisi taklit eden sahte bir controller
// container'a baglanip komutun bunu GERCEKTEN raporladigi dogrulanir.
class CheckAdminFlowsCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        City::firstOrCreate(['slug' => 'bursa'], ['name' => 'Bursa']);
        FacilityCategory::firstOrCreate(['slug' => 'huzurevi'], ['name' => 'Huzurevi', 'brand_scope' => 'yasli-bakim']);
        Admin::firstOrCreate(['email' => 'admin@test.local'], [
            'name' => 'Admin', 'password' => Hash::make('Admin12345!'), 'role' => 'superadmin',
        ]);
    }

    public function test_check_admin_flows_command_runs_with_no_failures(): void
    {
        $this->artisan('platform:check-admin-flows')->assertSuccessful();

        $this->assertSame(0, PlatformError::where('source', 'daily-admin-flows-check')->count());

        // basarili senaryolar kendi urettikleri test verisini silmis olmali -
        // arkasinda kalici qatest-admin-check-* kurum birakmamali.
        $this->assertSame(0, Facility::where('slug', 'like', 'qatest-admin-check-%')->count());
    }

    public function test_check_admin_flows_detects_a_reintroduced_bonus_stacking_regression(): void
    {
        // Bu haftaki gercek hatanin AYNISINI kasitli olarak geri getiren
        // sahte bir FacilityController - revertToPreRegistered() artik
        // bonusu SIFIRLAMIYOR. Komut bunu yakalamazsa, komutun kendisi
        // guvenilmez demektir.
        $this->app->bind(\App\Http\Controllers\Admin\FacilityController::class, function () {
            return new class extends \App\Http\Controllers\Admin\FacilityController
            {
                public function revertToPreRegistered(Facility $facility)
                {
                    $facility->update(['is_claimed' => false, 'claimed_at' => null]);

                    return back();
                }
            };
        });

        $this->artisan('platform:check-admin-flows')->assertSuccessful();

        $error = PlatformError::where('source', 'daily-admin-flows-check')->first();
        $this->assertNotNull($error, 'Kasitli olarak geri getirilen bonus-birikme hatasi, kontrol tarafindan YAKALANMADI.');
        $this->assertStringContainsString('sıfırlan', $error->message);
    }
}
