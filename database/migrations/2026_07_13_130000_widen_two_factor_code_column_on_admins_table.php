<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// 13 Temmuz 2026: 2026_07_12_130000 migration'i two_factor_code kolonunu
// VARCHAR(6) olarak acmisti (duz 6 haneli kod varsayimiyla), ama
// AuthController::sendLoginCode() kodu Hash::make() ile bcrypt hash'i
// (60 karakter) yaziyor - her admin giris denemesi "Data too long for
// column two_factor_code" SQL hatasiyla 500 veriyordu, admin paneline
// HICBIR sekilde giris yapilamiyordu. Kolonu genisletiyoruz.
return new class extends Migration
{
    public function up(): void
    {
        // 3 Agustos 2026: MySQL'e ozel "MODIFY" sozdizimi yerel PHPUnit
        // suite'inin SQLite test veritabaninda "near MODIFY: syntax error"
        // ile tum testleri patlatiyordu - SQLite zaten VARCHAR uzunlugunu
        // uygulamiyor (type affinity), bu yuzden orada no-op yeterli.
        if (DB::connection()->getDriverName() === 'sqlite') {
            return;
        }

        DB::statement('ALTER TABLE admins MODIFY two_factor_code VARCHAR(255) NULL');
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return;
        }

        DB::statement('ALTER TABLE admins MODIFY two_factor_code VARCHAR(6) NULL');
    }
};
