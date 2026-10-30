@extends('admin.layout')
@section('title', 'Pazarlamacı Görüşmeleri')

@php
  // 25 Ekim 2026: tüm durumlar tek kaynaktan (CRM ile aynı).
  $statusLabels = \App\Http\Controllers\Marketer\CrmController::STATUSES;
@endphp

@section('content')
<div class="flex items-center justify-between mb-2">
  <h1 class="text-2xl font-bold">Pazarlamacı Görüşmeleri</h1>
  <a href="{{ route('admin.marketers.index') }}" class="text-sm font-semibold text-gray-700 underline">Pazarlamacılara dön</a>
</div>
<p class="text-sm text-gray-500 mb-4">Tüm pazarlamacıların hangi kurum ya da doktorla ne zaman görüştüğü, sonucu ve notu — en yeniden eskiye.</p>
{{-- 25 Ekim 2026: Kurum / Doktor görüşmeleri sekmesi. --}}
<div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px;">
  <a href="{{ route('admin.marketers.conversations', array_filter(['marketer' => request('marketer')])) }}" style="border-radius:10px;padding:7px 14px;font-weight:700;font-size:14px;text-decoration:none;border:1.5px solid {{ $doktor ? '#e5e7eb' : '#111827' }};background:{{ $doktor ? '#fff' : '#111827' }};color:{{ $doktor ? '#374151' : '#fff' }};">🏠 Kurum görüşmeleri</a>
  <a href="{{ route('admin.marketers.conversations', array_filter(['tur' => 'doktor', 'marketer' => request('marketer')])) }}" style="border-radius:10px;padding:7px 14px;font-weight:700;font-size:14px;text-decoration:none;border:1.5px solid {{ $doktor ? '#111827' : '#e5e7eb' }};background:{{ $doktor ? '#111827' : '#fff' }};color:{{ $doktor ? '#fff' : '#374151' }};">🩺 Doktor görüşmeleri</a>
</div>

{{-- 19 Ekim 2026: kurum sitesi fikri ölçümü özeti --}}
<div class="bg-white rounded-xl shadow-sm p-4 mb-4">
  <div class="font-bold mb-2">🌐 Kurum sitesi araştırması <span class="font-normal text-sm text-gray-500">(her kurumun en son cevabı)</span></div>
  @if($siteSurvey['asked'] || $siteSurvey['offered'])
    <div class="flex flex-wrap gap-x-6 gap-y-1 text-sm">
      <span>Sorulan kurum: <strong>{{ $siteSurvey['asked'] }}</strong></span>
      <span>Sitesi yok: <strong>{{ $siteSurvey['yok'] }}</strong>@if($siteSurvey['asked']) (%{{ round($siteSurvey['yok'] * 100 / $siteSurvey['asked']) }})@endif</span>
      <span>Sadece Instagram/Facebook: <strong>{{ $siteSurvey['sosyal'] }}</strong></span>
      <span>Sitesi var: <strong>{{ $siteSurvey['var'] }}</strong></span>
      <span>Site teklifiyle ilgilenen: <strong>{{ $siteSurvey['ilgilendi'] }}</strong> / {{ $siteSurvey['offered'] }}</span>
    </div>
  @else
    <p class="text-sm text-gray-500">Henüz cevap yok. Pazarlamacılar görüşme notunda "Kurumun web sitesi var mı?" sorusunu işaretledikçe burada toplanır.</p>
  @endif
</div>

<form method="GET" class="mb-4">
  @if($doktor)<input type="hidden" name="tur" value="doktor">@endif
  <select name="marketer" onchange="this.form.submit()" class="border rounded-lg px-3 py-2 text-sm">
    <option value="">Tüm pazarlamacılar</option>
    @foreach($marketers as $m)
      <option value="{{ $m->id }}" @selected(request('marketer') == $m->id)>{{ $m->name }}</option>
    @endforeach
  </select>
</form>

{{-- 11 Ekim 2026: kullanicinin talebi - genis <table> yerine flex-wrap
     kart (bkz. admin/facilities/_results.blade.php ayni desen). --}}
<div class="bg-white rounded-xl shadow-sm divide-y divide-gray-100">
  @forelse($notes as $note)
    <div class="p-4 flex flex-col gap-2">
      <div class="flex items-start justify-between gap-3 flex-wrap">
        <div class="min-w-0">
          @if($doktor && $note->expert)
            <a href="{{ route('admin.experts.edit', $note->expert) }}" class="text-blue-600 font-bold">🩺 {{ trim(($note->expert->title ? $note->expert->title.' ' : '').$note->expert->name) }}</a>
            <div class="text-xs text-gray-400">{{ $note->expert->branch_label ?: $note->expert->branch }} · {{ $note->expert->district }} / {{ $note->expert->city?->name }}</div>
          @elseif($doktor)
            <span class="text-gray-300 font-bold">(silinmiş doktor)</span>
          @elseif($note->facility)
            <a href="{{ route('admin.facilities.edit', $note->facility) }}" class="text-blue-600 font-bold">{{ $note->facility->name }}</a>
            <div class="text-xs text-gray-400">{{ $note->facility->district }} / {{ $note->facility->city?->name }}</div>
          @else
            <span class="text-gray-300 font-bold">(silinmiş kurum)</span>
          @endif
          <div class="text-xs text-gray-500 mt-0.5">{{ $note->created_at->format('d.m.Y H:i') }} · {{ $note->marketer?->name ?? ($note->admin_id ? 'Admin' : '—') }}</div>
        </div>
        <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-gray-100 text-gray-700 whitespace-nowrap">{{ $statusLabels[$note->status] ?? $note->status }}</span>
      </div>
      @if($note->contactLabel())<div class="text-xs text-gray-700 font-semibold">👤 Görüşülen: {{ $note->contactLabel() }}</div>@endif
      @if($doktor && $note->package_interest)<div class="text-xs text-gray-700 font-semibold">📦 İlgilendiği paket: {{ \App\Models\ExpertMarketingNote::PACKAGES[$note->package_interest] ?? $note->package_interest }}</div>@endif
      @if($note->website_status || $note->site_offer_interest)<div class="text-xs text-gray-700">🌐 {{ \App\Models\FacilityMarketingNote::WEBSITE_STATUSES[$note->website_status] ?? '' }}@if($note->website_status && $note->site_offer_interest) · @endif{{ \App\Models\FacilityMarketingNote::SITE_OFFER_INTEREST[$note->site_offer_interest] ?? '' }}</div>@endif
      <div class="text-xs text-gray-600 break-words">{{ $note->note ?: '—' }}</div>
      <div class="flex items-center gap-3 flex-wrap text-xs text-gray-500">
        <span>Broşür:
          @if($note->brochure_requested === null) —
          @elseif($note->brochure_requested) <span class="text-green-700 font-semibold">İstiyor</span>
          @else İstemiyor
          @endif
        </span>
        <span>Randevu: {{ $note->callback_at?->format('d.m.Y H:i') ?? '—' }}</span>
      </div>
    </div>
  @empty
    <div class="p-4 text-gray-400 text-sm">Henüz görüşme kaydı yok.</div>
  @endforelse
</div>
<div class="mt-6">{{ $notes->links() }}</div>
@endsection
