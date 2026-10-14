@extends('admin.layout')
@section('title', 'Pusula Anketleri')

@section('content')
<div class="flex items-center justify-between mb-2 flex-wrap gap-3">
  <div>
    <h1 class="text-2xl font-bold">🧭 Pusula Anketleri</h1>
    <p class="text-sm text-gray-500 mt-1">
      Sitedeki Pusula'yı (il/yaş/durum soruları) dolduran herkes burada görünür — isim/telefon bırakmasalar bile,
      bölgedeki gerçek ihtiyacı görmek için. Formu bitirmeden yarıda bıraksalar bile o ana kadar girdikleri
      "⏳ Yarıda kaldı" rozetiyle görünür. İsim/telefon bırakanlar aynı zamanda ilgili kurumların
      "Fırsat Havuzu" panelinde de görünür ve doğrudan aranabilir olur.
    </p>
  </div>
</div>

@if(session('success'))
  <div class="bg-green-100 text-green-800 px-4 py-3 rounded-lg text-sm mb-4 mt-4">{{ session('success') }}</div>
@endif

{{-- 13 Ekim 2026 (9): kullanıcının talebi - "zamanla bölüm/il/durum gibi
     segmentlerde filtrelemeye ihtiyaç olacak". Admin dashboard'daki il/bölüm
     filtresiyle (bkz. admin/dashboard.blade.php) AYNI desen: native GET
     formu, seçim değişince otomatik gönderir, JS'siz de <noscript> butonuyla
     çalışır, #pusula-liste çapası sayesinde filtrelemede sayfa en üste atmaz. --}}
<form method="GET" action="{{ url()->current() }}#pusula-liste" class="flex items-center gap-2 flex-wrap mt-4">
  <select name="il" onchange="this.form.submit()" class="border rounded-lg px-2 py-1.5 text-xs bg-white">
    <option value="">Tüm iller</option>
    @foreach($filterCities as $city)
      <option value="{{ $city->id }}" @selected(request('il') == $city->id)>{{ $city->name }}</option>
    @endforeach
  </select>
  <select name="bolum" onchange="this.form.submit()" class="border rounded-lg px-2 py-1.5 text-xs bg-white">
    <option value="">Tüm bölümler</option>
    @foreach($filterSections as $slug => $section)
      <option value="{{ $slug }}" @selected(request('bolum') === $slug)>{{ $section['title'] }}</option>
    @endforeach
  </select>
  <select name="durum" onchange="this.form.submit()" class="border rounded-lg px-2 py-1.5 text-xs bg-white">
    <option value="">Tüm durumlar</option>
    <option value="tamamlandi" @selected(request('durum') === 'tamamlandi')>✅ Tamamlandı</option>
    <option value="yarida" @selected(request('durum') === 'yarida')>⏳ Yarıda kaldı</option>
  </select>
  <select name="iletisim" onchange="this.form.submit()" class="border rounded-lg px-2 py-1.5 text-xs bg-white">
    <option value="">İletişim farketmez</option>
    <option value="var" @selected(request('iletisim') === 'var')>📞 İletişime açık</option>
    <option value="yok" @selected(request('iletisim') === 'yok')>Sadece istatistik</option>
  </select>
  <noscript><button type="submit" class="border rounded-lg px-3 py-1.5 text-xs font-semibold bg-white">Filtrele</button></noscript>
  @if(request()->anyFilled(['il', 'bolum', 'durum', 'iletisim']))
    <a href="{{ route('admin.care-advisor-leads.index') }}#pusula-liste" class="text-xs text-gray-400 hover:text-gray-600 underline">Filtreleri temizle</a>
  @endif
</form>

<div id="pusula-liste" class="bg-white rounded-xl shadow-sm divide-y divide-gray-100 mt-4">
  @forelse($leads as $lead)
    <a href="{{ route('admin.care-advisor-leads.show', $lead) }}" class="p-4 flex items-center justify-between gap-3 flex-wrap hover:bg-gray-50">
      <div class="min-w-0">
        <span class="font-bold text-gray-900">{{ $lead->name ?: 'İsim girilmemiş' }}</span>
        @if($lead->section)
          <span class="ml-1 text-[11px] bg-indigo-100 text-indigo-700 px-1.5 py-0.5 rounded-full font-semibold whitespace-nowrap">{{ $filterSections[$lead->section]['title'] ?? $lead->section }}</span>
        @endif
        @if($lead->completed_at)
          <span class="ml-1 text-[11px] bg-blue-100 text-blue-700 px-1.5 py-0.5 rounded-full font-semibold whitespace-nowrap">✅ Tamamlandı</span>
        @else
          <span class="ml-1 text-[11px] bg-amber-100 text-amber-700 px-1.5 py-0.5 rounded-full font-semibold whitespace-nowrap">⏳ Yarıda kaldı</span>
        @endif
        @if($lead->phone)
          <span class="ml-1 text-[11px] bg-green-100 text-green-700 px-1.5 py-0.5 rounded-full font-semibold whitespace-nowrap">📞 İletişime açık</span>
        @else
          <span class="ml-1 text-[11px] bg-gray-100 text-gray-500 px-1.5 py-0.5 rounded-full font-semibold whitespace-nowrap">Sadece istatistik</span>
        @endif
        <div class="text-sm text-gray-500 mt-1">
          {{ $lead->city->name ?? 'İl belirtilmemiş' }}@if($lead->district) / {{ $lead->district }} @endif @if($lead->category) · {{ $lead->category->name }} @endif
          @if($lead->patient_age) · {{ $lead->patient_age }} yaş @endif
          @if($lead->gender) · {{ $lead->gender }} @endif
        </div>
        <div class="flex items-center gap-2 flex-wrap text-xs text-gray-600 mt-2">
          @if($lead->condition)<span class="bg-gray-100 px-2 py-0.5 rounded-full">{{ $lead->condition }}</span>@endif
          @if($lead->budget_max)<span class="bg-gray-100 px-2 py-0.5 rounded-full">Bütçe: {{ number_format($lead->budget_max, 0, ',', '.') }} TL'ye kadar</span>@endif
          @if($lead->phone)<span class="bg-gray-100 px-2 py-0.5 rounded-full">📞 {{ $lead->phone }}</span>@endif
          @foreach($lead->concerns ?? [] as $concern)<span class="bg-gray-100 px-2 py-0.5 rounded-full">{{ $concernLabels[$concern] ?? $concern }}</span>@endforeach
        </div>
      </div>
      <span class="text-xs text-gray-400 whitespace-nowrap">{{ $lead->created_at->format('d.m.Y H:i') }}</span>
    </a>
  @empty
    <div class="p-8 text-center text-gray-400">Henüz kayıt yok.</div>
  @endforelse
</div>
<div class="mt-6">{{ $leads->links() }}</div>
@endsection
