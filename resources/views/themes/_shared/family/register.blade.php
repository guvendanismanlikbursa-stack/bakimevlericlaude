@extends('layouts.brand')

@section('content')
<div class="max-w-md mx-auto px-4 py-12">
  <h1 class="text-2xl font-bold mb-2">Aile Hesabı Oluştur</h1>
  <p class="text-gray-500 text-sm mb-6">Ücretsizdir. Hesabınızla 3 sitemizde de giriş yapabilir, kurumlardan ücret bilgisi isteyebilirsiniz.</p>

  <a href="{{ brand_route('family.google-redirect') }}" class="w-full flex items-center justify-center gap-2 rounded-lg border border-gray-200 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50 bg-white shadow-sm mb-4">
    <svg viewBox="0 0 24 24" class="w-4 h-4" aria-hidden="true"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 0 1-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1Z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.99.66-2.26 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.85A10.99 10.99 0 0 0 12 23Z"/><path fill="#FBBC05" d="M5.84 14.1a6.6 6.6 0 0 1 0-4.2V7.05H2.18a11 11 0 0 0 0 9.9l3.66-2.85Z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1a10.99 10.99 0 0 0-9.82 6.05l3.66 2.85C6.71 7.3 9.14 5.38 12 5.38Z"/></svg>
    Google ile devam et
  </a>
  <div class="text-center text-[10px] text-gray-400 uppercase tracking-wider mb-4">veya e-posta ile</div>

  <form method="POST" action="{{ brand_route('family.register.attempt') }}" id="family-register-form" class="bg-white p-6 rounded-xl shadow-sm space-y-4">
    @csrf
    <input type="text" name="name" value="{{ old('name') }}" placeholder="Ad Soyad" required class="border rounded-lg px-3 py-2 w-full">
    <input type="email" name="email" value="{{ old('email') }}" placeholder="E-posta" required class="border rounded-lg px-3 py-2 w-full">
    <input type="text" name="phone" value="{{ old('phone') }}" placeholder="Telefon" required class="border rounded-lg px-3 py-2 w-full">
    <input type="password" name="password" placeholder="Şifre" required class="border rounded-lg px-3 py-2 w-full">
    <input type="password" name="password_confirmation" placeholder="Şifre (tekrar)" required class="border rounded-lg px-3 py-2 w-full">

    <input type="hidden" name="signup_lat" id="signup_lat">
    <input type="hidden" name="signup_lng" id="signup_lng">

    <label class="flex items-start gap-2 text-xs text-gray-600 leading-relaxed">
      <input type="checkbox" name="consent" id="consent-checkbox" required value="1" class="mt-0.5">
      <span>
        <a href="{{ brand_route('pages.show', ['slug' => 'kvkk']) }}" target="_blank" class="text-primary underline font-semibold">Açık Rıza Metni ve Kişisel Verilerin Korunması Aydınlatma Metni</a>'ni
        okudum, kişisel verilerimin (konum bilgim dahil) belirtilen amaçlarla işlenmesine
        açıkça rıza gösteriyorum. <span class="font-semibold">Bu kutuyu işaretlemek zorunludur.</span>
      </span>
    </label>

    <button class="btn-primary w-full py-2 rounded-lg font-semibold">Hesap Oluştur</button>
    <p id="location-note" class="text-xs text-gray-400 hidden">Konum izniniz alınıyor…</p>
  </form>
  <p class="text-sm text-gray-500 mt-4 text-center">Zaten hesabınız var mı? <a href="{{ brand_route('family.login') }}" class="text-primary font-semibold">Giriş yapın</a></p>
</div>

<script>
(function(){
  const checkbox = document.getElementById('consent-checkbox');
  const latInput = document.getElementById('signup_lat');
  const lngInput = document.getElementById('signup_lng');
  const note = document.getElementById('location-note');
  let locationRequested = false;

  function requestLocation() {
    if (locationRequested || !navigator.geolocation) return;
    locationRequested = true;
    note.classList.remove('hidden');
    navigator.geolocation.getCurrentPosition(function(position){
      latInput.value = position.coords.latitude;
      lngInput.value = position.coords.longitude;
      note.textContent = 'Konum alındı, teşekkürler.';
    }, function(){
      // Izin verilmedi veya alinamadi; kayit yine de devam eder, konum alanlari bos kalir.
      note.textContent = 'Konum paylaşılmadı, kaydınız yine de oluşturulacak.';
    }, { timeout: 8000 });
  }

  // Aciik riza kutusu isaretlendigi an konum izni istenir (zorunlu alan
  // tiklendiginde otomatik konum alma talebi).
  checkbox.addEventListener('change', function(){
    if (checkbox.checked) requestLocation();
  });
})();
</script>
@endsection
