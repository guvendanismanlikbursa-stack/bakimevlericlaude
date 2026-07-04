@extends('layouts.brand')
@section('content')
@php
  $variant = $engagementStyle['variant'] ?? 'classic';
  $isCompare = $boardMode === 'compare';
  $storageKey = $brand['slug'] . ':' . ($isCompare ? 'compare' : 'favorites');
@endphp
<section class="{{ $variant === 'dark' ? 'bg-gray-950 text-white' : ($variant === 'warm' ? 'bg-white' : 'bg-gray-50') }} border-b border-gray-100">
  <div class="max-w-6xl mx-auto px-4 py-10">
    <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-5">
      <div>
        <div class="text-sm font-black mb-2 {{ $variant === 'dark' ? 'text-white/60' : 'text-primary' }}">{{ $isCompare ? 'Karşılaştırma masası' : 'Kısa liste' }}</div>
        <h1 class="text-3xl md:text-5xl font-black {{ $variant === 'dark' ? 'text-white' : 'text-gray-950' }}">{{ $isCompare ? ($engagementStyle['compare_title'] ?? 'Kurumları karşılaştır') : ($engagementStyle['favorites_title'] ?? 'Favori kurumlar') }}</h1>
        <p class="mt-3 {{ $variant === 'dark' ? 'text-white/68' : 'text-gray-600' }} max-w-2xl">{{ $isCompare ? 'Liste sayfasında karşılaştırmaya eklediğiniz kurumlar burada yan yana görünür.' : 'Favoriye aldığınız kurumlar bu sayfada saklanır; not ekleyip tekrar değerlendirebilirsiniz.' }}</p>
      </div>
      <div class="flex flex-wrap gap-2">
        <a href="{{ brand_route('engagement.wizard') }}" class="rounded-xl px-4 py-3 font-black {{ $variant === 'dark' ? 'bg-white text-gray-950' : 'btn-primary' }}">Karar sihirbazı</a>
        <a href="{{ brand_route('facilities.index') }}" class="rounded-xl border px-4 py-3 font-black {{ $variant === 'dark' ? 'border-white/20 text-white' : 'bg-white text-gray-700 border-gray-200' }}">Kurumlara git</a>
      </div>
    </div>
  </div>
</section>

<section class="max-w-6xl mx-auto px-4 py-10">
  <div id="board-empty" class="hidden bg-white border border-dashed border-gray-200 rounded-xl p-8 text-center">
    <div class="text-3xl font-black text-gray-950 mb-2">Henüz kurum eklenmedi</div>
    <p class="text-gray-500 mb-5">Kurum liste veya detay sayfalarından {{ $isCompare ? 'karşılaştırmaya ekle' : 'favoriye ekle' }} düğmesini kullanın.</p>
    <a href="{{ brand_route('facilities.index') }}" class="btn-primary inline-flex rounded-xl px-5 py-3 font-black">Kurumları incele</a>
  </div>

  @if($isCompare)
    <div id="board-compare" class="overflow-x-auto bg-white border border-gray-100 rounded-xl shadow-sm"></div>
  @else
    <div id="board-favorites" class="grid md:grid-cols-3 gap-5"></div>
  @endif
</section>

