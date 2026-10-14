<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// 7 Ekim 2026: kullanicinin talebi - "Özel Doktorlar" (doktorlar) icin ayri,
// kucuk bir 4. bolum. BILEREK Facility tablosundan tamamen BAGIMSIZ -
// mevcut kurum listeleme/filtreleme/teklif sistemine hic dokunulmadan,
// ayri bir "bina" olarak insa edilir (kullanicinin acik talebi - mevcut
// calisan sistemi bozma riski olmasin). Faz 1: SADECE bilgilendirme,
// sahiplenme/uyelik YOK (ileride eklenebilir, bkz. is_claimed alani
// simdiden hazir ama arayuzde hic kullanilmiyor).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('experts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('title', 60)->nullable(); // "Uzm. Dr.", "Prof. Dr." vb.
            // branch: teknik/resmi ad ("Pediatri"), branch_label: gunluk dildeki
            // karsiligi ("Çocuk Doktoru") - kullanicinin talebi, ikisi birlikte
            // gosterilir ("Pediatri (Çocuk Doktoru)").
            $table->string('branch', 100);
            $table->string('branch_label', 100);
            $table->foreignId('city_id')->constrained();
            $table->string('district')->nullable();
            $table->string('address')->nullable();
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('phone_type', 20)->nullable();
            $table->string('clinic_name')->nullable(); // calistigi klinik/hastane
            $table->string('google_maps_url')->nullable();
            $table->float('rating')->nullable();
            $table->unsignedInteger('review_count')->nullable();
            // 7 Ekim 2026 duzeltme: kullanicinin bulgusu - ilk tasarimda
            // "sentezlenmis aciklama" (platform'un KENDI yazdigi, doktoru
            // oven metin) dusunulmustu, ama bu aslen REKLAM metni sayilir -
            // tam tersi daha guvenli: GERCEK, atifli (yazan+tarih) Google
            // yorumlarini AYNEN gostermek (doktorsitesi.com/doktortakvimi.com
            // gibi bu alandaki mevcut siteler de boyle yapiyor) - uclu taraf
            // icerigin aktarimi, platformun kendi iddiasi degil. Facility
            // tarafindaki ayri 'facility_google_reviews' tablosuyla AYNI
            // mantik, burada JSON olarak (Faz 1 icin daha hafif, max 5 kayit).
            $table->json('reviews')->nullable();
            $table->string('source', 40)->nullable();
            $table->json('source_payload')->nullable();
            $table->boolean('is_published')->default(false);
            // Ileride sahiplenme/uyelik icin hazir alan - Faz 1'de arayuzde
            // hic kullanilmiyor, sadece mimariyi hazirliyor.
            $table->boolean('is_claimed')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['city_id', 'branch']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('experts');
    }
};
