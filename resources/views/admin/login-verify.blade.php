<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Giriş Doğrulama</title>
@vite('resources/css/app.css')
</head>
<body class="bg-gray-900 min-h-screen flex items-center justify-center">
  <div class="bg-white rounded-xl shadow-lg p-8 w-full max-w-sm">
    <h1 class="text-xl font-bold mb-1">Giriş Doğrulama</h1>
    <p class="text-sm text-gray-500 mb-6">E-postanıza gönderilen 6 haneli kodu girin.</p>

    @if(session('success'))
      <div class="bg-green-100 text-green-800 px-4 py-3 rounded-lg text-sm mb-4">{{ session('success') }}</div>
    @endif
    @if($errors->any())
      <div class="bg-red-100 text-red-800 px-4 py-3 rounded-lg text-sm mb-4">
        @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
      </div>
    @endif

    <form method="POST" action="{{ route('admin.login.verify.attempt') }}" class="space-y-4">
      @csrf
      <input type="text" name="code" inputmode="numeric" pattern="[0-9]*" maxlength="6" placeholder="6 haneli kod" required autofocus class="border rounded-lg px-3 py-2 w-full text-center text-2xl tracking-widest">
      <button class="w-full bg-gray-900 text-white py-2 rounded-lg font-semibold">Doğrula</button>
    </form>

    <form method="POST" action="{{ route('admin.login.verify.resend') }}" class="mt-4">
      @csrf
      <button class="text-sm text-primary font-semibold underline">Kodu tekrar gönder</button>
    </form>
  </div>
</body>
</html>
