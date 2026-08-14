<!DOCTYPE html>
<html lang="tr">
@php $brand = current_brand(); $primary = $brand['primary_color'] ?? '#0b5d8c'; @endphp
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Erişim yok | {{ $brand['name'] ?? 'Kurum Bul' }}</title>
  <meta name="robots" content="noindex">
  @vite('resources/css/app.css')
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center px-4">
  <div class="max-w-md w-full text-center">
    <div class="text-5xl mb-4">🔒</div>
    <h1 class="text-xl font-black text-gray-950 mb-2">Bu sayfaya erişim yetkiniz yok</h1>
    <p class="text-sm text-gray-500 mb-8">Bu içeriği görüntülemek için gerekli yetkiye sahip değilsiniz, ya da giriş oturumunuz sona ermiş olabilir.</p>
    <a href="/" class="inline-block rounded-lg px-5 py-3 text-sm font-black text-white" style="background: {{ $primary }};">Ana Sayfaya Dön</a>
  </div>
</body>
</html>
