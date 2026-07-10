<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// "Yakinimda Ara" tiklamasi yapan HERKESIN (kayitli olsun olmasin) konumunu
// kaydeder - admin bu verileri inceleyip talep yogunlugunun gercekte
// nerelerden geldigini gorebilsin diye.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nearby_searches', function (Blueprint $table) {
            $table->id();
            $table->string('brand', 40);
            $table->decimal('lat', 10, 7);
            $table->decimal('lng', 10, 7);
            $table->string('city_name')->nullable();
            $table->string('ip', 45)->nullable();
            $table->foreignId('family_user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->index(['brand', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nearby_searches');
    }
};
