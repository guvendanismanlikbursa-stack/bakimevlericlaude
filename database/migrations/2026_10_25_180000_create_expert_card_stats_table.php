<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// 25 Ekim 2026: kullanıcının talebi - Doktorlar sayfasındaki kartların günlük
// sayacı (yalnızca admin panelinde görünür): kaç aramada listelendi, kaç kez
// açıldı (detayına bakıldı), telefon düğmesine kaç kez tıklandı.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expert_card_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expert_id')->constrained()->cascadeOnDelete();
            $table->date('gun');
            $table->unsignedInteger('listelenme')->default(0);
            $table->unsignedInteger('acilma')->default(0);
            $table->unsignedInteger('arama')->default(0);
            $table->unique(['expert_id', 'gun']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expert_card_stats');
    }
};
