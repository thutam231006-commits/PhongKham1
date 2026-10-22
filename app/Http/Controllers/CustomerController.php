<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * CustomerController — Phân hệ Khách hàng (SCR-CUS-*)
 *
 * Căn cứ: docs/ThietKeGiaoDien.md §4 (SCR-CUS-01 đến SCR-CUS-07)
 */
class CustomerController extends Controller
{
    /**
     * SCR-CUS-01 — Tổng quan khách hàng (Dashboard)
     */
    public function dashboard(): View
    {
        $user = (object) [
            'id' => 101,
            'name' => 'Nguyễn Văn An',
            'email' => 'nguyenvanan@example.com',
            'phone' => '0912 345 678',
            'code' => 'KH-00101',
            'dob' => '15/08/1995',
            'created_at' => '10/01/2026',
        ];

        // Lịch hẹn sắp tới (tối đa 3 lịch đang hoạt động)
        $upcomingAppointments = collect([
            (object) [
                'id' => 201,
                'code' => 'MG24101',
                'service_name' => 'Tư vấn tâm lý cá nhân 1-1',
                'specialist_name' => 'ThS. Nguyễn Thị Mai',
                'specialist_title' => 'Chuyên viên Tâm lý học lâm sàng',
                'date' => date('Y-m-d', strtotime('+2 days')),
                'time_slot' => '09:30 - 10:30',
                'status' => 'CONFIRMED',
                'payment_status' => 'PAID',
                'payment_method' => 'BANK_QR',
                'amount' => 500000,
                'type' => 'ONLINE',
                'meeting_url' => 'https://meet.mindguide.vn/room/mg-24101',
            ],
            (object) [
                'id' => 202,
                'code' => 'MG24102',
                'service_name' => 'Tham vấn & Trị liệu lo âu, trầm cảm',
                'specialist_name' => 'TS. Trần Văn Hưng',
                'specialist_title' => 'Tiến sĩ Tâm lý học tham vấn',
                'date' => date('Y-m-d', strtotime('+5 days')),
                'time_slot' => '15:00 - 16:30',
                'status' => 'PENDING',
                'payment_status' => 'UNPAID',
                'payment_method' => 'BANK_QR',
                'amount' => 750000,
                'type' => 'OFFLINE',
                'location' => 'Phòng 201, Tầng 2 - Cơ sở Quận 1',
            ],
        ]);

        // Thông báo mới nhất (5 mục)
        $notifications = collect([
            (object) [
                'id' => 1,
                'title' => 'Nhắc lịch hẹn tư vấn',
                'content' => 'Bạn có lịch hẹn tư vấn tâm lý vào 09:30 Thứ Sáu tới cùng ThS. Nguyễn Thị Mai.',
                'time' => '10 phút trước',
                'is_read' => false,
            ],
            (object) [
                'id' => 2,
                'title' => 'Xác nhận thanh toán thành công',
                'content' => 'Lịch hẹn MG24101 đã được xác nhận thanh toán 500.000 đ qua VietQR.',
                'time' => '2 giờ trước',
                'is_read' => true,
            ],
            (object) [
                'id' => 3,
                'title' => 'Chuyên viên gửi lời dặn',
                'content' => 'ThS. Nguyễn Thị Mai đã cập nhật bài tập nhật ký cảm xúc trước buổi tham vấn.',
                'time' => '1 ngày trước',
                'is_read' => true,
            ],
        ]);

        $stats = [
            'total_appointments' => 4,
            'completed_appointments' => 2,
            'pending_reviews' => 1,
        ];

        return view('customer.dashboard', compact('user', 'upcomingAppointments', 'notifications', 'stats'));
    }

