<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// 25 Ekim 2026: kullanıcının talebi - kurum ve doktor sitelerinin satış takibi:
// kim, hangi site (standart/profesyonel), başlangıç/bitiş, alınan ücret,
// ödeme durumu, satışı yapan pazarlamacı ve notlar.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_sales', function (Blueprint $table) {
            $table->id();
            $table->string('owner_type', 10)->index(); // kurum | doktor
            $table->unsignedBigInteger('owner_id')->index();
            $table->string('owner_name', 255);
            $table->string('plan', 16); // standart | profesyonel
            $table->date('baslangic');
            $table->date('bitis')->nullable()->index();
            $table->decimal('ucret', 10, 2)->default(0);
            $table->string('odeme', 12)->default('bekliyor'); // odendi | bekliyor | kismi
            $table->unsignedBigInteger('marketer_id')->nullable();
            $table->unsignedBigInteger('admin_id')->nullable();
            $table->text('notlar')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_sales');
    }
};
