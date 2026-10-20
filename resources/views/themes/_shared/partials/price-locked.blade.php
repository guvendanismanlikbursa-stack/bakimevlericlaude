{{--
  7 Eylul 2026: kullanicinin talebi - kurumlarin belirttigi GERCEK fiyat,
  suistimale (rakip toplama, teklif istemeden fiyat gormek) acikti. Artik
  sadece giris yapmis aileler gercek fiyati gorur; giris yapmamis ziyaretci
  SADECE segment rozetini (Ekonomik/Standart/Premium/Ultra Premium - bkz.
  price-tier-badge.blade.php, bu ZATEN herkese acikti, degismedi) gorur.
  Parametre: $class (opsiyonel, disaridan stil).
  20 Ekim 2026: $inLink=true verilirse (kurum kartı gibi zaten bir bağlantının
  İÇİNDEYSE) <a> yerine <span> basılır - iç içe bağlantı geçersiz HTML'dir,
  tarayıcı kartı bölüyordu (PageSpeed: adsız bağlantı + küçük dokunma hedefi).
  Varsayılan renk gray-400 -> gray-500 (yetersiz kontrast uyarısı).
--}}
@if(! empty($inLink))
<span class="{{ $class ?? 'text-gray-500 text-xs font-semibold' }} inline-flex items-center gap-1">🔒 Fiyat için giriş yapın</span>
@else
<a href="{{ brand_route('family.login') }}" class="{{ $class ?? 'text-gray-500 text-xs font-semibold' }} inline-flex items-center gap-1 hover:underline">🔒 Fiyat için giriş yapın</a>
@endif
