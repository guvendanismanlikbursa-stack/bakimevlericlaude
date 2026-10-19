<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// 19 Ekim 2026: kullanıcının talebi - "internet sitesi olmayan kurumları CRM
// pazarlamacı paneline ve admin kurumlar paneline belirt ki pazarlamada buna
// göre hareket edebilelim". Durum Google Haritalar kaydından (toplu kontrol)
// veya pazarlamacının görüşme notundan gelir; hangisinden geldiği de saklanır.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('facilities', function (Blueprint $table) {
            $table->string('website_status', 20)->nullable()->index();
            $table->string('website_url', 500)->nullable();
            $table->string('website_source', 20)->nullable();
            $table->timestamp('website_checked_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('facilities', function (Blueprint $table) {
            $table->dropIndex(['website_status']);
            $table->dropColumn(['website_status', 'website_url', 'website_source', 'website_checked_at']);
        });
    }
};
