@extends('layouts.brand')

@section('content')
<div class="max-w-xl mx-auto px-4 py-12">
  <h1 class="text-2xl font-bold mb-2">"{{ $facility->name }}" Kurumunu Sahiplen</h1>
  <p class="text-gray-500 text-sm mb-6">Bu kurumun yetkilisi olduğunuzu kanıtlayan bir evrak (vergi levhası, fatura, ruhsat vb.) görseli yükleyin. Admin onayından sonra giriş bilgileriniz e-postanıza gönderilecek.</p>

  <form method="POST" action="{{ brand_route('facility-claim.store', ['slug' => $facility->slug]) }}" enctype="multipart/form-data" class="bg-white p-6 rounded-xl shadow-sm space-y-4">
    @csrf
    <input type="hidden" name="lat" id="js-claim-lat">
    <input type="hidden" name="lng" id="js-claim-lng">
    <input type="text" name="applicant_name" value="{{ old('applicant_name') }}" placeholder="Ad Soyad" required class="border rounded-lg px-3 py-2 w-full">
    <input type="email" name="applicant_email" value="{{ old('applicant_email') }}" placeholder="E-posta (giriş bilgileri buraya gönderilecek)" required class="border rounded-lg px-3 py-2 w-full">
    <input type="text" name="applicant_phone" value="{{ old('applicant_phone') }}" placeholder="Telefon" required class="border rounded-lg px-3 py-2 w-full">
    <div>
      <label class="text-sm font-medium block mb-1">Evrak / Fatura Görseli</label>
      <input type="file" name="document" accept="image/*" required class="border rounded-lg px-3 py-2 w-full">
    </div>
    <textarea name="note" placeholder="Eklemek istediğiniz not (opsiyonel)" rows="3" class="border rounded-lg px-3 py-2 w-full">{{ old('note') }}</textarea>
    <button class="btn-primary w-full py-2 rounded-lg font-semibold">Başvuruyu Gönder</button>
    <p class="text-xs text-gray-400">Tarayıcınız konum izni isteyebilir; bu, başvurunuzun kurum adresine yakınlığını admin incelemesinde göstermek içindir. İzin vermezseniz başvurunuz yine de gönderilir.</p>
  </form>
</div>
<script>
(function () {
  if (!navigator.geolocation) return;
  navigator.geolocation.getCurrentPosition(function (position) {
    document.getElementById('js-claim-lat').value = position.coords.latitude;
    document.getElementById('js-claim-lng').value = position.coords.longitude;
  }, function () { /* izin verilmedi, sessizce yoksay */ });
})();
</script>
@endsection
