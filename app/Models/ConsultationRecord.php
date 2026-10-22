<?php

namespace App\Models;

use Database\Factories\ConsultationRecordFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsultationRecord extends Model
{
    /** @use HasFactory<ConsultationRecordFactory> */
    use HasFactory;

    /**
     * Nội dung chuyên môn (chỉ chuyên viên phụ trách đọc) và bản tóm lược
     * dành cho khách hàng. Tất cả đều được mã hóa ở mức trường (NFR-PRV-001).
     */
    protected $fillable = [
        'appointment_id', 'specialist_profile_id', 'summary', 'follow_up_plan', 'follow_up_needed',
        'customer_summary', 'customer_recommendation', 'version',
    ];

    /**
     * Ẩn khỏi mọi serialize/JSON mặc định để nội dung nhạy cảm không vô tình
     * bị trả ra ngoài qua model->toArray() hay response()->json($model).
     */
    protected $hidden = [
        'summary', 'follow_up_plan', 'customer_summary', 'customer_recommendation',
    ];

    protected function casts(): array
    {
        return [
            'summary' => 'encrypted',
            'follow_up_plan' => 'encrypted',
            'customer_summary' => 'encrypted',
            'customer_recommendation' => 'encrypted',
            'follow_up_needed' => 'boolean',
        ];
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function specialistProfile(): BelongsTo
    {
        return $this->belongsTo(SpecialistProfile::class);
    }

    /**
     * Bản tóm lược dành cho khách hàng (chỉ đọc).
     *
     * Khách hàng KHÔNG được đọc `summary`/`follow_up_plan` (BR-049) — chỉ đọc
     * cặp trường `customer_summary`/`customer_recommendation` do chuyên viên soạn.
     */
    public function hasCustomerVisibleContent(): bool
    {
        return filled($this->customer_summary) || filled($this->customer_recommendation);
    }
}
