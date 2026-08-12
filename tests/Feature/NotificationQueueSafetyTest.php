<?php

namespace Tests\Feature;

use App\Mail\NotificationMail;
use App\Models\FamilyUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class NotificationQueueSafetyTest extends TestCase
{
    use RefreshDatabase;

    // 3 Agustos 2026: kullanicinin acik/tekrarlanan talebi geregi ("kuyruga
    // filan asla alma, dogrudan mail yollasin") tum Mail::to(...) cagrilari
    // ->send() yerine ->sendNow() kullanacak sekilde degistirildi - bu da
    // bu testin ORIJINAL endisesini (queue worker'in HTTP-baglamsiz CLI
    // surecinde brand_route() icin gecerli bir actionUrl uretip
    // uretemeyecegi) tamamen ORTADAN KALDIRDI, cunku mail artik HICBIR
    // zaman bir kuyruk isci surecinde islenmiyor - hep senkron, orijinal
    // HTTP request baglaminin icinde gonderiliyor. Test simdi bunun TERSINI,
    // yani "mail kuyruga hic girmiyor" garantisini kalici olarak korur -
    // aksi halde bir sonraki 'ShouldQueue implement eden yeni bir Mailable'
    // veya '->send()'e geri donen bir degisiklik sessizce ayni eski
    // soruna (mailin kuyrukta takili kalmasi) geri doner.
    public function test_notification_mail_is_sent_synchronously_without_ever_touching_the_queue(): void
    {
        Mail::fake();
        config(['queue.default' => 'database']);

        $family = FamilyUser::create([
            'registered_brand' => 'bakimevibul',
            'name' => 'Kuyruk Testi',
            'email' => 'kuyruk-testi@test.local',
            'phone' => '05550000001',
            'password' => Hash::make('Aile12345!'),
        ]);

        notify_user($family, 'topup_approved', 'Test Basligi', 'Test govde metni');

        $this->assertDatabaseCount('jobs', 0);

        Mail::assertSent(NotificationMail::class, function (NotificationMail $mail) use ($family) {
            return $mail->hasTo($family->email)
                && $mail->actionUrl !== null
                && str_contains($mail->actionUrl, 'http');
        });
    }
}
