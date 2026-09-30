<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// 29 Eylul 2026: kullanicinin bildirdigi gercek hata - Google Haritalar bazi
// kurumlar icin 500 karakteri asan uzun linkler (base64 veri blobu iceren)
// donduruyor, google_maps_url varchar(500) bunu FATAL QueryException ile
// kesiyordu ve import donguSunun ORTASINDA duruyordu (o andan sonraki tum
// kurumlar hic islenmeden atlanmis oluyordu). Raw SQL kullanilir - doctrine/
// dbal kurulu degil, Schema::change() ona ihtiyac duyar.
// 30 Eylul 2026: bu ham SQL MySQL'e ozeldi, yerel test paketinin kullandigi
// sqlite'ta "near MODIFY: syntax error" ile TUM testler patliyordu
// (deploy.py'nin 1. adimi, migrate:fresh --seed). sqlite zaten dinamik
// tipli oldugu icin (VARCHAR(500) bir kisitlama getirmiyor) orada bu ALTER'a
// hic gerek yok - sadece mysql'de calistirilir.
return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE facilities MODIFY google_maps_url TEXT NULL');
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE facilities MODIFY google_maps_url VARCHAR(500) NULL');
        }
    }
};
