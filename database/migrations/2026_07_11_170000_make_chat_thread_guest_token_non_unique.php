<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Bir misafir (guest_token = bir tarayici/kisi) artik BIRDEN FAZLA sohbete
// sahip olabilir - her niyet (sohbet/dertlesme/fikir/temsilci) kendi ayri
// thread'i ve mesaj gecmisiyle. Onceki tasarimda guest_token tek bir thread'e
// kilitliydi (unique), bu da "konu degistir" yapildiginda eski mesajlarin
// yeni bolumde de gorunmesine yol aciyordu. Artik guest_token + intent
// kombinasyonu bir thread'i belirliyor (bkz. SupportChatController::start()).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chat_threads', function (Blueprint $table) {
            $table->dropUnique(['guest_token']);
            $table->index(['guest_token', 'intent']);
        });
    }

    public function down(): void
    {
        Schema::table('chat_threads', function (Blueprint $table) {
            $table->dropIndex(['guest_token', 'intent']);
            $table->unique('guest_token');
        });
    }
};
