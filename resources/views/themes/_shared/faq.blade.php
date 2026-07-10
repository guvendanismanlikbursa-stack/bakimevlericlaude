@extends('layouts.brand')
@section('title', 'Sıkça Sorulan Sorular')

@section('content')
<div class="max-w-3xl mx-auto px-4 py-12">
  <h1 class="text-3xl font-black mb-2">Sıkça Sorulan Sorular</h1>
  <p class="text-gray-500 mb-8">{{ $brand['name'] }} hakkında en çok sorulan sorular ve cevapları.</p>

  <div class="space-y-3">
    @forelse($faqs as $faq)
      <details class="bg-white rounded-xl shadow-sm p-5 group">
        <summary class="font-semibold cursor-pointer list-none flex items-center justify-between">
          {{ $faq->question }}
          <span class="text-gray-400 group-open:rotate-45 transition">+</span>
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
