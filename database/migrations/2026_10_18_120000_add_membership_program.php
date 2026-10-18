<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// 18 Ekim 2026: kullanıcının talebi - "İyilik Hareketi" üyelik programı.
// Paket kataloğu iki tür paketi ayırır: 'credit' (mevcut bakiye/teklif hakkı
// paketleri, aynen kalır) ve 'membership' (Farkındalık Destekçisi, Kurumsal
// İyilik Ortağı, Altın Kalpli Kurum, Vizyoner İyilik Elçisi). Üyelik
// süresince kurum teklif ücreti ödemez, kartında paketin rozeti görünür.
// Program Setting('membership_live') açılana kadar ailelere/kurumlara
// görünmez (bkz. membership_live()).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscription_packages', function (Blueprint $table) {
            $table->string('kind', 20)->default('credit')->after('id');
            $table->unsignedSmallInteger('months')->nullable()->after('duration_days');
            $table->string('badge_label', 80)->nullable()->after('months');
            $table->string('badge_emoji', 16)->nullable()->after('badge_label');
            $table->string('free_months_label', 80)->nullable()->after('badge_emoji');
            $table->string('tagline', 255)->nullable()->after('free_months_label');
            $table->text('features')->nullable()->after('tagline');
            $table->string('only_city_slug', 80)->nullable()->after('features');
            $table->boolean('is_premium')->default(false)->after('only_city_slug');
        });

        Schema::table('facilities', function (Blueprint $table) {
            $table->unsignedBigInteger('subscription_package_id')->nullable()->after('subscription_amount');
            $table->string('offer_token', 48)->nullable()->unique()->after('subscription_package_id');
            $table->timestamp('offer_link_opened_at')->nullable()->after('offer_token');
            $table->unsignedInteger('offer_link_open_count')->default(0)->after('offer_link_opened_at');
        });

        $now = now();
        $base = [
            'kind' => 'membership', 'description' => null, 'bonus_quote_credits' => 0,
            'duration_days' => null, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now,
        ];
        DB::table('subscription_packages')->insert([
            $base + [
                'name' => 'Aylık Üyelik', 'price' => 1000, 'months' => 1, 'sort_order' => 1,
                'badge_label' => 'Farkındalık Destekçisi', 'badge_emoji' => '💜',
                'free_months_label' => null, 'only_city_slug' => null, 'is_premium' => false,
                'tagline' => 'Platformda aktif üye olarak yer alırsınız; üyelik gelirinin bir bölümü kimsesiz yaşlı hastaların bakımına ayrılır.',
                'features' => null,
            ],
            $base + [
                'name' => '6 Aylık Üyelik', 'price' => 5000, 'months' => 6, 'sort_order' => 2,
                'badge_label' => 'Kurumsal İyilik Ortağı', 'badge_emoji' => '💚',
                'free_months_label' => '1 ay ücretsiz', 'only_city_slug' => null, 'is_premium' => false,
                'tagline' => 'Altı ay boyunca tüm üyelik haklarından yararlanırsınız, kurumunuz İyilik Ortağı rozetiyle görünür.',
                'features' => null,
            ],
            $base + [
                'name' => 'Yıllık Üyelik', 'price' => 9000, 'months' => 12, 'sort_order' => 3,
                'badge_label' => 'Altın Kalpli Kurum', 'badge_emoji' => '💛',
                'free_months_label' => '3 ay ücretsiz', 'only_city_slug' => null, 'is_premium' => false,
                'tagline' => 'Bir yıl boyunca tüm üyelik hakları ve kurumsal sosyal sorumluluk sertifikası.',
                'features' => null,
            ],
            $base + [
                'name' => 'Yıllık Premium & Prodüksiyon', 'price' => 50000, 'months' => 12, 'sort_order' => 4,
                'badge_label' => 'Vizyoner İyilik Elçisi', 'badge_emoji' => '⭐',
                'free_months_label' => 'Yıllık üyelik dahil', 'only_city_slug' => 'bursa', 'is_premium' => true,
                'tagline' => 'Yıllık üyeliğin tüm haklarına ek olarak profesyonel tanıtım prodüksiyonu (Bursa pilot uygulaması).',
                'features' => "4K drone ile dış mekân çekimi\nProfesyonel iç mekân video ve fotoğraf çekimi\n360 derece fotoğraf\nTanıtım videosunun kurum sayfanızda yayınlanması",
            ],
        ]);
    }

    public function down(): void
    {
        DB::table('subscription_packages')->where('kind', 'membership')->delete();

        Schema::table('facilities', function (Blueprint $table) {
            $table->dropUnique(['offer_token']);
            $table->dropColumn(['subscription_package_id', 'offer_token', 'offer_link_opened_at', 'offer_link_open_count']);
        });

        Schema::table('subscription_packages', function (Blueprint $table) {
            $table->dropColumn(['kind', 'months', 'badge_label', 'badge_emoji', 'free_months_label', 'tagline', 'features', 'only_city_slug', 'is_premium']);
        });
    }
};
