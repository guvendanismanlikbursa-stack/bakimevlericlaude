<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Aile <-> Kurum mesajlasmasi, bir teklif talebi (offer_request) altinda yurur.
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('offer_request_id')->constrained()->cascadeOnDelete();
            $table->string('sender_type'); // family | facility | admin
            $table->unsignedBigInteger('sender_id');
            $table->text('body');
            $table->boolean('is_read')->default(false);
            $table->timestamps();

            $table->index(['offer_request_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
