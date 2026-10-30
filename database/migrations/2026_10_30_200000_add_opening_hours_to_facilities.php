<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// 30 Ekim 2026: kullanıcının talebi - kurumların çalışma saatleri (arka plan
// tarayıcısından gelir), kartlarda ve detay sayfasında gösterilir.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('facilities', function (Blueprint $table) {
            $table->json('opening_hours')->nullable();
            $table->timestamp('opening_hours_fetched_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('facilities', function (Blueprint $table) {
            $table->dropColumn(['opening_hours', 'opening_hours_fetched_at']);
        });
    }
};
