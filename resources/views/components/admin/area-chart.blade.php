{{--
    Biểu đồ vùng nhiều chuỗi — dựng bằng SVG thuần, không cần thư viện JS.

    Props:
      labels   — nhãn trục X (mảng chuỗi), ví dụ ngày dạng d/m
      series   — [['name' => 'Tiền thực thu', 'color' => '#2D82C4', 'values' => [..]], ...]
      height   — chiều cao vùng vẽ (px)
      format   — 'currency' | 'number'
      suffix   — đơn vị hiển thị trong tooltip
--}}
@props([
    'labels' => [],
    'series' => [],
    'height' => 240,
    'format' => 'currency',
    'emptyMessage' => 'Chưa có dữ liệu trong kỳ đã chọn.',
])

@php
    $labels = array_values($labels);
    $pointCount = count($labels);

    $allValues = collect($series)->flatMap(fn (array $line): array => array_map('floatval', $line['values'] ?? []));
    $maxValue = (float) ($allValues->max() ?? 0);
    $minValue = min(0.0, (float) ($allValues->min() ?? 0));
    // Mốc trên luôn làm tròn lên để nhãn trục Y là số dễ đọc.
    $maxValue = $maxValue > 0 ? $maxValue * 1.12 : 1.0;

    $width = 1000;
    $paddingLeft = 8;
    $paddingRight = 8;
    $plotWidth = $width - $paddingLeft - $paddingRight;
    $plotHeight = $height;
    $range = max(0.0001, $maxValue - $minValue);

    $xAt = fn (int $index): float => $pointCount <= 1
        ? $paddingLeft + $plotWidth / 2
        : $paddingLeft + ($plotWidth * $index) / ($pointCount - 1);
    $yAt = fn (float $value): float => $plotHeight - (($value - $minValue) / $range) * $plotHeight;

    $formatValue = fn (float $value): string => $format === 'currency'
        ? number_format($value, 0, ',', '.').' đ'
        : number_format($value);

    // Nhãn trục X: giới hạn số nhãn để không chồng chữ.
    $labelStep = $pointCount > 14 ? (int) ceil($pointCount / 10) : 1;

    $seriesTotal = collect($series)->map(fn (array $line): float => array_sum(array_map('floatval', $line['values'] ?? [])))->sum();
@endphp

