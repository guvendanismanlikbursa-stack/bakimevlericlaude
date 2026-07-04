<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Performans denetimi: yeni eklenen kesif/fiyat rehberi sorgu paternlerini
// (is_claimed+claimed_at siralamasi, price_min filtreleme) destekleyen
// index'ler. Mevcut sema zaten iyi index'lenmisti (bkz. 0001_01_01_000023
// harden_core_schema_for_production); bu, o calismanin devami niteliginde.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('facilities', function (Blueprint $table) {
            $table->index(['is_claimed', 'claimed_at']);
            $table->index(['price_min']);
        });
    }

    public function down(): void
    {
        Schema::table('facilities', function (Blueprint $table) {
            $table->dropIndex(['is_claimed', 'claimed_at']);
            $table->dropIndex(['price_min']);
        });
    }
};
