@extends('layouts.brand')

@section('content')
<div class="max-w-md mx-auto px-4 py-12">
  <h1 class="text-2xl font-bold mb-2">Şifremi Unuttum</h1>
  <p class="text-gray-500 text-sm mb-6">Hesabınıza kayıtlı e-posta adresini girin, şifre sıfırlama bağlantısı gönderelim.</p>
  <form method="POST" action="{{ brand_route('family.password.email') }}" class="bg-white p-6 rounded-xl shadow-sm space-y-4">
    @csrf
    <input type="email" name="email" value="{{ old('email') }}" placeholder="E-posta" required class="border rounded-lg px-3 py-2 w-full">
    <button class="btn-primary w-full py-2 rounded-lg font-semibold">Sıfırlama Bağlantısı Gönder</button>
  </form>
  <p class="text-sm text-gray-500 mt-4 text-center"><a href="{{ brand_route('family.login') }}" class="text-primary font-semibold">Girişe dön</a></p>
</div>
@endsection
