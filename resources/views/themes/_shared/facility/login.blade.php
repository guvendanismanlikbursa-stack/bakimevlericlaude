@extends('layouts.brand')

@section('content')
<div class="max-w-md mx-auto px-4 py-12">
  <h1 class="text-2xl font-bold mb-2">Kurum Girişi</h1>
  <p class="text-gray-500 text-sm mb-6">Hesabınız yoksa aşağıdan devam edebilirsiniz.</p>

  <a href="{{ brand_route('facility.google-redirect') }}" class="w-full flex items-center justify-center gap-2 rounded-lg border border-gray-200 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50 bg-white shadow-sm mb-4">
    <svg viewBox="0 0 24 24" class="w-4 h-4" aria-hidden="true"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 0 1-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1Z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.99.66-2.26 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.85A10.99 10.99 0 0 0 12 23Z"/><path fill="#FBBC05" d="M5.84 14.1a6.6 6.6 0 0 1 0-4.2V7.05H2.18a11 11 0 0 0 0 9.9l3.66-2.85Z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1a10.99 10.99 0 0 0-9.82 6.05l3.66 2.85C6.71 7.3 9.14 5.38 12 5.38Z"/></svg>
    Google ile giriş yap
  </a>
  <div class="text-center text-[10px] text-gray-400 uppercase tracking-wider mb-4">veya e-posta ile</div>

  <form method="POST" action="{{ brand_route('facility.login.attempt') }}" class="bg-white p-6 rounded-xl shadow-sm space-y-4">
    @csrf
    <label for="facility-login-email" class="sr-only">E-posta</label>
    <input type="email" id="facility-login-email" name="email" value="{{ old('email') }}" placeholder="E-posta" required class="border rounded-lg px-3 py-2 w-full">
    <label for="facility-login-password" class="sr-only">Şifre</label>
    <input type="password" id="facility-login-password" name="password" placeholder="Şifre" required class="border rounded-lg px-3 py-2 w-full">
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
