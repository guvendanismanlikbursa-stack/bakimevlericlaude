<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// 6 Eylul 2026: kullanicinin talebi - admin dashboard'daki "Kurum Turune
// Gore Ilgi" karti facilities.views_count'un TUM ZAMANLAR toplamini
// kullaniyordu, bu yuzden yuzdeler gunden gune neredeyse hic degismiyordu
// (zaten cok buyumus bir toplama bir gunluk artis matematiksel olarak
// gorunmez kaliyor). Bu tablo HER GUN, HER bolum (brand_scope) icin o anki
// toplam goruntulenmeyi (SADECE yayindaki/silinmemis kurumlar, is_claimed
// sarti YOK - facility_daily_stats'in aksine TUM kurumlari kapsar) tek
// satirda kaydeder - iki farkli tarihteki satir farki alinarak "son N
// gunde GERCEKTEN kazanilan goruntulenme" hesaplanabilir.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('category_view_snapshots', function (Blueprint $table) {
            $table->id();
            $table->string('brand_scope', 50);
            $table->date('date');
            $table->unsignedBigInteger('total_views');
            $table->unsignedInteger('facility_count');
            $table->timestamps();

            $table->unique(['brand_scope', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('category_view_snapshots');
    }
};
