<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 13 Ekim 2026: FamilyNeedSurveySend ile AYNI desen - hangi kurumun
        // hangi lead'e WhatsApp'tan ulastigini takip eder (mukerrer
        // ulasimi gormek ve kurum sayfasinda gecmisi gostermek icin).
        Schema::create('care_advisor_lead_sends', function (Blueprint $table) {
            $table->id();
            $table->foreignId('care_advisor_lead_id')->nullable()->constrained('care_advisor_leads')->nullOnDelete();
            $table->foreignId('facility_id')->constrained('facilities')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('care_advisor_lead_sends');
    }
};
