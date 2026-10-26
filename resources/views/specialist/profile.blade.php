@extends('layouts.specialist')

@section('title', 'Cá nhân')

@section('content')
    <div class="mx-auto max-w-screen-xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">

        <div>
            <p class="text-xs font-bold uppercase tracking-widest text-[#6FA6CF]">Tài khoản</p>
            <h1 class="mt-1 text-2xl font-black tracking-tight text-[#102A43] sm:text-3xl">Cá nhân</h1>
            <p class="mt-2 max-w-3xl text-sm text-[#52637A]">
                Thông tin công khai giúp khách hàng chọn đúng chuyên viên. Những thay đổi tại đây hiển thị trên
                trang chuyên viên công khai.
            </p>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">

            {{-- ── Thẻ hồ sơ ── --}}
            <aside class="space-y-4 lg:col-span-1">
                <div class="rounded-2xl border border-[#D7E8F3] bg-white p-5 text-center">
                    @if($profile->avatar_url)
                        <img src="{{ $profile->avatar_url }}" alt="Ảnh đại diện của {{ $profile->user->name }}"
                             class="mx-auto h-24 w-24 rounded-full border-4 border-[#EAF4FB] object-cover">
                    @else
                        <span class="mx-auto flex h-24 w-24 items-center justify-center rounded-full bg-[#EAF4FB] text-3xl font-black uppercase text-[#2D82C4]">
                            {{ mb_substr($profile->user->name, 0, 1) }}
                        </span>
                    @endif

                    <h2 class="mt-3 text-lg font-black text-[#102A43]">{{ $profile->user->name }}</h2>
                    <p class="text-sm font-semibold text-[#2D82C4]">{{ $profile->title }}</p>
                    @if($profile->expertise)
                        <p class="mt-1 text-xs text-[#718096]">{{ $profile->expertise }}</p>
                    @endif

                    <dl class="mt-5 grid grid-cols-2 gap-3 border-t border-[#E8F1F6] pt-4 text-left">
                        <div>
                            <dt class="text-[11px] font-bold uppercase tracking-wide text-[#8CA3B8]">Kinh nghiệm</dt>
                            <dd class="mt-0.5 text-sm font-bold text-[#102A43]">{{ $profile->years_of_experience ? $profile->years_of_experience.' năm' : '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-[11px] font-bold uppercase tracking-wide text-[#8CA3B8]">Ca đã xong</dt>
                            <dd class="mt-0.5 text-sm font-bold tabular-nums text-[#102A43]">{{ number_format($profile->completed_appointments) }}</dd>
                        </div>
                        <div>
                            <dt class="text-[11px] font-bold uppercase tracking-wide text-[#8CA3B8]">Đánh giá</dt>
                            <dd class="mt-0.5 text-sm font-bold text-[#102A43]">{{ number_format((float) ($profile->average_rating ?? 0), 1) }} / 5</dd>
                        </div>
                        <div>
                            <dt class="text-[11px] font-bold uppercase tracking-wide text-[#8CA3B8]">Trạng thái</dt>
                            <dd class="mt-0.5">
                                <span class="rounded-full border px-2 py-0.5 text-[11px] font-bold
                                    {{ $profile->status === 'ACTIVE' ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-gray-200 bg-gray-50 text-gray-500' }}">
                                    {{ $profile->status === 'ACTIVE' ? 'Đang hoạt động' : 'Tạm ngừng' }}
                                </span>
                            </dd>
                        </div>
                    </dl>
                </div>

                <div class="rounded-2xl border border-[#D7E8F3] bg-white p-5">
                    <h2 class="text-sm font-black uppercase tracking-wide text-[#52637A]">Dịch vụ được phân công</h2>
                    @if($profile->services->isEmpty())
                        <p class="mt-2 text-xs text-[#8CA3B8]">Chưa có dịch vụ nào được gán. Vui lòng liên hệ Quản trị viên.</p>
                    @else
                        <ul class="mt-3 flex flex-wrap gap-2">
                            @foreach($profile->services as $service)
                                <li class="rounded-full border border-[#D7E8F3] bg-[#F7FBFE] px-3 py-1 text-[11px] font-semibold text-[#334155]">
                                    {{ $service->name }}
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>

                <div class="rounded-2xl border border-[#D7E8F3] bg-white p-5">
                    <h2 class="text-sm font-black uppercase tracking-wide text-[#52637A]">Liên kết nhanh</h2>
                    <ul class="mt-3 space-y-2 text-sm">
                        <li>
                            <a href="{{ route('specialist.schedule') }}" class="flex items-center justify-between rounded-lg px-3 py-2 font-semibold text-[#334155] transition hover:bg-[#F0F7FC] hover:text-[#2D82C4]">
                                Lịch làm việc
                                <span aria-hidden="true" class="text-[#B8D2E4]">→</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('specialist.appointments') }}" class="flex items-center justify-between rounded-lg px-3 py-2 font-semibold text-[#334155] transition hover:bg-[#F0F7FC] hover:text-[#2D82C4]">
                                Ca tư vấn
                                <span aria-hidden="true" class="text-[#B8D2E4]">→</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('password.change') }}" class="flex items-center justify-between rounded-lg px-3 py-2 font-semibold text-[#334155] transition hover:bg-[#F0F7FC] hover:text-[#2D82C4]">
                                Đổi mật khẩu
                                <span aria-hidden="true" class="text-[#B8D2E4]">→</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </aside>

            {{-- ── Form cập nhật ── --}}
            <section class="lg:col-span-2" aria-labelledby="profile-form-heading">
                <form method="POST" enctype="multipart/form-data" action="{{ route('specialist.profile.update') }}"
                      class="overflow-hidden rounded-2xl border border-[#D7E8F3] bg-white">
                    @csrf
                    @method('PUT')

                    <div class="border-b border-[#E8F1F6] px-6 py-5">
                        <h2 id="profile-form-heading" class="text-lg font-black text-[#102A43]">Thông tin công khai</h2>
                        <p class="mt-1 text-xs text-[#718096]">
                            Không đăng tải thông tin liên hệ cá nhân (số điện thoại, email riêng) tại đây.
                        </p>
                    </div>

                    <div class="space-y-5 px-6 py-6">

                        @if($errors->any())
                            <div role="alert" class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                                <ul class="list-disc space-y-1 pl-4">
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div class="grid gap-4 sm:grid-cols-2">
                            <label class="block">
                                <span class="text-xs font-bold text-[#334155]">Họ tên</span>
                                <input type="text" value="{{ $profile->user->name }}" disabled
                                       class="mt-1 w-full rounded-lg border-[#E8F1F6] bg-[#F7FBFE] text-sm text-[#52637A]">
                                <span class="mt-1 block text-[11px] text-[#8CA3B8]">Liên hệ Quản trị viên nếu cần đổi họ tên.</span>
                            </label>

                            <label class="block">
                                <span class="text-xs font-bold text-[#334155]">Email</span>
                                <input type="email" value="{{ $profile->user->email }}" disabled
                                       class="mt-1 w-full rounded-lg border-[#E8F1F6] bg-[#F7FBFE] text-sm text-[#52637A]">
                                <span class="mt-1 block text-[11px] text-[#8CA3B8]">Dùng để đăng nhập, không hiển thị công khai.</span>
                            </label>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <label class="block">
                                <span class="text-xs font-bold text-[#334155]">Chức danh *</span>
                                <input type="text" name="title" required maxlength="100"
                                       value="{{ old('title', $profile->title) }}"
                                       placeholder="Ví dụ: Chuyên viên tư vấn tâm lý"
                                       class="mt-1 w-full rounded-lg border-[#D7E8F3] text-sm focus:border-[#2D82C4] focus:ring-[#2D82C4]">
                            </label>

                            <label class="block">
                                <span class="text-xs font-bold text-[#334155]">Chuyên môn</span>
                                <input type="text" name="expertise" maxlength="255"
                                       value="{{ old('expertise', $profile->expertise) }}"
                                       placeholder="Ví dụ: Trị liệu lo âu, tham vấn gia đình"
                                       class="mt-1 w-full rounded-lg border-[#D7E8F3] text-sm focus:border-[#2D82C4] focus:ring-[#2D82C4]">
                            </label>
                        </div>

                        <label class="block">
                            <span class="text-xs font-bold text-[#334155]">Ảnh đại diện</span>
                            <input type="file" name="avatar" accept="image/*"
                                   class="mt-1 w-full rounded-lg border border-[#D7E8F3] p-2 text-sm file:mr-3 file:rounded-md file:border-0 file:bg-[#EAF4FB] file:px-3 file:py-1.5 file:text-xs file:font-bold file:text-[#2D82C4]">
                            <span class="mt-1 block text-[11px] text-[#8CA3B8]">Ảnh chân dung, tối đa 2 MB.</span>
                        </label>

                        <label class="block">
                            <span class="text-xs font-bold text-[#334155]">Giới thiệu</span>
                            <textarea name="bio" rows="7" maxlength="3000"
                                      placeholder="Giới thiệu ngắn về hướng tiếp cận và kinh nghiệm của bạn…"
                                      class="mt-1 w-full rounded-xl border-[#D7E8F3] text-sm leading-relaxed placeholder:text-[#B8D2E4] focus:border-[#2D82C4] focus:ring-[#2D82C4]">{{ old('bio', $profile->bio) }}</textarea>
                            <span class="mt-1 block text-[11px] text-[#8CA3B8]">Tối đa 3.000 ký tự.</span>
                        </label>
                    </div>

                    <div class="flex flex-col gap-2 border-t border-[#E8F1F6] bg-[#F7FBFE] px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
                        <p class="text-[11px] text-[#8CA3B8]">Thay đổi được áp dụng ngay trên trang chuyên viên công khai.</p>
                        <button type="submit"
                                class="inline-flex items-center justify-center rounded-lg bg-[#2D82C4] px-5 py-2.5 text-sm font-bold text-white transition hover:bg-[#23689C]">
                            Lưu hồ sơ
                        </button>
                    </div>
                </form>
            </section>
        </div>
    </div>
@endsection
