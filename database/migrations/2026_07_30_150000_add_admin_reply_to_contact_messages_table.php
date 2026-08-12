<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// 30 Temmuz 2026: admin panelinden /iletisim mesajlarina dogrudan cevap
// yazip e-posta olarak gonderebilmek icin - bkz. Admin\ContactMessageController::reply().
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contact_messages', function (Blueprint $table) {
            $table->text('admin_reply')->nullable()->after('message');
            $table->timestamp('replied_at')->nullable()->after('admin_reply');
        });
    }

    public function down(): void
    {
        Schema::table('contact_messages', function (Blueprint $table) {
            $table->dropColumn(['admin_reply', 'replied_at']);
        });
    }
};
