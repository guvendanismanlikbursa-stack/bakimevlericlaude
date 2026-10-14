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

{{-- 11 Ekim 2026: kullanicinin talebi - genis <table> yerine flex-wrap
     kart (bkz. admin/facilities/_results.blade.php ayni desen). --}}
<div class="bg-white rounded-xl shadow-sm divide-y divide-gray-100">
  @forelse($leads as $lead)
    <div class="p-4 flex items-start justify-between gap-3 flex-wrap">
      <div class="min-w-0">
        <div class="font-bold text-gray-900">{{ $lead->full_name }}</div>
        <div class="text-xs text-gray-500 mt-0.5">
          📞 {{ $lead->phone }}@if($lead->email) · {{ $lead->email }}@endif ·
          @if($lead->facility)
            <a href="{{ route('admin.facilities.edit', $lead->facility) }}" class="text-primary hover:underline">{{ $lead->facility->name }}</a> ({{ $lead->facility->city?->name }})
          @else
            (kurum silinmiş)
          @endif
          · {{ $lead->created_at->format('d.m.Y H:i') }}
        </div>
        @if($lead->message)<div class="text-xs text-gray-600 mt-1" title="{{ $lead->message }}">{{ $lead->message }}</div>@endif
      </div>
      <form method="POST" action="{{ route('admin.facility-interest-leads.update', $lead) }}">
        @csrf @method('PUT')
        <select name="status" onchange="this.form.submit()" class="border rounded px-2 py-1 text-xs">
          @foreach(['yeni'=>'Yeni','iletisime_gecildi'=>'İletişime Geçildi','anlasmali_oldu'=>'Anlaşmalı Oldu','ilgilenmiyor'=>'İlgilenmiyor'] as $k=>$v)
            <option value="{{ $k }}" @selected($lead->status===$k)>{{ $v }}</option>
          @endforeach
        </select>
      </form>
    </div>
  @empty
    <div class="p-4 text-gray-400 text-sm">Kayıt yok.</div>
  @endforelse
</div>
<div class="mt-6">{{ $leads->links() }}</div>
@endsection
