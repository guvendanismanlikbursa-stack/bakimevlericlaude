@extends('admin.layout')
@section('title', 'Talep Mesajları · Şikayet İncelemesi')

@section('content')
<a href="{{ route('admin.offer-requests.index') }}" class="text-sm text-gray-500 hover:underline">&larr; Teklif Talepleri</a>
<h1 class="text-2xl font-bold mt-2 mb-1">Şikayet / Mesaj İncelemesi</h1>
<p class="text-sm text-gray-500 mb-6">Bu ekran sadece bir şikayet/anlaşmazlık incelemesi sırasında açılır. Normal teklif talepleri listesinde mesajlaşma gösterilmez.</p>

@if(session('success'))
  <div class="mb-4 rounded-lg bg-green-50 text-green-700 text-sm px-4 py-3">{{ session('success') }}</div>
@endif

<div class="grid md:grid-cols-3 gap-6">
  <div class="md:col-span-2 bg-white rounded-xl shadow-sm p-5">
    <h2 class="font-bold text-gray-800 mb-3">Form Detayı</h2>
    <dl class="grid grid-cols-2 gap-3 text-sm mb-6">
      <div><dt class="text-gray-400">Ad Soyad</dt><dd class="font-medium">{{ $offerRequest->full_name }}</dd></div>
      <div><dt class="text-gray-400">Telefon</dt><dd class="font-medium">{{ $offerRequest->phone }}</dd></div>
      <div><dt class="text-gray-400">E-posta</dt><dd class="font-medium">{{ $offerRequest->email ?: '-' }}</dd></div>
      <div><dt class="text-gray-400">Kurum</dt><dd class="font-medium">{{ $offerRequest->facility?->name ?? '- (yayın talebi)' }}</dd></div>
      @if($offerRequest->care_for)<div><dt class="text-gray-400">Kimin için</dt><dd class="font-medium">{{ $offerRequest->care_for }}</dd></div>@endif
      @if($offerRequest->patient_name)<div><dt class="text-gray-400">Hasta/çocuk</dt><dd class="font-medium">{{ $offerRequest->patient_name }}</dd></div>@endif
      @if($offerRequest->message)<div class="col-span-2"><dt class="text-gray-400">Mesaj</dt><dd class="font-medium">{{ $offerRequest->message }}</dd></div>@endif
    </dl>

    <h2 class="font-bold text-gray-800 mb-3">Mesaj Geçmişi ({{ $offerRequest->messages->count() }})</h2>
    <div class="space-y-3">
      @forelse($offerRequest->messages as $message)
        <div class="rounded-lg p-3 text-sm {{ $message->sender_type === 'facility' ? 'bg-blue-50' : 'bg-gray-50' }}">
          <div class="flex justify-between text-[11px] text-gray-400 mb-1">
            <span class="font-semibold {{ $message->sender_type === 'facility' ? 'text-blue-600' : 'text-gray-600' }}">
              {{ $message->sender_type === 'facility' ? 'Kurum' : 'Aile' }}
            </span>
            <span>{{ $message->created_at->format('d.m.Y H:i') }}</span>
          </div>
          <p>{{ $message->body }}</p>
        </div>
      @empty
        <p class="text-gray-300 text-sm">Henüz mesajlaşma başlamamış.</p>
      @endforelse
    </div>
  </div>

  <div class="bg-white rounded-xl shadow-sm p-5 h-fit">
    <h2 class="font-bold text-gray-800 mb-3">Hesap Durumu / Aksiyon</h2>

    <div class="mb-4 pb-4 border-b">
      <p class="text-xs text-gray-400 mb-1">Aile Hesabı</p>
      @if($offerRequest->familyUser)
        <p class="text-sm font-medium mb-2">{{ $offerRequest->familyUser->name }} — {{ $offerRequest->familyUser->email }}</p>
        <span class="inline-block mb-2 text-[11px] font-bold px-2 py-1 rounded {{ $offerRequest->familyUser->status === 'active' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
          {{ $offerRequest->familyUser->status === 'active' ? 'Aktif' : 'Askıya Alınmış' }}
        </span>
        <form method="POST" action="{{ route('admin.offer-requests.suspend-family', $offerRequest) }}" onsubmit="return confirm('Bu ailenin hesap durumunu değiştirmek istediğinize emin misiniz?');">
          @csrf
          <button type="submit" class="w-full text-xs font-semibold px-3 py-2 rounded-lg {{ $offerRequest->familyUser->status === 'active' ? 'bg-red-600 text-white hover:bg-red-700' : 'bg-green-600 text-white hover:bg-green-700' }}">
            {{ $offerRequest->familyUser->status === 'active' ? 'Aile Hesabını Askıya Al' : 'Aile Hesabını Aktifleştir' }}
          </button>
        </form>
      @else
        <p class="text-sm text-gray-300">Aile hesabı bulunamadı.</p>
      @endif
    </div>

    <div>
      <p class="text-xs text-gray-400 mb-1">Kurum Hesabı</p>
      @if($offerRequest->facility)
        <p class="text-sm font-medium mb-2">{{ $offerRequest->facility->name }}</p>
        @php($facilityActive = $offerRequest->facility->facilityUsers->firstWhere('status', 'active'))
        <span class="inline-block mb-2 text-[11px] font-bold px-2 py-1 rounded {{ $facilityActive ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
          {{ $facilityActive ? 'Aktif' : 'Askıya Alınmış' }}
        </span>
        <form method="POST" action="{{ route('admin.offer-requests.suspend-facility', $offerRequest) }}" onsubmit="return confirm('Bu kurumun yetkilisi hesabının durumunu değiştirmek istediğinize emin misiniz?');">
          @csrf
          <button type="submit" class="w-full text-xs font-semibold px-3 py-2 rounded-lg {{ $facilityActive ? 'bg-red-600 text-white hover:bg-red-700' : 'bg-green-600 text-white hover:bg-green-700' }}">
            {{ $facilityActive ? 'Kurum Hesabını Askıya Al' : 'Kurum Hesabını Aktifleştir' }}
          </button>
        </form>
      @else
        <p class="text-sm text-gray-300">Bu talep bir yayın talebi olduğu için tek bir kuruma bağlı değil.</p>
      @endif
    </div>
  </div>
</div>
@endsection
