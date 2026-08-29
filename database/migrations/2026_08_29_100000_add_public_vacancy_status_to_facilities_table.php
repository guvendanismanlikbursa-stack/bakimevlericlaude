<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// 29 Agustos 2026: kullanicinin talebi - aileler kurum detay sayfasinda
// "bos yer var mi" diye soruyor, ama mevcut vacant_beds_male/female
// (bkz. 2026_08_25_120000 migration) bilinçli olarak GIZLI ve sadece
// admin panelinde tutuluyor. Bu, ONDAN AYRI, bilinçli olarak basit
// (sayi degil, sadece Var/Yok) ve KAMUYA ACIK, kurum yetkilisinin kendi
// panelinden duzenleyebildigi bir alan. Yasli bakim kategorilerinde
// (huzurevi/yasli bakim evi - koguslar cinsiyete gore ayrildigi icin)
// bay/bayan ayri gosterilir, diger kategorilerde (cocuk, rehabilitasyon
// vb.) tek bir genel "yer var mi" alani kullanilir.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('facilities', function (Blueprint $table) {
            $table->boolean('vacancy_male')->nullable()->after('capacity');
            $table->boolean('vacancy_female')->nullable()->after('vacancy_male');
            $table->boolean('vacancy_general')->nullable()->after('vacancy_female');
            $table->timestamp('vacancy_updated_at')->nullable()->after('vacancy_general');
        });
    }

    public function down(): void
    {
        Schema::table('facilities', function (Blueprint $table) {
            $table->dropColumn(['vacancy_male', 'vacancy_female', 'vacancy_general', 'vacancy_updated_at']);
        });
    }
};
