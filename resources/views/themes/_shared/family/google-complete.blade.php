@extends('layouts.brand')

@section('content')
<div class="max-w-md mx-auto px-4 py-12">
  <h1 class="text-2xl font-bold mb-2">Hesabınızı Tamamlayın</h1>
  <p class="text-gray-500 text-sm mb-6">Google hesabınızla bağlandık, son birkaç bilgi kaldı.</p>

  <form method="POST" action="{{ brand_route('family.google-complete.store') }}" class="bg-white p-6 rounded-xl shadow-sm space-y-4">
    @csrf

    <div class="flex items-center gap-3 bg-gray-50 rounded-lg px-3 py-2.5">
      @if($pending['avatar_url'] ?? null)
        <img src="{{ $pending['avatar_url'] }}" alt="" class="w-9 h-9 rounded-full">
      @endif
      <div>
        <div class="text-sm font-semibold text-gray-800">{{ $pending['name'] }}</div>
        <div class="text-xs text-gray-500">{{ $pending['email'] }}</div>
      </div>
    </div>

    <input type="text" name="phone" value="{{ old('phone') }}" placeholder="Telefon" required class="border rounded-lg px-3 py-2 w-full">

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

    <button class="btn-primary w-full py-2 rounded-lg font-semibold">Hesabı Tamamla</button>
  </form>
</div>

<script>
(function(){
  const checkbox = document.getElementById('consent-checkbox');
  const latInput = document.getElementById('signup_lat');
  const lngInput = document.getElementById('signup_lng');
  let locationRequested = false;

  function requestLocation() {
    if (locationRequested || !navigator.geolocation) return;
    locationRequested = true;
    navigator.geolocation.getCurrentPosition(function(position){
      latInput.value = position.coords.latitude;
      lngInput.value = position.coords.longitude;
    }, function(){}, { timeout: 8000 });
  }

  checkbox.addEventListener('change', function(){
    if (checkbox.checked) requestLocation();
  });
})();
</script>
@endsection
