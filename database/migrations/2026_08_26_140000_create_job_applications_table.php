<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 26 Agustos 2026: kullanicinin talebi - gercek bir sahiplenme
        // basvurusunda basvuran yanlislikla CV gonderince aklina gelen
        // fikir: kurum yetkilisi olmak yerine o kurumda CALISMAK isteyen
        // kisilerin basvurabilecegi ayri bir alan. SADECE sahiplenilmis
        // veya aracilik (is_broker_managed) kurumlarda gosterilir - hicbir
        // haberi olmayan bir on-kayitli kuruma "sana eleman buldum" demek
        // saçma olurdu (kullanicinin acik talebi). Basvurular admin
        // tarafindan MANUEL olarak kurumun WhatsApp'ina iletilir (bkz.
        // Admin\JobApplicationController) - facility_claims ile ayni
        // "basvuru -> admin inceler -> iletir" deseni, ama daha basit
        // (belge/onay sureci yok, sadece iletim takibi).
        Schema::create('job_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facility_id')->constrained()->cascadeOnDelete();
            $table->string('brand'); // hangi siteden basvuruldu
            $table->string('applicant_name');
            $table->unsignedTinyInteger('applicant_age')->nullable();
            // Tam adres degil, sadece il/ilce (kullanicinin acik talebi -
            // KVKK/gereksiz hassasiyet riskini azaltmak icin serbest metin).
            $table->string('applicant_location')->nullable();
            $table->string('applicant_phone');
            $table->string('applicant_email')->nullable();
            $table->text('experience')->nullable();
            $table->string('desired_position')->nullable();
            $table->string('status')->default('yeni'); // yeni | iletildi
            $table->timestamp('forwarded_at')->nullable();
            $table->foreignId('forwarded_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->string('consent_ip')->nullable();
            $table->timestamp('consent_accepted_at')->nullable();
            $table->timestamps();

            $table->index(['facility_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_applications');
    }
};
