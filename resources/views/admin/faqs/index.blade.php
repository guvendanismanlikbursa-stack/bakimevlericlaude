@extends('admin.layout')
@section('title', 'Sıkça Sorulan Sorular')

@section('content')
<h1 class="text-2xl font-bold mb-6">Sıkça Sorulan Sorular (SSS)</h1>

<form method="GET" class="mb-4">
  <select name="brand" onchange="this.form.submit()" class="border rounded-lg px-3 py-2 text-sm">
    <option value="">Tüm Markalar</option>
    @foreach($brands as $slug => $b)<option value="{{ $slug }}" @selected(request('brand')===$slug)>{{ $b['name'] }}</option>@endforeach
  </select>
</form>

<form method="POST" action="{{ route('admin.faqs.store') }}" class="bg-white rounded-xl shadow-sm p-5 grid md:grid-cols-2 gap-3 mb-8">
  @csrf
  <select name="brand" required class="border rounded-lg px-3 py-2">
    @foreach($brands as $slug => $b)<option value="{{ $slug }}">{{ $b['name'] }}</option>@endforeach
  </select>
  <input type="number" name="sort_order" placeholder="Sıra (0,10,20...)" class="border rounded-lg px-3 py-2">
  <input type="text" name="question" placeholder="Soru" required class="border rounded-lg px-3 py-2 md:col-span-2">
  <textarea name="answer" placeholder="Cevap" rows="3" required class="border rounded-lg px-3 py-2 md:col-span-2"></textarea>
  <label class="flex items-center gap-2 text-sm md:col-span-2"><input type="checkbox" name="is_active" value="1" checked> Yayında</label>
  <button class="bg-gray-900 text-white px-4 py-2 rounded-lg text-sm font-semibold md:col-span-2">Ekle</button>
</form>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
  <table class="w-full text-sm">
    <thead class="bg-gray-50 text-left text-gray-500"><tr><th class="p-3">Marka</th><th class="p-3">Soru</th><th class="p-3">Sıra</th><th class="p-3">Durum</th><th class="p-3"></th></tr></thead>
    <tbody class="divide-y">
      @foreach($faqs as $faq)
        <tr>
          <td class="p-3">{{ $brands[$faq->brand]['name'] ?? $faq->brand }}</td>
          <td class="p-3">{{ $faq->question }}</td>
          <td class="p-3 text-gray-400">{{ $faq->sort_order }}</td>
          <td class="p-3">{{ $faq->is_active ? 'Yayında' : 'Pasif' }}</td>
          <td class="p-3 text-right whitespace-nowrap">
            <form method="POST" action="{{ route('admin.faqs.destroy', $faq) }}" class="inline" onsubmit="return confirm('Silinsin mi?');">@csrf @method('DELETE')<button class="text-red-600">Sil</button></form>
          </td>
        </tr>
      @endforeach
    </tbody>
  </table>
</div>
@endsection
