@extends('admin.layout')
@section('title', 'Hatalar')

@section('content')
<div class="flex items-center gap-3 mb-6">
  <h1 class="text-2xl font-bold">Hatalar</h1>
  @if($openCount > 0)
    <span class="bg-red-100 text-red-700 text-xs font-semibold px-2 py-1 rounded-full">{{ $openCount }} çözülmemiş</span>
  @endif
</div>

<form method="GET" class="mb-4 flex gap-3">
  <select name="status" onchange="this.form.submit()" class="border rounded-lg px-3 py-2 text-sm">
    <option value="open" @selected(request('status','open')==='open')>Çözülmemiş</option>
    <option value="resolved" @selected(request('status')==='resolved')>Çözülmüş</option>
    <option value="all" @selected(request('status')==='all')>Tümü</option>
  </select>
</form>

<div class="space-y-3">
  @forelse($platformErrors as $error)
    <div class="bg-white rounded-xl shadow-sm p-5 {{ $error->resolved_at ? 'opacity-60' : '' }}">
      <div class="flex items-start justify-between gap-4">
        <div>
          <div class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">{{ $error->source }} · {{ $error->created_at->format('d.m.Y H:i') }}</div>
          <div class="font-bold text-gray-900">{{ $error->title }}</div>
        </div>
        <div class="flex gap-2 shrink-0">
          @unless($error->resolved_at)
            <form method="POST" action="{{ route('admin.platform-errors.resolve', $error) }}">
              @csrf
              <button class="bg-green-600 text-white px-3 py-1.5 rounded-lg text-xs font-semibold">Çözüldü İşaretle</button>
            </form>
          @endunless
          <form method="POST" action="{{ route('admin.platform-errors.destroy', $error) }}" onsubmit="return confirm('Silinsin mi?');">
            @csrf @method('DELETE')
            <button class="bg-gray-200 text-gray-700 px-3 py-1.5 rounded-lg text-xs font-semibold">Sil</button>
          </form>
        </div>
      </div>
      <pre class="mt-3 bg-gray-50 border border-gray-100 rounded-lg p-3 text-xs text-gray-600 whitespace-pre-wrap font-mono">{{ $error->message }}</pre>
    </div>
  @empty
    <div class="bg-white rounded-xl shadow-sm p-8 text-center text-gray-400">Kayıt yok.</div>
  @endforelse
</div>

<div class="mt-4">{{ $platformErrors->links() }}</div>
@endsection
