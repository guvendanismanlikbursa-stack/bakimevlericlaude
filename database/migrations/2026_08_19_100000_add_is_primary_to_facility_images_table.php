<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// 19 Agustos 2026: kullanicinin talebi - kurum gorselleri eklenirken 10
// gorselden hangisinin ANA (kapak) gorsel olacagi secilebilmeli. Onceden
// bu daima "en dusuk sort_order'li" (yani ilk yuklenen) gorseldi, admin/
// kurum yetkilisi bunu degistiremezdi. Bilerek sort_order'i YENIDEN
// SIRALAMAK yerine ayri bir alan: ana gorsel secmek galeri sirasini
// (kucuk resim seridi) BOZMAMALI - kullanici gorselleri belli bir sirada
// dizmis olabilir, sadece kapak gorseli degistirmek ister.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('facility_images', function (Blueprint $table) {
            $table->boolean('is_primary')->default(false)->after('sort_order');
        });
    }

    public function down(): void
    {
        Schema::table('facility_images', function (Blueprint $table) {
            $table->dropColumn('is_primary');
        });
    }
};
