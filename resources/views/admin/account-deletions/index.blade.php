@extends('admin.layout')
@section('title', 'Hesap Silme Talepleri')

@section('content')
<h1 class="text-2xl font-bold mb-2">Hesap Silme Talepleri</h1>
<p class="text-sm text-gray-500 mb-6">Aile/kurum yetkilisi kendi panelinden hesabının silinmesini talep etti. Onaylarsanız kişisel verileri (ad/e-posta/telefon) anonimleştirilir, hesap giriş yapamaz hale gelir - geçmiş talep/mesaj gibi başka tarafları ilgilendiren kayıtlar silinmez.</p>

<form method="GET" class="mb-4">
  <select name="status" onchange="this.form.submit()" class="border rounded-lg px-3 py-2 text-sm">
    <option value="pending" @selected(request('status','pending')==='pending')>Bekleyenler</option>
    <option value="completed" @selected(request('status')==='completed')>Silinenler</option>
    <option value="rejected" @selected(request('status')==='rejected')>Reddedilenler</option>
  </select>
</form>

{{-- 11 Ekim 2026: kullanicinin talebi - genis <table> (daha once yatay
     kaydirmali) yerine flex-wrap kart (bkz. admin/facilities/_results.blade.php
     ayni desen). --}}
<div class="bg-white rounded-xl shadow-sm divide-y divide-gray-100">
  @forelse($requests as $req)
    @php($user = $req->requestable)
    <div class="p-4 flex items-start justify-between gap-3 flex-wrap">
      <div class="min-w-0">
        <div class="font-bold text-gray-900">{{ $user->name ?? '(hesap bulunamadı)' }}</div>
        <div class="text-xs text-gray-500 mt-0.5">{{ $req->requestable_type === \App\Models\FamilyUser::class ? 'Aile' : 'Kurum Yetkilisi' }} · {{ $user->email ?? '—' }} · {{ $req->requested_at->format('d.m.Y H:i') }}</div>
        <div class="mt-1">
          @if($req->status === 'pending')
            <span class="bg-amber-100 text-amber-700 text-xs font-semibold px-2 py-0.5 rounded-full">Bekliyor</span>
          @elseif($req->status === 'completed')
            <span class="bg-green-100 text-green-700 text-xs font-semibold px-2 py-0.5 rounded-full">Silindi</span>
          @else
            <span class="bg-gray-100 text-gray-500 text-xs font-semibold px-2 py-0.5 rounded-full">Reddedildi</span>
          @endif
        </div>
      </div>
      @if($req->status === 'pending')
        <div class="flex items-center gap-2 flex-wrap justify-end">
          <form method="POST" action="{{ route('admin.account-deletions.approve', $req) }}" class="inline" onsubmit="return confirm('Bu hesabı kalıcı olarak silmek (kişisel verileri anonimleştirmek) istediğinize emin misiniz? Geri alınamaz.');">
            @csrf
            <button class="text-red-600 text-sm font-semibold whitespace-nowrap">Sil</button>
          </form>
          <form method="POST" action="{{ route('admin.account-deletions.reject', $req) }}" class="inline" onsubmit="return confirm('Talep reddedilsin mi?');">
            @csrf
            <button class="text-gray-500 text-sm font-semibold whitespace-nowrap">Reddet</button>
          </form>
        </div>
      @else
        <span class="text-xs text-gray-400 whitespace-nowrap">{{ $req->processedBy?->name ?? '—' }} &middot; {{ $req->processed_at?->format('d.m.Y H:i') }}</span>
      @endif
    </div>
  @empty
    <div class="p-4 text-gray-400 text-sm">Kayıt yok.</div>
  @endforelse
</div>
<div class="mt-6">{{ $requests->links() }}</div>
@endsection
