@extends('layouts.admin')

@section('title', 'Nhật ký kiểm toán')

@php
    $entityLabels = [
        'users' => 'Tài khoản',
        'services' => 'Gói dịch vụ',
        'service_categories' => 'Danh mục dịch vụ',
        'specialist_profiles' => 'Hồ sơ chuyên viên',
        'appointments' => 'Lịch hẹn',
        'reviews' => 'Đánh giá',
        'refund_requests' => 'Yêu cầu hoàn tiền',
        'schedule_exceptions' => 'Ngoại lệ lịch',
        'system_settings' => 'Cấu hình hệ thống',
        'roles' => 'Vai trò',
    ];

    $formatSnapshot = function (?array $snapshot): string {
        if (! $snapshot || $snapshot === []) {
            return '—';
        }
        $parts = [];
        foreach ($snapshot as $key => $value) {
            if (is_array($value)) {
                $value = implode(', ', array_map('strval', $value));
            } elseif (is_bool($value)) {
                $value = $value ? 'true' : 'false';
            }
            $parts[] = $key.'='.(string) $value;
        }

        return implode(' · ', $parts);
    };
@endphp

@section('content')
<div class="mx-auto max-w-[1600px] space-y-6 px-4 py-6 sm:px-6 lg:px-8">

    {{-- ═══════════════ HEADER ═══════════════ --}}
    <div>
        <p class="text-[11px] font-extrabold uppercase tracking-[0.16em] text-[#6FA6CF]">Phân hệ bảo mật &amp; audit</p>
        <h1 class="mt-1 text-2xl font-black tracking-tight text-[#102A43] sm:text-3xl">Nhật ký kiểm toán</h1>
        <p class="mt-2 max-w-3xl text-sm leading-relaxed text-[#52637A]">
            Nhật ký chỉ đọc: ghi lại người thao tác, hành động, đối tượng bị tác động, thời điểm, kết quả và lý do.
            Mật khẩu, token xác thực và dữ liệu thanh toán nhạy cảm không bao giờ được ghi vào log.
        </p>
    </div>

    {{-- ═══════════════ HÀNH ĐỘNG PHỔ BIẾN ═══════════════ --}}
    @if($actionCounts->isNotEmpty())
        <section class="rounded-2xl border border-[#D7E8F3] bg-white p-5 shadow-[0_4px_18px_rgb(35_111_168/0.05)]">
            <h2 class="text-base font-black text-[#102A43]">Hành động thường xuyên</h2>
            <p class="mt-1 text-xs text-[#718096]">Nhấn để lọc nhanh theo một loại hành động.</p>

            <ul class="mt-4 flex flex-wrap gap-2">
                @foreach($actionCounts as $action => $total)
                    <li>
                        <a href="{{ route('admin.audit-logs', ['action' => $action]) }}"
                           class="inline-flex items-center gap-2 rounded-lg border border-[#D7E8F3] px-3 py-2 text-xs font-bold text-[#52637A] transition hover:border-[#B8D2E4] hover:bg-[#F3F9FD]">
                            <span class="font-mono text-[11px] text-[#2D82C4]">{{ $action }}</span>
                            <span class="rounded-full bg-[#EAF4FB] px-1.5 py-0.5 text-[10px] font-black text-[#23689C]">{{ number_format($total) }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    {{-- ═══════════════ BỘ LỌC ═══════════════ --}}
    <section class="rounded-2xl border border-[#D7E8F3] bg-white p-4 shadow-[0_4px_18px_rgb(35_111_168/0.05)]">
        <form method="GET" class="grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
            <label class="text-xs font-bold text-[#52637A]">
                Người thao tác
                <select name="actor_id" class="mt-1.5 w-full rounded-lg border border-[#D7E8F3] px-3 py-2.5 text-sm">
                    <option value="">Tất cả</option>
                    @foreach($actors as $actor)
                        <option value="{{ $actor->id }}" @selected((string) request('actor_id') === (string) $actor->id)>
                            {{ $actor->name }} ({{ $actor->role }})
                        </option>
                    @endforeach
                </select>
            </label>

            <label class="text-xs font-bold text-[#52637A]">
                Hành động
                <input name="action" value="{{ request('action') }}" maxlength="60" placeholder="vd: user.updated"
                       class="mt-1.5 w-full rounded-lg border border-[#D7E8F3] px-3 py-2.5 text-sm">
            </label>

            <label class="text-xs font-bold text-[#52637A]">
                Loại đối tượng
                <select name="entity_type" class="mt-1.5 w-full rounded-lg border border-[#D7E8F3] px-3 py-2.5 text-sm">
                    <option value="">Tất cả</option>
                    @foreach($entityLabels as $value => $label)
                        <option value="{{ $value }}" @selected(request('entity_type') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>

            <label class="text-xs font-bold text-[#52637A]">
                Từ ngày
                <input type="date" name="from" value="{{ request('from') }}"
                       class="mt-1.5 w-full rounded-lg border border-[#D7E8F3] px-3 py-2.5 text-sm">
            </label>

            <label class="text-xs font-bold text-[#52637A]">
                Đến ngày
                <input type="date" name="to" value="{{ request('to') }}"
                       class="mt-1.5 w-full rounded-lg border border-[#D7E8F3] px-3 py-2.5 text-sm">
            </label>

            <div class="flex flex-wrap items-center gap-2 sm:col-span-2 xl:col-span-5">
                <button type="submit" class="rounded-lg bg-[#2D82C4] px-4 py-2.5 text-sm font-bold text-white transition hover:bg-[#23689C]">
                    Lọc nhật ký
                </button>
                @if(request()->query())
                    <a href="{{ route('admin.audit-logs') }}" class="rounded-lg border border-[#B8D2E4] px-4 py-2.5 text-sm font-bold text-[#52637A] transition hover:bg-[#F7FBFE]">
                        Xoá bộ lọc
                    </a>
                @endif
                <span class="ml-auto text-xs font-semibold text-[#8A9BB0]">{{ $logs->total() }} sự kiện</span>
            </div>
        </form>
    </section>

    {{-- ═══════════════ DANH SÁCH SỰ KIỆN ═══════════════ --}}
    <section class="overflow-hidden rounded-2xl border border-[#D7E8F3] bg-white shadow-[0_4px_18px_rgb(35_111_168/0.05)]">
        <div class="overflow-x-auto">
            <table class="min-w-[1080px] text-left text-sm">
                <thead class="bg-[#EDF6FC] text-[11px] uppercase tracking-wide text-[#49647D]">
                    <tr>
                        <th class="whitespace-nowrap px-5 py-3 font-bold">Thời điểm</th>
                        <th class="whitespace-nowrap px-5 py-3 font-bold">Người thực hiện</th>
                        <th class="whitespace-nowrap px-5 py-3 font-bold">Hành động</th>
                        <th class="whitespace-nowrap px-5 py-3 font-bold">Đối tượng</th>
                        <th class="whitespace-nowrap px-5 py-3 font-bold">Thay đổi</th>
                        <th class="whitespace-nowrap px-5 py-3 font-bold">IP</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#EEF4F8]">
                    @forelse($logs as $log)
                        <tr class="align-top transition hover:bg-[#F3F9FD]">
                            <td class="whitespace-nowrap px-5 py-4 text-xs font-semibold text-[#52637A]">
                                {{ $log->created_at?->format('d/m/Y H:i:s') }}
                            </td>

                            <td class="px-5 py-4">
                                <p class="whitespace-nowrap font-bold text-[#102A43]">{{ $log->actor?->name ?? 'Hệ thống' }}</p>
                                <p class="whitespace-nowrap text-[10px] font-extrabold uppercase tracking-wide text-[#8A9BB0]">{{ $log->actor_role }}</p>
                            </td>

                            <td class="px-5 py-4">
                                <span class="whitespace-nowrap rounded-md bg-[#EAF4FB] px-2 py-1 font-mono text-[11px] font-bold text-[#23689C]">
                                    {{ $log->action }}
                                </span>
                            </td>

                            <td class="px-5 py-4 whitespace-nowrap text-xs">
                                <span class="font-semibold text-[#334E68]">{{ $entityLabels[$log->entity_type] ?? $log->entity_type }}</span>
                                <span class="ml-1 font-mono text-[#8A9BB0]">#{{ $log->entity_id }}</span>
                            </td>

                            <td class="px-5 py-4 text-xs">
                                @if($log->old_value)
                                    <p class="text-[#8A9BB0]"><span class="font-bold uppercase tracking-wide">Trước:</span> {{ $formatSnapshot($log->old_value) }}</p>
                                @endif
                                @if($log->new_value)
                                    <p class="mt-0.5 text-[#334E68]"><span class="font-bold uppercase tracking-wide text-[#2D82C4]">Sau:</span> {{ $formatSnapshot($log->new_value) }}</p>
                                @endif
                                @if(! $log->old_value && ! $log->new_value)
                                    <span class="text-[#8A9BB0]">—</span>
                                @endif
                            </td>

                            <td class="whitespace-nowrap px-5 py-4 font-mono text-[11px] text-[#8A9BB0]">{{ $log->ip_address ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-16 text-center">
                                <p class="text-sm font-bold text-[#102A43]">Không có sự kiện phù hợp bộ lọc.</p>
                                <p class="mt-1 text-xs text-[#718096]">Thử xoá bộ lọc hoặc chọn khoảng thời gian khác.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($logs->hasPages())
            <div class="border-t border-[#E8F1F6] px-5 py-4">{{ $logs->links() }}</div>
        @endif
    </section>
</div>
@endsection
