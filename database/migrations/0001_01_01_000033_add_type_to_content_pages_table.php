<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// "Bakim Rehberi" makaleleri de content_pages uzerinden yonetilir;
// type='page' klasik statik sayfa (Hakkimizda/KVKK), type='guide' rehber makalesi.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('content_pages', function (Blueprint $table) {
            $table->string('type', 20)->default('page')->after('brand')->index();
            $table->string('summary', 300)->nullable()->after('title');
        });
    }

    public function down(): void
    {
        Schema::table('content_pages', function (Blueprint $table) {
            $table->dropColumn(['type', 'summary']);
        });
    }
};
