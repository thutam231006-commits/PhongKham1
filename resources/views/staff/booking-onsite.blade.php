@extends('layouts.staff')

@section('title', 'Đặt lịch tại quầy')

@section('content')
<div class="mx-auto max-w-3xl space-y-6 px-4 py-8 sm:px-6 lg:px-8"
     x-data="bookingSlots({
        endpoint: @js(route('staff.appointments.slots')),
        serviceId: {{ (int) ($services->first()?->id ?? 0) }},
        specialistId: {{ (int) ($specialists->first()?->id ?? 0) }},
        initialDate: @js(now()->addDay()->toDateString()),
        initialSlots: [],
        minDate: @js(now()->toDateString()),
        maxDate: @js(now()->addDays(30)->toDateString()),
     })">

    <div>
        <h1 class="text-3xl font-black text-[#2D82C4]">Đặt lịch tại quầy</h1>
        <p class="mt-2 text-sm text-[#4A5568]">Chỉ hiển thị khung giờ còn trống thực sự; hệ thống kiểm tra lại lịch làm việc và chống đặt trùng khi lưu.</p>
    </div>

    @if($errors->any())
        <div class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-800">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('staff.appointments.store') }}" class="rounded-xl border border-gray-200 bg-white p-6">
        @csrf

        <div class="grid gap-5 sm:grid-cols-2">
            <label class="text-sm font-bold text-[#334155]">
                Khách hàng
                <select name="customer_id" required class="mt-2 w-full rounded-lg border-gray-300">
                    <option value="">Chọn khách hàng</option>
                    @foreach($customers as $customer)
                        <option value="{{ $customer->id }}">{{ $customer->name }} · {{ $customer->phone ?: 'chưa có SĐT' }}</option>
                    @endforeach
                </select>
            </label>

            <label class="text-sm font-bold text-[#334155]">
                Dịch vụ
                <select name="service_id" required
                        x-model.number="serviceId"
                        @change="load()"
                        class="mt-2 w-full rounded-lg border-gray-300">
                    @foreach($services as $service)
                        <option value="{{ $service->id }}">{{ $service->name }} · {{ number_format($service->price, 0, ',', '.') }} VNĐ</option>
                    @endforeach
                </select>
            </label>

            <label class="text-sm font-bold text-[#334155]">
                Chuyên viên
                <select name="specialist_id" required
                        x-model.number="specialistId"
                        @change="load()"
                        class="mt-2 w-full rounded-lg border-gray-300">
                    @foreach($specialists as $specialist)
                        <option value="{{ $specialist->id }}">{{ $specialist->user->name }} · {{ $specialist->title }}</option>
                    @endforeach
                </select>
            </label>

            <label class="text-sm font-bold text-[#334155]">
                Ngày tư vấn
                <input type="date" name="date" x-model="date" @change="load()" :min="minDate" :max="maxDate" required class="mt-2 w-full rounded-lg border-gray-300">
            </label>
        </div>

        {{-- Khung giờ: nạp trực tiếp từ API khung giờ trống --}}
        <fieldset class="mt-5">
            <legend class="text-sm font-bold text-[#334155]">Khung giờ còn trống</legend>

            <p class="mt-2 text-xs text-[#52637A]">
                <span x-show="loading" class="inline-flex items-center gap-2">
                    <span class="h-3 w-3 animate-spin rounded-full border-2 border-[#2D82C4] border-t-transparent" aria-hidden="true"></span>
                    Đang tải khung giờ trống…
                </span>
                <span x-show="!loading && !error" x-cloak>
                    Còn <strong class="text-[#2D82C4]" x-text="slots.length"></strong> khung giờ trống.
                </span>
                <span x-show="error" x-cloak class="font-bold text-red-700" x-text="error"></span>
            </p>

            <div class="mt-3 grid grid-cols-2 gap-2 sm:grid-cols-4">
                <template x-for="slot in slots" :key="slot.start">
                    <label class="cursor-pointer">
                        <input type="radio" name="time" :value="slot.start" required
                               class="peer sr-only">
                        <span class="block rounded-lg border border-gray-300 px-3 py-2.5 text-center text-sm font-bold transition peer-checked:border-[#2D82C4] peer-checked:bg-[#EAF4FB] peer-checked:text-[#2D82C4] hover:border-[#6FA6CF]"
                              x-text="slot.start"></span>
                    </label>
                </template>

                <p x-show="!loading && slots.length === 0 && !error" x-cloak
                   class="col-span-full rounded-lg border border-dashed p-6 text-center text-sm text-[#4A5568]">
                    Ngày này không còn khung giờ trống. Vui lòng chọn ngày khác.
                </p>
            </div>
        </fieldset>

        <div class="mt-5 grid gap-5 sm:grid-cols-2">
            <label class="text-sm font-bold text-[#334155]">
                Hình thức tư vấn
                <select name="consultation_type" class="mt-2 w-full rounded-lg border-gray-300">
                    <option value="OFFLINE">Trực tiếp tại phòng khám</option>
                    <option value="ONLINE">Trực tuyến (phòng họp riêng)</option>
                </select>
            </label>

            <label class="text-sm font-bold text-[#334155]">
                Thanh toán
                <select name="payment_method" class="mt-2 w-full rounded-lg border-gray-300">
                    <option value="CASH">Tiền mặt tại quầy</option>
                    <option value="BANK_QR">Chuyển khoản QR</option>
                </select>
            </label>
        </div>

        <label class="mt-5 block text-sm font-bold text-[#334155]">
            Ghi chú cho buổi tư vấn <span class="font-normal text-[#718096]">(tùy chọn)</span>
            <textarea name="customer_note" rows="3" maxlength="500"
                      class="mt-2 w-full rounded-lg border-gray-300"
                      placeholder="Ghi chú ngắn giúp chuyên viên chuẩn bị cho buổi gặp."></textarea>
        </label>

        <button type="submit" class="mt-6 rounded-lg bg-[#2D82C4] px-5 py-3 font-bold text-white transition hover:bg-[#23689C]">
            Tạo lịch hẹn
        </button>
    </form>
</div>
@endsection