@extends('admin.layout')
@section('title', 'Pazarlamacılar')

@section('content')
<div class="flex items-center justify-between mb-2">
  <h1 class="text-2xl font-bold">Pazarlamacılar</h1>
  <div class="flex gap-4">
    <a href="{{ route('admin.marketers.funnel') }}" class="text-sm font-semibold text-gray-700 underline">Satış hunisi →</a>
    <a href="{{ route('admin.marketers.conversations') }}" class="text-sm font-semibold text-gray-700 underline">Tüm görüşme kayıtlarını gör →</a>
  </div>
</div>
<p class="text-sm text-gray-500 mb-6">CRM paneline (telefon + şifre ile) sadece buradan oluşturduğunuz hesaplar girebilir. Şifre sadece oluşturulduğu/sıfırlandığı an gösterilir, sonra tekrar görüntülenemez.</p>

@if(session('success'))
  <div class="bg-green-100 text-green-800 px-4 py-3 rounded-lg text-sm mb-4">{{ session('success') }}</div>
@endif

<form method="POST" action="{{ route('admin.marketers.store') }}" class="bg-white rounded-xl shadow-sm p-4 mb-6 flex flex-wrap items-end gap-3">
  @csrf
  <div>
    <label class="block text-xs font-semibold text-gray-500 mb-1">Ad Soyad</label>
    <input type="text" name="name" value="{{ old('name') }}" required class="border rounded-lg px-3 py-2 text-sm">
    @error('name')<div class="text-red-600 text-xs mt-1">{{ $message }}</div>@enderror
  </div>
  <div>
    <label class="block text-xs font-semibold text-gray-500 mb-1">Telefon</label>
    <input type="text" name="phone" value="{{ old('phone') }}" required placeholder="05xx xxx xx xx" class="border rounded-lg px-3 py-2 text-sm">
    @error('phone')<div class="text-red-600 text-xs mt-1">{{ $message }}</div>@enderror
  </div>
  <button class="btn-primary px-4 py-2 rounded-lg text-sm font-semibold">Pazarlamacı Ekle</button>
</form>

{{-- 11 Ekim 2026: kullanicinin talebi - genis <table> yerine flex-wrap
     kart (bkz. admin/facilities/_results.blade.php ayni desen). --}}
<div class="bg-white rounded-xl shadow-sm divide-y divide-gray-100">
  @forelse($marketers as $marketer)
    <div class="p-4 flex items-start justify-between gap-3 flex-wrap">
      <div class="min-w-0">
        <div class="font-bold text-gray-900">{{ $marketer->name }}</div>
        <div class="text-xs text-gray-500 mt-0.5">📞 {{ $marketer->phone }} · {{ $marketer->notes_count }} not · son giriş: {{ $marketer->last_login_at?->format('d.m.Y H:i') ?? '—' }}</div>
        <div class="flex items-center gap-2 mt-1 text-xs">
          @if($marketer->is_active)<span class="text-green-700 font-semibold">Aktif</span>@else<span class="text-red-700 font-semibold">Devre Dışı</span>@endif
          @if($marketer->is_logged_in)<span class="text-green-700 font-semibold">● Oturum Aktif</span>@else<span class="text-gray-400 font-semibold">● Oturum Pasif</span>@endif
        </div>
      </div>
      <div class="flex items-center gap-2 flex-wrap justify-end">
        <form method="POST" action="{{ route('admin.marketers.reset-password', $marketer) }}" class="inline" onsubmit="return confirm('Bu pazarlamacı için yeni bir şifre üretilsin mi? Eski şifre geçersiz olur.');">
          @csrf
          <button class="text-blue-600 text-sm font-semibold whitespace-nowrap">Şifre Sıfırla</button>
        </form>
        <form method="POST" action="{{ route('admin.marketers.toggle-active', $marketer) }}" class="inline">
          @csrf
          <button class="text-sm font-semibold whitespace-nowrap {{ $marketer->is_active ? 'text-red-600' : 'text-green-700' }}">{{ $marketer->is_active ? 'Devre Dışı Bırak' : 'Aktif Et' }}</button>
        </form>
      </div>
    </div>
  @empty
    <div class="p-4 text-gray-400 text-sm">Henüz pazarlamacı eklenmedi.</div>
  @endforelse
</div>
@endsection
