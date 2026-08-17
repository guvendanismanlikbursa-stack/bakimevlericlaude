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

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
  <table class="w-full text-sm">
    <thead class="bg-gray-50 text-left text-gray-500">
      <tr>
        <th class="p-3">Görev</th>
        <th class="p-3">Beklenen sıklık</th>
        <th class="p-3">Son başarılı çalışma</th>
        <th class="p-3">Son başarısızlık</th>
        <th class="p-3">Art arda hata</th>
        <th class="p-3">Durum</th>
      </tr>
    </thead>
    <tbody class="divide-y">
      @forelse($jobs as $job)
        <tr class="{{ $job->isOverdue() ? 'bg-red-50' : '' }}">
          <td class="p-3 font-mono text-xs">{{ $job->job_name }}</td>
          <td class="p-3 text-gray-500">
            @if($job->expected_frequency_minutes < 60)
              {{ $job->expected_frequency_minutes }} dakikada bir
            @elseif($job->expected_frequency_minutes < 1440)
              {{ round($job->expected_frequency_minutes / 60) }} saatte bir
            @else
              {{ round($job->expected_frequency_minutes / 1440) }} günde bir
            @endif
          </td>
          <td class="p-3">
            @if($job->last_success_at)
              {{ $job->last_success_at->diffForHumans() }}
              <div class="text-[11px] text-gray-400">{{ $job->last_success_at->format('d.m.Y H:i') }}</div>
            @else
              <span class="text-gray-300">—</span>
            @endif
          </td>
          <td class="p-3">
            @if($job->last_failure_at)
              <span class="text-red-600">{{ $job->last_failure_at->diffForHumans() }}</span>
            @else
              <span class="text-gray-300">—</span>
            @endif
          </td>
          <td class="p-3">
            @if($job->consecutive_failures > 0)
              <span class="text-red-600 font-semibold">{{ $job->consecutive_failures }}</span>
            @else
              <span class="text-gray-300">0</span>
            @endif
          </td>
          <td class="p-3">
            @if($job->isOverdue())
              <span class="bg-red-100 text-red-700 text-xs font-semibold px-2 py-0.5 rounded-full">Gecikmede</span>
            @else
              <span class="bg-green-100 text-green-700 text-xs font-semibold px-2 py-0.5 rounded-full">Zamanında</span>
            @endif
          </td>
        </tr>
      @empty
        <tr><td class="p-3 text-gray-400" colspan="6">Henüz hiçbir görev çalışmamış.</td></tr>
      @endforelse
    </tbody>
  </table>
</div>
@endsection
