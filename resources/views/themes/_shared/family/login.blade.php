@extends('layouts.brand')

@section('content')
<div class="max-w-md mx-auto px-4 py-12">
  <h1 class="text-2xl font-bold mb-6">Aile Girişi</h1>
  <form method="POST" action="{{ brand_route('family.login.attempt') }}" class="bg-white p-6 rounded-xl shadow-sm space-y-4">
    @csrf
    <input type="email" name="email" value="{{ old('email') }}" placeholder="E-posta" required class="border rounded-lg px-3 py-2 w-full">
    <input type="password" name="password" placeholder="Şifre" required class="border rounded-lg px-3 py-2 w-full">
    <button class="btn-primary w-full py-2 rounded-lg font-semibold">Giriş Yap</button>
  </form>
  <p class="text-sm text-gray-500 mt-4 text-center">Hesabınız yok mu? <a href="{{ brand_route('family.register') }}" class="text-primary font-semibold">Hemen oluşturun</a></p>
</div>
@endsection
