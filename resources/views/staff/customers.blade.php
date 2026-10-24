@extends('layouts.staff')

@section('title', 'Khách hàng')

@section('content')
<div class="mx-auto max-w-screen-xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">

    {{-- PAGE HEADER --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-xs font-bold uppercase tracking-widest text-[#6FA6CF]">Hồ sơ & liên hệ</p>
            <h1 class="mt-1 text-2xl font-black tracking-tight text-[#102A43]">Khách hàng</h1>
            <p class="mt-1 text-sm text-[#52637A]">Tra cứu hồ sơ, thông tin liên hệ và lịch sử lịch hẹn theo quyền được cấp.</p>
        </div>
    </div>

    @if(session('success'))
        <div role="alert" class="rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-800">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div role="alert" class="rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-800">{{ $errors->first() }}</div>
    @endif

    {{-- TẠO KHÁCH HÀNG MỚI --}}
    <section class="rounded-2xl border border-[#D7E8F3] bg-white p-5 shadow-sm" aria-labelledby="new-customer-heading">
        <h2 id="new-customer-heading" class="text-base font-black text-[#102A43]">Tạo hồ sơ khách hàng mới</h2>
        <p class="mt-0.5 text-xs text-[#52637A]">Chỉ tạo nếu chắc chắn chưa tồn tại trong hệ thống — kiểm tra kỹ bằng ô tìm kiếm bên dưới trước.</p>
        <form method="POST" action="{{ route('staff.customers.store') }}"
              class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
            @csrf
            <div>
                <label class="text-xs font-bold text-[#52637A]">Họ tên <span class="text-red-500">*</span></label>
                <input name="name" required placeholder="Nguyễn Văn A"
                       class="mt-1 w-full rounded-lg border-[#B8D2E4] text-sm focus:border-[#2D82C4] focus:ring-[#2D82C4]">
            </div>
            <div>
                <label class="text-xs font-bold text-[#52637A]">Email</label>
                <input name="email" type="email" placeholder="email@example.com"
                       class="mt-1 w-full rounded-lg border-[#B8D2E4] text-sm focus:border-[#2D82C4] focus:ring-[#2D82C4]">
            </div>
            <div>
                <label class="text-xs font-bold text-[#52637A]">Số điện thoại</label>
                <input name="phone" placeholder="09xx xxx xxx"
                       class="mt-1 w-full rounded-lg border-[#B8D2E4] text-sm focus:border-[#2D82C4] focus:ring-[#2D82C4]">
            </div>
            <div>
                <label class="text-xs font-bold text-[#52637A]">Ngày sinh <span class="text-red-500">*</span></label>
                <input name="date_of_birth" type="date" required
                       class="mt-1 w-full rounded-lg border-[#B8D2E4] text-sm focus:border-[#2D82C4] focus:ring-[#2D82C4]">
            </div>
            <div class="flex items-end">
                <button class="w-full rounded-lg bg-[#2D82C4] px-4 py-2.5 text-sm font-bold text-white hover:bg-[#23689C] transition">
                    Tạo khách hàng
                </button>
            </div>
        </form>
    </section>

    {{-- TÌM KIẾM --}}
    <form method="GET" class="flex gap-2">
        <div class="relative flex-1">
            <div class="pointer-events-none absolute inset-y-0 left-3.5 flex items-center">
                <svg class="h-4 w-4 text-[#6FA6CF]" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-4.35-4.35m1.35-5.15a6.5 6.5 0 1 1-13 0 6.5 6.5 0 0 1 13 0Z"/>
                </svg>
            </div>
            <input name="q" value="{{ request('q') }}"
                   placeholder="Tìm theo tên, email hoặc số điện thoại…"
                   class="w-full rounded-xl border border-[#B8D2E4] bg-[#F7FBFE] py-3 pl-10 pr-4 text-sm text-[#102A43] placeholder:text-[#8A9BB0] focus:border-[#2D82C4] focus:outline-none focus:ring-2 focus:ring-[#2D82C4]/20">
        </div>
        <button class="rounded-xl bg-[#2D82C4] px-5 py-3 text-sm font-bold text-white hover:bg-[#23689C] transition">Tìm</button>
    </form>

    {{-- BẢNG DANH SÁCH KHÁCH HÀNG --}}
    <section class="overflow-hidden rounded-2xl border border-[#D7E8F3] bg-white shadow-sm" aria-label="Danh sách khách hàng">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="border-b border-[#E8F1F6] bg-[#F7FBFE] text-left text-xs uppercase tracking-wide text-[#52637A]">
                    <tr>
                        <th class="px-5 py-3.5">Mã KH</th>
                        <th class="px-5 py-3.5">Khách hàng</th>
                        <th class="px-5 py-3.5">Liên hệ</th>
                        <th class="px-5 py-3.5">Ngày sinh</th>
                        <th class="px-5 py-3.5">Trạng thái TK</th>
                        <th class="px-5 py-3.5">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#EEF4F8]">
                    @forelse($customers as $customer)
                    <tr class="hover:bg-[#F7FBFE] transition">
                        <td class="px-5 py-4 text-xs font-mono text-[#52637A]">
                            {{ $customer->customerProfile?->customer_code ?? '—' }}
                        </td>
                        <td class="px-5 py-4">
                            <p class="font-bold text-[#102A43]">{{ $customer->name }}</p>
                        </td>
                        <td class="px-5 py-4 text-[#52637A]">
                            <p class="text-sm">{{ $customer->email }}</p>
                            <p class="text-xs">{{ $customer->phone ?? '—' }}</p>
                        </td>
                        <td class="px-5 py-4 text-xs text-[#52637A]">
                            {{ $customer->customerProfile?->date_of_birth?->format('d/m/Y') ?? '—' }}
                        </td>
                        <td class="px-5 py-4">
                            @if($customer->email_verified_at)
                                <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-bold text-emerald-800">Đã xác thực</span>
                            @else
                                <span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-bold text-amber-800">Chờ xác thực</span>
                            @endif
                        </td>
                        <td class="px-5 py-4">
                            <div class="flex flex-wrap gap-2">
                                <a href="{{ route('staff.customers.profile', $customer) }}"
                                   class="rounded-lg bg-[#2D82C4] px-3 py-2 text-xs font-bold text-white hover:bg-[#23689C] transition">
                                    Xem hồ sơ khách hàng
                                </a>
                                {{-- Cập nhật số điện thoại nhanh --}}
                                <form method="POST" action="{{ route('staff.customers.update', $customer) }}" class="flex gap-1.5">
                                    @csrf @method('PUT')
                                    <input type="hidden" name="name"          value="{{ $customer->name }}">
                                    <input type="hidden" name="email"         value="{{ $customer->email }}">
                                    <input type="hidden" name="date_of_birth" value="{{ $customer->customerProfile?->date_of_birth?->format('Y-m-d') }}">
                                    <input name="phone" value="{{ $customer->phone }}"
                                           placeholder="Số điện thoại"
                                           class="w-32 rounded-lg border-[#B8D2E4] text-xs focus:border-[#2D82C4] focus:ring-[#2D82C4]">
                                    <button class="rounded-lg border border-[#2D82C4] px-3 py-1.5 text-xs font-bold text-[#2D82C4] hover:bg-[#EAF4FB] transition">
                                        Lưu
                                    </button>
                                </form>
                                @if(!$customer->email_verified_at && $customer->email)
                                <form method="POST" action="{{ route('staff.customers.resend-activation', $customer) }}">
                                    @csrf
                                    <button class="rounded-lg border border-amber-300 px-3 py-2 text-xs font-bold text-amber-700 hover:bg-amber-50 transition">
                                        Gửi lại kích hoạt
                                    </button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-5 py-12 text-center text-sm text-[#718096]">
                            Không tìm thấy khách hàng.
                            @if(request('q'))
                                <a href="{{ route('staff.appointments.book') }}" class="ml-1 font-bold text-[#2D82C4] hover:underline">Đặt lịch hộ khách mới →</a>
                            @endif
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($customers->hasPages())
        <div class="border-t border-[#EEF4F8] px-5 py-4">
            {{ $customers->links() }}
        </div>
        @endif
    </section>
</div>
@endsection
