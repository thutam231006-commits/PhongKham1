<x-mail::message>
@if ($reminderType === '30_MINUTES')
# Buổi tư vấn của bạn bắt đầu sau 30 phút
@else
# Nhắc lịch: buổi tư vấn ngày mai
@endif

Xin chào **{{ $customerName }}**, đây là lời nhắc từ phòng khám Mind Guide.

## Thông tin lịch hẹn

| | |
|---|---|
| **Mã lịch** | {{ $appointment->appointment_code }} |
| **Gói dịch vụ** | {{ $serviceName }} |
| **Chuyên viên** | {{ $specialistName }} |
| **Thời gian** | {{ $startAt }} |
| **Địa điểm** | {{ $location ?: 'Phòng khám Mind Guide' }} |

## Thông tin khách hàng đã đăng ký

Vui lòng kiểm tra lại thông tin dưới đây để phòng khám liên hệ chính xác:

- **Họ và tên:** {{ $customerName ?: 'Chưa cập nhật' }}
- **Ngày sinh:** {{ $dateOfBirth ?: 'Chưa cập nhật' }}
- **Giới tính:** {{ $gender ?: 'Chưa cập nhật' }}
- **Số điện thoại:** {{ $phone ?: 'Chưa cập nhật' }}
- **Email:** {{ $email ?: 'Chưa cập nhật' }}
- **Địa chỉ:** {{ $address ?: 'Chưa cập nhật' }}

@if ($reminderType === '30_MINUTES')
Vui lòng đến trước 10 phút để làm thủ tục. Nếu cần dời lịch, gọi ngay 1900 1567.
@else
Nếu cần dời lịch, vui lòng yêu cầu đổi lịch trước ít nhất 24 giờ.
@endif

<x-mail::button :url="$detailUrl">
Xem chi tiết lịch hẹn
</x-mail::button>

Trân trọng,<br>
{{ config('app.name') }}
</x-mail::message>