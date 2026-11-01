<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// 1 Kasım 2026: kullanıcının talebi - doktorların çalışma saatleri (kart ve doktor sayfasında gösterilir).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('experts', function (Blueprint $table) {
            $table->json('opening_hours')->nullable();
            $table->timestamp('opening_hours_fetched_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('experts', function (Blueprint $table) {
            $table->dropColumn(['opening_hours', 'opening_hours_fetched_at']);
        });
    }
};
