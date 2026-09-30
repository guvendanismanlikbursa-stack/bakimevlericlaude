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

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
  <table class="w-full text-sm">
    <thead class="bg-gray-50 text-left text-gray-500"><tr><th class="p-3">Ad</th><th class="p-3">Telefon</th><th class="p-3">Hesap</th><th class="p-3">Oturum</th><th class="p-3">Not sayısı</th><th class="p-3">Son giriş</th><th class="p-3"></th></tr></thead>
    <tbody class="divide-y">
      @forelse($marketers as $marketer)
        <tr>
          <td class="p-3 font-medium">{{ $marketer->name }}</td>
          <td class="p-3">{{ $marketer->phone }}</td>
          <td class="p-3">
            @if($marketer->is_active)
              <span class="text-green-700 text-xs font-semibold">Aktif</span>
            @else
              <span class="text-red-700 text-xs font-semibold">Devre Dışı</span>
            @endif
          </td>
          <td class="p-3">
            @if($marketer->is_logged_in)
              <span class="text-green-700 text-xs font-semibold">● Aktif</span>
            @else
              <span class="text-gray-400 text-xs font-semibold">● Pasif</span>
            @endif
          </td>
          <td class="p-3">{{ $marketer->notes_count }}</td>
          <td class="p-3 text-gray-400">{{ $marketer->last_login_at?->format('d.m.Y H:i') ?? '—' }}</td>
          <td class="p-3 text-right space-x-2 whitespace-nowrap">
            <form method="POST" action="{{ route('admin.marketers.reset-password', $marketer) }}" class="inline" onsubmit="return confirm('Bu pazarlamacı için yeni bir şifre üretilsin mi? Eski şifre geçersiz olur.');">
              @csrf
              <button class="text-blue-600">Şifre Sıfırla</button>
            </form>
            <form method="POST" action="{{ route('admin.marketers.toggle-active', $marketer) }}" class="inline">
              @csrf
              <button class="{{ $marketer->is_active ? 'text-red-600' : 'text-green-700' }}">{{ $marketer->is_active ? 'Devre Dışı Bırak' : 'Aktif Et' }}</button>
            </form>
          </td>
        </tr>
      @empty
        <tr><td class="p-3 text-gray-400" colspan="7">Henüz pazarlamacı eklenmedi.</td></tr>
      @endforelse
    </tbody>
  </table>
</div>
@endsection
