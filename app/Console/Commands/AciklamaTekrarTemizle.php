<?php

namespace App\Console\Commands;

use App\Models\Facility;
use App\Services\ReviewBasedDescriptionGenerator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

// 18 Kasım 2026: her gece çalışan açıklama görevi, "formu doldur" ifadesi geçmeyen iki form-yönlendirme
// kalıbını ("…formu kullanarak…", "…formu kullanabilirsiniz") tanımadığı için aynı cümleyi her gece
// yeniden ekliyordu (1.239 kurumda aynı paragraf 2-6 kez). Bu komut tekrarlanan paragrafları siler ve
// birden fazla form-yönlendirme paragrafı varsa yalnız ilkini bırakır. Eski metinler önce JSON'a yedeklenir.
class AciklamaTekrarTemizle extends Command
{
    protected $signature = 'platform:aciklama-tekrar-temizle {--gercek : Veritabanına yaz (yoksa yalnız sayar)}';

    protected $description = 'Kurum açıklamalarındaki tekrarlanan paragrafları ve fazla form-yönlendirme cümlelerini temizler';

    /** @return list<string> */
    public static function paragraflar(string $metin): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\n\s*\n/u', $metin)), fn ($p) => $p !== ''));
    }

    public static function temizle(string $metin): string
    {
        $paragraflar = self::paragraflar($metin);
        $gorulen = [];
        $ctaVar = false;
        $sonuc = [];
        foreach ($paragraflar as $p) {
            $anahtar = mb_strtolower(preg_replace('/\s+/u', ' ', $p));
            if (isset($gorulen[$anahtar])) {
                continue;
            }
            $gorulen[$anahtar] = true;
            if (ReviewBasedDescriptionGenerator::ctaIceriyorMu($p)) {
                if ($ctaVar) {
                    continue;
                }
                $ctaVar = true;
            }
            $sonuc[] = $p;
        }

        return implode("\n\n", $sonuc);
    }

    public function handle(): int
    {
        $gercek = (bool) $this->option('gercek');
        $degisen = [];   // id => [eski, yeni]
        Facility::withTrashed()->whereNotNull('description')->select(['id', 'description'])
            ->chunkById(500, function ($kurumlar) use (&$degisen) {
                foreach ($kurumlar as $f) {
                    $yeni = self::temizle((string) $f->description);
                    // Yalnız satır sonu/boşluk farkı olanlara dokunulmaz (ekranda değişiklik yok); paragraf sayısı azalmalı.
                    $eskiSayi = count(self::paragraflar((string) $f->description));
                    if ($yeni !== '' && count(self::paragraflar($yeni)) < $eskiSayi) {
                        $degisen[$f->id] = [$f->description, $yeni];
                    }
                }
            });
        if ($gercek && $degisen) {
            // Önce yedek, sonra yazma: yarıda kesilse bile eski metinler kaybolmaz.
            $yol = '_yedek/aciklama_tekrar_'.now()->format('Ymd_His').'.json';
            Storage::disk('local')->put($yol, json_encode(array_map(fn ($d) => $d[0], $degisen), JSON_UNESCAPED_UNICODE));
            $this->info("Yedek: storage/app/private/{$yol}");
            foreach ($degisen as $id => [, $yeni]) {
                // updated_at değişmesin diye doğrudan sorguyla (model olayları tetiklenmez)
                Facility::withTrashed()->whereKey($id)->toBase()->update(['description' => $yeni]);
            }
        }
        $this->info(($gercek ? 'TEMİZLENDİ' : 'DENEME (yazılmadı)').': '.count($degisen).' kurum');

        return self::SUCCESS;
    }
}
