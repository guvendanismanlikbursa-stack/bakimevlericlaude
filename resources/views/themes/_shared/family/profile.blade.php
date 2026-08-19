@extends('layouts.brand')

@section('content')
<div class="max-w-2xl mx-auto px-4 py-10">
  <a href="{{ brand_route('family.dashboard') }}" class="text-sm text-primary font-semibold">← Panele dön</a>
  <h1 class="text-2xl font-bold mt-2 mb-6">Hesap Bilgilerim</h1>

  <form method="POST" action="{{ brand_route('family.profile.update') }}" class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 space-y-4">
    @csrf
    @method('PUT')

    <div>
      <label for="family-profile-name" class="block text-sm font-semibold text-gray-700 mb-1">Ad Soyad</label>
      <input type="text" id="family-profile-name" name="name" value="{{ old('name', $family->name) }}" required class="border rounded-lg px-3 py-2 w-full">
    </div>

    <div>
      <label for="family-profile-email" class="block text-sm font-semibold text-gray-700 mb-1">E-posta</label>
      <input type="email" id="family-profile-email" value="{{ $family->email }}" disabled class="border rounded-lg px-3 py-2 w-full bg-gray-50 text-gray-500">
      <p class="text-xs text-gray-400 mt-1">E-posta adresinizi değiştirmek için destek hattımızla iletişime geçin.</p>
    </div>

    <div>
      <label for="family-profile-phone" class="block text-sm font-semibold text-gray-700 mb-1">Telefon</label>
      <input type="text" id="family-profile-phone" name="phone" value="{{ old('phone', $family->phone) }}" class="border rounded-lg px-3 py-2 w-full">
    </div>

    <hr class="my-2">
    <p class="text-sm font-semibold text-gray-700">Şifreyi değiştir (isteğe bağlı)</p>
    <div>
      <label for="family-profile-password" class="block text-sm font-semibold text-gray-700 mb-1">Yeni şifre</label>
      <input type="password" id="family-profile-password" name="password" placeholder="Boş bırakırsanız şifreniz değişmez" class="border rounded-lg px-3 py-2 w-full">
    </div>
    <div>
      <label for="family-profile-password-confirmation" class="block text-sm font-semibold text-gray-700 mb-1">Yeni şifre (tekrar)</label>
      <input type="password" id="family-profile-password-confirmation" name="password_confirmation" class="border rounded-lg px-3 py-2 w-full">
    </div>

    <button class="btn-primary w-full py-2.5 rounded-lg font-bold">Kaydet</button>
  </form>

  @include('themes._shared.partials.notification-preferences-form', [
      'notificationGroups' => $notificationGroups,
      'preferences' => $family->notification_preferences ?? [],
      'notificationFormAction' => brand_route('family.profile.notifications.update'),
  ])

  {{-- 19 Agustos 2026: kullanicinin talebi - KVKK "silme hakki". Bu form
       hesabi DOGRUDAN SILMEZ, sadece bir talep olusturur - gercek silme
       islemini bir admin onaylar (bkz. Family\ProfileController::destroy()). --}}
  <div class="bg-white p-6 rounded-xl shadow-sm border border-red-100 mt-6">
    <p class="text-sm font-semibold text-red-700 mb-1">Hesabımı Sil</p>
    <p class="text-xs text-gray-500 mb-3">Hesabınızın ve kişisel verilerinizin (ad, e-posta, telefon) silinmesini talep edebilirsiniz. Talebiniz ekibimiz tarafından incelenip kısa süre içinde işleme alınır.</p>
    <form method="POST" action="{{ brand_route('family.profile.destroy') }}" onsubmit="return confirm('Hesabınızın silinmesini talep etmek istediğinize emin misiniz?');" class="flex flex-wrap gap-2 items-end">
      @csrf
      @method('DELETE')
      <div class="flex-1 min-w-[200px]">
        <label for="family-delete-password" class="block text-xs font-semibold text-gray-600 mb-1">Şifreniz</label>
        <input type="password" id="family-delete-password" name="password" required class="border rounded-lg px-3 py-2 w-full">
      </div>
      <button class="rounded-lg border border-red-200 text-red-700 px-4 py-2 text-sm font-semibold hover:bg-red-50">Silme talebi gönder</button>
    </form>
    @error('password')<p class="text-xs text-red-600 mt-2">{{ $message }}</p>@enderror
  </div>
</div>
@endsection
