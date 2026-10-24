@extends('layouts.app')
@section('title', 'Đặt lịch tư vấn - Bước '.$step)
@section('content')
<div class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8">
    <x-booking-stepper :current="$step" />
    @if(session('error'))<div role="alert" class="mb-6 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800">{{ session('error') }}</div>@endif

    @if($step === 1)
        <section class="space-y-6"><div><h1 class="text-3xl font-black text-[#2D82C4]">Chọn gói dịch vụ tư vấn</h1><p class="mt-2 text-[#4A5568]">Chọn nhu cầu phù hợp, bạn có thể thay đổi trước khi xác nhận.</p></div><div class="grid gap-5 md:grid-cols-2 lg:grid-cols-3">@foreach($services as $service)<article class="flex flex-col rounded-xl border bg-white p-5 shadow-sm"><div class="mb-4 h-32 rounded-lg bg-[#EAF4FB]"></div><h2 class="text-lg font-bold text-[#2D82C4]">{{ $service->name }}</h2><p class="mt-2 flex-1 text-sm text-[#4A5568]">{{ $service->description }}</p><div class="mt-4 flex items-center justify-between text-sm"><span>{{ $service->duration_minutes }} phút</span><strong>{{ number_format($service->price,0,',','.') }} VNĐ</strong></div><form method="POST" action="{{ route('booking.step.service') }}" class="mt-4">@csrf<input type="hidden" name="service_id" value="{{ $service->id }}"><button class="w-full rounded-lg bg-[#2D82C4] px-4 py-3 text-sm font-bold text-white">Đặt lịch ngay</button></form></article>@endforeach</div></section>
    @elseif($step === 2)
        <section class="space-y-6">

            {{-- ── 2.1. THÔNG TIN CHI TIẾT GÓI DỊCH VỤ ĐÃ CHỌN ── --}}
            <article class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                <div class="grid grid-cols-1 gap-6 p-6 sm:grid-cols-[220px_1fr] sm:p-8">
                    <div class="h-44 w-full overflow-hidden rounded-xl bg-[#EAF4FB] sm:h-full sm:min-h-44">
                        <img src="{{ $service->image_url ?: 'https://images.unsplash.com/photo-1499209974431-9dddcece7f88?auto=format&fit=crop&w=800&q=80' }}"
                             alt="{{ $service->name }}"
                             class="h-full w-full object-cover"
                             onerror="this.onerror=null;this.src='{{ asset('images/default-avatar.svg') }}'">
                    </div>

                    <div class="min-w-0">
                        <span class="inline-block rounded-full bg-[#EAF4FB] px-3 py-1 text-xs font-bold text-[#2D82C4]">
                            {{ $service->category?->name ?? 'Dịch vụ tư vấn' }}
                        </span>
                        <h1 class="mt-2 text-2xl font-black leading-tight text-[#0F172A] sm:text-3xl">{{ $service->name }}</h1>

                        @if($service->description)
                            <p class="mt-3 whitespace-pre-line text-sm leading-relaxed text-[#4A5568]">{{ $service->description }}</p>
                        @endif

                        @if($service->target_audience)
                            <div class="mt-4 rounded-xl border border-[#D7E8F3] bg-[#F5FAFE] p-4">
                                <p class="text-xs font-bold uppercase tracking-wider text-[#2D82C4]">Gói này phù hợp với</p>
                                <p class="mt-1 whitespace-pre-line text-sm leading-relaxed text-[#334E68]">{{ $service->target_audience }}</p>
                            </div>
                        @endif

                        <dl class="mt-5 grid grid-cols-2 gap-3 text-sm sm:grid-cols-3">
                            <div class="rounded-xl border border-gray-100 bg-white px-4 py-3">
                                <dt class="text-xs text-[#4A5568]">Thời lượng</dt>
                                <dd class="mt-0.5 font-black text-[#0F172A]">{{ $service->duration_minutes }} phút</dd>
                            </div>
                            <div class="rounded-xl border border-gray-100 bg-white px-4 py-3">
                                <dt class="text-xs text-[#4A5568]">Chi phí niêm yết</dt>
                                <dd class="mt-0.5 font-black text-[#2D82C4]">{{ number_format((float) $service->price, 0, ',', '.') }} VNĐ</dd>
                            </div>
                            <div class="col-span-2 rounded-xl border border-gray-100 bg-white px-4 py-3 sm:col-span-1">
                                <dt class="text-xs text-[#4A5568]">Bảo mật</dt>
                                <dd class="mt-0.5 font-black text-emerald-700">Riêng tư 100%</dd>
                            </div>
                        </dl>

                        <div class="mt-5 flex flex-wrap items-center gap-3">
                            <a href="{{ route('services.show', $service) }}" class="rounded-lg border border-[#2D82C4] px-4 py-2.5 text-sm font-bold text-[#2D82C4] transition hover:bg-[#EAF4FB]">
                                Xem trang dịch vụ đầy đủ
                            </a>
                            <a href="{{ route('appointments.book') }}" class="text-sm font-semibold text-[#4A5568] underline transition hover:text-[#2D82C4]">
                                Đổi sang gói dịch vụ khác
                            </a>
                        </div>
                    </div>
                </div>

                @if($service->session_process)
                    <div class="border-t border-gray-100 bg-[#F5FAFE] px-6 py-5 sm:px-8">
                        <p class="text-xs font-bold uppercase tracking-wider text-[#2D82C4]">Quy trình buổi tư vấn</p>
                        <p class="mt-2 whitespace-pre-line text-sm leading-relaxed text-[#334E68]">{{ $service->session_process }}</p>
                    </div>
                @endif
            </article>

            {{-- ── 2.2. CHỌN CHUYÊN VIÊN TƯ VẤN ── --}}
            <div>
                <h2 class="text-2xl font-black text-[#2D82C4]">Chọn chuyên viên tư vấn</h2>
                <p class="mt-2 text-[#4A5568]">
                    Các chuyên viên dưới đây phụ trách gói
                    <strong class="text-[#2D82C4]">{{ $service->name }}</strong>.
                    Bấm <strong>“Xem chi tiết”</strong> để mở hồ sơ chuyên viên, hoặc bấm <strong>“Chọn chuyên viên”</strong> để tiếp tục đến bước chọn thời gian lịch hẹn.
                </p>
            </div>

            <div class="grid gap-5 md:grid-cols-2">
                @forelse($specialists as $specialist)
                    <article class="flex flex-col rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                        <div class="flex gap-4">
                            <div class="flex h-20 w-20 shrink-0 items-center justify-center overflow-hidden rounded-full bg-[#EAF4FB] text-xl font-black text-[#2D82C4]">
                                @if($specialist->avatar_url)
                                    <img src="{{ $specialist->avatar_url }}" alt="{{ $specialist->user->name }}" class="h-full w-full object-cover">
                                @else
                                    {{ mb_substr($specialist->user->name, 0, 1) }}
                                @endif
                            </div>
                            <div class="min-w-0">
                                <h3 class="font-bold text-[#0F172A]">{{ $specialist->user->name }}</h3>
                                <p class="text-sm text-[#4A5568]">{{ $specialist->title }}</p>
                                <p class="mt-2 text-sm text-amber-600">★ {{ number_format((float) ($specialist->average_rating ?? 0), 1) }} · {{ $specialist->reviews_count }} đánh giá</p>
                            </div>
                        </div>

                        <div class="mt-5 flex flex-wrap gap-3">
                            <a href="{{ route('specialists.show', $specialist) }}" class="flex-1 rounded-lg border border-[#2D82C4] px-3 py-2 text-center text-sm font-bold text-[#2D82C4] transition hover:bg-[#EAF4FB]">
                                Xem chi tiết
                            </a>
                            <form method="POST" action="{{ route('booking.step.specialist') }}" class="flex-1">
                                @csrf
                                <input type="hidden" name="specialist_id" value="{{ $specialist->id }}">
                                <button class="w-full rounded-lg bg-[#2D82C4] px-3 py-2 text-sm font-bold text-white transition hover:bg-[#23689C]">
                                    Chọn chuyên viên
                                </button>
                            </form>
                        </div>
                    </article>
                @empty
                    <div class="col-span-full rounded-xl border border-dashed bg-white p-10 text-center text-[#4A5568]">
                        Chưa có chuyên viên cung cấp gói này.
                        <a href="{{ route('appointments.book') }}" class="font-bold text-[#2D82C4] underline">Quay lại bước 1</a>
                    </div>
                @endforelse
            </div>
        </section>
    @elseif($step === 3)
        {{--
            Yêu cầu 3: chỉ hiển thị khung giờ còn trống và đồng bộ trực tiếp với
            lịch bác sĩ. Mỗi lần khách đổi ngày, UI gọi /api/slots để lấy đúng
            các khung giờ còn trống — không bao giờ hiện khung đã có người đặt.
        --}}
        <section class="mx-auto max-w-3xl space-y-6"
                 x-data="bookingSlots({
                    endpoint: @js(route('api.slots.index')),
                    serviceId: {{ (int) $service->id }},
                    specialistId: {{ (int) $specialist->id }},
                    initialDate: @js($date),
                    initialSlots: @js($slots),
                    minDate: @js(now()->addHours(2)->toDateString()),
                    maxDate: @js(now()->addDays(30)->toDateString()),
                 })">
            <div>
                <h1 class="text-3xl font-black text-[#2D82C4]">Chọn ngày và khung giờ</h1>
                <p class="mt-2 text-[#4A5568]">{{ $specialist->user->name }} · {{ $service->name }}</p>
            </div>

            <form method="GET" action="{{ route('booking.select-slot') }}" class="flex flex-col gap-3 rounded-lg border bg-white p-4 sm:flex-row">
                <input type="hidden" name="specialist_id" value="{{ $specialist->id }}">
                <div class="flex-1">
                    <label for="slot-date" class="mb-1 block text-xs font-bold text-[#52637A]">Ngày tư vấn</label>
                    <input id="slot-date"
                           type="date"
                           name="date"
                           x-model="date"
                           @change="load()"
                           :min="minDate"
                           :max="maxDate"
                           required
                           class="w-full rounded-lg border-gray-300">
                </div>
                <div class="flex items-end">
                    <button type="submit" class="rounded-lg bg-[#6FA6CF] px-5 py-2.5 font-bold text-white">
                        Xem giờ trống
                    </button>
                </div>
            </form>

            <div class="flex flex-wrap items-center justify-between gap-3 text-sm">
                <p class="text-[#52637A]">
                    <span x-show="loading" class="inline-flex items-center gap-2">
                        <span class="h-3 w-3 animate-spin rounded-full border-2 border-[#2D82C4] border-t-transparent" aria-hidden="true"></span>
                        Đang cập nhật khung giờ trống…
                    </span>
                    <span x-show="!loading && !error" x-cloak>
                        Còn <strong class="text-[#2D82C4]" x-text="slots.length"></strong>
                        khung giờ trống ngày <strong x-text="displayDate"></strong>
                        <span class="text-[#718096]">({{ $service->duration_minutes }} phút / khung giờ)</span>
                    </span>
                </p>
                <p x-show="error" x-cloak role="alert" class="font-bold text-red-700" x-text="error"></p>
            </div>

            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                <template x-for="slot in slots" :key="slot.start">
                    <form method="POST" action="{{ route('booking.step.slot') }}">
                        @csrf
                        <input type="hidden" name="date" :value="date">
                        <input type="hidden" name="time" :value="slot.start">
                        <button type="submit" class="w-full rounded-lg border border-gray-300 bg-white px-4 py-3 text-sm font-bold transition hover:border-[#2D82C4] hover:bg-[#EAF4FB]">
                            <span x-text="slot.start"></span>
                            <span class="text-xs font-normal text-[#718096]">– <span x-text="slot.end"></span></span>
                        </button>
                    </form>
                </template>

                <p x-show="!loading && slots.length === 0 && !error" x-cloak
                   class="col-span-full rounded-lg border border-dashed bg-white p-8 text-center text-[#4A5568]">
                    Ngày này chuyên viên đã kín lịch hoặc không làm việc. Vui lòng chọn ngày khác.
                </p>
                <p x-show="error" x-cloak class="col-span-full rounded-lg border border-red-200 bg-red-50 p-6 text-center text-sm text-red-800">
                    Không tải được khung giờ. Vui lòng kiểm tra kết nối rồi thử lại.
                </p>
            </div>
        </section>
    @elseif($step === 4)
        <section class="mx-auto max-w-2xl space-y-6"><div><h1 class="text-3xl font-black text-[#2D82C4]">Thông tin tư vấn</h1><p class="mt-2 text-[#4A5568]">Ghi chú giúp chuyên viên chuẩn bị tốt hơn cho buổi gặp.</p></div><form method="POST" action="{{ route('booking.step.note') }}" class="space-y-5 rounded-xl border bg-white p-6">@csrf<label class="block text-sm font-bold">Vấn đề bạn muốn trao đổi<textarea name="customer_note" maxlength="500" rows="8" class="mt-2 w-full rounded-lg border-gray-300">{{ session('booking.customer_note') }}</textarea><span class="mt-1 block text-xs font-normal text-gray-500">Tối đa 500 ký tự.</span></label><div class="flex justify-between"><a href="{{ route('booking.select-slot') }}" class="rounded-lg border px-4 py-3 text-sm font-bold">Quay lại</a><button class="rounded-lg bg-[#2D82C4] px-5 py-3 text-sm font-bold text-white">Tiếp tục thanh toán</button></div></form></section>
    @else
        <section class="mx-auto max-w-3xl space-y-6"><div><h1 class="text-3xl font-black text-[#2D82C4]">Thanh toán và xác nhận</h1><p class="mt-2 text-[#4A5568]">Kiểm tra thông tin trước khi gửi yêu cầu đặt lịch.</p></div><div class="rounded-xl border bg-white p-6"><dl class="grid gap-4 sm:grid-cols-2 text-sm"><div><dt class="text-gray-500">Dịch vụ</dt><dd class="font-bold">{{ $service->name }}</dd></div><div><dt class="text-gray-500">Chuyên viên</dt><dd class="font-bold">{{ $specialist->user->name }}</dd></div><div><dt class="text-gray-500">Thời gian</dt><dd class="font-bold">{{ $booking['date'] }} {{ $booking['time'] }}</dd></div><div><dt class="text-gray-500">Chi phí</dt><dd class="font-bold text-[#2D82C4]">{{ number_format($service->price,0,',','.') }} VNĐ</dd></div></dl></div><form method="POST" action="{{ route('booking.confirm.store') }}" class="space-y-5 rounded-xl border bg-white p-6">@csrf<div><p class="mb-2 text-sm font-bold">Hình thức tư vấn</p><div class="grid gap-3 sm:grid-cols-2"><label class="flex gap-3 rounded-lg border p-4"><input type="radio" name="consultation_type" value="OFFLINE" checked><span><strong>Trực tiếp tại phòng khám</strong><small class="block text-gray-500">Hệ thống sẽ xếp phòng trống khi nhân viên xác nhận.</small></span></label><label class="flex gap-3 rounded-lg border p-4"><input type="radio" name="consultation_type" value="ONLINE"><span><strong>Trực tuyến qua phòng họp trực tuyến</strong><small class="block text-gray-500">Link phòng họp riêng sẽ hiển thị sau khi được xác nhận.</small></span></label></div></div><div><p class="mb-2 text-sm font-bold">Thanh toán</p><div class="grid gap-3 sm:grid-cols-2"><label class="flex gap-3 rounded-lg border p-4"><input type="radio" name="payment_method" value="CASH" checked><span><strong>Tiền mặt tại quầy</strong><small class="block text-gray-500">Nhân viên sẽ gọi xác nhận trong vòng 24 giờ.</small></span></label><label class="flex gap-3 rounded-lg border p-4"><input type="radio" name="payment_method" value="BANK_QR"><span><strong>Chuyển khoản QR</strong><small class="block text-gray-500">Giữ chỗ và chờ nhân viên xác nhận.</small></span></label></div></div><div class="flex justify-between"><a href="{{ route('booking.note') }}" class="rounded-lg border px-4 py-3 text-sm font-bold">Quay lại</a><button class="rounded-lg bg-[#2D82C4] px-5 py-3 text-sm font-bold text-white">Gửi yêu cầu đặt lịch</button></div></form></section>
    @endif
</div>
@endsection
