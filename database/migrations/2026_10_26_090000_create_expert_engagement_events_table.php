<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// 26 Ekim 2026: kullanıcının talebi - Doktorlar bölümü de panelde "Kurum Türüne Göre İlgi" /
// "Gerçek Tıklama" kutularına ve son 24 saat sayacına girsin. Kurum sayfalarındaki
// facility_engagement_events ile aynı mantık: her açılış 'view' (bot dahil), tekil gerçek
// kişi 'real_view' (bot hariç, aynı tarayıcı/bağlantı 24 saatte 1 kez).
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('expert_engagement_events')) {
            Schema::create('expert_engagement_events', function (Blueprint $table) {
                $table->id();
                $table->foreignId('expert_id')->constrained()->cascadeOnDelete();
                $table->string('type', 20);
                $table->string('ip_hash', 64)->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->index(['expert_id', 'type', 'created_at']);
                $table->index(['type', 'created_at']);
            });
        }

        // Kurum olaylarında bölüm geneli "son 24 saat / son 30 gün" sayımı için.
        if (! collect(Schema::getIndexes('facility_engagement_events'))->contains('name', 'fee_type_created_idx')) {
            Schema::table('facility_engagement_events', fn (Blueprint $t) => $t->index(['type', 'created_at'], 'fee_type_created_idx'));
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('expert_engagement_events');
        if (collect(Schema::getIndexes('facility_engagement_events'))->contains('name', 'fee_type_created_idx')) {
            Schema::table('facility_engagement_events', fn (Blueprint $t) => $t->dropIndex('fee_type_created_idx'));
        }
    }
};