    /**
     * SCR-CUS-04 — Lịch hẹn của tôi
     */
    public function appointments(Request $request): View
    {
        $tab = $request->query('tab', 'upcoming');

        $allAppointments = $this->getSampleAppointments();

        // Lọc theo tab
        if ($tab === 'upcoming') {
            $appointments = $allAppointments->filter(fn ($item) => in_array($item->status, ['PENDING', 'CONFIRMED', 'RESCHEDULE_REQUESTED']))->values();
        } elseif ($tab === 'completed') {
            $appointments = $allAppointments->filter(fn ($item) => $item->status === 'COMPLETED')->values();
        } elseif ($tab === 'cancelled') {
            $appointments = $allAppointments->filter(fn ($item) => in_array($item->status, ['CANCELLED_BY_CUSTOMER', 'CANCELLED_BY_CLINIC', 'NO_SHOW']))->values();
        } else {
            $appointments = $allAppointments;
        }

        return view('customer.appointments', compact('appointments', 'tab'));
    }

    /**
     * SCR-CUS-05 — Chi tiết lịch hẹn
     */
    public function appointmentDetail(int|string $id): View
    {
        $all = $this->getSampleAppointments();
        $appointment = $all->firstWhere('id', (int) $id) ?? $all->first();

        // Lịch sử dòng thời gian trạng thái
        $timeline = [
            (object) ['status' => 'PENDING', 'label' => 'Khởi tạo lịch hẹn', 'time' => '10:15 12/10/2026', 'actor' => 'Khách hàng', 'desc' => 'Đặt lịch qua hệ thống trực tuyến'],
            (object) ['status' => 'CONFIRMED', 'label' => 'Đã xác nhận', 'time' => '10:18 12/10/2026', 'actor' => 'Hệ thống', 'desc' => 'Xác nhận thanh toán tự động qua VietQR'],
        ];

        return view('customer.appointment-detail', compact('appointment', 'timeline'));
    }

    /**
     * SCR-CUS-02 — Hồ sơ cá nhân
     */
    public function profile(): View
    {
        $user = (object) [
            'id' => 101,
            'name' => 'Nguyễn Văn An',
            'email' => 'nguyenvanan@example.com',
            'phone' => '0912 345 678',
            'code' => 'KH-00101',
            'dob' => '1995-08-15',
            'created_at' => '10/01/2026',
            'email_verified' => true,
        ];

        return view('customer.profile', compact('user'));
    }

    /**
     * Cập nhật hồ sơ cá nhân
     */
    public function updateProfile(Request $request)
    {
        $request->validate([
            'name' => 'required|string|min:2|max:100',
            'phone' => 'nullable|string|regex:/^[0-9+\s\-]{9,15}$/',
            'email' => 'required|email|max:255',
        ], [
            'name.required' => 'Họ và tên không được để trống (2-100 ký tự).',
            'email.required' => 'Email không được để trống.',
            'email.email' => 'Địa chỉ email không đúng định dạng.',
        ]);

        return back()->with('success', 'Hồ sơ cá nhân đã được cập nhật thành công.');
    }

