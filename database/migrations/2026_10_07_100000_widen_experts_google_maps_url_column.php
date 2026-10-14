<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// 7 Ekim 2026: facilities.google_maps_url icin daha once yasanan AYNI hata
// (bkz. 2026_09_29_150000_widen_google_maps_url_column.php) simdi experts
// tablosunda da cikti - gercek Nilufer doktor verisini ice aktarirken
// VARCHAR(255) (Laravel'in varsayilan string() uzunlugu) Google'in uzun
// linklerini (!4m7!3m6!1s...!19s... parametreleri) kesip QueryException
// firlatti. sqlite'ta (test paketi) bu ALTER'a gerek yok, sadece mysql'de.
return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE experts MODIFY google_maps_url TEXT NULL');
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE experts MODIFY google_maps_url VARCHAR(255) NULL');
        }
    }
};
