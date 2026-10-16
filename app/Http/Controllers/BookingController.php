<?php

namespace App\Http\Controllers;

use App\Exceptions\BookingSlotUnavailable;
use App\Http\Requests\StoreAppointmentRequest;
use App\Models\Service;
use App\Models\SpecialistProfile;
use App\Services\AppointmentBookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * BookingController — SCR-CUS-03 (FR-APPT-001, FR-SCHEDULE-004, FR-PAY-001/002/006)
 *
 * Màn hình Đặt lịch tư vấn 7 bước:
 *  1. Chọn dịch vụ
 *  2. Chọn chuyên viên
 *  3. Chọn ngày
 *  4. Chọn giờ
 *  5. Ghi chú cá nhân
 *  6. Phương thức thanh toán & chính sách hoàn tiền
 *  7. Xác nhận đặt lịch
 */
class BookingController extends Controller
{
    /**
     * GET /booking hoặc /dat-lich (appointments.book)
     */
    public function index(Request $request): View
    {
        $selectedServiceId = $request->input('service');
        $selectedSpecialistId = $request->input('specialist');

        $services = $this->getAvailableServices();
        $specialists = $this->getAvailableSpecialists();

        return view('public.booking', compact(
            'services',
            'specialists',
            'selectedServiceId',
            'selectedSpecialistId'
        ));
    }

    public function store(StoreAppointmentRequest $request, AppointmentBookingService $bookingService): JsonResponse
    {
        try {
            $appointment = $bookingService->create($request->user(), $request->validated());

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $appointment->id,
                    'appointment_code' => $appointment->appointment_code,
                    'status' => $appointment->status,
                    'payment_status' => $appointment->payment_status,
                    'start_at' => $appointment->start_at->toIso8601String(),
                ],
                'error' => null,
            ], 201);
        } catch (BookingSlotUnavailable $exception) {
            return response()->json([
                'success' => false,
                'data' => null,
                'error' => [
                    'code' => $exception->errorCode,
                    'message' => $exception->getMessage(),
                ],
            ], $exception->errorCode === 'APPOINTMENT_SLOT_UNAVAILABLE' ? 409 : 422);
        }
    }

    private function getAvailableServices(): Collection
    {
        try {
            $services = Service::query()
                ->active()
                ->with('category')
                ->orderBy('display_order')
                ->get()
                ->map(function (Service $service): object {
                    return (object) [
                        'id' => $service->id,
                        'name' => $service->name,
                        'category_name' => $service->category?->name ?? 'Dịch vụ tư vấn',
                        'duration_minutes' => $service->duration_minutes,
                        'price' => (int) $service->price,
                        'price_label' => number_format((int) $service->price, 0, ',', '.').' đ',
                    ];
                });

            if ($services->isNotEmpty()) {
                return $services;
            }
        } catch (\Throwable) {
            // Database may not be migrated during the first local setup.
        }

        return collect([
            (object) [
                'id' => 1,
                'name' => 'Tư vấn tâm lý cá nhân 1-1',
                'category_name' => 'Tâm lý lâm sàng',
                'duration_minutes' => 60,
                'price' => 500000,
            ],
            (object) [
                'id' => 2,
                'name' => 'Tham vấn & Trị liệu lo âu, trầm cảm',
                'category_name' => 'Trị liệu tâm lý',
                'duration_minutes' => 90,
                'price' => 750000,
            ],
            (object) [
                'id' => 3,
                'name' => 'Tư vấn hôn nhân & Mối quan hệ gia đình',
                'category_name' => 'Hôn nhân & Gia đình',
                'duration_minutes' => 90,
                'price' => 800000,
            ],
            (object) [
                'id' => 4,
                'name' => 'Tư vấn tâm lý học đường & Thanh thiếu niên',
                'category_name' => 'Tâm lý thanh thiếu niên',
                'duration_minutes' => 60,
                'price' => 450000,
            ],
            (object) [
                'id' => 5,
                'name' => 'Quản trị cảm xúc & Khủng hoảng tuổi trưởng thành',
                'category_name' => 'Phát triển cá nhân',
                'duration_minutes' => 60,
                'price' => 550000,
            ],
            (object) [
                'id' => 6,
                'name' => 'Đánh giá & Sàng lọc sức khỏe tinh thần tổng quát',
                'category_name' => 'Đánh giá tâm lý',
                'duration_minutes' => 75,
                'price' => 600000,
            ],
        ]);
    }

    private function getAvailableSpecialists(): Collection
    {
        try {
            $specialists = SpecialistProfile::query()
                ->where('status', 'ACTIVE')
                ->with(['user', 'services'])
                ->orderBy('display_order')
                ->get()
                ->map(function (SpecialistProfile $specialist): object {
                    return (object) [
                        'id' => $specialist->id,
                        'name' => $specialist->user?->name ?? 'Chuyên viên Mind Guide',
                        'title' => $specialist->title,
                        'rating' => 5.0,
                        'reviews_count' => 0,
                        'experience' => $specialist->years_of_experience
                            ? $specialist->years_of_experience.' năm kinh nghiệm'
                            : 'Nhiều năm kinh nghiệm',
                        'service_ids' => $specialist->services->pluck('id')->map(fn (mixed $id): int => (int) $id)->all(),
                    ];
                });

            if ($specialists->isNotEmpty()) {
                return $specialists;
            }
        } catch (\Throwable) {
            // Database may not be migrated during the first local setup.
        }

        return collect([
            (object) [
                'id' => 1,
                'name' => 'ThS. Nguyễn Thị Mai',
                'title' => 'Chuyên viên Tâm lý học lâm sàng',
                'rating' => 4.9,
                'reviews_count' => 62,
                'experience' => '10 năm',
                'service_ids' => [1, 2, 6],
            ],
            (object) [
                'id' => 2,
                'name' => 'TS. Trần Văn Hưng',
                'title' => 'Tiến sĩ Tâm lý học tham vấn',
                'rating' => 5.0,
                'reviews_count' => 84,
                'experience' => '14 năm',
                'service_ids' => [3, 5],
            ],
            (object) [
                'id' => 3,
                'name' => 'ThS. Lê Hoàng An',
                'title' => 'Chuyên viên Trị liệu CBT',
                'rating' => 4.8,
                'reviews_count' => 41,
                'experience' => '7 năm',
                'service_ids' => [1, 2, 5],
            ],
            (object) [
                'id' => 4,
                'name' => 'Chuyên viên Phạm Thu Hà',
                'title' => 'Chuyên viên Tham vấn Thanh thiếu niên',
                'rating' => 4.9,
                'reviews_count' => 35,
                'experience' => '6 năm',
                'service_ids' => [4],
            ],
        ]);
    }
}
