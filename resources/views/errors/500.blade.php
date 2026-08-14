<!DOCTYPE html>
<html lang="tr">
@php
  // 14 Agustos 2026: kullanicinin talebi - ozel 500 sayfasi. Bu sayfa TAM
  // DB coktugu bir anda bile render edilebilmeli - bu yuzden bilerek
  // layouts.brand'i EXTENDS etmiyor (o layout'un View::composer'i
  // FacilityUser/FamilyUser sorgusu yaptigi icin DB coktuyse o da patlar,
  // Laravel'in cirkin varsayilan hata sayfasina geri duserdik). current_brand()
  // sadece config okur, DB'ye hic dokunmaz - guvenli.
  try {
    $brand = current_brand();
  } catch (\Throwable $e) {
    $brand = null;
  }
  $primary = $brand['primary_color'] ?? '#0b5d8c';
@endphp
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Bir şeyler ters gitti @if($brand) | {{ $brand['name'] }}@endif</title>
  <meta name="robots" content="noindex">
  @vite('resources/css/app.css')
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center px-4">
  <div class="max-w-md w-full text-center">
    <div class="text-5xl mb-4">⚠️</div>
    <h1 class="text-xl font-black text-gray-950 mb-2">Sistemde teknik bir hata oluştu</h1>
    <p class="text-sm text-gray-500 mb-8">Bu durumdan otomatik olarak haberdar olduk ve inceliyoruz. Lütfen birkaç dakika sonra tekrar deneyin.</p>
    <a href="/" class="inline-block rounded-lg px-5 py-3 text-sm font-black text-white" style="background: {{ $primary }};">Ana Sayfaya Dön</a>
  </div>
</body>
</html>
