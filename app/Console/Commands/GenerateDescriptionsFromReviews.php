<?php

namespace App\Console\Commands;

use App\Services\ReviewBasedDescriptionGenerator;
use Illuminate\Console\Command;

// 30 Eylul 2026: kullanicinin talebi - "yorumu olmayan kurumlarda sadece
// forma yonlendiren bir bilgilendirme olmali, yorum eklendigi zaman
// otomatik olarak o yoruma gore aciklama yapmali". Yorumsuz kurumlar zaten
// OpsController::bursaDescriptionComplete() (artik tum Turkiye) tarafindan
// sade "forma yonlendir" aciklamasiyla kapsaniyor. Bu komut SADECE yorumu
// OLAN (ve yorumu HENUZ aciklamaya islenmemis) on kayitli kurumlari
// gunluk olarak tarayip zengin, yorum-alintili bir aciklamaya gunceller -
// asil uretim mantigi icin bkz. App\Services\ReviewBasedDescriptionGenerator.
// Elle tek seferlik calistirma icin hala /_ops/generate-descriptions-from-reviews
// da kullanilabilir (ayni servisi cagirir).
class GenerateDescriptionsFromReviews extends Command
{
    protected $signature = 'platform:generate-descriptions-from-reviews';

    protected $description = 'Yorumu olan on kayitli kurumlarin aciklamasini, GERCEK 5 yildizli Google yorumundan alintiyla otomatik gunceller (yorumu olmayanlara dokunmaz)';

    public function handle(ReviewBasedDescriptionGenerator $generator): int
    {
        $afterId = 0;
        $toplamGuncellenen = 0;
        $toplamTarama = 0;

        do {
            $result = $generator->run(200, $afterId);
            $afterId = $result['son_id'];
            $toplamGuncellenen += $result['guncellenen'];
            $toplamTarama += $result['islenen'];
        } while ($result['islenen'] > 0);

        $this->info("{$toplamTarama} on kayitli+yorumlu kurum tarandi, {$toplamGuncellenen} kurumun aciklamasi guncellendi.");

        return self::SUCCESS;
    }
}
