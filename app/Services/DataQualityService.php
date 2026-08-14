<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

// 13 Agustos 2026: kullanicinin talebi - daha once bu denetimler sadece
// /_ops/{action} uzerinden (Bearer token'li API cagrisiyla) calistirilabiliyordu,
// admin panelinde tiklanabilir bir ekrani yoktu. Bu servis, OpsController'daki
// mevcut (kanitlanmis) sorgu mantigini TEK bir yerde toplar - hem OpsController
// (metin raporu) hem yeni Admin\DataQualityController (tablo/buton UI) AYNI
// mantigi kullanir, birbirinden sapmaz.
class DataQualityService
{
    // [hedef_slug, [anahtar kelimeler]] - sirali kontrol edilir, ilk eslesen kazanir
    private const MISCATEGORY_RULES = [
        // 14 Agustos 2026: canli sitede "ALTI YAS ALTI OKUL ONCESI EGITIM
        // MERKEZI" adinda bir kres, "okul oncesi" kelimesi listede olmadigi
        // icin Yasli Bakim Evi kategorisinde kalmis, ailelerin karsisina
        // yanlislikla huzurevi aramasinda cikmisti - anahtar kelime eklendi.
        ['kres-ve-anaokulu', ['kreş', 'anaokulu', 'ana okulu', 'okul öncesi']],
        ['cocuk-bakim-merkezi', ['çocuk bakım', 'çocuk gelişim', 'oyun evi', 'çocuk etkinlik', 'gündüz bakım evi', 'çocuk kulübü', 'çocuk evleri', 'çocuk yuvası', 'çocukevi', 'çocuk evi']],
        ['ozel-egitim-ve-gelisim-merkezi', ['özel eğitim', 'otizm', 'down sendrom', 'özel gereksinim', 'gelişim merkezi', 'ozel egitim uygulama okulu']],
        ['norolojik-rehabilitasyon-merkezi', ['nörolojik rehabilitasyon', 'inme sonrası', 'felç sonrası']],
        ['fizik-tedavi-ve-rehabilitasyon', ['fizik tedavi', 'fizyoterap', 'rehabilitasyon merkezi', 'rehabilitasyon ve']],
    ];

    private const NOT_A_CARE_FACILITY_KEYWORDS = [
        'psikolojik danışman', 'psikolog', 'psikiyatri', 'danışmanlık merkezi',
        'sağlık kabini', 'özel güvenlik eğitim', 'akademi', 'kurs merkezi', 'dershane',
    ];

    private const CARE_SELF_LABEL_KEYWORDS = ['huzurevi', 'yaşlı bakım', 'yasli bakim'];

    public function guessRealCategory(string $name): ?string
    {
        $n = Str::of($name)->lower()->ascii()->toString();

        foreach (self::CARE_SELF_LABEL_KEYWORDS as $kw) {
            if (str_contains($n, Str::of($kw)->lower()->ascii()->toString())) {
                return null;
            }
        }

        foreach (self::MISCATEGORY_RULES as [$slug, $keywords]) {
            foreach ($keywords as $kw) {
                if (str_contains($n, Str::of($kw)->lower()->ascii()->toString())) {
                    return $slug;
                }
            }
        }

        return null;
    }

    public function looksLikeNonCareFacility(string $name): bool
    {
        $n = Str::of($name)->lower()->ascii()->toString();
        foreach (self::NOT_A_CARE_FACILITY_KEYWORDS as $kw) {
            if (str_contains($n, Str::of($kw)->lower()->ascii()->toString())) {
                return true;
            }
        }

        return false;
    }

    public function cleanFacilityName(string $name): string
    {
        $clean = trim($name);
        $clean = preg_replace('/\s+/u', ' ', $clean);

        return rtrim($clean, ", \t\n\r");
    }

    /** @return array{scanned:int, reassignable:array, nonCare:array} */
    public function miscategoryScan(): array
    {
        $rows = DB::table('facilities as f')
            ->join('facility_categories as fc', 'fc.id', '=', 'f.facility_category_id')
            ->join('cities as c', 'c.id', '=', 'f.city_id')
            ->whereIn('fc.slug', ['yasli-bakim-evi', 'huzurevi'])
            ->whereNull('f.deleted_at')
            ->select('f.id', 'f.name', 'c.name as sehir', 'fc.slug as mevcut_kategori')
            ->get();

        $reassignable = [];
        $nonCare = [];
        foreach ($rows as $r) {
            $guess = $this->guessRealCategory($r->name);
            if ($guess) {
                $reassignable[] = ['facility' => $r, 'target' => $guess];
                continue;
            }
            if ($this->looksLikeNonCareFacility($r->name)) {
                $nonCare[] = $r;
            }
        }

        return ['scanned' => $rows->count(), 'reassignable' => $reassignable, 'nonCare' => $nonCare];
    }

