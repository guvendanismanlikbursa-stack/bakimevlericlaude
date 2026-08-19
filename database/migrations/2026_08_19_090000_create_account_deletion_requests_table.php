<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// 19 Agustos 2026: kullanicinin talebi - KVKK "silme hakki" icin aile/kurum
// yetkilisi kendi hesabinin silinmesini talep edebilsin, ama gercek silme
// (kisisel veri anonimlestirme) islemini bir admin onaylayip yapsin -
// platformun diger tum "kullanici talebi -> admin onayi" akislariyla
// (sahiplenme, kayit, bakiye yukleme) ayni desen.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_deletion_requests', function (Blueprint $table) {
            $table->id();
            $table->string('requestable_type');
            $table->unsignedBigInteger('requestable_id');
            $table->timestamp('requested_at');
            $table->string('status')->default('pending'); // pending | completed | rejected
            $table->timestamp('processed_at')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->text('admin_note')->nullable();
            $table->timestamps();

            $table->index(['requestable_type', 'requestable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_deletion_requests');
    }
};
