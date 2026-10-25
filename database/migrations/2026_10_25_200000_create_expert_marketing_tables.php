<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// 25 Ekim 2026: kullanıcının talebi - pazarlamacı CRM'ine doktorlar da eklendi.
// Sorular kurumlardakiyle aynı; tek fark "ilgilendiği paket" seçenekleri
// (İyilik Hareketi destekçiliği / Standart Site / Profesyonel Site).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expert_marketing_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expert_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('marketer_id')->nullable()->index();
            $table->unsignedBigInteger('admin_id')->nullable();
            $table->string('status', 40);
            $table->string('contact_name', 120)->nullable();
            $table->string('contact_role', 20)->nullable();
            $table->string('website_status', 20)->nullable();
            $table->string('site_offer_interest', 20)->nullable();
            $table->string('package_interest', 30)->nullable();
            $table->text('note')->nullable();
            $table->boolean('brochure_requested')->nullable();
            $table->timestamp('callback_at')->nullable();
            $table->timestamps();
        });

        Schema::table('experts', function (Blueprint $table) {
            $table->string('marketing_status', 40)->nullable()->index();
            $table->timestamp('marketing_status_at')->nullable();
            $table->boolean('marketing_brochure_requested')->nullable();
            $table->timestamp('marketing_callback_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expert_marketing_notes');
        Schema::table('experts', function (Blueprint $table) {
            $table->dropColumn(['marketing_status', 'marketing_status_at', 'marketing_brochure_requested', 'marketing_callback_at']);
        });
    }
};
