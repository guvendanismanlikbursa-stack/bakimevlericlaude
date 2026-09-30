<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pazarlamacı Girişi</title>
<meta name="robots" content="noindex, nofollow">
<link rel="manifest" href="{{ route('marketer.manifest') }}">
<meta name="theme-color" content="#111827">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="CRM">
<link rel="icon" type="image/png" sizes="192x192" href="{{ asset('images/logo-bakimevleri-192.png') }}">
<link rel="apple-touch-icon" sizes="180x180" href="{{ asset('images/logo-bakimevleri-180.png') }}">
@vite('resources/css/app.css')
</head>
<body class="bg-gray-900 min-h-screen flex items-center justify-center">
  <div class="bg-white rounded-xl shadow-lg p-8 w-full max-w-sm">
    <h1 class="text-xl font-bold mb-1">Pazarlamacı Girişi</h1>
    <p class="text-sm text-gray-500 mb-6">Sadece kurum arama/CRM paneli için.</p>

    @if($errors->any())
      <div class="bg-red-100 text-red-800 px-4 py-3 rounded-lg text-sm mb-4">
        @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
      </div>
    @endif

    <form method="POST" action="{{ route('marketer.login.attempt') }}" class="space-y-4">
      @csrf
      <label for="marketer-login-phone" class="sr-only">Telefon</label>
      <input type="text" id="marketer-login-phone" name="phone" value="{{ old('phone') }}" placeholder="Telefon" required autofocus class="border rounded-lg px-3 py-2 w-full">
      <label for="marketer-login-password" class="sr-only">Şifre</label>
      <input type="password" id="marketer-login-password" name="password" placeholder="Şifre" required class="border rounded-lg px-3 py-2 w-full">
      <button class="w-full bg-gray-900 text-white py-2 rounded-lg font-semibold">Giriş Yap</button>
    </form>
  </div>
</body>
</html>
