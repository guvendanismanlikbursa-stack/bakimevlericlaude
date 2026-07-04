@php $tier = $facility->priceTier(); @endphp
@if($tier)
  <span class="{{ $tier['classes'] }} text-xs font-semibold px-2 py-0.5 rounded-full whitespace-nowrap">{{ $tier['emoji'] }} {{ $tier['label'] }}</span>
@endif
