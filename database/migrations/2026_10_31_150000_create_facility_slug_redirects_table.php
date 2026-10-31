<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// 31 Ekim 2026: kullanıcının talebi - mükerrer kurumlar kalıcı silinince eski sayfa
// adresleri yayındaki kuruma 301 ile yönlenmeye devam etsin (kayıt silinse de adres
// Google'da ve paylaşılmış linklerde yaşıyor olabilir).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('facility_slug_redirects', function (Blueprint $table) {
            $table->id();
            $table->string('old_slug')->unique();
            $table->foreignId('facility_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('deleted_facility_id')->nullable();
            $table->string('reason', 40)->default('mukerrer');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facility_slug_redirects');
    }
};
