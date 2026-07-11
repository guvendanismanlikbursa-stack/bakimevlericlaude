<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Girişi</title>
@vite('resources/css/app.css')
</head>
<body class="bg-gray-900 min-h-screen flex items-center justify-center">
  <div class="bg-white rounded-xl shadow-lg p-8 w-full max-w-sm">
    <h1 class="text-xl font-bold mb-1">Ortak Admin Panel</h1>
    <p class="text-sm text-gray-500 mb-6">3 site için tek yönetim paneli</p>

    @if(session('error'))
      <div class="bg-red-100 text-red-800 px-4 py-3 rounded-lg text-sm mb-4">{{ session('error') }}</div>
    @endif
    @if($errors->any())
      <div class="bg-red-100 text-red-800 px-4 py-3 rounded-lg text-sm mb-4">
        @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
      </div>
    @endif

    <form method="POST" action="{{ route('admin.login.attempt') }}" class="space-y-4">
      @csrf
      <input type="email" name="email" value="{{ old('email') }}" placeholder="E-posta" required class="border rounded-lg px-3 py-2 w-full">
      <input type="password" name="password" placeholder="Şifre" required class="border rounded-lg px-3 py-2 w-full">
      <button class="w-full bg-gray-900 text-white py-2 rounded-lg font-semibold">Giriş Yap</button>
    </form>
    @if(app()->environment('local'))
      <p class="text-xs text-gray-400 mt-4">Demo (sadece local ortamda görünür): admin@bakimplatform.test / Admin12345!</p>
    @endif
  </div>
</body>
</html>
