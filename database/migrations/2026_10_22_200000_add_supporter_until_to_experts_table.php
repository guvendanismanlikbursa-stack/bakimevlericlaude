<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// 22 Ekim 2026: kullanıcının kararı - doktorlara tek çeşit, sembolik yıllık
// "İyilik Hareketi Destekçisi" üyeliği. Hiçbir ayrıcalık vermez (sıralama,
// öne çıkma yok); yalnızca doktor kartında kalp ikonu ve etiket görünür.
// Bitiş tarihi admin panelinden girilir; tarih geçince etiket kendiliğinden kalkar.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('experts', function (Blueprint $table) {
            $table->date('supporter_until')->nullable()->after('is_published');
        });
    }

    public function down(): void
    {
        Schema::table('experts', function (Blueprint $table) {
            $table->dropColumn('supporter_until');
        });
    }
};
