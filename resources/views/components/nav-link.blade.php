@props(['href' => '#'])

@php
    $isActive = request()->url() === $href || request()->is(ltrim(parse_url($href, PHP_URL_PATH), '/') . '*');
@endphp

<a href="{{ $href }}"
   {{ $attributes->merge([
       'class' => 'rounded-lg px-4 py-2.5 text-sm font-semibold transition focus:outline-none focus:ring-2 focus:ring-white '
           . ($isActive
               ? 'bg-[#23689C] text-white'
               : 'text-white/90 hover:bg-[#23689C] hover:text-white')
   ]) }}
   @if ($isActive) aria-current="page" @endif>
    {{ $slot }}
</a>
