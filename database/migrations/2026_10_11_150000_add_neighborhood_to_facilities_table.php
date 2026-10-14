<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// 11 Ekim 2026: kullanicinin talebi - admin panelde kurumlari MAHALLE'ye
// gore de filtreleyebilmek (ozellikle buyuk ilcelerde uye olmayan bir
// kuruma gelen talebi, ayni mahallede+ayni kategoride uye olan bir kuruma
// yonlendirebilmek icin). Kullanicinin ACIK sarti: "sallama" mahalle
// ASLA yazilmaz - sadece adres metni VE koordinat-bazli (Nominatim
// reverse-geocode) kaynaklar BIRBIRINI DOGRULADIGINDA doldurulur, aksi
// halde bos birakilip elle incelemeye birakilir (bkz. FacilityNeighborhood
// doldurma eylemi, OpsController).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('facilities', function (Blueprint $table) {
            $table->string('neighborhood')->nullable()->after('district');
        });
    }

    public function down(): void
    {
        Schema::table('facilities', function (Blueprint $table) {
            $table->dropColumn('neighborhood');
        });
    }
};
