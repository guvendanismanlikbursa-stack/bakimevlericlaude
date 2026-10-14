<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // 13 Ekim 2026 (4): kullanıcının talebi - "soruların başında il ilçe
    // sorulması gerekiyor" - il zaten vardı (city_id), ilçe yeni ekleniyor.
    public function up(): void
    {
        Schema::table('care_advisor_leads', function (Blueprint $table) {
            $table->string('district')->nullable()->after('city_id');
        });
    }

    public function down(): void
    {
        Schema::table('care_advisor_leads', function (Blueprint $table) {
            $table->dropColumn('district');
        });
    }
};
