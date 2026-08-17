<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('facilities', function (Blueprint $table) {
            // 17 Agustos 2026: kasitli olarak UNIQUE degil - iki farkli
            // kurumun gecmiste ayni slug'i tasimis olma ihtimali (ornegin
            // isim degisikligi sonrasi eski slug bosalip baska bir kurum
            // tarafindan kullanilmis olmasi) cok dusuk ama admin'in kurum
            // adini kaydetmesini ENGELLEMEMESI, salt eski-adres yonlendirme
            // rahatliginin onunde tutuluyor.
            $table->string('old_slug')->nullable()->index()->after('slug');
        });
    }

    public function down(): void
    {
        Schema::table('facilities', function (Blueprint $table) {
            $table->dropColumn('old_slug');
        });
    }
};
