@php
  $iconKey = $icon ?? ($section['icon'] ?? ($section['slug'] ?? ''));
  $class = $class ?? 'w-6 h-6';
@endphp
<span class="inline-flex items-center justify-center {{ $class }}" aria-hidden="true">
  @switch($iconKey)
    @case('elderly-care')
    @case('yasli-bakim')
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="w-full h-full">
        <path d="M8.5 10.5a3.5 3.5 0 1 0 7 0a3.5 3.5 0 0 0-7 0Z" />
        <path d="M6.8 20a5.6 5.6 0 0 1 10.4 0" />
        <path d="M17.5 9.2c1.7.5 3 2.1 3 4v1.2" />
        <path d="M18.7 14.5l1.8 1.8l1.8-1.8" />
        <path d="M4.5 20v-5.5" />
        <path d="M4.5 14.5h2" />
      </svg>
      @break
    @case('child-care')
    @case('cocuk')
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="w-full h-full">
        <path d="M8 9a4 4 0 0 1 8 0" />
        <path d="M7 10.5h10" />
        <path d="M8.5 10.5V13a3.5 3.5 0 0 0 7 0v-2.5" />
        <path d="M9 18.5c1.8 1.2 4.2 1.2 6 0" />
        <path d="M5 14.5l-2 2" />
        <path d="M19 14.5l2 2" />
        <path d="M10 6.2V4" />
        <path d="M14 6.2V4" />
      </svg>
      @break
    @case('rehab-care')
    @case('rehabilitasyon')
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="w-full h-full">
        <path d="M8 21v-5.5a4 4 0 0 1 8 0V21" />
        <path d="M12 15.5V21" />
        <path d="M7 8.5a5 5 0 0 1 10 0" />
        <path d="M5.5 8.5h13" />
        <path d="M9 3.8L12 2l3 1.8" />
        <path d="M12 5.5v6" />
        <path d="M9 8.5h6" />
      </svg>
      @break
    @default
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="w-full h-full">
        <path d="M4 21V7l8-4l8 4v14" />
        <path d="M9 21v-6h6v6" />
        <path d="M9 10h.01" />
        <path d="M15 10h.01" />
      </svg>
  @endswitch
</span>
