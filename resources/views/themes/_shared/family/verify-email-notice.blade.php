@extends('layouts.brand')

@section('content')
<div class="max-w-3xl mx-auto px-4 py-16">
  <div class="bg-white rounded-3xl shadow-lg border border-gray-200 p-8">
    <div class="text-center">
      <h1 class="text-3xl font-black text-gray-900">E-posta Doğrulaması Gerekiyor</h1>
      <p class="mt-4 text-gray-600">Hesabınızı kullanmaya devam edebilmek için lütfen kayıtlı e-posta adresinize gönderilen doğrulama bağlantısını tıklayın.</p>
    </div>

    <div class="mt-8 grid gap-6">
      <div class="rounded-2xl border border-yellow-200 bg-yellow-50 p-6">
        <h2 class="font-bold text-lg text-yellow-800">E-posta adresi: {{ $family->email }}</h2>
        <p class="mt-2 text-sm text-yellow-700">Doğrulama e-postası gelmedi mi? Aşağıdaki düğmeye tıklayarak tekrar gönderebilirsiniz.</p>
      </div>

      <form method="POST" action="{{ brand_route('family.verify-email.resend') }}" class="flex flex-col sm:flex-row gap-3">
        @csrf
        <button type="submit" class="inline-flex items-center justify-center rounded-full bg-primary px-6 py-3 text-sm font-semibold text-white shadow-sm hover:bg-opacity-90">Doğrulama E-postasını Tekrar Gönder</button>
        <a href="{{ brand_route('home') }}" class="inline-flex items-center justify-center rounded-full border border-gray-200 bg-white px-6 py-3 text-sm font-semibold text-gray-700 hover:bg-gray-50">Anasayfaya Dön</a>
      </form>

      @if(session('success'))
      <div class="rounded-2xl border border-green-200 bg-green-50 p-4 text-sm text-green-800">{{ session('success') }}</div>
      @endif
      @if(session('info'))
      <div class="rounded-2xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-800">{{ session('info') }}</div>
      @endif
      @if($errors->any())
      <div class="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-800">
        <ul class="list-disc list-inside">
          @foreach($errors->all() as $error)
          <li>{{ $error }}</li>
          @endforeach
        </ul>
      </div>
      @endif
    </div>
  </div>
</div>
@endsection
