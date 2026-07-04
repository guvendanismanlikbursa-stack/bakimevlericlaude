@extends('admin.layout')
@section('title', 'İletişim Mesajları')

@section('content')
<h1 class="text-2xl font-bold mb-6">İletişim Mesajları</h1>

<form method="GET" class="mb-4">
  <select name="brand" onchange="this.form.submit()" class="border rounded-lg px-3 py-2 text-sm">
    <option value="">Tüm Markalar</option>
    @foreach($brands as $slug => $b)<option value="{{ $slug }}" @selected(request('brand')===$slug)>{{ $b['name'] }}</option>@endforeach
  </select>
</form>

<div class="space-y-3">
  @forelse($messages as $m)
    <div class="bg-white rounded-xl shadow-sm p-4 {{ $m->is_read ? '' : 'border-l-4 border-orange-400' }}">
      <div class="flex justify-between text-sm">
        <div class="font-semibold">{{ $m->name }} <span class="text-gray-400 font-normal">({{ $m->email }})</span></div>
        <div class="text-gray-400">{{ $brands[$m->brand]['name'] ?? $m->brand }} · {{ $m->created_at->format('d.m.Y H:i') }}</div>
      </div>
      <div class="text-sm font-medium mt-1">{{ $m->subject }}</div>
      <p class="text-sm text-gray-600 mt-2">{{ $m->message }}</p>
      <div class="mt-3 flex gap-3 text-xs">
        @unless($m->is_read)
          <form method="POST" action="{{ route('admin.contact-messages.read', $m) }}">@csrf @method('PATCH')<button class="text-blue-600">Okundu işaretle</button></form>
        @endunless
        <form method="POST" action="{{ route('admin.contact-messages.destroy', $m) }}" onsubmit="return confirm('Silinsin mi?');">@csrf @method('DELETE')<button class="text-red-600">Sil</button></form>
      </div>
    </div>
  @empty
    <p class="text-gray-400">Kayıt yok.</p>
  @endforelse
</div>
<div class="mt-6">{{ $messages->links() }}</div>
@endsection
