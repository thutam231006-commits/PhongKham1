{{--
    Thẻ chỉ số dùng chung cho Dashboard/Báo cáo quản trị.

    Props:
      label    — tên chỉ số
      value    — giá trị đã format sẵn (chuỗi) hoặc số
      unit     — đơn vị hiển thị sau giá trị (mặc định rỗng)
      hint     — dòng mô tả/khung thời gian
      trend    — % thay đổi so với kỳ trước (null = không so sánh được)
      trendGood — xu hướng tăng là tốt (mặc định true)
      tone     — blue | emerald | amber | red | slate
      href     — nếu có, cả thẻ trở thành liên kết
      icon     — đường dẫn SVG cho icon
--}}
@props([
    'label' => '',
    'value' => 0,
    'unit' => '',
    'hint' => null,
    'trend' => null,
    'trendGood' => true,
    'tone' => 'blue',
    'href' => null,
    'icon' => null,
])

@php
    $tones = [
        'blue' => ['accent' => '#2D82C4', 'soft' => '#EAF4FB', 'text' => '#1B5E91'],
        'emerald' => ['accent' => '#16A34A', 'soft' => '#ECFDF3', 'text' => '#15803D'],
        'amber' => ['accent' => '#D97706', 'soft' => '#FFF7E6', 'text' => '#B45309'],
        'red' => ['accent' => '#DC2626', 'soft' => '#FEF2F2', 'text' => '#B91C1C'],
        'slate' => ['accent' => '#64748B', 'soft' => '#F1F5F9', 'text' => '#475569'],
    ];
    $toneStyle = $tones[$tone] ?? $tones['blue'];

    $isPositive = $trend !== null && $trend > 0;
    $isNeutral = $trend === null || $trend == 0;
    $goodForBusiness = $isNeutral ? null : ($isPositive === $trendGood);
    $trendClass = $isNeutral
        ? 'bg-slate-100 text-slate-600'
        : ($goodForBusiness ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700');

    $tag = $href ? 'a' : 'div';
@endphp

<{{ $tag }}
    @if($href) href="{{ $href }}" @endif
    {{ $attributes->merge([
        'class' => 'group relative block overflow-hidden rounded-2xl border border-[#D7E8F3] bg-white p-5 shadow-[0_4px_18px_rgb(35_111_168/0.05)] transition'
            .($href ? ' hover:-translate-y-0.5 hover:border-[#B8D2E4] hover:shadow-[0_12px_28px_rgb(35_111_168/0.12)]' : ''),
    ]) }}>

    <span class="absolute inset-x-0 top-0 h-1" style="background-color: {{ $toneStyle['accent'] }}" aria-hidden="true"></span>

    <div class="flex items-start justify-between gap-3">
        <p class="text-[11px] font-extrabold uppercase tracking-[0.1em] text-[#78889B]">{{ $label }}</p>
        @if($icon)
            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl"
                  style="background-color: {{ $toneStyle['soft'] }}; color: {{ $toneStyle['text'] }}" aria-hidden="true">
                <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9">
                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}"/>
                </svg>
            </span>
        @endif
    </div>

    <p class="mt-3 flex flex-wrap items-baseline gap-x-1.5 text-[1.75rem] font-black leading-none tabular-nums text-[#102A43]">
        {{ $value }}
        @if($unit)
            <span class="text-xs font-bold text-[#78889B]">{{ $unit }}</span>
        @endif
    </p>

    <div class="mt-2.5 flex flex-wrap items-center gap-2">
        @if($trend !== null)
            <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[11px] font-extrabold {{ $trendClass }}">
                <span aria-hidden="true">{{ $isPositive ? '▲' : ($trend == 0 ? '■' : '▼') }}</span>
                {{ number_format(abs((float) $trend), 1, ',', '.') }}%
                <span class="font-semibold opacity-80">so với kỳ trước</span>
            </span>
        @endif
        @if($hint)
            <span class="text-[11px] font-semibold text-[#8A9BB0]">{{ $hint }}</span>
        @endif
    </div>
</{{ $tag }}>
