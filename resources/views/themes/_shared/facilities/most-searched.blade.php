@extends('layouts.brand')
@section('title', 'En Çok Aranan Bölgeler | '.$brand['name'])
@section('meta_description', 'Son 30 günde ziyaretçilerin en çok filtrelediği il ve kurum türü kombinasyonları.')
@section('content')
<div class="max-w-4xl mx-auto px-4 py-10">
  <h1 class="text-3xl font-black text-gray-950 mb-2">En Çok Aranan Bölgeler</h1>
  <p class="text-sm text-gray-500 mb-2">Son 30 günde ziyaretçilerin kurum listesinde en çok filtrelediği il + kurum türü kombinasyonları.</p>
  <p class="text-xs text-gray-400 mb-6">Not: sitede serbest metin arama kutusu yok; burada gösterilen, ziyaretçilerin il/kategori filtrelerini kaç kez kullandığıdır — kelime bazlı arama loglaması değildir.</p>

  <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
    @forelse($rows as $i => $row)
      <a href="{{ brand_route('facilities.index', array_filter(['city' => $row->city_slug, 'category' => $row->category_slug])) }}"
         class="flex items-center gap-3 py-3 {{ ! $loop->last ? 'border-b border-gray-50' : '' }} group">
        <span class="w-6 text-sm font-black text-gray-400">{{ $i + 1 }}</span>
        <span class="flex-1">
          <span class="block font-bold text-gray-950 group-hover:text-primary">{{ $row->city_name }}{{ $row->category_name ? ' — '.$row->category_name : '' }}</span>
        </span>
        <span class="text-sm font-black text-gray-900">{{ number_format($row->total) }} arama</span>
      </a>
    @empty
      <p class="text-sm text-gray-400 py-6 text-center">Henüz yeterli arama verisi yok.</p>
    @endforelse
  </div>
</div>
@endsection
