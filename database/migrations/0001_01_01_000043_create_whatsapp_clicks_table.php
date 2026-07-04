<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// 3 sitedeki yuzen WhatsApp butonuna tiklama kaydi. WhatsApp'in kendisine
// otomatik bildirim gitmesi resmi (ucretli) WhatsApp Business API gerektirir;
// bu tablo bunun yerine admin panelinde goruntulenen ucretsiz bir kayittir.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_clicks', function (Blueprint $table) {
            $table->id();
            $table->string('brand', 40);
            $table->string('page_url', 500)->nullable();
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->string('city_name')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamps();

            $table->index(['brand', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_clicks');
    }
};
