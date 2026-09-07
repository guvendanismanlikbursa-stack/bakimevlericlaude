{{--
  7 Eylul 2026: kullanicinin talebi - kurumlarin belirttigi GERCEK fiyat,
  suistimale (rakip toplama, teklif istemeden fiyat gormek) acikti. Artik
  sadece giris yapmis aileler gercek fiyati gorur; giris yapmamis ziyaretci
  SADECE segment rozetini (Ekonomik/Standart/Premium/Ultra Premium - bkz.
  price-tier-badge.blade.php, bu ZATEN herkese acikti, degismedi) gorur.
  Parametre: $class (opsiyonel, disaridan stil).
--}}
<a href="{{ brand_route('family.login') }}" class="{{ $class ?? 'text-gray-400 text-xs font-semibold' }} inline-flex items-center gap-1 hover:underline">🔒 Fiyat için giriş yapın</a>
