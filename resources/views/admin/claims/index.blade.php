@extends('admin.layout')
@section('title', 'Sahiplenme Başvuruları')

@section('content')
<h1 class="text-2xl font-bold mb-6">Sahiplenme Başvuruları</h1>

<form method="GET" class="mb-4">
  <select name="status" onchange="this.form.submit()" class="border rounded-lg px-3 py-2 text-sm">
    <option value="pending" @selected(request('status','pending')==='pending')>Bekleyenler</option>
    <option value="approved" @selected(request('status')==='approved')>Onaylananlar</option>
    <option value="rejected" @selected(request('status')==='rejected')>Reddedilenler</option>
  </select>
</form>

{{-- 11 Ekim 2026: kullanicinin talebi - genis <table> yerine flex-wrap
     kart (bkz. admin/facilities/_results.blade.php ayni desen). --}}
<div class="bg-white rounded-xl shadow-sm divide-y divide-gray-100">
  @forelse($claims as $claim)
    <div class="p-4 flex flex-col gap-2">
      <div class="flex items-start justify-between gap-3 flex-wrap">
        <div class="min-w-0">
          <div class="font-bold text-gray-900 break-words">{{ $claim->facility->name }}</div>
          <div class="text-xs text-gray-500 mt-0.5">{{ $claim->applicant_name }} · {{ $claim->applicant_email }} · {{ $claim->created_at->format('d.m.Y H:i') }}</div>
        </div>
        <a href="{{ route('admin.claims.show', $claim) }}" class="text-blue-600 text-sm font-semibold whitespace-nowrap">İncele</a>
      </div>
      <div class="flex items-center gap-3 flex-wrap text-xs">
        @if($claim->document_path)
          <span class="text-green-700 font-semibold">✓ Evrak Var</span>
        @else
          <span class="text-amber-700 font-semibold" title="24 saat içinde eklenmezse otomatik silinir">⏳ Evrak Bekliyor</span>
        @endif
        @if($claim->distance_km !== null)
          <span class="{{ $claim->distance_km > 50 ? 'text-amber-700 font-semibold' : 'text-gray-600' }}">{{ number_format($claim->distance_km, 1) }} km</span>
        @endif
      </div>
    </div>
  @empty
    <div class="p-4 text-gray-400 text-sm">Kayıt yok.</div>
  @endforelse
</div>
<div class="mt-6">{{ $claims->links() }}</div>
@endsection
