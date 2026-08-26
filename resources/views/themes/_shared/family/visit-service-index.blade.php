@extends('layouts.brand')

@section('content')
<div class="max-w-3xl mx-auto px-4 py-10">
  <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
    <div>
      <a href="{{ brand_route('family.dashboard') }}" class="inline-flex items-center gap-1.5 text-sm font-semibold text-gray-500 hover:text-gray-800 mb-2">← Panelime dön</a>
      <h1 class="text-xl font-black text-gray-950">Bakım Takip Ziyaretlerim</h1>
    </div>
    <a href="{{ brand_route('family.visit-service.create') }}" class="bg-primary text-white font-black text-sm px-4 py-2.5 rounded-lg">+ Yeni Talep</a>
  </div>

  @if($requests->isEmpty())
    <div class="bg-white rounded-xl shadow-sm p-8 text-center text-gray-500">
      Henüz bir bakım takip ziyareti talebiniz yok.
      <a href="{{ brand_route('family.visit-service.create') }}" class="text-primary font-semibold underline block mt-2">Şimdi talep oluşturun</a>
    </div>
  @else
    <div class="space-y-4">
      @foreach($requests as $req)
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
          <div class="p-5 border-b border-gray-100">
            <div class="flex items-start justify-between gap-3">
              <div>
                <div class="font-black text-gray-950">{{ $req->patient_name }}</div>
                <div class="text-sm text-gray-500">{{ $req->facility->name ?? '(kurum silinmiş)' }}</div>
              </div>
              <span class="text-xs font-bold px-2.5 py-1 rounded-full whitespace-nowrap
                {{ $req->status === 'aktif' ? 'bg-green-100 text-green-700' : ($req->status === 'pasif' ? 'bg-gray-100 text-gray-500' : 'bg-amber-100 text-amber-700') }}">
                {{ $req->statusLabel() }}
              </span>
            </div>
            @if($req->desired_frequency)
              <div class="text-xs text-gray-400 mt-1">İstenen sıklık: {{ $req->desired_frequency }}</div>
            @endif
          </div>

          <div class="p-5">
            @if($req->reports->isEmpty())
              <p class="text-sm text-gray-400">Henüz bir ziyaret raporu eklenmedi.</p>
            @else
              <div class="space-y-3">
                @foreach($req->reports as $report)
                  <div class="border-l-2 border-primary/30 pl-3">
                    <div class="text-xs text-gray-400">{{ $report->visited_at->format('d.m.Y') }}</div>
                    <p class="text-sm text-gray-700 mt-0.5">{{ $report->note }}</p>
                    @if($report->photo_path)
                      <img src="{{ asset('storage/'.$report->photo_path) }}" alt="Ziyaret fotoğrafı" class="mt-2 rounded-lg max-h-48">
                    @endif
                  </div>
                @endforeach
              </div>
            @endif
          </div>
        </div>
      @endforeach
    </div>
  @endif
</div>
@endsection
