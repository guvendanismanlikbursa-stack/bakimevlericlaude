<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// 11 Ekim 2026: kullanicinin bulgusu (canli hata) - gercek Google Haritalar
// linkleri (ozellikle !data=... takip parametreleriyle) 255 karakteri
// kolayca asiyor, string(255) sutunu "Data too long" hatasiyla INSERT'i
// patlatiyordu (organizations tablosunda Bursa importu sirasinda yakalandi).
// Expert tablosunda ayni sutun/ayni risk var, henuz patlamamis olsa da
// ayni duzeltme ONCEDEN uygulanir.
return new class extends Migration
{
    // dogrudan ALTER TABLE kullanilir - doctrine/dbal kurulu degil, Blueprint::change() calismaz.
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

        DB::statement('ALTER TABLE organizations MODIFY google_maps_url TEXT NULL');
        DB::statement('ALTER TABLE experts MODIFY google_maps_url TEXT NULL');
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

        DB::statement('ALTER TABLE organizations MODIFY google_maps_url VARCHAR(255) NULL');
        DB::statement('ALTER TABLE experts MODIFY google_maps_url VARCHAR(255) NULL');
    }
};
