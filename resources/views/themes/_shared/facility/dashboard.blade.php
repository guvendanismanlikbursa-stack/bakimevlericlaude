@extends('layouts.brand')

@section('content')
@php
  $quoteStatus = [
    'pending' => 'Beklemede',
    'accepted' => 'Kabul edildi',
    'declined' => 'Reddedildi',
  ];
  $profileQuality = $facility->profileQuality();
  $missingExtraCount = max(0, count($profileQuality['missing']) - 3);
@endphp

@php $primary = current_brand()['primary_color']; @endphp
{{-- 12 Agustos 2026: kullanicinin talebi - kurum paneli sayfalari duz
     beyaz basliklarla "kod odakli/hazir kalip" hissi veriyordu; artik
     diger sayfalarla ayni gorsel dili (renkli gradyan serit) kullaniyor. --}}
<div style="background: linear-gradient(135deg, {{ $primary }}, {{ $primary }}cc);" class="text-white">
  <div class="max-w-6xl mx-auto px-4 py-8 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
    <div>
      <p class="text-sm text-white/70">Kurum Paneli</p>
      <h1 class="text-2xl font-black">{{ $facility->name }}</h1>
      <p class="text-sm text-white/80 mt-1">{{ $user->name }} · {{ $facility->category->name ?: 'Kategori yok' }} · {{ $facility->city->name ?: 'Şehir yok' }}</p>
    </div>
    <div class="flex items-center gap-3">
      <a href="{{ brand_route('facility.profile.edit') }}" class="bg-white px-4 py-2 rounded-lg text-sm font-black" style="color: {{ $primary }};">Profili Düzenle</a>
      <form method="POST" action="{{ brand_route('facility.logout') }}">@csrf<button class="text-sm font-semibold text-white/80 hover:text-white">Çıkış Yap</button></form>
    </div>
  </div>
</div>

