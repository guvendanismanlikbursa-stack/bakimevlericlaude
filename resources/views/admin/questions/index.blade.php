@extends('admin.layout')
@section('title', 'Aile Soruları')

@section('content')
<h1 class="text-2xl font-bold mb-6">Aile Soruları (Moderasyon)</h1>

<form method="GET" class="mb-4 flex gap-3">
  <select name="brand" onchange="this.form.submit()" class="border rounded-lg px-3 py-2 text-sm">
    <option value="">Tüm Markalar</option>
    @foreach($brands as $slug => $b)<option value="{{ $slug }}" @selected(request('brand')===$slug)>{{ $b['name'] }}</option>@endforeach
  </select>
  <select name="status" onchange="this.form.submit()" class="border rounded-lg px-3 py-2 text-sm">
    <option value="">Tüm Durumlar</option>
    <option value="pending" @selected(request('status')==='pending')>Cevap Bekliyor</option>
    <option value="answered" @selected(request('status')==='answered')>Cevaplandı</option>
  </select>
</form>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
  <table class="w-full text-sm">
    <thead class="bg-gray-50 text-left text-gray-500"><tr><th class="p-3">Kurum</th><th class="p-3">Soru</th><th class="p-3">Cevap</th><th class="p-3">Tarih</th><th class="p-3"></th></tr></thead>
    <tbody class="divide-y">
      @forelse($questions as $q)
        <tr>
          <td class="p-3">{{ $q->facility?->name }}</td>
          <td class="p-3">{{ $q->asker_name }}: {{ $q->question }}</td>
          <td class="p-3 text-gray-500">{{ $q->answer ?: '— bekliyor —' }}</td>
          <td class="p-3 text-gray-400 whitespace-nowrap">{{ $q->created_at->format('d.m.Y H:i') }}</td>
          <td class="p-3 text-right"><form method="POST" action="{{ route('admin.questions.destroy', $q) }}" onsubmit="return confirm('Silinsin mi?');">@csrf @method('DELETE')<button class="text-red-600">Sil</button></form></td>
        </tr>
      @empty
        <tr><td colspan="5" class="p-8 text-center text-gray-500">Kayıt bulunamadı.</td></tr>
      @endforelse
    </tbody>
  </table>
</div>
<div class="mt-6">{{ $questions->links() }}</div>
@endsection
