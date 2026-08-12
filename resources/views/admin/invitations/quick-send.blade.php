@extends('admin.layout')
@section('title', 'Hızlı Gönderim')

@section('content')
<div class="mb-6 flex items-center justify-between">
  <div>
    <h1 class="text-2xl font-bold">Hızlı Gönderim</h1>
    <p class="text-sm text-gray-500 mt-1">Kurumları tek tek listede aramak yerine, sırayla tek ekrandan gönderin: WhatsApp'ta Aç → gönderin → Sıradaki'ne basın.</p>
  </div>
  <a href="{{ route('admin.invitations.index') }}" class="text-sm font-semibold text-gray-600">← Listeye Dön</a>
</div>

<form method="GET" class="mb-6 flex gap-2 flex-wrap">
  <select name="city" onchange="this.form.submit()" class="border rounded-lg px-3 py-2 text-sm">
    <option value="">Tüm İller</option>
    @foreach($cities as $city)
      <option value="{{ $city->slug }}" @selected(request('city') === $city->slug)>{{ $city->name }}</option>
    @endforeach
  </select>
  <select name="category" onchange="this.form.submit()" class="border rounded-lg px-3 py-2 text-sm">
    <option value="">Tüm Bölümler / Kategoriler</option>
    @foreach($categories as $category)
      <option value="{{ $category->slug }}" @selected(request('category') === $category->slug)>{{ $category->name }}</option>
    @endforeach
  </select>
  @if(request('city') || request('category'))
    <a href="{{ route('admin.invitations.quick-send') }}" class="border rounded-lg px-3 py-2 text-sm font-semibold text-gray-600">Filtreleri Temizle</a>
  @endif
</form>

@if(! $facility)
  <div class="bg-white rounded-xl border border-dashed border-gray-300 p-10 text-center">
    <div class="text-2xl mb-2">🎉</div>
    <div class="font-bold text-gray-900">Bu filtrede gönderilecek kurum kalmadı</div>
    <p class="text-sm text-gray-500 mt-1">Seçtiğiniz il/kategori için "Gönderilecekler" listesi boş.</p>
  </div>
@else
  <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 max-w-2xl">
    <div class="text-xs font-semibold text-gray-400 mb-1">Kalan: {{ number_format($remaining) }} kurum</div>
    <h2 class="text-xl font-black text-gray-950">{{ $facility->name }}</h2>
    <p class="text-sm text-gray-500 mt-1">{{ $facility->city->name ?? '—' }} · {{ $facility->district ?: '—' }} · {{ $facility->category->name ?? '—' }}</p>
    <p class="text-sm text-gray-700 mt-3"><strong>Telefon:</strong> {{ $facility->phone }}</p>

    <div class="mt-5 bg-gray-50 rounded-lg p-4 text-sm text-gray-600 whitespace-pre-line">{{ facility_invitation_message($facility) }}</div>

    <div class="mt-6 flex flex-wrap gap-3">
      <a href="{{ route('admin.invitations.whatsapp', $facility) }}" target="_blank" rel="noopener"
         class="bg-green-600 text-white rounded-lg px-5 py-3 text-sm font-bold text-center">
        WhatsApp'ta Aç (yeni sekme) →
      </a>
      <a href="{{ route('admin.invitations.quick-send', array_filter(['city' => request('city'), 'category' => request('category')])) }}"
         class="bg-gray-900 text-white rounded-lg px-5 py-3 text-sm font-bold text-center">
        Sıradaki Kurum →
      </a>
      <form method="POST" action="{{ route('admin.invitations.update-status', $facility) }}" class="inline">
        @csrf
        <input type="hidden" name="status" value="unreachable">
        <button class="border border-red-200 text-red-700 rounded-lg px-5 py-3 text-sm font-bold">Ulaşılamıyor, Atla</button>
      </form>
    </div>
  </div>

  <p class="text-xs text-gray-400 mt-4 max-w-2xl">"WhatsApp'ta Aç" yeni sekmede açılır ve durumu otomatik "Açıldı" yapar, bu sekme kapanmaz — mesajı gönderdikten sonra bu sekmeye dönüp "Sıradaki Kurum"a basmanız yeterli.</p>
@endif
@endsection
