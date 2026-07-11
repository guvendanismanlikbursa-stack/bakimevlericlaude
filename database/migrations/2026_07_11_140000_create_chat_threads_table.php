<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Canli destek sohbeti - insan operatorlu (admin), anonim-oncelikli.
// guest_token localStorage'da tutulan kimlik, giris zorunlu degil.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_threads', function (Blueprint $table) {
            $table->id();
            $table->string('brand'); // paylasilan DB'de marka ayrimi (bkz. offer_requests.brand)
            $table->string('guest_token')->unique();
            $table->foreignId('family_user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('intent'); // sohbet | dertlesme | fikir | temsilci
            $table->string('operator_gender_preference')->nullable(); // erkek | kadin | farketmez
            $table->string('status')->default('open'); // open | assigned | closed
            $table->foreignId('assigned_admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->string('city_name')->nullable();
            $table->timestamp('last_message_at')->nullable();
            $table->string('last_message_preview')->nullable();
            $table->boolean('unread_by_admin')->default(false);
            $table->boolean('unread_by_guest')->default(false);
            $table->timestamps();

            $table->index(['brand', 'status']);
            $table->index('last_message_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_threads');
    }
};
