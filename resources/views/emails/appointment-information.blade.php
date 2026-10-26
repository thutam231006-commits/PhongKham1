<x-mail::message>
# Thông tin buổi tư vấn của bạn

Xin chào **{{ $customerName }}**,

Phòng khám Mind Guide gửi bạn thông tin chi tiết về buổi tư vấn sắp tới.

## Thông tin buổi tư vấn

| | |
|---|---|
| **Mã lịch hẹn** | {{ $appointment->appointment_code }} |
| **Gói dịch vụ** | {{ $serviceName }} |
| **Chuyên viên phụ trách** | {{ $specialistName }}{{ $specialistTitle ? ' — '.$specialistTitle : '' }} |
| **Thời gian** | {{ $startAt }} – {{ $endAt }}@if ($durationMinutes) ({{ $durationMinutes }} phút)@endif |
| **Hình thức** | {{ $isOnline ? 'Trực tuyến' : 'Trực tiếp tại phòng khám' }} |
| **Địa điểm** | {{ $location }} |
| **Trạng thái lịch** | {{ $statusLabel }} |

@if ($extraNote)
## Ghi chú từ phòng khám

{{ $extraNote }}
@endif

## Bạn cần chuẩn bị gì?

- Có mặt trước giờ hẹn **10 phút** để hoàn tất thủ tục tiếp đón.
- Mang theo giấy tờ tùy thân và các kết quả xét nghiệm, đơn thuốc liên quan (nếu có).
- Nếu buổi tư vấn diễn ra trực tuyến, hãy kiểm tra kết nối mạng, tai nghe và không gian riêng tư yên tĩnh.
- Ghi lại ngắn gọn những điều bạn muốn chia sẻ để buổi tư vấn hiệu quả hơn.

<x-mail::button :url="$detailUrl">
Xem chi tiết lịch hẹn
</x-mail::button>

Nếu cần đổi lịch, hủy lịch hoặc có thắc mắc, vui lòng liên hệ hotline **{{ $hotline }}** hoặc trả lời email này. Thay đổi lịch cần thực hiện trước giờ hẹn ít nhất 24 giờ.

Mọi thông tin bạn chia sẻ đều được bảo mật theo chính sách quyền riêng tư của phòng khám.

Trân trọng,<br>
{{ config('app.name') }}
</x-mail::message>
