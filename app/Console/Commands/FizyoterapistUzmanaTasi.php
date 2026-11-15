<?php

namespace App\Console\Commands;

use App\Models\Expert;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * 15 Kasım 2026: kullanıcının kararı - "bireysel fizyoterapist" kurum kayıtları Uzmanlar bölümüne
 * (doktorlarla aynı düzen) taşınır: reklam yasağı (puan/yorum/fotoğraf/açıklama gösterilmez), sade bilgi,
 * aile doğrudan arar. Sahiplenilmiş (panel hesabı/bakiyesi olan) kurumlar TAŞINMAZ.
 *
 * Kurum kaydı silinmiş sayılır (deleted_at) ve uzmana_tasindi_expert_id ile işaretlenir; fotoğraf ve
 * yorum satırları silinmez. Kurumun eski adresleri expert_slug_redirects ile yeni uzman sayfasına 301 olur.
 *
 *   php artisan fizyoterapist:uzmana-tasi --il=bursa            (deneme, yazmaz)
 *   php artisan fizyoterapist:uzmana-tasi --il=bursa --gercek
 *   php artisan fizyoterapist:uzmana-tasi --il=bursa --geri-al --gercek
 */
class FizyoterapistUzmanaTasi extends Command
{
    public const KAYNAK = 'fizyoterapist-kurumdan';

    protected $signature = 'fizyoterapist:uzmana-tasi {--il= : İl slug (boşsa tüm Türkiye)} {--gercek : Yaz (yoksa yalnızca rapor)} {--geri-al : Taşınanları kuruma geri döndür} {--rapor= : CSV rapor yolu}';

    protected $description = 'Bireysel fizyoterapist kurum kayıtlarını Uzmanlar (doktorlar) bölümüne taşır';

    public function handle(): int
    {
        $gercek = (bool) $this->option('gercek');
        $ilId = null;
        if ($this->option('il')) {
            $ilId = DB::table('cities')->where('slug', $this->option('il'))->value('id');
            if (! $ilId) {
                $this->error('İl bulunamadı: '.$this->option('il'));

                return self::FAILURE;
            }
        }

        return $this->option('geri-al') ? $this->geriAl($ilId, $gercek) : $this->tasi($ilId, $gercek);
    }

