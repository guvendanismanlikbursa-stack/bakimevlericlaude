<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// 20 Eylul 2026: kullanicinin talebi - kurum detay sayfasinda "Google'da
// Yapilan Son Yorumlar" bolumu. Google Places API ucretli oldugu icin
// (kullanicinin acik talebi - "ucretsiz olmak zorunda") veri cekici
// (Playwright tabanli tarayici otomasyonu) ile toplanir, resmi API DEGIL.
// Ilk asama: anlasmali + sahiplenilmis TUM kurumlar + Bursa'daki on
// kayitli kurumlar. Kazanc arttiktan sonra resmi API'ye gecilebilir (bkz.
// kullanicinin kendi ifadesi).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('facility_google_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facility_id')->constrained()->cascadeOnDelete();
            $table->string('author_name', 150);
            $table->unsignedTinyInteger('rating');
            $table->text('body')->nullable();
            // Google'in kendi gosterdigi "2 hafta önce" gibi bagil zaman
            // metni - kesin bir tarih Google'da zaten gosterilmiyor.
            $table->string('relative_time_text', 50)->nullable();
            $table->unsignedTinyInteger('position')->default(0);
            $table->timestamp('fetched_at');
            $table->timestamps();
        });

        Schema::table('facilities', function (Blueprint $table) {
            $table->string('google_maps_url', 500)->nullable()->after('source_payload');
            $table->timestamp('google_reviews_fetched_at')->nullable()->after('google_maps_url');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facility_google_reviews');
        Schema::table('facilities', function (Blueprint $table) {
            $table->dropColumn(['google_maps_url', 'google_reviews_fetched_at']);
        });
    }
};
