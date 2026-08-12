@extends('layouts.brand')
@section('title', 'Başvurunuz Alındı | '.current_brand()['name'])

@section('content')
@php $primary = current_brand()['primary_color']; @endphp
{{-- 12 Agustos 2026: kullanicinin talebi - "kucuk anlar" da site kalitesini
     gosterir; bu sayfa tek satir duz metindi, artik gercek bir "tesekkurler"
     deneyimi: buyuk basari ikonu + "sirada ne var" adimlari. --}}
<div class="max-w-xl mx-auto px-4 py-16 text-center">
  <div class="w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-6" style="background: {{ $primary }}14;">
    <span class="w-14 h-14 rounded-full flex items-center justify-center text-white text-2xl font-black" style="background: {{ $primary }};">✓</span>
  </div>
  <h1 class="text-2xl md:text-3xl font-black text-gray-950 mb-3">Başvurunuz alındı!</h1>
  <p class="text-gray-600 leading-relaxed max-w-md mx-auto">Kurum kaydı başvurunuz ekibimize ulaştı. Aşağıdaki adımları takip edebilirsiniz.</p>

  <div class="mt-10 space-y-3 text-left">
    <div class="flex items-start gap-3 rounded-xl border border-gray-100 bg-white p-4 shadow-sm">
      <span class="shrink-0 w-8 h-8 rounded-full flex items-center justify-center font-black text-white text-sm" style="background: {{ $primary }};">1</span>
      <div><div class="font-black text-gray-950 text-sm">İnceleme</div><p class="text-sm text-gray-500 mt-0.5">Ekibimiz bilgilerinizi kısa süre içinde inceler.</p></div>
    </div>
    <div class="flex items-start gap-3 rounded-xl border border-gray-100 bg-white p-4 shadow-sm">
      <span class="shrink-0 w-8 h-8 rounded-full flex items-center justify-center font-black text-white text-sm" style="background: {{ $primary }};">2</span>
      <div><div class="font-black text-gray-950 text-sm">E-posta bildirimi</div><p class="text-sm text-gray-500 mt-0.5">Onaylandığında veya bir düzeltme gerektiğinde e-posta ile bilgilendirileceksiniz.</p></div>
    </div>
    <div class="flex items-start gap-3 rounded-xl border border-gray-100 bg-white p-4 shadow-sm">
      <span class="shrink-0 w-8 h-8 rounded-full flex items-center justify-center font-black text-white text-sm" style="background: {{ $primary }};">3</span>
      <div><div class="font-black text-gray-950 text-sm">Giriş bilgileri</div><p class="text-sm text-gray-500 mt-0.5">Onay sonrası kurum panelinize giriş bilgileri aynı e-postayla gelir.</p></div>
    </div>
  </div>

  <a href="{{ brand_route('home') }}" class="inline-flex items-center gap-2 rounded-xl text-white font-black px-6 py-3 mt-10" style="background: {{ $primary }};">Anasayfaya dön</a>
</div>
@endsection
