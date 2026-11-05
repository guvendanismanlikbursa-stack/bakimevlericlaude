<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// 5 Kasım 2026: kullanıcının talebi - her siteye 100 rehber yazısı. Yazılara kapak görseli, konu (kategori)
// ve yayın tarihi eklenir; yayın tarihi ileri bir gün olan yazı o güne kadar sitede görünmez.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('content_pages', function (Blueprint $table) {
            $table->string('cover_path')->nullable()->after('body');
            $table->string('category', 60)->nullable()->after('cover_path');
            $table->timestamp('published_at')->nullable()->after('category');
            $table->index(['brand', 'type', 'category']);
            $table->index('published_at');
        });
    }

    public function down(): void
    {
        Schema::table('content_pages', function (Blueprint $table) {
            $table->dropIndex(['brand', 'type', 'category']);
            $table->dropIndex(['published_at']);
            $table->dropColumn(['cover_path', 'category', 'published_at']);
        });
    }
};
