<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * ConsultationRecordResource — nội dung đầy đủ cho CHUYÊN VIÊN PHỤ TRÁCH.
 *
 * Chỉ dùng resource này sau khi đã kiểm tra quyền (specialist phụ trách lịch hẹn).
 * Các trường mã hóa được đọc trực tiếp từ model vì model ẩn chúng khỏi
 * serialize mặc định.
 */
class ConsultationRecordResource extends JsonResource
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
            'version' => $this->version,
            'follow_up_needed' => (bool) $this->follow_up_needed,

            // Nội dung chuyên môn — chỉ chuyên viên phụ trách được đọc.
            'summary' => $this->summary,
            'follow_up_plan' => $this->follow_up_plan,

            // Bản tóm lược dành cho khách hàng (chuyên viên soạn để gửi đi).
            'customer_summary' => $this->customer_summary,
            'customer_recommendation' => $this->customer_recommendation,

            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
