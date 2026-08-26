@extends('layouts.brand')
@section('title', '"'.$facility->name.'" - İş Başvurusu')
{{-- bkz. facility-claim.blade.php ayni tarihli/mantikli deseni - is
     arayanlarin dogrudan Google'dan bu forma dusmesi anlamsiz, sadece
     kurumun kendi profilindeki "Burada çalışmak istiyorum" butonuyla
     buraya ulasilmali. --}}
@section('robots_meta', 'noindex,follow')

@section('content')
@php
  $section = service_section_for_scope($facility->category->brand_scope);
  $colors = $section['theme'] ?? ['primary' => current_brand()['primary_color'], 'soft' => '#f8fafc'];
@endphp
<div class="max-w-xl mx-auto px-4 py-12">
  <a href="{{ brand_route('facilities.show', ['slug' => $facility->slug]) }}" class="text-sm font-semibold text-gray-500">← {{ $facility->name }} sayfasına dön</a>

  <div class="mt-4 bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
    <div class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-black mb-3" style="background: {{ $colors['soft'] }}; color: {{ $colors['primary'] }};">İş Başvurusu</div>
    <h1 class="text-2xl font-black text-gray-950 mb-2">"{{ $facility->name }}" kurumunda çalışmak istiyorum</h1>
    <p class="text-gray-500 text-sm mb-6">Bilgilerinizi bırakın, kurum yetkilisiyle sizin adınıza paylaşalım. Kurum sizinle doğrudan iletişime geçebilir.</p>

    <form method="POST" action="{{ brand_route('job-application.store', ['slug' => $facility->slug]) }}" class="space-y-4">
      @csrf
      @include('themes._shared.partials.honeypot')

      <label for="job-applicant-name" class="sr-only">Ad Soyad</label>
      <input type="text" id="job-applicant-name" name="applicant_name" value="{{ old('applicant_name') }}" placeholder="Ad Soyad" required class="border rounded-lg px-3 py-2.5 w-full">

      <div class="grid grid-cols-2 gap-3">
        <div>
          <label for="job-applicant-age" class="sr-only">Yaş</label>
          <input type="number" id="job-applicant-age" name="applicant_age" value="{{ old('applicant_age') }}" placeholder="Yaş" min="16" max="90" class="border rounded-lg px-3 py-2.5 w-full">
        </div>
        <div>
          <label for="job-applicant-location" class="sr-only">İl / İlçe</label>
          <input type="text" id="job-applicant-location" name="applicant_location" value="{{ old('applicant_location') }}" placeholder="İl / İlçe" class="border rounded-lg px-3 py-2.5 w-full">
        </div>
      </div>

      <label for="job-applicant-phone" class="sr-only">Telefon</label>
      <input type="text" id="job-applicant-phone" name="applicant_phone" value="{{ old('applicant_phone') }}" placeholder="Telefon" required class="border rounded-lg px-3 py-2.5 w-full">

      <label for="job-applicant-email" class="sr-only">E-posta (opsiyonel)</label>
      <input type="email" id="job-applicant-email" name="applicant_email" value="{{ old('applicant_email') }}" placeholder="E-posta (opsiyonel)" class="border rounded-lg px-3 py-2.5 w-full">

      <label for="job-desired-position" class="sr-only">Çalışmak istediğiniz bölüm/pozisyon</label>
      <input type="text" id="job-desired-position" name="desired_position" value="{{ old('desired_position') }}" placeholder="Çalışmak istediğiniz bölüm/pozisyon (ör. bakım personeli, öğretmen)" class="border rounded-lg px-3 py-2.5 w-full">

      <label for="job-experience" class="sr-only">Tecrübeniz</label>
      <textarea id="job-experience" name="experience" placeholder="Varsa tecrübenizden kısaca bahsedin (opsiyonel)" rows="3" class="border rounded-lg px-3 py-2.5 w-full">{{ old('experience') }}</textarea>

      <label class="flex items-start gap-2 text-xs text-gray-600 leading-relaxed">
        <input type="checkbox" name="consent" required value="1" class="mt-0.5">
        <span>
          <a href="{{ brand_route('pages.show', ['slug' => 'kvkk']) }}" target="_blank" class="text-primary underline font-semibold">Açık Rıza Metni ve Kişisel Verilerin Korunması Aydınlatma Metni</a>'ni
          okudum, bilgilerimin kurumla paylaşılması amacıyla işlenmesine açıkça rıza gösteriyorum.
          <span class="font-semibold">Bu kutuyu işaretlemek zorunludur.</span>
        </span>
      </label>

      <button class="w-full py-3 rounded-lg font-black text-white" style="background: {{ $colors['primary'] }};">Başvuruyu Gönder</button>
      <p class="text-xs text-gray-400">Girdiğiniz kişisel bilgiler <a href="{{ brand_route('pages.show', ['slug' => 'kvkk']) }}" class="underline" target="_blank" rel="noopener">KVKK</a> kapsamında korunur, sadece bu kurumla ve platform yöneticisiyle paylaşılır.</p>
    </form>
  </div>
</div>
@endsection
