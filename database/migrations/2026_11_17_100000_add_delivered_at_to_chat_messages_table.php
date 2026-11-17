<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// 17 Kasım 2026: kullanıcının talebi - canlı sohbette WhatsApp gibi tik işaretleri (✓ gönderildi, ✓✓ iletildi, mavi ✓✓ okundu).
// "Okundu" için read_at zaten vardı (hiç kullanılmıyordu); "iletildi" için delivered_at eklenir.
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('chat_messages', 'delivered_at')) {
            Schema::table('chat_messages', function (Blueprint $table) {
                $table->timestamp('delivered_at')->nullable()->after('attachment_size');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('chat_messages', 'delivered_at')) {
            Schema::table('chat_messages', function (Blueprint $table) {
                $table->dropColumn('delivered_at');
            });
        }
    }
};
