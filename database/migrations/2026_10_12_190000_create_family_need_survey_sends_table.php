<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('family_need_survey_sends', function (Blueprint $table) {
            $table->id();
            // 12 Ekim 2026: kullanicinin talebi - hangi ankete gore hangi
            // kuruma WhatsApp gonderildigi takip edilsin. Anket silinirse bile
            // kurum tarafindaki "toplam kac anket gonderildi" sayisi dusmesin
            // diye family_need_survey_id null olabilir (nullOnDelete).
            $table->foreignId('family_need_survey_id')->nullable()->constrained('family_need_surveys')->nullOnDelete();
            $table->foreignId('facility_id')->constrained('facilities')->cascadeOnDelete();
            $table->foreignId('sent_by_admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('family_need_survey_sends');
    }
};
