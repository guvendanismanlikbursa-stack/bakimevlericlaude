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

      @if($m->admin_reply)
        <div class="mt-3 bg-green-50 border border-green-100 rounded-lg p-3">
          <div class="text-xs font-semibold text-green-800 mb-1">Verilen cevap ({{ $m->replied_at?->format('d.m.Y H:i') }}):</div>
          <p class="text-sm text-green-900 whitespace-pre-line">{{ $m->admin_reply }}</p>
        </div>
      @endif

      <div class="mt-3 flex gap-3 text-xs items-center">
        @unless($m->is_read)
          <form method="POST" action="{{ route('admin.contact-messages.read', $m) }}">@csrf @method('PATCH')<button class="text-blue-600">Okundu işaretle</button></form>
        @endunless
        <form method="POST" action="{{ route('admin.contact-messages.destroy', $m) }}" onsubmit="return confirm('Silinsin mi?');">@csrf @method('DELETE')<button class="text-red-600">Sil</button></form>
        <button type="button" class="text-primary font-semibold" onclick="document.getElementById('reply-form-{{ $m->id }}').classList.toggle('hidden')">{{ $m->admin_reply ? 'Tekrar Cevapla' : 'Cevapla' }}</button>
      </div>

      <form id="reply-form-{{ $m->id }}" method="POST" action="{{ route('admin.contact-messages.reply', $m) }}" class="hidden mt-3 flex gap-2">
        @csrf
        <textarea name="body" rows="3" required placeholder="Cevabınızı buraya yazın..." class="flex-1 border rounded-lg px-3 py-2 text-sm"></textarea>
        <button class="bg-gray-900 text-white rounded-lg px-4 py-2 text-sm font-bold self-start">Gönder</button>
      </form>
    </div>
  @empty
    <p class="text-gray-400">Kayıt yok.</p>
  @endforelse
</div>
<div class="mt-6">{{ $messages->links() }}</div>
@endsection
