@extends('layouts.admin')

@section('title', 'Quản lý tài khoản')

@php
    use App\Support\AdminPalette;

    $roleLabels = ['admin' => 'Quản trị viên', 'staff' => 'Nhân viên', 'specialist' => 'Chuyên viên', 'customer' => 'Khách hàng'];
    $statusLabels = ['ACTIVE' => 'Đang hoạt động', 'LOCKED' => 'Tạm khóa', 'DISABLED' => 'Đã vô hiệu hóa', 'PENDING_VERIFICATION' => 'Chờ xác thực email'];
@endphp

@section('content')
<div class="mx-auto max-w-[1600px] space-y-6 px-4 py-6 sm:px-6 lg:px-8" x-data="adminPanel()">

    {{-- ═══════════════ HEADER ═══════════════ --}}
    <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
        <div>
            <p class="text-[11px] font-extrabold uppercase tracking-[0.16em] text-[#6FA6CF]">Phân hệ tài khoản &amp; phân quyền</p>
            <h1 class="mt-1 text-2xl font-black tracking-tight text-[#102A43] sm:text-3xl">Quản lý tài khoản</h1>
            <p class="mt-2 max-w-3xl text-sm leading-relaxed text-[#52637A]">
                Tạo tài khoản, gán vai trò và khóa/mở khóa. Mỗi lần thay đổi quyền hoặc trạng thái đều được ghi vào
                nhật ký kiểm toán kèm lý do. Hệ thống luôn giữ ít nhất một quản trị viên đang hoạt động.
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.roles') }}"
               class="inline-flex items-center gap-2 rounded-lg border border-[#B8D2E4] bg-white px-4 py-2.5 text-sm font-bold text-[#2D82C4] transition hover:bg-[#EAF4FB]">
                Vai trò &amp; quyền
            </a>
            <button type="button"
                    @click="toggle('create')"
                    class="inline-flex items-center gap-2 rounded-lg bg-[#2D82C4] px-4 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-[#23689C]">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                </svg>
                Thêm tài khoản
            </button>
        </div>
    </div>

    {{-- ═══════════════ THẺ TỔNG QUAN ═══════════════ --}}
    <section aria-label="Tổng quan tài khoản" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach([
            ['label' => 'Quản trị viên', 'value' => $roleCounts->get('admin', 0), 'color' => AdminPalette::forRole('admin')],
            ['label' => 'Nhân viên', 'value' => $roleCounts->get('staff', 0), 'color' => AdminPalette::forRole('staff')],
            ['label' => 'Chuyên viên', 'value' => $roleCounts->get('specialist', 0), 'color' => AdminPalette::forRole('specialist')],
            ['label' => 'Khách hàng', 'value' => $roleCounts->get('customer', 0), 'color' => AdminPalette::forRole('customer')],
        ] as $card)
            <div class="rounded-2xl border border-[#D7E8F3] bg-white p-5 shadow-[0_4px_18px_rgb(35_111_168/0.05)]">
                <div class="flex items-center gap-2">
                    <span class="h-2.5 w-2.5 rounded-full" style="background-color: {{ $card['color'] }}" aria-hidden="true"></span>
                    <p class="text-[11px] font-extrabold uppercase tracking-[0.1em] text-[#78889B]">{{ $card['label'] }}</p>
                </div>
                <p class="mt-2.5 text-3xl font-black tabular-nums text-[#102A43]">{{ number_format($card['value']) }}</p>
            </div>
        @endforeach
    </section>

    {{-- ═══════════════ FORM TẠO TÀI KHOẢN ═══════════════ --}}
    <section class="rounded-2xl border border-[#D7E8F3] bg-white p-5 shadow-[0_4px_18px_rgb(35_111_168/0.05)] sm:p-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-lg font-black text-[#102A43]">Tạo tài khoản mới</h2>
                <p class="mt-1 text-xs text-[#718096]">
                    Email trùng sẽ bị từ chối — hãy tra cứu danh sách trước khi tạo.
                </p>
            </div>
            <button type="button"
                    @click="toggle('create')"
                    class="inline-flex items-center gap-1.5 rounded-lg border border-[#B8D2E4] px-3 py-2 text-xs font-bold text-[#52637A] transition hover:bg-[#F7FBFE]">
                <span x-text="isOpen('create') ? 'Ẩn biểu mẫu' : 'Mở biểu mẫu'">Mở biểu mẫu</span>
                <svg class="h-3.5 w-3.5 transition-transform" :class="{ 'rotate-180': isOpen('create') }" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/>
                </svg>
            </button>
        </div>

        <form x-show="isOpen('create')" x-cloak x-transition.opacity.duration.150ms
              method="POST" action="{{ route('admin.users.store') }}"
              class="mt-5 grid gap-4 border-t border-[#EEF4F8] pt-5 sm:grid-cols-2 xl:grid-cols-3">
            @csrf

            <label class="text-xs font-bold text-[#52637A]">
                Họ và tên <span class="text-red-500">*</span>
                <input name="name" required minlength="2" maxlength="100" value="{{ old('name') }}"
                       placeholder="Nguyễn Văn A"
                       class="mt-1.5 w-full rounded-lg border border-[#D7E8F3] px-3 py-2.5 text-sm">
            </label>

            <label class="text-xs font-bold text-[#52637A]">
                Email <span class="text-red-500">*</span>
                <input name="email" type="email" required maxlength="255" value="{{ old('email') }}"
                       placeholder="ten@congty.vn"
                       class="mt-1.5 w-full rounded-lg border border-[#D7E8F3] px-3 py-2.5 text-sm">
            </label>

            <label class="text-xs font-bold text-[#52637A]">
                Số điện thoại
                <input name="phone" maxlength="20" value="{{ old('phone') }}"
                       placeholder="09xx xxx xxx"
                       class="mt-1.5 w-full rounded-lg border border-[#D7E8F3] px-3 py-2.5 text-sm">
            </label>

            <label class="text-xs font-bold text-[#52637A]">
                Vai trò <span class="text-red-500">*</span>
                <select name="role" required class="mt-1.5 w-full rounded-lg border border-[#D7E8F3] px-3 py-2.5 text-sm">
                    @foreach($roleLabels as $value => $label)
                        <option value="{{ $value }}" @selected(old('role') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>

            <label class="text-xs font-bold text-[#52637A]">
                Trạng thái <span class="text-red-500">*</span>
                <select name="status" required class="mt-1.5 w-full rounded-lg border border-[#D7E8F3] px-3 py-2.5 text-sm">
                    @foreach($statusLabels as $value => $label)
                        <option value="{{ $value }}" @selected(old('status', 'ACTIVE') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>

            <div class="flex items-end">
                <button type="submit" class="w-full rounded-lg bg-[#2D82C4] px-4 py-2.5 text-sm font-bold text-white transition hover:bg-[#23689C]">
                    Tạo tài khoản
                </button>
            </div>
        </form>
    </section>

    {{-- ═══════════════ BỘ LỌC ═══════════════ --}}
    <section class="rounded-2xl border border-[#D7E8F3] bg-white p-4 shadow-[0_4px_18px_rgb(35_111_168/0.05)]">
        <form method="GET" class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <label class="text-xs font-bold text-[#52637A] xl:col-span-2">
                Tìm theo tên, email hoặc số điện thoại
                <input name="q" value="{{ request('q') }}" maxlength="100" placeholder="Nhập từ khoá…"
                       class="mt-1.5 w-full rounded-lg border border-[#D7E8F3] px-3 py-2.5 text-sm">
            </label>

            <label class="text-xs font-bold text-[#52637A]">
                Vai trò
                <select name="role" class="mt-1.5 w-full rounded-lg border border-[#D7E8F3] px-3 py-2.5 text-sm">
                    <option value="">Tất cả vai trò</option>
                    @foreach($roleLabels as $value => $label)
                        <option value="{{ $value }}" @selected(request('role') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>

            <label class="text-xs font-bold text-[#52637A]">
                Trạng thái
                <select name="status" class="mt-1.5 w-full rounded-lg border border-[#D7E8F3] px-3 py-2.5 text-sm">
                    <option value="">Tất cả trạng thái</option>
                    @foreach($statusLabels as $value => $label)
                        <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>

            <div class="flex flex-wrap items-center gap-2 sm:col-span-2 xl:col-span-4">
                <button type="submit" class="rounded-lg bg-[#2D82C4] px-4 py-2.5 text-sm font-bold text-white transition hover:bg-[#23689C]">
                    Lọc danh sách
                </button>
                @if(request()->query())
                    <a href="{{ route('admin.users') }}" class="rounded-lg border border-[#B8D2E4] px-4 py-2.5 text-sm font-bold text-[#52637A] transition hover:bg-[#F7FBFE]">
                        Xoá bộ lọc
                    </a>
                @endif
                <span class="ml-auto text-xs font-semibold text-[#8A9BB0]">{{ $users->total() }} tài khoản phù hợp</span>
            </div>
        </form>
    </section>

    {{-- ═══════════════ DANH SÁCH ═══════════════ --}}
    <section class="overflow-hidden rounded-2xl border border-[#D7E8F3] bg-white shadow-[0_4px_18px_rgb(35_111_168/0.05)]">
        <div class="overflow-x-auto">
            <table class="min-w-[1100px] text-left text-sm">
                <thead class="bg-[#EDF6FC] text-[11px] uppercase tracking-wide text-[#49647D]">
                    <tr>
                        <th class="whitespace-nowrap px-5 py-3 font-bold">Tài khoản</th>
                        <th class="whitespace-nowrap px-5 py-3 font-bold">Liên hệ</th>
                        <th class="whitespace-nowrap px-5 py-3 font-bold">Vai trò</th>
                        <th class="whitespace-nowrap px-5 py-3 font-bold">Trạng thái</th>
                        <th class="whitespace-nowrap px-5 py-3 font-bold">Ngày tạo</th>
                        <th class="whitespace-nowrap px-5 py-3 font-bold">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#EEF4F8]">
                    @forelse($users as $user)
                        <tr class="align-top transition hover:bg-[#F3F9FD]">
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-xs font-black uppercase text-white"
                                          style="background-color: {{ AdminPalette::forRole($user->role) }}">
                                        {{ mb_substr($user->name ?? '?', 0, 1) }}
                                    </span>
                                    <div class="min-w-0">
                                        <p class="font-bold text-[#102A43]">{{ $user->name }}</p>
                                        <p class="font-mono text-[11px] text-[#8A9BB0]">#{{ $user->id }}</p>
                                    </div>
                                </div>
                            </td>

                            <td class="px-5 py-4">
                                <p class="whitespace-nowrap text-sm text-[#334E68]">{{ $user->email }}</p>
                                <p class="whitespace-nowrap text-xs text-[#8A9BB0]">{{ $user->phone ?: 'Chưa có số điện thoại' }}</p>
                            </td>

                            <td class="px-5 py-4">
                                <span class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full px-2.5 py-1 text-[11px] font-extrabold"
                                      style="background-color: {{ AdminPalette::forRole($user->role) }}1A; color: {{ AdminPalette::forRole($user->role) }}">
                                    {{ $roleLabels[$user->role] ?? $user->role }}
                                </span>
                            </td>

                            <td class="px-5 py-4">
                                <x-status-chip type="account" :status="$user->status" />
                            </td>

                            <td class="whitespace-nowrap px-5 py-4 text-xs font-semibold text-[#718096]">
                                {{ $user->created_at?->format('d/m/Y') }}
                            </td>

                            <td class="px-5 py-4">
                                <div class="flex flex-wrap items-center gap-2">
                                    <button type="button"
                                            @click="toggle('edit-{{ $user->id }}')"
                                            class="rounded-lg border border-[#B8D2E4] px-3 py-2 text-xs font-bold text-[#52637A] transition hover:bg-[#F7FBFE]"
                                            :aria-expanded="isOpen('edit-{{ $user->id }}').toString()">
                                        <span x-text="isOpen('edit-{{ $user->id }}') ? 'Đóng' : 'Cập nhật'">Cập nhật</span>
                                    </button>

                                    @if($user->status === 'ACTIVE')
                                        <x-modal-confirm
                                            id="lock-user-{{ $user->id }}"
                                            title="Khóa tài khoản {{ $user->name }}"
                                            description="Toàn bộ phiên đăng nhập hiện tại sẽ bị thu hồi ngay. Tài khoản không thể đăng nhập cho tới khi được mở khóa."
                                            confirm-label="Khóa tài khoản"
                                            :require-reason="true"
                                            reason-label="Lý do khóa"
                                            :action="route('admin.users.status', $user)">
                                            <x-slot:trigger>
                                                <button type="button" class="rounded-lg border border-red-200 px-3 py-2 text-xs font-bold text-red-700 transition hover:bg-red-50">
                                                    Khóa
                                                </button>
                                            </x-slot:trigger>
                                        </x-modal-confirm>
                                    @else
                                        <x-modal-confirm
                                            id="unlock-user-{{ $user->id }}"
                                            title="Mở khóa tài khoản {{ $user->name }}"
                                            description="Tài khoản sẽ hoạt động trở lại và người dùng có thể đăng nhập ngay."
                                            confirm-label="Mở khóa"
                                            confirm-class="bg-emerald-600 hover:bg-emerald-700 focus:ring-emerald-500 text-white"
                                            :require-reason="true"
                                            reason-label="Lý do mở khóa"
                                            :action="route('admin.users.status', $user)">
                                            <x-slot:trigger>
                                                <button type="button" class="rounded-lg border border-emerald-200 px-3 py-2 text-xs font-bold text-emerald-700 transition hover:bg-emerald-50">
                                                    Mở khóa
                                                </button>
                                            </x-slot:trigger>
                                            <input type="hidden" name="status" value="ACTIVE">
                                        </x-modal-confirm>
                                    @endif
                                </div>

                                {{-- Form cập nhật --}}
                                <div x-show="isOpen('edit-{{ $user->id }}')" x-cloak x-transition.opacity.duration.150ms
                                     class="mt-3 rounded-xl border border-[#D7E8F3] bg-[#F7FBFE] p-4">
                                    <form method="POST" action="{{ route('admin.users.update', $user) }}" class="grid gap-3 sm:grid-cols-2">
                                        @csrf
                                        @method('PUT')

                                        <label class="text-xs font-bold text-[#52637A]">
                                            Họ và tên
                                            <input name="name" required maxlength="100" value="{{ $user->name }}"
                                                   class="mt-1.5 w-full rounded-lg border border-[#D7E8F3] bg-white px-3 py-2 text-sm">
                                        </label>

                                        <label class="text-xs font-bold text-[#52637A]">
                                            Email
                                            <input name="email" type="email" required maxlength="255" value="{{ $user->email }}"
                                                   class="mt-1.5 w-full rounded-lg border border-[#D7E8F3] bg-white px-3 py-2 text-sm">
                                        </label>

                                        <label class="text-xs font-bold text-[#52637A]">
                                            Vai trò
                                            <select name="role" class="mt-1.5 w-full rounded-lg border border-[#D7E8F3] bg-white px-3 py-2 text-sm">
                                                @foreach($roleLabels as $value => $label)
                                                    <option value="{{ $value }}" @selected($user->role === $value)>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </label>

                                        <label class="text-xs font-bold text-[#52637A]">
                                            Trạng thái
                                            <select name="status" class="mt-1.5 w-full rounded-lg border border-[#D7E8F3] bg-white px-3 py-2 text-sm">
                                                @foreach($statusLabels as $value => $label)
                                                    <option value="{{ $value }}" @selected($user->status === $value)>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </label>

                                        <div class="flex flex-wrap items-center gap-2 sm:col-span-2">
                                            <button type="submit" class="rounded-lg bg-[#2D82C4] px-4 py-2.5 text-sm font-bold text-white transition hover:bg-[#23689C]">
                                                Lưu thay đổi
                                            </button>
                                            <button type="submit"
                                                    form="reset-password-{{ $user->id }}"
                                                    class="rounded-lg border border-[#B8D2E4] bg-white px-4 py-2.5 text-sm font-bold text-[#52637A] transition hover:bg-[#EAF4FB]">
                                                Gửi liên kết đặt lại mật khẩu
                                            </button>
                                            <span class="text-[11px] text-[#8A9BB0]">Thay đổi quyền được ghi vào nhật ký kiểm toán.</span>
                                        </div>
                                    </form>

                                    <form id="reset-password-{{ $user->id }}" method="POST"
                                          action="{{ route('admin.users.password-reset', $user) }}" class="hidden">
                                        @csrf
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-16 text-center">
                                <p class="text-sm font-bold text-[#102A43]">Không có tài khoản phù hợp bộ lọc.</p>
                                <p class="mt-1 text-xs text-[#718096]">Thử xoá bộ lọc hoặc tạo tài khoản mới.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($users->hasPages())
            <div class="border-t border-[#E8F1F6] px-5 py-4">{{ $users->links() }}</div>
        @endif
    </section>
</div>
@endsection
