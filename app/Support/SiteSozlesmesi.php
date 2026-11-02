<?php

namespace App\Support;

use App\Http\Controllers\Public\HekimPaketController;
use App\Models\Setting;

/**
 * 2 Kasım 2026: kullanıcının talebi - site satışında WhatsApp'tan gönderilen kısa hizmet
 * sözleşmesi. Yenileme ücreti tutarı yazılmaz (gelecek yılın ücretleri belirsiz).
 */
class SiteSozlesmesi
{
    /** Paket adı => varsayılan ilk yıl ücreti (yalnızca rakam; admin değiştirebilir). */
    public static function paketler(bool $doktor): array
    {
        $rakam = fn ($s) => trim(preg_replace('/[^0-9.,]/', '', (string) $s), '.,');
        if ($doktor) {
            return collect(HekimPaketController::PAKETLER)
                ->mapWithKeys(fn ($p, $k) => [$p['ad'].' Site' => $rakam(HekimPaketController::fiyat($k))])->all();
        }

        return [
            'Standart Site' => '',
            'Profesyonel Site' => $rakam(Setting::get('kurum_sitesi_pro_fiyat', '14.900 ₺ / yıl')),
        ];
    }

    public static function metin(string $ad, string $paket, string $ucret): string
    {
        return "📄 *Web Sitesi Hizmet Koşulları*\n\n"
            ."Sayın {$ad},\n"
            ."{$paket} hizmetiniz için koşullarımız:\n\n"
            ."1️⃣ *Ücret:* İlk yıl {$ucret} ₺ (KDV dahil). Tasarım, yayın, alan adı, SSL güvenlik sertifikası ve teknik bakım dahildir.\n"
            ."2️⃣ *Yenileme:* Her yılın sonunda alan adı, SSL ve teknik bakım için yıllık yenileme ücreti alınır.\n"
            ."3️⃣ *Yenilenmezse:* Süre bitince site yayını durdurulur ve alan adınız yenilenmez.\n"
            ."4️⃣ *Alan adı:* Yenilenmeyen alan adının ne zaman tekrar satışa çıkacağı, alan adını satan firmanın kurallarına bağlıdır. Firmamızın bu süreçte bir yetkisi veya sorumluluğu yoktur.\n"
            ."5️⃣ *Fatura:* Ücret hizmet bedeli olarak faturalandırılır ve WhatsApp hattınıza gönderilir. Fatura için vergi dairesi, vergi numarası ve açık adresinizi lütfen iletiniz.\n"
            ."6️⃣ *Onay:* Bu mesajı aldıktan sonra koşulları okuyup kabul etmiş sayılırsınız. Onayınızı *\"Okudum, kabul ediyorum\"* yazarak ve ödeme dekontunuzu ileterek verebilirsiniz.\n\n"
            ."Siteniz onay ve ödemenin ardından en geç 24 saat içinde yayına alınır ve size WhatsApp'tan bilgi verilir.\n\n"
            .now()->format('d.m.Y')."\n"
            .'*Bakimevleri.com – Güven Bakım ve Danışmanlık Hizmetleri*';
    }
}
