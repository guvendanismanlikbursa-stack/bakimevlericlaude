<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Ortak veritabani: aile bir kez kayit olur, 3 sitenin hepsinde ayni
        // e-posta/sifre ile giris yapabilir. 'registered_brand' sadece istatistik icindir.
        Schema::create('family_users', function (Blueprint $table) {
            $table->id();
            $table->string('registered_brand')->nullable();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->string('password');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('family_users');
    }
};
