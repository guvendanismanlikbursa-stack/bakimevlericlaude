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

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
  <div class="p-4 border-b border-gray-100 font-black text-gray-950">Ücret/Teklif Talep Geçmişi ({{ $family->offerRequests->count() }})</div>
  <table class="w-full text-sm">
    <thead class="bg-gray-50 text-left text-gray-500"><tr><th class="p-3">Kurum</th><th class="p-3">Marka</th><th class="p-3">Durum</th><th class="p-3">Tarih</th></tr></thead>
    <tbody class="divide-y">
      @forelse($family->offerRequests as $offerRequest)
        <tr>
          <td class="p-3">{{ $offerRequest->facility->name ?? 'Yayın talebi (tüm uygun kurumlar)' }}</td>
          <td class="p-3 text-gray-500">{{ config("brands.brands.{$offerRequest->brand}.name", $offerRequest->brand) }}</td>
          <td class="p-3">{{ $offerRequest->status }}</td>
          <td class="p-3 text-gray-400">{{ $offerRequest->created_at->format('d.m.Y H:i') }}</td>
        </tr>
      @empty
        <tr><td colspan="4" class="p-8 text-center text-gray-500">Henüz talep yok.</td></tr>
      @endforelse
    </tbody>
  </table>
</div>
@endsection
