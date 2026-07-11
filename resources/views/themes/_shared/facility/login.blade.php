@extends('layouts.brand')

@section('content')
<div class="max-w-md mx-auto px-4 py-12">
  <h1 class="text-2xl font-bold mb-2">Kurum Girişi</h1>
  <p class="text-gray-500 text-sm mb-6">Hesabınız yoksa aşağıdan devam edebilirsiniz.</p>
  <form method="POST" action="{{ brand_route('facility.login.attempt') }}" class="bg-white p-6 rounded-xl shadow-sm space-y-4">
    @csrf
    <input type="email" name="email" value="{{ old('email') }}" placeholder="E-posta" required class="border rounded-lg px-3 py-2 w-full">
    <input type="password" name="password" placeholder="Şifre" required class="border rounded-lg px-3 py-2 w-full">
    <button class="btn-primary w-full py-2 rounded-lg font-semibold">Giriş Yap</button>
  </form>
  <p class="text-sm text-gray-500 mt-4 text-center"><a href="{{ brand_route('facility.password.request') }}" class="text-primary font-semibold">Şifremi unuttum</a></p>

  <div class="mt-8 bg-gray-50 rounded-xl p-5 space-y-3">
    <div class="font-bold text-sm text-gray-800">Hesabınız yok mu?</div>

    <div>
      <p class="text-sm text-gray-600 mb-2">Kurumunuz sistemde <strong>zaten kayıtlıysa</strong>, kurum sayfasından "Kurumu Sahiplen" ile hesap oluşturabilirsiniz.</p>
      <a href="{{ brand_route('facilities.index') }}" class="block text-center rounded-lg border border-gray-200 py-2.5 text-sm font-semibold text-gray-700 hover:bg-white">Kurumunuzu Arayın</a>
    </div>

    <div class="pt-2 border-t border-gray-200">
      <p class="text-sm text-gray-600 mb-2 mt-2">Kurumunuz listede <strong>yoksa</strong>, sıfırdan kayıt oluşturabilirsiniz.</p>
      <a href="{{ brand_route('facility-registration.create') }}" class="btn-primary block text-center rounded-lg py-2.5 text-sm font-bold">Sıfırdan Kurum Kaydı Oluştur</a>
    </div>
  </div>
</div>
@endsection
