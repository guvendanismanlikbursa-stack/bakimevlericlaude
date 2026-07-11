<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Canli sohbet operator musaitlik saatleri - marka bazli DEGIL (global),
// cunku ayni 2 kisi 3 markayi da birlikte yonetiyor. 7 gun icin bir kerelik
// varsayilan satir (09:00-18:00, pazar kapali) bu migration icinde seed edilir,
// admin panelinden duzenlenir.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_working_hours', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('weekday'); // 0=Pazar .. 6=Cumartesi
            $table->time('open_time')->default('09:00:00');
            $table->time('close_time')->default('18:00:00');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique('weekday');
        });

        $now = now();
        DB::table('chat_working_hours')->insert(collect(range(0, 6))->map(fn ($weekday) => [
            'weekday' => $weekday,
            'open_time' => '09:00:00',
            'close_time' => '18:00:00',
            'is_active' => $weekday !== 0, // pazar varsayilan kapali
            'created_at' => $now,
            'updated_at' => $now,
        ])->all());
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_working_hours');
    }
};