<div {{ $attributes->merge(['class' => 'w-full']) }}>
    @if ($pointCount === 0 || $seriesTotal <= 0)
        <p class="rounded-xl border border-dashed border-[#D7E8F3] bg-[#F7FBFE] px-4 py-10 text-center text-sm text-[#718096]">
            {{ $emptyMessage }}
        </p>
    @else
        {{-- Chú giải --}}
        <div class="mb-3 flex flex-wrap items-center gap-x-5 gap-y-2">
            @foreach ($series as $line)
                <span class="inline-flex items-center gap-2 text-xs font-semibold text-[#52637A]">
                    <span class="h-2.5 w-2.5 rounded-full" style="background-color: {{ $line['color'] }}" aria-hidden="true"></span>
                    {{ $line['name'] }}
                    <span class="font-bold text-[#102A43]">{{ $formatValue(array_sum(array_map('floatval', $line['values'] ?? []))) }}</span>
                </span>
            @endforeach
        </div>

        <div class="relative">
            <svg viewBox="0 0 {{ $width }} {{ $plotHeight + 30 }}"
                 class="h-auto w-full"
                 preserveAspectRatio="none"
                 role="img"
                 aria-label="Biểu đồ {{ implode(', ', array_column($series, 'name')) }} theo thời gian">
                <defs>
                    @foreach ($series as $seriesIndex => $line)
                        <linearGradient id="area-gradient-{{ $seriesIndex }}-{{ $attributes->get('id', 'chart') }}" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%" stop-color="{{ $line['color'] }}" stop-opacity="0.30"/>
                            <stop offset="100%" stop-color="{{ $line['color'] }}" stop-opacity="0.02"/>
                        </linearGradient>
                    @endforeach
                </defs>

                {{-- Đường lưới ngang --}}
                @foreach ([0, 0.25, 0.5, 0.75, 1] as $gridRatio)
                    @php $gridY = $plotHeight * $gridRatio; @endphp
                    <line x1="{{ $paddingLeft }}" y1="{{ $gridY }}" x2="{{ $width - $paddingRight }}" y2="{{ $gridY }}"
                          stroke="#E4EFF7" stroke-width="1" stroke-dasharray="{{ $gridRatio === 1.0 ? '0' : '4 6' }}"/>
                @endforeach

                @foreach ($series as $seriesIndex => $line)
                    @php
                        $values = array_map('floatval', $line['values'] ?? []);
                        $points = [];
                        foreach ($values as $index => $value) {
                            $points[] = round($xAt($index), 2).','.round($yAt($value), 2);
                        }
                        $linePath = implode(' ', $points);
                        $areaPath = 'M '.$paddingLeft.','.$plotHeight
                            .' L '.implode(' L ', $points)
                            .' L '.round($xAt($pointCount - 1), 2).','.$plotHeight.' Z';
                    @endphp

                    <path d="{{ $areaPath }}" fill="url(#area-gradient-{{ $seriesIndex }}-{{ $attributes->get('id', 'chart') }})"/>
                    <polyline points="{{ $linePath }}" fill="none"
                              stroke="{{ $line['color'] }}" stroke-width="2.5"
                              stroke-linejoin="round" stroke-linecap="round"/>

                    {{-- Điểm dữ liệu — chỉ vẽ khi số điểm ít để không rối --}}
                    @if ($pointCount <= 20)
                        @foreach ($values as $index => $value)
                            <circle cx="{{ round($xAt($index), 2) }}" cy="{{ round($yAt($value), 2) }}" r="3.2"
                                    fill="#ffffff" stroke="{{ $line['color'] }}" stroke-width="2">
                                <title>{{ $labels[$index] ?? '' }} — {{ $line['name'] }}: {{ $formatValue($value) }}</title>
                            </circle>
                        @endforeach
                    @endif
                @endforeach
            </svg>

            {{-- Nhãn trục X --}}
            <div class="mt-1 flex justify-between gap-1 overflow-hidden text-[10px] font-semibold text-[#8A9BB0]">
                @foreach ($labels as $index => $label)
                    <span class="{{ $index % $labelStep === 0 || $index === $pointCount - 1 ? '' : 'invisible' }} whitespace-nowrap">
                        {{ $label }}
                    </span>
                @endforeach
            </div>
        </div>

        {{-- Bảng số liệu thu gọn cho trình đọc màn hình / khi cần đối chiếu số --}}
        <details class="mt-3 group">
            <summary class="cursor-pointer text-xs font-bold text-[#2D82C4] hover:underline">
                Xem số liệu chi tiết
            </summary>
            <div class="mt-2 max-h-56 overflow-auto rounded-lg border border-[#E4EFF7]">
                <table class="min-w-full text-left text-xs">
                    <thead class="sticky top-0 bg-[#EDF6FC] text-[#49647D]">
                        <tr>
                            <th class="px-3 py-2 font-bold">Thời điểm</th>
                            @foreach ($series as $line)
                                <th class="px-3 py-2 text-right font-bold">{{ $line['name'] }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#EEF4F8]">
                        @foreach ($labels as $index => $label)
                            <tr>
                                <td class="whitespace-nowrap px-3 py-1.5 font-semibold text-[#52637A]">{{ $label }}</td>
                                @foreach ($series as $line)
                                    <td class="whitespace-nowrap px-3 py-1.5 text-right tabular-nums text-[#102A43]">
                                        {{ $formatValue((float) (($line['values'] ?? [])[$index] ?? 0)) }}
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </details>
    @endif
</div>
