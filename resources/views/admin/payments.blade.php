@extends('layouts.admin')

@section('title', 'Thanh toán & hoàn tiền')

@php
    $statusMap = [
        'PENDING' => ['label' => 'Chờ hoàn tiền', 'tone' => '#B45309', 'soft' => '#FFF7E6', 'border' => '#FDE68A', 'icon' => '⏳'],
        'COMPLETED' => ['label' => 'Đã hoàn tiền', 'tone' => '#15803D', 'soft' => '#ECFDF3', 'border' => '#BBF7D0', 'icon' => '✓'],
        'REJECTED' => ['label' => 'Từ chối', 'tone' => '#B91C1C', 'soft' => '#FEF2F2', 'border' => '#FCA5A5', 'icon' => '✕'],
    ];

    $pending = $refunds->getCollection()->where('status', 'PENDING');
    $pendingTotal = (float) $pending->sum('amount');
    $completedTotal = (float) $refunds->getCollection()->where('status', 'COMPLETED')->sum('amount');
@endphp

@section('content')
<div class="mx-auto max-w-[1600px] space-y-6 px-4 py-6 sm:px-6 lg:px-8">

    {{-- ═══════════════ HEADER ═══════════════ --}}
    <div>
        <p class="text-[11px] font-extrabold uppercase tracking-[0.16em] text-[#6FA6CF]">Phân hệ vận hành &amp; doanh thu</p>
        <h1 class="mt-1 text-2xl font-black tracking-tight text-[#102A43] sm:text-3xl">Thanh toán &amp; hoàn tiền</h1>
        <p class="mt-2 max-w-3xl text-sm leading-relaxed text-[#52637A]">
            Thông tin ngân hàng của khách được mã hoá khi lưu. Chỉ xác nhận <strong class="font-bold text-[#334E68]">sau khi đã chuyển khoản thực tế</strong>
            và nhập đúng mã giao dịch — doanh thu thuần được tính bằng tiền đã thu trừ tiền đã hoàn.
        </p>
    </div>

    {{-- ═══════════════ KPI ═══════════════ --}}
    <div class="grid gap-4 sm:grid-cols-3">
        <x-admin.stat-card label="Chờ hoàn tiền" :value="$pending->count()" unit="yêu cầu"
                           :hint="number_format($pendingTotal, 0, ',', '.').' đ cần chuyển'"
                           :tone="$pending->isNotEmpty() ? 'amber' : 'emerald'"
                           icon="M12 8v4l3 2m6-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
        <x-admin.stat-card label="Đã hoàn trong trang" :value="$refunds->where('status', 'COMPLETED')->count()" unit="yêu cầu"
                           :hint="number_format($completedTotal, 0, ',', '.').' đ đã hoàn'"
                           tone="blue" icon="M5 13l4 4L19 7"/>
        <x-admin.stat-card label="Tổng yêu cầu hoàn" :value="$refunds->total()" unit="yêu cầu"
                           hint="Toàn bộ lịch sử hoàn tiền" tone="slate"
                           icon="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
    </div>

    {{-- ═══════════════ BẢNG HOÀN TIỀN ═══════════════ --}}
    <section class="overflow-hidden rounded-2xl border border-[#D7E8F3] bg-white shadow-[0_4px_18px_rgb(35_111_168/0.05)]">
        <div class="border-b border-[#E8F1F6] px-5 py-4">
            <h2 class="text-base font-black text-[#102A43]">Yêu cầu hoàn tiền</h2>
            <p class="mt-1 text-xs text-[#718096]">Mỗi lịch hẹn có tối đa một yêu cầu hoàn tiền. Số tiền hoàn theo bậc chính sách tại thời điểm hủy.</p>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-[1120px] text-left text-sm">
                <thead class="bg-[#EDF6FC] text-[11px] uppercase tracking-wide text-[#49647D]">
                    <tr>
                        <th class="whitespace-nowrap px-5 py-3 font-bold">Lịch hẹn</th>
                        <th class="whitespace-nowrap px-5 py-3 font-bold">Khách hàng</th>
                        <th class="whitespace-nowrap px-5 py-3 font-bold">Số tiền hoàn</th>
                        <th class="whitespace-nowrap px-5 py-3 font-bold">Trạng thái</th>
                        <th class="whitespace-nowrap px-5 py-3 font-bold">Xác nhận chuyển khoản</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#EEF4F8]">
                    @forelse($refunds as $refund)
                        @php $meta = $statusMap[$refund->status] ?? ['label' => $refund->status, 'tone' => '#64748B', 'soft' => '#F1F5F9', 'border' => '#E2E8F0', 'icon' => '•']; @endphp
                        <tr class="align-top transition hover:bg-[#F3F9FD]">
                            <td class="px-5 py-4">
                                <p class="font-mono text-xs font-black text-[#2D82C4]">{{ $refund->appointment?->appointment_code }}</p>
                                <p class="mt-0.5 text-[11px] text-[#8A9BB0]">
                                    {{ $refund->appointment?->start_at?->format('d/m/Y H:i') ?? '—' }}
                                    · {{ $refund->appointment?->service?->name ?? '—' }}
                                </p>
                            </td>

                            <td class="px-5 py-4">
                                <p class="font-bold text-[#102A43]">{{ $refund->appointment?->customer?->name ?? '—' }}</p>
                                <p class="text-[11px] text-[#8A9BB0]">{{ $refund->appointment?->customer?->email }}</p>
                            </td>

                            <td class="whitespace-nowrap px-5 py-4">
                                <p class="text-sm font-black tabular-nums text-[#102A43]">
                                    {{ number_format((float) $refund->amount, 0, ',', '.') }} đ
                                </p>
                                <p class="text-[11px] font-bold text-[#8A9BB0]">{{ $refund->percentage }}% giá trị lịch</p>
                            </td>

                            <td class="px-5 py-4">
                                <span class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-[11px] font-bold"
                                      style="color: {{ $meta['tone'] }}; background-color: {{ $meta['soft'] }}; border-color: {{ $meta['border'] }}">
                                    <span aria-hidden="true">{{ $meta['icon'] }}</span>
                                    {{ $meta['label'] }}
                                </span>
                                @if($refund->processed_at)
                                    <p class="mt-1 text-[10px] font-semibold text-[#8A9BB0]">
                                        Xử lý {{ $refund->processed_at->format('d/m/Y H:i') }}
                                    </p>
                                @endif
                            </td>

                            <td class="px-5 py-4">
                                @if($refund->status === 'PENDING')
                                    <form method="POST" action="{{ route('admin.refunds.complete', $refund) }}" class="flex flex-wrap items-end gap-2">
                                        @csrf
                                        <label class="text-[11px] font-bold text-[#52637A]">
                                            Mã giao dịch hoàn <span class="text-red-500" aria-hidden="true">*</span>
                                            <input name="reference" required maxlength="100" placeholder="VD: FT26102001"
                                                   class="mt-1 w-44 rounded-lg border border-[#D7E8F3] px-3 py-2 text-sm">
                                        </label>
                                        <button type="submit" class="rounded-lg bg-[#2D82C4] px-4 py-2 text-xs font-bold text-white transition hover:bg-[#23689C]">
                                            Xác nhận đã hoàn
                                        </button>
                                    </form>
                                    <p class="mt-1.5 text-[10px] font-semibold text-[#8A9BB0]">Chỉ bấm sau khi đã thực hiện chuyển khoản cho khách.</p>
                                @else
                                    <p class="font-mono text-xs font-bold text-[#334E68]">{{ $refund->reference ?: '—' }}</p>
                                    <p class="mt-0.5 text-[11px] text-[#8A9BB0]">{{ $refund->appointment?->customer?->name }}</p>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-16 text-center">
                                <p class="text-sm font-bold text-[#102A43]">Không có yêu cầu hoàn tiền.</p>
                                <p class="mt-1 text-xs text-[#718096]">Yêu cầu hoàn tiền xuất hiện khi khách hủy lịch đã thanh toán hợp lệ.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($refunds->hasPages())
            <div class="border-t border-[#E8F1F6] px-5 py-4">{{ $refunds->links() }}</div>
        @endif
    </section>

    {{-- ═══════════════ QUY TẮC NGHIỆP VỤ ═══════════════ --}}
    <section class="grid gap-4 sm:grid-cols-2">
        <div class="rounded-2xl border border-[#D7E8F3] bg-white p-5 shadow-[0_4px_18px_rgb(35_111_168/0.05)]">
            <h2 class="text-sm font-black text-[#102A43]">Doanh thu thuần</h2>
            <p class="mt-1.5 text-xs leading-relaxed text-[#52637A]">
                Báo cáo doanh thu chỉ tính <strong class="font-bold text-[#334E68]">thanh toán hợp lệ đã thu</strong> trừ
                <strong class="font-bold text-[#334E68]">tiền hoàn thực tế</strong>. Yêu cầu chờ hoàn tiền không bị trừ cho tới khi xác nhận chuyển khoản.
            </p>
        </div>
        <div class="rounded-2xl border border-[#D7E8F3] bg-white p-5 shadow-[0_4px_18px_rgb(35_111_168/0.05)]">
            <h2 class="text-sm font-black text-[#102A43]">Dữ liệu nhạy cảm</h2>
            <p class="mt-1.5 text-xs leading-relaxed text-[#52637A]">
                Số tài khoản ngân hàng của khách được mã hoá và <strong class="font-bold text-[#334E68]">không hiển thị đầy đủ</strong>
                trên màn hình quản trị. Nhật ký kiểm toán không ghi nội dung tài khoản.
            </p>
        </div>
    </section>
</div>
@endsection
