@extends('layouts.brand')
@section('title', $page->title)
@section('meta_description', \Illuminate\Support\Str::limit(strip_tags($page->summary ?? $page->body), 155).' | '.current_brand()['name'])

@section('content')
@php
  $brand = current_brand();
  $relatedSectionSlug = null;
  foreach (array_keys(service_sections()) as $candidateSectionSlug) {
      if (str_starts_with($page->slug ?? '', $candidateSectionSlug . '-')) {
          $relatedSectionSlug = $candidateSectionSlug;
          break;
      }
  }
  $relatedContent = $relatedSectionSlug ? site_section_content($brand['slug'], $relatedSectionSlug) : [];
  $relatedArticles = collect($relatedContent['articles'] ?? [])->reject(fn ($article) => ($article['slug'] ?? null) === ($page->slug ?? null))->values();
@endphp
<section class="bg-white border-b border-gray-100">
  <div class="max-w-4xl mx-auto px-4 py-10">
    <a href="{{ brand_route('home', $relatedSectionSlug ? ['bolum' => $relatedSectionSlug] : []) }}" class="text-sm font-semibold text-primary">← Ana sayfaya dön</a>
    <h1 class="text-3xl md:text-4xl font-black text-gray-950 mt-4 mb-3">{{ $page->title }}</h1>
    <p class="text-gray-500">{{ $brand['name'] }} bilgi merkezi</p>
  </div>
</section>

<section class="max-w-4xl mx-auto px-4 py-10">
  <article class="bg-white border border-gray-100 rounded-xl shadow-sm p-6 md:p-8 leading-relaxed text-gray-700 page-content">
    {!! $page->body !!}
  </article>

  @if($relatedArticles->isNotEmpty())
    <div class="mt-8">
      <div class="flex items-end justify-between gap-4 mb-4">
        <div>
          <div class="text-sm font-black text-primary mb-1">Aynı bölümden diğer yazılar</div>
          <h2 class="text-2xl font-black text-gray-950">Bilgilendirme sayfaları</h2>
        </div>
        <a href="{{ brand_route('home', ['bolum' => $relatedSectionSlug]) }}" class="text-sm font-black text-primary">Bölüme dön →</a>
      </div>
      <div class="grid sm:grid-cols-2 gap-3">
        @foreach($relatedArticles as $article)
          <a href="{{ brand_route('pages.show', ['slug' => $article['slug']]) }}" class="rounded-xl border border-gray-100 bg-white p-4 shadow-sm hover:shadow-md transition">
            <div class="font-black text-gray-950">{{ $article['title'] }}</div>
            <p class="text-sm text-gray-500 mt-1">{{ $article['summary'] }}</p>
          </a>
        @endforeach
      </div>
    </div>
  @endif

  <div class="mt-8 grid sm:grid-cols-2 gap-3">
    <a href="{{ brand_route('facilities.index', $relatedSectionSlug ? ['bolum' => $relatedSectionSlug] : []) }}" class="rounded-xl border border-gray-100 bg-white p-4 shadow-sm hover:shadow-md transition">
      <div class="font-black text-gray-950">Kurumları karşılaştır</div>
      <p class="text-sm text-gray-500 mt-1">İl, ilçe, kurum türü ve hizmet filtresiyle seçenekleri listeleyin.</p>
    </a>
    <a href="{{ brand_route('contact.create') }}" class="rounded-xl border border-gray-100 bg-white p-4 shadow-sm hover:shadow-md transition">
      <div class="font-black text-gray-950">Soru gönder</div>
      <p class="text-sm text-gray-500 mt-1">Aklınızdaki soruyu iletin, platform ekibi dönüş yapsın.</p>
    </a>
  </div>
</section>

<style>
  .page-content h2{font-size:1.25rem;font-weight:900;color:#111827;margin-top:1.75rem;margin-bottom:.6rem;}
  .page-content p{margin:.85rem 0;}
  .page-content ul{list-style:disc;padding-left:1.25rem;margin:1rem 0;}
  .page-content li{margin:.35rem 0;}
  .page-content strong{color:#111827;}
</style>
@endsection