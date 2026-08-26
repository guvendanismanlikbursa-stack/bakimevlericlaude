<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 26 Agustos 2026: kullanicinin talebi - "bakim takip ziyareti"
        // hizmeti icin aile paneli basvurusu. DIKKAT: mevcut 'visit_requests'
        // tablosuyla (kurumu incelemeye gelmek isteyen ADAY aileler icin,
        // tamamen farkli bir ozellik) KARISTIRILMAMALI - bilerek ayri isim.
        Schema::create('visit_service_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('family_user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('facility_id')->constrained()->cascadeOnDelete();
            $table->string('brand', 50);
            $table->string('patient_name', 150);
            $table->unsignedTinyInteger('patient_age')->nullable();
            $table->text('patient_condition')->nullable();
            $table->string('desired_frequency', 60)->nullable();
            $table->string('phone', 30);
            // yeni: admin henuz incelemedi. iletisime_gecildi: admin aradi/
            // fiyat konusuldu. aktif: duzenli ziyaret devam ediyor. pasif:
            // hizmet durduruldu/tamamlandi.
            $table->string('status', 30)->default('yeni');
            $table->timestamp('admin_reviewed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visit_service_requests');
    }
};
