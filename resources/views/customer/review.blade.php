{{--
    SCR-CUS-07 — Đánh giá của tôi (FR-REVIEW-001/002)
    ===================================================
    Căn cứ: docs/ThietKeGiaoDien.md §SCR-CUS-07
    Quy định:
      - Danh sách các buổi COMPLETED: "Chưa đánh giá — còn X ngày" / "Đã đánh giá".
      - Form đánh giá: chọn 1–5 sao (bắt buộc), nhận xét (≤ 1000 ký tự).
      - Lưu ý: "Đánh giá của bạn sẽ được hiển thị công khai ẩn danh trên trang chuyên viên (BR-047)."
--}}

@extends('layouts.app')

@section('title', 'Đánh Giá Của Tôi — Mind Guide')

@section('breadcrumb')
<nav aria-label="Đường dẫn điều hướng" class="py-2">
    <ol class="flex items-center gap-2 text-xs text-[#4A5568]">
        <li>
            <a href="{{ url('/') }}" class="hover:text-[#1D4B43] hover:underline transition">Trang chủ</a>
        </li>
        <li aria-hidden="true" class="text-gray-400">/</li>
        <li>
            <a href="{{ route('customer.dashboard') }}" class="hover:text-[#1D4B43] hover:underline transition">Khách hàng</a>
        </li>
        <li aria-hidden="true" class="text-gray-400">/</li>
        <li class="font-semibold text-[#1A202C]" aria-current="page">Đánh giá & Phản hồi</li>
    </ol>
</nav>
@endsection

