<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Widget'ta "size nasil hitap edelim?" adiminda isteğe bagli olarak
// toplanan isim/yas - zorunlu degil, kullanici atlayabilir (bkz.
// SupportChatController::start(), support-chat-widget.blade.php).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chat_threads', function (Blueprint $table) {
            $table->string('guest_name')->nullable()->after('guest_token');
            $table->unsignedTinyInteger('guest_age')->nullable()->after('guest_name');
        });
    }

    public function down(): void
    {
        Schema::table('chat_threads', function (Blueprint $table) {
            $table->dropColumn(['guest_name', 'guest_age']);
        });
    }
};
