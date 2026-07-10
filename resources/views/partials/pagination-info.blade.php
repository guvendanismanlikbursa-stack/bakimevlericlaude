@if($paginator->lastPage() > 1)
  <div class="text-xs font-semibold text-gray-500 mb-2">
    Sayfa {{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}
    <span class="text-gray-400 font-normal">&middot; {{ number_format($paginator->total(), 0, ',', '.') }} kurumdan {{ number_format($paginator->firstItem(), 0, ',', '.') }}-{{ number_format($paginator->lastItem(), 0, ',', '.') }} arası gösteriliyor</span>
  </div>
@endif
