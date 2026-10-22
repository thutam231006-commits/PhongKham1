<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * CustomerConsultationRecordResource — bản tóm lược dành cho KHÁCH HÀNG.
 *
 * Khách hàng KHÔNG được đọc `summary`/`follow_up_plan` (BR-049) — chỉ đọc cặp
 * trường `customer_summary`/`customer_recommendation` do chuyên viên soạn.
 */
class CustomerConsultationRecordResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $appointment = $this->appointment;

        return [
            'id' => $this->id,
            'appointment_id' => $this->appointment_id,
            'appointment_code' => $appointment?->appointment_code,
            'service_name' => $appointment?->service?->name,
            'specialist_name' => $appointment?->specialistProfile?->user?->name,
            'consulted_at' => $appointment?->start_at?->toIso8601String(),
            'follow_up_needed' => (bool) $this->follow_up_needed,
            'customer_summary' => $this->customer_summary,
            'customer_recommendation' => $this->customer_recommendation,
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
