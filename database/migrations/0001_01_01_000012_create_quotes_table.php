<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Armut mantigi: bir teklif talebine (offer_request) kurum kendi ucret/fiyat
        // bilgisini (quote) gonderir, aile en uygununu kabul eder.
        Schema::create('quotes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('offer_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('facility_id')->constrained()->cascadeOnDelete();
            $table->foreignId('facility_user_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('price', 10, 2);
            $table->string('price_period')->default('monthly'); // monthly | one_time
            $table->text('message')->nullable();
            $table->string('status')->default('pending'); // pending | accepted | declined | withdrawn
            $table->timestamps();

            $table->unique(['offer_request_id', 'facility_id']);
            $table->index(['facility_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotes');
    }
};
