@extends('layouts.app')

@section('title', 'Thanh toán lịch hẹn '.$appointment->appointment_code)

@section('content')
<div class="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8">
    <div class="mb-7">
        <p class="mg-section-kicker">Thanh toán SePay</p>
        <h1 class="mt-2 text-3xl font-extrabold text-[#102A43] sm:text-4xl">Quét mã để hoàn tất đặt lịch</h1>
        <p class="mt-3 max-w-2xl leading-7 text-[#52637A]">Mở ứng dụng ngân hàng, quét VietQR và giữ nguyên số tiền cùng nội dung chuyển khoản.</p>
    </div>

    <div
        class="grid gap-6 lg:grid-cols-[0.9fr_1.1fr]"
        data-payment-status-url="{{ route('booking.payment.status', $appointment) }}"
        data-payment-redirect-url="{{ route('customer.appointments.detail', $appointment) }}"
        data-payment-expires-at="{{ $payment['expires_at'] }}">
        <section class="rounded-2xl border border-[#D7E8F3] bg-white p-6 shadow-sm" aria-labelledby="qr-heading">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <p class="text-sm font-bold text-[#52637A]">{{ $payment['bank_code'] }}</p>
                    <h2 id="qr-heading" class="mt-1 text-xl font-extrabold text-[#102A43]">VietQR thanh toán</h2>
                </div>
                <span class="rounded-full bg-[#EAF4FB] px-3 py-1 text-xs font-bold text-[#236FA8]">SePay</span>
            </div>

            <div class="mx-auto mt-6 aspect-square max-w-[330px] overflow-hidden rounded-xl border border-[#D7E8F3] bg-white p-3">
                <img
                    src="{{ $payment['qr_url'] }}"
                    alt="Mã VietQR thanh toán lịch {{ $appointment->appointment_code }}"
                    width="640"
                    height="640"
                    class="h-full w-full object-contain">
            </div>

            <p class="mt-5 text-center text-xs leading-5 text-[#52637A]">QR đã chứa sẵn tài khoản, số tiền và nội dung. Không chỉnh sửa khi chuyển khoản.</p>
        </section>

        <section class="space-y-5 rounded-2xl border border-[#D7E8F3] bg-white p-6 shadow-sm" aria-labelledby="payment-details-heading">
            <div class="flex flex-col justify-between gap-3 border-b border-[#D7E8F3] pb-5 sm:flex-row sm:items-center">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.06em] text-[#2D82C4]">Mã lịch {{ $appointment->appointment_code }}</p>
                    <h2 id="payment-details-heading" class="mt-1 text-xl font-extrabold text-[#102A43]">Thông tin chuyển khoản</h2>
                </div>
                @if($appointment->status === 'PENDING')
                    <div class="rounded-lg bg-amber-50 px-3 py-2 text-sm font-bold text-amber-800" aria-live="polite">
                        Giữ chỗ còn <span data-payment-countdown>10:00</span>
                    </div>
                @endif
            </div>

            <dl class="space-y-4 text-sm">
                <div class="grid gap-1 sm:grid-cols-[150px_1fr] sm:items-center">
                    <dt class="text-[#52637A]">Ngân hàng</dt>
                    <dd class="font-bold text-[#102A43]">{{ $payment['bank_code'] }}</dd>
                </div>
                <div class="grid gap-1 sm:grid-cols-[150px_1fr] sm:items-center">
                    <dt class="text-[#52637A]">Số tài khoản</dt>
                    <dd class="flex items-center justify-between gap-3 font-mono text-base font-extrabold text-[#102A43]">
                        <span>{{ $payment['bank_account'] }}</span>
                        <button type="button" class="rounded-lg border border-[#C9DFEE] px-3 py-1.5 font-sans text-xs font-bold text-[#2D82C4] hover:bg-[#EAF4FB]" data-copy-value="{{ $payment['bank_account'] }}">Sao chép</button>
                    </dd>
                </div>
                <div class="grid gap-1 sm:grid-cols-[150px_1fr] sm:items-center">
                    <dt class="text-[#52637A]">Chủ tài khoản</dt>
                    <dd class="font-bold text-[#102A43]">{{ $payment['account_name'] }}</dd>
                </div>
                <div class="grid gap-1 sm:grid-cols-[150px_1fr] sm:items-center">
                    <dt class="text-[#52637A]">Số tiền</dt>
                    <dd class="text-xl font-extrabold text-[#2D82C4]">{{ number_format($payment['amount'], 0, ',', '.') }} VNĐ</dd>
                </div>
                <div class="grid gap-1 sm:grid-cols-[150px_1fr] sm:items-center">
                    <dt class="text-[#52637A]">Nội dung</dt>
                    <dd class="flex items-center justify-between gap-3 rounded-lg bg-[#F5FAFE] px-3 py-2 font-mono font-extrabold text-[#102A43]">
                        <span class="break-all">{{ $payment['transfer_content'] }}</span>
                        <button type="button" class="shrink-0 rounded-lg border border-[#C9DFEE] bg-white px-3 py-1.5 font-sans text-xs font-bold text-[#2D82C4] hover:bg-[#EAF4FB]" data-copy-value="{{ $payment['transfer_content'] }}">Sao chép</button>
                    </dd>
                </div>
            </dl>

            <div class="rounded-xl border border-[#BFDBFE] bg-[#EFF6FF] p-4 text-sm leading-6 text-[#1E40AF]" data-payment-message role="status" aria-live="polite">
                Hệ thống đang chờ SePay xác nhận giao dịch. Trang sẽ tự cập nhật khi thanh toán thành công.
            </div>

            <div class="rounded-xl border border-[#D7E8F3] p-4">
                <p class="text-sm font-bold text-[#102A43]">Lịch hẹn đang giữ</p>
                <p class="mt-1 text-sm leading-6 text-[#52637A]">{{ $appointment->service->name }} với {{ $appointment->specialistProfile->user->name }}</p>
                <p class="mt-1 text-sm font-semibold text-[#102A43]">{{ $appointment->start_at->format('d/m/Y H:i') }}</p>
            </div>

            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-between">
                <a href="{{ route('customer.appointments') }}" class="inline-flex min-h-11 items-center justify-center rounded-full border border-[#C9DFEE] px-5 py-2 text-sm font-bold text-[#334E68] hover:bg-[#F5FAFE]">Xem lịch hẹn</a>
                <button type="button" class="inline-flex min-h-11 items-center justify-center rounded-full bg-[#2D82C4] px-5 py-2 text-sm font-bold text-white hover:bg-[#23689C]" data-payment-check>Kiểm tra thanh toán</button>
            </div>
        </section>
    </div>
</div>
@endsection
