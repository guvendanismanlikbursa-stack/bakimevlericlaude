<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// 17 Agustos 2026: kullanicinin talebi - bu oturumda AYNI KOKTEN 3 ayri
// sessiz aksama yasandi (yedekleme gunlerce calismadi, kuyruk kilidi 24
// saat takildi, gunluk istatistik gorevi 5 gun calismadi) - hicbiri hata
// vermedi, sadece "olmadi". Sebep: zamanlanmis gorevlerin (routes/console.php)
// calisip calismadigini goren TEK bir yer yoktu. Bu tablo, HER zamanlanmis
// gorevin son basarili/basarisiz calisma zamanini tutar - /admin/zamanlanan-
// gorevler ekrani ve /_saglik ucu buradan "gecikmede mi" hesaplar.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scheduled_job_runs', function (Blueprint $table) {
            $table->id();
            $table->string('job_name')->unique();
            $table->unsignedInteger('expected_frequency_minutes');
            $table->timestamp('last_success_at')->nullable();
            $table->timestamp('last_failure_at')->nullable();
            $table->unsignedInteger('consecutive_failures')->default(0);
            $table->text('last_output')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scheduled_job_runs');
    }
};
