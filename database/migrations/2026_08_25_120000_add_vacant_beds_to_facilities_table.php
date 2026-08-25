<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// 25 Agustos 2026: kullanicinin talebi - hangi kurumda bay/bayan icin kac
// bos yer oldugunu takip edebilmek istiyor, ama bu SADECE kendisinin
// gorebilecegi, GIZLI bir bilgi olmali - ne diger kurumlar ne aileler
// gormemeli. Bu yuzden bilerek HICBIR public blade/controller/API'ye
// eklenmez, sadece admin panelde okunur/yazilir. Tum kurumlar icin (sadece
// aracilik yaptigi kurumlarla sinirli degil).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('facilities', function (Blueprint $table) {
            $table->unsignedInteger('vacant_beds_male')->nullable()->after('capacity');
            $table->unsignedInteger('vacant_beds_female')->nullable()->after('vacant_beds_male');
            $table->timestamp('vacant_beds_updated_at')->nullable()->after('vacant_beds_female');
        });
    }

    public function down(): void
    {
        Schema::table('facilities', function (Blueprint $table) {
            $table->dropColumn(['vacant_beds_male', 'vacant_beds_female', 'vacant_beds_updated_at']);
        });
    }
};
