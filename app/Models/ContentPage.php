<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class ContentPage extends Model
{
    protected $fillable = ['brand', 'type', 'title', 'summary', 'slug', 'body', 'cover_path', 'category', 'published_at'];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    public function scopePages($query)
    {
        return $query->where('type', 'page');
    }

    public function scopeGuides($query)
    {
        return $query->where('type', 'guide');
    }

    /** 5 Kasım 2026: yayın tarihi gelmemiş rehber yazıları sitede görünmez. */
    public function scopeYayinda($query)
    {
        return $query->where(fn ($q) => $q->whereNull('published_at')->orWhere('published_at', '<=', now()));
    }

    public function yayindaMi(): bool
    {
        return ! $this->published_at || $this->published_at->lte(now());
    }

    public function kategoriAdi(): ?string
    {
        return $this->category ? (config('rehber.kategoriler.'.$this->brand.'.'.$this->category) ?: null) : null;
    }

    /**
     * 5 Kasım 2026: kurum / şehir rehberi sayfası için konuyla ilgili rehber yazıları.
     * Her konudan bir yazı; $tohum (ör. kurum id) farklı sayfaların farklı yazılara bağlanmasını sağlar.
     * Yazı başka bir sitedeyse bağlantı o sitenin adresine gider.
     *
     * @return Collection<int, array{baslik: string, url: string, kapak: ?string}>
     */
    public static function kapsamIcinYazilar(?string $kapsam, int $tohum, int $adet = 3): Collection
    {
        $esle = $kapsam ? config('rehber.kurum_eslesme.'.$kapsam) : null;
        if (! $esle) {
            return collect();
        }
        [$marka, $konular] = $esle;
        $gruplar = Cache::remember('rehber:kapsam:'.$marka.':'.implode(',', $konular), now()->addHour(), function () use ($marka, $konular) {
            return static::guides()->yayinda()->where('brand', $marka)->whereIn('category', $konular)->whereNotNull('cover_path')
                ->orderBy('id')->get(['slug', 'title', 'category', 'cover_path', 'updated_at'])
                ->groupBy('category')
                ->map(fn ($g) => $g->map(fn ($p) => ['slug' => $p->slug, 'baslik' => $p->title, 'kapak' => $p->cover_path.'?v='.substr(md5((string) $p->updated_at), 0, 6)])->values()->all())
                ->all();
        });
        $ayniSite = (current_brand()['slug'] ?? null) === $marka;
        $kok = $ayniSite ? null : 'https://'.(config('brands.brands.'.$marka.'.domains')[0] ?? $marka.'.com');
        $sonuc = collect();
        foreach ($konular as $i => $konu) {
            $liste = $gruplar[$konu] ?? [];
            if (! $liste || $sonuc->count() >= $adet) {
                continue;
            }
            $y = $liste[($tohum + $i * 7) % count($liste)];
            $sonuc->push([
                'baslik' => $y['baslik'],
                'url' => $ayniSite ? brand_route('pages.show', ['slug' => $y['slug']]) : $kok.'/sayfa/'.$y['slug'],
                'kapak' => $ayniSite ? asset($y['kapak']) : $kok.'/'.$y['kapak'],
            ]);
        }

        return $sonuc;
    }

    public function kapakUrl(): ?string
    {
        return $this->cover_path ? asset($this->cover_path).'?v='.substr(md5((string) $this->updated_at), 0, 6) : null;
    }
}
