<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\ConsultationRoom;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * RoomAllocationService — phân bổ phòng tư vấn cho lễ tân.
 *
 * Phòng khám có 8 phòng tư vấn. Nhiều chuyên viên có thể có lịch trùng giờ,
 * nên việc xếp phòng phải cân đối: mỗi phòng chỉ nhận một buổi tư vấn tại
 * một thời điểm, kể cả khi có thêm khoảng đệm giữa hai buổi.
 *
 * Thuật toán: sắp xếp các phòng theo số buổi đang chiếm (ít nhất trước),
 * rồi chọn phòng trống đầu tiên phù hợp. Nhờ vậy các phòng được dùng đều
 * nhau thay vì dồn vào phòng số 1.
 */
class RoomAllocationService
{
    /** Số phòng tư vấn của phòng khám. */
    public const TOTAL_ROOMS = 8;

    /**
     * Tìm phòng trống phù hợp cho lịch hẹn, ưu tiên phòng đang được dùng ít nhất.
     */
    public function findAvailableRoom(Appointment $appointment): ?ConsultationRoom
    {
        $rooms = ConsultationRoom::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->get();

        if ($rooms->isEmpty()) {
            return null;
        }

        $busy = $this->busyRoomIds($appointment, $rooms->pluck('id')->all());

        $free = $rooms->reject(fn (ConsultationRoom $room): bool => $busy->contains($room->id));

        if ($free->isEmpty()) {
            return null;
        }

        $usage = $this->usageCount($appointment->start_at, $rooms->pluck('id')->all());

        // Cân đối tải: ưu tiên phòng đang được dùng ít nhất trong ngày.
        // Phòng nào dùng bằng nhau thì ưu tiên phòng có id nhỏ hơn (thứ tự ổn định).
        return $free
            ->sortBy(fn (ConsultationRoom $room): int => ($usage[$room->id] ?? 0) * 100000 + $room->id)
            ->first();
    }

    /**
     * Gán phòng cho lịch hẹn; trả về null nếu đã hết phòng trống.
     */
    public function allocate(Appointment $appointment): ?ConsultationRoom
    {
        $room = $this->findAvailableRoom($appointment);

        if ($room instanceof ConsultationRoom) {
            $appointment->update(['consultation_room_id' => $room->id]);
        }

        return $room;
    }

    /**
     * Tổng quan sức chứa phòng theo ngày: dùng cho bảng điều phối của lễ tân.
     *
     * @return array{date:string, total:int, rooms:array<int, array{id:int, name:string, booked:int, free:int}>}
     */
    public function dailyCapacity(string $date): array
    {
        $rooms = ConsultationRoom::query()->where('is_active', true)->orderBy('id')->get();
        $booked = $this->bookedCountPerRoom($date);

        return [
            'date' => $date,
            'total' => $rooms->count(),
            'rooms' => $rooms->map(fn (ConsultationRoom $room): array => [
                'id' => $room->id,
                'name' => $room->name,
                'booked' => $booked[$room->id] ?? 0,
                'free' => self::TOTAL_ROOMS - ($booked[$room->id] ?? 0),
            ])->all(),
        ];
    }

    /**
     * Các phòng đang bị chiếm bởi lịch khác trong khung giờ của lịch hẹn.
     *
     * @param  array<int, int>  $roomIds
     * @return Collection<int, int>
     */
    private function busyRoomIds(Appointment $appointment, array $roomIds): Collection
    {
        return Appointment::query()
            ->whereKeyNot($appointment->id)
            ->whereIn('consultation_room_id', $roomIds)
            ->holdingSlot()
            ->where('start_at', '<', $appointment->end_at)
            ->where('end_at', '>', $appointment->start_at)
            ->pluck('consultation_room_id')
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values();
    }

    /**
     * Số buổi đang chiếm mỗi phòng trong ngày — dùng để cân đối tải.
     *
     * @param  array<int, int>  $roomIds
     * @return array<int, int>
     */
    private function usageCount(CarbonInterface $day, array $roomIds): array
    {
        $counts = $this->bookedCountPerRoom($day->toDateString());

        return array_intersect_key($counts, array_flip($roomIds));
    }

    /**
     * Số lịch đang giữ slot theo từng phòng trong ngày.
     *
     * @return array<int, int>
     */
    private function bookedCountPerRoom(string $date): array
    {
        return Appointment::query()
            ->whereNotNull('consultation_room_id')
            ->whereDate('start_at', $date)
            ->holdingSlot()
            ->groupBy('consultation_room_id')
            ->selectRaw('consultation_room_id, COUNT(*) as total')
            ->pluck('total', 'consultation_room_id')
            ->map(fn ($total): int => (int) $total)
            ->all();
    }
}
