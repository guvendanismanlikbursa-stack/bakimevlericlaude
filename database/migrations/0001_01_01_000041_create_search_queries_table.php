<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// "En cok aranan kurumlar/bolgeler" icin: sitede serbest metin arama kutusu
// yok (kurum listesi il/kategori/hizmet/butce filtreleriyle calisiyor), bu
// yuzden burada gercekte loglanan sey "hangi kelime arandi" degil "hangi
// il+kategori+hizmet kombinasyonu filtrelendi". Buyumeyi sinirlamak icin
// (bkz. admin_events/platform_notifications icin admin-events:prune emri)
// ayni kombinasyon+gun tek satirda sayilir, her aramada yeni satir acilmaz.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('search_queries', function (Blueprint $table) {
            $table->id();
            $table->string('brand', 40);
            $table->foreignId('city_id')->nullable()->constrained('cities')->nullOnDelete();
            $table->foreignId('facility_category_id')->nullable()->constrained('facility_categories')->nullOnDelete();
            $table->string('service', 120)->nullable();
            $table->date('search_date');
            $table->unsignedInteger('count')->default(1);
            $table->timestamps();

            $table->index(['brand', 'search_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('search_queries');
    }
};
