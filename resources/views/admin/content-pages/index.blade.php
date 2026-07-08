@extends('admin.layout')
@section('title', 'Statik Sayfalar & Bakım Rehberi')

@section('content')
<h1 class="text-2xl font-bold mb-6">Statik Sayfalar (Hakkımızda, KVKK) & Bakım Rehberi Makaleleri</h1>

<form method="GET" class="mb-4 flex gap-3">
  <select name="brand" onchange="this.form.submit()" class="border rounded-lg px-3 py-2 text-sm">
    <option value="">Tüm Markalar</option>
    @foreach($brands as $slug => $b)<option value="{{ $slug }}" @selected(request('brand')===$slug)>{{ $b['name'] }}</option>@endforeach
  </select>
  <select name="type" onchange="this.form.submit()" class="border rounded-lg px-3 py-2 text-sm">
    <option value="">Tüm Tipler</option>
    <option value="page" @selected(request('type')==='page')>Statik Sayfa</option>
    <option value="guide" @selected(request('type')==='guide')>Bakım Rehberi Makalesi</option>
  </select>
</form>

@if($editingPage)
  <p class="text-sm font-semibold text-amber-700 mb-2">"{{ $editingPage->title }}" düzenleniyor. <a href="{{ route('admin.content-pages.index') }}" class="underline">Vazgeç, yeni sayfa oluştur</a></p>
@endif
<form method="POST" action="{{ $editingPage ? route('admin.content-pages.update', $editingPage) : route('admin.content-pages.store') }}" class="bg-white rounded-xl shadow-sm p-5 grid md:grid-cols-2 gap-3 mb-8">
  @csrf
  @if($editingPage) @method('PUT') @endif
  <select name="brand" required class="border rounded-lg px-3 py-2">
    @foreach($brands as $slug => $b)<option value="{{ $slug }}" @selected($editingPage?->brand === $slug)>{{ $b['name'] }}</option>@endforeach
  </select>
  <select name="type" required class="border rounded-lg px-3 py-2">
    <option value="page" @selected(($editingPage?->type ?? 'page') === 'page')>Statik Sayfa (Hakkımızda, KVKK vb.)</option>
    <option value="guide" @selected($editingPage?->type === 'guide')>Bakım Rehberi Makalesi</option>
  </select>
  <input type="text" name="title" value="{{ old('title', $editingPage?->title) }}" placeholder="Başlık (örn: Hakkımızda / Huzurevi seçerken dikkat edilmesi gerekenler)" required class="border rounded-lg px-3 py-2 md:col-span-2">
  <input type="text" name="summary" value="{{ old('summary', $editingPage?->summary) }}" placeholder="Kısa özet (rehber makaleleri için listede gösterilir)" class="border rounded-lg px-3 py-2 md:col-span-2">
  <textarea name="body" placeholder="İçerik (HTML olabilir)" rows="4" required class="border rounded-lg px-3 py-2 md:col-span-2">{{ old('body', $editingPage?->body) }}</textarea>
  <button class="bg-gray-900 text-white px-4 py-2 rounded-lg text-sm font-semibold md:col-span-2">{{ $editingPage ? 'Güncelle' : 'Yeni Sayfa Oluştur' }}</button>
</form>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
  <table class="w-full text-sm">
    <thead class="bg-gray-50 text-left text-gray-500"><tr><th class="p-3">Marka</th><th class="p-3">Tip</th><th class="p-3">Başlık</th><th class="p-3">Slug</th><th class="p-3"></th></tr></thead>
    <tbody class="divide-y">
      @foreach($pages as $page)
        <tr>
          <td class="p-3">{{ $brands[$page->brand]['name'] ?? $page->brand }}</td>
          <td class="p-3">{{ $page->type === 'guide' ? 'Rehber' : 'Sayfa' }}</td>
          <td class="p-3">{{ $page->title }}</td>
          <td class="p-3 text-gray-400">{{ $page->slug }}</td>
          <td class="p-3 text-right whitespace-nowrap">
            <a href="{{ route('admin.content-pages.index', ['edit' => $page->id]) }}" class="text-primary font-semibold mr-3">Düzenle</a>
            <form method="POST" action="{{ route('admin.content-pages.destroy', $page) }}" onsubmit="return confirm('Silinsin mi?');" class="inline">@csrf @method('DELETE')<button class="text-red-600">Sil</button></form>
          </td>
        </tr>
      @endforeach
    </tbody>
  </table>
</div>
@endsection
