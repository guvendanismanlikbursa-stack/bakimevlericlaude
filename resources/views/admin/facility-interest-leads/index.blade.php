@extends('admin.layout')
@section('title', 'Kurum İlgi Talepleri')

@section('content')
<h1 class="text-2xl font-bold mb-2">Kurum İlgi Talepleri</h1>
<p class="text-sm text-gray-500 mb-4">Sahiplenilmemiş (henüz anlaşmalı olmayan) kurumların sayfasındaki "Bilgi ve Fiyat Talep Et" formundan gelen talepler. Bu veriyi kurumu anlaşmalı olmaya ikna ederken kullanabilirsiniz.</p>

<form method="GET" class="flex gap-3 mb-4 flex-wrap items-center">
  <select name="status" onchange="this.form.submit()" class="border rounded-lg px-3 py-2 text-sm">
    <option value="">Tüm Durumlar</option>
    @foreach(['yeni'=>'Yeni','iletisime_gecildi'=>'İletişime Geçildi','anlasmali_oldu'=>'Anlaşmalı Oldu','ilgilenmiyor'=>'İlgilenmiyor'] as $k=>$v)
      <option value="{{ $k }}" @selected(request('status')===$k)>{{ $v }}</option>
    @endforeach
  </select>
</form>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
  <table class="w-full text-sm">
    <thead class="bg-gray-50 text-left text-gray-500">
      <tr><th class="p-3">Ad Soyad</th><th class="p-3">Telefon</th><th class="p-3">Kurum</th><th class="p-3">Mesaj</th><th class="p-3">Tarih</th><th class="p-3">Durum</th></tr>
    </thead>
    <tbody class="divide-y">
      @forelse($leads as $lead)
        <tr>
          <td class="p-3 font-medium">{{ $lead->full_name }}</td>
          <td class="p-3">
            {{ $lead->phone }}
            @if($lead->email)<span class="block text-xs text-gray-400">{{ $lead->email }}</span>@endif
          </td>
          <td class="p-3">
            @if($lead->facility)
              <a href="{{ route('admin.facilities.edit', $lead->facility) }}" class="text-primary hover:underline">{{ $lead->facility->name }}</a>
              <span class="block text-xs text-gray-400">{{ $lead->facility->city?->name }}</span>
            @else
              <span class="text-gray-400">(kurum silinmiş)</span>
            @endif
          </td>
          <td class="p-3 text-xs text-gray-600 max-w-[260px] line-clamp-2" title="{{ $lead->message }}">{{ $lead->message ?: '-' }}</td>
          <td class="p-3 text-gray-400 whitespace-nowrap">{{ $lead->created_at->format('d.m.Y H:i') }}</td>
          <td class="p-3">
            <form method="POST" action="{{ route('admin.facility-interest-leads.update', $lead) }}">
              @csrf @method('PUT')
              <select name="status" onchange="this.form.submit()" class="border rounded px-2 py-1 text-xs">
                @foreach(['yeni'=>'Yeni','iletisime_gecildi'=>'İletişime Geçildi','anlasmali_oldu'=>'Anlaşmalı Oldu','ilgilenmiyor'=>'İlgilenmiyor'] as $k=>$v)
                  <option value="{{ $k }}" @selected($lead->status===$k)>{{ $v }}</option>
                @endforeach
              </select>
            </form>
          </td>
        </tr>
      @empty
        <tr><td class="p-3 text-gray-400" colspan="6">Kayıt yok.</td></tr>
      @endforelse
    </tbody>
  </table>
</div>
<div class="mt-6">{{ $leads->links() }}</div>
@endsection
