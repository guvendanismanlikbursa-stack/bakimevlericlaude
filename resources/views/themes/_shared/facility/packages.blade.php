@extends('layouts.brand')

@section('content')
<div class="max-w-4xl mx-auto px-4 py-10">
  <a href="{{ brand_route('facility.dashboard') }}" class="text-sm text-gray-500">← Panele dön</a>
  <h1 class="text-2xl font-bold mt-2 mb-2">Paketler</h1>
  <p class="text-sm text-gray-500 mb-8">Bir paket seçip dekont yükleyin; admin onayından sonra tutar bakiyenize, bonus hak varsa ücretsiz hak sayınıza eklenir.</p>

  <div class="grid md:grid-cols-3 gap-4 mb-10">
    @forelse($packages as $package)
      <div class="bg-white rounded-xl shadow-sm p-5 flex flex-col">
        <div class="font-bold text-lg">{{ $package->name }}</div>
        <div class="text-2xl font-black text-primary my-2">{{ number_format($package->price,2,',','.') }}₺</div>
        @if($package->description)<p class="text-sm text-gray-500 mb-3">{{ $package->description }}</p>@endif
        <ul class="text-sm text-gray-600 space-y-1 mb-4">
          @if($package->bonus_quote_credits > 0)<li>+{{ $package->bonus_quote_credits }} ücretsiz teklif hakkı</li>@endif
          @if($package->duration_days)<li>{{ $package->duration_days }} gün geçerli</li>@endif
        </ul>
        <form method="POST" action="{{ brand_route('facility.packages.store', $package) }}" enctype="multipart/form-data" class="mt-auto space-y-2">
          @csrf
          <input type="file" name="receipt" accept="image/*" required class="border rounded-lg px-3 py-2 w-full text-sm">
          <button class="btn-primary w-full py-2 rounded-lg font-semibold text-sm">Dekont Yükle, Talep Et</button>
        </form>
      </div>
    @empty
      <p class="text-sm text-gray-400 md:col-span-3">Şu anda tanımlı paket yok.</p>
    @endforelse
  </div>

  <h2 class="font-bold mb-3">Paket Taleplerim</h2>
  <div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <table class="w-full text-sm">
      <thead class="bg-gray-50 text-left text-gray-500"><tr><th class="p-3">Paket</th><th class="p-3">Tutar</th><th class="p-3">Durum</th><th class="p-3">Tarih</th></tr></thead>
      <tbody class="divide-y">
        @forelse($myTopups as $t)
          <tr>
            <td class="p-3">{{ $t->subscriptionPackage?->name ?? '—' }}</td>
            <td class="p-3">{{ number_format($t->amount,2,',','.') }}₺</td>
            <td class="p-3">{{ $t->status }}</td>
            <td class="p-3 text-gray-400">{{ $t->created_at->format('d.m.Y H:i') }}</td>
          </tr>
        @empty
          <tr><td class="p-3 text-gray-400" colspan="4">Henüz paket talebiniz yok.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>
@endsection
