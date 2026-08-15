@extends('layouts.brand')
@section('title', 'En Çok Aranan Bölgeler')
@section('meta_description', 'Son 30 günde ziyaretçilerin en çok filtrelediği il ve kurum türü kombinasyonları.')
@section('content')
@php
  $primary = $brand['primary_color'];
  $maxTotal = $rows->max('total') ?: 1;
  $medalColors = ['#d4af37', '#a8a9ad', '#b26a2b'];
@endphp
<section style="background: linear-gradient(135deg, {{ $primary }}, {{ $primary }}cc);" class="text-white">
  <div class="max-w-4xl mx-auto px-4 py-12">
    <div class="inline-flex items-center gap-2 rounded-full bg-white/15 border border-white/20 px-3 py-1 text-xs font-black mb-4">Son 30 gün</div>
    <h1 class="text-2xl md:text-3xl font-black mb-2">En Çok Aranan Bölgeler</h1>
    <p class="text-white/85 max-w-2xl">Ziyaretçilerin kurum listesinde en çok filtrelediği il ve kurum türü kombinasyonları.</p>
  </div>
</section>

<div class="max-w-4xl mx-auto px-4 py-10">
  <div class="grid sm:grid-cols-2 gap-3">
    @forelse($rows as $i => $row)
      <a href="{{ brand_route('facilities.index', array_filter(['city' => $row->city_slug, 'category' => $row->category_slug])) }}"
         class="flex items-center gap-3 rounded-xl border border-gray-100 bg-white p-4 shadow-sm hover:shadow-md transition group">
        <span class="w-9 h-9 rounded-full flex items-center justify-center font-black text-sm shrink-0 {{ $i < 3 ? 'text-white' : 'text-gray-500 bg-gray-100' }}" @if($i < 3) style="background: {{ $medalColors[$i] }};" @endif>{{ $i + 1 }}</span>
        <span class="flex-1 min-w-0">
          <span class="block font-black text-gray-950 group-hover:text-primary truncate">{{ $row->city_name }}{{ $row->category_name ? ' — '.$row->category_name : '' }}</span>
          <span class="block h-1.5 rounded-full bg-gray-100 overflow-hidden mt-2"><span class="block h-full rounded-full" style="background: {{ $primary }}; width: {{ max(6, round($row->total / $maxTotal * 100)) }}%"></span></span>
        </span>
        <span class="text-sm font-black text-gray-900 whitespace-nowrap">{{ number_format($row->total, 0, ',', '.') }} arama</span>
      </a>
    @empty
      <div class="sm:col-span-2 rounded-xl border border-dashed border-gray-200 p-10 text-center text-gray-400 bg-white">Henüz yeterli arama verisi yok.</div>
    @endforelse
  </div>
  <p class="text-xs text-gray-400 mt-6 text-center">Not: sitede serbest metin arama kutusu yok; burada gösterilen, ziyaretçilerin il/kategori filtrelerini kaç kez kullandığıdır — kelime bazlı arama loglaması değildir.</p>
</div>
@endsection