@section('content')
<div class="mx-auto max-w-screen-xl px-4 py-8 sm:px-6 lg:px-8 space-y-8"
     x-data="{ selectedRating: 5, hoverRating: 5, selectedAppId: '{{ $appointmentId ?? ($pendingReviews->first()->id ?? '') }}' }">

    {{-- Tiêu đề trang --}}
    <div>
        <span class="inline-flex items-center gap-1.5 rounded-full bg-[#E8F0EC] px-3.5 py-1 text-xs font-bold text-[#1D4B43]">
            <span class="h-2 w-2 rounded-full bg-[#709782]"></span>
            Chia sẻ trải nghiệm thân chủ (BR-047)
        </span>
        <h1 class="mt-2 text-3xl font-black tracking-tight text-[#0F172A]">
            Đánh giá buổi tư vấn
        </h1>
        <p class="mt-1 text-xs sm:text-sm text-[#4A5568]">
            Ý kiến đóng góp chân thực của bạn là động lực để đội ngũ chuyên gia không ngừng nâng cao chất lượng phụng sự.
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

    {{-- Layout 2 cột: Form gửi đánh giá & Lịch sử nhận xét --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">

        {{-- CỘT TRÁI (7/12): BIỂU MẪU ĐÁNH GIÁ MỚI --}}
        <div class="lg:col-span-7 space-y-6">

            <div class="rounded-2xl border border-gray-200 bg-white p-6 sm:p-8 shadow-xs space-y-6">
                <div class="border-b border-gray-100 pb-4">
                    <h2 class="text-lg font-bold text-[#0F172A]">Gửi đánh giá cho chuyên gia</h2>
                    <p class="text-xs text-[#4A5568] mt-0.5">
                        Lưu ý: Đánh giá chỉ được gửi một lần và sẽ được hiển thị công khai ở trạng thái <strong>ẩn danh</strong> (BR-047).
                    </p>
                </div>

                @if($pendingReviews->isNotEmpty())
                    <form method="POST" action="{{ route('customer.review.submit') }}" class="space-y-5">
                        @csrf

                        {{-- Chọn buổi hẹn cần đánh giá --}}
                        <div>
                            <label for="appointment_id" class="block text-xs font-bold text-[#0F172A] mb-1">
                                Chọn buổi tư vấn đã hoàn thành <span class="text-red-500">*</span>
                            </label>
                            <select id="appointment_id"
                                    name="appointment_id"
                                    x-model="selectedAppId"
                                    required
                                    class="w-full rounded-xl border border-gray-300 bg-[#F7FAFC] px-4 py-2.5 text-sm text-[#0F172A] font-medium focus:border-[#1D4B43] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#1D4B43]/20">
                                @foreach($pendingReviews as $p)
                                    <option value="{{ $p->id }}" {{ ($appointmentId == $p->id) ? 'selected' : '' }}>
                                        {{ $p->code }} — {{ $p->service_name }} ({{ $p->specialist_name }}, ngày {{ $p->date }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Chọn số sao 1 - 5 (Interactive Star Rating) --}}
                        <div>
                            <label class="block text-xs font-bold text-[#0F172A] mb-2">
                                Mức độ hài lòng của bạn <span class="text-red-500">*</span>
                            </label>
                            <input type="hidden" name="rating" :value="selectedRating">

                            <div class="flex items-center gap-2">
                                <template x-for="star in [1, 2, 3, 4, 5]" :key="star">
                                    <button type="button"
                                            @click="selectedRating = star"
                                            @mouseenter="hoverRating = star"
                                            @mouseleave="hoverRating = selectedRating"
                                            class="p-1 focus:outline-none transition-transform hover:scale-110">
                                        <svg class="h-8 w-8"
                                             :class="(hoverRating >= star) ? 'text-amber-400 fill-amber-400' : 'text-gray-300 fill-transparent'"
                                             viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z"/>
                                        </svg>
                                    </button>
                                </template>

                                <span class="ml-2 text-xs font-bold text-[#1D4B43]"
                                      x-text="['Rất không hài lòng', 'Chưa hài lòng', 'Bình thường', 'Hài lòng', 'Rất hài lòng & Biết ơn'][selectedRating - 1]"></span>
                            </div>
                        </div>

                        {{-- Nhận xét chi tiết --}}
                        <div>
                            <label for="comment" class="block text-xs font-bold text-[#0F172A] mb-1">
                                Chia sẻ cảm nhận của bạn (Tùy chọn, tối đa 1000 ký tự)
                            </label>
                            <textarea id="comment"
                                      name="comment"
                                      rows="5"
                                      maxlength="1000"
                                      placeholder="Hãy chia sẻ về sự lắng nghe của chuyên gia, cảm xúc sau buổi gặp hoặc những tiến bộ bạn cảm nhận được..."
                                      class="w-full rounded-xl border border-gray-300 bg-[#F7FAFC] p-4 text-xs sm:text-sm text-[#0F172A] focus:border-[#1D4B43] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#1D4B43]/20 leading-relaxed"></textarea>
                        </div>

                        {{-- Lưu ý ẩn danh --}}
                        <div class="rounded-xl border border-[#709782]/30 bg-[#E8F0EC]/50 p-4 text-xs text-[#334155] flex items-start gap-2.5">
                            <svg class="h-4 w-4 text-[#1D4B43] flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M12 2a10 10 0 110 20A10 10 0 0112 2z"/>
                            </svg>
                            <span>
                                Theo quy tắc bảo mật <strong>BR-047</strong>, tên thật của bạn sẽ <strong>hoàn toàn được ẩn danh</strong> khi hiển thị đánh giá trên trang công khai của chuyên viên.
                            </span>
                        </div>

                        <button type="submit"
                                class="rounded-xl bg-[#1D4B43] px-8 py-3 text-sm font-bold text-white hover:bg-[#14352F] transition shadow-xs">
                            Gửi đánh giá
                        </button>
                    </form>
                @else
                    <div class="p-8 text-center bg-[#F7FAFC] rounded-xl border border-dashed border-gray-300">
                        <p class="text-sm font-semibold text-[#0F172A]">Hiện bạn không có buổi tư vấn nào cần đánh giá.</p>
                        <p class="text-xs text-[#4A5568] mt-1">Các buổi tham vấn hoàn thành trong vòng 30 ngày sẽ xuất hiện tại đây.</p>
                    </div>
                @endif
            </div>

        </div>

        {{-- CỘT PHẢI (5/12): CÁC ĐÁNH GIÁ ĐÃ GỬI TRƯỚC ĐÂY --}}
        <div class="lg:col-span-5 space-y-6">

            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-xs space-y-4">
                <h3 class="text-base font-bold text-[#0F172A] border-b border-gray-100 pb-3">
                    Đánh giá bạn đã gửi
                </h3>

                <div class="space-y-4">
                    @forelse($completedReviews as $cr)
                        <div class="p-4 rounded-xl border border-gray-100 bg-[#F7FAFC] text-xs space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-[#0F172A]">{{ $cr->specialist_name }}</span>
                                <span class="text-gray-400">{{ $cr->created_at }}</span>
                            </div>

                            <p class="text-[#709782] font-semibold">{{ $cr->service_name }}</p>

                            <div class="flex text-amber-500 text-sm">
                                @for($i = 0; $i < $cr->rating; $i++) ★ @endfor
                            </div>

                            <p class="text-[#334155] leading-relaxed italic">
                                "{{ $cr->comment }}"
                            </p>
                        </div>
                    @empty
                        <p class="text-xs text-gray-400 italic text-center py-4">Bạn chưa gửi đánh giá nào.</p>
                    @endforelse
                </div>
            </div>

        </div>

    </div>

</div>
@endsection
