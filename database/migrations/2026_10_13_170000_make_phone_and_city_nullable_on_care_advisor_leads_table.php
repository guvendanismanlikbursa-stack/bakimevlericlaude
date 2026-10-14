<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // 13 Ekim 2026 (3): kullanicinin talebi - isim/telefon yazilmasa da
    // doldurulan Pusula anketi bölgesel ihtiyacı görmek için kaydedilsin.
    // Eskiden 'phone' ve 'city_id' ZORUNLUYDU (bkz. ayni tarihli
    // CareAdvisorController::results() yorumu) - artik ikisi de nullable.
    public function up(): void
    {
        Schema::table('care_advisor_leads', function (Blueprint $table) {
            $table->string('phone')->nullable()->change();
            $table->foreignId('city_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('care_advisor_leads', function (Blueprint $table) {
            $table->string('phone')->nullable(false)->change();
            $table->foreignId('city_id')->nullable(false)->change();
        });
    }
};
