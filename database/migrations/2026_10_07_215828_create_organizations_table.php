<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// 7 Ekim 2026: kullanicinin talebi - "Organizasyon Şirketleri" icin, Expert
// (Doktorlar/Uzmanlar) ile AYNI mimari ama "branş" yerine "hizmet türü"
// olan ikinci bagimsiz bolum. Facility VE Expert tablolarindan tamamen
// BAGIMSIZ. Expert gibi Faz 1: sadece bilgilendirme, sahiplenme/uyelik
// YOK. is_published varsayilan false - icinde gercek kurum olana kadar
// kullanici bunu elle true yapmayacak (ExpertController'daki
// PUBLICLY_VISIBLE sabiti gibi ayri bir goruntu-kapisi da olacak).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organizations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('title', 60)->nullable();
            // service_type: teknik/resmi ad ("Doğum Günü Organizasyonu"),
            // service_type_label: gunluk dildeki karsiligi - Expert'teki
            // branch/branch_label ikilisiyle birebir ayni mantik.
            $table->string('service_type', 100);
            $table->string('service_type_label', 100);
            $table->foreignId('city_id')->constrained();
            $table->string('district')->nullable();
            $table->string('address')->nullable();
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('phone_type', 20)->nullable();
            $table->string('clinic_name')->nullable();
            $table->string('google_maps_url')->nullable();
            $table->float('rating')->nullable();
            $table->unsignedInteger('review_count')->nullable();
            $table->json('reviews')->nullable();
            $table->string('source', 40)->nullable();
            $table->json('source_payload')->nullable();
            $table->boolean('is_published')->default(false);
            $table->boolean('is_claimed')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['city_id', 'service_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organizations');
    }
};
