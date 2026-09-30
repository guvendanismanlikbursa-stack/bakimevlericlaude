<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// 30 Eylul 2026: kullanicinin talebi - kurumlari platforma cekmek icin
// gercek views_count'a EK olarak, her yayindaki kuruma her gun farkli
// rastgele saatlerde 3-10 arasi "eklenen" goruntulenme eklenir (bkz.
// App\Console\Commands\BoostFacilityViews). Gercek sayac (views_count)
// HIC DEGISTIRILMEZ/KARISTIRILMAZ - iç analitik (en cok goruntulenen,
// bolum talep trendi vb.) hala SADECE gercek veriye dayanir. Sadece
// kurum sahibine/ziyaretcilere GORUNEN toplam (views_count+boosted_views_count)
// buyur. Admin panelinde ise ikisi ayri ayri da gorunur (seffaflik).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('facilities', function (Blueprint $table) {
            $table->unsignedInteger('boosted_views_count')->default(0)->after('views_count');
            $table->date('view_boost_date')->nullable()->after('boosted_views_count');
            $table->unsignedTinyInteger('view_boost_daily_target')->nullable()->after('view_boost_date');
            $table->unsignedTinyInteger('view_boost_daily_added')->default(0)->after('view_boost_daily_target');
        });
    }

    public function down(): void
    {
        Schema::table('facilities', function (Blueprint $table) {
            $table->dropColumn(['boosted_views_count', 'view_boost_date', 'view_boost_daily_target', 'view_boost_daily_added']);
        });
    }
};
