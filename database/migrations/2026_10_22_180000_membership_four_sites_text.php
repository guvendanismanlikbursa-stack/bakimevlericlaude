<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// 22 Ekim 2026: kullanıcının talebi - Standart ve Premium üyelikte kurumun
// kendi internet sitesi de olduğu için (yoksa biz yapıyoruz) yayın "4 sitede
// birden" olarak anlatılır. Yalnızca bu iki paketin ilk maddesi değişir.
return new class extends Migration
{
    private const ESKI = "3 sitede aynı anda yayın: Kurumunuz bakimevleri.com, bakimevibul.com ve bakimeviara.com'da aynı anda yayınlanır.";

    private const YENI = "4 sitede aynı anda yayın: Kurumunuz bakimevleri.com, bakimevibul.com, bakimeviara.com ve kendi internet sitenizde aynı anda yayınlanır. İnternet siteniz yoksa sizin için biz hazırlıyoruz.";

    public function up(): void
    {
        DB::table('subscription_packages')->where('kind', 'membership')
            ->get(['id', 'features'])->each(function ($p) {
                // Yalnızca "internet sitesi" bölümü olan paketler (Standart, Premium).
                if (str_contains((string) $p->features, self::ESKI) && preg_match('/^##\s.*internet sitesi/miu', (string) $p->features)) {
                    DB::table('subscription_packages')->where('id', $p->id)
                        ->update(['features' => str_replace(self::ESKI, self::YENI, $p->features), 'updated_at' => now()]);
                }
            });
    }

    public function down(): void
    {
        DB::table('subscription_packages')->where('kind', 'membership')->get(['id', 'features'])->each(function ($p) {
            DB::table('subscription_packages')->where('id', $p->id)
                ->update(['features' => str_replace(self::YENI, self::ESKI, (string) $p->features)]);
        });
    }
};
