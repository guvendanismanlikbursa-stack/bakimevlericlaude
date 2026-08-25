@extends('admin.layout')
@section('title', 'Anlaşmalı Kurumlar')

@section('content')
<div class="mb-6 flex items-center justify-between flex-wrap gap-3">
  <div>
    <h1 class="text-2xl font-bold">Anlaşmalı Kurumlar</h1>
    <p class="text-sm text-gray-500 mt-1">Kişisel aracılık (komisyonlu yerleştirme) yaptığınız kurumları burada işaretleyin. Sadece siz görürsünüz, kamuya açık siteyi etkilemez.</p>
  </div>
  <a href="{{ route('admin.broker.referrals') }}" class="bg-gray-900 text-white rounded-lg px-5 py-2.5 text-sm font-bold whitespace-nowrap">Yönlendirmeleri Gör</a>
</div>

<div class="bg-white rounded-xl shadow-sm p-4 mb-5">
  <div class="text-sm text-gray-500">Şu an anlaşmalı: <span class="font-bold text-gray-900">{{ $managedCount }}</span> kurum</div>
</div>

<form method="GET" class="mb-4 flex gap-2 flex-wrap">
  <input type="text" name="q" value="{{ $q }}" placeholder="Kurum adında ara..." class="border rounded-lg px-3 py-2 text-sm flex-1 min-w-[200px]">
  <button type="submit" class="bg-gray-900 text-white rounded-lg px-4 py-2 text-sm font-bold">Ara</button>
  @if($q !== '')
    <a href="{{ route('admin.broker.facilities') }}" class="text-sm font-semibold text-gray-500 underline self-center">Aramayı temizle</a>
  @endif
</form>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
  <table class="w-full text-sm">
    <thead class="bg-gray-50 text-left text-gray-500">
      <tr>
        <th class="p-3">Kurum</th>
        <th class="p-3">Şehir</th>
        <th class="p-3">Kategori</th>
        <th class="p-3">Durum</th>
        <th class="p-3"></th>
      </tr>
    </thead>
    <tbody class="divide-y">
      @forelse($facilities as $facility)
        <tr>
          <td class="p-3 font-medium">{{ $facility->name }}</td>
          <td class="p-3 text-gray-500">{{ $facility->city->name ?? '-' }}</td>
          <td class="p-3 text-gray-500">{{ $facility->category->name ?? '-' }}</td>
          <td class="p-3">
            @if($facility->is_broker_managed)
              <span class="bg-green-100 text-green-700 text-xs font-bold px-2 py-1 rounded-full">Anlaşmalı</span>
            @else
              <span class="text-gray-400 text-xs">-</span>
            @endif
          </td>
          <td class="p-3">
            <form method="POST" action="{{ route('admin.broker.facilities.toggle', $facility) }}">
              @csrf
              @if($facility->is_broker_managed)
                <button type="submit" class="text-red-600 text-sm font-bold hover:underline">Çıkar</button>
              @else
                <button type="submit" class="text-green-700 text-sm font-bold hover:underline">Ekle</button>
              @endif
            </form>
          </td>
        </tr>
      @empty
        <tr><td colspan="5" class="p-6 text-center text-gray-400">
          @if($q !== '')
            "{{ $q }}" için kurum bulunamadı.
          @else
            Henüz anlaşmalı kurum yok. Yukarıdan arayıp ekleyin.
          @endif
        </td></tr>
      @endforelse
    </tbody>
  </table>
</div>

<div class="mt-4">{{ $facilities->links() }}</div>
@endsection
