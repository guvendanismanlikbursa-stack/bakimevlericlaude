@extends('layouts.brand')
@section('title', '"'.$facility->name.'" Kurumunu Sahiplen | '.current_brand()['name'])

@section('content')
@php
  $brand = current_brand();
  $section = service_section_for_scope($facility->category->brand_scope);
  $colors = $section['theme'] ?? ['primary' => $brand['primary_color'], 'secondary' => $brand['secondary_color'], 'soft' => '#f8fafc'];
  $benefits = [
    ['title' => 'Profilinizi siz yönetin', 'text' => 'Görsel, açıklama, hizmet ve fiyat bilgilerini istediğiniz zaman güncelleyin.'],
    ['title' => 'Ailelerden doğrudan talep alın', 'text' => 'Fiyat, ziyaret ve kontenjan taleplerini panelinizden takip edin, doğrudan yanıtlayın.'],
    ['title' => 'Doğrulanmış rozeti kazanın', 'text' => 'Sahiplenilen kurumlar ziyaretçilere "Onaylı" rozetiyle gösterilir, güven artar.'],
    ['title' => 'Ücretsiz başlangıç hakkı', 'text' => 'Onay sonrası hesabınıza ücretsiz teklif hakkı tanımlanır, hemen kullanmaya başlayın.'],
  ];
@endphp
<div class="max-w-5xl mx-auto px-4 py-12">
  <a href="{{ brand_route('facilities.show', ['slug' => $facility->slug]) }}" class="text-sm font-semibold text-gray-500">← {{ $facility->name }} sayfasına dön</a>

  <div class="mt-4 grid lg:grid-cols-[1.05fr_1fr] gap-8 items-start">
    {{-- 12 Agustos 2026: kullanicinin talebi - bu sayfa sadece bir form
         kutusuydu, hicbir gorsel/renk/aciklama yoktu. Artik sol tarafta
         "neden sahiplenmeli" faydalari + kurum ozeti, sagda form var -
         diger premium sayfalarla ayni gorsel dil. --}}
    <div>
      <div class="rounded-2xl overflow-hidden border border-gray-100 shadow-sm mb-6">
        <div class="relative h-40" style="background: {{ $colors['primary'] }};">
          @if($section['hero_image'] ?? null)
            <img src="{{ $section['hero_image'] }}" alt="" class="absolute inset-0 w-full h-full object-cover opacity-40">
          @endif
          <div class="absolute inset-0 bg-gradient-to-t from-black/40 to-transparent"></div>
          <div class="relative h-full flex flex-col justify-end p-5 text-white">
            @if($section)<span class="text-xs font-black uppercase tracking-wide bg-white/20 border border-white/25 rounded-full px-3 py-1 inline-block mb-2 w-fit">{{ $section['title'] }}</span>@endif
            <div class="font-black text-xl">{{ $facility->name }}</div>
            <div class="text-sm text-white/80">{{ $facility->city->name ?? '' }} · {{ $facility->district ?? '' }}</div>
          </div>
        </div>
      </div>

      <div class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-black mb-3" style="background: {{ $colors['soft'] }}; color: {{ $colors['primary'] }};">Kurumunuzu Sahiplenin</div>
      <h1 class="text-2xl md:text-3xl font-black text-gray-950 mb-4">"{{ $facility->name }}" kurumunu sahiplenerek profilin kontrolünü alın</h1>

      <div class="space-y-3">
        @foreach($benefits as $b)
          <div class="flex items-start gap-3 rounded-xl border border-gray-100 bg-white p-4 shadow-sm">
            <span class="inline-flex rounded-lg p-2 shrink-0" style="background: {{ $colors['soft'] }}; color: {{ $colors['primary'] }};">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5"><path fill-rule="evenodd" d="M16.7 5.3a1 1 0 0 1 0 1.4l-7.5 7.5a1 1 0 0 1-1.4 0l-3.5-3.5a1 1 0 1 1 1.4-1.4l2.8 2.8 6.8-6.8a1 1 0 0 1 1.4 0Z" clip-rule="evenodd"/></svg>
            </span>
            <div>
              <div class="font-black text-gray-950 text-sm">{{ $b['title'] }}</div>
              <p class="text-sm text-gray-500 mt-0.5">{{ $b['text'] }}</p>
            </div>
          </div>
        @endforeach
      </div>
    </div>

    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 lg:sticky lg:top-24">
      <h2 class="font-black text-gray-950 text-lg mb-1">Başvuru Formu</h2>
      <p class="text-gray-500 text-sm mb-5">Bu kurumun yetkilisi olduğunuzu kanıtlayan bir evrak (vergi levhası, fatura, ruhsat vb.) görseli yükleyin. Admin onayından sonra giriş bilgileriniz e-postanıza gönderilecek.</p>

      <form method="POST" action="{{ brand_route('facility-claim.store', ['slug' => $facility->slug]) }}" enctype="multipart/form-data" class="space-y-4">
        @csrf
        <input type="hidden" name="lat" id="js-claim-lat">
        <input type="hidden" name="lng" id="js-claim-lng">
        <input type="text" name="applicant_name" value="{{ old('applicant_name') }}" placeholder="Ad Soyad" required class="border rounded-lg px-3 py-2.5 w-full">
        <input type="email" name="applicant_email" value="{{ old('applicant_email') }}" placeholder="E-posta (giriş bilgileri buraya gönderilecek)" required class="border rounded-lg px-3 py-2.5 w-full">
        <input type="text" name="applicant_phone" value="{{ old('applicant_phone') }}" placeholder="Telefon" required class="border rounded-lg px-3 py-2.5 w-full">
        <div>
          <label class="text-sm font-medium block mb-1">Evrak / Fatura Görseli</label>
          <input type="file" name="document" accept="image/*" required class="border rounded-lg px-3 py-2.5 w-full text-sm">
        </div>
        <textarea name="note" placeholder="Eklemek istediğiniz not (opsiyonel)" rows="3" class="border rounded-lg px-3 py-2.5 w-full">{{ old('note') }}</textarea>
        <button class="w-full py-3 rounded-lg font-black text-white" style="background: {{ $colors['primary'] }};">Başvuruyu Gönder</button>
        <p class="text-xs text-gray-400">Tarayıcınız konum izni isteyebilir; bu, başvurunuzun kurum adresine yakınlığını admin incelemesinde göstermek içindir. İzin vermezseniz başvurunuz yine de gönderilir.</p>
      </form>
    </div>
  </div>
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
