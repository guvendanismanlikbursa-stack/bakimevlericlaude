<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// 25 Ekim 2026: kullanıcının talebi - doktor sitelerine de kurum siteleri gibi
// günlük ziyaret / WhatsApp / telefon / form sayacı (admin raporu için).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expert_website_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expert_website_id')->constrained()->cascadeOnDelete();
            $table->date('gun');
            $table->unsignedInteger('ziyaret')->default(0);
            $table->unsignedInteger('whatsapp')->default(0);
            $table->unsignedInteger('arama')->default(0);
            $table->unsignedInteger('form')->default(0);
            $table->unique(['expert_website_id', 'gun']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expert_website_stats');
    }
};
