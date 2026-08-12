@extends('layouts.brand')
@section('title', 'Kurumunuzu Kaydedin | '.current_brand()['name'])

@section('content')
@php
  $brand = current_brand();
  $primary = $brand['primary_color'];
  $sections = service_sections();
  $benefits = [
    ['title' => 'Ücretsiz ve hızlı', 'text' => 'Kayıt formu birkaç dakika sürer, admin onayından sonra hemen yayına alınır.'],
    ['title' => 'Ailelere doğrudan görünürlük', 'text' => 'Şehir, ilçe ve hizmet filtrelerinde binlerce aile tarafından bulunabilirsiniz.'],
    ['title' => 'Talepleri panelinizden yönetin', 'text' => 'Fiyat, ziyaret ve kontenjan taleplerini tek bir yerden takip edip yanıtlayın.'],
    ['title' => 'Ücretsiz başlangıç hakkı', 'text' => 'Onay sonrası hesabınıza tanımlanan ücretsiz hakla hemen teklif göndermeye başlayın.'],
  ];
@endphp
{{-- 12 Agustos 2026: kullanicinin talebi - bu sayfa sadece uzun bir form
     kutusuydu, kuruma "neden kaydolmali" anlatan hicbir sey yoktu. Artik
     solda gercek fotograflarla (3 hizmet alani) desteklenmis bir fayda
     anlatimi, sagda ayni islevsel (Google ile doldur dahil) form var. --}}
<section class="relative bg-gray-950 text-white overflow-hidden">
  <div class="absolute inset-0 grid grid-cols-3">
    @foreach($sections as $s)
      <div class="relative"><img src="{{ $s['hero_image'] }}" alt="" class="w-full h-full object-cover"><div class="absolute inset-0 bg-gray-950/75"></div></div>
    @endforeach
  </div>
  <div class="relative max-w-5xl mx-auto px-4 py-14 text-center">
    <div class="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-4 py-2 text-xs font-black uppercase tracking-wide mb-5">Kurumunuzu Ekleyin</div>
    <h1 class="text-2xl md:text-4xl font-black leading-tight mb-3 text-balance">Kurumunuzu {{ ucfirst($brand['name']) }}'a kaydedin, ailelere ulaşın</h1>
    <p class="text-white/80 max-w-2xl mx-auto">Kurumunuzun bilgilerini girin, başvurunuz admin onayından sonra yayına alınır ve size giriş bilgileri e-posta ile gönderilir.</p>
  </div>
</section>

