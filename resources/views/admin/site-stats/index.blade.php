@extends('admin.layout')
@section('title', 'Site İstatistikleri')

@section('content')
<h1 class="text-2xl font-bold mb-6">Sitelere Giriş Sayıları & Aile Konumları</h1>

<h2 class="font-bold text-lg mb-3">Sitelere Giriş Sayıları</h2>
<p class="text-xs text-gray-400 mb-4">"Giriş" burada bir tarayıcı oturumunun o markanın sitesine bir gün içindeki ilk ziyaretini ifade eder (aynı oturumda tekrar sayılmaz).</p>
<div class="grid md:grid-cols-3 gap-4 mb-10">
  @foreach($visitStats as $slug => $stat)
    <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-100">
      <div class="font-black text-gray-950 mb-3">{{ $stat['name'] }}</div>
      <div class="grid grid-cols-2 gap-3 text-sm">
        <div><div class="text-gray-500 text-xs">Bugün</div><div class="font-black text-lg">{{ number_format($stat['today']) }}</div></div>
        <div><div class="text-gray-500 text-xs">Son 7 gün</div><div class="font-black text-lg">{{ number_format($stat['last_7_days']) }}</div></div>
        <div><div class="text-gray-500 text-xs">Son 30 gün</div><div class="font-black text-lg">{{ number_format($stat['last_30_days']) }}</div></div>
        <div><div class="text-gray-500 text-xs">Toplam</div><div class="font-black text-lg">{{ number_format($stat['all_time']) }}</div></div>
      </div>
    </div>
  @endforeach
</div>

@if($dailySeries->isNotEmpty())
  @php $maxDaily = $dailySeries->flatMap(fn($rows) => $rows->pluck('count'))->max() ?: 1; @endphp
  <h2 class="font-bold text-lg mb-3">Son 30 Gün Trend</h2>
  <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 mb-10 overflow-x-auto">
    <div class="flex items-end gap-1 h-32 min-w-[600px]">
      @foreach($dailySeries as $date => $rows)
        @php $dayTotal = $rows->sum('count'); @endphp
        <div class="flex-1 bg-primary/80 rounded-t" style="height: {{ max(4, round($dayTotal / $maxDaily * 100)) }}%" title="{{ $date }}: {{ $dayTotal }}"></div>
      @endforeach
    </div>
    <div class="text-xs text-gray-400 mt-2">{{ $dailySeries->keys()->first() }} — {{ $dailySeries->keys()->last() }}</div>
  </div>
@endif

<h2 class="font-bold text-lg mb-3">Kayıt Olan Ailelerin Konumları</h2>
<div class="grid sm:grid-cols-4 gap-4 mb-6">
  <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-100">
    <div class="text-xs text-gray-500">Toplam Aile</div>
    <div class="text-2xl font-black mt-1">{{ number_format($totalFamilies) }}</div>
  </div>
  <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-100">
    <div class="text-xs text-gray-500">Konum Paylaşan</div>
    <div class="text-2xl font-black mt-1">{{ number_format($withLocation) }} <span class="text-sm text-gray-400 font-normal">/ {{ number_format($totalFamilies) }}</span></div>
  </div>
  <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-100">
    <div class="text-xs text-gray-500">E-posta Doğrulayan</div>
    <div class="text-2xl font-black mt-1">{{ number_format($verifiedCount) }} <span class="text-sm text-gray-400 font-normal">/ {{ number_format($totalFamilies) }}</span></div>
  </div>
  <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-100">
    <div class="text-xs text-gray-500">Farklı Şehir</div>
    <div class="text-2xl font-black mt-1">{{ $cityCounts->count() }}</div>
  </div>
</div>

@if($cityCounts->isNotEmpty())
  @php $maxCity = $cityCounts->max('total'); @endphp
  <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 mb-10">
    <h3 class="font-bold mb-3">Şehre Göre Dağılım</h3>
    <div class="space-y-2">
      @foreach($cityCounts as $row)
        <div class="flex items-center gap-3">
          <span class="w-28 text-sm font-semibold text-gray-700 truncate">{{ $row->signup_city_name }}</span>
          <span class="flex-1 bg-gray-100 rounded-full h-3 overflow-hidden"><span class="block h-full bg-primary rounded-full" style="width: {{ max(4, round($row->total / $maxCity * 100)) }}%"></span></span>
          <span class="w-10 text-right text-sm font-black">{{ $row->total }}</span>
        </div>
      @endforeach
    </div>
  </div>
@endif

<form method="GET" class="mb-4 flex gap-3">
  <select name="brand" onchange="this.form.submit()" class="border rounded-lg px-3 py-2 text-sm">
    <option value="">Tüm Markalar</option>
    @foreach($brands as $slug => $b)<option value="{{ $slug }}" @selected(request('brand')===$slug)>{{ $b['name'] }}</option>@endforeach
  </select>
  <select name="has_location" onchange="this.form.submit()" class="border rounded-lg px-3 py-2 text-sm">
    <option value="">Konum Farketmez</option>
    <option value="1" @selected(request('has_location')==='1')>Sadece konum paylaşanlar</option>
    <option value="0" @selected(request('has_location')==='0')>Sadece konum paylaşmayanlar</option>
  </select>
</form>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
  <table class="w-full text-sm">
    <thead class="bg-gray-50 text-left text-gray-500"><tr><th class="p-3">Ad</th><th class="p-3">Marka</th><th class="p-3">Şehir (yaklaşık)</th><th class="p-3">E-posta</th><th class="p-3">Rıza Tarihi</th><th class="p-3">Kayıt Tarihi</th><th class="p-3"></th></tr></thead>
    <tbody class="divide-y">
      @forelse($families as $family)
        <tr>
          <td class="p-3"><div class="font-semibold">{{ $family->name }}</div><div class="text-xs text-gray-400">{{ $family->email }}</div></td>
          <td class="p-3 text-gray-500">{{ $brands[$family->registered_brand]['name'] ?? $family->registered_brand }}</td>
          <td class="p-3">{{ $family->signup_city_name ?? '—' }}</td>
          <td class="p-3">
            @if($family->hasVerifiedEmail())
              <span class="bg-green-100 text-green-700 text-xs font-semibold px-2 py-0.5 rounded-full">Doğrulandı</span>
            @else
              <span class="bg-gray-100 text-gray-500 text-xs font-semibold px-2 py-0.5 rounded-full">Bekliyor</span>
            @endif
          </td>
          <td class="p-3 text-gray-400">{{ $family->consent_accepted_at?->format('d.m.Y H:i') ?? '—' }}</td>
          <td class="p-3 text-gray-400">{{ $family->created_at->format('d.m.Y H:i') }}</td>
          <td class="p-3 text-right">
            @if($family->signup_lat && $family->signup_lng)
              <a href="https://www.google.com/maps?q={{ $family->signup_lat }},{{ $family->signup_lng }}" target="_blank" class="text-primary text-xs font-semibold">Haritada gör →</a>
            @endif
          </td>
        </tr>
      @empty
        <tr><td colspan="7" class="p-8 text-center text-gray-500">Kayıt bulunamadı.</td></tr>
      @endforelse
    </tbody>
  </table>
</div>
<div class="mt-6">{{ $families->links() }}</div>
@endsection
