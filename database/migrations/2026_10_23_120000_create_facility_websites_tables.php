<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// 23 Ekim 2026: kullanıcının talebi - kurumlara internet sitesi. Site
// bakimevleri.com/siteler/{slug} adresinde yayınlanır; kurum alan adı alınca
// custom_domain'e taşınır. İçeriğin çoğu kurum panelindeki bilgilerden gelir,
// burada yalnızca siteye özel alanlar tutulur. Kurum panelinden ve admin
// panelinden düzenlenir; her değişiklik facility_website_revisions'a yazılır.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('facility_websites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facility_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('slug', 120)->unique();
            $table->string('status', 16)->default('taslak')->index();
            $table->string('custom_domain', 190)->nullable()->unique();
            $table->string('hero_title', 150)->nullable();
            $table->string('hero_subtitle', 255)->nullable();
            $table->text('about_text')->nullable();
            $table->unsignedBigInteger('hero_image_id')->nullable();
            $table->string('theme', 20)->default('yesil');
            $table->json('sections')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });

        Schema::create('facility_website_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facility_website_id')->constrained()->cascadeOnDelete();
            $table->string('editor_type', 16);
            $table->unsignedBigInteger('editor_id')->nullable();
            $table->string('editor_name', 150)->nullable();
            $table->json('before');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facility_website_revisions');
        Schema::dropIfExists('facility_websites');
    }
};
