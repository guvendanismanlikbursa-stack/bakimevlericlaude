<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // 13 Ekim 2026 (4): kullanıcının talebi - "yarıda bırakılanlar da
    // gerekli notlarla anketler sayfasında listelenecek, admin detaylı
    // inceleyip gerekirse düzenleyip silebilecek". progress_token: adım
    // adım ilerlerken AYNI satırı güncellemek için tarayıcıda üretilen
    // geçici kimlik (bkz. CareAdvisorController::progress()).
    // completed_at: NULL ise kullanıcı formu hiç bitirip "Sonucumu Gör"a
    // basmamış - admin ekranında bu "yarıda kaldı" rozetiyle gösterilir.
    public function up(): void
    {
        Schema::table('care_advisor_leads', function (Blueprint $table) {
            $table->string('progress_token')->nullable()->index()->after('brand');
            $table->timestamp('completed_at')->nullable()->after('progress_token');
        });
    }

    public function down(): void
    {
        Schema::table('care_advisor_leads', function (Blueprint $table) {
            $table->dropColumn(['progress_token', 'completed_at']);
        });
    }
};
