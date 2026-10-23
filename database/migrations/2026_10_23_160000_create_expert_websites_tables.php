<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// 23 Ekim 2026: kullanıcının talebi - doktorlara internet sitesi. Site
// bakimevleri.com/dr/{slug} adresinde yayınlanır; alan adı alınınca
// custom_domain'e taşınır. Doktorların paneli olmadığı için YALNIZCA admin
// panelinden düzenlenir. Hukuki nedenle sitede hasta yorumu gösterilmez.
// Her değişiklik expert_website_revisions'a yazılır (geri alınabilir).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expert_websites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expert_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('slug', 120)->unique();
            $table->string('status', 16)->default('taslak')->index();
            $table->string('custom_domain', 190)->nullable()->unique();
            $table->string('hero_title', 150)->nullable();
            $table->string('hero_subtitle', 255)->nullable();
            $table->text('about_text')->nullable();
            $table->text('ilgi_alanlari')->nullable();
            $table->text('calisma_saatleri')->nullable();
            $table->string('photo_path', 255)->nullable();
            $table->string('theme', 20)->default('mavi');
            $table->json('sections')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });

        Schema::create('expert_website_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expert_website_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('editor_id')->nullable();
            $table->string('editor_name', 150)->nullable();
            $table->json('before');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expert_website_revisions');
        Schema::dropIfExists('expert_websites');
    }
};
