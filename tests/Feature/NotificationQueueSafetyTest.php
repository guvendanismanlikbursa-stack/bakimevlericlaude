<?php

namespace Tests\Feature;

use App\Models\FamilyUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class NotificationQueueSafetyTest extends TestCase
{
    use RefreshDatabase;

    // 12 Temmuz 2026'da production'da QUEUE_CONNECTION sync'ten database'e
    // gecirildi (kapasite icin - eskiden her bildirim maili, o istegi yapan
    // ziyaretciyi mail gonderilene kadar bekletiyordu). helpers.php'deki
    // notification_action_url() yorumu bir riske isaret ediyordu:
    // brand_route() request()->route('brand')'a bagimli, gercek bir kuyruk
    // isci sureci (CLI, HTTP baglamsiz) icinde calisirsa bos/yanlis URL
    // uretebilirdi. Kod zaten URL'i notify_user() icinde - kuyruga GIRMEDEN
    // once - sabit string olarak hesaplayip Mailable'a tasiyor; bu test
    // bunun "mantiken dogru" olmanin otesinde gercek bir queue:work ile
    // calistigini kalici olarak dogrular.
    public function test_queued_notification_survives_being_processed_by_a_real_worker_outside_http_context(): void
    {
        config(['queue.default' => 'database']);

        $family = FamilyUser::create([
            'registered_brand' => 'bakimevibul',
            'name' => 'Kuyruk Testi',
            'email' => 'kuyruk-testi@test.local',
            'phone' => '05550000001',
            'password' => Hash::make('Aile12345!'),
        ]);

        notify_user($family, 'topup_approved', 'Test Basligi', 'Test govde metni');

        $this->assertDatabaseCount('jobs', 1);

        // notify_user() cagrisi burada (test metodunda) aktif bir HTTP
        // route'u OLMADAN calisiyor - yani brand_route()'un
        // request()->route('brand') donusu zaten null, tipki gercek bir
        // kuyruk iscisinin CLI baglaminda olacagi gibi. Yine de URL'in
        // bos/null degil, gecerli bir string olarak uretilip tasindigini
        // doğrudan is kuyrugundaki (henuz islenmemis) payload'dan
        // dogruluyoruz.
        $payload = json_decode(DB::table('jobs')->value('payload'), true);
        $this->assertStringContainsString('actionUrl', $payload['data']['command']);
        $this->assertStringNotContainsString('"actionUrl";N;', $payload['data']['command']);

        Artisan::call('queue:work', [
            '--stop-when-empty' => true,
            '--no-interaction' => true,
        ]);

        $this->assertDatabaseCount('jobs', 0);
        $this->assertDatabaseCount('failed_jobs', 0);
    }
}
