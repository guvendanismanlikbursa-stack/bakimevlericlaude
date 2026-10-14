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
        DB::statement('ALTER TABLE organizations MODIFY source VARCHAR(100) NULL');
        DB::statement('ALTER TABLE experts MODIFY source VARCHAR(100) NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE organizations MODIFY source VARCHAR(40) NULL');
        DB::statement('ALTER TABLE experts MODIFY source VARCHAR(40) NULL');
    }
};
