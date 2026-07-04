@extends('layouts.brand')

@section('content')
<div class="max-w-md mx-auto px-4 py-12">
  <h1 class="text-2xl font-bold mb-2">Şifrenizi Belirleyin</h1>
  <p class="text-gray-500 text-sm mb-6">Güvenlik için ilk girişte size e-posta ile gönderilen geçici şifreyi değiştirmeniz gerekiyor.</p>
  <form method="POST" action="{{ brand_route('facility.password.update') }}" class="bg-white p-6 rounded-xl shadow-sm space-y-4">
    @csrf
    <input type="password" name="password" placeholder="Yeni Şifre" required class="border rounded-lg px-3 py-2 w-full">
    <input type="password" name="password_confirmation" placeholder="Yeni Şifre (tekrar)" required class="border rounded-lg px-3 py-2 w-full">
    <button class="btn-primary w-full py-2 rounded-lg font-semibold">Şifreyi Kaydet</button>
  </form>
</div>
@endsection