    /** @return array{fixed:int, byTarget:array<string,int>} */
    public function miscategoryFix(): array
    {
        $categoryIds = DB::table('facility_categories')->pluck('id', 'slug');

        $rows = DB::table('facilities as f')
            ->join('facility_categories as fc', 'fc.id', '=', 'f.facility_category_id')
            ->whereIn('fc.slug', ['yasli-bakim-evi', 'huzurevi'])
            ->whereNull('f.deleted_at')
            ->select('f.id', 'f.name')
            ->get();

        $byTarget = [];
        foreach ($rows as $r) {
            $guess = $this->guessRealCategory($r->name);
            if (! $guess || ! isset($categoryIds[$guess])) {
                continue;
            }

            DB::table('facilities')->where('id', $r->id)->update([
                'facility_category_id' => $categoryIds[$guess],
                'updated_at' => now(),
            ]);
            $byTarget[$guess] = ($byTarget[$guess] ?? 0) + 1;
        }

        return ['fixed' => array_sum($byTarget), 'byTarget' => $byTarget];
    }

    /** @return array{checked:int, mismatches:array} */
    public function phoneTypeAudit(?string $citySlug = null): array
    {
        $rows = DB::table('facilities as f')
            ->join('cities as c', 'c.id', '=', 'f.city_id')
            ->whereIn('f.ownership_type', ['ozel', 'vakif'])
            ->whereNull('f.deleted_at')
            ->when($citySlug, fn ($q) => $q->where('c.slug', $citySlug))
            ->select('f.id', 'f.name', 'f.phone', 'f.phone_type', 'f.invitation_status', 'c.name as sehir')
            ->get();

        $mismatches = [];
        foreach ($rows as $r) {
            $computed = classify_phone_type($r->phone);
            $stored = $r->phone_type ?: 'none';
            if ($computed !== $stored) {
                $mismatches[] = ['facility' => $r, 'stored' => $stored, 'computed' => $computed];
            }
        }

        return ['checked' => $rows->count(), 'mismatches' => $mismatches];
    }

    /** @return array{fixedPhoneType:int, fixedStatus:int} */
    public function phoneTypeFix(?string $citySlug = null): array
    {
        $autoStatuses = ['not_started', 'landline_only', 'contact_missing'];

        $rows = DB::table('facilities as f')
            ->join('cities as c', 'c.id', '=', 'f.city_id')
            ->whereIn('f.ownership_type', ['ozel', 'vakif'])
            ->whereNull('f.deleted_at')
            ->when($citySlug, fn ($q) => $q->where('c.slug', $citySlug))
            ->select('f.id', 'f.phone', 'f.phone_type', 'f.invitation_status')
            ->get();

        $fixedPhoneType = 0;
        $fixedStatus = 0;

        foreach ($rows as $r) {
            $computed = classify_phone_type($r->phone);
            $stored = $r->phone_type ?: 'none';
            if ($computed === $stored) {
                continue;
            }

            $update = ['phone_type' => $computed, 'updated_at' => now()];

            if (in_array($r->invitation_status, $autoStatuses, true)) {
                $update['invitation_status'] = match ($computed) {
                    'mobile' => 'not_started',
                    'landline' => 'landline_only',
                    default => 'contact_missing',
                };
                $update['invitation_status_at'] = now();
                $fixedStatus++;
            }

            DB::table('facilities')->where('id', $r->id)->update($update);
            $fixedPhoneType++;
        }

        return ['fixedPhoneType' => $fixedPhoneType, 'fixedStatus' => $fixedStatus];
    }

    /** @return array */
    public function ownershipAudit(): array
    {
        return DB::table('facilities as f')
            ->join('cities as c', 'c.id', '=', 'f.city_id')
            ->where('f.ownership_type', 'kamu')
            ->whereNull('f.deleted_at')
            ->whereRaw('LOWER(f.name) LIKE ?', ['%özel%'])
            ->select('f.id', 'f.name', 'c.name as sehir')
            ->orderBy('c.name')
            ->get()
            ->all();
    }