<script>
(function(){
  const storageKey = @json($storageKey);
  const allFacilities = @json($facilitiesForJs);
  const selectedIds = JSON.parse(localStorage.getItem(storageKey) || '[]').map(Number);
  const notesKey = storageKey + ':notes';
  const notes = JSON.parse(localStorage.getItem(notesKey) || '{}');
  const selected = selectedIds.map(function(id){ return allFacilities.find(function(item){ return Number(item.id) === id; }); }).filter(Boolean);
  const empty = document.getElementById('board-empty');
  const compare = document.getElementById('board-compare');
  const favorites = document.getElementById('board-favorites');
  window.removeEngagementItem = function(id) {
    localStorage.setItem(storageKey, JSON.stringify(selectedIds.filter(function(item){ return Number(item) !== Number(id); })));
    location.reload();
  };
  window.saveFavoriteNote = function(id, value) {
    notes[id] = value;
    localStorage.setItem(notesKey, JSON.stringify(notes));
  };
  function esc(value) {
    return String(value || '').replace(/[&<>"']/g, function(ch){ return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[ch]; });
  }
  if (!selected.length) { empty.classList.remove('hidden'); return; }
  if (compare) {
    const headers = selected.map(function(item){
      return '<th class="min-w-[220px] p-4 text-left align-top"><div class="font-black text-gray-950">' + esc(item.name) + '</div><div class="text-xs text-gray-500 mt-1">' + esc(item.city) + ' · ' + esc(item.district) + '</div><button onclick="removeEngagementItem(' + Number(item.id) + ')" class="mt-3 text-xs font-black text-red-600">Kaldır</button></th>';
    }).join('');
    const row = function(label, valueFn) {
      return '<tr class="border-t"><td class="p-4 text-sm font-black text-gray-500 bg-gray-50">' + label + '</td>' + selected.map(function(item){ return '<td class="p-4 text-sm text-gray-700 align-top">' + valueFn(item) + '</td>'; }).join('') + '</tr>';
    };
    compare.innerHTML = '<table class="w-full text-sm"><thead><tr><th class="w-44 p-4 bg-gray-50 text-left text-gray-500">Kriter</th>' + headers + '</tr></thead><tbody>'
      + row('Bölüm', function(i){ return esc(i.section || '-'); })
      + row('Kategori', function(i){ return esc(i.category || '-'); })
      + row('Puan', function(i){ return '★ ' + esc(i.rating); })
      + row('Fiyat', function(i){ return esc(i.price_min); })
      + row('Kapasite', function(i){ return esc(i.capacity); })
      + row('Özellikler', function(i){ return (i.services || []).map(esc).join('<br>') || '-'; })
      + row('Aksiyon', function(i){ return '<a href="' + esc(i.url) + '" class="font-black text-primary">Detaya git →</a>'; })
      + '</tbody></table>';
  }
  if (favorites) {
    favorites.innerHTML = selected.map(function(item){
      const image = item.image ? '<img src="' + esc(item.image) + '" class="w-full h-full object-cover" alt="">' : '';
      return '<article class="bg-white border border-gray-100 rounded-xl overflow-hidden shadow-sm">'
        + '<div class="h-36 bg-gray-100">' + image + '</div>'
        + '<div class="p-4">'
        + '<div class="text-xs font-black text-primary mb-2">' + esc(item.section || item.category || '') + '</div>'
        + '<h2 class="font-black text-gray-950 mb-1">' + esc(item.name) + '</h2>'
        + '<p class="text-sm text-gray-500 mb-3">' + esc(item.city) + ' · ' + esc(item.district) + '</p>'
        + '<p class="text-sm text-gray-600 line-clamp-2 mb-4">' + esc(item.description) + '</p>'
        + '<label class="block mb-4"><span class="text-xs font-black text-gray-500">Kişisel not</span><textarea oninput="saveFavoriteNote(' + Number(item.id) + ', this.value)" class="mt-1 w-full border border-gray-200 rounded-lg px-3 py-2 text-sm" rows="3" placeholder="Görüşme notu, artı/eksi, aranacak kişi...">' + esc(notes[item.id] || '') + '</textarea></label>'
        + '<div class="flex gap-2"><a href="' + esc(item.url) + '" class="flex-1 text-center btn-primary rounded-lg px-3 py-2 text-sm font-black">Detay</a><button onclick="removeEngagementItem(' + Number(item.id) + ')" class="rounded-lg border border-gray-200 px-3 py-2 text-sm font-black text-gray-600">Kaldır</button></div>'
        + '</div></article>';
    }).join('');
  }
})();
</script>
@endsection
