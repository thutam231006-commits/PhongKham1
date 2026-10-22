<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\UpsertConsultationRecordRequest;
use App\Http\Resources\ConsultationRecordResource;
use App\Http\Resources\CustomerConsultationRecordResource;
use App\Models\Appointment;
use App\Models\User;
use App\Services\ConsultationRecordService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

/**
 * ConsultationRecordController — FR-CONSULT-001…003 (mục 29 SRS)
 *
 * - Mã hóa ở mức trường: `summary`, `follow_up_plan` (và bản tóm lược gửi khách)
 *   đều dùng cast `encrypted`.
 * - Phân quyền: chỉ chuyên viên PHỤ TRÁCH lịch hẹn được đọc/ghi nội dung
 *   chuyên môn; khách hàng chỉ đọc được bản tóm lược đã lọc (BR-049).
 * - Audit: mọi lần đọc/ghi đều ghi audit, không bao giờ chứa nội dung (BR-053).
 */
class ConsultationRecordController extends Controller
{
    public function __construct(private readonly ConsultationRecordService $records) {}

    /**
     * GET /api/appointments/{appointment}/consultation-record
     *
     * Đọc hồ sơ tư vấn của một lịch hẹn (chuyên viên phụ trách).
     */
    public function show(Request $request, Appointment $appointment): JsonResponse
    {
        $this->authorizeSpecialistFor($request, $appointment);

        $record = $appointment->consultationRecord()->first();
        $this->records->recordRead($record, $appointment);

        if (! $record) {
            return response()->json([
                'success' => true,
                'data' => null,
                'error' => null,
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => new ConsultationRecordResource($record),
            'error' => null,
        ]);
    }

    /**
     * PUT /api/appointments/{appointment}/consultation-record
     *
     * Tạo mới hoặc cập nhật hồ sơ tư vấn (một record cho mỗi lịch hẹn).
     */
    public function upsert(UpsertConsultationRecordRequest $request, Appointment $appointment): JsonResponse
    {
        $record = $this->records->upsert($appointment, $request->payload(), $request->user());

        return response()->json([
            'success' => true,
            'data' => new ConsultationRecordResource($record->fresh(['appointment'])),
            'error' => null,
        ]);
    }

    /**
     * GET /api/specialist/customers/{customer}/consultation-records
     *
     * Lịch sử hồ sơ tư vấn của một khách hàng, chỉ gồm các buổi mà chuyên viên
     * đang xem được phụ trách.
     */
    public function indexForCustomer(Request $request, User $customer): AnonymousResourceCollection
    {
        $profile = $request->user()->specialistProfile;

        abort_if($profile === null, 403, 'Tài khoản chưa có hồ sơ chuyên viên.');

        $records = $this->records->forSpecialistCustomer($profile, $customer);

        foreach ($records as $record) {
            $this->records->recordRead($record, $record->appointment, 'customer_history');
        }

        return CustomerConsultationRecordResource::collection($records);
    }

    /**
     * GET /api/customer/consultation-records
     *
     * Bản tóm lược hồ sơ tư vấn của chính khách hàng.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $records = $this->records->forCustomer($request->user());

        foreach ($records as $record) {
            $this->records->recordRead($record, $record->appointment, 'customer_self');
        }

        return CustomerConsultationRecordResource::collection($records);
    }

    /**
     * Chỉ chuyên viên phụ trách lịch hẹn được đọc nội dung (BR-049).
     *
     * Lịch hẹn của người khác trả về 404 để không tiết lộ sự tồn tại.
     */
    private function authorizeSpecialistFor(Request $request, Appointment $appointment): void
    {
        $user = $request->user();
        $appointment->loadMissing('specialistProfile');

        if ($user === null || ! Gate::allows('viewConsultationRecord', $appointment)) {
            $this->records->recordDenied('read', $appointment, $user);

            abort(404);
        }
    }
}
