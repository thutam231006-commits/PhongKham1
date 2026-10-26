@extends('layouts.staff')
@section('title', 'Hồ sơ khách hàng')
@section('content')
<div class="mx-auto max-w-screen-xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
    <div class="flex flex-col gap-4 border-b border-[#D7E8F3] pb-6 sm:flex-row sm:items-end sm:justify-between"><div><p class="text-sm font-semibold text-[#6FA6CF]">Hồ sơ khách hàng</p><h1 class="mt-1 text-3xl font-black text-[#102A43]">{{ $user->name }}</h1><p class="mt-2 text-sm text-[#52637A]">Mã khách: {{ $user->customerProfile?->customer_code ?? 'Chưa cấp' }}</p></div><a href="{{ route('staff.customers') }}" class="rounded-lg border border-[#2D82C4] px-4 py-2.5 text-sm font-bold text-[#2D82C4]">Quay lại danh sách</a></div>
    <section class="grid gap-4 sm:grid-cols-4">
        <div class="rounded-xl border border-[#D7E8F3] bg-white p-5">
            <p class="text-xs font-bold uppercase text-[#718096]">Họ tên</p>
            <p class="mt-2 font-bold text-[#102A43]">{{ $user->name }}</p>
        </div>
        <div class="rounded-xl border border-[#D7E8F3] bg-white p-5">
            <p class="text-xs font-bold uppercase text-[#718096]">Email</p>
            <p class="mt-2 break-all font-bold text-[#102A43]">{{ $user->email ?: 'Chưa cập nhật' }}</p>
            <p class="mt-1 text-xs text-[#718096]">Trạng thái xác thực: {{ $user->email_verified_at ? 'Đã xác thực ('.$user->email_verified_via.')' : 'Chưa xác thực' }}</p>
        </div>
        <div class="rounded-xl border border-[#D7E8F3] bg-white p-5">
            <p class="text-xs font-bold uppercase text-[#718096]">Số điện thoại</p>
            <p class="mt-2 font-bold text-[#102A43]">{{ $user->phone ?: 'Chưa cập nhật' }}</p>
        </div>
        <div class="rounded-xl border border-[#D7E8F3] bg-white p-5">
            <p class="text-xs font-bold uppercase text-[#718096]">Trạng thái tài khoản</p>
            <p class="mt-2 font-bold text-[#102A43]">{{ $user->status === 'PENDING_ACTIVATION' ? 'Chưa kích hoạt' : 'Đang hoạt động' }}</p>
            @if($user->activation_email_status && $user->activation_email_status !== 'NOT_SENT')
                <p class="mt-1 text-xs text-[#718096]">Email kích hoạt: {{ $user->activation_email_status }}{{ $user->activation_email_sent_at ? ' ('.$user->activation_email_sent_at->format('d/m H:i').')' : '' }}</p>
            @endif
            @if($user->activation_email_error)
                <p class="mt-1 text-xs text-red-600">{{ $user->activation_email_error }}</p>
            @endif
        </div>
    </section>

    @if($user->status === 'PENDING_ACTIVATION')
    <section class="rounded-xl border border-[#D7E8F3] bg-white p-6">
        <h2 class="text-lg font-black text-[#102A43]">Xác thực email cho khách</h2>
        <p class="mt-1 text-xs text-[#52637A]">Chọn một cách xác thực phù hợp với tình huống thực tế khi khách không nhận được email.</p>

        <div class="mt-5 grid gap-4 lg:grid-cols-3">
            {{-- Cách 1: gửi lại email kích hoạt --}}
            <div class="rounded-lg border border-[#E8F1F6] bg-[#F7FBFE] p-4">
                <h3 class="text-sm font-black text-[#102A43]">1. Gửi lại email kích hoạt</h3>
                <p class="mt-1 text-xs text-[#718096]">Gửi lại link + mã 6 số cho khách qua email.</p>
                <form method="POST" action="{{ route('staff.customers.resend-activation', $user) }}" class="mt-3">@csrf<button class="w-full rounded-lg bg-[#2D82C4] px-3 py-2 text-xs font-bold text-white">Gửi email kích hoạt</button></form>
            </div>

            {{-- Cách 2: gửi mã 6 số --}}
            <div class="rounded-lg border border-[#E8F1F6] bg-[#F7FBFE] p-4">
                <h3 class="text-sm font-black text-[#102A43]">2. Gửi mã xác thực 6 số</h3>
                <p class="mt-1 text-xs text-[#718096]">Chỉ gửi mã số (hết hạn {{ config('auth.verification.otp_expire', 15) }} phút) cho khách đọc qua điện thoại.</p>
                <form method="POST" action="{{ route('staff.customers.activation-code', $user) }}" class="mt-3">@csrf<button class="w-full rounded-lg border border-[#2D82C4] px-3 py-2 text-xs font-bold text-[#2D82C4]" @disabled(!$user->email)>Gửi mã 6 số</button></form>
            </div>

            {{-- Cách 3: xác nhận thủ công tại quầy --}}
            <div class="rounded-lg border border-[#E8F1F6] bg-[#F7FBFE] p-4">
                <h3 class="text-sm font-black text-[#102A43]">3. Xác nhận thủ công tại quầy</h3>
                <p class="mt-1 text-xs text-[#718096]">Dùng khi khách không nhận được email. Đặt mật khẩu tại quầy và ghi lý do.</p>
                <form method="POST" action="{{ route('staff.customers.verify-manual', $user) }}" class="mt-3 space-y-2">@csrf<input type="password" name="password" required placeholder="Mật khẩu tạm cho khách" class="w-full rounded-lg border-gray-300 text-xs"><input type="password" name="password_confirmation" required placeholder="Xác nhận mật khẩu" class="w-full rounded-lg border-gray-300 text-xs"><input type="text" name="reason" required placeholder="Lý do xác nhận thủ công" class="w-full rounded-lg border-gray-300 text-xs"><button class="w-full rounded-lg bg-[#1F9D55] px-3 py-2 text-xs font-bold text-white">Xác nhận thủ công</button></form>
            </div>
        </div>
    </section>
    @endif

    <section class="overflow-hidden rounded-xl border border-[#D7E8F3] bg-white"><div class="border-b border-[#E8F1F6] px-5 py-4"><h2 class="text-lg font-black text-[#102A43]">Lịch sử gói và lịch đã đặt</h2><p class="mt-1 text-xs text-[#718096]">Chỉ hiển thị thông tin đặt lịch vận hành, không hiển thị ghi chú riêng tư hoặc nội dung tư vấn.</p></div><div class="overflow-x-auto"><table class="min-w-full text-left text-sm"><thead class="bg-[#F7FBFE] text-xs uppercase text-[#52637A]"><tr><th class="px-5 py-3">Gói tư vấn</th><th class="px-5 py-3">Chuyên viên</th><th class="px-5 py-3">Thời gian</th><th class="px-5 py-3">Hình thức</th><th class="px-5 py-3">Thanh toán</th><th class="px-5 py-3">Trạng thái</th></tr></thead><tbody class="divide-y divide-[#EEF4F8]">@forelse($appointments as $appointment)<tr><td class="px-5 py-4"><p class="font-bold text-[#102A43]">{{ $appointment->service->name }}</p><p class="text-xs text-[#718096]">{{ $appointment->appointment_code }}</p></td><td class="px-5 py-4">{{ $appointment->specialistProfile->user->name }}</td><td class="whitespace-nowrap px-5 py-4 font-semibold">{{ $appointment->start_at->format('d/m/Y H:i') }} - {{ $appointment->end_at->format('H:i') }}</td><td class="px-5 py-4 text-xs">{{ $appointment->consultation_type === 'ONLINE' ? 'Trực tuyến' : ($appointment->consultationRoom?->name ?? 'Trực tiếp') }}</td><td class="px-5 py-4"><x-status-chip type="payment" :status="$appointment->payment_status" /></td><td class="px-5 py-4"><x-status-chip type="appointment" :status="$appointment->status" /></td></tr>@empty<tr><td colspan="6" class="px-5 py-12 text-center text-sm text-[#718096]">Khách hàng chưa có lịch đặt nào.</td></tr>@endforelse</tbody></table></div></section>
</div>
@endsection
