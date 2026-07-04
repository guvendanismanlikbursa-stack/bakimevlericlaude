@extends('layouts.brand')

@section('content')
@php
  $statusLabels = [
    'new' => 'Yeni talep',
    'contacted' => 'İletişim başladı',
    'closed' => 'Kapandı',
  ];
@endphp

<div class="max-w-6xl mx-auto px-4 py-10">
  <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between mb-6">
    <div>
      <p class="text-sm text-gray-500">Aile Paneli</p>
      <h1 class="text-2xl font-bold">Merhaba, {{ $family->name }}</h1>
      <p class="text-sm text-gray-500 mt-1">{{ $family->email }} @if($family->phone) · {{ $family->phone }} @endif</p>
    </div>
    <div class="flex items-center gap-3">
      <a href="{{ brand_route('facilities.index') }}" class="bg-primary text-white px-4 py-2 rounded-lg text-sm font-semibold">Yeni Talep Oluştur</a>
      <form method="POST" action="{{ brand_route('family.logout') }}">@csrf<button class="text-sm text-red-600">Çıkış Yap</button></form>
    </div>
  </div>

  @unless($family->hasVerifiedEmail())
    <div class="mb-6 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
      <span>E-posta adresinizi henüz doğrulamadınız. Gelen kutunuzu kontrol edin.</span>
      <form method="POST" action="{{ brand_route('family.verify-email.resend') }}">
        @csrf
        <button class="font-semibold underline whitespace-nowrap">Doğrulama e-postasını tekrar gönder</button>
      </form>
    </div>
  @endunless

  <div class="grid sm:grid-cols-2 lg:grid-cols-5 gap-3 mb-8">
    <div class="bg-white rounded-lg shadow-sm p-4 border border-gray-100">
      <div class="text-xs text-gray-500">Toplam Talep</div>
      <div class="text-2xl font-bold mt-1">{{ $stats['total_requests'] }}</div>
    </div>
  <div class="grid md:grid-cols-3 gap-4 mb-8">
    <a href="{{ brand_route('engagement.wizard') }}" class="bg-white rounded-lg shadow-sm border border-gray-100 p-5 hover:shadow-md">
      <div class="text-xs font-semibold text-primary">Karar Merkezi</div>
      <div class="font-black text-gray-950 mt-1">İhtiyaç sihirbazı</div>
      <p class="text-sm text-gray-500 mt-2">Bakım, eğitim veya rehabilitasyon ihtiyacına göre uygun arama yolunu seçin.</p>
    </a>
    <a href="{{ brand_route('engagement.compare') }}" class="bg-white rounded-lg shadow-sm border border-gray-100 p-5 hover:shadow-md">
      <div class="text-xs font-semibold text-primary">Karşılaştırma<span class="sr-only">Karsilastirma</span></div>
      <div class="font-black text-gray-950 mt-1">Kurumları yan yana gör</div>
      <p class="text-sm text-gray-500 mt-2">Favoriye veya karşılaştırmaya eklediğiniz kurumları tek ekranda inceleyin.</p>
    </a>
    <a href="{{ brand_route('engagement.favorites') }}" class="bg-white rounded-lg shadow-sm border border-gray-100 p-5 hover:shadow-md">
      <div class="text-xs font-semibold text-primary">Notlar</div>
      <div class="font-black text-gray-950 mt-1">Favori listeniz</div>
      <p class="text-sm text-gray-500 mt-2">Kısa liste oluşturun, görüşme notlarını aile panelinden takip edin.</p>
    </a>
  </div>
    <div class="bg-white rounded-lg shadow-sm p-4 border border-gray-100">
      <div class="text-xs text-gray-500">Açık Talep</div>
      <div class="text-2xl font-bold mt-1">{{ $stats['open_requests'] }}</div>
    </div>
    <div class="bg-white rounded-lg shadow-sm p-4 border border-gray-100">
      <div class="text-xs text-gray-500">Gelen Teklif</div>
      <div class="text-2xl font-bold mt-1">{{ $stats['total_quotes'] }}</div>
    </div>
    <div class="bg-white rounded-lg shadow-sm p-4 border border-gray-100">
      <div class="text-xs text-gray-500">Kabul Edilen</div>
      <div class="text-2xl font-bold mt-1">{{ $stats['accepted_quotes'] }}</div>
    </div>
    <div class="bg-white rounded-lg shadow-sm p-4 border border-gray-100">
      <div class="text-xs text-gray-500">Mesaj</div>
      <div class="text-2xl font-bold mt-1">{{ $stats['message_count'] }}</div>
    </div>
  </div>

  @if($requests->isEmpty())
    <div class="bg-white rounded-lg shadow-sm border border-dashed border-gray-300 p-8 text-center">
      <h2 class="font-bold text-lg">Henüz teklif talebiniz yok</h2>
      <p class="text-gray-500 text-sm mt-2">Kurum sayfalarından ücret bilgisi isteyerek uygun kurumlardan teklif alabilirsiniz.</p>
      <a href="{{ brand_route('facilities.index') }}" class="inline-block mt-4 bg-primary text-white px-5 py-2 rounded-lg text-sm font-semibold">Kurumları İncele</a>
    </div>
  @endif

  <div class="space-y-5">
    @foreach($requests as $req)
      @php
        $acceptedQuote = $req->acceptedQuote;
        $quoteCount = $req->quotes->count();
        $messageCount = $req->messages->count();
      @endphp
      <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-5">
        <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
          <div>
            <div class="flex flex-wrap items-center gap-2">
              <h2 class="font-bold text-lg">
                @if($req->facility)
                  {{ $req->facility->name }}
                @else
                  {{ $req->category->name ?? 'Genel Talep' }} - {{ $req->city->name ?? 'Şehir seçilmedi' }}
                @endif
              </h2>
              <span class="text-xs rounded-full px-2 py-1 bg-gray-100 text-gray-700">{{ $statusLabels[$req->status] ?? $req->status }}</span>
              @if($acceptedQuote)
                <span class="text-xs rounded-full px-2 py-1 bg-green-100 text-green-700">Teklif kabul edildi</span>
              @endif
            </div>
            <p class="text-xs text-gray-400 mt-1">{{ $req->created_at->format('d.m.Y H:i') }} &middot; {{ $req->isBroadcast() ? 'Şehir/kategori talebi' : 'Doğrudan kurum talebi' }}</p>
            <div class="grid sm:grid-cols-3 gap-3 text-sm text-gray-600 mt-4">
              <div><span class="block text-xs text-gray-400">Hasta / Yakın</span>{{ $req->patient_name ?: $req->full_name }}</div>
              <div><span class="block text-xs text-gray-400">İletişim</span>{{ $req->phone ?: '-' }}</div>
              <div><span class="block text-xs text-gray-400">Teklif / Mesaj</span>{{ $quoteCount }} teklif · {{ $messageCount }} mesaj</div>
            </div>
            @if($req->message)
              <p class="text-sm text-gray-600 mt-4 bg-gray-50 rounded-lg p-3">{{ $req->message }}</p>
            @endif
          </div>
          <div class="flex items-center gap-3">
            @if($req->facility || $req->accepted_quote_id)
              <a href="{{ brand_route('family.thread', $req) }}" class="text-sm text-primary font-semibold">Mesajlar →</a>
            @endif
          </div>
        </div>

        @if($acceptedQuote)
          <div class="mt-4 rounded-lg bg-green-50 border border-green-100 p-3 text-sm text-green-800">
            Kabul edilen teklif: <strong>{{ $acceptedQuote->facility->name ?? 'Kurum' }}</strong> · {{ number_format($acceptedQuote->price,0,',','.') }}₺ {{ $acceptedQuote->price_period === 'monthly' ? '/ ay' : '(tek seferlik)' }}
          </div>
        @endif

        @if($req->quotes->isNotEmpty())
          <div class="mt-4 divide-y border rounded-lg overflow-hidden">
            @foreach($req->quotes as $quote)
              <div class="p-3 flex flex-col gap-3 md:flex-row md:items-center md:justify-between text-sm {{ $quote->status === 'accepted' ? 'bg-green-50' : '' }}">
                <div>
                  <div class="font-semibold">{{ $quote->facility->name }}</div>
                  <div class="text-gray-500">{{ number_format($quote->price,0,',','.') }}₺ {{ $quote->price_period === 'monthly' ? '/ ay' : '(tek seferlik)' }}</div>
                  @if($quote->message)<div class="text-gray-400 text-xs mt-1">{{ $quote->message }}</div>@endif
                </div>
                @if($req->accepted_quote_id === $quote->id)
                  <span class="text-green-700 font-semibold text-xs">Kabul Edildi</span>
                @elseif(is_null($req->accepted_quote_id))
                  <form method="POST" action="{{ brand_route('family.quotes.accept', $quote) }}">
                    @csrf
                    <button class="text-xs bg-gray-900 text-white px-3 py-1.5 rounded-lg">Kabul Et</button>
                  </form>
                @else
                  <span class="text-gray-400 text-xs">Pasif</span>
                @endif
              </div>
            @endforeach
          </div>
        @else
          <p class="text-sm text-gray-400 mt-4">Henüz teklif gelmedi. Uygun kurumlar talebi incelediğinde burada görünecek.</p>
        @endif
      </div>
    @endforeach
  </div>
</div>
@endsection
