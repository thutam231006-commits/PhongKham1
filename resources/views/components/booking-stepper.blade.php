@props(['current' => 1])
@php
    $steps = [1 => 'Gói dịch vụ', 2 => 'Chuyên viên', 3 => 'Ngày & giờ', 4 => 'Thông tin', 5 => 'Thanh toán'];
@endphp
<nav aria-label="Tiến trình đặt lịch" class="mb-8 rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
    <ol class="grid grid-cols-5 gap-2">
        @foreach($steps as $number => $label)
            <li class="text-center">
                @if($number <= $current)
                    <a href="{{ $number === 1 ? route('appointments.book') : '#' }}" class="mx-auto flex h-9 w-9 items-center justify-center rounded-full {{ $number === $current ? 'bg-[#2D82C4] text-white ring-4 ring-[#EAF4FB]' : 'bg-[#6FA6CF] text-white' }} text-sm font-black">{{ $number < $current ? '✓' : $number }}</a>
                @else
                    <span class="mx-auto flex h-9 w-9 items-center justify-center rounded-full bg-gray-100 text-sm font-bold text-gray-400">{{ $number }}</span>
                @endif
                <span class="mt-2 block text-[11px] font-bold {{ $number <= $current ? 'text-[#2D82C4]' : 'text-gray-400' }}">{{ $label }}</span>
            </li>
        @endforeach
    </ol>
</nav>
