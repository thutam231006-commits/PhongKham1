{{--
    Biểu đồ vành khuyên — dùng cho cơ cấu trạng thái (loại trừ lẫn nhau).

    Props:
      items — [['label' => string, 'value' => int|float, 'color' => '#hex'], ...]
      total — tổng để tính % (mặc định = sum của items)
      centerLabel / centerValue — nội dung giữa vòng
--}}
@props([
    'items' => [],
    'total' => null,
    'centerLabel' => 'Tổng',
    'centerValue' => null,
    'emptyMessage' => 'Chưa có dữ liệu.',
])

@php
    $items = collect($items)
        ->map(fn (array $item): array => [
            'label' => (string) ($item['label'] ?? ''),
            'value' => (float) ($item['value'] ?? 0),
            'color' => (string) ($item['color'] ?? '#94A3B8'),
        ])
        ->filter(fn (array $item): bool => $item['value'] > 0)
        ->values();

    $sum = $total !== null ? (float) $total : (float) $items->sum('value');

    $radius = 56;
    $circumference = 2 * M_PI * $radius;
    $offset = 0.0;
@endphp

@if ($items->isEmpty())
    <p class="rounded-xl border border-dashed border-[#D7E8F3] bg-[#F7FBFE] px-4 py-10 text-center text-sm text-[#718096]">
        {{ $emptyMessage }}
    </p>
@else
    <div class="flex flex-col items-center gap-6 sm:flex-row sm:items-center sm:gap-8">
        <div class="relative shrink-0">
            <svg viewBox="0 0 140 140" class="h-40 w-40 -rotate-90" role="img"
                 aria-label="Biểu đồ cơ cấu: {{ $items->map(fn ($item) => $item['label'].' '.$item['value'])->implode(', ') }}">
                <circle cx="70" cy="70" r="{{ $radius }}" fill="none" stroke="#EEF4F8" stroke-width="18"/>
                @foreach ($items as $item)
                    @php
                        $portion = $sum > 0 ? $item['value'] / $sum : 0;
                        $dash = $portion * $circumference;
                        $gap = $circumference - $dash;
                    @endphp
                    <circle cx="70" cy="70" r="{{ $radius }}" fill="none"
                            stroke="{{ $item['color'] }}" stroke-width="18"
                            stroke-dasharray="{{ round($dash - 2, 2) }} {{ round($gap + 2, 2) }}"
                            stroke-dashoffset="{{ round(-$offset, 2) }}"
                            stroke-linecap="butt">
                        <title>{{ $item['label'] }}: {{ number_format($item['value']) }} ({{ number_format($portion * 100, 1, ',', '.') }}%)</title>
                    </circle>
                    @php $offset += $dash; @endphp
                @endforeach
            </svg>
            <div class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center text-center">
                <span class="text-[10px] font-extrabold uppercase tracking-wide text-[#8A9BB0]">{{ $centerLabel }}</span>
                <span class="text-2xl font-black tabular-nums text-[#102A43]">{{ $centerValue ?? number_format($sum) }}</span>
            </div>
        </div>

        <ul class="w-full min-w-0 space-y-2.5">
            @foreach ($items as $item)
                @php $percent = $sum > 0 ? $item['value'] / $sum * 100 : 0; @endphp
                <li class="flex items-center gap-2.5">
                    <span class="h-2.5 w-2.5 shrink-0 rounded-full" style="background-color: {{ $item['color'] }}" aria-hidden="true"></span>
                    <span class="min-w-0 flex-1 truncate text-sm font-semibold text-[#334E68]">{{ $item['label'] }}</span>
                    <span class="shrink-0 text-sm font-black tabular-nums text-[#102A43]">{{ number_format($item['value']) }}</span>
                    <span class="w-12 shrink-0 text-right text-xs font-bold tabular-nums text-[#8A9BB0]">{{ number_format($percent, 1, ',', '.') }}%</span>
                </li>
            @endforeach
        </ul>
    </div>
@endif
