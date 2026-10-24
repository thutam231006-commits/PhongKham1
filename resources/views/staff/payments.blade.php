@extends('layouts.staff')

@section('title', 'Thu tiền & Hoàn tiền')

@section('content')
<div class="mx-auto max-w-screen-xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">

    {{-- PAGE HEADER --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-xs font-bold uppercase tracking-widest text-[#6FA6CF]">Quầy thu tiền</p>
            <h1 class="mt-1 text-2xl font-black tracking-tight text-[#102A43]">Thu tiền & Hoàn tiền</h1>
            <p class="mt-1 text-sm text-[#52637A]">Xác nhận tiền mặt sau khi buổi tư vấn hoàn tất. Chỉ bấm xác nhận sau khi đã nhận đủ tiền thực tế.</p>
        </div>
    </div>

    @if(session('success'))
        <div role="alert" class="rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-800">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div role="alert" class="rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-800">{{ $errors->first() }}</div>
    @endif

    {{-- NGUYÊN TẮC --}}
    <div class="rounded-xl border border-blue-200 bg-blue-50 px-5 py-4">
        <div class="flex items-start gap-3">
            <svg class="mt-0.5 h-5 w-5 shrink-0 text-[#2D82C4]" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M12 2a10 10 0 110 20A10 10 0 0112 2z"/>
            </svg>
            <div class="text-xs text-blue-800 space-y-0.5">
                <p class="font-bold">Quy tắc thu tiền mặt</p>
                <p>• Chỉ xác nhận sau khi đã nhận đủ tiền và đối chiếu số tiền trên hệ thống.</p>
                <p>• Không xác nhận thanh toán hai lần — hệ thống sẽ chặn thao tác nếu đã ghi PAID.</p>
                <p>• Nếu cần hoàn tiền, sử dụng phần "Yêu cầu hoàn tiền" phía dưới — không sửa giao dịch cũ.</p>
            </div>
        </div>
    </div>

    {{-- TIỀN MẶT CHỜ THU --}}
    <section class="overflow-hidden rounded-2xl border border-emerald-200 bg-white shadow-sm" aria-labelledby="cash-heading">
        <div class="flex items-center justify-between border-b border-emerald-100 bg-emerald-50/60 px-5 py-4">
            <div>
                <h2 id="cash-heading" class="text-lg font-black text-[#102A43]">Tiền mặt chờ thu</h2>
                <p class="mt-0.5 text-xs text-[#718096]">Lịch đã hoàn tất, khách chọn thanh toán tiền mặt tại quầy.</p>
            </div>
            <span class="rounded-full bg-emerald-500 px-3 py-1 text-xs font-black text-white">{{ $appointments->count() }}</span>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="border-b border-[#EEF4F8] bg-[#F7FBFE] text-left text-xs uppercase tracking-wide text-[#52637A]">
                    <tr>
                        <th class="px-5 py-3.5">Mã lịch</th>
                        <th class="px-5 py-3.5">Khách hàng</th>
                        <th class="px-5 py-3.5">Dịch vụ</th>
                        <th class="px-5 py-3.5">Chuyên viên</th>
                        <th class="px-5 py-3.5">Số tiền</th>
                        <th class="px-5 py-3.5">Xác nhận thu</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#EEF4F8]">
                    @forelse($appointments as $appointment)
                    <tr class="hover:bg-[#F7FBFE] transition">
                        <td class="px-5 py-4 font-mono text-xs text-[#52637A]">{{ $appointment->appointment_code }}</td>
                        <td class="px-5 py-4">
                            <p class="font-bold text-[#102A43]">{{ $appointment->customer->name }}</p>
                            <p class="text-xs text-[#52637A]">{{ $appointment->customer->phone ?? $appointment->customer->email }}</p>
                        </td>
                        <td class="px-5 py-4 text-[#52637A]">{{ $appointment->service->name }}</td>
                        <td class="px-5 py-4 text-[#52637A]">{{ $appointment->specialistProfile->user->name }}</td>
                        <td class="px-5 py-4">
                            <span class="text-base font-black text-emerald-700">
                                {{ number_format($appointment->price, 0, ',', '.') }} VNĐ
                            </span>
                        </td>
                        <td class="px-5 py-4">
                            <form method="POST" action="{{ route('staff.payments.collect', $appointment) }}" class="flex gap-2">
                                @csrf
                                <input name="payment_reference" maxlength="100" placeholder="Mã tham chiếu (tuỳ chọn)"
                                       class="w-40 rounded-lg border-[#B8D2E4] text-xs focus:border-[#2D82C4] focus:ring-[#2D82C4]">
                                <button class="rounded-lg bg-emerald-600 px-4 py-2 text-xs font-bold text-white hover:bg-emerald-700 transition">
                                    Đã thu tiền
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-5 py-12 text-center text-sm text-[#718096]">
                            Không có lịch nào chờ thu tiền mặt.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($appointments->hasPages())
        <div class="border-t border-[#EEF4F8] px-5 py-4">
            {{ $appointments->links() }}
        </div>
        @endif
    </section>

    {{-- YÊU CẦU HOÀN TIỀN --}}
    <section class="overflow-hidden rounded-2xl border border-[#D7E8F3] bg-white shadow-sm" aria-labelledby="refund-heading">
        <div class="flex items-center justify-between border-b border-[#E8F1F6] px-5 py-4">
            <div>
                <h2 id="refund-heading" class="text-lg font-black text-[#102A43]">Yêu cầu hoàn tiền</h2>
                <p class="mt-0.5 text-xs text-[#718096]">Xác nhận sau khi giao dịch hoàn tiền thực tế đã thành công. Không sửa lịch sử giao dịch cũ.</p>
            </div>
            <span class="rounded-full bg-[#2D82C4] px-3 py-1 text-xs font-black text-white">{{ $refunds->count() }}</span>
        </div>
        @forelse($refunds as $refund)
        <div class="flex flex-col gap-4 border-b border-[#EEF4F8] px-5 py-4 last:border-0 sm:flex-row sm:items-center sm:justify-between">
            <div class="min-w-0">
                <p class="font-bold text-[#102A43]">
                    {{ $refund->appointment->appointment_code }} · {{ $refund->appointment->customer->name }}
                </p>
                <p class="mt-0.5 text-sm text-[#52637A]">
                    {{ $refund->appointment->service->name }} ·
                    <strong class="text-[#102A43]">{{ number_format($refund->amount, 0, ',', '.') }} VNĐ</strong>
                    ({{ $refund->percentage }}%)
                </p>
                @if($refund->reason)
                <p class="mt-0.5 text-xs text-[#718096]">Lý do: {{ $refund->reason }}</p>
                @endif
            </div>
            <form method="POST" action="{{ route('staff.refunds.complete', $refund) }}" class="flex shrink-0 gap-2">
                @csrf
                <input name="reference" required placeholder="Mã giao dịch hoàn"
                       class="w-44 rounded-lg border-[#B8D2E4] text-xs focus:border-[#2D82C4] focus:ring-[#2D82C4]">
                <button class="rounded-lg bg-[#2D82C4] px-4 py-2 text-xs font-bold text-white hover:bg-[#23689C] transition">
                    Xác nhận đã hoàn
                </button>
            </form>
        </div>
        @empty
        <p class="px-5 py-10 text-center text-sm text-[#718096]">Không có yêu cầu hoàn tiền nào đang chờ.</p>
        @endforelse
    </section>
</div>
@endsection
