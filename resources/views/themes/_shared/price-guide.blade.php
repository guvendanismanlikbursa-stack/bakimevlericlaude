@extends('layouts.brand')
@section('title', $city->name.' '.$section['title'].' Fiyatları - Ücret Rehberi')
@section('meta_description', $city->name.' ilinde '.$section['title'].' kurumlarının ortalama, en düşük ve en yüksek fiyat bilgileri.')
@section('content')
<div class="max-w-6xl mx-auto px-4 py-10">
  <div class="text-xs font-black text-primary mb-2">Ücret Rehberi</div>
  <h1 class="text-3xl font-black text-gray-950 mb-2">{{ $city->name }} {{ $section['title'] }} Fiyatları</h1>
  <p class="text-sm text-gray-500 mb-8">{{ $city->name }} ilinde yayında olan {{ $stats['total'] }} {{ $section['title'] }} kurumunun fiyat özeti. Fiyatlar kurumlar tarafından girilir, kesin ücret için kuruma teklif talebi gönderin.</p>

  @if($stats['priced_count'] > 0)
    <div class="grid sm:grid-cols-3 gap-4 mb-10">
      <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-100">
        <div class="text-xs text-gray-500">Ortalama Başlangıç Ücreti</div>
        <div class="text-2xl font-black mt-1">{{ number_format($stats['avg_min'], 0, ',', '.') }} TL</div>
      </div>
      <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-100">
        <div class="text-xs text-gray-500">En Düşük</div>
        <div class="text-2xl font-black mt-1">{{ number_format($stats['min'], 0, ',', '.') }} TL</div>
      </div>
      <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-100">
        <div class="text-xs text-gray-500">En Yüksek</div>
        <div class="text-2xl font-black mt-1">{{ number_format($stats['max'], 0, ',', '.') }} TL</div>
      </div>
    </div>

    @if(count($tierCounts))
      <div class="flex flex-wrap gap-2 mb-10">
        @foreach(['ekonomik' => ['🟢','Ekonomik','bg-green-100 text-green-800'], 'standart' => ['🔵','Standart','bg-blue-100 text-blue-800'], 'premium' => ['🟣','Premium','bg-purple-100 text-purple-800'], 'ultra_premium' => ['🟡','Ultra Premium','bg-amber-100 text-amber-800']] as $key => [$emoji, $label, $classes])
          @if(($tierCounts[$key] ?? 0) > 0)
            <span class="{{ $classes }} text-xs font-semibold px-3 py-1.5 rounded-full">{{ $emoji }} {{ $label }}: {{ $tierCounts[$key] }} kurum</span>
          @endif
        @endforeach
      </div>
    @endif
  @else
    <div class="bg-amber-50 border border-amber-100 text-amber-800 text-sm rounded-xl p-4 mb-10">Bu il için henüz fiyat bilgisi girilmiş kurum yok. Aşağıdaki kurumlardan doğrudan teklif isteyebilirsiniz.</div>
  @endif

  <h2 class="font-black text-xl mb-4">{{ $city->name }} {{ $section['title'] }} Kurumları</h2>
  @if($facilities->isEmpty())
    <div class="text-center py-16 text-gray-500 bg-white rounded-xl border border-dashed">
      <p>Bu il için henüz yayında kurum yok.</p>
    </div>
  @else
    <div class="grid md:grid-cols-3 gap-6">
      @foreach($facilities as $facility)
        @include('themes._shared.partials.facility-card', ['facility' => $facility])
      @endforeach
    </div>
    <div class="mt-8">{{ $facilities->links() }}</div>
  @endif
</div>
@endsection
