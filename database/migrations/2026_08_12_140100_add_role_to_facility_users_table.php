<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// 12 Agustos 2026: kullanicinin talebi - kurum panelinde tek email/sifre
// yerine ekip (personel) hesabi acilabilsin. 'owner' = sahiplenmeyi
// onaylatan ilk hesap (bakiye yukleme/personel yonetimi gibi hassas
// islemler sadece owner'a acik), 'staff' = owner'in davet ettigi ek hesap.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('facility_users', function (Blueprint $table) {
            $table->string('role')->default('owner')->after('facility_id');
        });
    }

    public function down(): void
    {
        Schema::table('facility_users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }
};
