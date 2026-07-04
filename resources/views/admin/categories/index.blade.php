@extends('admin.layout')
@section('title', 'Kategoriler')

@section('content')
<h1 class="text-2xl font-bold mb-6">Kategoriler</h1>
<p class="text-sm text-gray-500 mb-4">Kategoriler üç ana hizmet bölümüne bağlanır. Her site bu bölümlerin tamamını gösterebilir; admin buradan kategori ekler veya siler.</p>

<form method="POST" action="{{ route('admin.categories.store') }}" class="bg-white rounded-xl shadow-sm p-4 grid md:grid-cols-[1fr_260px_auto] gap-2 mb-6">
  @csrf
  <input type="text" name="name" placeholder="Kategori adı" required class="border rounded-lg px-3 py-2">
  <select name="brand_scope" required class="border rounded-lg px-3 py-2">
    @foreach(service_sections() as $section)
      @foreach($section['scopes'] as $scope)
        <option value="{{ $scope }}">{{ $section['title'] }} · {{ $scope }}</option>
      @endforeach
    @endforeach
  </select>
  <button class="bg-gray-900 text-white px-4 py-2 rounded-lg text-sm font-semibold">Ekle</button>
</form>

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
  <table class="w-full text-sm">
    <thead class="bg-gray-50 text-left text-gray-500"><tr><th class="p-3">Ad</th><th class="p-3">Ana Bölüm</th><th class="p-3">Kapsam</th><th class="p-3">Kurum Sayısı</th><th class="p-3"></th></tr></thead>
    <tbody class="divide-y">
      @foreach($categories as $category)
        @php $section = service_section_for_scope($category->brand_scope); @endphp
        <tr>
          <td class="p-3">{{ $category->name }}</td>
          <td class="p-3">{{ $section['title'] ?? '-' }}</td>
          <td class="p-3">{{ $category->brand_scope }}</td>
          <td class="p-3">{{ $category->facilities_count }}</td>
          <td class="p-3 text-right">
            <form method="POST" action="{{ route('admin.categories.destroy', $category) }}" onsubmit="return confirm('Silinsin mi?');">@csrf @method('DELETE')<button class="text-red-600">Sil</button></form>
          </td>
        </tr>
      @endforeach
    </tbody>
  </table>
</div>
@endsection