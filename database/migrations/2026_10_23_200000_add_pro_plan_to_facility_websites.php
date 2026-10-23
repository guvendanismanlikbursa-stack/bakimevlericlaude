<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// 23 Ekim 2026: kullanıcının talebi - "Profesyonel Site" paketi. Hazır
// sitenin yanında, kurumun logosu ve rengiyle, çok sayfalı, premium
// görünümlü ikinci bir şablon. Admin plan ve bitiş tarihini belirler;
// içerik (ekip, SSS, duyurular, neden biz, rakamlar, video) admin ve kurum
// panelinden düzenlenir. Ziyaret/WhatsApp/arama/form sayıları günlük tutulur
// (aylık rapor için).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('facility_websites', function (Blueprint $table) {
            $table->string('plan', 16)->default('standart')->after('status');
            $table->date('pro_until')->nullable()->after('plan');
            $table->string('logo_path', 255)->nullable();
            $table->string('brand_color', 7)->nullable();
            $table->string('font_style', 16)->default('zarif');
            $table->string('video_url', 255)->nullable();
            $table->json('stats')->nullable();
            $table->json('values_list')->nullable();
            $table->json('team')->nullable();
            $table->json('faqs')->nullable();
            $table->json('announcements')->nullable();
        });

        Schema::create('facility_website_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facility_website_id')->constrained()->cascadeOnDelete();
            $table->date('gun');
            $table->unsignedInteger('ziyaret')->default(0);
            $table->unsignedInteger('whatsapp')->default(0);
            $table->unsignedInteger('arama')->default(0);
            $table->unsignedInteger('form')->default(0);
            $table->unique(['facility_website_id', 'gun']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facility_website_stats');
        Schema::table('facility_websites', function (Blueprint $table) {
            $table->dropColumn(['plan', 'pro_until', 'logo_path', 'brand_color', 'font_style', 'video_url', 'stats', 'values_list', 'team', 'faqs', 'announcements']);
        });
    }
};
