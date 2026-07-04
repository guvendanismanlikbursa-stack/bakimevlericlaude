<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // KVKK, hakkimizda gibi statik sayfalari marka bazli admin'den yonetmek icin
        Schema::create('content_pages', function (Blueprint $table) {
            $table->id();
            $table->string('brand');
            $table->string('title');
            $table->string('slug');
            $table->longText('body');
            $table->timestamps();

            $table->unique(['brand', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_pages');
    }
};
