@extends('layouts.brand')
@section('title', 'İletişim')

@section('content')
@php
  $brand = current_brand();
  $primary = $brand['primary_color'];
  $whatsappDefaults = config('platform.default_whatsapp');
  $whatsappNumber = \App\Models\Setting::get('whatsapp_number', $whatsappDefaults['number']);
  $whatsappMessage = str_replace('{marka}', $brand['name'], \App\Models\Setting::get('whatsapp_message', $whatsappDefaults['message']));
@endphp
{{-- 12 Agustos 2026: kullanicinin talebi - bu sayfa sadece logo + bos
     bir formdan ibaretti, hicbir sicaklik/guven unsuru yoktu. Artik
     renkli bir baslik seridi, WhatsApp hizli iletisim karti ve "ne
     zaman doner" beklenti bilgisiyle desteklenmis iki sutunlu bir
     yapiya sahip. --}}
<section style="background: linear-gradient(135deg, {{ $primary }}, {{ $primary }}cc);" class="text-white">
  <div class="max-w-5xl mx-auto px-4 py-14 text-center">
    <div class="inline-flex items-center gap-2 rounded-full bg-white/15 border border-white/20 px-4 py-2 text-xs font-black mb-4">İletişim</div>
    <h1 class="text-2xl md:text-4xl font-black mb-3">Size nasıl yardımcı olabiliriz?</h1>
    <p class="text-white/85 max-w-2xl mx-auto">Sorularınızı, önerilerinizi veya bir sorunu bize iletin — ekibimiz en kısa sürede dönüş yapar.</p>
  </div>
</section>

<div class="max-w-5xl mx-auto px-4 py-12 grid lg:grid-cols-[0.85fr_1.15fr] gap-8 items-start">
  <div class="space-y-3">
    <a href="https://wa.me/{{ $whatsappNumber }}?text={{ rawurlencode($whatsappMessage) }}" target="_blank" rel="noopener" class="flex items-center gap-3 rounded-xl border border-gray-100 bg-white p-4 shadow-sm hover:shadow-md transition">
      <span class="inline-flex rounded-lg p-2.5 shrink-0" style="background: #25D36622; color: #25D366;">
        <svg viewBox="0 0 32 32" class="w-6 h-6 fill-current" aria-hidden="true"><path d="M16.004 3C9.376 3 4 8.373 4 15c0 2.288.638 4.428 1.744 6.252L4 29l7.94-1.71A11.94 11.94 0 0 0 16.004 27C22.63 27 28 21.627 28 15S22.63 3 16.004 3Zm6.965 17.09c-.29.82-1.44 1.5-2.36 1.7-.64.13-1.47.24-4.28-.92-3.59-1.49-5.9-5.13-6.08-5.37-.18-.24-1.45-1.93-1.45-3.68 0-1.75.92-2.61 1.24-2.97.32-.36.7-.45.93-.45.23 0 .47 0 .67.01.21.01.5-.08.78.6.29.7.98 2.42 1.06 2.6.08.18.14.39.03.63-.11.24-.17.39-.34.6-.17.21-.36.47-.51.63-.17.18-.35.37-.15.72.2.36.9 1.48 1.93 2.4 1.33 1.18 2.44 1.55 2.8 1.72.36.18.57.15.78-.09.21-.24.9-1.05 1.14-1.41.24-.36.48-.3.79-.18.32.12 2.01.95 2.36 1.12.35.18.58.27.66.42.09.15.09.87-.2 1.68Z"/></svg>
      </span>
      <div>
        <div class="font-black text-gray-950 text-sm">WhatsApp'tan yazın</div>
        <p class="text-sm text-gray-500 mt-0.5">En hızlı yanıt için canlı destek hattımız.</p>
      </div>
    </a>

    <div class="flex items-start gap-3 rounded-xl border border-gray-100 bg-white p-4 shadow-sm">
      <span class="inline-flex rounded-lg p-2.5 shrink-0" style="background: {{ $primary }}14; color: {{ $primary }};">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5"><path d="M10 2a6 6 0 0 0-6 6c0 4.5 6 10 6 10s6-5.5 6-10a6 6 0 0 0-6-6Zm0 8.25a2.25 2.25 0 1 1 0-4.5 2.25 2.25 0 0 1 0 4.5Z"/></svg>
      </span>
      <div>
        <div class="font-black text-gray-950 text-sm">{{ ucfirst($brand['name']) }}</div>
        <p class="text-sm text-gray-500 mt-0.5">Türkiye genelinde hizmet veren dijital platform.</p>
      </div>
    </div>

    <div class="flex items-start gap-3 rounded-xl border border-gray-100 bg-white p-4 shadow-sm">
      <span class="inline-flex rounded-lg p-2.5 shrink-0" style="background: {{ $primary }}14; color: {{ $primary }};">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5"><path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm1-12a1 1 0 1 0-2 0v4a1 1 0 0 0 .3.7l3 3a1 1 0 0 0 1.4-1.4L11 9.6V6Z" clip-rule="evenodd"/></svg>
      </span>
      <div>
        <div class="font-black text-gray-950 text-sm">Yanıt süresi</div>
        <p class="text-sm text-gray-500 mt-0.5">Mesajlar genellikle 1 iş günü içinde yanıtlanır.</p>
      </div>
    </div>
  </div>

  <div class="bg-white p-6 md:p-7 rounded-2xl shadow-sm border border-gray-100">
    <h2 class="font-black text-gray-950 text-lg mb-1">Mesaj Formu</h2>
    <p class="text-gray-500 text-sm mb-5">Formu doldurun, e-posta adresinize dönüş yapalım.</p>
    <form method="POST" action="{{ brand_route('contact.store') }}" class="space-y-4">
      @csrf
      @include('themes._shared.partials.honeypot')
      <label for="contact-name" class="sr-only">Ad Soyad</label>
      <input type="text" id="contact-name" name="name" value="{{ old('name') }}" placeholder="Ad Soyad" required class="border rounded-lg px-3 py-2.5 w-full">
      <label for="contact-email" class="sr-only">E-posta</label>
      <input type="email" id="contact-email" name="email" value="{{ old('email') }}" placeholder="E-posta" required class="border rounded-lg px-3 py-2.5 w-full">
      <label for="contact-subject" class="sr-only">Konu</label>
      <input type="text" id="contact-subject" name="subject" value="{{ old('subject') }}" placeholder="Konu" class="border rounded-lg px-3 py-2.5 w-full">
      <label for="contact-message" class="sr-only">Mesajınız</label>
      <textarea id="contact-message" name="message" rows="5" placeholder="Mesajınız" required class="border rounded-lg px-3 py-2.5 w-full">{{ old('message') }}</textarea>
      <button class="w-full py-3 rounded-lg font-black text-white" style="background: {{ $primary }};">Gönder</button>
    </form>
  </div>
</div>
@endsection
