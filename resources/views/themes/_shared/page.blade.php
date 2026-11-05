@extends('layouts.brand')
@section('title', $page->title)
@section('meta_description', \Illuminate\Support\Str::limit(strip_tags($page->summary ?? $page->body), 155).' | '.current_brand()['name'])
{{-- 5 Kasım 2026: paylaşımlarda (WhatsApp, Facebook) yazının kendi kapağı görünsün. --}}
@if(($page->type ?? null) === 'guide' && ! empty($page->cover_path))
@section('og_image', asset($page->cover_path))
@endif
{{-- 3 Kasım 2026: kullanıcının talebi (maksimum görünürlük) - rehber
     yazıları Google'a "makale" olarak bildirilir (başlık, güncelleme tarihi,
     yayıncı). Yalnızca veritabanındaki gerçek rehber yazılarında. --}}
@if(($page->type ?? null) === 'guide' && ! empty($page->updated_at))
@section('breadcrumb_jsonld')
<script type="application/ld+json">{!! json_encode(array_filter([
  '@context' => 'https://schema.org',
  '@type' => 'Article',
  'headline' => \Illuminate\Support\Str::limit($page->title, 110, ''),
  'description' => \Illuminate\Support\Str::limit(strip_tags($page->summary ?? ''), 200) ?: null,
  'datePublished' => optional($page->published_at ?? $page->created_at)->toIso8601String(),
  'image' => ! empty($page->cover_path) ? asset($page->cover_path) : null,
  'dateModified' => $page->updated_at->toIso8601String(),
  'mainEntityOfPage' => url()->current(),
  'inLanguage' => 'tr-TR',
  'author' => ['@type' => 'Organization', 'name' => current_brand()['name'], 'url' => brand_route('home')],
  'publisher' => ['@type' => 'Organization', 'name' => current_brand()['name'], 'url' => brand_route('home')],
]), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
{{-- 5 Kasım 2026: Google sonuçlarında "Ana sayfa › Bakım Rehberi › Konu" yolu. --}}
@include('themes._shared.partials.breadcrumb-jsonld', ['items' => array_values(array_filter([
  ['name' => 'Ana sayfa', 'url' => brand_route('home')],
  ['name' => 'Bakım Rehberi', 'url' => brand_route('guides.index')],
  $page->kategoriAdi() ? ['name' => $page->kategoriAdi(), 'url' => brand_route('guides.index', ['kategori' => $page->category])] : null,
  ['name' => $page->title, 'url' => url()->current()],
]))])
@endsection
@endif

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
    @if(($page->type ?? null) === 'guide' && method_exists($page, 'kategoriAdi') && $page->kategoriAdi())
      <a href="{{ brand_route('guides.index', ['kategori' => $page->category]) }}" style="display:inline-block;margin-top:16px;font-size:13px;font-weight:800;color:var(--primary);text-decoration:none;">{{ $page->kategoriAdi() }}</a>
    @endif
    <h1 class="text-3xl md:text-4xl font-black text-gray-950 mt-4 mb-3">{{ $page->title }}</h1>
    <p class="text-gray-500">{{ $brand['name'] }} bilgi merkezi @if(($page->type ?? null) === 'guide' && ! empty($page->updated_at))· Son güncelleme: {{ $page->updated_at->translatedFormat('j F Y') }}@endif</p>
  </div>
</section>

<section class="max-w-4xl mx-auto px-4 py-10">
  @if(! empty($page->cover_path))
    {{-- 5 Kasım 2026: rehber yazısının kapak görseli --}}
    <img src="{{ $page->kapakUrl() }}" alt="{{ $page->title }}" width="960" height="540" style="display:block;width:100%;aspect-ratio:16/9;object-fit:cover;border-radius:12px;margin-bottom:20px;background:#f3f4f6;">
  @endif
  <article class="bg-white border border-gray-100 rounded-xl shadow-sm p-6 md:p-8 leading-relaxed text-gray-700 page-content">
    {!! $page->body !!}
  </article>

  @if(isset($ilgili) && $ilgili->isNotEmpty())
    {{-- 5 Kasım 2026: aynı konudaki diğer rehber yazıları --}}
    <div class="mt-8">
      <div class="flex items-end justify-between gap-4 mb-4">
        <h2 class="text-2xl font-black text-gray-950">Aynı konudaki diğer yazılar</h2>
        <a href="{{ brand_route('guides.index', ['kategori' => $page->category]) }}" class="text-sm font-black text-primary">Tümü →</a>
      </div>
      <div class="grid sm:grid-cols-2 gap-3">
        @foreach($ilgili as $diger)
          <a href="{{ brand_route('pages.show', ['slug' => $diger->slug]) }}" class="rounded-xl border border-gray-100 bg-white shadow-sm hover:shadow-md transition" style="display:flex;gap:12px;align-items:center;padding:10px;">
            @if($diger->cover_path)<img src="{{ $diger->kapakUrl() }}" alt="" loading="lazy" width="96" height="54" style="width:96px;height:54px;object-fit:cover;border-radius:8px;flex-shrink:0;">@endif
            <span class="font-black text-gray-950" style="font-size:14px;line-height:1.35;">{{ $diger->title }}</span>
          </a>
        @endforeach
      </div>
    </div>
  @endif

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
  .page-content h3{font-size:1.05rem;font-weight:800;color:#111827;margin-top:1.25rem;margin-bottom:.4rem;}
  .page-content ol{list-style:decimal;padding-left:1.25rem;margin:1rem 0;}
  .page-content table{width:100%;border-collapse:collapse;margin:1rem 0;font-size:.95rem;}
  .page-content th,.page-content td{border:1px solid #e5e7eb;padding:.5rem .65rem;text-align:left;vertical-align:top;}
  .page-content th{background:#f9fafb;font-weight:800;color:#111827;}
  .page-content .not{background:#fffbeb;border:1px solid #fde68a;border-radius:.75rem;padding:.75rem 1rem;margin:1rem 0;font-size:.92rem;}
  .page-content a{color:var(--primary);font-weight:700;text-decoration:underline;}
  .page-content strong{color:#111827;}
</style>
@endsection