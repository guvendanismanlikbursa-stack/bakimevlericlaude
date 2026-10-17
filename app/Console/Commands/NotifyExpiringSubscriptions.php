<?php

namespace App\Console\Commands;

use App\Models\Admin;
use App\Models\Facility;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

// 22 Eylul 2026: kullanicinin talebi - abonelik ozelligi. Telefon/WhatsApp
// butonlarinin gizlenmesi (bkz. Facility::isActiveSubscriber()) HER SAYFA
// YUKLEMESINDE canli hesaplandigi icin bu komut GATING icin GEREKLI DEGIL -
// tek amaci, odeme suresi yaklasan/yeni dolan abonelikleri admin'in
// FARK ETMESINI saglamak (yenileme icin kuruma ulasabilsin diye). Spam
// olmasin diye TEK bir ozet mail (CheckAdminFlows/CheckUserFlows ile ayni
// prensip), hicbir abone yoksa mail atilmaz.
class NotifyExpiringSubscriptions extends Command
{
    protected $signature = 'subscriptions:notify-expiring {--days=3 : Bu gun icinde (veya dun) suresi dolacak/dolmus abonelikler bildirilir}';

    protected $description = 'Odeme suresi yaklasan veya yeni dolan abonelikleri admin(ler)e tek bir ozet mailde bildirir';

    public function handle(): int
    {
        $days = (int) $this->option('days');

        $expiringSoon = Facility::where('is_subscribed', true)
            ->whereBetween('subscription_paid_until', [now()->toDateString(), now()->addDays($days)->toDateString()])
            ->orderBy('subscription_paid_until')
            ->get(['id', 'name', 'subscription_plan', 'subscription_paid_until']);

        $justExpired = Facility::where('is_subscribed', true)
            ->whereDate('subscription_paid_until', now()->subDay()->toDateString())
            ->get(['id', 'name', 'subscription_plan', 'subscription_paid_until', 'is_broker_managed']);

        // 17 Ekim 2026: kullanıcının talebi (denetim bulgusu) - süre dolunca
        // kurumun telefon/WhatsApp butonları gizleniyordu ama kurumun kendisine
        // hiçbir uyarı gitmiyordu; sebebini bilmeden müşteri kaybediyordu.
        // Kuruma iki kez haber verilir: bitişe tam 3 gün kala ve bittiğinin
        // ertesi günü (her gün çalışan komutta tekrar tekrar gitmesin diye
        // tarihler tam eşleşmeyle seçilir). Anlaşmalı kurumlar adminlerce
        // yönetildiği için onlara ayrıca gönderilmez (admin özeti yeterli).
        $this->notifyFacilities(
            Facility::where('is_subscribed', true)->where('is_broker_managed', false)
                ->whereDate('subscription_paid_until', now()->addDays(3)->toDateString())->get(),
            'subscription_expiring',
            'Aboneliğiniz 3 gün içinde sona eriyor',
            fn ($f) => 'Aboneliğiniz '.$f->subscription_paid_until->format('d.m.Y').' tarihinde sona erecek. Süre dolduğunda kurum sayfanızdaki telefon ve WhatsApp butonları gizlenir. Yenilemek için bizimle iletişime geçebilirsiniz.'
        );
        $this->notifyFacilities(
            $justExpired->where('is_broker_managed', false),
            'subscription_expired',
            'Aboneliğinizin süresi doldu',
            fn ($f) => 'Aboneliğiniz '.$f->subscription_paid_until->format('d.m.Y').' tarihinde sona erdi. Kurum sayfanızdaki telefon ve WhatsApp butonları şu an gizli. Yeniledikten sonra butonlar otomatik olarak tekrar görünür.'
        );

        if ($expiringSoon->isEmpty() && $justExpired->isEmpty()) {
            $this->info('Bildirilecek abonelik yok.');

            return self::SUCCESS;
        }

        $lines = [];
        if ($justExpired->isNotEmpty()) {
            $lines[] = 'DÜN SÜRESİ DOLDU (telefon/WhatsApp butonları artık gizli):';
            foreach ($justExpired as $f) {
                $lines[] = "- {$f->name} (#{$f->id}) — {$f->subscription_paid_until->format('d.m.Y')}";
            }
            $lines[] = '';
        }
        if ($expiringSoon->isNotEmpty()) {
            $lines[] = "ÖNÜMÜZDEKİ {$days} GÜN İÇİNDE SÜRESİ DOLACAK:";
            foreach ($expiringSoon as $f) {
                $lines[] = "- {$f->name} (#{$f->id}) — {$f->subscription_paid_until->format('d.m.Y')}";
            }
        }

        $body = implode("\n", $lines)."\n\nÖdeme aldıysanız admin panelinde 'Abone Kurumlar' sayfasından 'Ödeme Aldım' ile uzatabilirsiniz.";

        foreach (Admin::all() as $admin) {
            try {
                Mail::to($admin->email)->send(new \App\Mail\SubscriptionExpiryDigestMail($body));
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Abonelik bildirim maili gonderilemedi: '.$e->getMessage(), ['admin_id' => $admin->id]);
            }
        }

        $this->info(($expiringSoon->count() + $justExpired->count()).' abonelik icin ozet mail gonderildi.');

        return self::SUCCESS;
    }

    private function notifyFacilities($facilities, string $type, string $title, \Closure $body): void
    {
        foreach ($facilities as $facility) {
            \App\Models\FacilityUser::where('facility_id', $facility->id)->get()->each(function ($user) use ($facility, $type, $title, $body) {
                try {
                    notify_user($user, $type, $title, $body($facility), ['facility_id' => $facility->id]);
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning('Abonelik kurum bildirimi gonderilemedi: '.$e->getMessage(), ['facility_id' => $facility->id]);
                }
            });
        }
    }
}
