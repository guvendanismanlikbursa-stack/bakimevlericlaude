<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// 28 Eylul 2026: kullanicinin talebi - yetkili o an musait olmayabilir,
// "su gun su saatte arayin" diyebilir. Pazarlamaci bu randevuyu not ile
// birlikte kaydeder; facilities.marketing_callback_at (bkz. ayni tarihli
// CrmController) SADECE en guncel/aktif randevuyu tutar, CRM'de "Randevulu
// Aramalar" sekmesinin hizli filtrelenip en yakindan siralanabilmesi icin -
// notun kendisindeki callback_at ise o notun alindigi anki tam gecmis kaydidir.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('facility_marketing_notes', function (Blueprint $table) {
            $table->timestamp('callback_at')->nullable()->after('brochure_requested');
        });
        Schema::table('facilities', function (Blueprint $table) {
            $table->timestamp('marketing_callback_at')->nullable()->after('marketing_brochure_requested');
        });
    }

    public function down(): void
    {
        Schema::table('facility_marketing_notes', function (Blueprint $table) {
            $table->dropColumn('callback_at');
        });
        Schema::table('facilities', function (Blueprint $table) {
            $table->dropColumn('marketing_callback_at');
        });
    }
};
