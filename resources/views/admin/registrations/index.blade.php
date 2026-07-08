@extends('admin.layout')
@section('title', 'Kurum Kayıt Başvuruları')

@section('content')
<h1 class="text-2xl font-bold mb-6">Kurum Kayıt Başvuruları</h1>

<form method="GET" class="mb-4">
  <select name="status" onchange="this.form.submit()" class="border rounded-lg px-3 py-2 text-sm">
    <option value="pending" @selected(request('status','pending')==='pending')>Bekleyenler</option>
    <option value="revision_requested" @selected(request('status')==='revision_requested')>Revize İstenenler</option>
    <option value="approved" @selected(request('status')==='approved')>Onaylananlar</option>
  </select>
</form>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
  <table class="w-full text-sm">
    <thead class="bg-gray-50 text-left text-gray-500"><tr><th class="p-3">Kurum Adı</th><th class="p-3">Kategori</th><th class="p-3">Şehir</th><th class="p-3">Başvuran</th><th class="p-3">E-posta</th><th class="p-3">Tarih</th><th class="p-3"></th></tr></thead>
    <tbody class="divide-y">
      @forelse($registrations as $registration)
        <tr>
          <td class="p-3 font-medium">{{ $registration->name }}</td>
          <td class="p-3">{{ $registration->category->name ?? '-' }}</td>
          <td class="p-3">{{ $registration->city->name ?? '-' }}</td>
          <td class="p-3">{{ $registration->applicant_name }}</td>
          <td class="p-3">{{ $registration->applicant_email }}</td>
          <td class="p-3 text-gray-400">{{ $registration->created_at->format('d.m.Y H:i') }}</td>
          <td class="p-3 text-right"><a href="{{ route('admin.registrations.show', $registration) }}" class="text-blue-600">İncele</a></td>
        </tr>
      @empty
        <tr><td class="p-3 text-gray-400" colspan="7">Kayıt yok.</td></tr>
      @endforelse
    </tbody>
  </table>
</div>
<div class="mt-6">{{ $registrations->links() }}</div>
@endsection
