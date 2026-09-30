<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// 27 Eylul 2026: kullanicinin talebi - pazarlamacinin her arama sonrasi
// dustugu not gecmisi (BalanceLog gibi, silinmez/append-only). Kurum
// bazinda BIRDEN FAZLA not olabilir, listede en son durum facilities.
// marketing_status'te (bkz. ayni tarihli o migration) hizli filtreleme
// icin ayrica tutulur.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('facility_marketing_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facility_id')->constrained()->cascadeOnDelete();
            $table->foreignId('marketer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->string('status')->nullable();
            $table->text('note')->nullable();
            $table->boolean('brochure_requested')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facility_marketing_notes');
    }
};
