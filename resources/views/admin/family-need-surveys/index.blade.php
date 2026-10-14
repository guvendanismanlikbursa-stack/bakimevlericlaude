@extends('admin.layout')
@section('title', 'Aile Anketleri')

@section('content')
<div class="flex items-center justify-between mb-2 flex-wrap gap-3">
  <div>
    <h1 class="text-2xl font-bold">📋 Aile Anketleri</h1>
    <p class="text-sm text-gray-500 mt-1">Hastanede toplanan kağıt anketler. Her satırı açıp o aileye uygun kurumlara WhatsApp gönderebilirsiniz.</p>
  </div>
  <a href="{{ route('admin.family-need-surveys.create') }}" class="bg-gray-900 text-white rounded-lg px-5 py-2.5 text-sm font-bold whitespace-nowrap">+ Yeni Anket Gir</a>
</div>

@if(session('success'))
  <div class="bg-green-100 text-green-800 px-4 py-3 rounded-lg text-sm mb-4 mt-4">{{ session('success') }}</div>
@endif

{{-- 12 Ekim 2026: kullanicinin talebi - "akordion yapi olsun asiri yer
     kaplamasin". Her anket <details> ile kapali baslar, acilinca detay +
     uygun kurum listesi (en fazla 10, bkz. FamilyNeedSurvey::matchingFacilities())
     gorunur. Kurum listesi SADECE acildiginda hesaplanir (performans). --}}
<div class="bg-white rounded-xl shadow-sm divide-y divide-gray-100 mt-4">
  @forelse($surveys as $survey)
    <details class="group">
      <summary class="p-4 flex items-center justify-between gap-3 flex-wrap cursor-pointer list-none hover:bg-gray-50">
        <div class="min-w-0">
          <span class="font-bold text-gray-900">{{ $survey->name ?: 'İsim girilmemiş' }}</span>
          <span class="text-sm text-gray-500">
            · {{ $survey->city->name }}@if($survey->district) / {{ $survey->district }}@endif @if($survey->neighborhood) / {{ $survey->neighborhood }}@endif
            · {{ \App\Models\FamilyNeedSurvey::SERVICE_TYPES[$survey->service_type] ?? $survey->service_type }}
          </span>
        </div>
        <span class="text-xs text-gray-400 whitespace-nowrap">
          @if($survey->sends_count > 0)
            <span class="text-green-700 font-semibold bg-green-100 px-2 py-0.5 rounded-full">📤 {{ $survey->sends_count }} gönderim</span>
          @else
            <span class="text-gray-400">Henüz gönderim yok</span>
          @endif
          {{ $survey->created_at->format('d.m.Y H:i') }} <span class="ml-1 text-gray-300 group-open:rotate-45 inline-block transition">+</span>
        </span>
      </summary>

      <div class="px-4 pb-4">
        <div class="flex items-center gap-2 flex-wrap text-xs text-gray-600 mb-3">
          <span class="bg-gray-100 px-2 py-0.5 rounded-full">📞 {{ $survey->phone ?: '—' }}</span>
          @if($survey->age_group)<span class="bg-gray-100 px-2 py-0.5 rounded-full">{{ \App\Models\FamilyNeedSurvey::AGE_GROUPS[$survey->age_group] ?? $survey->age_group }}</span>@endif
          @if($survey->urgency)<span class="bg-gray-100 px-2 py-0.5 rounded-full">{{ \App\Models\FamilyNeedSurvey::URGENCIES[$survey->urgency] ?? $survey->urgency }}</span>@endif
          @if($survey->ownership_preference)<span class="bg-gray-100 px-2 py-0.5 rounded-full">{{ \App\Models\FamilyNeedSurvey::OWNERSHIP_PREFERENCES[$survey->ownership_preference] ?? $survey->ownership_preference }}</span>@endif
          <form method="POST" action="{{ route('admin.family-need-surveys.destroy', $survey) }}" onsubmit="return confirm('Bu anket silinsin mi?');" class="ml-auto">
            @csrf @method('DELETE')
            <button class="text-red-600 font-semibold">Sil</button>
          </form>
        </div>

        <div class="text-xs font-bold text-gray-500 mb-2">Uygun Kurumlar (en fazla 10)</div>
        @php($matches = $survey->matchingFacilities())
        @if($matches->isEmpty())
          <p class="text-sm text-gray-400">Bu konuma/ihtiyaca uygun kurum bulunamadı.</p>
        @else
          <div class="divide-y divide-gray-100 border border-gray-100 rounded-lg">
            @foreach($matches as $facility)
              @php($sentForThis = \App\Models\FamilyNeedSurveySend::where('family_need_survey_id', $survey->id)->where('facility_id', $facility->id)->count())
              @php($totalSentToFacility = $facility->needSurveySends()->count())
              <div class="p-3 flex items-center justify-between gap-3 flex-wrap">
                <div class="min-w-0">
                  <a href="{{ route('admin.facilities.edit', $facility) }}" class="font-semibold text-gray-900 hover:underline">{{ $facility->name }}</a>
                  @if($sentForThis > 0)
                    <span class="ml-1 text-[11px] bg-green-100 text-green-700 px-1.5 py-0.5 rounded-full font-semibold whitespace-nowrap">✓ Bu anket için gönderildi ({{ $sentForThis }}x)</span>
                  @endif
                  <div class="text-xs text-gray-500">
                    {{ $facility->district }}@if($facility->neighborhood) / {{ $facility->neighborhood }}@endif · {{ $facility->category->name }}
                    @if($facility->is_claimed)<span class="text-green-700 font-semibold">· Sahipli</span>@elseif($facility->is_broker_managed)<span class="text-blue-700 font-semibold">· Anlaşmalı</span>@else<span class="text-amber-700 font-semibold">· Ön Kayıtlı</span>@endif
                    @if($totalSentToFacility > 0)
                      <span class="text-gray-400">· bu kuruma toplam {{ $totalSentToFacility }} anket gönderilmiş</span>
                    @endif
                  </div>
                </div>
                @php($hasMobile = classify_phone_type($facility->phone) === 'mobile')
                @if($hasMobile)
                  <a href="{{ route('admin.family-need-surveys.whatsapp', [$survey, $facility]) }}" target="_blank" rel="noopener" class="inline-block rounded-lg bg-green-600 text-white px-3 py-1.5 text-xs font-bold hover:bg-green-700 whitespace-nowrap">{{ $sentForThis > 0 ? "Tekrar Gönder" : "WhatsApp'tan Gönder" }}</a>
                @else
                  <span class="text-xs text-gray-400 whitespace-nowrap">📵 Cep telefonu yok</span>
                @endif
              </div>
            @endforeach
          </div>
        @endif
      </div>
    </details>
  @empty
    <div class="p-8 text-center text-gray-400">Henüz anket girilmedi.</div>
  @endforelse
</div>
<div class="mt-6">{{ $surveys->links() }}</div>
@endsection
