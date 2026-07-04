@extends('admin.layout')
@section('title', 'Şehirler')

@section('content')
<h1 class="text-2xl font-bold mb-6">Şehirler</h1>

<form method="POST" action="{{ route('admin.cities.store') }}" class="flex gap-2 mb-6">
  @csrf
  <input type="text" name="name" placeholder="Yeni şehir adı" required class="border rounded-lg px-3 py-2">
  <button class="bg-gray-900 text-white px-4 py-2 rounded-lg text-sm font-semibold">Ekle</button>
</form>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
  <table class="w-full text-sm">
    <thead class="bg-gray-50 text-left text-gray-500"><tr><th class="p-3">Ad</th><th class="p-3">Kurum Sayısı</th><th class="p-3"></th></tr></thead>
    <tbody class="divide-y">
      @foreach($cities as $city)
        <tr>
          <td class="p-3">{{ $city->name }}</td>
          <td class="p-3">{{ $city->facilities_count }}</td>
          <td class="p-3 text-right">
            <form method="POST" action="{{ route('admin.cities.destroy', $city) }}" onsubmit="return confirm('Silinsin mi?');">@csrf @method('DELETE')<button class="text-red-600">Sil</button></form>
          </td>
        </tr>
      @endforeach
    </tbody>
  </table>
</div>
@endsection
