@extends('admin.layout')
@section('title', 'Canlı Sohbet İstatistikleri')

@section('content')
<div class="flex items-center justify-between mb-6">
  <h1 class="text-2xl font-bold">Canlı Sohbet İstatistikleri</h1>
  <a href="{{ route('admin.chat.index') }}" class="text-sm text-blue-600">← Sohbet listesine dön</a>
</div>

<div class="grid sm:grid-cols-2 gap-4 mb-8">
  <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-100">
    <div class="text-xs text-gray-500">Toplam Ziyaretçi</div>
    <div class="text-3xl font-black mt-1">{{ number_format($totalGuests) }}</div>
  </div>
  <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-100">
    <div class="text-xs text-gray-500">Toplam Sohbet (bölüm bazlı)</div>
    <div class="text-3xl font-black mt-1">{{ number_format($totalThreads) }}</div>
  </div>
</div>

<div class="grid lg:grid-cols-2 gap-6">
  <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-100">
    <div class="font-black text-gray-950 mb-3">Bölüm Dağılımı</div>
    @php $intentLabels = ['sohbet' => '💬 Sohbet', 'dertlesme' => '🤍 Dertleşme', 'fikir' => '💡 Fikir', 'temsilci' => '🎧 Temsilci']; @endphp
    <div class="space-y-2">
      @foreach($byIntent as $row)
        @php $pct = $totalThreads > 0 ? round($row->thread_count / $totalThreads * 100) : 0; @endphp
        <div>
          <div class="flex items-center justify-between text-sm mb-1">
            <span>{{ $intentLabels[$row->intent] ?? $row->intent }}</span>
            <span class="text-gray-500">{{ $row->thread_count }} ({{ $pct }}%)</span>
          </div>
          <div class="w-full bg-gray-100 rounded-full h-2"><div class="bg-blue-600 h-2 rounded-full" style="width: {{ $pct }}%"></div></div>
        </div>
      @endforeach
    </div>
  </div>

  <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-100">
    <div class="font-black text-gray-950 mb-3">Markaya Göre Ziyaretçi</div>
    <div class="space-y-2">
      @foreach($byBrand as $row)
        <div class="flex items-center justify-between text-sm">
          <span>{{ $row->brand }}</span>
          <span class="font-bold">{{ $row->guest_count }}</span>
        </div>
      @endforeach
    </div>
  </div>

  <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-100">
    <div class="font-black text-gray-950 mb-3">En Çok Ziyaretçi Gelen İller</div>
    @if($byCity->isEmpty())
      <div class="text-sm text-gray-400">Henüz şehir verisi yok.</div>
    @else
      <div class="space-y-2">
        @foreach($byCity as $row)
          <div class="flex items-center justify-between text-sm">
            <span>{{ $row->city_name }}</span>
            <span class="font-bold">{{ $row->guest_count }}</span>
          </div>
        @endforeach
      </div>
    @endif
  </div>

  <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-100">
    <div class="font-black text-gray-950 mb-3">Yaş Dağılımı</div>
    <div class="space-y-2">
      @foreach($ageBuckets as $label => $count)
        <div class="flex items-center justify-between text-sm">
          <span>{{ $label }}</span>
          <span class="font-bold">{{ $count }}</span>
        </div>
      @endforeach
    </div>
  </div>
</div>
@endsection
