@extends('layouts.brand')
@section('title', 'Sıkça Sorulan Sorular')

@section('content')
@php $primary = current_brand()['primary_color']; @endphp
<section style="background: linear-gradient(135deg, {{ $primary }}, {{ $primary }}cc);" class="text-white">
  <div class="max-w-3xl mx-auto px-4 py-14 text-center">
    <div class="inline-flex items-center gap-2 rounded-full bg-white/15 border border-white/20 px-4 py-2 text-xs font-black mb-4">Yardım Merkezi</div>
    <h1 class="text-2xl md:text-4xl font-black mb-3">Sıkça Sorulan Sorular</h1>
    <p class="text-white/85">{{ $brand['name'] }} hakkında en çok sorulan sorular ve cevapları.</p>
  </div>
</section>
<div class="max-w-3xl mx-auto px-4 py-12">
  <div class="space-y-3">
    @forelse($faqs as $faq)
      <details class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 group">
        <summary class="font-black text-gray-950 cursor-pointer list-none flex items-center justify-between gap-4">
          {{ $faq->question }}
          <span class="shrink-0 w-7 h-7 rounded-full flex items-center justify-center text-white group-open:rotate-45 transition" style="background: {{ $primary }};">+</span>
        </summary>
        <p class="text-sm text-gray-600 mt-3">{{ $faq->answer }}</p>
      </details>
    @empty
      <p class="text-sm text-gray-400">Henüz eklenmiş bir soru yok.</p>
    @endforelse
  </div>
</div>

@if($faqs->isNotEmpty())
<script type="application/ld+json">
{!! json_encode([
  '@@context' => 'https://schema.org',
  '@type' => 'FAQPage',
  'mainEntity' => $faqs->map(function ($faq) {
      return [
          '@type' => 'Question',
          'name' => $faq->question,
          'acceptedAnswer' => [
              '@type' => 'Answer',
              'text' => $faq->answer,
          ],
      ];
  })->values()->all(),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
</script>
@endif
@endsection
