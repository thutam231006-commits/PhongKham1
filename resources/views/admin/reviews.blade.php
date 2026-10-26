@extends('layouts.admin')

@section('title', 'Kiểm duyệt đánh giá')

@php
    use App\Support\AdminPalette;

    $statusLabels = [
        'PENDING_REVIEW' => 'Chờ kiểm duyệt',
        'VISIBLE' => 'Đang hiển thị',
        'HIDDEN' => 'Đã ẩn',
        'RESTORED' => 'Đã khôi phục',
        'REPORTED' => 'Đã báo cáo',
    ];

    $filterTabs = [
        '' => 'Tất cả',
        'PENDING_REVIEW' => 'Chờ duyệt',
        'HIDDEN' => 'Đã ẩn',
        'VISIBLE' => 'Đang hiển thị',
    ];
@endphp

@section('content')
<div class="mx-auto max-w-[1600px] space-y-6 px-4 py-6 sm:px-6 lg:px-8">

    {{-- ═══════════════ HEADER ═══════════════ --}}
    <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
        <div>
            <p class="text-[11px] font-extrabold uppercase tracking-[0.16em] text-[#6FA6CF]">Phân hệ kiểm duyệt</p>
            <h1 class="mt-1 text-2xl font-black tracking-tight text-[#102A43] sm:text-3xl">Kiểm duyệt đánh giá</h1>
            <p class="mt-2 max-w-3xl text-sm leading-relaxed text-[#52637A]">
                Đánh giá tiêu cực <strong class="font-bold text-[#334E68]">không tự động</strong> bị coi là vi phạm.
                Cần phân biệt phản hồi hợp lệ với xúc phạm, tiết lộ dữ liệu cá nhân, spam hoặc quảng cáo trái phép.
                Khi ẩn, bắt buộc chọn lý do và lưu lại người xử lý, thời điểm.
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            @foreach($filterTabs as $value => $label)
                <a href="{{ route('admin.reviews', array_filter(['status' => $value])) }}"
                   class="rounded-lg px-3.5 py-2 text-xs font-bold transition
                          {{ request('status') === $value
                              ? 'bg-[#2D82C4] text-white'
                              : 'border border-[#B8D2E4] bg-white text-[#52637A] hover:bg-[#EAF4FB]' }}">
                    {{ $label }}
                    <span class="ml-1 opacity-70">{{ $value === '' ? $statusCounts->sum() : ($statusCounts->get($value, 0)) }}</span>
                </a>
            @endforeach
        </div>
    </div>

    {{-- ═══════════════ DANH SÁCH + CHI TIẾT ═══════════════ --}}
    <div class="grid gap-6 xl:grid-cols-5">

        {{-- Cột trái: danh sách đánh giá --}}
        <section class="overflow-hidden rounded-2xl border border-[#D7E8F3] bg-white shadow-[0_4px_18px_rgb(35_111_168/0.05)] xl:col-span-2"
                 x-data="adminPanel()">
            <div class="border-b border-[#E8F1F6] px-5 py-4">
                <h2 class="text-base font-black text-[#102A43]">Hàng đợi đánh giá</h2>
                <p class="mt-0.5 text-xs text-[#718096]">{{ $reviews->total() }} đánh giá phù hợp bộ lọc.</p>
            </div>

            <ul class="max-h-[720px] divide-y divide-[#EEF4F8] overflow-y-auto">
                @forelse($reviews as $review)
                    <li>
                        <button type="button"
                                @click="toggle('review-{{ $review->id }}')"
                                :aria-expanded="isOpen('review-{{ $review->id }}').toString()"
                                class="block w-full px-5 py-4 text-left transition hover:bg-[#F3F9FD]">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-bold text-[#102A43]">{{ $review->specialistProfile->user->name }}</p>
                                    <p class="mt-0.5 text-xs text-[#8A9BB0]">{{ $review->created_at?->format('d/m/Y H:i') }}</p>
                                </div>
                                <span class="shrink-0 rounded-full px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-wide"
                                      style="background-color: {{ AdminPalette::forReviewStatus($review->status) }}1A; color: {{ AdminPalette::forReviewStatus($review->status) }}">
                                    {{ $statusLabels[$review->status] ?? $review->status }}
                                </span>
                            </div>

                            <p class="mt-2 line-clamp-2 text-sm text-[#52637A]">{{ $review->comment ?: 'Không có nhận xét.' }}</p>

                            <p class="mt-2 text-xs font-bold text-[#B45309]" aria-label="Điểm đánh giá {{ $review->rating }} trên 5 sao">
                                <span aria-hidden="true">{{ str_repeat('★', $review->rating) }}{{ str_repeat('☆', max(0, 5 - $review->rating)) }}</span>
                                <span class="ml-1.5 text-[#52637A]">{{ $review->rating }}/5</span>
                            </p>
                        </button>

                        {{-- Chi tiết mở rộng --}}
                        <div x-show="isOpen('review-{{ $review->id }}')" x-cloak x-transition.opacity.duration.150ms
                             class="border-t border-[#EEF4F8] bg-[#F7FBFE] px-5 py-4">
                            <dl class="space-y-2.5 text-xs">
                                <div class="flex justify-between gap-3">
                                    <dt class="font-bold text-[#8A9BB0]">Mã lịch</dt>
                                    <dd class="font-mono font-bold text-[#2D82C4]">{{ $review->appointment->appointment_code }}</dd>
                                </div>
                                <div class="flex justify-between gap-3">
                                    <dt class="font-bold text-[#8A9BB0]">Dịch vụ</dt>
                                    <dd class="text-right font-semibold text-[#334E68]">{{ $review->appointment->service->name }}</dd>
                                </div>
                                <div class="flex justify-between gap-3">
                                    <dt class="font-bold text-[#8A9BB0]">Thời điểm tư vấn</dt>
                                    <dd class="whitespace-nowrap font-semibold text-[#334E68]">{{ $review->appointment->start_at->format('d/m/Y H:i') }}</dd>
                                </div>
                            </dl>

                            <div class="mt-3 rounded-lg border border-[#E4EFF7] bg-white p-3">
                                <p class="text-[10px] font-extrabold uppercase tracking-wide text-[#8A9BB0]">Nội dung gốc</p>
                                <p class="mt-1 text-sm leading-relaxed text-[#334E68]">{{ $review->comment ?: 'Không có nhận xét.' }}</p>
                            </div>

                            @if($review->moderated_at)
                                <p class="mt-2 text-[11px] text-[#8A9BB0]">
                                    Đã xử lý {{ $review->moderated_at->format('d/m/Y H:i') }}
                                    @if($review->moderation_reason)
                                        · Lý do: {{ $review->moderation_reason }}
                                    @endif
                                </p>
                            @endif

                            <form method="POST" action="{{ route('admin.reviews.update', $review) }}" class="mt-4 space-y-3">
                                @csrf
                                @method('PUT')

                                <label class="block text-xs font-bold text-[#52637A]">
                                    Quyết định
                                    <select name="status" class="mt-1.5 w-full rounded-lg border border-[#D7E8F3] bg-white px-3 py-2 text-sm">
                                        @foreach($statusLabels as $value => $label)
                                            <option value="{{ $value }}" @selected($review->status === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </label>

                                <label class="block text-xs font-bold text-[#52637A]">
                                    Lý do xử lý
                                    <span class="font-normal text-[#8A9BB0]">(bắt buộc khi ẩn đánh giá)</span>
                                    <textarea name="reason" rows="2" maxlength="500"
                                              placeholder="Ví dụ: chứa thông tin cá nhân của khách khác…"
                                              class="mt-1.5 w-full rounded-lg border border-[#D7E8F3] bg-white px-3 py-2 text-sm">{{ $review->moderation_reason }}</textarea>
                                </label>

                                <button type="submit" class="w-full rounded-lg bg-[#2D82C4] px-4 py-2.5 text-sm font-bold text-white transition hover:bg-[#23689C]">
                                    Lưu quyết định
                                </button>
                            </form>
                        </div>
                    </li>
                @empty
                    <li class="px-5 py-16 text-center">
                        <p class="text-sm font-bold text-[#102A43]">Không có đánh giá phù hợp bộ lọc.</p>
                        <p class="mt-1 text-xs text-[#718096]">Hàng đợi kiểm duyệt đang trống.</p>
                    </li>
                @endforelse
            </ul>

            @if($reviews->hasPages())
                <div class="border-t border-[#E8F1F6] px-5 py-4">{{ $reviews->links() }}</div>
            @endif
        </section>

        {{-- Cột phải: hướng dẫn xử lý --}}
        <section class="rounded-2xl border border-[#D7E8F3] bg-white p-5 shadow-[0_4px_18px_rgb(35_111_168/0.05)] sm:p-6 xl:col-span-3">
            <h2 class="text-lg font-black text-[#102A43]">Tiêu chuẩn xử lý đánh giá</h2>
            <p class="mt-1 text-xs text-[#718096]">Áp dụng theo thứ tự từ trên xuống.</p>

            <ol class="mt-5 space-y-3">
                @foreach([
                    ['title' => 'Phản hồi hợp lệ', 'body' => 'Góp ý về chuyên môn, dịch vụ, thái độ điều trị — kể cả đánh giá thấp điểm. Giữ nguyên (VISIBLE).', 'tone' => 'emerald'],
                    ['title' => 'Xúc phạm, spam, quảng cáo trái phép', 'body' => 'Ẩn (HIDDEN) và ghi rõ lý do để đối chiếu khi cần.', 'tone' => 'red'],
                    ['title' => 'Tiết lộ dữ liệu cá nhân', 'body' => 'Ẩn (HIDDEN) ngay. Nội dung gốc được giữ nguyên phục vụ kiểm toán, không sửa nội dung.', 'tone' => 'amber'],
                    ['title' => 'Cần thêm thông tin', 'body' => 'Chuyển cấp xử lý bằng cách đặt trạng thái PENDING_REVIEW và ghi chú nội bộ.', 'tone' => 'blue'],
                ] as $index => $rule)
                    <li class="flex gap-3 rounded-xl border border-[#E4EFF7] bg-[#F7FBFE] p-4">
                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-[#2D82C4] text-xs font-black text-white">{{ $index + 1 }}</span>
                        <div>
                            <p class="text-sm font-bold text-[#102A43]">{{ $rule['title'] }}</p>
                            <p class="mt-1 text-xs leading-relaxed text-[#52637A]">{{ $rule['body'] }}</p>
                        </div>
                    </li>
                @endforeach
            </ol>

            <div class="mt-5 rounded-xl border border-[#D7E8F3] bg-[#EAF4FB] p-4">
                <p class="text-[11px] font-extrabold uppercase tracking-wide text-[#23689C]">Lưu ý về dữ liệu</p>
                <ul class="mt-2 space-y-1.5 text-xs leading-relaxed text-[#334E68]">
                    <li class="flex gap-2"><span aria-hidden="true">•</span> Không chỉnh sửa nội dung đánh giá gốc — chỉ thay đổi trạng thái hiển thị.</li>
                    <li class="flex gap-2"><span aria-hidden="true">•</span> Mọi quyết định đều lưu người xử lý, thời điểm và lý do.</li>
                    <li class="flex gap-2"><span aria-hidden="true">•</span> Đánh giá đã ẩn có thể khôi phục (RESTORED) nếu khiếu nại được xác nhận là không có cơ sở.</li>
                </ul>
            </div>
        </section>
    </div>
</div>
@endsection
