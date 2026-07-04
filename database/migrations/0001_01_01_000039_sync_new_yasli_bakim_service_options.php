<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

// config/brands.php > yasli-bakim features listesine "Bakim Danismani" ve
// orijinal yol haritasindaki "Hizmete gore filtre" icin eklenen yeni
// secenekleri (Demans bakimi, Palyatif bakim, Gunduz bakim, Tam zamanli
// bakim, Fizik tedavi) facility_service_options tablosuna ekler. Bu tablo
// ilk migration'da tek seferlik doldurulmustu (idempotent degildi), bu
// yuzden zaten migrate edilmis ortamlarda da eksik kalan secenekleri
// guvenle (mukerrer eklemeden) tamamlar.
return new class extends Migration
{
    public function up(): void
    {
        $newFeatures = ['Demans bakımı', 'Palyatif bakım', 'Gündüz bakım', 'Tam zamanlı bakım', 'Fizik tedavi'];

        foreach ($newFeatures as $feature) {
            DB::table('facility_service_options')->updateOrInsert(
                ['section_slug' => 'yasli-bakim', 'slug' => Str::slug($feature)],
                ['name' => $feature, 'updated_at' => now(), 'created_at' => now()]
            );
        }
    }

    public function down(): void
    {
        // Kasitli olarak geri alinmiyor: kurumlar bu ozelliklerle iliskilendirilmis olabilir.
    }
};
