<x-mail::message>
# Xác nhận đặt lịch thành công

Xin chào **{{ $customerName }}**, phòng khám đã ghi nhận yêu cầu đặt lịch của bạn.

## Thông tin lịch hẹn

| | |
|---|---|
| **Mã lịch** | {{ $appointment->appointment_code }} |
| **Gói dịch vụ** | {{ $serviceName }} |
| **Chuyên viên** | {{ $specialistName }} |
| **Thời gian** | {{ $startAt }} – {{ $endAt }} |
| **Hình thức** | {{ $appointment->consultation_type === 'ONLINE' ? 'Trực tuyến' : 'Trực tiếp tại phòng khám' }} |
| **Địa điểm** | {{ $location ?: 'Sẽ được xếp phòng khi phòng khám xác nhận' }} |

<x-mail::button :url="$detailUrl">
Xem chi tiết lịch hẹn
</x-mail::button>

Phòng khám sẽ gọi điện xác nhận trong vòng 24 giờ. Bạn sẽ nhận được email nhắc lịch trước buổi hẹn 24 giờ và nhắc lại 30 phút trước khi bắt đầu.

Nếu cần đổi lịch hoặc hủy, vui lòng mở trang chi tiết lịch hẹn hoặc gọi 1900 1567.

Trân trọng,<br>
{{ config('app.name') }}
</x-mail::message>