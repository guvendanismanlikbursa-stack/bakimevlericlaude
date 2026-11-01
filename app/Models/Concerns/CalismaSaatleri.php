<?php

namespace App\Models\Concerns;

/**
 * 30 Ekim 2026 (kurumlar), 1 Kasım 2026 (doktorlar): çalışma saatleri yardımcıları.
 * Modelde 'opening_hours' (gün adı => saat metni) dizisi olmalı.
 */
trait CalismaSaatleri
{
    // Pazartesi'den Pazar'a sıralı.
    public const GUNLER = ['Pazartesi', 'Salı', 'Çarşamba', 'Perşembe', 'Cuma', 'Cumartesi', 'Pazar'];

    public const GUN_KISA = ['Pzt', 'Sal', 'Çar', 'Per', 'Cum', 'Cmt', 'Paz'];

    /**
     * Gün => saat metni (sıralı, sadeleştirilmiş). Veri eksikse ya da 7 günün
     * hepsi "Kapalı" ise (çoğunlukla hatalı kayıt) boş dizi döner.
     *
     * @return array<string, string>
     */
    public function openingHoursRows(): array
    {
        $ham = is_array($this->opening_hours) ? $this->opening_hours : [];
        $satirlar = [];
        foreach (self::GUNLER as $gun) {
            $deger = trim((string) ($ham[$gun] ?? ''));
            if ($deger === '') {
                return [];
            }
            $deger = preg_replace('/\s*-\s*/u', '–', $deger);
            $deger = preg_replace('/\s*,\s*/u', ', ', $deger);
            $satirlar[$gun] = mb_strtolower($deger) === '24 saat açık' ? '24 saat açık' : $deger;
        }
        $kapali = count(array_filter($satirlar, fn ($v) => $v === 'Kapalı'));

        return $kapali === 7 ? [] : $satirlar;
    }

    /** Kart için tek satırlık özet: "Pzt–Cum 08:30–17:30 · Cmt–Paz Kapalı". */
    public function openingHoursSummary(int $enFazlaGrup = 3): ?string
    {
        $satirlar = array_values($this->openingHoursRows());
        if (! $satirlar) {
            return null;
        }
        if (count(array_unique($satirlar)) === 1) {
            return $satirlar[0] === '24 saat açık' ? '7 gün 24 saat açık' : 'Her gün '.$satirlar[0];
        }
        $gruplar = [];
        foreach ($satirlar as $i => $deger) {
            $son = count($gruplar) - 1;
            if ($son >= 0 && $gruplar[$son]['deger'] === $deger) {
                $gruplar[$son]['bitis'] = $i;
            } else {
                $gruplar[] = ['bas' => $i, 'bitis' => $i, 'deger' => $deger];
            }
        }
        $parcalar = array_map(fn ($g) => self::GUN_KISA[$g['bas']].($g['bitis'] > $g['bas'] ? '–'.self::GUN_KISA[$g['bitis']] : '').' '.$g['deger'], $gruplar);
        if (count($parcalar) > $enFazlaGrup) {
            return implode(' · ', array_slice($parcalar, 0, $enFazlaGrup)).' …';
        }

        return implode(' · ', $parcalar);
    }
}
