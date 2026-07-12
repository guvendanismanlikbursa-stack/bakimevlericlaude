@extends('layouts.brand')

@section('content')
<div class="max-w-2xl mx-auto px-4 py-10">
  <a href="{{ brand_route('family.dashboard') }}" class="text-sm text-primary font-semibold">← Panele dön</a>
  <h1 class="text-2xl font-bold mt-2 mb-6">Hesap Bilgilerim</h1>

  <form method="POST" action="{{ brand_route('family.profile.update') }}" class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 space-y-4">
    @csrf
    @method('PUT')

    <div>
      <label class="block text-sm font-semibold text-gray-700 mb-1">Ad Soyad</label>
      <input type="text" name="name" value="{{ old('name', $family->name) }}" required class="border rounded-lg px-3 py-2 w-full">
    </div>

    <div>
      <label class="block text-sm font-semibold text-gray-700 mb-1">E-posta</label>
      <input type="email" value="{{ $family->email }}" disabled class="border rounded-lg px-3 py-2 w-full bg-gray-50 text-gray-500">
      <p class="text-xs text-gray-400 mt-1">E-posta adresinizi değiştirmek için destek hattımızla iletişime geçin.</p>
    </div>

    <div>
      <label class="block text-sm font-semibold text-gray-700 mb-1">Telefon</label>
      <input type="text" name="phone" value="{{ old('phone', $family->phone) }}" class="border rounded-lg px-3 py-2 w-full">
    </div>

    <hr class="my-2">
    <p class="text-sm font-semibold text-gray-700">Şifreyi değiştir (isteğe bağlı)</p>
    <div>
      <label class="block text-sm font-semibold text-gray-700 mb-1">Yeni şifre</label>
      <input type="password" name="password" placeholder="Boş bırakırsanız şifreniz değişmez" class="border rounded-lg px-3 py-2 w-full">
    </div>
    <div>
      <label class="block text-sm font-semibold text-gray-700 mb-1">Yeni şifre (tekrar)</label>
      <input type="password" name="password_confirmation" class="border rounded-lg px-3 py-2 w-full">
    </div>

    <button class="btn-primary w-full py-2.5 rounded-lg font-bold">Kaydet</button>
  </form>
</div>
@endsection
