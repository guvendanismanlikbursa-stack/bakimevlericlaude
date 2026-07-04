@extends('layouts.brand')

@section('content')
<div class="max-w-2xl mx-auto px-4 py-12">
  <h1 class="text-2xl font-bold mb-6">İletişim</h1>
  <form method="POST" action="{{ brand_route('contact.store') }}" class="bg-white p-6 rounded-xl shadow-sm space-y-4">
    @csrf
    <input type="text" name="name" value="{{ old('name') }}" placeholder="Ad Soyad" required class="border rounded-lg px-3 py-2 w-full">
    <input type="email" name="email" value="{{ old('email') }}" placeholder="E-posta" required class="border rounded-lg px-3 py-2 w-full">
    <input type="text" name="subject" value="{{ old('subject') }}" placeholder="Konu" class="border rounded-lg px-3 py-2 w-full">
    <textarea name="message" rows="5" placeholder="Mesajınız" required class="border rounded-lg px-3 py-2 w-full">{{ old('message') }}</textarea>
    <button class="btn-primary px-6 py-2 rounded-lg font-semibold">Gönder</button>
  </form>
</div>
@endsection
