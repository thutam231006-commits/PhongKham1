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
            'huong.tran@mindguide.vn' => ['name' => 'Trần Thị Hương', 'role' => 'specialist', 'phone' => '0909234567', 'password' => 'Specialist@2026'],
            'cuong.nguyen@mindguide.vn' => ['name' => 'Nguyễn Mạnh Cường', 'role' => 'specialist', 'phone' => '0909678901', 'password' => 'Specialist@2026'],
            'ha.vo@mindguide.vn' => ['name' => 'Võ Kim Hà', 'role' => 'specialist', 'phone' => '0909789012', 'password' => 'Specialist@2026'],
            'hanh.pham@mindguide.vn' => ['name' => 'Phạm Thị Mỹ Hạnh', 'role' => 'specialist', 'phone' => '0909890123', 'password' => 'Specialist@2026'],
            'long.pham@mindguide.vn' => ['name' => 'Phạm Hoàng Long', 'role' => 'specialist', 'phone' => '0909901234', 'password' => 'Specialist@2026'],
            'nga.le@mindguide.vn' => ['name' => 'Lê Thị Nga', 'role' => 'specialist', 'phone' => '0909912345', 'password' => 'Specialist@2026'],
            'staff@mindguide.vn' => ['name' => 'Lê Thu Hương', 'role' => 'staff', 'phone' => '0909345678', 'password' => 'Reception@2026'],
            'thuy.le@mindguide.vn' => ['name' => 'Lê Minh Thùy', 'role' => 'staff', 'phone' => '0909345679', 'password' => 'Reception@2026'],
            'minh.nguyen@mindguide.vn' => ['name' => 'Nguyễn Hoàng Minh', 'role' => 'staff', 'phone' => '0909345680', 'password' => 'Reception@2026'],
            'anh.tran@mindguide.vn' => ['name' => 'Trần Ngọc Anh', 'role' => 'staff', 'phone' => '0909345681', 'password' => 'Reception@2026'],
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

        $renamedNames = [
            'Tư vấn Lo âu & Cân bằng cảm xúc' => 'Rối loạn Lo âu',
            'Đồng hành cùng Trầm cảm & Kiệt sức' => 'Trầm cảm',
            'Trị liệu Nhận thức Hành vi CBT' => 'Rối loạn Cảm xúc & Điều hòa cảm xúc',
            'Tham vấn Cặp đôi & Hôn nhân' => 'Hòa hợp Mối quan hệ',
            'Trị liệu Hệ thống Gia đình' => 'Tư vấn Gia đình',
            'Tâm lý Học đường & Tuổi teen' => 'Hỗ trợ Tâm lý Học đường',
            'Đánh giá & Hỗ trợ Tâm lý Trẻ em' => 'Đồng hành Học sinh & Tuổi teen',
            'Đánh giá Sức khỏe Tinh thần Doanh nghiệp' => 'Tìm điểm Cân bằng trong Cuộc sống',
        ];
        foreach ($renamedNames as $oldName => $newName) {
            Service::query()->where('name', $oldName)->update(['name' => $newName]);
        }

        $models = [];
        foreach ($services as $index => $serviceData) {
            $serviceData['name'] = $renamedNames[$serviceData['name']] ?? $serviceData['name'];
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

        $catalog = [
            'individual_anxiety' => ['name' => 'Rối loạn Lo âu', 'audience' => 'Đồng hành với cảm giác lo lắng kéo dài, hồi hộp, bất an, suy nghĩ quá mức và những phản ứng khiến sinh hoạt hằng ngày trở nên nặng nề.', 'process' => 'Đánh giá bối cảnh và mức độ lo âu; nhận diện vòng lặp suy nghĩ - cảm xúc - hành vi; thực hành kỹ năng điều hòa và xây dựng kế hoạch theo dõi cá nhân.'],
            'individual_depression' => ['name' => 'Trầm cảm', 'audience' => 'Hỗ trợ người đang trải qua buồn bã kéo dài, giảm năng lượng, mất hứng thú, tự trách hoặc khó duy trì nhịp sống thường ngày.', 'process' => 'Lắng nghe an toàn; sàng lọc nhu cầu và rủi ro; xác định mục tiêu nhỏ có thể thực hiện; theo dõi tiến triển và phối hợp chuyển tuyến khi cần.'],
            'cbt' => ['name' => 'Rối loạn Cảm xúc & Điều hòa cảm xúc', 'audience' => 'Dành cho người gặp khó khăn trong việc nhận diện, diễn đạt và điều chỉnh cảm xúc hoặc thường phản ứng quá mạnh trước áp lực.', 'process' => 'Nhận diện tác nhân; quan sát phản ứng cơ thể; luyện kỹ thuật CBT và điều hòa cảm xúc; xây dựng bài tập ứng phó phù hợp.'],
            'couple' => ['name' => 'Hòa hợp Mối quan hệ', 'audience' => 'Hỗ trợ cặp đôi và người thân cải thiện giao tiếp, xử lý xung đột, khác biệt kỳ vọng và những tổn thương trong mối quan hệ.', 'process' => 'Thiết lập nguyên tắc đối thoại; làm rõ nhu cầu của mỗi bên; nhận diện chu kỳ xung đột; thực hành cách lắng nghe và phản hồi an toàn.'],
            'family' => ['name' => 'Tư vấn Gia đình', 'audience' => 'Dành cho gia đình đang gặp khó khăn trong giao tiếp, nuôi dạy con, thay đổi vai trò hoặc thích nghi sau biến cố.', 'process' => 'Khám phá hệ thống quan hệ; xác định nguồn lực; tạo không gian để các thành viên được lắng nghe; thống nhất thay đổi nhỏ tại nhà.'],
            'teen' => ['name' => 'Hỗ trợ Tâm lý Học đường', 'audience' => 'Đồng hành với học sinh trước áp lực thi cử, học tập, bạn bè, tự tin, thay đổi tâm sinh lý và định hướng tương lai.', 'process' => 'Tạo không gian không phán xét; lắng nghe góc nhìn của học sinh; hỗ trợ phụ huynh giao tiếp hiệu quả; thống nhất kế hoạch theo dõi.'],
            'child' => ['name' => 'Đồng hành Học sinh & Tuổi teen', 'audience' => 'Hỗ trợ trẻ và thanh thiếu niên có thay đổi về cảm xúc, hành vi, giấc ngủ, học tập hoặc khó thích nghi với môi trường mới.', 'process' => 'Trao đổi với người chăm sóc; đánh giá phù hợp lứa tuổi; xác định yếu tố bảo vệ; đưa ra hướng dẫn thực hành cho trẻ và gia đình.'],
            'corporate' => ['name' => 'Tìm điểm Cân bằng trong Cuộc sống', 'audience' => 'Dành cho người đang mất cân bằng giữa công việc, học tập, gia đình và nhu cầu chăm sóc bản thân.', 'process' => 'Nhìn lại ưu tiên; nhận diện nguồn gây quá tải; thiết kế nhịp sinh hoạt bền vững; xây dựng ranh giới và kế hoạch phục hồi năng lượng.'],
        ];
        foreach ($models as $key => $model) {
            $content = $catalog[$key];
            $model->update(['name' => $content['name'], 'description' => $content['audience'], 'target_audience' => $content['audience'], 'session_process' => $content['process']]);
        }
        $models['sleep'] = Service::updateOrCreate(['name' => 'Mất ngủ & Phục hồi giấc ngủ'], ['category_id' => $categoryModels['individual']->id, 'description' => 'Hỗ trợ người khó đi vào giấc ngủ, ngủ không sâu, thức giấc nhiều lần hoặc mệt mỏi do nhịp ngủ bị xáo trộn.', 'image_url' => 'https://images.unsplash.com/photo-1515894203077-9cd36032142f?auto=format&fit=crop&w=1200&q=85', 'target_audience' => 'Người trưởng thành đang gặp khó khăn với giấc ngủ hoặc lịch sinh hoạt thất thường.', 'session_process' => 'Tìm hiểu nhịp ngủ và thói quen liên quan; nhận diện lo âu trước khi ngủ; thiết kế vệ sinh giấc ngủ và kế hoạch theo dõi.', 'duration_minutes' => 60, 'buffer_minutes' => 10, 'price' => 600000, 'status' => 'ACTIVE', 'display_order' => 9]);
        $activeNames = collect($catalog)->pluck('name')->push('Mất ngủ & Phục hồi giấc ngủ')->all();
        Service::query()->whereNotIn('name', $activeNames)->update(['status' => 'INACTIVE']);

        return $models;
    }

    /** @param array<string, User> $users @param array<string, Service> $services @return array<string, SpecialistProfile> */
    private function seedSpecialists(array $users, array $services): array
    {
        $profiles = [
            [
                'key' => 'tran_thi_huong',
                'email' => 'huong.tran@mindguide.vn',
                'name' => 'Trần Thị Hương',
                'title' => 'Chuyên gia Tâm lý - Trưởng ban Kết nối Yêu thương',
                'expertise' => 'Tham vấn, trị liệu stress, trầm cảm, lo âu, hàn gắn quan hệ gia đình & nuôi dạy con',
                'credentials' => 'Chứng chỉ Nhà thôi miên trị liệu (ABH); Nhà thực hành Timeline Therapy; Hoàn thành đào tạo ACT cho thanh thiếu niên (Viện Tâm lý Việt - Pháp).',
                'bio' => 'Chuyên gia Trần Thị Hương hiện là Trưởng ban Kết nối Yêu thương tại Trung tâm Tâm lý NHC Việt Nam. Với phương châm "Bởi vì tôi ở đây nên bạn sẽ ổn thôi", chị luôn đồng hành tận tâm, tạo không gian an toàn để thân chủ giải tỏa cảm xúc và hàn gắn các mối quan hệ rạn nứt.',
                'years' => 9,
                'rating' => 4.9,
                'completed' => 860,
                'fee' => 750000,
                'avatar' => '/images/specialists/tran-thi-huong.png',
                'services' => ['individual_anxiety', 'individual_depression', 'couple', 'family'],
            ],
            [
                'key' => 'nguyen_manh_cuong',
                'email' => 'cuong.nguyen@mindguide.vn',
                'name' => 'Nguyễn Mạnh Cường',
                'title' => 'Chuyên gia Tâm lý - Trưởng Dự án Cai nghiện Thế giới Ảo',
                'expertise' => 'Cai nghiện game & cá độ online, trầm cảm, rối loạn lo âu, căng thẳng thanh thiếu niên',
                'credentials' => 'Chứng chỉ Nhà thôi miên trị liệu (ABH); Nhà thực hành Liệu pháp Dòng thời gian (TLTA); Master Practitioner NLP (ABNLP).',
                'bio' => 'Chuyên gia Nguyễn Mạnh Cường phụ trách dự án chuyên sâu hỗ trợ người nghiện game trực tuyến, cá độ online và khủng hoảng thế giới số. Anh kết hợp liệu pháp tâm lý hiện đại giúp thân chủ tái thiết lập mục tiêu sống lành mạnh.',
                'years' => 8,
                'rating' => 4.9,
                'completed' => 740,
                'fee' => 800000,
                'avatar' => '/images/specialists/nguyen-manh-cuong.png',
                'services' => ['individual_anxiety', 'individual_depression', 'teen', 'child', 'corporate'],
            ],
            [
                'key' => 'vo_kim_ha',
                'email' => 'ha.vo@mindguide.vn',
                'name' => 'Võ Kim Hà',
                'title' => 'Chuyên gia Tâm lý Trị liệu',
                'expertise' => 'Trầm cảm, rối loạn lo âu, khủng hoảng tâm lý hiện sinh, mâu thuẫn mối quan hệ',
                'credentials' => 'Chứng nhận Tư vấn Trị liệu Chuyên nghiệp Quốc tế (ICTA); Chứng chỉ Bồi dưỡng Tâm lý học Lâm sàng (ĐH Sư phạm TP.HCM).',
                'bio' => 'Với triết lý "Sự chân thành sẽ chạm trái tim", Chuyên gia Võ Kim Hà có bề dày trải nghiệm và sự thấu cảm sâu sắc, hỗ trợ khách hàng giải tỏa những nút thắt tâm lý sâu kín và tìm lại bình an nội tại.',
                'years' => 12,
                'rating' => 5.0,
                'completed' => 1120,
                'fee' => 850000,
                'avatar' => '/images/specialists/vo-kim-ha.png',
                'services' => ['individual_anxiety', 'individual_depression', 'cbt', 'couple', 'corporate'],
            ],
            [
                'key' => 'pham_thi_my_hanh',
                'email' => 'hanh.pham@mindguide.vn',
                'name' => 'Phạm Thị Mỹ Hạnh',
                'title' => 'Chuyên gia Tâm lý - Coach Chữa lành & Tái tạo Cảm xúc',
                'expertise' => 'Quản trị cảm xúc, lo âu, chữa lành tổn thương tâm lý tuổi trẻ, phát triển cá nhân',
                'credentials' => 'Chứng chỉ Nhà Huấn luyện Quốc tế Master Coach NLP (ABNLP); Đào tạo Chuyên sâu về Tâm lý Tham vấn & Chữa lành.',
                'bio' => 'Theo đuổi phương châm "Định nghĩa lại trải nghiệm. Khai mở tâm thức. Tái thiết kế cuộc đời", Chuyên gia Phạm Thị Mỹ Hạnh mang đến nguồn năng lượng chuyển hóa tích cực, giúp người trẻ vượt qua bế tắc và tự tin làm chủ cuộc sống.',
                'years' => 7,
                'rating' => 4.9,
                'completed' => 620,
                'fee' => 700000,
                'avatar' => '/images/specialists/pham-thi-my-hanh.png',
                'services' => ['individual_anxiety', 'cbt', 'teen', 'corporate'],
            ],
            [
                'key' => 'pham_hoang_long',
                'email' => 'long.pham@mindguide.vn',
                'name' => 'Phạm Hoàng Long',
                'title' => 'Chuyên gia Tâm lý - Chuyên gia Tham vấn Song ngữ',
                'expertise' => 'Trầm cảm, rối loạn lo âu, mất ngủ, khủng hoảng người trẻ, hòa hợp mối quan hệ (Việt - Anh)',
                'credentials' => 'Học viên Thạc sĩ Tâm lý học; Chứng chỉ Nhà thôi miên trị liệu (ABH); Timeline Therapy (TLTA); NLP Practitioner (ABNLP); Giảng dạy Kỹ năng sống (ĐH KHXH&NV TP.HCM).',
                'bio' => 'Chuyên gia Phạm Hoàng Long đồng hành cùng thân chủ tuổi từ 14-60, đặc biệt thanh niên đối diện với tự ti, mất động lực và sợ giao tiếp xã hội. Triết lý: "Chỉ cần một người để có mối quan hệ hòa hợp và thành công!". Hỗ trợ song ngữ Anh - Việt.',
                'years' => 8,
                'rating' => 4.9,
                'completed' => 890,
                'fee' => 750000,
                'avatar' => '/images/specialists/pham-hoang-long.png',
                'services' => ['individual_anxiety', 'individual_depression', 'cbt', 'couple', 'corporate', 'sleep'],
            ],
            [
                'key' => 'le_thi_nga',
                'email' => 'nga.le@mindguide.vn',
                'name' => 'Lê Thị Nga',
                'title' => 'Chuyên gia Tâm lý Trị liệu',
                'expertise' => 'Trầm cảm, lo âu kéo dài, trị liệu dòng thời gian, hàn gắn tổn thương gia đình',
                'credentials' => 'Chứng chỉ Nhà thôi miên trị liệu (ABH); Nhà thực hành Timeline Therapy (TLTA); Đào tạo chuyên gia trị liệu tâm lý phát triển con người.',
                'bio' => 'Triết lý hành nghề: "Tình yêu thương, lòng biết ơn và sự hiểu biết đúng là nguồn sức mạnh vô hình, phá bỏ mọi giới hạn". Chuyên gia Lê Thị Nga đem lại không gian thấu hiểu sâu sắc, giúp thân chủ giải tỏa âu lo và chuyển hóa cuộc sống.',
                'years' => 9,
                'rating' => 4.9,
                'completed' => 910,
                'fee' => 750000,
                'avatar' => '/images/specialists/le-thi-nga.png',
                'services' => ['individual_anxiety', 'individual_depression', 'couple', 'family', 'corporate'],
            ],
        ];

        $models = [];
        foreach ($profiles as $index => $profile) {
            $user = $users[$profile['email']];
            $specialist = SpecialistProfile::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'avatar_url' => $profile['avatar'],
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
            ['code' => 'MG-HIST-001', 'customer' => 'customer@mindguide.vn', 'specialist' => 'tran_thi_huong', 'service' => 'individual_anxiety', 'days' => -2, 'hour' => 9, 'status' => 'COMPLETED', 'payment_status' => 'PAID', 'payment_method' => 'BANK_QR', 'note' => 'Buổi tư vấn về tình trạng mất ngủ và lo âu.'],
            ['code' => 'MG-HIST-002', 'customer' => 'customer@mindguide.vn', 'specialist' => 'cuong.nguyen', 'specialist_key' => 'nguyen_manh_cuong', 'service' => 'individual_depression', 'days' => -4, 'hour' => 14, 'status' => 'COMPLETED', 'payment_status' => 'PAID', 'payment_method' => 'CASH', 'note' => 'Đánh giá ban đầu.'],
            ['code' => 'MG-HIST-003', 'customer' => 'customer2@mindguide.vn', 'specialist_key' => 'vo_kim_ha', 'service' => 'cbt', 'days' => -5, 'hour' => 10, 'status' => 'COMPLETED', 'payment_status' => 'PAID', 'payment_method' => 'CASH', 'note' => null],
            ['code' => 'MG-HIST-004', 'customer' => 'customer2@mindguide.vn', 'specialist_key' => 'pham_thi_my_hanh', 'service' => 'couple', 'days' => -8, 'hour' => 15, 'status' => 'CANCELLED_BY_CUSTOMER', 'payment_status' => 'REFUNDED', 'payment_method' => 'BANK_QR', 'note' => 'Khách có việc đột xuất và xin hủy lịch.'],
            ['code' => 'MG-HIST-005', 'customer' => 'customer@mindguide.vn', 'specialist_key' => 'pham_hoang_long', 'service' => 'teen', 'days' => -12, 'hour' => 18, 'status' => 'NO_SHOW', 'payment_status' => 'UNPAID', 'payment_method' => 'CASH', 'note' => null],
        ];

        foreach ($appointments as $data) {
            $specKey = $data['specialist_key'] ?? ($data['specialist'] ?? 'tran_thi_huong');
            $specialist = $specialists[$specKey];
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
                    'specialist_profile_id' => $specialist->id,
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
                    'slot_lock_key' => $isHolding ? $specialist->id.'|'.$start->format('Y-m-d H:i:s') : null,
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

        // Chỉ tạo lịch đã qua trong quá khứ (index 1..59) để báo cáo/thống kê hoạt động chính xác
        // Không tạo lịch tương lai để lịch trống hoàn toàn sạch, cho phép 4 actor đặt lịch thực tế không bị cản trở
        for ($index = 1; $index <= 58; $index++) {
            $start = $startDate->addDays($index)->setTime(9 + ($index % 5) * 2, 0);
            if ($start->isFuture()) {
                continue;
            }
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
