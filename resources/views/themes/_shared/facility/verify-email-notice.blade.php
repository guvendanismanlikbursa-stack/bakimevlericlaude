@extends('layouts.brand')

@section('content')
<div class="max-w-md mx-auto px-4 py-12">
  <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
    <h1 class="text-2xl font-bold mb-3">E-posta Doğrulaması</h1>
    <p class="text-gray-600 mb-6">Hesabınızdaki e-posta adresini doğrulamanız gerekiyor. Doğrulama bağlantısı aynı adrese gönderildi.</p>
    <div class="rounded-xl bg-gray-50 border border-gray-200 p-4 mb-6">
      <div class="text-sm font-semibold text-gray-700">Kullanıcı</div>
      <div class="text-gray-900 mt-1">{{ $user->name }} &middot; {{ $user->email }}</div>
    </div>
    <form method="POST" action="{{ brand_route('facility.verify-email.resend') }}" class="space-y-4">
      @csrf
      <button class="btn-primary w-full py-3 rounded-lg font-semibold">Doğrulama e-postasını tekrar gönder</button>
    </form>
    <p class="text-sm text-gray-500 mt-4">Doğrulama bağlantısı 60 dakika içinde geçerlidir. Gelen kutunuzda görünmezse, spam klasörünü kontrol edin.</p>
  </div>
</div>
@endsection
