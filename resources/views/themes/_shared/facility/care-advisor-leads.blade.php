@extends('layouts.brand')
@section('content')
@include('themes._shared.partials.facility-panel-header', ['title' => 'Fırsat Havuzu'])

<div class="max-w-3xl mx-auto px-4 py-10">
  @if(!$isPremium)
    {{-- 13 Ekim 2026: kullanicinin talebi - "ucretli uyeligin en buyuk
         satici gucu olur" - ucretsiz kurumlar bu sayfayi GORUR (kapali
         kapi degil) ama icerigi goremez, boylece ozelligin varligi
         basli basina bir satis araci olur. --}}
    <div class="bg-white rounded-xl shadow-sm p-8 text-center">
      <div class="text-4xl mb-3">🔒</div>
      <h2 class="text-xl font-black text-gray-950 mb-2">Fırsat Havuzu Premium Üyelere Özel</h2>
      <p class="text-sm text-gray-600 max-w-md mx-auto mb-6">
        Pusula'yı dolduran aileler, iletişim bilgilerini bıraktıysa burada listelenir — bölgenizdeki ve
        kurum türünüzdeki gerçek ailelere doğrudan WhatsApp'tan ulaşabilirsiniz. Bu özellik yalnızca aktif üyeliği
        olan kurumlara açıktır.
      </p>
      <a href="{{ brand_route('facility.packages.index') }}" class="inline-block rounded-lg px-6 py-3 text-sm font-black text-white" style="background: var(--color-primary, #0b5d8c);">Üyelik Paketlerini İncele</a>
    </div>
  @else
    <p class="text-sm text-gray-500 mb-5">Bölgenizde ({{ $facility->city?->name }}) Pusula'yı dolduran, iletişim bilgisi bırakmış aileler — son 60 gün.</p>
    <div class="bg-white rounded-xl shadow-sm divide-y">
      @forelse($leads as $lead)
        <div class="p-5">
          <div class="flex items-center justify-between gap-3 flex-wrap mb-2">
            <div class="font-black text-gray-900">{{ $lead->name ?: 'İsim belirtilmemiş' }}</div>
            <span class="text-xs text-gray-400">{{ $lead->created_at->format('d.m.Y H:i') }}</span>
          </div>
          <div class="flex items-center gap-2 flex-wrap text-xs text-gray-600 mb-3">
            @if($lead->patient_age)<span class="bg-gray-100 px-2 py-0.5 rounded-full">{{ $lead->patient_age }} yaş</span>@endif
            @if($lead->gender)<span class="bg-gray-100 px-2 py-0.5 rounded-full">{{ $lead->gender }}</span>@endif
            @if($lead->condition)<span class="bg-gray-100 px-2 py-0.5 rounded-full">{{ $lead->condition }}</span>@endif
            @if($lead->budget_max)<span class="bg-gray-100 px-2 py-0.5 rounded-full">Bütçe: {{ number_format($lead->budget_max, 0, ',', '.') }} TL'ye kadar</span>@endif
            @if($lead->category)<span class="bg-gray-100 px-2 py-0.5 rounded-full">{{ $lead->category->name }}</span>@endif
          </div>
          @if($lead->sent_count > 0)
            <span class="inline-block text-[11px] bg-green-100 text-green-700 px-1.5 py-0.5 rounded-full font-semibold mb-2">✓ Daha önce ulaştınız ({{ $lead->sent_count }}x)</span>
          @endif
          <div>
            <a href="{{ brand_route('facility.care-advisor-leads.whatsapp', $lead) }}" target="_blank" rel="noopener" class="inline-block rounded-lg bg-green-600 text-white px-4 py-2 text-xs font-bold hover:bg-green-700">{{ $lead->sent_count > 0 ? "Tekrar Ulaş" : "WhatsApp'tan Ulaş" }}</a>
          </div>
        </div>
      @empty
        <div class="p-6 text-sm text-gray-400">Şu anda bölgenizde/kurum türünüzde bekleyen bir talep yok. Yeni talep geldiğinde burada görünecek.</div>
      @endforelse
    </div>
  @endif
</div>
@endsection
