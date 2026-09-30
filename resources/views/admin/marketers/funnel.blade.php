@extends('admin.layout')
@section('title', 'Satış Hunisi')

@section('content')
<div class="flex items-center justify-between mb-2">
  <h1 class="text-2xl font-bold">Pazarlamacı Satış Hunisi</h1>
  <a href="{{ route('admin.marketers.conversations') }}" class="text-sm font-semibold text-gray-700 underline">Tüm görüşme kayıtlarını gör →</a>
</div>
<p class="text-sm text-gray-500 mb-6">Her kurum, pazarlamacı notlarında şimdiye kadar ulaştığı EN İLERİ aşamaya göre sayılır — örn. "Fiyat İstedi" işaretlenen bir kurum, "Yetkiliyle Görüştü" ve "Detay Dinledi" aşamalarını da geçmiş sayılır.</p>

@if($stages[0]['count'] === 0)
  <div class="bg-white rounded-xl shadow-sm p-8 text-center text-gray-400">Henüz hiçbir kurum aranmamış.</div>
@else
  <div class="bg-white rounded-xl shadow-sm p-6">
    @php($maxCount = $stages[0]['count'] ?: 1)
    <div class="space-y-3">
      @foreach($stages as $stage)
        <div>
          <div class="flex items-center justify-between text-sm mb-1">
            <span class="font-semibold text-gray-800">{{ $stage['label'] }}</span>
            <span class="text-gray-500">
              <strong class="text-gray-900">{{ $stage['count'] }}</strong> kurum
              · %{{ $stage['percent_of_total'] }} toplam
              @if($stage['percent_of_prev'] !== null)
                · %{{ $stage['percent_of_prev'] }} önceki adımdan
              @endif
            </span>
          </div>
          <div class="w-full bg-gray-100 rounded-lg h-8 overflow-hidden">
            <div class="h-8 rounded-lg flex items-center justify-end px-3 text-white text-xs font-bold" style="width: {{ max(4, round($stage['count'] / $maxCount * 100)) }}%; background: linear-gradient(90deg, #0b5d8c, #1e6f5c);">
              {{ $stage['count'] > 0 ? $stage['count'] : '' }}
            </div>
          </div>
        </div>
      @endforeach
    </div>
  </div>
@endif
@endsection
