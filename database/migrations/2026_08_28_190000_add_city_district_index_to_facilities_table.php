<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// 28 Agustos 2026: kullanicinin bildirdigi, AYNI sorgu deseninde 3 KEZ
// tekrarlanan "MySQL: No space left on device" hatasi kok nedenine
// kadar izlendi. LocationGuideController::show() (rehber/{bolum}/{il}/
// {ilce} sayfalari) `city_id` + `district` (STRING sutunu, district_id
// DEGIL) ile filtreleyip is_featured/rating'e gore siraliyor - ama bu
// ikisini (city_id+district) birlikte kapsayan HICBIR indeks yoktu
// (mevcut ['city_id','district_id'] indeksi FARKLI bir sutunu, sayisal
// FK'yi kullanir). Sonuc: MySQL bu WHERE'i verimli filtreleyemiyor,
// genis bir ara kume uzerinde ORDER BY icin buyuk bir gecici (disk)
// siralama tablosu olusturmak zorunda kaliyordu - bu da sunucunun
// paylasimli /tmp alani anlik olarak sikistiginda hataya yol aciyordu.
// Bu indeks, ozellikle nadir/kucuk ilcelerde (Erciş, Oltu gibi - hatanin
// GERCEKTEN olustugu 2 ornek) bu sorguyu ilce bazinda birkac satira
// kadar daraltarak gecici tablo ihtiyacini onemli olcude azaltir.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('facilities', function (Blueprint $table) {
            $table->index(['city_id', 'district', 'is_published'], 'facilities_city_district_published_index');
        });
    }

    public function down(): void
    {
        Schema::table('facilities', function (Blueprint $table) {
            $table->dropIndex('facilities_city_district_published_index');
        });
    }
};
