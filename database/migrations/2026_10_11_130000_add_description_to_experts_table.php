<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// 11 Ekim 2026: kullanicinin talebi - "3 ve uzeri yorumu olanlar icin
// aciklama yaz" (Facility'deki ReviewBasedDescriptionGenerator'in ayni
// mantigi, doktor icin). Expert tablosunda bu alan hic yoktu.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('experts', function (Blueprint $table) {
            $table->text('description')->nullable()->after('clinic_name');
        });
    }

    public function down(): void
    {
        Schema::table('experts', function (Blueprint $table) {
            $table->dropColumn('description');
        });
    }
};
