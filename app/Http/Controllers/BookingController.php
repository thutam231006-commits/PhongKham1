<?php

namespace App\Http\Controllers;

use App\Exceptions\BookingSlotUnavailable;
use App\Http\Requests\StoreAppointmentRequest;
use App\Models\Appointment;
use App\Models\Service;
use App\Models\SpecialistProfile;
use App\Services\AppointmentBookingService;
use App\Services\PaymentService;
use App\Services\SlotAvailabilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
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
    public function __construct(private readonly SlotAvailabilityService $slots) {}

    /**
     * GET /booking hoặc /dat-lich (appointments.book)
     */
    public function index(Request $request): View|RedirectResponse
    {
        // Đến từ nút "Đặt lịch ngay" trên thẻ gói dịch vụ (?service=ID):
        // hiển thị ngay thông tin chi tiết gói + danh sách chuyên viên phụ trách.
        $requestedServiceId = (int) $request->input('service');
        if ($requestedServiceId > 0 && Service::query()->active()->whereKey($requestedServiceId)->exists()) {
            // Bắt đầu phiên đặt lịch mới: xoá dữ liệu cũ để không dùng nhầm
            // chuyên viên / khung giờ của lần chọn trước đó.
            $request->session()->forget('booking');

            return redirect()->route('booking.select-specialist', array_filter([
                'service_id' => $requestedServiceId,
                'specialist_id' => $request->input('specialist'),
            ]));
        }

        $services = $this->getAvailableServices();
        $selectedServiceId = (int) ($request->input('service') ?: session('booking.service_id', 0));

        return view('booking.step', ['step' => 1, 'services' => $services, 'selectedServiceId' => $selectedServiceId, 'booking' => session('booking', [])]);
    }

    public function specialistsApi(Request $request): JsonResponse
    {
        $data = $request->validate(['service_id' => ['required', 'integer', 'exists:services,id']]);
        $specialists = SpecialistProfile::query()->where('status', 'ACTIVE')->whereHas('services', fn ($query) => $query->whereKey($data['service_id']))->with('user')->withAvg(['reviews as average_rating' => fn ($query) => $query->where('status', 'VISIBLE')], 'rating')->withCount(['reviews as reviews_count' => fn ($query) => $query->where('status', 'VISIBLE')])->get()->map(fn (SpecialistProfile $specialist): array => ['id' => $specialist->id, 'name' => $specialist->user->name, 'title' => $specialist->title, 'avatar_url' => $specialist->avatar_url, 'rating' => (float) ($specialist->average_rating ?? 0), 'reviews_count' => $specialist->reviews_count]);

        return response()->json(['success' => true, 'data' => $specialists, 'error' => null]);
    }

    public function selectSpecialist(Request $request): View|RedirectResponse
    {
        $serviceId = (int) ($request->input('service_id') ?: session('booking.service_id', 0));
        if (! $serviceId || ! Service::query()->active()->whereKey($serviceId)->exists()) {
            return redirect()->route('appointments.book')->with('error', 'Vui lòng chọn gói dịch vụ trước.');
        }
        session(['booking' => [...session('booking', []), 'service_id' => $serviceId]]);
        $service = Service::query()->active()->with('category')->findOrFail($serviceId);
        $specialists = SpecialistProfile::query()->where('status', 'ACTIVE')->whereHas('services', fn ($query) => $query->whereKey($serviceId))->with(['user', 'services'])->withAvg(['reviews as average_rating' => fn ($query) => $query->where('status', 'VISIBLE')], 'rating')->withCount(['reviews as reviews_count' => fn ($query) => $query->where('status', 'VISIBLE')])->get();

        // Đến từ thẻ "Đặt lịch với chuyên gia" (?specialist_id=ID): bỏ qua bước chọn
        // chuyên viên và chuyển thẳng sang bước chọn thời gian lịch hẹn.
        $requestedSpecialistId = (int) $request->input('specialist_id');
        if ($requestedSpecialistId > 0 && $specialists->contains('id', $requestedSpecialistId)) {
            session(['booking' => [...session('booking', []), 'specialist_id' => $requestedSpecialistId]]);

            return redirect()->route('booking.select-slot');
        }

        return view('booking.step', ['step' => 2, 'service' => $service, 'specialists' => $specialists, 'booking' => session('booking', [])]);
    }

    /**
     * GET /api/slots — chỉ trả về khung giờ còn trống thực sự (Yêu cầu 3).
     *
     * UI gọi endpoint này mỗi khi khách đổi ngày / dịch vụ / chuyên viên để
     * danh sách luôn khớp với lịch thật của bác sĩ, tránh chọn xong mới báo hết slot.
     */
    public function slotsApi(Request $request): JsonResponse
    {
        $data = $request->validate([
            'service_id' => ['required', 'integer', 'exists:services,id'],
            'specialist_id' => ['required', 'integer', 'exists:specialist_profiles,id'],
            'date' => ['required', 'date_format:Y-m-d'],
        ]);

        $service = Service::query()->active()->findOrFail($data['service_id']);
        $specialist = SpecialistProfile::query()
            ->where('status', 'ACTIVE')
            ->whereHas('services', fn ($query) => $query->whereKey($service->id))
            ->with('user')
            ->findOrFail($data['specialist_id']);

        $slots = $this->slots->openSlotsFor($specialist, $service, $data['date']);

        return response()->json([
            'success' => true,
            'data' => [
                'date' => $data['date'],
                'service_id' => $service->id,
                'service_name' => $service->name,
                'duration_minutes' => $service->duration_minutes,
                'specialist_id' => $specialist->id,
                'specialist_name' => $specialist->user->name,
                'slots' => $slots,
                'available_count' => count($slots),
            ],
            'error' => null,
        ]);
    }

    public function selectSlot(Request $request): View|RedirectResponse
    {
        $serviceId = (int) session('booking.service_id', 0);
        $specialistId = (int) ($request->input('specialist_id') ?: session('booking.specialist_id', 0));
        if (! $serviceId || ! $specialistId) {
            return redirect()->route('appointments.book')->with('error', 'Vui lòng chọn dịch vụ và chuyên viên trước.');
        }
        session(['booking.specialist_id' => $specialistId]);
        $service = Service::query()->active()->findOrFail($serviceId);
        $specialist = SpecialistProfile::query()->where('status', 'ACTIVE')->with('user')->findOrFail($specialistId);
        $date = $request->date('date')?->toDateString() ?? session('booking.date', now()->addDays(2)->toDateString());
        $slots = $this->slots->openSlotsFor($specialist, $service, $date);

        return view('booking.step', ['step' => 3, 'service' => $service, 'specialist' => $specialist, 'date' => $date, 'slots' => $slots, 'booking' => session('booking', [])]);
    }

    public function note(): View|RedirectResponse
    {
        if (! session('booking.service_id') || ! session('booking.specialist_id') || ! session('booking.start_at')) {
            return redirect()->route('appointments.book')->with('error', 'Vui lòng hoàn tất dịch vụ, chuyên viên và khung giờ trước.');
        }

        return view('booking.step', ['step' => 4, 'booking' => session('booking', [])]);
    }

    public function confirm(): View|RedirectResponse
    {
        $booking = session('booking', []);
        if (! isset($booking['service_id'], $booking['specialist_id'], $booking['start_at'])) {
            return redirect()->route('appointments.book')->with('error', 'Thông tin đặt lịch chưa đầy đủ.');
        }
        $service = Service::query()->active()->findOrFail($booking['service_id']);
        $specialist = SpecialistProfile::query()->with('user')->findOrFail($booking['specialist_id']);

        return view('booking.step', ['step' => 5, 'service' => $service, 'specialist' => $specialist, 'booking' => $booking]);
    }

    public function storeServiceStep(Request $request): RedirectResponse
    {
        $data = $request->validate(['service_id' => ['required', 'integer', 'exists:services,id']]);
        abort_unless(Service::query()->active()->whereKey($data['service_id'])->exists(), 404);
        session(['booking' => ['service_id' => (int) $data['service_id']]]);

        return redirect()->route('booking.select-specialist', ['service_id' => $data['service_id']]);
    }

    public function storeSpecialistStep(Request $request): RedirectResponse
    {
        $data = $request->validate(['specialist_id' => ['required', 'integer', 'exists:specialist_profiles,id']]);
        $serviceId = (int) session('booking.service_id', 0);
        abort_unless(SpecialistProfile::query()->whereKey($data['specialist_id'])->where('status', 'ACTIVE')->whereHas('services', fn ($query) => $query->whereKey($serviceId))->exists(), 422, 'Chuyên viên không cung cấp gói dịch vụ đã chọn.');
        session(['booking' => [...session('booking', []), 'specialist_id' => (int) $data['specialist_id']]]);

        return redirect()->route('booking.select-slot');
    }

    public function storeSlotStep(Request $request): RedirectResponse
    {
        $data = $request->validate(['date' => ['required', 'date_format:Y-m-d'], 'time' => ['required', 'date_format:H:i']]);
        $service = Service::query()->active()->findOrFail(session('booking.service_id'));
        $specialist = SpecialistProfile::query()->where('status', 'ACTIVE')->findOrFail(session('booking.specialist_id'));
        abort_unless($this->slots->isSlotOpen($specialist, $service, $data['date'], $data['time']), 422, 'Khung giờ đã chọn không còn khả dụng. Vui lòng chọn khung giờ khác.');
        session(['booking' => [...session('booking', []), 'date' => $data['date'], 'time' => $data['time'], 'start_at' => $data['date'].' '.$data['time']]]);

        return redirect()->route('booking.note');
    }

    public function storeNoteStep(Request $request): RedirectResponse
    {
        $data = $request->validate(['customer_note' => ['nullable', 'string', 'max:500']]);
        session(['booking' => [...session('booking', []), 'customer_note' => $data['customer_note'] ?? null]]);

        return redirect()->route('booking.confirm');
    }

    public function confirmBooking(Request $request, AppointmentBookingService $bookingService): RedirectResponse
    {
        $data = $request->validate([
            'payment_method' => ['required', 'in:CASH,BANK_QR'],
            'consultation_type' => ['nullable', 'in:ONLINE,OFFLINE'],
        ]);
        abort_unless($request->user(), 401);
        $booking = session('booking', []);
        if (! isset($booking['service_id'], $booking['specialist_id'], $booking['date'], $booking['time'])) {
            return redirect()->route('appointments.book')->with('error', 'Thông tin đặt lịch chưa đầy đủ. Vui lòng thực hiện lại các bước.');
        }

        try {
            $appointment = $bookingService->create($request->user(), [
                'service_id' => $booking['service_id'],
                'specialist_id' => $booking['specialist_id'],
                'date' => $booking['date'],
                'time' => $booking['time'],
                'customer_note' => $booking['customer_note'] ?? null,
                'payment_method' => $data['payment_method'],
                'consultation_type' => $data['consultation_type'] ?? 'OFFLINE',
                'idempotency_key' => 'web-'.hash('sha256', $request->session()->getId().'|'.json_encode($booking)),
            ]);
        } catch (BookingSlotUnavailable $exception) {
            return redirect()->route('booking.confirm')->withInput()->with('error', $exception->getMessage());
        }

        session()->forget('booking');

        if ($appointment->payment_method === 'BANK_QR' && $appointment->payment_status === 'UNPAID') {
            return redirect()->route('booking.payment.show', $appointment);
        }

        return redirect()->route('customer.appointments.detail', $appointment->id)->with('success', 'Đặt lịch thành công. Mã lịch: '.$appointment->appointment_code);
    }

    public function payment(Request $request, Appointment $appointment, PaymentService $payments): View|RedirectResponse
    {
        abort_unless((int) $appointment->customer_id === (int) $request->user()?->id, 403);
        abort_unless($appointment->payment_method === 'BANK_QR', 404);

        $appointment = $payments->expirePendingPayment($appointment);
        if ($appointment->payment_status === 'PAID') {
            return redirect()->route('customer.appointments.detail', $appointment)->with('success', 'Thanh toán đã được xác nhận.');
        }
        if ($appointment->status === 'CANCELLED_BY_CLINIC') {
            return redirect()->route('customer.appointments.detail', $appointment)->with('error', 'Mã thanh toán đã hết hạn và khung giờ không còn được giữ. Vui lòng đặt lịch mới.');
        }

        $appointment->load(['service', 'specialistProfile.user']);

        return view('booking.payment', [
            'appointment' => $appointment,
            'payment' => $payments->paymentInstructions($appointment),
        ]);
    }

    public function paymentStatus(Request $request, Appointment $appointment, PaymentService $payments): JsonResponse
    {
        abort_unless((int) $appointment->customer_id === (int) $request->user()?->id, 403);
        $appointment = $payments->expirePendingPayment($appointment);

        return response()->json([
            'success' => true,
            'data' => [
                'payment_status' => $appointment->payment_status,
                'appointment_status' => $appointment->status,
                'expires_at' => $appointment->payment_expires_at?->toIso8601String(),
                'redirect_url' => $appointment->payment_status === 'PAID'
                    ? route('customer.appointments.detail', $appointment)
                    : null,
            ],
            'error' => null,
        ]);
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
                        'description' => $service->description ?? $service->target_audience ?? 'Gói tư vấn tâm lý được thiết kế theo nhu cầu của bạn.',
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
                'description' => 'Đồng hành riêng tư với cảm xúc, stress và định hướng cá nhân.',
                'category_name' => 'Tâm lý lâm sàng',
                'duration_minutes' => 60,
                'price' => 500000,
            ],
            (object) [
                'id' => 2,
                'name' => 'Tham vấn & Trị liệu lo âu, trầm cảm',
                'description' => 'Hỗ trợ nhận diện và điều chỉnh lo âu, trầm cảm, kiệt sức.',
                'category_name' => 'Trị liệu tâm lý',
                'duration_minutes' => 90,
                'price' => 750000,
            ],
            (object) [
                'id' => 3,
                'name' => 'Tư vấn hôn nhân & Mối quan hệ gia đình',
                'description' => 'Không gian an toàn để lắng nghe và xây dựng lại kết nối.',
                'category_name' => 'Hôn nhân & Gia đình',
                'duration_minutes' => 90,
                'price' => 800000,
            ],
            (object) [
                'id' => 4,
                'name' => 'Tư vấn tâm lý học đường & Thanh thiếu niên',
                'description' => 'Hỗ trợ áp lực học tập, cảm xúc tuổi teen và gia đình.',
                'category_name' => 'Tâm lý thanh thiếu niên',
                'duration_minutes' => 60,
                'price' => 450000,
            ],
            (object) [
                'id' => 5,
                'name' => 'Quản trị cảm xúc & Khủng hoảng tuổi trưởng thành',
                'description' => 'Cùng bạn xây dựng kỹ năng ứng phó và cân bằng cảm xúc.',
                'category_name' => 'Phát triển cá nhân',
                'duration_minutes' => 60,
                'price' => 550000,
            ],
            (object) [
                'id' => 6,
                'name' => 'Đánh giá & Sàng lọc sức khỏe tinh thần tổng quát',
                'description' => 'Đánh giá ban đầu và định hướng hỗ trợ phù hợp.',
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
