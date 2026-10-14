@extends('admin.layout')
@section('title', $family->name)

@section('content')
<a href="{{ route('admin.site-stats.index') }}" class="text-sm text-primary font-semibold mb-4 inline-block">&larr; Site İstatistiklerine Dön</a>
<h1 class="text-2xl font-bold mb-6">{{ $family->name }}</h1>

<div class="grid md:grid-cols-2 gap-6 mb-8">
  <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-100">
    <div class="font-black text-gray-950 mb-3">Profil Bilgileri</div>
    <dl class="text-sm space-y-2">
      <div class="flex justify-between"><dt class="text-gray-500">E-posta</dt><dd class="font-semibold">{{ $family->email }}</dd></div>
      <div class="flex justify-between"><dt class="text-gray-500">Telefon</dt><dd class="font-semibold">{{ $family->phone ?? '—' }}</dd></div>
      <div class="flex justify-between"><dt class="text-gray-500">Kayıt Markası</dt><dd class="font-semibold">{{ config("brands.brands.{$family->registered_brand}.name", $family->registered_brand) }}</dd></div>
      <div class="flex justify-between"><dt class="text-gray-500">Durum</dt><dd class="font-semibold">{{ $family->status ?? 'active' }}</dd></div>
      <div class="flex justify-between"><dt class="text-gray-500">E-posta Doğrulama</dt><dd class="font-semibold">{{ $family->hasVerifiedEmail() ? 'Doğrulandı' : 'Bekliyor' }}</dd></div>
      <div class="flex justify-between"><dt class="text-gray-500">Kayıt Tarihi</dt><dd class="font-semibold">{{ $family->created_at->format('d.m.Y H:i') }}</dd></div>
    </dl>
  </div>

  <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-100">
    <div class="font-black text-gray-950 mb-3">Rıza &amp; Konum Bilgisi</div>
    <dl class="text-sm space-y-2">
      <div class="flex justify-between"><dt class="text-gray-500">Rıza Tarihi</dt><dd class="font-semibold">{{ $family->consent_accepted_at?->format('d.m.Y H:i') ?? '—' }}</dd></div>
      <div class="flex justify-between"><dt class="text-gray-500">Kayıt IP</dt><dd class="font-semibold font-mono">{{ $family->consent_ip ?? '—' }}</dd></div>
      <div class="flex justify-between"><dt class="text-gray-500">Yaklaşık Şehir</dt><dd class="font-semibold">{{ $family->signup_city_name ?? '—' }}</dd></div>
      <div class="flex justify-between items-center"><dt class="text-gray-500">GPS Konumu</dt>
        <dd class="font-semibold">
          @if($family->signup_lat && $family->signup_lng)
            <a href="https://www.google.com/maps?q={{ $family->signup_lat }},{{ $family->signup_lng }}" target="_blank" class="text-primary">Haritada gör →</a>
          @else
            Paylaşılmadı
          @endif
        </dd>
      </div>
    </dl>
  </div>
</div>

{{-- 11 Ekim 2026: kullanicinin talebi - genis <table> yerine flex-wrap
     kart (bkz. admin/facilities/_results.blade.php ayni desen). --}}
<div class="bg-white rounded-xl shadow-sm">
  <div class="p-4 border-b border-gray-100 font-black text-gray-950">Ücret/Teklif Talep Geçmişi ({{ $family->offerRequests->count() }})</div>
  <div class="divide-y divide-gray-100">
    @forelse($family->offerRequests as $offerRequest)
      <div class="p-4 flex items-center justify-between gap-3 flex-wrap">
        <div class="font-semibold text-gray-900">{{ $offerRequest->facility->name ?? 'Yayın talebi (tüm uygun kurumlar)' }}</div>
        <div class="text-xs text-gray-500">{{ config("brands.brands.{$offerRequest->brand}.name", $offerRequest->brand) }} · {{ $offerRequest->status }} · {{ $offerRequest->created_at->format('d.m.Y H:i') }}</div>
      </div>
    @empty
      <div class="p-8 text-center text-gray-500">Henüz talep yok.</div>
    @endforelse
  </div>
</div>
@endsection
