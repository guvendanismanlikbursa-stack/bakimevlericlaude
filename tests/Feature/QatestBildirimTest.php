<?php

namespace Tests\Feature;

use App\Mail\NotificationMail;
use App\Models\Admin;
use App\Models\PlatformNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

// 4 Kasım 2026: otomatik kontrolün (QATEST) bildirimleri panele yazılır ama e-posta gönderilmez.
class QatestBildirimTest extends TestCase
{
    use RefreshDatabase;

    public function test_qatest_bildirimi_mail_gondermez_gercek_bildirim_gonderir(): void
    {
        Mail::fake();
        $admin = Admin::create(['name' => 'Yönetici', 'email' => 'yonetici@bakimevleri.com', 'password' => bcrypt('x-123456789')]);

        notify_user($admin, 'broker_visit_request', 'Anlaşmalı kurum: Yeni ziyaret', 'QATEST Daily Anlaşmalı Ziyaretçi 123 bir ziyaret talebi gönderdi.');
        Mail::assertNothingSent();

        notify_user($admin, 'broker_visit_request', 'Anlaşmalı kurum: Yeni ziyaret', 'Ayşe Yılmaz bir ziyaret talebi gönderdi.');
        Mail::assertSent(NotificationMail::class, 1);

        $this->assertSame(2, PlatformNotification::count());
    }
}
