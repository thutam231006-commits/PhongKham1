<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\CustomerProfile;
use App\Models\Permission;
use App\Models\RefundRequest;
use App\Models\Review;
use App\Models\Role;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\SpecialistProfile;
use App\Models\SystemSetting;
use App\Models\User;
use App\Models\WorkingSchedule;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $users = $this->seedUsers();
        $services = $this->seedServices();
        $specialists = $this->seedSpecialists($users, $services);

        $this->seedAppointments($users, $specialists, $services);
        $this->seedAnalyticsAppointments($users, $specialists, $services);
        $this->seedOperationalData($users);
    }

    /** @return array<string, User> */
    private function seedUsers(): array
    {
        $accounts = [
            'customer@mindguide.vn' => ['name' => 'Nguyễn Minh Anh', 'role' => 'customer', 'phone' => '0909123456', 'password' => 'MindGuide@2026'],
            'khachhang@mindguide.vn' => ['name' => 'Nguyễn Văn An', 'role' => 'customer', 'phone' => '0909012345', 'password' => 'Customer@2026'],
            'test@example.com' => ['name' => 'Test Customer', 'role' => 'customer', 'phone' => '0909000000', 'password' => 'TestUser@2026'],
            'specialist@mindguide.vn' => ['name' => 'TS.BS Nguyễn Văn An', 'role' => 'specialist', 'phone' => '0909234567', 'password' => 'Therapy@2026'],
            'mai@mindguide.vn' => ['name' => 'ThS. Chuyên gia Tâm lý Vũ Thị Mai', 'role' => 'specialist', 'phone' => '0909678901', 'password' => 'Specialist@2026'],
            'nam@mindguide.vn' => ['name' => 'BS. CKI Lê Hoàng Nam', 'role' => 'specialist', 'phone' => '0909789012', 'password' => 'Doctor@2026'],
            'ha@mindguide.vn' => ['name' => 'TS. Nguyễn Thu Hà', 'role' => 'specialist', 'phone' => '0909890123', 'password' => 'Counselor@2026'],
            'khoa@mindguide.vn' => ['name' => 'ThS. Trần Minh Khoa', 'role' => 'specialist', 'phone' => '0909901234', 'password' => 'Therapy@2026!'],
            'hung@mindguide.vn' => ['name' => 'TS. Trần Văn Hưng', 'role' => 'specialist', 'phone' => '0909912345', 'password' => 'Expert@2026'],
            'staff@mindguide.vn' => ['name' => 'Lê Thu Hương', 'role' => 'staff', 'phone' => '0909345678', 'password' => 'Reception@2026'],
            'admin@mindguide.vn' => ['name' => 'Mind Guide Admin', 'role' => 'admin', 'phone' => '0909456789', 'password' => 'Admin@2026!'],
            'customer2@mindguide.vn' => ['name' => 'Trần Ngọc Linh', 'role' => 'customer', 'phone' => '0909567890', 'password' => 'Customer2@2026'],
        ];

        $users = [];
        foreach ($accounts as $email => $data) {
            $user = User::query()->firstOrNew(['email' => $email]);
            $user->fill(['name' => $data['name'], 'role' => $data['role'], 'phone' => $data['phone'], 'email_verified_at' => $user->email_verified_at ?? now()]);
            if (! $user->exists) {
                $user->password = Hash::make($data['password']);
            }
            $user->save();
            $users[$email] = $user;
        }

        return $users;
    }

    /** @return array<string, Service> */
    private function seedServices(): array
    {
        $categories = [
            'individual' => ['name' => 'Tư vấn Cá nhân', 'description' => 'Đồng hành riêng tư với các vấn đề cảm xúc, stress và định hướng cá nhân.'],
            'couple' => ['name' => 'Trị liệu Cặp đôi / Hôn nhân', 'description' => 'Không gian an toàn để hai người lắng nghe, thấu hiểu và xây dựng lại kết nối.'],
            'teen' => ['name' => 'Tư vấn Trẻ em & Tuổi teen', 'description' => 'Hỗ trợ trẻ em, thanh thiếu niên và gia đình trước những thay đổi tâm lý theo lứa tuổi.'],
            'corporate' => ['name' => 'Đánh giá Sức khỏe Tinh thần Doanh nghiệp', 'description' => 'Đánh giá và xây dựng chương trình chăm sóc sức khỏe tinh thần cho đội ngũ.'],
        ];

        $categoryModels = [];
        foreach ($categories as $key => $category) {
            $categoryModels[$key] = ServiceCategory::updateOrCreate(
                ['name' => $category['name']],
                ['description' => $category['description'], 'display_order' => count($categoryModels) + 1, 'is_active' => true]
            );
        }

        $services = [
            ['key' => 'individual_anxiety', 'category' => 'individual', 'name' => 'Tư vấn Lo âu & Cân bằng cảm xúc', 'duration' => 60, 'price' => 650000, 'image' => 'https://images.unsplash.com/photo-1499209974431-9dddcece7f88?auto=format&fit=crop&w=1200&q=85', 'audience' => 'Người trưởng thành đang trải qua lo âu, căng thẳng kéo dài, mất ngủ hoặc khó điều chỉnh cảm xúc.', 'process' => 'Lắng nghe bối cảnh; nhận diện vòng lặp suy nghĩ và cảm xúc; thống nhất bài tập ứng phó thực tế giữa các buổi.'],
            ['key' => 'individual_depression', 'category' => 'individual', 'name' => 'Đồng hành cùng Trầm cảm & Kiệt sức', 'duration' => 60, 'price' => 700000, 'image' => 'https://images.unsplash.com/photo-1544027993-37dbfe43562a?auto=format&fit=crop&w=1200&q=85', 'audience' => 'Người cảm thấy mất năng lượng, giảm hứng thú, tự trách hoặc kiệt sức trong học tập và công việc.', 'process' => 'Đánh giá nhu cầu ban đầu; xây dựng mục tiêu nhỏ an toàn; theo dõi tiến triển và điều chỉnh kế hoạch đồng hành.'],
            ['key' => 'cbt', 'category' => 'individual', 'name' => 'Trị liệu Nhận thức Hành vi CBT', 'duration' => 60, 'price' => 800000, 'image' => 'https://images.unsplash.com/photo-1576091160399-112ba8d25d1d?auto=format&fit=crop&w=1200&q=85', 'audience' => 'Người muốn làm việc có hệ thống với suy nghĩ tự động, hành vi né tránh và các phản ứng lo âu.', 'process' => 'Lập bản đồ vấn đề; thực hành kỹ thuật CBT; giao bài tập quan sát và thử nghiệm hành vi phù hợp.'],
            ['key' => 'couple', 'category' => 'couple', 'name' => 'Tham vấn Cặp đôi & Hôn nhân', 'duration' => 60, 'price' => 950000, 'image' => 'https://images.unsplash.com/photo-1529156069898-49953e39b3ac?auto=format&fit=crop&w=1200&q=85', 'audience' => 'Các cặp đôi muốn cải thiện giao tiếp, xử lý xung đột, tổn thương niềm tin hoặc khác biệt kỳ vọng.', 'process' => 'Thiết lập nguyên tắc đối thoại; nhận diện chu kỳ xung đột; luyện tập cách phản hồi và kế hoạch kết nối tại nhà.'],
            ['key' => 'family', 'category' => 'couple', 'name' => 'Trị liệu Hệ thống Gia đình', 'duration' => 60, 'price' => 900000, 'image' => 'https://images.unsplash.com/photo-1511895426328-dc8714191300?auto=format&fit=crop&w=1200&q=85', 'audience' => 'Gia đình gặp khó khăn trong giao tiếp, chuyển tiếp vai trò, nuôi dạy con hoặc thích nghi sau biến cố.', 'process' => 'Khám phá mối quan hệ trong hệ thống; xác định nguồn lực gia đình; thiết kế thay đổi nhỏ có sự tham gia của các thành viên.'],
            ['key' => 'teen', 'category' => 'teen', 'name' => 'Tâm lý Học đường & Tuổi teen', 'duration' => 45, 'price' => 550000, 'image' => 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?auto=format&fit=crop&w=1200&q=85', 'audience' => 'Học sinh, sinh viên và phụ huynh cần hỗ trợ về áp lực học tập, bạn bè, tự tin và định hướng tương lai.', 'process' => 'Tạo không gian an toàn; lắng nghe góc nhìn của trẻ; phối hợp với phụ huynh khi phù hợp và thống nhất bước tiếp theo.'],
            ['key' => 'child', 'category' => 'teen', 'name' => 'Đánh giá & Hỗ trợ Tâm lý Trẻ em', 'duration' => 45, 'price' => 600000, 'image' => 'https://images.unsplash.com/photo-1503454537195-1dcabb73ffb9?auto=format&fit=crop&w=1200&q=85', 'audience' => 'Trẻ có thay đổi về cảm xúc, hành vi, giấc ngủ, học tập hoặc khó thích nghi với môi trường mới.', 'process' => 'Trao đổi với người chăm sóc; quan sát và đánh giá phù hợp lứa tuổi; phản hồi bằng kế hoạch hỗ trợ dễ thực hiện.'],
            ['key' => 'corporate', 'category' => 'corporate', 'name' => 'Đánh giá Sức khỏe Tinh thần Doanh nghiệp', 'duration' => 60, 'price' => 1200000, 'image' => 'https://images.unsplash.com/photo-1556761175-b413da4baf72?auto=format&fit=crop&w=1200&q=85', 'audience' => 'Doanh nghiệp muốn khảo sát rủi ro tâm lý, sức khỏe tinh thần và nhu cầu hỗ trợ của nhân viên.', 'process' => 'Xác định mục tiêu; khảo sát bảo mật; phân tích tổng hợp và đề xuất chương trình hỗ trợ phù hợp văn hóa tổ chức.'],
        ];

        $models = [];
        foreach ($services as $index => $serviceData) {
            $models[$serviceData['key']] = Service::updateOrCreate(
                ['category_id' => $categoryModels[$serviceData['category']]->id, 'name' => $serviceData['name']],
                [
                    'description' => $serviceData['audience'],
                    'image_url' => $serviceData['image'],
                    'target_audience' => $serviceData['audience'],
                    'session_process' => $serviceData['process'],
                    'duration_minutes' => $serviceData['duration'],
                    'buffer_minutes' => 10,
                    'price' => $serviceData['price'],
                    'status' => 'ACTIVE',
                    'display_order' => $index + 1,
                ]
            );
        }

        return $models;
    }

    /** @param array<string, User> $users @param array<string, Service> $services @return array<string, SpecialistProfile> */
    private function seedSpecialists(array $users, array $services): array
    {
        $profiles = [
            ['key' => 'nguyen_van_an', 'email' => 'specialist@mindguide.vn', 'name' => 'TS.BS Nguyễn Văn An', 'title' => 'Tiến sĩ Tâm lý lâm sàng', 'expertise' => 'Lo âu, trầm cảm, sang chấn tâm lý và CBT', 'credentials' => 'Tiến sĩ Tâm lý lâm sàng - Đại học Quốc gia; Chứng chỉ Trị liệu Nhận thức Hành vi CBT; Thành viên Hội Tâm lý học Việt Nam.', 'bio' => 'TS.BS Nguyễn Văn An theo đuổi phong cách tư vấn điềm tĩnh, khoa học và thân chủ trọng tâm. Anh có hơn 12 năm làm việc trong lĩnh vực tâm lý lâm sàng, đặc biệt với lo âu, trầm cảm và sang chấn. Tâm niệm của anh là mỗi thay đổi bền vững đều bắt đầu từ một cuộc trò chuyện an toàn và không phán xét.', 'years' => 12, 'rating' => 4.9, 'completed' => 1280, 'fee' => 800000, 'services' => ['individual_anxiety', 'individual_depression', 'cbt']],
            ['key' => 'vu_thi_mai', 'email' => 'mai@mindguide.vn', 'name' => 'ThS. Chuyên gia Tâm lý Vũ Thị Mai', 'title' => 'Thạc sĩ Tâm lý học tham vấn', 'expertise' => 'Tâm lý phụ nữ, cân bằng cảm xúc và kiệt sức nghề nghiệp', 'credentials' => 'Thạc sĩ Tâm lý học tham vấn; Chứng chỉ Tham vấn Khủng hoảng; Đào tạo chuyên sâu về Mindfulness và trị liệu thân chủ trọng tâm.', 'bio' => 'ThS. Vũ Thị Mai tạo dựng không gian nhẹ nhàng, nơi khách hàng có thể chậm lại và gọi tên điều mình đang trải qua. Chị có hơn 10 năm kinh nghiệm đồng hành với người trưởng thành, phụ nữ sau biến cố và người đang kiệt sức. Chị tin rằng sự tử tế với chính mình là bước đầu tiên của quá trình hồi phục.', 'years' => 10, 'rating' => 5.0, 'completed' => 960, 'fee' => 700000, 'services' => ['individual_anxiety', 'individual_depression', 'teen']],
            ['key' => 'le_hoang_nam', 'email' => 'nam@mindguide.vn', 'name' => 'BS. CKI Lê Hoàng Nam', 'title' => 'Bác sĩ Chuyên khoa I Tâm thần', 'expertise' => 'Đánh giá sức khỏe tinh thần, mất ngủ và rối loạn cảm xúc', 'credentials' => 'Bác sĩ Chuyên khoa I Tâm thần; Chứng chỉ Đánh giá lâm sàng sức khỏe tinh thần; 11 năm công tác tại bệnh viện chuyên khoa.', 'bio' => 'BS. CKI Lê Hoàng Nam kết hợp tư duy y khoa với cách giải thích rõ ràng, tôn trọng và dễ hiểu. Anh có thế mạnh trong đánh giá ban đầu, mất ngủ, rối loạn cảm xúc và định hướng phối hợp chăm sóc khi cần. Quan điểm làm việc của anh là trao cho khách hàng thông tin đủ rõ để chủ động lựa chọn bước tiếp theo.', 'years' => 11, 'rating' => 4.8, 'completed' => 1430, 'fee' => 900000, 'services' => ['individual_depression', 'cbt', 'corporate']],
            ['key' => 'nguyen_thu_ha', 'email' => 'ha@mindguide.vn', 'name' => 'TS. Nguyễn Thu Hà', 'title' => 'Tiến sĩ Tâm lý Trẻ em & Gia đình', 'expertise' => 'Trẻ em, tuổi teen, nuôi dạy con và trị liệu hệ thống gia đình', 'credentials' => 'Tiến sĩ Tâm lý học phát triển; Chứng chỉ Trị liệu Hệ thống Gia đình; Chứng chỉ Can thiệp Tâm lý Trẻ em và Tuổi teen.', 'bio' => 'TS. Nguyễn Thu Hà làm việc gần gũi với trẻ em, phụ huynh và cả hệ thống gia đình xung quanh trẻ. Chị có hơn 9 năm kinh nghiệm trong tâm lý học đường, khó khăn hành vi và các giai đoạn chuyển tiếp của gia đình. Chị luôn bắt đầu bằng việc lắng nghe điều trẻ muốn nói theo cách riêng của mình.', 'years' => 9, 'rating' => 4.9, 'completed' => 740, 'fee' => 650000, 'services' => ['couple', 'family', 'teen', 'child']],
            ['key' => 'tran_minh_khoa', 'email' => 'khoa@mindguide.vn', 'name' => 'ThS. Trần Minh Khoa', 'title' => 'Chuyên gia Tâm lý Cặp đôi & Hôn nhân', 'expertise' => 'Giao tiếp cặp đôi, xung đột hôn nhân và phục hồi niềm tin', 'credentials' => 'Thạc sĩ Tâm lý học gia đình; Chứng chỉ Trị liệu Cặp đôi Gottman; Chứng chỉ Trị liệu Hệ thống Gia đình.', 'bio' => 'ThS. Trần Minh Khoa tập trung giúp các cặp đôi chuyển từ tranh luận sang đối thoại có trách nhiệm. Anh có 8 năm thực hành với xung đột hôn nhân, khác biệt nuôi dạy con và phục hồi niềm tin. Tâm niệm của anh là mối quan hệ không cần hoàn hảo, nhưng cần đủ an toàn để hai người tiếp tục học cách hiểu nhau.', 'years' => 8, 'rating' => 4.9, 'completed' => 520, 'fee' => 950000, 'services' => ['couple', 'family']],
        ];

        $models = [];
        foreach ($profiles as $index => $profile) {
            $user = $users[$profile['email']];
            $specialist = SpecialistProfile::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'avatar_url' => $this->avatarUrl($index),
                    'title' => $profile['title'],
                    'expertise' => $profile['expertise'],
                    'credentials' => $profile['credentials'],
                    'bio' => $profile['bio'],
                    'years_of_experience' => $profile['years'],
                    'completed_appointments' => $profile['completed'],
                    'average_rating' => $profile['rating'],
                    'consultation_fee' => $profile['fee'],
                    'status' => 'ACTIVE',
                    'display_order' => $index + 1,
                ]
            );
            $specialist->services()->sync(collect($profile['services'])->map(fn (string $key): int => $services[$key]->id)->all());
            $this->seedWorkingSchedules($specialist);
            $models[$profile['key']] = $specialist;
        }

        return $models;
    }

    private function seedWorkingSchedules(SpecialistProfile $specialist): void
    {
        $shifts = [
            ['start_time' => '08:00', 'end_time' => '12:00'],
            ['start_time' => '13:30', 'end_time' => '17:30'],
            ['start_time' => '18:00', 'end_time' => '20:00'],
        ];

        foreach (range(1, 6) as $day) {
            foreach ($shifts as $shift) {
                WorkingSchedule::updateOrCreate(
                    ['specialist_profile_id' => $specialist->id, 'day_of_week' => $day, 'start_time' => $shift['start_time']],
                    [...$shift, 'is_active' => true, 'effective_from' => now()->toDateString()]
                );
            }
        }
    }

    /** @param array<string, User> $users @param array<string, SpecialistProfile> $specialists @param array<string, Service> $services */
    private function seedAppointments(array $users, array $specialists, array $services): void
    {
        $staff = $users['staff@mindguide.vn'];
        $appointments = [
            ['code' => 'MG-DEMO-PENDING', 'customer' => 'customer@mindguide.vn', 'specialist' => 'nguyen_van_an', 'service' => 'individual_anxiety', 'days' => 2, 'hour' => 9, 'status' => 'PENDING', 'payment_status' => 'UNPAID', 'payment_method' => 'BANK_QR', 'note' => 'Muốn trao đổi về tình trạng mất ngủ và lo âu gần đây.'],
            ['code' => 'MG-DEMO-CONFIRMED', 'customer' => 'customer@mindguide.vn', 'specialist' => 'vu_thi_mai', 'service' => 'individual_depression', 'days' => 4, 'hour' => 14, 'status' => 'CONFIRMED', 'payment_status' => 'UNPAID', 'payment_method' => 'CASH', 'note' => 'Đây là buổi tư vấn đầu tiên của tôi.'],
            ['code' => 'MG-DEMO-COMPLETED', 'customer' => 'customer2@mindguide.vn', 'specialist' => 'le_hoang_nam', 'service' => 'cbt', 'days' => -5, 'hour' => 10, 'status' => 'COMPLETED', 'payment_status' => 'PAID', 'payment_method' => 'CASH', 'note' => null],
            ['code' => 'MG-DEMO-CANCELLED', 'customer' => 'customer2@mindguide.vn', 'specialist' => 'tran_minh_khoa', 'service' => 'couple', 'days' => -8, 'hour' => 15, 'status' => 'CANCELLED_BY_CUSTOMER', 'payment_status' => 'REFUNDED', 'payment_method' => 'BANK_QR', 'note' => 'Khách có việc đột xuất và xin hủy lịch.'],
            ['code' => 'MG-DEMO-NOSHOW', 'customer' => 'customer@mindguide.vn', 'specialist' => 'nguyen_thu_ha', 'service' => 'teen', 'days' => -12, 'hour' => 18, 'status' => 'NO_SHOW', 'payment_status' => 'UNPAID', 'payment_method' => 'CASH', 'note' => null],
        ];

        foreach ($appointments as $data) {
            $service = $services[$data['service']];
            $start = CarbonImmutable::now()->addDays($data['days'])->setTime($data['hour'], 0);
            $end = $start->addMinutes($service->duration_minutes);
            $isHolding = in_array($data['status'], ['PENDING', 'CONFIRMED', 'RESCHEDULE_REQUESTED', 'IN_PROGRESS'], true);
            $isConfirmed = in_array($data['status'], ['CONFIRMED', 'COMPLETED', 'NO_SHOW'], true);
            $isCompleted = in_array($data['status'], ['COMPLETED', 'NO_SHOW'], true);
            $isCancelled = str_starts_with($data['status'], 'CANCELLED_');

            $appointment = Appointment::updateOrCreate(
                ['appointment_code' => $data['code']],
                [
                    'customer_id' => $users[$data['customer']]->id,
                    'specialist_profile_id' => $specialists[$data['specialist']]->id,
                    'service_id' => $service->id,
                    'start_at' => $start,
                    'end_at' => $end,
                    'duration_minutes' => $service->duration_minutes,
                    'buffer_minutes' => $service->buffer_minutes,
                    'price' => $service->price,
                    'status' => $data['status'],
                    'payment_status' => $data['payment_status'],
                    'payment_method' => $data['payment_method'],
                    'source' => 'ONLINE',
                    'customer_note' => $data['note'],
                    'created_by' => $users[$data['customer']]->id,
                    'slot_lock_key' => $isHolding ? $specialists[$data['specialist']]->id.'|'.$start->format('Y-m-d H:i:s') : null,
                    'needs_attention' => false,
                    'confirmed_at' => $isConfirmed ? $start->subDay() : null,
                    'completed_at' => $isCompleted ? $end : null,
                    'cancelled_at' => $isCancelled ? $start->subDays(2) : null,
                    'cancel_reason' => $isCancelled ? 'Khách đã yêu cầu hủy lịch trong thời hạn cho phép.' : null,
                ]
            );

            $appointment->statusHistory()->delete();
            $history = [['to_status' => $data['status'], 'action' => 'create', 'actor_id' => $appointment->created_by, 'actor_role' => 'CUSTOMER']];
            if ($isConfirmed && $data['status'] !== 'CONFIRMED') {
                $history[] = ['from_status' => 'CONFIRMED', 'to_status' => $data['status'], 'action' => strtolower($data['status']), 'actor_id' => $staff->id, 'actor_role' => 'STAFF'];
            }
            foreach ($history as $item) {
                $appointment->statusHistory()->create($item);
            }
        }
    }

    private function avatarUrl(int $index): string
    {
        return [
            'https://images.unsplash.com/photo-1494790108377-be9c29b29330?auto=format&fit=crop&w=640&q=85',
            'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?auto=format&fit=crop&w=640&q=85',
            'https://images.unsplash.com/photo-1506794778202-cad84cf45f1d?auto=format&fit=crop&w=640&q=85',
            'https://images.unsplash.com/photo-1544725176-7c40e5a71c5e?auto=format&fit=crop&w=640&q=85',
            'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=640&q=85',
        ][$index];
    }

    /** @param array<string, User> $users @param array<string, SpecialistProfile> $specialists @param array<string, Service> $services */
    private function seedAnalyticsAppointments(array $users, array $specialists, array $services): void
    {
        $customers = array_values(array_filter($users, fn (User $user): bool => $user->role === 'customer'));
        $specialistModels = array_values($specialists);
        $serviceModels = array_values($services);
        $startDate = CarbonImmutable::now()->subDays(60)->startOfDay();

        for ($index = 1; $index <= 90; $index++) {
            $start = $startDate->addDays($index)->setTime(9 + ($index % 5) * 2, 0);
            $service = $serviceModels[$index % count($serviceModels)];
            $specialist = $specialistModels[$index % count($specialistModels)];
            $customer = $customers[$index % count($customers)];
            $status = $this->analyticsStatus($index, $start);
            $paymentMethod = $index % 10 < 7 ? 'BANK_QR' : 'CASH';
            $isCompleted = in_array($status, ['COMPLETED', 'NO_SHOW'], true);
            $isRefunded = $status === 'CANCELLED_BY_CUSTOMER' && $index % 2 === 0;
            $isRefundPending = $status === 'CANCELLED_BY_CUSTOMER' && $index % 2 === 1;
            $isPaid = $isCompleted || $isRefunded || $isRefundPending;
            $price = (int) $service->price;

            Appointment::updateOrCreate(
                ['appointment_code' => sprintf('MG-SEED-%03d', $index)],
                [
                    'customer_id' => $customer->id,
                    'specialist_profile_id' => $specialist->id,
                    'service_id' => $service->id,
                    'start_at' => $start,
                    'end_at' => $start->addMinutes($service->duration_minutes),
                    'duration_minutes' => $service->duration_minutes,
                    'buffer_minutes' => $service->buffer_minutes,
                    'price' => $price,
                    'status' => $status,
                    'payment_status' => $isRefunded ? 'REFUNDED' : ($isRefundPending ? 'REFUND_PENDING' : ($isPaid ? 'PAID' : 'UNPAID')),
                    'paid_at' => $isPaid ? $start->subDays(1) : null,
                    'refunded_at' => $isRefunded ? $start->subHours(8) : null,
                    'refund_amount' => ($isRefunded || $isRefundPending) ? (int) floor($price * 0.5) : null,
                    'refund_status' => $isRefunded ? 'REFUNDED' : ($isRefundPending ? 'REFUND_PENDING' : null),
                    'payment_method' => $paymentMethod,
                    'source' => $index % 4 === 0 ? 'STAFF' : 'ONLINE',
                    'customer_note' => null,
                    'created_by' => $customer->id,
                    'slot_lock_key' => in_array($status, ['PENDING', 'CONFIRMED'], true) ? $specialist->id.'|'.$start->format('Y-m-d H:i:s') : null,
                    'needs_attention' => $index % 17 === 0,
                    'confirmed_at' => $status !== 'PENDING' ? $start->subHours(12) : null,
                    'completed_at' => $isCompleted ? $start->addMinutes($service->duration_minutes) : null,
                    'cancelled_at' => str_starts_with($status, 'CANCELLED_') ? $start->subDays(2) : null,
                    'cancel_reason' => str_starts_with($status, 'CANCELLED_') ? 'Dữ liệu mẫu kiểm thử vận hành.' : null,
                ]
            );
        }
    }

    private function analyticsStatus(int $index, CarbonImmutable $start): string
    {
        if ($start->isFuture()) {
            return $index % 3 === 0 ? 'PENDING' : 'CONFIRMED';
        }
        if ($index % 13 === 0) {
            return 'CANCELLED_BY_CUSTOMER';
        }
        if ($index % 11 === 0) {
            return 'NO_SHOW';
        }

        return $index % 7 === 0 ? 'CANCELLED_BY_CLINIC' : 'COMPLETED';
    }

    /** @param array<string, User> $users */
    private function seedOperationalData(array $users): void
    {
        foreach ($users as $user) {
            if ($user->role === 'customer') {
                CustomerProfile::query()->updateOrCreate(
                    ['user_id' => $user->id],
                    ['customer_code' => 'KH-'.str_pad((string) $user->id, 5, '0', STR_PAD_LEFT), 'date_of_birth' => '1995-01-01', 'privacy_policy_version' => '2026-01', 'consented_at' => now()]
                );
            }
        }

        $settings = [
            ['key' => 'min_booking_lead_hours', 'value' => '2', 'type' => 'integer', 'group' => 'booking', 'label' => 'Số giờ tối thiểu trước khi đặt'],
            ['key' => 'max_advance_booking_days', 'value' => '30', 'type' => 'integer', 'group' => 'booking', 'label' => 'Số ngày được đặt trước tối đa'],
            ['key' => 'max_active_appointments', 'value' => '3', 'type' => 'integer', 'group' => 'booking', 'label' => 'Số lịch hoạt động tối đa'],
            ['key' => 'start_early_minutes', 'value' => '15', 'type' => 'integer', 'group' => 'appointment', 'label' => 'Số phút được bắt đầu sớm'],
            ['key' => 'no_show_grace_minutes', 'value' => '15', 'type' => 'integer', 'group' => 'appointment', 'label' => 'Số phút chờ trước khi no-show'],
            ['key' => 'review_window_days', 'value' => '30', 'type' => 'integer', 'group' => 'review', 'label' => 'Số ngày được gửi đánh giá'],
            ['key' => 'email_verification_required', 'value' => '0', 'type' => 'boolean', 'group' => 'authentication', 'label' => 'Bắt buộc xác thực email'],
            ['key' => 'email_notifications_enabled', 'value' => '0', 'type' => 'boolean', 'group' => 'notification', 'label' => 'Bật gửi thông báo qua email'],
        ];
        foreach ($settings as $setting) {
            SystemSetting::query()->updateOrCreate(['key' => $setting['key']], $setting);
        }

        $permissionData = [
            'appointments.manage' => ['Quản lý lịch hẹn', 'appointment'],
            'customers.manage' => ['Quản lý khách hàng', 'customer'],
            'payments.manage' => ['Quản lý thanh toán', 'payment'],
            'specialists.manage' => ['Quản lý chuyên viên', 'specialist'],
            'services.manage' => ['Quản lý dịch vụ', 'service'],
            'reports.view' => ['Xem báo cáo', 'report'],
            'audit.view' => ['Xem nhật ký audit', 'security'],
        ];
        $permissions = collect($permissionData)->mapWithKeys(function (array $data, string $code): array {
            $permission = Permission::query()->updateOrCreate(['code' => $code], ['name' => $data[0], 'group' => $data[1]]);

            return [$code => $permission];
        });
        foreach (['customer' => [], 'specialist' => ['appointments.manage'], 'staff' => ['appointments.manage', 'customers.manage', 'payments.manage'], 'admin' => array_keys($permissionData)] as $code => $codes) {
            $role = Role::query()->updateOrCreate(['code' => $code], ['name' => ucfirst($code), 'is_system' => true]);
            $role->permissions()->sync($permissions->only($codes)->pluck('id'));
        }

        Appointment::query()->where('status', 'COMPLETED')->whereDoesntHave('review')->with('customer')->limit(6)->get()->each(function (Appointment $appointment, int $index): void {
            Review::query()->updateOrCreate(['appointment_id' => $appointment->id], ['customer_id' => $appointment->customer_id, 'specialist_profile_id' => $appointment->specialist_profile_id, 'rating' => 5 - ($index % 2), 'comment' => 'Chuyên viên lắng nghe kỹ và đưa ra hướng dẫn rõ ràng, phù hợp.', 'status' => 'VISIBLE']);
        });

        Appointment::query()->where('payment_status', 'REFUND_PENDING')->whereDoesntHave('refundRequest')->get()->each(function (Appointment $appointment): void {
            RefundRequest::query()->create(['appointment_id' => $appointment->id, 'amount' => (int) ($appointment->refund_amount ?? 0), 'percentage' => 50, 'status' => 'PENDING']);
        });
    }
}
