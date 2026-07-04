@extends('layouts.brand')
@section('title', 'Bakım Danışmanı - Size Uygun Kurumları Bulalım')
@section('meta_description', 'Hasta yaşı, durumu, şehir ve bütçe bilginizi paylaşın; size uygun kurumları puanlayıp listeleyelim.')
@section('content')
<div class="max-w-2xl mx-auto px-4 py-12">
  <div class="text-center mb-8">
    <div class="inline-flex items-center gap-2 rounded-full px-4 py-2 text-sm font-black mb-4" style="background: {{ $activeSection['theme']['soft'] }}; color: {{ $activeSection['theme']['primary'] }};">🧭 Bakım Danışmanı</div>
    <h1 class="text-3xl md:text-4xl font-black text-gray-950 mb-3">Size uygun kurumu birlikte bulalım</h1>
    <p class="text-gray-600">Birkaç soruyu yanıtlayın, ihtiyacınıza en uygun kurumları puanlayıp sıralayalım.</p>
  </div>

  <form method="GET" action="{{ brand_route('care-advisor.results') }}" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 space-y-5">
    <input type="hidden" name="bolum" value="{{ $activeSection['slug'] }}">

    <div class="grid sm:grid-cols-2 gap-2">
      @foreach($sections as $slug => $section)
        @php $active = $activeSection['slug'] === $slug; @endphp
        <a href="{{ brand_route('care-advisor.form', ['bolum' => $slug]) }}" class="rounded-xl border px-4 py-3 text-sm font-black text-center {{ $active ? 'text-white' : 'bg-white text-gray-700 border-gray-200' }}" style="{{ $active ? 'background: '.$section['theme']['primary'].'; border-color: '.$section['theme']['primary'].';' : '' }}">{{ $section['title'] }}</a>
      @endforeach
    </div>

    <div class="grid sm:grid-cols-2 gap-4">
      <label class="block"><span class="text-xs font-black text-gray-500">Hasta/kullanıcı yaşı</span>
        <input type="number" name="patient_age" min="0" max="120" placeholder="Örn: 78" class="mt-1 w-full border border-gray-200 rounded-xl px-3 py-3">
      </label>
      <label class="block"><span class="text-xs font-black text-gray-500">Cinsiyet</span>
        <select name="gender" class="mt-1 w-full border border-gray-200 rounded-xl px-3 py-3 bg-white">
          <option value="">Belirtmek istemiyorum</option>
          <option value="kadın">Kadın</option>
          <option value="erkek">Erkek</option>
        </select>
      </label>
    </div>

    <label class="block"><span class="text-xs font-black text-gray-500">Durum / hastalık (varsa)</span>
      <input type="text" name="condition" placeholder="Örn: Parkinson, diyabet, otizm..." class="mt-1 w-full border border-gray-200 rounded-xl px-3 py-3">
    </label>

    <div class="grid sm:grid-cols-3 gap-3">
      <label class="flex items-center gap-2 text-sm font-semibold bg-gray-50 rounded-xl px-3 py-3"><input type="checkbox" name="has_dementia" value="1"> Demans/Alzheimer var</label>
      <label class="flex items-center gap-2 text-sm font-semibold bg-gray-50 rounded-xl px-3 py-3"><input type="checkbox" name="is_bedridden" value="1"> Yatalak</label>
      <label class="flex items-center gap-2 text-sm font-semibold bg-gray-50 rounded-xl px-3 py-3"><input type="checkbox" name="needs_physio" value="1"> Fizik tedavi gerekiyor</label>
    </div>

    <div class="grid sm:grid-cols-3 gap-4">
      <label class="block"><span class="text-xs font-black text-gray-500">İl</span>
        <select name="city" class="mt-1 w-full border border-gray-200 rounded-xl px-3 py-3 bg-white"><option value="">Farketmez</option>@foreach($cities as $city)<option value="{{ $city->slug }}">{{ $city->name }}</option>@endforeach</select>
      </label>
      <label class="block"><span class="text-xs font-black text-gray-500">Kurum türü</span>
        <select name="category" class="mt-1 w-full border border-gray-200 rounded-xl px-3 py-3 bg-white"><option value="">Farketmez</option>@foreach($categories as $category)<option value="{{ $category->slug }}">{{ $category->name }}</option>@endforeach</select>
      </label>
      <label class="block"><span class="text-xs font-black text-gray-500">Aylık bütçe (üst sınır)</span>
        <select name="budget_max" class="mt-1 w-full border border-gray-200 rounded-xl px-3 py-3 bg-white">
          <option value="">Belirtmek istemiyorum</option>
          <option value="15000">15.000 TL</option>
          <option value="30000">30.000 TL</option>
          <option value="50000">50.000 TL</option>
          <option value="100000">100.000 TL</option>
        </select>
      </label>
    </div>

    <button class="w-full rounded-xl text-white font-black px-5 py-4 text-lg" style="background: {{ $activeSection['theme']['primary'] }};">Uygun Kurumları Bul</button>
    <p class="text-xs text-gray-400 text-center">Bu sonuçlar, girdiğiniz bilgilerle kurum profillerindeki hizmet bilgilerinin eşleşmesine göre puanlanır; kesin tıbbi/idari bir değerlendirme değildir.</p>
  </form>
</div>
@endsection
