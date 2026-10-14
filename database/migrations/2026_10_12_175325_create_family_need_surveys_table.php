<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// 12 Ekim 2026: kullanicinin talebi - hastanede balon karsiligi toplanan
// kagit "Cocuk ve Aile Ihtiyac Arastirmasi" anketlerini admin panelinden
// elle girebilmek, girilen konum+ihtiyaca uygun kurumlari otomatik listeleyip
// WhatsApp'tan elle gonderebilmek icin (bkz. Admin\FamilyNeedSurveyController).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('family_need_surveys', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('phone')->nullable();
            $table->foreignId('city_id')->constrained('cities');
            $table->string('district')->nullable();
            $table->string('neighborhood')->nullable();
            $table->string('age_group')->nullable(); // 0_2, 3_5, 6_9, 10_14, 15_18
            $table->string('service_type'); // kres_anaokulu, ozel_egitim_rehabilitasyon, ihtiyac_yok
            $table->string('urgency')->nullable(); // 0_3_ay, 3_6_ay, 6_12_ay, ihtiyac_yok
            $table->string('ownership_preference')->nullable(); // ozel, kamu
            $table->foreignId('created_by_admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('family_need_surveys');
    }
};
