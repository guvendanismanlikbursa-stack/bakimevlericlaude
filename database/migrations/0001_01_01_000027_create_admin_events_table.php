<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// canliyaal projesinden tasindi: Admin islem gunlugu (audit log).
// Kim, ne zaman, hangi kaydi, ne yapti sorularini cevaplar.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_events', function (Blueprint $table) {
            $table->id();
            $table->string('action_site', 40)->nullable();
            $table->foreignId('admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->string('event_type', 80)->index();
            $table->string('entity_type', 80)->nullable()->index();
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->json('detail_json')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_events');
    }
};
