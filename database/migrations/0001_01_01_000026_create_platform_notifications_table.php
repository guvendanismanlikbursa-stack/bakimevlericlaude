<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// canliyaal projesinden tasindi: Aile ve kurum panelleri icin uygulama-ici
// bildirim sistemi. Polymorphic notifiable (FacilityUser / FamilyUser).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('notifiable_type');
            $table->unsignedBigInteger('notifiable_id');
            $table->string('type', 60);
            $table->string('title', 200);
            $table->text('body')->nullable();
            $table->json('data')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['notifiable_type', 'notifiable_id', 'read_at'], 'platform_notifications_notifiable_read_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_notifications');
    }
};