    /**
     * Đổi mật khẩu tài khoản
     */
    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'password' => 'required|min:8|confirmed',
        ], [
            'current_password.required' => 'Vui lòng nhập mật khẩu hiện tại.',
            'password.required' => 'Vui lòng nhập mật khẩu mới.',
            'password.min' => 'Mật khẩu mới phải có tối thiểu 8 ký tự.',
            'password.confirmed' => 'Xác nhận mật khẩu mới không khớp.',
        ]);

        return back()->with('success', 'Đổi mật khẩu thành công. Mật khẩu mới có hiệu lực ngay lập tức.');
    }

    /**
     * Lịch sử tư vấn & Tóm tắt chuyên viên (Medical History / Consultation Notes)
     */
    public function medicalHistory(): View
    {
        $records = collect([
            (object) [
                'id' => 301,
                'appointment_code' => 'MG24089',
                'service_name' => 'Tư vấn tâm lý cá nhân 1-1',
                'specialist_name' => 'ThS. Nguyễn Thị Mai',
                'date' => '28/09/2026',
                'time_slot' => '09:00 - 10:00',
                'summary' => 'Thân chủ có biểu hiện căng thẳng mạn tính do áp lực công việc. Khả năng nhận diện cảm xúc tốt, đã tiếp thu bài tập thở 4-7-8.',
                'recommendation' => 'Duy trì ghi chép nhật ký biết ơn mỗi tối. Thực hành bài tập hít thở khi có dấu hiệu tim đập nhanh.',
                'homework' => 'Ghi lại 3 khoảnh khắc cảm thấy nhẹ nhõm trong ngày.',
            ],
            (object) [
                'id' => 302,
                'appointment_code' => 'MG24075',
                'service_name' => 'Đánh giá & Sàng lọc sức khỏe tinh thần tổng quát',
                'specialist_name' => 'ThS. Vũ Đức Minh',
                'date' => '10/09/2026',
                'time_slot' => '14:00 - 15:15',
                'summary' => 'Hoàn thành thang đo DASS-21. Điểm lo âu ở mức trung bình (12 điểm), điểm trầm cảm ở mức bình thường (4 điểm).',
                'recommendation' => 'Tập trung cân bằng đồng hồ sinh học, hạn chế màn hình điện thoại 1 giờ trước khi đi ngủ.',
                'homework' => 'Thiết lập thói quen đi bộ nhẹ 20 phút mỗi sáng.',
            ],
        ]);

        return view('customer.medical-history', compact('records'));
    }

    /**
     * SCR-CUS-07 — Đánh giá của tôi / Form viết nhận xét
     */
    public function review(Request $request): View
    {
        $appointmentId = $request->query('appointment_id');

        $pendingReviews = collect([
            (object) [
                'id' => 203,
                'code' => 'MG24095',
                'service_name' => 'Tham vấn & Trị liệu lo âu, trầm cảm',
                'specialist_name' => 'TS. Trần Văn Hưng',
                'specialist_title' => 'Tiến sĩ Tâm lý học tham vấn',
                'date' => '05/10/2026',
                'time_slot' => '15:00 - 16:30',
                'days_left' => 20,
            ],
        ]);

        $completedReviews = collect([
            (object) [
                'id' => 204,
                'code' => 'MG24089',
                'service_name' => 'Tư vấn tâm lý cá nhân 1-1',
                'specialist_name' => 'ThS. Nguyễn Thị Mai',
                'date' => '28/09/2026',
                'rating' => 5,
                'comment' => 'Chuyên viên rất tận tâm và lắng nghe sâu sắc. Tôi cảm thấy nhẹ nhõm hơn nhiều sau buổi hẹn.',
                'created_at' => '29/09/2026',
            ],
        ]);

        return view('customer.review', compact('pendingReviews', 'completedReviews', 'appointmentId'));
    }

    /**
     * Gửi đánh giá cho chuyên viên
     */
    public function submitReview(Request $request)
    {
        $request->validate([
            'appointment_id' => 'required',
            'rating' => 'required|integer|between:1,5',
            'comment' => 'nullable|string|max:1000',
        ], [
            'rating.required' => 'Vui lòng chọn số sao đánh giá (1-5 sao).',
            'rating.between' => 'Số sao đánh giá phải từ 1 đến 5.',
            'comment.max' => 'Nhận xét không được vượt quá 1000 ký tự.',
        ]);

        return back()->with('success', 'Cảm ơn bạn đã gửi đánh giá. Nhận xét của bạn sẽ được hiển thị ẩn danh để bảo vệ quyền riêng tư.');
    }

    /**
     * Hủy lịch hẹn
     */
    public function cancelAppointment(Request $request, int|string $id)
    {
        return back()->with('success', 'Lịch hẹn đã được hủy thành công. Hệ thống đã cập nhật trạng thái hủy.');
    }

    /**
     * Yêu cầu đổi lịch
     */
    public function rescheduleAppointment(Request $request, int|string $id)
    {
        return back()->with('success', 'Yêu cầu đổi lịch hẹn đã được gửi tới phòng khám để xử lý.');
    }

    // -------------------------------------------------------------------------
    // Private Helpers
    // -------------------------------------------------------------------------
    private function getSampleAppointments(): Collection
    {
        return collect([
            (object) [
                'id' => 201,
                'code' => 'MG24101',
                'service_name' => 'Tư vấn tâm lý cá nhân 1-1',
                'specialist_name' => 'ThS. Nguyễn Thị Mai',
                'specialist_title' => 'Chuyên viên Tâm lý học lâm sàng',
                'date' => date('Y-m-d', strtotime('+2 days')),
                'time_slot' => '09:30 - 10:30',
                'status' => 'CONFIRMED',
                'payment_status' => 'PAID',
                'payment_method' => 'BANK_QR',
                'amount' => 500000,
                'type' => 'ONLINE',
                'customer_note' => 'Cảm thấy mất ngủ kéo dài và thường xuyên hồi hộp khi bắt đầu ngày mới.',
                'location' => 'Phòng họp trực tuyến bảo mật',
                'meeting_url' => 'https://meet.mindguide.vn/room/mg-24101',
                'can_reschedule' => true,
                'reschedule_count' => 0,
            ],
            (object) [
                'id' => 202,
                'code' => 'MG24102',
                'service_name' => 'Tham vấn & Trị liệu lo âu, trầm cảm',
                'specialist_name' => 'TS. Trần Văn Hưng',
                'specialist_title' => 'Tiến sĩ Tâm lý học tham vấn',
                'date' => date('Y-m-d', strtotime('+5 days')),
                'time_slot' => '15:00 - 16:30',
                'status' => 'PENDING',
                'payment_status' => 'UNPAID',
                'payment_method' => 'BANK_QR',
                'amount' => 750000,
                'type' => 'OFFLINE',
                'customer_note' => 'Gặp khó khăn trong việc cân bằng công việc và cuộc sống hôn nhân.',
                'location' => 'Phòng 201, Tầng 2 - Cơ sở Quận 1, TP.HCM',
                'can_reschedule' => false,
                'reschedule_count' => 0,
            ],
            (object) [
                'id' => 203,
                'code' => 'MG24095',
                'service_name' => 'Tham vấn & Trị liệu lo âu, trầm cảm',
                'specialist_name' => 'TS. Trần Văn Hưng',
                'specialist_title' => 'Tiến sĩ Tâm lý học tham vấn',
                'date' => '05/10/2026',
                'time_slot' => '15:00 - 16:30',
                'status' => 'COMPLETED',
                'payment_status' => 'PAID',
                'payment_method' => 'BANK_QR',
                'amount' => 750000,
                'type' => 'OFFLINE',
                'customer_note' => null,
                'location' => 'Phòng 201, Tầng 2 - Cơ sở Quận 1',
                'can_reschedule' => false,
                'reschedule_count' => 0,
            ],
            (object) [
                'id' => 204,
                'code' => 'MG24089',
                'service_name' => 'Tư vấn tâm lý cá nhân 1-1',
                'specialist_name' => 'ThS. Nguyễn Thị Mai',
                'specialist_title' => 'Chuyên viên Tâm lý học lâm sàng',
                'date' => '28/09/2026',
                'time_slot' => '09:00 - 10:00',
                'status' => 'COMPLETED',
                'payment_status' => 'PAID',
                'payment_method' => 'CASH',
                'amount' => 500000,
                'type' => 'OFFLINE',
                'customer_note' => 'Tham vấn lần đầu tiên.',
                'location' => 'Phòng 102, Tầng 1 - Cơ sở Quận 1',
                'can_reschedule' => false,
                'reschedule_count' => 0,
            ],
            (object) [
                'id' => 205,
                'code' => 'MG24070',
                'service_name' => 'Tư vấn hôn nhân & Gia đình',
                'specialist_name' => 'ThS. Đỗ Thúy Hằng',
                'specialist_title' => 'Chuyên viên Trị liệu Gia đình',
                'date' => '15/09/2026',
                'time_slot' => '14:00 - 15:30',
                'status' => 'CANCELLED_BY_CUSTOMER',
                'payment_status' => 'REFUNDED',
                'payment_method' => 'BANK_QR',
                'amount' => 800000,
                'type' => 'ONLINE',
                'customer_note' => 'Bận công tác đột xuất.',
                'location' => 'Phòng họp trực tuyến',
                'can_reschedule' => false,
                'reschedule_count' => 0,
            ],
        ]);
    }
}
