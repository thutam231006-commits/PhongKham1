<?php

namespace App\Policies;

use App\Models\Appointment;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * AppointmentPolicy — BR-049
 *
 * "Chỉ specialist phụ trách đọc nội dung consultation record; Staff/Admin/Customer
 * không đọc; mọi lần đọc/ghi đều audit."
 *
 * Chuyên viên A KHÔNG đọc được hồ sơ của chuyên viên B, kể cả khi cùng phục vụ
 * một khách hàng.
 */
class AppointmentPolicy
{
    /**
     * Chuyên viên được phân công lịch hẹn này được xem/ghi hồ sơ tư vấn.
     */
    public function viewConsultationRecord(User $user, Appointment $appointment): Response
    {
        if (strtolower((string) $user->role) !== 'specialist') {
            return Response::deny('Chỉ chuyên viên phụ trách được xem hồ sơ tư vấn.');
        }

        return $appointment->specialistProfile?->user_id === $user->id
            ? Response::allow()
            : Response::deny('Chỉ chuyên viên phụ trách được xem hồ sơ tư vấn.');
    }

    /**
     * Ghi hồ sơ tư vấn: cùng điều kiện với xem (BR-048).
     */
    public function updateConsultationRecord(User $user, Appointment $appointment): Response
    {
        return $this->viewConsultationRecord($user, $appointment);
    }
}
