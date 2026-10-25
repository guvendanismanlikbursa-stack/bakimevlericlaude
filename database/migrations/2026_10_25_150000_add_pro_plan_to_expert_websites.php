<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// 25 Ekim 2026: kullanıcının talebi - doktor sitelerine de kurum siteleri gibi
// "Profesyonel Site" paketi: premium tasarım, logo/renk, video, rakamlar,
// sık sorulan sorular, duyurular. Hukuki nedenle yorum alanı YOK.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expert_websites', function (Blueprint $table) {
            $table->string('plan', 16)->default('standart')->after('status');
            $table->date('pro_until')->nullable()->after('plan');
            $table->boolean('pro_yayina_aldi')->default(false)->after('pro_until');
            $table->string('logo_path', 255)->nullable();
            $table->string('brand_color', 7)->nullable();
            $table->string('font_style', 16)->default('zarif');
            $table->string('video_url', 255)->nullable();
            $table->json('stats')->nullable();
            $table->json('faqs')->nullable();
            $table->json('announcements')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('expert_websites', function (Blueprint $table) {
            $table->dropColumn(['plan', 'pro_until', 'pro_yayina_aldi', 'logo_path', 'brand_color', 'font_style', 'video_url', 'stats', 'faqs', 'announcements']);
        });
    }
};
