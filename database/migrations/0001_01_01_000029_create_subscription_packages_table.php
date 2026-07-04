<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// canliyaal projesinden tasindi: Paket/abonelik katalogu. Mevcut
// bakiye/cuzdan sistemiyle celismesin diye paket "satin alma" islemi,
// bakiye yukleme (wallet_topups) akisina baglanir: kurum bir paket secip
// talep olusturur, admin normal dekont onay akisiyla onaylar, onayda
// paketin tanimladigi tutar + ucretsiz teklif hakki kurum hesabina islenir.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_packages', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2);
            $table->unsignedInteger('bonus_quote_credits')->default(0);
            $table->unsignedInteger('duration_days')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::table('wallet_topups', function (Blueprint $table) {
            $table->foreignId('subscription_package_id')->nullable()->after('facility_id')
                ->constrained('subscription_packages')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('wallet_topups', function (Blueprint $table) {
            $table->dropConstrainedForeignId('subscription_package_id');
        });

        Schema::dropIfExists('subscription_packages');
    }
};
