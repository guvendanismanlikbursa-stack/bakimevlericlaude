@extends('admin.layout')
@section('title', 'Kurum Kayıt Başvuruları')

@section('content')
<h1 class="text-2xl font-bold mb-6">Kurum Kayıt Başvuruları</h1>

<form method="GET" class="mb-4">
  <select name="status" onchange="this.form.submit()" class="border rounded-lg px-3 py-2 text-sm">
    <option value="pending" @selected(request('status','pending')==='pending')>Bekleyenler</option>
    <option value="revision_requested" @selected(request('status')==='revision_requested')>Revize İstenenler</option>
    <option value="approved" @selected(request('status')==='approved')>Onaylananlar</option>
    <option value="rejected" @selected(request('status')==='rejected')>Reddedilenler</option>
  </select>
</form>

{{-- 11 Ekim 2026: kullanicinin talebi - genis <table> yerine flex-wrap
     kart (bkz. admin/facilities/_results.blade.php ayni desen). --}}
<div class="bg-white rounded-xl shadow-sm divide-y divide-gray-100">
  @forelse($registrations as $registration)
    <div class="p-4 flex items-start justify-between gap-3 flex-wrap">
      <div class="min-w-0">
        <div class="font-bold text-gray-900 break-words">{{ $registration->name }}</div>
        <div class="text-xs text-gray-500 mt-0.5">{{ $registration->category->name ?? '-' }} · {{ $registration->city->name ?? '-' }} · {{ $registration->applicant_name }} · {{ $registration->applicant_email }} · {{ $registration->created_at->format('d.m.Y H:i') }}</div>
      </div>
      <a href="{{ route('admin.registrations.show', $registration) }}" class="text-blue-600 text-sm font-semibold whitespace-nowrap">İncele</a>
    </div>
  @empty
    <div class="p-4 text-gray-400 text-sm">Kayıt yok.</div>
  @endforelse
</div>
<div class="mt-6">{{ $registrations->links() }}</div>
@endsection