<div class="max-w-5xl mx-auto px-4 py-12 grid lg:grid-cols-[0.85fr_1.15fr] gap-8 items-start">
  <div class="space-y-3 lg:sticky lg:top-24">
    @foreach($benefits as $b)
      <div class="flex items-start gap-3 rounded-xl border border-gray-100 bg-white p-4 shadow-sm">
        <span class="inline-flex rounded-lg p-2 shrink-0" style="background: {{ $primary }}14; color: {{ $primary }};">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5"><path fill-rule="evenodd" d="M16.7 5.3a1 1 0 0 1 0 1.4l-7.5 7.5a1 1 0 0 1-1.4 0l-3.5-3.5a1 1 0 1 1 1.4-1.4l2.8 2.8 6.8-6.8a1 1 0 0 1 1.4 0Z" clip-rule="evenodd"/></svg>
        </span>
        <div>
          <div class="font-black text-gray-950 text-sm">{{ $b['title'] }}</div>
          <p class="text-sm text-gray-500 mt-0.5">{{ $b['text'] }}</p>
        </div>
      </div>
    @endforeach
    <div class="rounded-xl p-4 text-sm" style="background: {{ $primary }}0d; color: {{ $primary }};">
      Kurumunuz sistemde zaten kayıtlıysa, kaydetmek yerine kurum sayfasından <strong>"Kurumu Sahiplen"</strong> yolunu kullanmanız daha hızlıdır.
    </div>
  </div>

  <div class="bg-white p-6 md:p-7 rounded-2xl shadow-sm border border-gray-100">
    <form method="POST" action="{{ brand_route('facility-registration.store') }}" class="space-y-4">
      @csrf
      <div>
        <label class="text-sm font-medium block mb-1">Kurum Türü</label>
        <select name="facility_category_id" required class="border rounded-lg px-3 py-2.5 w-full">
          <option value="">Kurum türü seçin</option>
          @foreach($categories as $category)
            <option value="{{ $category->id }}" @selected(old('facility_category_id') == $category->id)>{{ $category->name }}</option>
          @endforeach
        </select>
      </div>

      <input type="text" name="name" value="{{ old('name') }}" placeholder="Kurum Adı" required class="border rounded-lg px-3 py-2.5 w-full">

      <div>
        <label class="text-sm font-medium block mb-1">İl</label>
        <select name="city_id" required class="border rounded-lg px-3 py-2.5 w-full">
          <option value="">İl seçin</option>
          @foreach($cities as $city)
            <option value="{{ $city->id }}" @selected(old('city_id') == $city->id)>{{ $city->name }}</option>
          @endforeach
        </select>
      </div>

      <input type="text" name="district" value="{{ old('district') }}" placeholder="İlçe" class="border rounded-lg px-3 py-2.5 w-full">
      <input type="text" name="address" value="{{ old('address') }}" placeholder="Açık Adres" class="border rounded-lg px-3 py-2.5 w-full">
      <input type="text" name="phone" value="{{ old('phone') }}" placeholder="Kurum Telefonu" class="border rounded-lg px-3 py-2.5 w-full">
      <textarea name="description" placeholder="Kurum hakkında kısa açıklama" rows="4" class="border rounded-lg px-3 py-2.5 w-full">{{ old('description') }}</textarea>

      <div class="grid grid-cols-2 gap-3">
        <input type="number" name="capacity" value="{{ old('capacity') }}" placeholder="Kapasite" min="0" class="border rounded-lg px-3 py-2.5 w-full">
        <div class="grid grid-cols-2 gap-2">
          <input type="number" name="price_min" value="{{ old('price_min') }}" placeholder="Min. Fiyat" min="0" class="border rounded-lg px-3 py-2.5 w-full">
          <input type="number" name="price_max" value="{{ old('price_max') }}" placeholder="Maks. Fiyat" min="0" class="border rounded-lg px-3 py-2.5 w-full">
        </div>
      </div>

      <hr class="my-2">
      <p class="text-sm font-black text-gray-700">Yetkili Bilgileri</p>

      <a href="{{ brand_route('facility-registration.google-redirect') }}" id="js-facility-google-btn" class="w-full flex items-center justify-center gap-2 rounded-lg border border-gray-200 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50 bg-white">
        <svg viewBox="0 0 24 24" class="w-4 h-4" aria-hidden="true"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 0 1-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1Z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.99.66-2.26 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.85A10.99 10.99 0 0 0 12 23Z"/><path fill="#FBBC05" d="M5.84 14.1a6.6 6.6 0 0 1 0-4.2V7.05H2.18a11 11 0 0 0 0 9.9l3.66-2.85Z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1a10.99 10.99 0 0 0-9.82 6.05l3.66 2.85C6.71 7.3 9.14 5.38 12 5.38Z"/></svg>
        Google ile ad/e-posta doldur
      </a>
      <div id="js-facility-google-note" class="hidden text-xs text-green-700 bg-green-50 rounded-lg px-3 py-2">✓ Google'dan ad/e-posta dolduruldu, aşağıdan kontrol edip devam edin.</div>

      <input type="text" name="applicant_name" id="js-applicant-name" value="{{ old('applicant_name') }}" placeholder="Ad Soyad" required class="border rounded-lg px-3 py-2.5 w-full">
      <input type="email" name="applicant_email" id="js-applicant-email" value="{{ old('applicant_email') }}" placeholder="E-posta (giriş bilgileri buraya gönderilecek)" required class="border rounded-lg px-3 py-2.5 w-full">
      <input type="text" name="applicant_phone" value="{{ old('applicant_phone') }}" placeholder="Telefon" required class="border rounded-lg px-3 py-2.5 w-full">

      <input type="hidden" name="lat" id="signup_lat">
      <input type="hidden" name="lng" id="signup_lng">

      <button class="w-full py-3 rounded-lg font-black text-white" style="background: {{ $primary }};">Kaydı Gönder</button>
    </form>
  </div>
</div>

<script>
(function(){
  const form = document.querySelector('form');
  const latInput = document.getElementById('signup_lat');
  const lngInput = document.getElementById('signup_lng');
  let locationRequested = false;

  function requestLocation() {
    if (locationRequested || !navigator.geolocation) return;
    locationRequested = true;
    navigator.geolocation.getCurrentPosition(function(position){
      latInput.value = position.coords.latitude;
      lngInput.value = position.coords.longitude;
    }, function(){
      // Izin verilmedi veya alinamadi; basvuru yine de devam eder, konum alanlari bos kalir.
    }, { timeout: 8000 });
  }

  if (form) {
    form.addEventListener('focusin', requestLocation, { once: true });
  }

  // Google donusunu yakala: query string'de applicant_google_name/email
  // varsa yetkili alanlarini doldur, notu goster, URL'i temizle.
  var params = new URLSearchParams(window.location.search);
  var googleName = params.get('applicant_google_name');
  var googleEmail = params.get('applicant_google_email');
  if (googleName || googleEmail) {
    var nameInput = document.getElementById('js-applicant-name');
    var emailInput = document.getElementById('js-applicant-email');
    var note = document.getElementById('js-facility-google-note');
    var btn = document.getElementById('js-facility-google-btn');
    if (googleName && nameInput) nameInput.value = googleName;
    if (googleEmail && emailInput) emailInput.value = googleEmail;
    if (note) note.classList.remove('hidden');
    if (btn) btn.classList.add('hidden');
    window.history.replaceState({}, document.title, window.location.pathname);
  }
})();
</script>
@endsection
