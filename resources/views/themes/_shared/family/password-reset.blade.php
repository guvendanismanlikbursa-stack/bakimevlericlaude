@extends('layouts.brand')

@section('content')
<div class="max-w-md mx-auto px-4 py-12">
  <h1 class="text-2xl font-bold mb-2">Yeni Şifre Belirle</h1>
  <p class="text-gray-500 text-sm mb-6">{{ $family->email }} hesabı için yeni bir şifre belirleyin.</p>
  <form method="POST" action="{{ url()->full() }}" class="bg-white p-6 rounded-xl shadow-sm space-y-4">
    @csrf
    <input type="password" name="password" placeholder="Yeni Şifre" required minlength="8" class="border rounded-lg px-3 py-2 w-full">
    <input type="password" name="password_confirmation" placeholder="Yeni Şifre (tekrar)" required minlength="8" class="border rounded-lg px-3 py-2 w-full">
    <button class="btn-primary w-full py-2 rounded-lg font-semibold">Şifremi Güncelle</button>
  </form>
</div>
@endsection
