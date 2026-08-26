<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 26 Agustos 2026: kullanicinin talebi - cocuk bakim/kres-anaokulu
        // kurumlarinda program suresine gore (yarim gun/tam gun/saatlik)
        // ayri fiyat araligi. facility_room_types/facility_age_groups ile
        // AYNI desen.
        Schema::create('facility_program_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facility_id')->constrained()->cascadeOnDelete();
            $table->string('program_type');
            $table->decimal('price_min', 10, 2)->nullable();
            $table->decimal('price_max', 10, 2)->nullable();
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['facility_id', 'program_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facility_program_types');
    }
};
