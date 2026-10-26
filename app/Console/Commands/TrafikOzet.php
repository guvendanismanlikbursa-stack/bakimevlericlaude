<?php

namespace App\Console\Commands;

use App\Support\TrafikAnaliz;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

// 26 Ekim 2026: sunucu erişim kayıtlarından günlük ziyaretçi türü özeti (bkz. App\Support\TrafikAnaliz).
// Saatlik çalışır: bugünü ve dünü yeniden hesaplayıp üzerine yazar (dün gece yarısından sonraki
// satırlar da tamamlansın diye). --gun=N ile kayıtlarda hâlâ duran son N gün geriye dönük doldurulur.
class TrafikOzet extends Command
{
    protected $signature = 'platform:trafik-ozet {--gun=2 : Bugün dahil kaç gün geriye} {--sifirla : Bu günlerin eski kaydını silip sıfırdan hesapla}';

    protected $description = 'Erişim kayıtlarından günlük trafik özetini (ziyaretçi türüne göre) trafik_gunluk tablosuna yazar';

    public function handle(): int
    {
        $gun = max(1, min(31, (int) $this->option('gun')));
        $tarihler = collect(range(0, $gun - 1))->map(fn ($i) => now()->subDays($i)->toDateString())->all();
        $sonuc = TrafikAnaliz::gunler($tarihler);
        if ($this->option('sifirla')) {
            DB::table('trafik_gunluk')->whereIn('tarih', array_keys($sonuc))->delete();
        }
        $satir = 0;
        foreach ($sonuc as $tarih => $markalar) {
            foreach ($markalar as $marka => $turler) {
                foreach ($turler as $tur => $d) {
                    // Sunucu kayıt dosyasını gün içinde kısaltabilir: önceki hesap daha yüksekse korunur (veri kaybolmaz).
                    $eski = DB::table('trafik_gunluk')->where(['tarih' => $tarih, 'marka' => $marka, 'tur' => $tur])->first();
                    DB::table('trafik_gunluk')->updateOrInsert(
                        ['tarih' => $tarih, 'marka' => $marka, 'tur' => $tur],
                        ['istek' => max($d['istek'], (int) ($eski->istek ?? 0)), 'ip' => max($d['ip'], (int) ($eski->ip ?? 0)), 'updated_at' => now(), 'created_at' => $eski->created_at ?? now()]
                    );
                    $satir++;
                }
                $this->line(sprintf('%s %-12s insan=%d(%d ip) supheli=%d(%d ip) google=%d bing=%d yz=%d diger=%d', $tarih, $marka,
                    $turler['insan']['istek'], $turler['insan']['ip'], $turler['supheli']['istek'], $turler['supheli']['ip'],
                    $turler['googlebot']['istek'], $turler['bing']['istek'], $turler['yz']['istek'], $turler['diger_bot']['istek']));
            }
        }
        $this->info('Islenen gunler: '.implode(', ', array_keys($sonuc)).' | yazilan satir: '.$satir);

        return self::SUCCESS;
    }
}
