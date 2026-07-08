@extends('admin.layout')
@section('title', 'Bakiye Yüklemeleri')

@section('content')
<h1 class="text-2xl font-bold mb-6">Bakiye Yükleme Talepleri</h1>

<form method="GET" class="mb-4">
  <select name="status" onchange="this.form.submit()" class="border rounded-lg px-3 py-2 text-sm">
    <option value="pending" @selected(request('status','pending')==='pending')>Bekleyenler</option>
    <option value="approved" @selected(request('status')==='approved')>Onaylananlar</option>
    <option value="rejected" @selected(request('status')==='rejected')>Reddedilenler</option>
  </select>
</form>

<div class="space-y-4">
  @forelse($topups as $topup)
    <div class="bg-white rounded-xl shadow-sm p-5 flex gap-6">
      @if(str_ends_with(strtolower($topup->receipt_path), '.pdf'))
        <a href="{{ asset('storage/'.$topup->receipt_path) }}" target="_blank" class="w-40 h-32 rounded-lg border border-gray-200 bg-gray-50 flex flex-col items-center justify-center text-red-600 hover:bg-gray-100">
          <span class="text-3xl">📄</span>
          <span class="text-xs font-semibold mt-1">PDF'i Aç</span>
        </a>
      @else
        <a href="{{ asset('storage/'.$topup->receipt_path) }}" target="_blank">
          <img src="{{ asset('storage/'.$topup->receipt_path) }}" class="w-40 h-32 object-cover rounded-lg">
        </a>
      @endif
      <div class="flex-1">
        <h2 class="font-bold">{{ $topup->facility->name }}</h2>
        <p class="text-sm text-gray-600">Tutar: <strong>{{ number_format($topup->amount,2,',','.') }}₺</strong></p>
        <p class="text-xs text-gray-400">{{ $topup->created_at->format('d.m.Y H:i') }} · Durum: {{ $topup->status }}</p>
        @if($topup->note)<p class="text-sm text-gray-500 mt-1">{{ $topup->note }}</p>@endif

        @if($topup->status === 'pending')
          <div class="flex gap-3 mt-4">
            <form method="POST" action="{{ route('admin.topups.approve', $topup) }}">@csrf<button class="bg-green-600 text-white px-4 py-1.5 rounded-lg text-sm font-semibold">Onayla</button></form>
            <form method="POST" action="{{ route('admin.topups.reject', $topup) }}" class="flex gap-2">
              @csrf
              <input type="text" name="admin_note" placeholder="Red sebebi" class="border rounded-lg px-2 py-1.5 text-sm">
              <button class="bg-red-600 text-white px-4 py-1.5 rounded-lg text-sm font-semibold">Reddet</button>
            </form>
          </div>
        @endif
      </div>
    </div>
  @empty
    <p class="text-gray-400">Kayıt yok.</p>
  @endforelse
</div>
<div class="mt-6">{{ $topups->links() }}</div>
@endsection
