<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// 24 Kasım 2026: kullanıcının talebi - arama kartında kurum sahibi / yetkili hakkında elle girilen bilgiler
// (ilgi alanları, önem verdiği konular, kişisel notlar, sosyal medya). Yalnız admin görür.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('facilities', function (Blueprint $table) {
            $table->json('sahip_profili')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('facilities', function (Blueprint $table) {
            $table->dropColumn('sahip_profili');
        });
    }
};
