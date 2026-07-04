@extends('layouts.brand')

@section('content')
<div class="max-w-3xl mx-auto px-4 py-10">
  <a href="{{ brand_route('facility.dashboard') }}" class="text-sm text-gray-500">← Panele dön</a>
  <h1 class="text-2xl font-bold mt-2 mb-6">Bakiyem</h1>

  <div class="grid md:grid-cols-2 gap-4 mb-8">
    <div class="bg-white rounded-xl shadow-sm p-4">
      <div class="text-gray-500 text-sm">Ücretsiz Hak</div>
      <div class="text-2xl font-bold">{{ $facility->free_quote_credits }}</div>
    </div>
    <div class="bg-white rounded-xl shadow-sm p-4">
      <div class="text-gray-500 text-sm">Bakiye</div>
      <div class="text-2xl font-bold">{{ number_format($facility->balance,2,',','.') }}₺</div>
      <div class="text-xs text-gray-400 mt-1">Teklif başına ücret: {{ number_format($quotePrice,0,',','.') }}₺ (ücretsiz hak bittiğinde)</div>
    </div>
  </div>

  <div class="bg-white rounded-xl shadow-sm p-6 mb-8">
    <h2 class="font-bold mb-3">Banka Hesap Bilgileri</h2>
    <p class="text-sm text-gray-600">{{ $bankInfo['bank_name'] }} — {{ $bankInfo['account_holder'] }}</p>
    <p class="text-sm font-mono mt-1">{{ $bankInfo['iban'] }}</p>
    <p class="text-xs text-gray-400 mt-2">Bu hesaba havale/EFT yaptıktan sonra dekont görselini aşağıdan yükleyin, admin onayı sonrası bakiyenize işlenir.</p>
  </div>

  <form method="POST" action="{{ brand_route('facility.wallet.store') }}" enctype="multipart/form-data" class="bg-white rounded-xl shadow-sm p-6 space-y-3 mb-8">
    @csrf
    <input type="number" step="0.01" name="amount" placeholder="Yatırdığınız Tutar (₺)" required class="border rounded-lg px-3 py-2 w-full">
    <input type="file" name="receipt" accept="image/*" required class="border rounded-lg px-3 py-2 w-full">
    <input type="text" name="note" placeholder="Not (opsiyonel)" class="border rounded-lg px-3 py-2 w-full">
    <button class="btn-primary px-6 py-2 rounded-lg font-semibold">Dekontu Gönder</button>
  </form>

  <h2 class="font-bold mb-3">Yükleme Geçmişi</h2>
  <div class="bg-white rounded-xl shadow-sm overflow-hidden mb-8">
    <table class="w-full text-sm">
      <thead class="bg-gray-50 text-left text-gray-500"><tr><th class="p-3">Tutar</th><th class="p-3">Durum</th><th class="p-3">Tarih</th></tr></thead>
      <tbody class="divide-y">
        @forelse($topups as $t)
          <tr><td class="p-3">{{ number_format($t->amount,2,',','.') }}₺</td><td class="p-3">{{ $t->status }}</td><td class="p-3 text-gray-400">{{ $t->created_at->format('d.m.Y H:i') }}</td></tr>
        @empty
          <tr><td class="p-3 text-gray-400" colspan="3">Henüz yükleme talebiniz yok.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <h2 class="font-bold mb-3">Hareket Geçmişi</h2>
  <div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <table class="w-full text-sm">
      <thead class="bg-gray-50 text-left text-gray-500"><tr><th class="p-3">Tip</th><th class="p-3">Tutar</th><th class="p-3">Hak</th><th class="p-3">Not</th><th class="p-3">Tarih</th></tr></thead>
      <tbody class="divide-y">
        @forelse($logs as $log)
          <tr>
            <td class="p-3">{{ $log->type }}</td>
            <td class="p-3">{{ $log->amount != 0 ? number_format($log->amount,2,',','.').'₺' : '-' }}</td>
            <td class="p-3">{{ $log->credits_amount != 0 ? $log->credits_amount : '-' }}</td>
            <td class="p-3 text-gray-400">{{ $log->note }}</td>
            <td class="p-3 text-gray-400">{{ $log->created_at->format('d.m.Y H:i') }}</td>
          </tr>
        @empty
          <tr><td class="p-3 text-gray-400" colspan="5">Hareket yok.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>
@endsection
