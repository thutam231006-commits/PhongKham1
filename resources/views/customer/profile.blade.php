{{--
    SCR-CUS-02 — Hồ sơ cá nhân khách hàng (FR-CUSTOMER-001)
    =========================================================
    Căn cứ: docs/ThietKeGiaoDien.md §SCR-CUS-02
    Bố cục:
      - Thông tin chỉ đọc: mã khách, ngày sinh, ngày tham gia.
      - Thông tin sửa được: họ tên (2–100), số điện thoại (9–15 số), email.
      - Khối đổi mật khẩu tài khoản.
      - Khối cài đặt tùy chọn nhận thông báo.
--}}

@extends('layouts.app')

@section('title', 'Hồ Sơ Cá Nhân — Mind Guide')

@section('breadcrumb')
<nav aria-label="Đường dẫn điều hướng" class="py-2">
    <ol class="flex items-center gap-2 text-xs text-[#4A5568]">
        <li>
            <a href="{{ url('/') }}" class="hover:text-[#2D82C4] hover:underline transition">Trang chủ</a>
        </li>
        <li aria-hidden="true" class="text-gray-400">/</li>
        <li>
            <a href="{{ route('customer.dashboard') }}" class="hover:text-[#2D82C4] hover:underline transition">Khách hàng</a>
        </li>
        <li aria-hidden="true" class="text-gray-400">/</li>
        <li class="font-semibold text-[#1A202C]" aria-current="page">Hồ sơ cá nhân</li>
    </ol>
</nav>
@endsection

