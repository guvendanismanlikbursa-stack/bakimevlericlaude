<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// 14 Agustos 2026: kullanicinin talebi - aile "Bursa'da rehabilitasyon"
// gibi bir aramayi kaydedip, o kriterlere uyan YENI bir kurum eklendiginde
// bildirim alabilsin (bkz. App\Models\FamilySavedSearch,
// App\Console\Commands\NotifyFamilySavedSearches). `filters` filtre
// sayfasindaki ayni sorgu parametrelerini (city/district/category/service/
// price_tier/q) JSON olarak tutar - FiltersFacilities trait'i tekrar
// kullanilabilsin diye ayri kolonlara bolunmedi.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('family_saved_searches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('family_user_id')->constrained()->cascadeOnDelete();
            $table->string('brand');
            $table->string('section_slug');
            $table->json('filters')->nullable();
            $table->string('label');
            $table->timestamp('last_checked_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('family_saved_searches');
    }
};
