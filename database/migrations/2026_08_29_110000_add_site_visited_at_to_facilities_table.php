<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// 29 Agustos 2026: kullanicinin talebi - anlasmali (is_broker_managed)
// kurumlarin ekibimiz tarafindan YERINDE ziyaret edildigini kurum detay
// sayfasinda dogru/durust bir rozetle gostermek istiyor. is_broker_managed
// alaninin kendisi BUNU garanti etmez (admin bir kurumu baska bir sebeple
// de aracilik listesine ekleyebilir) - bu yuzden BILEREK ayri, sadece
// gercekten ziyaret edildiginde admin tarafindan elle isaretlenen bir alan.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('facilities', function (Blueprint $table) {
            $table->timestamp('site_visited_at')->nullable()->after('is_broker_managed');
        });
    }

    public function down(): void
    {
        Schema::table('facilities', function (Blueprint $table) {
            $table->dropColumn('site_visited_at');
        });
    }
};
