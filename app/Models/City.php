<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class City extends Model
{
    protected $fillable = ['name', 'slug'];

    public function facilities()
    {
        return $this->hasMany(Facility::class);
    }

    public function districts()
    {
        return $this->hasMany(District::class);
    }

    /**
     * 10 Eylul 2026: kullanicinin talebi - paylasilan barindirmanin 300
     * baglanti siniri, trafik yogunlugunda "Too many connections" hatasina
     * yol aciyor (hosting firmasi sinirı artiramiyor). Cities tablosu ~81
     * satir ve neredeyse HIC degismiyor; her sayfa yuklemesinde slug'a gore
     * ayri ayri sorgulanmasi gereksiz DB yuku. Butun tabloyu 6 saatlik
     * dosya-onbellegine alip slug aramalarini bellekten yapiyoruz - admin
     * sehir ekler/duzenlerse Admin\CityController onbellegi temizler,
     * ayrica /_ops/cache-refresh de temizler.
     */
    public static function cachedAll()
    {
        return Cache::remember('cities:all:v1', now()->addHours(6), fn () => static::orderBy('name')->get());
    }

    public static function findBySlugCached(?string $slug): ?self
    {
        if (! filled($slug)) {
            return null;
        }

        return static::cachedAll()->firstWhere('slug', $slug);
    }

    public static function forgetCache(): void
    {
        Cache::forget('cities:all:v1');
    }
}
