{{--
    Chính sách quyền riêng tư — Mind Guide
    ======================================
    Căn cứ: docs/ThietKeGiaoDien.md §1.7 Bảo mật và quyền riêng tư, NFR-PRV
--}}

@extends('layouts.app')

@section('title', 'Chính Sách Quyền Riêng Tư — Phòng Khám Tâm Lý Mind Guide')

@section('breadcrumb')
<nav aria-label="Đường dẫn điều hướng" class="py-2">
    <ol class="flex items-center gap-2 text-xs text-[#4A5568]">
        <li>
            <a href="{{ url('/') }}" class="hover:text-[#2D82C4] hover:underline transition">Trang chủ</a>
        </li>
        <li aria-hidden="true" class="text-gray-400">/</li>
        <li class="font-semibold text-[#1A202C]" aria-current="page">Chính sách quyền riêng tư</li>
    </ol>
</nav>
@endsection

@section('content')
<div class="mx-auto max-w-screen-xl px-4 py-8 sm:px-6 lg:px-8">
    <div class="max-w-4xl mx-auto bg-white p-6 sm:p-10 rounded-2xl border border-gray-200/80 shadow-xs space-y-8">

        <div>
            <span class="inline-flex items-center gap-1.5 rounded-full bg-[#EAF4FB] px-3.5 py-1 text-xs font-bold text-[#2D82C4] mb-3">
                <span class="h-2 w-2 rounded-full bg-[#6FA6CF]"></span>
                Cam kết y đức & Bảo vệ thân chủ
            </span>
            <h1 class="text-2xl sm:text-4xl font-black text-[#0F172A]">Chính Sách Quyền Riêng Tư</h1>
            <p class="text-xs text-[#4A5568] mt-1">Phiên bản 1.0 — Cập nhật lần cuối: Tháng 10/2026</p>
        </div>

        <div class="prose prose-slate max-w-none text-sm leading-relaxed text-[#334155] space-y-6">
            <section>
                <h2 class="text-lg font-bold text-[#0F172A] mb-2">1. Cam kết cốt lõi về bảo mật thông tin (§1.7)</h2>
                <p>
                    Tại Phòng khám Tâm lý Mind Guide, sự riêng tư và bảo mật của thân chủ là nguyên tắc đạo đức tối thượng.
                    Chúng tôi thấu hiểu rằng những trăn trở, tâm tư và dữ liệu tâm lý của bạn cần được bảo vệ bằng các tiêu chuẩn bảo mật khắt khe nhất.
                </p>
            </section>

            <section>
                <h2 class="text-lg font-bold text-[#0F172A] mb-2">2. Dữ liệu thu thập và nguyên tắc tối thiểu hóa (NFR-PRV-003)</h2>
                <p>
                    Khi bạn đăng ký tài khoản và đặt lịch, chúng tôi chỉ thu thập các thông tin cơ bản phục vụ việc quản lý lịch hẹn:
                </p>
                <ul class="list-disc pl-5 space-y-1">
                    <li>Họ và tên của bạn</li>
                    <li>Địa chỉ email để gửi thư xác nhận và thông báo lịch hẹn</li>
                    <li>Ngày sinh (để đảm bảo bạn từ đủ 18 tuổi theo quy định hành nghề tâm lý)</li>
                    <li>Số điện thoại (tùy chọn) để gửi tin nhắn nhắc lịch</li>
                </ul>
                <div class="mt-3 p-3.5 bg-amber-50 rounded-xl border border-amber-200 text-xs text-amber-900">
                    <strong>Nguyên tắc NFR-PRV-003:</strong> Mind Guide tuyệt đối <strong>không</strong> thu thập giới tính, địa chỉ nhà riêng, thông tin liên hệ khẩn cấp hoặc bệnh sử y tế tổng quát khi đăng ký tài khoản.
                </div>
            </section>

            <section>
                <h2 class="text-lg font-bold text-[#0F172A] mb-2">3. Quyền hạn tiếp cận hồ sơ tư vấn (§1.7 BR-049, BR-050)</h2>
                <p>
                    - Ghi chú trước buổi hẹn của khách và toàn bộ hồ sơ tham vấn chuyên môn được mã hóa trên hệ thống máy chủ.
                </p>
                <p class="mt-1">
                    - <strong>Chỉ chuyên viên tâm lý phụ trách trực tiếp:</strong> mới có quyền xem nội dung ghi chú và ghi chép hồ sơ của buổi hẹn.
                </p>
                <p class="mt-1">
                    - <strong>Nhân viên điều phối (Staff) và Quản trị viên (Admin):</strong> tuyệt đối <strong>không thể xem</strong> nội dung ghi chú của khách và nội dung buổi tư vấn.
                </p>
            </section>

            <section>
                <h2 class="text-lg font-bold text-[#0F172A] mb-2">4. Tính ẩn danh của đánh giá công khai (BR-047)</h2>
                <p>
                    Mọi đánh giá sao và nhận xét của khách hàng sau buổi tư vấn khi hiển thị công khai trên hồ sơ chuyên viên đều là <strong>ẩn danh 100%</strong>. Tên hoặc thông tin định danh của bạn sẽ không bao giờ xuất hiện trên trang công khai.
                </p>
            </section>
        </div>

    </div>
</div>
@endsection
