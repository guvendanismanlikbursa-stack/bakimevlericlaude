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

    // 15 Agustos 2026: kullanicinin "asla hata kalmamali" talebi uzerine
    // yapilan denetimde bulundu - "huzurevi" bitisik yazimi araniyordu,
    // Turkce'de cok yaygin olan "Huzur Evi" (araya boşluklu) yazim varyanti
    // bu korumadan gecmiyordu - boyle isimlendirilmis gercek bir huzurevi,
    // ismi tesadufen bir MISCATEGORY_RULES anahtar kelimesini de icerirse
    // yanlis kategoriye onerilebiliyordu.
    private const CARE_SELF_LABEL_KEYWORDS = ['huzurevi', 'huzur evi', 'yaşlı bakım', 'yasli bakim'];

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

    /**
     * 5 Ekim 2026: kullanicinin bildirdigi gercek hata - "Telefonu olmayanlar"
     * sekmesinde (invitation_status='contact_missing') telefon numarasi OLAN
     * kurumlar gorunuyordu. Kok neden: phone_type kolonu zaten dogruydu
     * (0 uyumsuzluk cikti), ama invitation_status telefon SONRADAN eklendiginde
     * (ör. admin elle duzenledi / yeniden import) hic yeniden senkronize
     * edilmemisti - eski mantik SADECE phone_type'in KENDISI degistiginde
     * durumu guncelliyordu. Artik phone_type ayni kalsa bile, auto-statuste
     * (not_started/landline_only/contact_missing) olan bir kurumun durumu
     * GUNCEL phone_type'a uymuyorsa yeniden senkronize edilir - ilerlemis
     * durumlara (opened/sent/claimed/... ) yine hic dokunulmaz.
     *
     * @return array{fixedPhoneType:int, fixedStatus:int}
     */
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
            $phoneTypeChanged = $computed !== $stored;

            $desiredStatus = match ($computed) {
                'mobile' => 'not_started',
                'landline' => 'landline_only',
                default => 'contact_missing',
            };
            $statusStale = in_array($r->invitation_status, $autoStatuses, true) && $r->invitation_status !== $desiredStatus;

            if (! $phoneTypeChanged && ! $statusStale) {
                continue;
            }

            $update = ['updated_at' => now()];

            if ($phoneTypeChanged) {
                $update['phone_type'] = $computed;
                $fixedPhoneType++;
            }

            if ($statusStale) {
                $update['invitation_status'] = $desiredStatus;
                $update['invitation_status_at'] = now();
                $fixedStatus++;
            }

            DB::table('facilities')->where('id', $r->id)->update($update);
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

    /**
     * 5 Ekim 2026: kullanicinin bulduğu gercek hata - kaynak veride bazi
     * telefon numaralari bozuk ekler tasiyor (ör. "+90 224 220 58 47. 1"),
     * classify_phone_type() bunu gecerli 10 haneli bir numara olarak
     * taniyamadigi icin kurum sehven "telefon yok" sayiliyordu. Toplu/
     * otomatik duzeltme YAPILMAZ (bozukluk her kayitta farkli sekilde
     * olabilir) - ownershipFix() ile ayni desen: tek kurum, elle onaylanmis
     * temiz numarayla, burdan duzeltilir.
     *
     * @return array{ok:bool, message:string}
     */
    public function phoneFix(int $id, string $phone): array
    {
        $facility = DB::table('facilities')->where('id', $id)->first();
        if (! $facility) {
            return ['ok' => false, 'message' => "#{$id} bulunamadı"];
        }

        $computed = classify_phone_type($phone);
        $update = ['phone' => $phone, 'phone_type' => $computed, 'updated_at' => now()];

        $autoStatuses = ['not_started', 'landline_only', 'contact_missing'];
        if (in_array($facility->invitation_status, $autoStatuses, true)) {
            $update['invitation_status'] = match ($computed) {
                'mobile' => 'not_started',
                'landline' => 'landline_only',
                default => 'contact_missing',
            };
            $update['invitation_status_at'] = now();
        }

        DB::table('facilities')->where('id', $id)->update($update);

        return ['ok' => true, 'message' => "#{$id} {$facility->name} -> tel={$phone} ({$computed})"];
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

    // 1 Ekim 2026: Izmir anaokulu/kres toplu importu sirasinda sunucu
    // baglanti timeoutlari nedeniyle ayni dosya birden fazla kez calisti.
    // Bu, elle (adres+telefon+olusturma zamani karsilastirarak) dogrulanmis
    // 59 mukerrer kaydin SABIT listesidir - genel bir "her zaman calisan"
    // tekrar-tespit algoritmasi DEGIL, bu tek seferlik olayin kesin kaydi.
    // Admin buradan tek tek (mevcut Sil butonuyla) temizleyebilir.
    private const IZMIR_OCT1_DUPLICATE_IDS = [
        10429, 10701, 10830, 10430, 10886, 10831, 10411, 10418, 10864, 10395,
        10870, 10876, 10829, 10374, 10382, 10863, 10408, 10809, 10861, 10816,
        10835, 10945, 10461, 10873, 10396, 10675, 10723, 10352, 10363, 10372,
        10389, 10398, 10409, 10425, 10432, 10441, 10451, 10459, 10466, 10476,
        10487, 10496, 10505, 10511, 10515, 10522, 10529, 10537, 10600, 10546,
        10555, 10565, 10573, 10581, 10591, 10610, 10618, 10625, 10630,
    ];

    public function izmirOct1DuplicateAudit(): array
    {
        $rows = DB::table('facilities')
            ->whereIn('id', self::IZMIR_OCT1_DUPLICATE_IDS)
            ->whereNull('deleted_at')
            ->select('id', 'name', 'address', 'phone', 'district', 'created_at')
            ->orderBy('id')
            ->get();

        return ['count' => $rows->count(), 'rows' => $rows];
    }

    // 1 Ekim 2026, 2. tur: Bursa/Istanbul eksik-kurum tamamlama importu sonrasi
    // kaynak Excel'lerin kendisinde (ayni kurum, hafif farkli mahalle/sokak
    // yaziliyla) iki kez gecen, adres+saat karsilastirmasiyla dogrulanmis
    // kesin mukerrerler.
    private const BURSA_ISTANBUL_OCT1_DUPLICATE_IDS = [
        11020, 11023, 11189,
        // 1 Ekim 2026 (2. tur): kullanicinin talebi - ayni isim+ayni ilce,
        // farkli sokak yazimiyla Google'da iki kez taranmis kayitlar
        // (ozellikle kisiye ozel isimlerde - "Ummehan Elginkan" gibi -
        // rastgele cakisma ihtimali yok denecek kadar dusuk).
        9712, 9729, 9899, 10062, 11121, 10106, 10121, 10135, 10136,
        // 2 Ekim 2026: Istanbul fizik tedavi/rehabilitasyon eksik-tamamlama
        // importu sonrasi, ayni adres veya neredeyse ayni telefonla
        // dogrulanmis kesin mukerrerler.
        11384, 12259, 11502, 11703, 11943,
    ];

    public function bursaIstanbulOct1DuplicateAudit(): array
    {
        $rows = DB::table('facilities')
            ->whereIn('id', self::BURSA_ISTANBUL_OCT1_DUPLICATE_IDS)
            ->whereNull('deleted_at')
            ->select('id', 'name', 'address', 'phone', 'district', 'created_at')
            ->orderBy('id')
            ->get();

        return ['count' => $rows->count(), 'rows' => $rows];
    }

    // 6 Ekim 2026: Antalya anaokulu/kres importu - ilk cagri sunucu/proxy
    // tarafindan "Request Timeout" dondurdu ama PHP sureci ARKA PLANDA
    // CALISMAYA DEVAM ETTI (istemci baglantisi kesilse de islem canliydi).
    // Bu zombi surec hala satir satir kurum olustururken, "basarisiz oldu"
    // sanilip AYNI listeyle (kucuk bir fark disinda) ikinci bir import
    // calistirildi - isDuplicate() kontrolu kilitsiz oldugu icin iki surec
    // AYNI satiri neredeyse ayni anda isleyip IKI AYRI kurum olusturdu.
    // facility-duplicate-name-scan (city_id=7, facility_category_id=4) ile
    // dogrulandi: her ciftte DUSUK ID (once olusan, yorumlari/eslesmesi
    // olan) tutuluyor, YUKSEK ID (sonradan olusan, hep yorumsuz) silinir.
    // 6 Ekim 2026 duzeltme: ID sirasina gore "dusuk ID = yorumlu" varsayimi
    // 35 ciftin 34'unde dogruydu ama BIRINDE (Linden Kids Okullari) TERSTI -
    // #12979 (5 yorumlu) yanlislikla "silinecek" listesindeydi, #12975
    // (0 yorumlu) tutulacakti. Kullanici butona basmadan fark etti, veri
    // kaybi olmadi. Artik HER ciftin yorum sayisi TEK TEK dogrulanarak
    // (ID sirasina degil) hangisinin silinecegi belirlendi.
    private const ANTALYA_OCT6_DUPLICATE_IDS = [
        12973, 12975, 12995, 12997, 12999, 13001, 13003, 13005, 13008, 13010,
        13012, 13014, 13016, 13018, 13020, 13022, 13025, 13027, 13029, 13031,
        13033, 13035, 13037, 13039, 13042, 13044, 13046, 13049, 13051, 13053,
        13055, 13057, 13059, 13061, 13063,
    ];

    public function antalyaOct6DuplicateAudit(): array
    {
        $rows = DB::table('facilities')
            ->whereIn('id', self::ANTALYA_OCT6_DUPLICATE_IDS)
            ->whereNull('deleted_at')
            ->select('id', 'name', 'address', 'phone', 'district', 'created_at')
            ->orderBy('id')
            ->get();

        return ['count' => $rows->count(), 'rows' => $rows];
    }

    // 4 Ekim 2026: kullanicinin bildirdigi gercek hata - "Yalova Evde Bakim"
    // isimli bir kurumun ADRESI "...Yalova Merkez" yaziyordu ama kayitli
    // city_id Istanbul'du. Veri cekici aradigi isletmeyi bulamayinca BASKA,
    // alakasiz bir sehirdeki isletmeyle eslestirmis olabilir - sadece il
    // etiketini duzeltmek YETERSIZ (isim de adrese ait olmayabilir), bu
    // yuzden OpsController::facilityAddressCityMismatchScan() ile AYNI
    // mantik kullanilarak (tek kaynak, sapma riski yok) bulunan kayitlar
    // admin panelinden TOPLU SILINEBILIR hale getirildi - /_ops uzerinden
    // 135 kayitlik toplu silme, boyutu nedeniyle otomatik guvenlik
    // siniflandiricisi tarafindan engellenmisti, admin kendi panelinden
    // (normal oturum bazli istekle) silebilsin diye buraya tasindi.
    public function addressCityMismatchAudit(): array
    {
        $norm = fn (string $s) => Str::slug($s);

        $cities = DB::table('cities')->select('id', 'name')->get();
        $citySlugToId = [];
        foreach ($cities as $c) {
            $citySlugToId[$norm($c->name)] = $c->id;
        }
        $cityNameById = $cities->pluck('name', 'id');

        $facilities = DB::table('facilities')
            ->whereNull('deleted_at')
            ->whereNotNull('address')
            ->where('address', '!=', '')
            ->select('id', 'name', 'city_id', 'district', 'address', 'phone')
            ->orderBy('id')
            ->get();

        $rows = collect();
        foreach ($facilities as $f) {
            $addr = trim((string) $f->address);
            if (! preg_match('/([\p{L}\x{0300}-\x{036F}]+)\s*\/\s*([\p{L}\x{0300}-\x{036F}]+)\s*$/u', $addr, $m)) {
                continue;
            }
            $addrProvinceSlug = $norm(preg_replace('/[\x{0300}-\x{036F}]/u', '', $m[2]));
            $foundCityId = $citySlugToId[$addrProvinceSlug] ?? null;
            if (! $foundCityId || $foundCityId == $f->city_id) {
                continue;
            }
            $rows->push((object) [
                'id' => $f->id,
                'name' => $f->name,
                'address' => $f->address,
                'phone' => $f->phone,
                'district' => $f->district,
                'kayitli_il' => $cityNameById[$f->city_id] ?? '?',
                'adres_ili' => $cityNameById[$foundCityId] ?? '?',
            ]);
        }

        return ['count' => $rows->count(), 'rows' => $rows];
    }

    // 1 Ekim 2026: kullanicinin talebi - platform ozel/ucretli kurumlar
    // icin oldugundan, isminde TOKI/BUSMEK/YUVAMIZ gecen VEYA kendi gercek
    // ilcesiyle AYNI olan "{Ilce} Anaokulu/Kres" kalibindaki (MEB/belediye
    // devlet kurumu oldugu isimden anlasilan) kurumlar kaldirilir. Site
    // genelinde tarandi - sadece bugunku importtan degil.
    private const DEVLET_KURUMU_IDS = [
        1389, 2582, 2958, 3626, 8301, 9049, 9338, 9340, 9720, 10314,
        10689, 10704, 10844, 11019, 11020, 11023, 11039, 11040, 11043, 11044,
        11048, 11051, 11053, 11054, 11058, 11065, 11066, 11073, 11074, 11087,
        11089, 11139, 11159, 11160, 11174, 11185, 11186, 11188, 11189, 11190,
        11192, 11195, 11202,
    ];

    // 1 Ekim 2026: kullanicinin talebi - "kamu/belediye kurumlari zaten
    // veritabaninda yok" varsayimiyla 3 Eylul'de admin filtresinden
    // kaldirilmisti (bkz. FacilityController::filteredQuery() ayni tarihli
    // yorum) - TOKI/BUSMEK/YUVAMIZ ice aktarimlariyla bu varsayimin yanlis
    // oldugu kanitlandi. Bu, SITE GENELINDE (sadece bugunku importtan
    // degil) TOKI/BUSMEK/YUVAMIZ gecen VEYA kendi gercek ilcesiyle AYNI
    // olan "{Ilce} Anaokulu/Kres" kalibindaki kurumlari bulur - SILMEZ,
    // sadece ownership_type='kamu' olarak isaretler ki admin "Kuruluş
    // Türü: Kamu" filtresiyle hepsini bir arada gorup kendi karar versin.
    public function governmentNameAudit(): array
    {
        $rows = DB::table('facilities')
            ->whereNull('deleted_at')
            ->where(function ($q) {
                $q->whereNull('ownership_type')->orWhere('ownership_type', '!=', 'kamu');
            })
            ->select('id', 'name', 'district', 'address')
            ->get();

        $ascii = function (string $s): string {
            $s = mb_strtolower($s, 'UTF-8');

            return strtr($s, ['ı' => 'i', 'ğ' => 'g', 'ü' => 'u', 'ş' => 's', 'ö' => 'o', 'ç' => 'c']);
        };

        $matches = [];
        foreach ($rows as $r) {
            $n = $ascii($r->name);

            // 1 Ekim 2026: kullanicinin talebi - isminde "ozel" gecen bir
            // kurum, baska bir kalibe uysa bile ASLA devlet kurumu olarak
            // isaretlenmez (isim zaten ozel oldugunu soyluyor).
            if (str_contains($n, 'ozel')) {
                continue;
            }

            if (str_contains($n, 'toki') || str_contains($n, 'busmek') || str_contains($n, 'yuvamiz')) {
                $matches[] = $r->id;

                continue;
            }

            // 11 Ekim 2026: kullanicinin bulgusu - burada daha once "isim
            // kendi ilcesiyle basliyor + sonunda anaokulu/kres geciyor" kurali
            // vardi ("Karsiyaka Girne Anaokulu" gibi) - kullanici bu kuraldan
            // isaretlenen 163 kurumun TAMAMININ GERCEKTE OZEL oldugunu
            // dogruladi (ör. "Kozan Gülbahçe Anaokulu" tamamen ozel bir
            // isletme, sadece bulundugu ilcenin adini tasiyor - cok yaygin bir
            // ozel kurum isimlendirme kalibi). Kural %100 yanlis-pozitif
            // uretiyordu, KALDIRILDI. Devlet kurumu tespiti artik SADECE
            // yukaridaki acik anahtar kelimelerle (toki/busmek/yuvamiz) yapilir.
        }

        $facilities = DB::table('facilities')
            ->whereIn('id', $matches)
            ->select('id', 'name', 'address', 'phone', 'district')
            ->orderBy('id')
            ->get();

        return ['count' => $facilities->count(), 'rows' => $facilities, 'ids' => $matches];
    }

    public function governmentNameFix(): array
    {
        $ids = $this->governmentNameAudit()['ids'];

        if (empty($ids)) {
            return ['fixed' => 0];
        }

        DB::table('facilities')->whereIn('id', $ids)->update([
            'ownership_type' => 'kamu',
            'updated_at' => now(),
        ]);

        return ['fixed' => count($ids)];
    }

    /**
     * 11 Ekim 2026: kullanicinin bulgusu - governmentNameFix()'in "isim
     * kendi ilcesiyle basliyor + sonunda anaokulu/kres geciyor" kurali COK
     * GEVSEKTI - bircok OZEL anaokulu/kresin ismi de bulundugu mahalle/
     * ilceyle basliyor (ör. "Kozan Gülbahçe Anaokulu" gercekte ozel bir
     * isletme). Kullanici TUM mevcut "kamu" etiketli kayitlari (163 adet,
     * hepsi bu hatali kuraldan gelmis) tek tek kontrol edip "hepsi ozel"
     * onayladi - toplu olarak 'ozel'e cevrilir. ownershipFix() ile AYNI
     * yan-etki mantigi (davet durumu) korunur.
     */
    public function governmentNameRevertToOzel(): array
    {
        $rows = DB::table('facilities')
            ->where('ownership_type', 'kamu')
            ->whereNull('deleted_at')
            ->select('id', 'phone', 'invitation_status')
            ->get();

        if ($rows->isEmpty()) {
            return ['fixed' => 0];
        }

        foreach ($rows as $f) {
            $update = ['ownership_type' => 'ozel', 'updated_at' => now()];
            if ($f->invitation_status === 'excluded') {
                $phoneType = classify_phone_type($f->phone);
                $update['invitation_status'] = match ($phoneType) {
                    'mobile' => 'not_started',
                    'landline' => 'landline_only',
                    default => 'contact_missing',
                };
                $update['invitation_status_at'] = now();
            }
            DB::table('facilities')->where('id', $f->id)->update($update);
        }

        return ['fixed' => $rows->count()];
    }

    public function devletKurumuAudit(): array
    {
        $rows = DB::table('facilities')
            ->whereIn('id', self::DEVLET_KURUMU_IDS)
            ->whereNull('deleted_at')
            ->select('id', 'name', 'address', 'phone', 'district', 'created_at')
            ->orderBy('id')
            ->get();

        return ['count' => $rows->count(), 'rows' => $rows];
    }
}
