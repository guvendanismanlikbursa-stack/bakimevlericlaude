@extends('admin.layout')
@section('title', 'Yakınımı Ziyaret Et Talepleri')

@section('content')
<div class="mb-6">
  <h1 class="text-2xl font-bold">Yakınımı Ziyaret Et Talepleri</h1>
  <p class="text-sm text-gray-500 mt-1">Ailelerin, kurumda yatan yakınları için talep ettiği periyodik ziyaret+rapor hizmeti. Sadece "Ziyaret hizmetine açık" işaretli yaşlı bakım kurumları için aileye gösterilir.</p>
</div>

<div class="flex flex-wrap gap-2 mb-5">
  @foreach(\App\Models\VisitServiceRequest::STATUSES + ['' => 'Tümü'] as $key => $label)
    <a href="{{ route('admin.visit-service.index', array_filter(['status' => $key])) }}"
       class="inline-flex items-center gap-2 rounded-xl border px-4 py-2 text-sm font-semibold {{ $status === $key ? 'bg-gray-900 text-white border-gray-900' : 'bg-white text-gray-700 border-gray-200' }}">
      <span>{{ $label }}</span>
      @if($key === 'yeni')<span class="text-xs {{ $status === $key ? 'text-gray-300' : 'text-gray-400' }}">{{ $newCount }}</span>@endif
    </a>
  @endforeach
</div>

<div class="space-y-2">
  @forelse($visitServiceRequests as $req)
    <a href="{{ route('admin.visit-service.show', $req) }}" class="block bg-white rounded-xl shadow-sm p-4 hover:shadow-md transition-shadow">
      <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="min-w-0">
          <div class="font-bold text-gray-950 truncate">{{ $req->patient_name }}@if($req->patient_age) <span class="font-normal text-gray-400">· {{ $req->patient_age }} yaş</span>@endif</div>
          <div class="text-xs text-gray-500 truncate">{{ $req->facility->name ?? '(kurum silinmiş)' }} · {{ $req->familyUser->name ?? '' }} · {{ $req->created_at->format('d.m.Y H:i') }}</div>
        </div>
        <span class="text-xs font-bold px-2.5 py-1 rounded-full whitespace-nowrap
          {{ $req->status === 'aktif' ? 'bg-green-100 text-green-700' : ($req->status === 'pasif' ? 'bg-gray-100 text-gray-500' : 'bg-amber-100 text-amber-700') }}">
          {{ $req->statusLabel() }}
        </span>
      </div>
    </a>
  @empty
    <div class="bg-white rounded-xl shadow-sm p-6 text-center text-gray-400">Bu filtrede talep yok.</div>
  @endforelse
</div>

<div class="mt-4">{{ $visitServiceRequests->links() }}</div>
@endsection
