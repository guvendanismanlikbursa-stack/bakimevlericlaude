@extends('admin.layout')
@section('title', $visitServiceRequest->patient_name)

@section('content')
<a href="{{ route('admin.visit-service.index') }}" class="inline-flex items-center gap-1.5 text-sm font-semibold text-gray-500 hover:text-gray-800 mb-4">← Listeye dön</a>

<div class="max-w-3xl">
  <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
    <div class="flex flex-wrap items-start justify-between gap-4 mb-4">
      <div>
        <h1 class="text-xl font-bold">{{ $visitServiceRequest->patient_name }}</h1>
        <p class="text-sm text-gray-500">{{ $visitServiceRequest->facility->name ?? '(kurum silinmiş)' }}</p>
      </div>
      <form method="POST" action="{{ route('admin.visit-service.update-status', $visitServiceRequest) }}">
        @csrf
        @method('PUT')
        <select name="status" onchange="this.form.submit()" class="border rounded-lg px-3 py-2 text-sm font-semibold">
          @foreach(\App\Models\VisitServiceRequest::STATUSES as $key => $label)
            <option value="{{ $key }}" @selected($visitServiceRequest->status === $key)>{{ $label }}</option>
          @endforeach
        </select>
      </form>
    </div>

    <div class="grid grid-cols-2 gap-x-4 gap-y-2 text-sm">
      <div><span class="text-gray-400">Aile:</span> {{ $visitServiceRequest->familyUser->name ?? '-' }}</div>
      <div><span class="text-gray-400">Telefon:</span> {{ $visitServiceRequest->phone }}</div>
      @if($visitServiceRequest->patient_age)<div><span class="text-gray-400">Yaş:</span> {{ $visitServiceRequest->patient_age }}</div>@endif
      @if($visitServiceRequest->desired_frequency)<div><span class="text-gray-400">İstenen sıklık:</span> {{ $visitServiceRequest->desired_frequency }}</div>@endif
    </div>
    @if($visitServiceRequest->patient_condition)
      <div class="text-sm mt-3"><span class="text-gray-400">Genel durum:</span> {{ $visitServiceRequest->patient_condition }}</div>
    @endif

    @php
      $waMessage = "Merhaba, \"{$visitServiceRequest->facility->name}\" kurumundaki {$visitServiceRequest->patient_name} için bakım takip ziyareti talebiniz alındı.";
      $waDigits = normalize_whatsapp_number($visitServiceRequest->phone);
      $waUrl = $waDigits ? 'https://wa.me/'.$waDigits.'?text='.rawurlencode($waMessage) : null;
    @endphp
    @if($waUrl)
      <a href="{{ $waUrl }}" target="_blank" class="inline-flex items-center gap-1.5 mt-4 bg-[#25D366] text-white font-bold text-xs px-4 py-2 rounded-lg">📱 Aileye WhatsApp'tan yaz</a>
    @else
      <p class="text-xs text-amber-700 mt-4">Ailenin kayıtlı telefonu WhatsApp için uygun görünmüyor - iletişime elle geçin.</p>
    @endif
  </div>

  <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
    <h2 class="font-bold mb-3">Yeni Ziyaret Raporu Ekle</h2>
    <form method="POST" action="{{ route('admin.visit-service.store-report', $visitServiceRequest) }}" enctype="multipart/form-data" class="space-y-3">
      @csrf
      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="text-xs font-medium text-gray-500">Ziyaret Tarihi</label>
          <input type="date" name="visited_at" value="{{ old('visited_at', now()->toDateString()) }}" max="{{ now()->toDateString() }}" required class="border rounded-lg px-3 py-2 w-full mt-1 text-sm">
          @error('visited_at')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>
        <div>
          <label class="text-xs font-medium text-gray-500">Fotoğraf (isteğe bağlı)</label>
          <input type="file" name="photo" accept="image/*" class="border rounded-lg px-3 py-1.5 w-full mt-1 text-sm">
          @error('photo')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>
      </div>
      <div>
        <label class="text-xs font-medium text-gray-500">Rapor Notu</label>
        <textarea name="note" rows="3" required class="border rounded-lg px-3 py-2 w-full mt-1 text-sm" placeholder="Ziyarette gözlemlenenler, hastanın durumu, iletilen ihtiyaçlar...">{{ old('note') }}</textarea>
        @error('note')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
      </div>
      <button type="submit" class="bg-gray-900 text-white font-bold text-sm px-4 py-2 rounded-lg">Raporu Kaydet ve Aileye Bildir</button>
    </form>
  </div>

  <div class="bg-white rounded-xl shadow-sm p-6">
    <h2 class="font-bold mb-3">Ziyaret Geçmişi ({{ $visitServiceRequest->reports->count() }})</h2>
    @forelse($visitServiceRequest->reports as $report)
      <div class="border-t border-gray-100 py-3 first:border-t-0 first:pt-0">
        <div class="flex items-start justify-between gap-3">
          <div>
            <div class="text-xs text-gray-400">{{ $report->visited_at->format('d.m.Y') }} @if($report->admin) · {{ $report->admin->name }}@endif</div>
            <p class="text-sm text-gray-700 mt-1">{{ $report->note }}</p>
            @if($report->photo_path)
              <img src="{{ asset('storage/'.$report->photo_path) }}" alt="Ziyaret fotoğrafı" class="mt-2 rounded-lg max-h-40">
            @endif
          </div>
          <form method="POST" action="{{ route('admin.visit-service.destroy-report', $report) }}" onsubmit="return confirm('Bu rapor silinsin mi?');">
            @csrf
            @method('DELETE')
            <button type="submit" class="text-red-600 text-xs font-bold hover:underline whitespace-nowrap">Sil</button>
          </form>
        </div>
      </div>
    @empty
      <p class="text-sm text-gray-400">Henüz bir ziyaret raporu eklenmedi.</p>
    @endforelse
  </div>
</div>
@endsection
