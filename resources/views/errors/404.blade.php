<!DOCTYPE html>
<html lang="tr">
@php $brand = current_brand(); $primary = $brand['primary_color'] ?? '#0b5d8c'; @endphp
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Sayfa bulunamadı | {{ $brand['name'] ?? 'Kurum Bul' }}</title>
  <meta name="robots" content="noindex">
  @vite('resources/css/app.css')
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center px-4">
  <div class="max-w-md w-full text-center">
    <div class="text-7xl font-black mb-4" style="color: {{ $primary }};">404</div>
    <h1 class="text-xl font-black text-gray-950 mb-2">Aradığınız sayfa bulunamadı</h1>
    <p class="text-sm text-gray-500 mb-8">Bu sayfa kaldırılmış, taşınmış olabilir ya da hiç var olmamış olabilir. Aradığınız kurumu arama sayfasından bulabilirsiniz.</p>
    <div class="flex flex-col sm:flex-row gap-3 justify-center">
      <a href="/" class="rounded-lg px-5 py-3 text-sm font-black text-white" style="background: {{ $primary }};">Ana Sayfaya Dön</a>
      <a href="/kurumlar" class="rounded-lg border border-gray-200 bg-white px-5 py-3 text-sm font-black text-gray-700">Kurumları Bul</a>
    </div>
  </div>
</body>
</html>
