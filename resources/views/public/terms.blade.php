{{--
    Điều khoản sử dụng dịch vụ — Mind Guide
    ========================================
    Căn cứ: docs/ThietKeGiaoDien.md §SCR-CUS-03, NFR-USA-003, FR-PAY-009/010
--}}

@extends('layouts.app')

@section('title', 'Điều Khoản Sử Dụng — Phòng Khám Tâm Lý Mind Guide')

@section('breadcrumb')
<nav aria-label="Đường dẫn điều hướng" class="py-2">
    <ol class="flex items-center gap-2 text-xs text-[#4A5568]">
        <li>
            <a href="{{ url('/') }}" class="hover:text-[#2D82C4] hover:underline transition">Trang chủ</a>
        </li>
        <li aria-hidden="true" class="text-gray-400">/</li>
        <li class="font-semibold text-[#1A202C]" aria-current="page">Điều khoản sử dụng</li>
    </ol>
</nav>
@endsection

@section('content')
<div class="mx-auto max-w-screen-xl px-4 py-8 sm:px-6 lg:px-8">
    <div class="max-w-4xl mx-auto bg-white p-6 sm:p-10 rounded-2xl border border-gray-200/80 shadow-xs space-y-8">

        <div>
            <span class="inline-flex items-center gap-1.5 rounded-full bg-[#EAF4FB] px-3.5 py-1 text-xs font-bold text-[#2D82C4] mb-3">
                <span class="h-2 w-2 rounded-full bg-[#6FA6CF]"></span>
                Quy định & Quyền lợi thân chủ
            </span>
            <h1 class="text-2xl sm:text-4xl font-black text-[#0F172A]">Điều Khoản Sử Dụng Dịch Vụ</h1>
            <p class="text-xs text-[#4A5568] mt-1">Áp dụng cho mọi khách hàng đặt lịch tại Phòng khám Tâm lý Mind Guide</p>
        </div>

        <div class="prose prose-slate max-w-none text-sm leading-relaxed text-[#334155] space-y-6">
            <section>
                <h2 class="text-lg font-bold text-[#0F172A] mb-2">1. Phạm vi dịch vụ và Cảnh báo an toàn (NFR-USA-003)</h2>
                <p>
                    Dịch vụ của Mind Guide là các buổi tham vấn, trị liệu và hỗ trợ sức khỏe tinh thần được cung cấp bởi các chuyên viên tâm lý có chuyên môn.
                </p>
                <div class="mt-3 p-4 bg-amber-50 rounded-xl border border-amber-200 text-xs text-amber-900 leading-relaxed">
                    <strong>Lưu ý quan trọng:</strong> Dịch vụ này <strong>không thay thế điều trị y tế khẩn cấp, kê đơn thuốc tâm thần hoặc can thiệp khủng hoảng đe dọa tính mạng</strong>. Trong tình huống nguy cấp, vui lòng liên hệ ngay 115 hoặc đường dây nóng 1900 1567.
                </div>
            </section>

            <section>
                <h2 class="text-lg font-bold text-[#0F172A] mb-2">2. Quy định đặt lịch và Thời gian có mặt</h2>
                <ul class="list-disc pl-5 space-y-1.5">
                    <li>Khách hàng cần đặt lịch hẹn trước giờ tư vấn tối thiểu 2 giờ và trong vòng 30 ngày.</li>
                    <li>Đối với hình thức thanh toán tiền mặt tại quầy, quý khách vui lòng <strong>có mặt trước giờ hẹn ít nhất 10 phút</strong> để hoàn tất thủ tục đón tiếp.</li>
                    <li>Thời gian buổi hẹn được tính đúng khung giờ đã đăng ký để đảm bảo quyền lợi cho các thân chủ kế tiếp.</li>
                </ul>
            </section>

            <section>
                <h2 class="text-lg font-bold text-[#0F172A] mb-2">3. Chính sách đổi lịch và Hủy hẹn (FR-PAY-009/010)</h2>
                <p>
                    Mind Guide áp dụng chính sách hủy lịch và hoàn tiền minh bạch 3 mốc:
                </p>
                <ul class="list-disc pl-5 space-y-1">
                    <li>Hủy trước giờ hẹn từ 24h trở lên: Được <strong>hoàn tiền 100%</strong> hoặc đổi sang khung giờ mới miễn phí (tối đa 2 lần).</li>
                    <li>Hủy trong khoảng 12h đến dưới 24h: Được <strong>hoàn tiền 50%</strong>.</li>
                    <li>Hủy dưới 12h hoặc vắng mặt (No-Show): <strong>Không hoàn lại tiền (0%)</strong>.</li>
                </ul>
            </section>
        </div>

    </div>
</div>
@endsection