<div class="max-w-6xl mx-auto px-4 py-10">

  @if(isset($facilityInBrandScope) && ! $facilityInBrandScope)
    <div class="mb-6 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
      Bu kurum hesabı bu sitede açılabilir, ancak kurum kategorisi aktif sitenin hizmet kapsamına girmediği için bu siteden yeni talep alamaz veya teklif veremez.
    </div>
  @endif

  @if($profileQuality['score'] < 100)
    <a href="{{ brand_route('facility.profile.edit') }}" class="block mb-6 rounded-xl border border-amber-200 bg-amber-50 p-5 hover:bg-amber-100 transition">
      <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <div class="text-sm font-black text-amber-800">Profiliniz %{{ $profileQuality['score'] }} tamamlandı</div>
          <p class="text-sm text-amber-700 mt-1">
            Tam profil daha fazla ziyaretçi güveni ve daha çok teklif talebi demektir. Eksik:
            <strong>{{ implode(', ', array_slice($profileQuality['missing'], 0, 3)) }}</strong>{{ $missingExtraCount > 0 ? ' ve '.$missingExtraCount.' eksik daha' : '' }}.
          </p>
        </div>
        <div class="w-full sm:w-48 shrink-0">
          <div class="h-2.5 rounded-full bg-amber-200 overflow-hidden"><div class="h-full bg-amber-600" style="width: {{ $profileQuality['score'] }}%"></div></div>
          <div class="text-xs font-semibold text-amber-700 mt-2 text-right">Şimdi tamamla →</div>
        </div>
      </div>
    </a>
  @endif

  <div class="mb-8 rounded-xl border border-gray-100 bg-white p-5 shadow-sm">
    <h2 class="font-bold text-lg mb-1">Profiliniz Ne Kadar İlgi Görüyor</h2>
    <p class="text-sm text-gray-500 mb-4">Ailelerin kurum profilinizle ilgili gerçek etkileşimleri.</p>
    <div class="grid grid-cols-3 gap-3">
      <div class="text-center">
        <div class="text-2xl font-black text-gray-950">{{ number_format($performance['views_count']) }}</div>
        <div class="text-xs text-gray-500 mt-1">Profil Görüntülenme</div>
      </div>
      <div class="text-center">
        <div class="text-2xl font-black text-gray-950">{{ number_format($performance['favorites_count']) }}</div>
        <div class="text-xs text-gray-500 mt-1">Favoriye Eklenme</div>
      </div>
      <div class="text-center">
        <div class="text-2xl font-black text-gray-950">{{ number_format($performance['reviews_count']) }}</div>
        <div class="text-xs text-gray-500 mt-1">Onaylı Yorum</div>
      </div>
    </div>
  </div>

  {{-- 12 Agustos 2026: kullanicinin talebi - "gecen aya gore nasilim
       goremiyorum, para harcayip sonucunu goremiyorum" --}}
  <div class="mb-8 rounded-xl border border-gray-100 bg-white p-5 shadow-sm">
    <h2 class="font-bold text-lg mb-1">Performans Trendi</h2>
    <p class="text-sm text-gray-500 mb-4">Son 7 gün, önceki 7 günle karşılaştırıldığında.</p>

    @if(!$trend['has_data'])
      <p class="text-sm text-gray-400">Trend verisi birikmeye başladı, birkaç gün sonra burada görünecek.</p>
    @else
      <div class="grid sm:grid-cols-3 gap-3 mb-5">
        @php
          $viewsDiff = $trend['views_this_week'] - $trend['views_last_week'];
          $viewsPct = $trend['views_last_week'] > 0 ? round($viewsDiff / $trend['views_last_week'] * 100) : ($trend['views_this_week'] > 0 ? 100 : 0);
          $offersDiff = $trend['offer_requests_this_week'] - $trend['offer_requests_last_week'];
          $offersPct = $trend['offer_requests_last_week'] > 0 ? round($offersDiff / $trend['offer_requests_last_week'] * 100) : ($trend['offer_requests_this_week'] > 0 ? 100 : 0);
        @endphp
        <div class="rounded-lg bg-gray-50 p-4">
          <div class="text-xs text-gray-500">Bu hafta görüntülenme</div>
          <div class="text-xl font-black text-gray-950 mt-1">{{ number_format($trend['views_this_week']) }}</div>
          <div class="text-xs font-bold mt-1 {{ $viewsDiff >= 0 ? 'text-green-600' : 'text-red-600' }}">{{ $viewsDiff >= 0 ? '▲' : '▼' }} %{{ abs($viewsPct) }} geçen haftaya göre</div>
        </div>
        <div class="rounded-lg bg-gray-50 p-4">
          <div class="text-xs text-gray-500">Bu hafta gelen talep</div>
          <div class="text-xl font-black text-gray-950 mt-1">{{ number_format($trend['offer_requests_this_week']) }}</div>
          <div class="text-xs font-bold mt-1 {{ $offersDiff >= 0 ? 'text-green-600' : 'text-red-600' }}">{{ $offersDiff >= 0 ? '▲' : '▼' }} %{{ abs($offersPct) }} geçen haftaya göre</div>
        </div>
        <div class="rounded-lg bg-primary/10 p-4">
          <div class="text-xs text-gray-500">Bu ay kabul edilen tekliflerin toplam değeri</div>
          <div class="text-xl font-black text-gray-950 mt-1">{{ number_format($trend['lead_value_this_month'], 0, ',', '.') }} ₺</div>
          <div class="text-xs text-gray-500 mt-1">Ödediğiniz bakiyenin karşılığı</div>
        </div>
      </div>

      @if(count($trend['daily_deltas']) > 1)
        @php $maxDelta = max(1, collect($trend['daily_deltas'])->max('views_delta')); @endphp
        <div class="text-xs font-black text-gray-500 mb-2">Günlük görüntülenme (son 14 gün)</div>
        <div class="flex items-end gap-1.5 h-20">
          @foreach($trend['daily_deltas'] as $day)
            <div class="flex-1 flex flex-col items-center gap-1" title="{{ $day['date'] }}: {{ $day['views_delta'] }} görüntülenme">
              <div class="w-full rounded-t bg-primary/70" style="height: {{ max(3, round($day['views_delta'] / $maxDelta * 64)) }}px;"></div>
              <div class="text-[10px] text-gray-400">{{ $day['date'] }}</div>
            </div>
          @endforeach
        </div>
      @endif
    @endif
  </div>

  {{-- 13 Agustos 2026: kullanicinin talebi - "hangi gorselim daha cok
       ilgi cekiyor goremiyorum". --}}
  @if($topImages->isNotEmpty())
    <div class="mb-8 rounded-xl border border-gray-100 bg-white p-5 shadow-sm">
      <h2 class="font-bold text-lg mb-1">En Çok İlgi Gören Görselleriniz</h2>
      <p class="text-sm text-gray-500 mb-4">Ziyaretçilerin galeride büyütüp incelediği görseller.</p>
      <div class="grid grid-cols-3 gap-3">
        @foreach($topImages as $img)
          <div class="relative rounded-lg overflow-hidden border border-gray-100">
            <img src="{{ asset('storage/'.$img->path) }}" class="w-full h-24 object-cover" alt="Kurum görseli">
            <div class="absolute bottom-0 inset-x-0 bg-gray-950/70 text-white text-xs font-bold text-center py-1">{{ number_format($img->views_count) }} görüntülenme</div>
          </div>
        @endforeach
      </div>
    </div>
  @endif

  <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 mb-8">
    <div class="bg-white rounded-lg shadow-sm p-4 border border-gray-100">
      <div class="text-xs text-gray-500">Ücretsiz Hak</div>
      <div class="text-2xl font-bold mt-1">{{ $facility->free_quote_credits }}</div>
    </div>
    <div class="bg-white rounded-lg shadow-sm p-4 border border-gray-100">
      <div class="text-xs text-gray-500">Bakiye</div>
      <div class="text-2xl font-bold mt-1">{{ number_format($facility->balance,2,',','.') }}₺</div>
    </div>
    <div class="bg-white rounded-lg shadow-sm p-4 border border-gray-100">
      <div class="text-xs text-gray-500">Doğrudan Talep</div>
      <div class="text-2xl font-bold mt-1">{{ $stats['direct_requests'] }}</div>
    </div>
    <div class="bg-white rounded-lg shadow-sm p-4 border border-gray-100">
      <div class="text-xs text-gray-500">Uygun Talep</div>
      <div class="text-2xl font-bold mt-1">{{ $stats['broadcast_leads'] }}</div>
    </div>
    <div class="bg-white rounded-lg shadow-sm p-4 border border-gray-100">
      <div class="text-xs text-gray-500">Tekliflerim</div>
      <div class="text-2xl font-bold mt-1">{{ $stats['sent_quotes'] }}</div>
    </div>
    <a href="{{ brand_route('facility.wallet.index') }}" class="bg-primary text-white rounded-lg shadow-sm p-4 flex items-center justify-center font-semibold text-center">Bakiye Yükle →</a>
    <a href="{{ brand_route('facility.packages.index') }}" class="border border-primary text-primary rounded-lg shadow-sm p-4 flex items-center justify-center font-semibold text-center">Paketler →</a>
    <a href="{{ brand_route('facility.questions.index') }}" class="border border-gray-200 text-gray-700 rounded-lg shadow-sm p-4 flex items-center justify-center font-semibold text-center">Aile Soruları →</a>
    <a href="{{ brand_route('facility.reviews.index') }}" class="border border-gray-200 text-gray-700 rounded-lg shadow-sm p-4 flex items-center justify-center font-semibold text-center">Yorumlarım →</a>
    @if($user->role === 'owner')
      <a href="{{ brand_route('facility.team.index') }}" class="border border-gray-200 text-gray-700 rounded-lg shadow-sm p-4 flex items-center justify-center font-semibold text-center">Ekip Yönetimi →</a>
    @endif
    <a href="{{ brand_route('facility.notifications.index') }}" class="border border-gray-200 text-gray-700 rounded-lg shadow-sm p-4 flex items-center justify-center font-semibold text-center">Bildirimler →</a>
    <a href="{{ brand_route('facility.password.change') }}" class="border border-gray-200 text-gray-700 rounded-lg shadow-sm p-4 flex items-center justify-center font-semibold text-center">Şifre Değiştir →</a>
  </div>

  <div class="grid lg:grid-cols-[1fr_320px] gap-6">
    <div>
      <section class="mb-8">
        <div class="flex items-center justify-between mb-3">
          <h2 class="font-bold text-lg">Size Doğrudan Gelen Talepler</h2>
          <span class="text-xs text-gray-400">{{ $directRequests->count() }} kayıt</span>
        </div>
        <div class="space-y-3">
          @forelse($directRequests as $req)
            @include('themes._shared.facility._request-card', ['req' => $req, 'facility' => $facility])
          @empty
            <div class="bg-white rounded-lg border border-dashed border-gray-300 p-5 text-sm text-gray-500">Henüz doğrudan talep yok.</div>
          @endforelse
        </div>
      </section>

      <section class="mb-8">
        <div class="flex items-center justify-between mb-3">
          <h2 class="font-bold text-lg">Şehir/Kategorinize Uygun Yeni Talepler</h2>
          <span class="text-xs text-gray-400">{{ $broadcastLeads->count() }} kayıt</span>
        </div>
        <div class="space-y-3">
          @forelse($broadcastLeads as $req)
            @include('themes._shared.facility._request-card', ['req' => $req, 'facility' => $facility])
          @empty
            <div class="bg-white rounded-lg border border-dashed border-gray-300 p-5 text-sm text-gray-500">Şu anda uygun yeni talep yok.</div>
          @endforelse
        </div>
      </section>
    </div>

    <aside class="space-y-4">
      <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-5">
        <h2 class="font-bold mb-3">Kurum Durumu</h2>
        <div class="space-y-3 text-sm">
          <div class="flex justify-between gap-4"><span class="text-gray-500">Yayın</span><span class="font-semibold">{{ $facility->is_published ? 'Yayında' : 'Pasif' }}</span></div>
          <div class="flex justify-between gap-4"><span class="text-gray-500">Sahiplenme</span><span class="font-semibold">{{ $facility->is_claimed ? 'Onaylı' : 'Onaysız' }}</span></div>
          <div class="flex justify-between gap-4"><span class="text-gray-500">Kapasite</span><span class="font-semibold">{{ $facility->capacity ?: '-' }}</span></div>
          <div class="flex justify-between gap-4"><span class="text-gray-500">Fiyat Aralığı</span><span class="font-semibold text-right">@if($facility->price_min || $facility->price_max) {{ number_format($facility->price_min,0,',','.') }}₺ - {{ number_format($facility->price_max,0,',','.') }}₺ @else - @endif</span></div>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-5">
        <h2 class="font-bold mb-3">Teklif Özeti</h2>
        <div class="grid grid-cols-2 gap-3 text-sm">
          <div class="rounded-lg bg-gray-50 p-3"><div class="text-gray-500 text-xs">Bekleyen</div><div class="font-bold text-lg">{{ $stats['pending_quotes'] }}</div></div>
          <div class="rounded-lg bg-green-50 p-3"><div class="text-gray-500 text-xs">Kabul</div><div class="font-bold text-lg">{{ $stats['accepted_quotes'] }}</div></div>
        </div>
      </div>
    </aside>
  </div>

  <section class="mt-2">
    <h2 class="font-bold text-lg mb-3">Gönderdiğim Teklifler</h2>
    <div class="bg-white rounded-lg shadow-sm overflow-hidden border border-gray-100">
      <table class="w-full text-sm">
        <thead class="bg-gray-50 text-left text-gray-500"><tr><th class="p-3">Aile</th><th class="p-3">Talep</th><th class="p-3">Fiyat</th><th class="p-3">Durum</th><th class="p-3"></th></tr></thead>
        <tbody class="divide-y">
          @forelse($sentQuotes as $q)
            <tr>
              <td class="p-3">{{ $q->offerRequest->familyUser->name ?? $q->offerRequest->full_name ?? '-' }}</td>
              <td class="p-3 text-gray-500">{{ $q->offerRequest->category->name ?? '-' }} · {{ $q->offerRequest->city->name ?? '-' }}</td>
              <td class="p-3">{{ number_format($q->price,0,',','.') }}₺</td>
              <td class="p-3">{{ $quoteStatus[$q->status] ?? $q->status }}</td>
              <td class="p-3">
                @if($q->offerRequest->facility_id || $q->offerRequest->accepted_quote_id === $q->id)
                  <a href="{{ brand_route('facility.thread', $q->offerRequest) }}" class="text-primary font-semibold">Mesajlar</a>
                @else
                  <span class="text-gray-400 text-xs">Kabul edilirse açılır</span>
                @endif
              </td>
            </tr>
          @empty
            <tr><td class="p-3 text-gray-400" colspan="5">Henüz teklif göndermediniz.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </section>
</div>
@endsection