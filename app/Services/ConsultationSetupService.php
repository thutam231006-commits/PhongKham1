<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\ConsultationRoom;
use Illuminate\Support\Str;

class ConsultationSetupService
{
    /**
     * Sinh link phòng họp trực tuyến duy nhất theo mã lịch hẹn.
     *
     * Mỗi lịch hẹn có một phòng riêng nên hai buổi tư vấn khác nhau
     * không bao giờ dùng chung một phòng họp.
     */
    public function joinUrl(Appointment $appointment): string
    {
        $baseUrl = rtrim((string) config('services.jitsi.base_url'), '/');
        $prefix = trim((string) config('services.jitsi.room_prefix'), '-_');
        $roomName = Str::lower(trim($prefix.'-'.$appointment->appointment_code, '-_'));

        return $baseUrl.'/'.$roomName;
    }

    /**
     * Tìm phòng tư vấn còn trống cho khung giờ của lịch hẹn.
     */
    public function findAvailableRoom(Appointment $appointment): ?ConsultationRoom
    {
        return ConsultationRoom::query()
            ->where('is_active', true)
            ->whereDoesntHave('appointments', function ($query) use ($appointment): void {
                $query->whereKeyNot($appointment->id)
                    ->holdingSlot()
                    ->where('start_at', '<', $appointment->end_at)
                    ->where('end_at', '>', $appointment->start_at);
            })
            ->orderBy('id')
            ->lockForUpdate()
            ->first();
    }

    /**
     * Gán phòng tư vấn cho lịch hẹn trực tiếp, dừng luồng nếu hết phòng.
     */
    public function assignRoom(Appointment $appointment): ConsultationRoom
    {
        $room = $this->findAvailableRoom($appointment);

        abort_unless($room, 409, 'Hiện không còn phòng tư vấn trống trong khung giờ này.');

        return $room;
    }

    /**
     * Thuộc tính cần cập nhật cho lịch hẹn theo hình thức tư vấn:
     * trực tiếp thì gán phòng, trực tuyến thì gán link phòng họp.
     *
     * @return array{consultation_room_id:int|null,meeting_url:string|null}
     */
    public function detailsFor(Appointment $appointment): array
    {
        if ($appointment->consultation_type === 'ONLINE') {
            return ['consultation_room_id' => null, 'meeting_url' => $this->joinUrl($appointment)];
        }

        return ['consultation_room_id' => $this->assignRoom($appointment)->id, 'meeting_url' => null];
    }

    /**
     * Thuộc tính cho lịch hẹn tạo ngay tại quầy: không dừng luồng khi hết phòng
     * mà gắn cờ cần xử lý để nhân viên xếp phòng bổ sung.
     *
     * @return array{consultation_room_id:int|null,meeting_url:string|null,needs_attention:bool}
     */
    public function detailsForOnsiteBooking(Appointment $appointment): array
    {
        if ($appointment->consultation_type === 'ONLINE') {
            return ['consultation_room_id' => null, 'meeting_url' => $this->joinUrl($appointment), 'needs_attention' => false];
        }

        $room = $this->findAvailableRoom($appointment);

        return [
            'consultation_room_id' => $room?->id,
            'meeting_url' => null,
            'needs_attention' => $room === null,
        ];
    }
}