    public function ownershipFix(int $id, string $type): array
    {
        if (! in_array($type, ['ozel', 'kamu', 'belediye', 'vakif'], true)) {
            return ['ok' => false, 'message' => "Geçersiz tür '{$type}'"];
        }

        $facility = DB::table('facilities')->where('id', $id)->first();
        if (! $facility) {
            return ['ok' => false, 'message' => "#{$id} bulunamadı"];
        }

        $update = ['ownership_type' => $type, 'updated_at' => now()];

        if (in_array($type, ['ozel', 'vakif'], true) && $facility->invitation_status === 'excluded') {
            $phoneType = classify_phone_type($facility->phone);
            $update['invitation_status'] = match ($phoneType) {
                'mobile' => 'not_started',
                'landline' => 'landline_only',
                default => 'contact_missing',
            };
            $update['invitation_status_at'] = now();
        }

        DB::table('facilities')->where('id', $id)->update($update);

        return ['ok' => true, 'message' => "#{$id} {$facility->name} -> {$type}"];
    }

    /** @return array{checked:int, bothEmpty:int, onlyFkFilled:int, mismatch:int, consistent:int, examples:array} */
    public function districtAudit(?string $citySlug = null): array
    {
        $rows = DB::table('facilities as f')
            ->join('cities as c', 'c.id', '=', 'f.city_id')
            ->leftJoin('districts as d', 'd.id', '=', 'f.district_id')
            ->whereNull('f.deleted_at')
            ->when($citySlug, fn ($q) => $q->where('c.slug', $citySlug))
            ->select('f.id', 'f.name', 'f.district as district_text', 'd.name as district_fk', 'c.name as sehir')
            ->get();

        $bothEmpty = 0;
        $onlyFkFilled = 0;
        $mismatch = 0;
        $consistent = 0;
        $examples = [];

        foreach ($rows as $r) {
            $text = trim((string) $r->district_text);
            $fk = trim((string) $r->district_fk);

            if ($text === '' && $fk === '') {
                $bothEmpty++;
            } elseif ($text === '' && $fk !== '') {
                $onlyFkFilled++;
                $examples[] = ['facility' => $r, 'reason' => 'sadece_fk'];
            } elseif ($text !== '' && $fk !== '' && mb_strtolower($text) !== mb_strtolower($fk)) {
                $mismatch++;
                $examples[] = ['facility' => $r, 'reason' => 'uyumsuz'];
            } else {
                $consistent++;
            }
        }

        return [
            'checked' => $rows->count(), 'bothEmpty' => $bothEmpty, 'onlyFkFilled' => $onlyFkFilled,
            'mismatch' => $mismatch, 'consistent' => $consistent, 'examples' => array_slice($examples, 0, 30),
        ];
    }

    public function districtFix(): array
    {
        $rows = DB::table('facilities as f')
            ->leftJoin('districts as d', 'd.id', '=', 'f.district_id')
            ->whereNull('f.deleted_at')
            ->whereNotNull('f.district_id')
            ->where(function ($q) {
                $q->whereNull('f.district')->orWhere('f.district', '');
            })
            ->select('f.id', 'd.name as district_fk')
            ->get();

        $fixed = 0;
        foreach ($rows as $r) {
            if (! $r->district_fk) {
                continue;
            }
            DB::table('facilities')->where('id', $r->id)->update([
                'district' => $r->district_fk,
                'updated_at' => now(),
            ]);
            $fixed++;
        }

        return ['fixed' => $fixed];
    }

    /** @return array{count:int, examples:array} */
    public function nameCleanupAudit(): array
    {
        $rows = DB::table('facilities')->whereNull('deleted_at')->select('id', 'name')->get();

        $examples = [];
        foreach ($rows as $r) {
            $clean = $this->cleanFacilityName($r->name);
            if ($clean !== $r->name) {
                $examples[] = ['id' => $r->id, 'old' => $r->name, 'new' => $clean];
            }
        }

        return ['count' => count($examples), 'examples' => $examples];
    }

    public function nameCleanupFix(): array
    {
        $rows = DB::table('facilities')->whereNull('deleted_at')->select('id', 'name')->get();

        $fixed = 0;
        foreach ($rows as $r) {
            $clean = $this->cleanFacilityName($r->name);
            if ($clean !== $r->name) {
                DB::table('facilities')->where('id', $r->id)->update(['name' => $clean, 'updated_at' => now()]);
                $fixed++;
            }
        }

        return ['fixed' => $fixed];
    }
}