    private function tasi(?int $ilId, bool $gercek): int
    {
        $kategoriId = DB::table('facility_categories')->where('slug', 'bireysel-fizyoterapist')->value('id');
        if (! $kategoriId) {
            $this->error('bireysel-fizyoterapist kategorisi yok.');

            return self::FAILURE;
        }
        $sahipli = DB::table('facility_users')->pluck('facility_id')->unique()->flip();
        $q = DB::table('facilities')->where('facility_category_id', $kategoriId)->whereNull('deleted_at')
            ->whereNull('uzmana_tasindi_expert_id')->when($ilId, fn ($w) => $w->where('city_id', $ilId))->orderBy('id');

        $rapor = $this->option('rapor') ? fopen($this->option('rapor'), 'w') : null;
        $rapor && fputcsv($rapor, ['kurum_id', 'ad', 'il', 'ilce', 'durum', 'uzman_id', 'yeni_adres', 'yonlendirilen_eski_adres'], ';');
        $sayac = ['tasindi' => 0, 'sahipli_atlandi' => 0, 'yonlendirme' => 0];
        $iller = DB::table('cities')->pluck('name', 'id');

        foreach ($q->get() as $f) {
            if ($f->is_claimed || $f->claimed_at || isset($sahipli[$f->id])) {
                $sayac['sahipli_atlandi']++;
                $rapor && fputcsv($rapor, [$f->id, $f->name, $iller[$f->city_id] ?? '', $f->district, 'sahiplenilmiş - kurum olarak kaldı', '', '', ''], ';');
                continue;
            }
            $eskiAdresler = array_values(array_unique(array_filter(array_merge(
                [$f->slug, $f->old_slug],
                DB::table('facility_slug_redirects')->where('facility_id', $f->id)->pluck('old_slug')->all()
            ))));
            $ad = trim(str_replace("\u{0307}", '', (string) $f->name));
            $yorumlar = DB::table('facility_google_reviews')->where('facility_id', $f->id)->orderBy('position')->limit(5)
                ->get(['author_name', 'rating', 'body', 'relative_time_text'])->map(fn ($r) => (array) $r)->all();
            $gorseller = DB::table('facility_images')->where('facility_id', $f->id)->orderBy('sort_order')->pluck('path')->all();
            $il = $iller[$f->city_id] ?? '';
            $yer = trim(($f->district ? $f->district.', ' : '').$il, ', ');

            $expertId = null;
            $slug = $this->benzersizSlug($ad);
            if ($gercek) {
                DB::transaction(function () use ($f, $ad, $slug, $yorumlar, $gorseller, $yer, $eskiAdresler, &$expertId) {
                    $e = Expert::create([
                        'name' => $ad,
                        'slug' => $slug,
                        'title' => null,
                        'branch' => 'Fizyoterapi',
                        'branch_label' => 'Fizyoterapist',
                        'city_id' => $f->city_id,
                        'district' => $f->district ? str_replace("\u{0307}", '', $f->district) : null,
                        'address' => $f->address ? str_replace("\u{0307}", '', $f->address) : null,
                        'lat' => $f->lat,
                        'lng' => $f->lng,
                        'phone' => $f->phone,
                        'phone_type' => $f->phone_type ?: classify_phone_type($f->phone),
                        'clinic_name' => null,
                        // Doktorlar gibi sade açıklama (reklam yasağı; sayfada gösterilmez, arama/meta için).
                        'description' => $ad.($yer ? ', '.$yer.' bölgesinde' : '').' hizmet veren bir fizyoterapisttir. Bilgi ve randevu için doğrudan telefonla ulaşabilirsiniz.',
                        'google_maps_url' => $f->google_maps_url,
                        'rating' => $f->rating,
                        'review_count' => count($yorumlar) ?: null,
                        'reviews' => $yorumlar ?: null,
                        'website_status' => $f->website_status,
                        'website_url' => $f->website_url,
                        'website_checked_at' => $f->website_checked_at,
                        'opening_hours' => $f->opening_hours ? json_decode($f->opening_hours, true) : null,
                        'opening_hours_fetched_at' => $f->opening_hours_fetched_at,
                        'source' => self::KAYNAK,
                        'source_payload' => ['kurum_id' => $f->id, 'kurum_slug' => $f->slug, 'eski_adresler' => $eskiAdresler, 'gorseller' => $gorseller, 'tasindi' => now()->toDateTimeString()],
                        'is_published' => true,
                        'is_claimed' => false,
                    ]);
                    $expertId = $e->id;
                    foreach ($eskiAdresler as $eski) {
                        DB::table('expert_slug_redirects')->updateOrInsert(['old_slug' => $eski], ['expert_id' => $e->id, 'updated_at' => now(), 'created_at' => now()]);
                    }
                    // Model olayları tetiklenmesin (görsel/yorum silen zincir yok, yine de doğrudan güncellenir).
                    DB::table('facilities')->where('id', $f->id)->update(['deleted_at' => now(), 'uzmana_tasindi_expert_id' => $e->id, 'updated_at' => now()]);
                });
            }
            $sayac['tasindi']++;
            $sayac['yonlendirme'] += count($eskiAdresler);
            $rapor && fputcsv($rapor, [$f->id, $ad, $il, $f->district, $gercek ? 'taşındı' : 'taşınacak', $expertId, '/doktor/'.$slug, implode(' ', array_map(fn ($s) => '/kurumlar/'.$s, $eskiAdresler))], ';');
        }
        $rapor && fclose($rapor);
        // Uzmanlar il/ilçe/branş sayfaları dağılımı 6 saat önbellekte (DoktorSeo::dagilim) - yeni branş hemen görünsün.
        $gercek && \Illuminate\Support\Facades\Cache::forget('doktor-seo:dagilim:v1');
        $this->info(($gercek ? 'YAZILDI' : 'DENEME').': '.json_encode($sayac, JSON_UNESCAPED_UNICODE));

        return self::SUCCESS;
    }

    private function geriAl(?int $ilId, bool $gercek): int
    {
        $uzmanlar = Expert::withTrashed()->where('source', self::KAYNAK)->when($ilId, fn ($w) => $w->where('city_id', $ilId))->get();
        foreach ($uzmanlar as $e) {
            if ($gercek) {
                DB::transaction(function () use ($e) {
                    DB::table('facilities')->where('uzmana_tasindi_expert_id', $e->id)->update(['deleted_at' => null, 'uzmana_tasindi_expert_id' => null, 'updated_at' => now()]);
                    DB::table('expert_slug_redirects')->where('expert_id', $e->id)->delete();
                    $e->forceDelete();
                });
            }
        }
        $gercek && \Illuminate\Support\Facades\Cache::forget('doktor-seo:dagilim:v1');
        $this->info(($gercek ? 'GERİ ALINDI' : 'DENEME').': '.$uzmanlar->count().' fizyoterapist kuruma döner.');

        return self::SUCCESS;
    }

    private function benzersizSlug(string $ad): string
    {
        do {
            $slug = Str::slug($ad).'-'.Str::random(5);
        } while (Expert::withTrashed()->where('slug', $slug)->exists());

        return $slug;
    }
}
