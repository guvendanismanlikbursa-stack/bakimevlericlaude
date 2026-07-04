<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Admin > Site Istatistikleri ekrani marka ve sehre gore filtreleme/gruplama
// yapiyor; bu index'ler o sorgularin veri buyudukce hizli kalmasini saglar.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('family_users', function (Blueprint $table) {
            $table->index('registered_brand');
            $table->index('signup_city_name');
        });
    }

    public function down(): void
    {
        Schema::table('family_users', function (Blueprint $table) {
            $table->dropIndex(['registered_brand']);
            $table->dropIndex(['signup_city_name']);
        });
    }
};
