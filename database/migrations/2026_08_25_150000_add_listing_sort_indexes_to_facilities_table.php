<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// 25 Agustos 2026: kullanicinin bildirdigi "MySQL: No space left on device"
// hatasi incelendi - facilities.is_featured/rating/updated_at/created_at
// SUTUNLARINDA HIC INDEKS YOKTU. Bu kolonlar Public\FacilityController::index
// ve DiscoveryController'daki listeleme sayfalarinda ORDER BY icin
// kullaniliyor - indekssiz oldugu icin MySQL her sorguda buyuk bir gecici
// diskte siralama tablosu olusturuyordu, bu da sunucunun paylasimli /tmp
// alani sinirdayken hataya sebep oluyordu. Bu, hatanin KOKUNU (sunucunun
// /tmp alaninin kendisi) DUZELTMEZ (o hala hosting firmasiyla konusulmali)
// ama sorgularin gecici tabloya olan ihtiyacini/boyutunu onemli olcude
// azaltarak hatanin sikligini dusurmesi beklenir.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('facilities', function (Blueprint $table) {
            $table->index(['is_published', 'is_featured', 'rating']);
            $table->index(['is_published', 'updated_at']);
            $table->index(['is_published', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('facilities', function (Blueprint $table) {
            $table->dropIndex(['is_published', 'is_featured', 'rating']);
            $table->dropIndex(['is_published', 'updated_at']);
            $table->dropIndex(['is_published', 'created_at']);
        });
    }
};
