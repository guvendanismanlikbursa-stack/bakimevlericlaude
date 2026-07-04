<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('facility_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            // hangi marka(lar)in bu kategoriyi listeleyecegini belirler
            // (config/brands.php -> category_scope ile eslestirilir)
            $table->string('brand_scope');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facility_categories');
    }
};
