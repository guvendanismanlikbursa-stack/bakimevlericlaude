@extends('admin.layout')
@section('title', 'Ücret ve Bilgi Talepleri')

@section('content')
<h1 class="text-2xl font-bold mb-2">Ücret ve Bilgi Talepleri</h1>
<p class="text-sm text-gray-500 mb-4">Ailelerin kurumlardan gönderdiği tüm ücret/bilgi taleplerinin tek listesi. Sahipli kurumların talepleri zaten doğrudan kurumun kendi paneline düşer — burada sadece görüntülenir, işlem yapılamaz.</p>

<div class="flex gap-2 mb-4 flex-wrap">
  @foreach(['tumu' => 'Tümü', 'bekleyen' => 'Bekleyen', 'islem_yapildi' => 'İşlem Yapıldı', 'anlasmali' => 'Anlaşmalı', 'on_kayitli' => 'Ön Kayıtlı', 'sahipli' => 'Sahipli (salt görüntüleme)'] as $k => $label)
    <a href="{{ route('admin.fee-info-requests.index', ['scope' => $k]) }}"
       class="px-4 py-2 rounded-lg text-sm font-semibold border {{ $scope === $k ? 'bg-gray-900 text-white border-gray-900' : 'bg-white text-gray-700 border-gray-200 hover:border-gray-400' }}">
      {{ $label }} <span class="opacity-70">({{ $sayilar[$k] }})</span>
    </a>
  @endforeach
</div>

{{-- 11 Ekim 2026: kullanicinin talebi - genis <table> yerine flex-wrap
     kart (bkz. admin/facilities/_results.blade.php ayni desen). --}}
<div class="bg-white rounded-xl shadow-sm divide-y divide-gray-100">
  @forelse($items as $r)
    @php
      $rozet = ['anlasmali' => ['🤝 Anlaşmalı', 'text-amber-700'], 'on_kayitli' => ['Ön Kayıtlı', 'text-gray-400'], 'sahipli' => ['✓ Sahipli', 'text-green-700']][$r['kurum_durumu']];
    @endphp
    <div class="p-4 flex flex-col gap-2">
      <div class="flex items-start justify-between gap-3 flex-wrap">
        <div class="min-w-0">
          <div class="font-bold text-gray-900">{{ $r['full_name'] }} <span class="font-normal text-gray-500">· {{ $r['phone'] }}@if($r['email']) · {{ $r['email'] }}@endif</span></div>
          <div class="text-xs text-gray-500 mt-0.5">
            {{ $r['kurum']->name }} ({{ $r['kurum']->city?->name }}) ·
            <span class="font-semibold {{ $rozet[1] }}">{{ $rozet[0] }}</span> ·
            {{ $r['tur'] === 'teklif' ? 'Teklif Talebi' : 'Kurum İlgi Talebi' }} ·
            {{ $r['created_at']->format('d.m.Y H:i') }}
            @if($r['tur'] === 'teklif')
              · <a href="{{ route('admin.offer-requests.messages', $r['id']) }}" class="font-semibold text-red-600 hover:underline">Şikayet / Mesajları İncele</a>
            @endif
          </div>
          @if($r['message'])<div class="text-xs text-gray-600 mt-1" title="{{ $r['message'] }}">{{ $r['message'] }}</div>@endif
        </div>
        <div class="flex items-center gap-2 flex-wrap justify-end">
          @if($r['kurum_durumu'] === 'sahipli')
            <span class="text-xs text-gray-400 whitespace-nowrap">Kurumun kendi paneline gitti</span>
          @else
            @if($r['sent_to_facility_at'])
              <span class="text-xs font-semibold text-green-700 whitespace-nowrap">✓ İşlem yapıldı ({{ $r['sent_to_facility_at']->format('d.m.Y H:i') }})</span>
            @else
              <span class="text-xs font-semibold text-amber-600 whitespace-nowrap">Beklemede</span>
            @endif
            @php($kurumTelefonTuru = classify_phone_type($r['kurum']->phone))
            @if($kurumTelefonTuru !== 'mobile')
              <span class="text-xs font-semibold text-red-600 whitespace-nowrap">
                @if($kurumTelefonTuru === 'landline') ☎️ Sabit hat, WhatsApp gönderilemez @else 📵 Geçerli telefon yok @endif
              </span>
            @else
              <form method="POST" action="{{ route('admin.fee-info-requests.whatsapp', ['type' => $r['tur'], 'id' => $r['id']]) }}" class="inline">
                @csrf
                <button class="text-green-700 text-sm font-semibold whitespace-nowrap">WhatsApp'tan Gönder</button>
              </form>
            @endif
            @unless($r['sent_to_facility_at'])
              <form method="POST" action="{{ route('admin.fee-info-requests.mark-handled', ['type' => $r['tur'], 'id' => $r['id']]) }}" class="inline">
                @csrf
                <button class="text-blue-700 text-sm font-semibold whitespace-nowrap">İşlem Yapıldı</button>
              </form>
            @endunless
            <form method="POST" action="{{ route('admin.fee-info-requests.destroy', ['type' => $r['tur'], 'id' => $r['id']]) }}" class="inline" onsubmit="return confirm('Bu talep silinsin mi?');">
              @csrf @method('DELETE')
              <button class="text-red-600 text-sm font-semibold whitespace-nowrap">Sil</button>
            </form>
          @endif
        </div>
      </div>
    </div>
  @empty
    <div class="p-4 text-gray-400 text-sm">Kayıt yok.</div>
  @endforelse
</div>
<div class="mt-6">{{ $items->links() }}</div>
@endsection