@section('content')
<div class="mx-auto max-w-screen-xl px-4 py-8 sm:px-6 lg:px-8 space-y-8"
     x-data="{ activeTab: 'info' }">

    {{-- Tiêu đề trang --}}
    <div>
        <span class="inline-flex items-center gap-1.5 rounded-full bg-[#EAF4FB] px-3.5 py-1 text-xs font-bold text-[#2D82C4]">
            <span class="h-2 w-2 rounded-full bg-[#6FA6CF]"></span>
            Tài khoản thân chủ
        </span>
        <h1 class="mt-2 text-3xl font-black tracking-tight text-[#0F172A]">
            Hồ sơ & Cài đặt cá nhân
        </h1>
        <p class="mt-1 text-xs sm:text-sm text-[#4A5568]">
            Quản lý thông tin định danh, bảo mật tài khoản và tùy chọn nhận thông báo lịch hẹn.
        </p>
    </div>

    {{-- Thông báo session --}}
    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-xs font-semibold text-emerald-800 flex items-center gap-2">
            <svg class="h-4 w-4 text-emerald-600 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    {{-- Hiển thị lỗi validate --}}
    @if ($errors->any())
        <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-xs text-red-800">
            <ul class="list-disc pl-5 space-y-0.5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- THANH TAB ĐIỀU HƯỚNG CÀI ĐẶT --}}
    <div class="border-b border-gray-200">
        <nav class="-mb-px flex space-x-6 text-sm font-semibold">
            <button type="button"
                    @click="activeTab = 'info'"
                    class="pb-4 px-1 border-b-2 transition"
                    :class="activeTab === 'info' ? 'border-[#2D82C4] text-[#2D82C4] font-bold' : 'border-transparent text-[#4A5568] hover:text-[#0F172A]'">
                Thông tin tài khoản
            </button>

            <button type="button"
                    @click="activeTab = 'security'"
                    class="pb-4 px-1 border-b-2 transition"
                    :class="activeTab === 'security' ? 'border-[#2D82C4] text-[#2D82C4] font-bold' : 'border-transparent text-[#4A5568] hover:text-[#0F172A]'">
                Đổi mật khẩu & Bảo mật
            </button>

            <button type="button"
                    @click="activeTab = 'notification'"
                    class="pb-4 px-1 border-b-2 transition"
                    :class="activeTab === 'notification' ? 'border-[#2D82C4] text-[#2D82C4] font-bold' : 'border-transparent text-[#4A5568] hover:text-[#0F172A]'">
                Cài đặt thông báo
            </button>

            <button type="button"
                    @click="activeTab = 'consultations'"
                    class="pb-4 px-1 border-b-2 transition"
                    :class="activeTab === 'consultations' ? 'border-[#2D82C4] text-[#2D82C4] font-bold' : 'border-transparent text-[#4A5568] hover:text-[#0F172A]'">
                Kết quả tư vấn
                @if (count($consultations ?? []) > 0)
                    <span class="ml-1 rounded-full bg-[#EAF4FB] px-1.5 py-0.5 text-[10px] font-bold text-[#2D82C4]">{{ count($consultations) }}</span>
                @endif
            </button>
        </nav>
    </div>

    {{-- ═══ TAB 1: THÔNG TIN TÀI KHOẢN (SCR-CUS-02) ═══ --}}
    <div x-show="activeTab === 'info'" class="space-y-6">
        <div class="rounded-2xl border border-gray-200 bg-white p-6 sm:p-8 shadow-xs">
            <h2 class="text-base font-bold text-[#0F172A] pb-3 border-b border-gray-100">
                Thông tin hồ sơ khách hàng
            </h2>

            {{-- Thông tin chỉ đọc (Readonly) theo đặc tả SCR-CUS-02 --}}
            <div class="mt-6 grid grid-cols-1 sm:grid-cols-3 gap-4 p-4 rounded-xl bg-[#F5FAFE] border border-gray-200/80 text-xs">
                <div>
                    <span class="text-[#4A5568] block">Mã khách hàng:</span>
                    <strong class="font-mono text-sm font-bold text-[#2D82C4] mt-0.5 block">{{ $user->code }}</strong>
                </div>

                <div>
                    <span class="text-[#4A5568] block">Ngày sinh:</span>
                    <strong class="text-sm font-bold text-[#0F172A] mt-0.5 block">{{ date('d/m/Y', strtotime($user->dob)) }}</strong>
                    <span class="text-[10px] text-gray-400">(Từ 18 tuổi - chỉ đọc)</span>
                </div>

                <div>
                    <span class="text-[#4A5568] block">Ngày tham gia:</span>
                    <strong class="text-sm font-bold text-[#0F172A] mt-0.5 block">{{ $user->created_at }}</strong>
                </div>
            </div>

            {{-- Form cập nhật thông tin chỉnh sửa được --}}
            <form method="POST" action="{{ route('customer.profile.update') }}" class="mt-6 space-y-4">
                @csrf

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="name" class="block text-xs font-bold text-[#0F172A] mb-1">
                            Họ và tên của bạn <span class="text-red-500">*</span>
                        </label>
                        <input type="text"
                               id="name"
                               name="name"
                               value="{{ old('name', $user->name) }}"
                               required
                               minlength="2"
                               maxlength="100"
                               class="w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-[#0F172A] focus:border-[#2D82C4] focus:ring-2 focus:ring-[#2D82C4]/20">
                    </div>

                    <div>
                        <label for="email" class="block text-xs font-bold text-[#0F172A] mb-1">
                            Địa chỉ Email <span class="text-red-500">*</span>
                        </label>
                        <input type="email"
                               id="email"
                               name="email"
                               value="{{ old('email', $user->email) }}"
                               required
                               class="w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-[#0F172A] focus:border-[#2D82C4] focus:ring-2 focus:ring-[#2D82C4]/20">
                    </div>

                    <div>
                        <label for="phone" class="block text-xs font-bold text-[#0F172A] mb-1">
                            Số điện thoại nhận tin nhắn
                        </label>
                        <input type="tel"
                               id="phone"
                               name="phone"
                               value="{{ old('phone', $user->phone) }}"
                               placeholder="0912 345 678"
                               class="w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-[#0F172A] focus:border-[#2D82C4] focus:ring-2 focus:ring-[#2D82C4]/20">
                    </div>

                    {{-- Giới tính — dùng để điền thông tin trong email nhắc lịch --}}
                    <div>
                        <label for="gender" class="block text-xs font-bold text-[#0F172A] mb-1">
                            Giới tính
                        </label>
                        <select id="gender"
                                name="gender"
                                class="w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-[#0F172A] focus:border-[#2D82C4] focus:ring-2 focus:ring-[#2D82C4]/20">
                            <option value="">Chưa cập nhật</option>
                            @foreach(\App\Support\CustomerGender::options() as $value => $label)
                                <option value="{{ $value }}" @selected(old('gender', $user->gender) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <label for="address" class="block text-xs font-bold text-[#0F172A] mb-1">
                        Địa chỉ
                    </label>
                    <input type="text"
                           id="address"
                           name="address"
                           value="{{ old('address', $user->address) }}"
                           placeholder="Số nhà, đường, phường/xã, quận/huyện, tỉnh/thành"
                           maxlength="255"
                           class="w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-[#0F172A] focus:border-[#2D82C4] focus:ring-2 focus:ring-[#2D82C4]/20">
                    <p class="mt-1 text-[11px] text-gray-500">
                        Giới tính và địa chỉ giúp phòng khám liên hệ chính xác trong email nhắc lịch.
                    </p>
                </div>

                <div class="pt-4 border-t border-gray-100 flex justify-end">
                    <button type="submit"
                            class="rounded-xl bg-[#2D82C4] px-6 py-2.5 text-sm font-bold text-white hover:bg-[#23689C] transition shadow-xs">
                        Lưu thay đổi hồ sơ
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ═══ TAB 2: ĐỔI MẬT KHẨU & BẢO MẬT ═══ --}}
    <div x-show="activeTab === 'security'" x-cloak class="space-y-6">
        <div class="rounded-2xl border border-gray-200 bg-white p-6 sm:p-8 shadow-xs max-w-2xl">
            <h2 class="text-base font-bold text-[#0F172A] pb-3 border-b border-gray-100">
                Đổi mật khẩu tài khoản
            </h2>

            <form method="POST" action="{{ route('customer.password.change') }}" class="mt-6 space-y-4">
                @csrf

                <div>
                    <label for="current_password" class="block text-xs font-bold text-[#0F172A] mb-1">
                        Mật khẩu hiện tại <span class="text-red-500">*</span>
                    </label>
                    <input type="password"
                           id="current_password"
                           name="current_password"
                           required
                           placeholder="••••••••"
                           class="w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-[#0F172A] focus:border-[#2D82C4] focus:ring-2 focus:ring-[#2D82C4]/20">
                </div>

                <div>
                    <label for="password" class="block text-xs font-bold text-[#0F172A] mb-1">
                        Mật khẩu mới <span class="text-red-500">*</span> (tối thiểu 8 ký tự)
                    </label>
                    <input type="password"
                           id="password"
                           name="password"
                           required
                           minlength="8"
                           placeholder="••••••••"
                           class="w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-[#0F172A] focus:border-[#2D82C4] focus:ring-2 focus:ring-[#2D82C4]/20">
                </div>

                <div>
                    <label for="password_confirmation" class="block text-xs font-bold text-[#0F172A] mb-1">
                        Xác nhận mật khẩu mới <span class="text-red-500">*</span>
                    </label>
                    <input type="password"
                           id="password_confirmation"
                           name="password_confirmation"
                           required
                           minlength="8"
                           placeholder="••••••••"
                           class="w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-[#0F172A] focus:border-[#2D82C4] focus:ring-2 focus:ring-[#2D82C4]/20">
                </div>

                <div class="pt-4 border-t border-gray-100 flex justify-end">
                    <button type="submit"
                            class="rounded-xl bg-[#2D82C4] px-6 py-2.5 text-sm font-bold text-white hover:bg-[#23689C] transition shadow-xs">
                        Cập nhật mật khẩu mới
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ═══ TAB 3: CÀI ĐẶT THÔNG BÁO ═══ --}}
    <div x-show="activeTab === 'notification'" x-cloak class="space-y-6">
        <div class="rounded-2xl border border-gray-200 bg-white p-6 sm:p-8 shadow-xs max-w-2xl">
            <h2 class="text-base font-bold text-[#0F172A] pb-3 border-b border-gray-100">
                Tùy chọn nhận thông báo
            </h2>

            <div class="mt-6 space-y-4 text-xs">
                <label class="flex items-start gap-3 p-3.5 rounded-xl border border-gray-200 bg-[#F5FAFE] cursor-pointer">
                    <input type="checkbox" checked class="mt-1 rounded text-[#2D82C4] focus:ring-[#2D82C4]">
                    <div>
                        <span class="font-bold text-[#0F172A] text-sm block">Nhắc lịch hẹn qua Email (24h trước giờ hẹn)</span>
                        <p class="text-[#4A5568] mt-0.5">Nhận thư thông báo và đường dẫn phòng họp trực tuyến tự động.</p>
                    </div>
                </label>

                <label class="flex items-start gap-3 p-3.5 rounded-xl border border-gray-200 bg-[#F5FAFE] cursor-pointer">
                    <input type="checkbox" checked class="mt-1 rounded text-[#2D82C4] focus:ring-[#2D82C4]">
                    <div>
                        <span class="font-bold text-[#0F172A] text-sm block">Cập nhật trạng thái thanh toán & hoàn tiền</span>
                        <p class="text-[#4A5568] mt-0.5">Thông báo khi giao dịch VietQR hoàn tất hoặc khi lệnh hoàn tiền được xác nhận.</p>
                    </div>
                </label>

                <label class="flex items-start gap-3 p-3.5 rounded-xl border border-gray-200 bg-[#F5FAFE] cursor-pointer">
                    <input type="checkbox" class="mt-1 rounded text-[#2D82C4] focus:ring-[#2D82C4]">
                    <div>
                        <span class="font-bold text-[#0F172A] text-sm block">Bài viết & Lời khuyên sức khỏe tinh thần</span>
                        <p class="text-[#4A5568] mt-0.5">Bản tin định kỳ chia sẻ các bài tập chánh niệm và quản lý căng thẳng từ chuyên gia.</p>
                    </div>
                </label>
            </div>
        </div>
    </div>

    {{-- ═══ TAB 4: KẾT QUẢ TƯ VẤN (SCR-CUS-02) ═══ --}}
    <div x-show="activeTab === 'consultations'" x-cloak class="space-y-6">
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50/70 p-4 sm:p-5 flex items-start gap-3.5 text-xs text-emerald-950">
            <svg class="h-5 w-5 text-emerald-700 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
            </svg>
            <div class="leading-relaxed">
                <strong class="font-bold">Bảo vệ quyền riêng tư:</strong> Nội dung dưới đây do chuyên viên phụ trách soạn riêng để gửi bạn.
                Hồ sơ chuyên môn nội bộ của buổi tư vấn được mã hóa và chỉ chuyên viên phụ trách được truy cập.
            </div>
        </div>

        @forelse($consultations as $consultation)
            <article class="rounded-2xl border border-gray-200 bg-white p-6 sm:p-8 shadow-xs space-y-5">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-gray-100 pb-4">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="font-mono text-xs font-black text-[#2D82C4] bg-[#EAF4FB] px-3 py-1 rounded-lg">
                                {{ $consultation->appointment_code }}
                            </span>
                            <span class="text-xs text-[#4A5568]">
                                Ngày tư vấn: <strong class="text-[#0F172A]">{{ $consultation->date }}</strong> ({{ $consultation->time_slot }})
                            </span>
                        </div>
                        <h3 class="text-lg font-bold text-[#0F172A] mt-2">{{ $consultation->service_name }}</h3>
                        <p class="text-xs text-[#6FA6CF] font-semibold mt-0.5">Chuyên gia: {{ $consultation->specialist_name }}</p>
                    </div>

                    @if ($consultation->follow_up_needed)
                        <span class="inline-flex items-center gap-1.5 self-start sm:self-auto rounded-full bg-amber-50 px-3 py-1 text-xs font-bold text-amber-800 border border-amber-200">
                            <span aria-hidden="true">↻</span>
                            Cần theo dõi tiếp
                        </span>
                    @endif
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-xs sm:text-sm">
                    <div class="rounded-xl border border-gray-200/80 bg-[#F5FAFE] p-4 space-y-1.5">
                        <span class="text-xs font-bold text-[#2D82C4] uppercase tracking-wider block">1. Nhận định từ chuyên gia</span>
                        <p class="text-[#334155] leading-relaxed">
                            {{ $consultation->summary ?: 'Chuyên viên chưa gửi tóm lược cho buổi tư vấn này.' }}
                        </p>
                    </div>

                    <div class="rounded-xl border border-emerald-100 bg-emerald-50/40 p-4 space-y-1.5">
                        <span class="text-xs font-bold text-emerald-900 uppercase tracking-wider block">2. Bài tập & Hướng rèn luyện</span>
                        <p class="text-[#334155] leading-relaxed">
                            {{ $consultation->recommendation ?: 'Chuyên viên chưa gửi khuyến nghị cho buổi tư vấn này.' }}
                        </p>
                    </div>
                </div>
            </article>
        @empty
            <div class="rounded-2xl border border-dashed border-gray-300 bg-white p-12 text-center">
                <p class="text-sm font-semibold text-[#0F172A]">Chưa có kết quả tư vấn nào.</p>
                <p class="text-xs text-[#4A5568] mt-1">
                    Sau khi hoàn thành buổi tư vấn, chuyên viên sẽ gửi tóm lược và khuyến nghị tại đây.
                </p>
                <a href="{{ route('customer.medical-history') }}" class="mt-4 inline-block rounded-xl bg-[#2D82C4] px-5 py-2.5 text-xs font-bold text-white hover:bg-[#23689C] transition">
                    Xem lịch sử tư vấn
                </a>
            </div>
        @endforelse
    </div>

</div>
@endsection
