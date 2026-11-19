<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// 19 Kasım 2026: kullanıcının talebi - canlı sohbette telefon zorunlu, "ne arıyorsunuz" + şehir sorusu,
// boş/kısa sohbetler bildirim göndermesin (ayrı listede dursun), admin "Engelle" düğmesi.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chat_threads', function (Blueprint $table) {
            $table->string('guest_phone', 20)->nullable()->after('guest_age')->index();
            $table->string('looking_for', 30)->nullable()->after('guest_phone');
            // Sohbet "anlamlı" bir mesaj aldı mı (admin'e bildirim gitti mi). false olanlar listede "kısa/boş" bölümünde durur.
            $table->boolean('bildirildi')->default(false)->after('unread_by_guest')->index();
        });
        // Mevcut sohbetler eskisi gibi ana listede kalsın.
        DB::table('chat_threads')->update(['bildirildi' => true]);

        Schema::create('chat_blocks', function (Blueprint $table) {
            $table->id();
            $table->string('guest_token', 64)->nullable()->index();
            $table->string('phone', 20)->nullable()->index();
            $table->unsignedBigInteger('chat_thread_id')->nullable();
            $table->unsignedBigInteger('admin_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_blocks');
        Schema::table('chat_threads', function (Blueprint $table) {
            $table->dropIndex(['guest_phone']);
            $table->dropIndex(['bildirildi']);
            $table->dropColumn(['guest_phone', 'looking_for', 'bildirildi']);
        });
    }
};
