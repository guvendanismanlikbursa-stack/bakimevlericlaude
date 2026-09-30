@extends('admin.layout')
@section('title', 'Ücret ve Bilgi Talepleri')

@section('content')
<h1 class="text-2xl font-bold mb-2">Ücret ve Bilgi Talepleri</h1>
<p class="text-sm text-gray-500 mb-4">Ailelerin kurumlardan gönderdiği tüm ücret/bilgi taleplerinin tek listesi. Sahipli kurumların talepleri zaten doğrudan kurumun kendi paneline düşer — burada sadece görüntülenir, işlem yapılamaz.</p>

<div class="flex gap-2 mb-4 flex-wrap">
  @foreach(['tumu' => 'Tümü', 'anlasmali' => 'Anlaşmalı', 'on_kayitli' => 'Ön Kayıtlı', 'sahipli' => 'Sahipli (salt görüntüleme)'] as $k => $label)
    <a href="{{ route('admin.fee-info-requests.index', ['scope' => $k]) }}"
       class="px-4 py-2 rounded-lg text-sm font-semibold border {{ $scope === $k ? 'bg-gray-900 text-white border-gray-900' : 'bg-white text-gray-700 border-gray-200 hover:border-gray-400' }}">
      {{ $label }} <span class="opacity-70">({{ $sayilar[$k] }})</span>
    </a>
  @endforeach
</div>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
  <table class="w-full text-sm">
    <thead class="bg-gray-50 text-left text-gray-500">
      <tr>
        <th class="p-3">Ad Soyad / Telefon</th>
        <th class="p-3">Kurum</th>
        <th class="p-3">Mesaj</th>
        <th class="p-3">Tarih</th>
        <th class="p-3">Durum</th>
        <th class="p-3"></th>
      </tr>
    </thead>
    <tbody class="divide-y">
      @forelse($items as $r)
        <tr>
          <td class="p-3">
            <div class="font-medium">{{ $r['full_name'] }}</div>
            <div class="text-gray-500">{{ $r['phone'] }}</div>
            @if($r['email'])<div class="text-gray-400 text-xs">{{ $r['email'] }}</div>@endif
          </td>
          <td class="p-3">
            <div class="font-medium">{{ $r['kurum']->name }}</div>
            <div class="text-[11px] text-gray-400">{{ $r['kurum']->city?->name }}</div>
            @php
              $rozet = ['anlasmali' => ['🤝 Anlaşmalı', 'text-amber-700'], 'on_kayitli' => ['Ön Kayıtlı', 'text-gray-400'], 'sahipli' => ['✓ Sahipli', 'text-green-700']][$r['kurum_durumu']];
            @endphp
            <div class="text-[11px] font-semibold {{ $rozet[1] }} mt-0.5">{{ $rozet[0] }}</div>
            <div class="text-[11px] text-gray-400 mt-0.5">{{ $r['tur'] === 'teklif' ? 'Teklif Talebi' : 'Kurum İlgi Talebi' }}</div>
          </td>
          <td class="p-3 text-xs text-gray-600 max-w-[260px]">
            @if($r['message'])
              <div class="line-clamp-3" title="{{ $r['message'] }}">{{ $r['message'] }}</div>
            @else
              <span class="text-gray-300">—</span>
            @endif
          </td>
          <td class="p-3 text-gray-400 whitespace-nowrap">
            {{ $r['created_at']->format('d.m.Y H:i') }}
            @if($r['tur'] === 'teklif')
              <a href="{{ route('admin.offer-requests.messages', $r['id']) }}" class="block mt-1 text-[11px] font-semibold text-red-600 hover:underline">Şikayet / Mesajları İncele</a>
            @endif
          </td>
          <td class="p-3">
            @if($r['kurum_durumu'] === 'sahipli')
              <span class="text-[11px] text-gray-400">Kurumun kendi paneline gitti</span>
            @elseif($r['sent_to_facility_at'])
              <span class="text-[11px] font-semibold text-green-700">✓ Kuruma yollandı<br>{{ $r['sent_to_facility_at']->format('d.m.Y H:i') }}</span>
            @else
              <span class="text-[11px] font-semibold text-amber-600">Beklemede</span>
            @endif
          </td>
          <td class="p-3 text-right whitespace-nowrap">
            @if($r['kurum_durumu'] !== 'sahipli')
              @php($kurumTelefonTuru = classify_phone_type($r['kurum']->phone))
              @if($kurumTelefonTuru !== 'mobile')
                <div class="text-[11px] font-semibold text-red-600 mb-1">
                  @if($kurumTelefonTuru === 'landline')
                    ☎️ Kurumun numarası sabit hat, WhatsApp gönderilemez
                  @else
                    📵 Kurumun geçerli bir telefonu yok, WhatsApp gönderilemez
                  @endif
                </div>
              @else
                <form method="POST" action="{{ route('admin.fee-info-requests.whatsapp', ['type' => $r['tur'], 'id' => $r['id']]) }}" class="inline">
                  @csrf
                  <button class="text-green-700 font-semibold">WhatsApp'tan Gönder</button>
                </form>
              @endif
              <form method="POST" action="{{ route('admin.fee-info-requests.destroy', ['type' => $r['tur'], 'id' => $r['id']]) }}" class="inline ml-2" onsubmit="return confirm('Bu talep silinsin mi?');">
                @csrf @method('DELETE')
                <button class="text-red-600">Sil</button>
              </form>
            @endif
          </td>
        </tr>
      @empty
        <tr><td class="p-3 text-gray-400" colspan="6">Kayıt yok.</td></tr>
      @endforelse
    </tbody>
  </table>
</div>
<div class="mt-6">{{ $items->links() }}</div>
@endsection
