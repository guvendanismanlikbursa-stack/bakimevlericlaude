@extends('admin.layout')
@section('title', 'Sahiplenme Başvuruları')

@section('content')
<h1 class="text-2xl font-bold mb-6">Sahiplenme Başvuruları</h1>

<form method="GET" class="mb-4">
  <select name="status" onchange="this.form.submit()" class="border rounded-lg px-3 py-2 text-sm">
    <option value="pending" @selected(request('status','pending')==='pending')>Bekleyenler</option>
    <option value="approved" @selected(request('status')==='approved')>Onaylananlar</option>
    <option value="rejected" @selected(request('status')==='rejected')>Reddedilenler</option>
  </select>
</form>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
  <table class="w-full text-sm">
    <thead class="bg-gray-50 text-left text-gray-500"><tr><th class="p-3">Kurum</th><th class="p-3">Başvuran</th><th class="p-3">E-posta</th><th class="p-3">Mesafe</th><th class="p-3">Tarih</th><th class="p-3"></th></tr></thead>
    <tbody class="divide-y">
      @forelse($claims as $claim)
        <tr>
          <td class="p-3 font-medium">{{ $claim->facility->name }}</td>
          <td class="p-3">{{ $claim->applicant_name }}</td>
          <td class="p-3">{{ $claim->applicant_email }}</td>
          <td class="p-3">
            @if($claim->distance_km !== null)
              <span class="{{ $claim->distance_km > 50 ? 'text-amber-700 font-semibold' : 'text-gray-600' }}">{{ number_format($claim->distance_km, 1) }} km</span>
            @else
              <span class="text-gray-300">—</span>
            @endif
          </td>
          <td class="p-3 text-gray-400">{{ $claim->created_at->format('d.m.Y H:i') }}</td>
          <td class="p-3 text-right"><a href="{{ route('admin.claims.show', $claim) }}" class="text-blue-600">İncele</a></td>
        </tr>
      @empty
        <tr><td class="p-3 text-gray-400" colspan="6">Kayıt yok.</td></tr>
      @endforelse
    </tbody>
  </table>
</div>
<div class="mt-6">{{ $claims->links() }}</div>
@endsection
