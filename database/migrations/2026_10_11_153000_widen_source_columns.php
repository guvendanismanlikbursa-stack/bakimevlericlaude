<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// 11 Ekim 2026: kullanicinin bulgusu (canli hata #226) - "source" sutunu
// SADECE 40 karakter, biraz aciklayici bir kaynak etiketi (ör.
// "istanbul_pediatri_kucukcekmece_2026_10_11" = 41 karakter) bile INSERT'i
// patlatiyordu. google_maps_url'de yasanan ayni sinif hatanin (bkz.
// 2026_10_11_134000) tekrari - dogrudan ALTER TABLE, doctrine/dbal yok.
return new class extends Migration
{
    public function up(): void
    {
        // 17 Ekim 2026: MySQL'e özel "MODIFY" sözdizimi yerel PHPUnit
        // paketinin SQLite test veritabanında "near MODIFY: syntax error"
        // ile tüm testleri başlamadan düşürüyordu (2026_07_13 migration'ındaki
        // aynı düzeltme). SQLite VARCHAR uzunluğunu zaten uygulamadığı için
        // orada bu adım gereksiz; canlıdaki (MySQL) davranış değişmez.
        if (DB::connection()->getDriverName() === 'sqlite') {
            return;
        }

        DB::statement('ALTER TABLE organizations MODIFY source VARCHAR(100) NULL');
        DB::statement('ALTER TABLE experts MODIFY source VARCHAR(100) NULL');
    }

    public function down(): void
    {
        // 17 Ekim 2026: MySQL'e özel "MODIFY" sözdizimi yerel PHPUnit
        // paketinin SQLite test veritabanında "near MODIFY: syntax error"
        // ile tüm testleri başlamadan düşürüyordu (2026_07_13 migration'ındaki
        // aynı düzeltme). SQLite VARCHAR uzunluğunu zaten uygulamadığı için
        // orada bu adım gereksiz; canlıdaki (MySQL) davranış değişmez.
        if (DB::connection()->getDriverName() === 'sqlite') {
            return;
        }

        DB::statement('ALTER TABLE organizations MODIFY source VARCHAR(40) NULL');
        DB::statement('ALTER TABLE experts MODIFY source VARCHAR(40) NULL');
    }
};
