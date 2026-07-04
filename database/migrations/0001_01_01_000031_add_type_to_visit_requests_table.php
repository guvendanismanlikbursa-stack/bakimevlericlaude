<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// "Kontenjan Sor" tek tikla soru butonu, mevcut ziyaret talebi tablosunu
// paylasir; admin panelde ayni ekrandan tipe gore ayirt edilebilir.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('visit_requests', function (Blueprint $table) {
            $table->string('type', 20)->default('ziyaret')->after('facility_id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('visit_requests', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
