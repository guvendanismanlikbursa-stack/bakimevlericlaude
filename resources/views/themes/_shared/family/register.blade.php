@extends('layouts.brand')

@section('content')
<div class="max-w-md mx-auto px-4 py-12">
  <h1 class="text-2xl font-bold mb-2">Aile Hesabı Oluştur</h1>
  <p class="text-gray-500 text-sm mb-6">Ücretsizdir. Hesabınızla 3 sitemizde de giriş yapabilir, kurumlardan ücret bilgisi isteyebilirsiniz.</p>
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
