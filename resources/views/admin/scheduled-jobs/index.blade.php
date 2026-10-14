@extends('admin.layout')
@section('title', 'Zamanlanan Görevler')

@section('content')
@php($overdueCount = $jobs->filter(fn ($j) => $j->isOverdue())->count())
<div class="flex items-center gap-3 mb-2">
  <h1 class="text-2xl font-bold">Zamanlanan Görevler</h1>
  @if($overdueCount > 0)
    <span class="bg-red-100 text-red-700 text-xs font-semibold px-2 py-1 rounded-full">{{ $overdueCount }} gecikmede</span>
  @else
    <span class="bg-green-100 text-green-700 text-xs font-semibold px-2 py-1 rounded-full">Hepsi zamanında</span>
  @endif
</div>
<p class="text-sm text-gray-500 mb-6">Sunucudaki gece/saatlik otomatik görevlerin (yedekleme, kuyruk, günlük kontroller vb.) son ne zaman başarıyla çalıştığını gösterir. Bir görev burada hiç görünmüyorsa henüz hiç çalışmamış demektir.</p>

{{-- 11 Ekim 2026: kullanicinin talebi - genis <table> yerine flex-wrap
     kart (bkz. admin/facilities/_results.blade.php ayni desen). --}}
<div class="bg-white rounded-xl shadow-sm divide-y divide-gray-100">
  @forelse($jobs as $job)
    <div class="p-4 flex items-start justify-between gap-3 flex-wrap {{ $job->isOverdue() ? 'bg-red-50' : '' }}">
      <div class="min-w-0">
        <div class="font-mono text-xs font-bold text-gray-900">{{ $job->job_name }}</div>
        <div class="text-xs text-gray-500 mt-0.5">
          Beklenen sıklık:
          @if($job->expected_frequency_minutes < 60)
            {{ $job->expected_frequency_minutes }} dakikada bir
          @elseif($job->expected_frequency_minutes < 1440)
            {{ round($job->expected_frequency_minutes / 60) }} saatte bir
          @else
            {{ round($job->expected_frequency_minutes / 1440) }} günde bir
          @endif
        </div>
        <div class="text-xs text-gray-500 mt-0.5">
          Son başarılı:
          @if($job->last_success_at)
            {{ $job->last_success_at->diffForHumans() }} ({{ $job->last_success_at->format('d.m.Y H:i') }})
          @else
            —
          @endif
          @if($job->last_failure_at)
            · Son başarısızlık: <span class="text-red-600">{{ $job->last_failure_at->diffForHumans() }}</span>
          @endif
          @if($job->consecutive_failures > 0)
            · Art arda hata: <span class="text-red-600 font-semibold">{{ $job->consecutive_failures }}</span>
          @endif
        </div>
      </div>
      @if($job->isOverdue())
        <span class="bg-red-100 text-red-700 text-xs font-semibold px-2 py-0.5 rounded-full whitespace-nowrap">Gecikmede</span>
      @else
        <span class="bg-green-100 text-green-700 text-xs font-semibold px-2 py-0.5 rounded-full whitespace-nowrap">Zamanında</span>
      @endif
    </div>
  @empty
    <div class="p-4 text-gray-400 text-sm">Henüz hiçbir görev çalışmamış.</div>
  @endforelse
</div>
@endsection
