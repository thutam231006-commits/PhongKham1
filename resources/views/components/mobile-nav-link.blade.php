@props(['href' => '#'])

@php
    $isActive = request()->url() === $href || request()->is(ltrim(parse_url($href, PHP_URL_PATH), '/') . '*');
@endphp

<a href="{{ $href }}"
   {{ $attributes->merge([
       'class' => 'block rounded-md px-3 py-2 text-sm font-medium transition focus:outline-none focus:ring-2 focus:ring-white '
           . ($isActive
               ? 'bg-[#14352F] text-white'
               : 'text-white/80 hover:bg-[#14352F] hover:text-white')
   ]) }}
   @if ($isActive) aria-current="page" @endif>
    {{ $slot }}
</a>
