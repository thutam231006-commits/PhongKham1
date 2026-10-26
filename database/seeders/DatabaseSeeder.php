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
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $users = $this->seedUsers();
        $services = $this->seedServices();
        $specialists = $this->seedSpecialists($users, $services);

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
            [
                'key' => 'individual_anxiety',
                'summary' => 'Hỗ trợ lo âu, hồi hộp và căng thẳng kéo dài bằng CBT, ACT và chánh niệm thực tế.',
                'category' => 'individual',
                'name' => 'Rối loạn Lo âu',
                'duration' => 60,
                'price' => 650000,
                'image' => 'https://images.unsplash.com/photo-1499209974431-9dddcece7f88?auto=format&fit=crop&w=1200&q=85',
                'introduction' => "Rối loạn lo âu (bao gồm rối loạn lo âu lan toả, hồi hộp và sợ hãi) là một trong những vấn đề tâm lý phổ biến nhất tại Việt Nam. Người bệnh thường trải qua trạng thái lo lắng không dứt dù không có mối đe dọa rõ ràng, kèm theo hồi hộp, căng cơ, tim đập nhanh, khó ngủ và suy nghĩ vòng quanh về những điều xấu có thể xảy ra.\n\nGói tư vấn Rối loạn Lo âu của Mind Guide được thiết kế theo hướng tâm lý học hiện đại, kết hợp liệu pháp nhận thức – hành vi (CBT), các kỹ thuật chấp nhận và đồng cảm (ACT) cùng những bài tập chánh niệm thực tế. Chuyên gia không chỉ giúp bạn hiểu vì sao lo âu xuất hiện mà còn hướng dẫn cách thay đổi mối quan hệ với chính nỗi lo, để bạn tìm lại sự bình an trong cuộc sống thường ngày mà không phải né tránh mọi thứ.",
                'audience' => "Người trưởng thành đang trải qua lo âu, căng thẳng kéo dài, mất ngủ hoặc khó điều chỉnh cảm xúc.\n\nDấu hiệu thường gặp:\n• Lo lắng không dứt về công việc, tài chính, sức khỏe hoặc gia đình\n• Hồi hộp, tim đập nhanh, căng cơ khi phải đối mặt với tình huống xã hội\n• Khó ngủ hoặc thức giấc giữa đêm với suy nghĩ tiêu cực\n• Suy nghĩ quá mức về những điều chưa xảy ra, kiểm soát hành vi lặp đi lặp lại\n• Khó tập trung, giảm hiệu quả làm việc và học tập\n• Né tránh các tình huống gây lo lắng ngày càng nhiều",
                'process' => "Buổi 1 — Đánh giá và nhận diện: Chuyên gia lắng nghe bối cảnh, đo mức độ lo âu và xác định vòng lặp suy nghĩ – cảm xúc – hành vi đang duy trì sự lo âu.\n\nBuổi 2 — Kỹ thuật CBT: Phân tích các niềm tin không hợp lý, thực hành tách biệt suy nghĩ và thử nghiệm hành vi an toàn (graded exposure).\n\nBuổi 3 — Chấp nhận và điều hòa: Luyện kỹ năng chịu đựng cảm xúc, chánh niệm và thay đổi mối quan hệ với nỗi lo.\n\nBuổi 4 trở đi — Củng cố: Ôn lại bài tập, điều chỉnh chiến lược, xây dựng kế hoạch duy trì sau khi kết thúc gói.",
                'benefits' => [
                    'Nhận diện chính xác cơ chế sinh ra lo âu thay vì chỉ đối phó triệu chứng bề mặt.',
                    'Có bộ kỹ năng tự điều hòa cảm xúc và thư giãn cơ thể áp dụng được ngay trong ngày.',
                    'Giảm dần sự né tránh và khôi phục khả năng tham gia các hoạt động, mối quan hệ đã bỏ rơi.',
                    'Cải thiện chất lượng giấc ngủ và khả năng tập trung trong công việc, học tập.',
                    'Xây dựng kế hoạch duy trì dài hạn để không tái phát lo âu.',
                ],
                'symptoms' => [
                    'Lo lắng không dứt, hồi hộp trong vô căn cứ',
                    'Tim đập nhanh, thở gấp, run tay khi lo',
                    'Mất ngủ hoặc giấc ngủ không sâu',
                    'Suy nghĩ quá mức, khó ngăn mình',
                    'Né tránh, xã hội hóa ngày càng thu hẹp',
                ],
            ],
            [
                'key' => 'individual_depression',
                'summary' => 'Đồng hành an toàn qua trầm cảm, khôi phục năng lượng và niềm tin vào bản thân.',
                'category' => 'individual',
                'name' => 'Trầm cảm',
                'duration' => 60,
                'price' => 700000,
                'image' => 'https://images.unsplash.com/photo-1544027993-37dbfe43562a?auto=format&fit=crop&w=1200&q=85',
                'introduction' => "Trầm cảm không chỉ là buồn bã. Đó là một trạng thái kiệt sức kéo dài, khi mọi thứ mất hết màu sắc: bạn không thấy vui điều gì, không còn động lực, mọi việc trở nên nặng nề đến mức tưởng chừng không thể hoàn thành. Người trong cuộc thường cảm thấy mình là gánh nặng, có lỗi với mọi thứ xảy ra xung quanh mình.\n\nGói tư vấn Trầm cảm của Mind Guide được xây dựng theo hướng đồng hành an toàn, kết hợp trị liệu nhận thức – hành vi, hoạt động hành vi và các bài tập chánh niệm. Chuyên gia sẽ đồng hành cùng bạn từng bước: khôi phục lại khả năng tận hưởng cuộc sống, xây dựng nền tảng tự giá lành mạnh và ngăn ngừa tái phát trong tương lai. Trong trường hợp cần thiết, chúng tôi phối hợp chuyển tuyến đến bác sĩ tâm thần để điều trị kết hợp.",
                'audience' => "Người cảm thấy mất năng lượng, giảm hứng thú, tự trách hoặc kiệt sức trong học tập và công việc.\n\nDấu hiệu thường gặp:\n• Tâm trạng u buồn, trống rỗng hoặc bất cảm kéo dài hơn 2 tuần\n• Mất hứng thú với mọi hoạt động từng yêu thích\n• Luôn mệt mỏi, thiếu năng lượng ngay cả khi đã ngủ đủ\n• Cảm giác vô dụng, tự trách hoặc tự thương lượng quá mức\n• Khó tập trung, quên nhớ, giảm quyết tâm trong công việc và học tập\n• Rối loạn giấc ngủ hoặc khẩu phần tăng đáng kể",
                'process' => "Buổi 1 — Lắng nghe và sàng lọc: Tạo không gian an toàn để bạn kể câu chuyện của mình, đồng thời đánh giá mức độ trầm cảm và rủi ro an toàn.\n\nBuổi 2 — Ổn định trước: Xây dựng những mục tiêu nhỏ, an toàn, khả thi để khôi phục cảm giác chủ động và thành công trong ngày.\n\nBuổi 3 — Xử lý suy nghĩ tiêu cực: Làm việc với các niềm tin tự trách, phân biệt ẩn dụ với sự thật, thực hành kỹ thuật nhận thức.\n\nBuổi 4 trở đi — Tái tạo ý nghĩa: Xác định lại giá trị, kết nối lại với điều có ý nghĩa, lập kế hoạch phòng ngừa tái phát.",
                'benefits' => [
                    'Hiểu rõ nguồn gốc cảm xúc trầm cảm thay vì tự trách móc bản thân.',
                    'Khôi phục dần năng lượng, khả năng tập trung và hứng thú với cuộc sống.',
                    'Có hệ thống bài tập cụ thể để thực hành tại nhà giữa các buổi.',
                    'Tăng cường cảm giác tự giá và khả năng đối mặt với thử thách.',
                    'Nhận diện sớm dấu hiệu cảnh báo để chủ động điều chỉnh trước khi trầm cảm tái phát.',
                ],
                'symptoms' => [
                    'Tâm trạng buồn bã, trống rỗng kéo dài',
                    'Mất hứng thú với mọi hoạt động',
                    'Mệt mỏi kéo dài, thiếu năng lượng',
                    'Cảm giác vô dụng, tự trách',
                    'Rối loạn giấc ngủ và khẩu phần',
                ],
            ],
            [
                'key' => 'cbt',
                'summary' => 'Trị liệu có cấu trúc theo trường phái nhận thức – hành vi, xử lý gốc rễ phản ứng cảm xúc.',
                'category' => 'individual',
                'name' => 'Rối loạn Cảm xúc & Điều hòa cảm xúc',
                'duration' => 60,
                'price' => 800000,
                'image' => 'https://images.unsplash.com/photo-1576091160399-112ba8d25d1d?auto=format&fit=crop&w=1200&q=85',
                'introduction' => "Không ít người hiểu cảm xúc của mình nhưng vẫn không kiểm soát được nó — cơn giận bùng lên trước khi kịp nhận ra, nỗi buồn kéo dài hàng tuần sau một câu nói vô tâm, hay nỗi buồn cũ bùng lên mỗi khi gặp lại mùi hương quen thuộc.\n\nGói Trị liệu Nhận thức – Hành vi (CBT) của Mind Guide là chương trình trị liệu có cấu trúc, dựa trên nền tảng khoa học được kiểm chứng quốc tế. Chuyên gia sẽ cùng bạn lập bản đồ vấn đề: từ tình huống kích hoạt, qua suy nghĩ tự động, đến cảm xúc và hành vi phản ứng. Từ đó, bạn được trang bị các kỹ thuật thực hành cụ thể để thay đổi mối quan hệ với cảm xúc thay vì chống chống lại nó.",
                'audience' => "Người muốn làm việc có hệ thống với suy nghĩ tự động, hành vi né tránh và các phản ứng lo âu.\n\nDấu hiệu thường gặp:\n• Cảm xúc thay đổi đột ngột, không kiểm soát được cơn giận hoặc khóc\n• Cảm xúc không khớp với hoàn cảnh (tức bất) và khó điều chỉnh\n• Lo lắng, giận dữ hoặc buồn bã lặp lại theo cùng một khuôn mẫu\n• Khó nhận diện cảm xúc của chính mình và của người khác\n• Phản ứng cơ thể mạnh (tim đập nhanh, nóng mặt, căng cơ) khi bị kích hoạt\n• Hay phản ứng quá mức với những vấn đề nhỏ",
                'process' => "Buổi 1 — Lập bản đồ vấn đề: Xác định tình huống kích hoạt, chuỗi suy nghĩ tự động, cảm xúc và hành vi kèm theo.\n\nBuổi 2 — Nhận diện lỗi tư duy: Tìm các niềm tin tuyệt đối, tư duy đen trắng và những quy tắc thiếu cân bằng.\n\nBuổi 3 — Thực hành kỹ thuật CBT: Diễn tập lại hồi ký suy nghĩ, thử nghiệm hành vi, luyện kỹ năng điều hòa thông qua hơi thở và grounding.\n\nBuổi 4 trở đi — Tăng cường độ khó: Mở rộng thử nghiệm sang các tình huống khó hơn và xây dựng phương án ứng phó cho thời gian dài.",
                'benefits' => [
                    'Hiểu mối liên hệ giữa tình huống, suy nghĩ, cảm xúc và hành vi của bạn.',
                    'Có kỹ năng cụ thể để ngắt chuỗi phản ứng cảm xúc không mong muốn.',
                    'Cải thiện khả năng nhận diện và diễn đạt cảm xúc trong các mối quan hệ.',
                    'Tăng sức chịu đựng với áp lực và khả năng đối mặt với tình huống khó.',
                    'Tự trị bằng kỹ năng tư duy từ bên trong, không phụ thuộc thuốc hay chất kích thích.',
                ],
                'symptoms' => [
                    'Phản ứng cảm xúc không tương xứng với hoàn cảnh',
                    'Cơn giận nổi lên đột ngột',
                    'Khó nhận diện và gọi tên cảm xúc',
                    'Lặp lại cùng một khuôn mẫu phản ứng',
                    'Lo lắng và giận dữ xuất hiện theo chu kỳ',
                ],
            ],
            [
                'key' => 'couple',
                'summary' => 'Không gian trung lập để hai người lắng nghe, xử lý xung đột và xây dựng lại kết nối.',
                'category' => 'couple',
                'name' => 'Hòa hợp Mối quan hệ',
                'duration' => 60,
                'price' => 950000,
                'image' => 'https://images.unsplash.com/photo-1529156069898-49953e39b3ac?auto=format&fit=crop&w=1200&q=85',
                'introduction' => "Mối quan hệ tình cảm không hỏng vì thiếu tình yêu, mà vì cách hai người giao tiếp với nhau. Một câu nói vô tâm, một cuộc gọi không được trả lời, một chuỗi hiểu lầm không được giải quyết — chúng tích tụ thành bức tường mà cả hai đều không biết bắt đầu từ đâu.\n\nGói Hòa hợp Mối quan hệ của Mind Guide dành cho các cặp đôi, vợ chồng, người yêu và các mối quan hệ thân thiết đang trong giai đoạn căng thẳng. Chuyên gia tạo không gian trung lập, an toàn để cả hai người cùng được lắng nghe, nhận diện điều thực sự đang đứng giữa mối quan hệ và xây dựng lại cách giao tiếp dựa trên sự tôn trọng và hiểu biết. Nếu mối quan hệ còn đủ nền tảng để cứu chữa, đây là con đường nhanh và bền vững nhất.",
                'audience' => "Các cặp đôi muốn cải thiện giao tiếp, xử lý xung đột, tổn thương niềm tin hoặc khác biệt kỳ vọng.\n\nDấu hiệu thường gặp:\n• Cãi nhau lặp đi lặp lại về cùng một chủ đề mà không tiến triển\n• Mất kết nối, mỗi người đều cảm thấy mình không được thấu hiểu\n• Nghi ngờ, kiểm soát hoặc mất niềm tin sau một sự việc trong quá khứ\n• Khác biệt lớn về kỳ vọng về tiền bạc, thời gian, công việc hoặc vai trò gia đình\n• Không còn cảm giác gần gũi, tình cảm lạnh dần\n• Cân nhắc đến chia tay nhưng chưa biết bắt đầu từ đâu",
                'process' => "Buổi 1 — Thiết lập nguyên tắc đối thoại: Xác định mục tiêu buổi làm việc, làm rõ nhu cầu của từng người và luật chơi an toàn.\n\nBuổi 2 — Nhận diện chu kỳ xung đột: Lần theo mô hình kích hoạt – phản ứng – tranh cãi – xa cách, làm rõ điều gì đang được nói và điều gì thực sự cần được nói.\n\nBuổi 3 — Thực hành kỹ năng: Luyện tập lắng nghe phản chiếu, đặt câu hỏi mở, diễn đạt nhu cầu mà không đổ lỗi.\n\nBuổi 4 trở đi — Xây dựng nền tảng: Thống nhất thỏa thuận về giao tiếp, phân chia vai trò, kế hoạch kết nối và duy trì tại nhà.",
                'benefits' => [
                    'Hiểu rõ gốc rễ của xung đột thay vì chỉ tranh luận về triệu chứng.',
                    'Có kỹ năng giao tiếp và tranh luận lành mạnh hơn trong mọi tình huống.',
                    'Khôi phục sự gần gũi, tin tưởng và tình cảm giữa hai người.',
                    'Giải quyết được những bế tắc kéo dài nhiều tháng bằng một mình.',
                    'Nâng cao năng lực ứng xử với các mối quan hệ khác ngoài đôi bạn.',
                ],
                'symptoms' => [
                    'Tranh cãi lặp đi lặp lại cùng một chủ đề',
                    'Mất kết nối tình cảm',
                    'Nghi ngờ và mất niềm tin',
                    'Khác biệt kỳ vọng không được giải quyết',
                    'Giao tiếp chủ yếu qua tranh cãi hoặc im lặng',
                ],
            ],
            [
                'key' => 'family',
                'summary' => 'Tiếp cận hệ thống để gia đình hiểu mối quan hệ và cùng nhau thay đổi bền vững.',
                'category' => 'couple',
                'name' => 'Tư vấn Gia đình',
                'duration' => 60,
                'price' => 900000,
                'image' => 'https://images.unsplash.com/photo-1511895426328-dc8714191300?auto=format&fit=crop&w=1200&q=85',
                'introduction' => "Gia đình không bao giờ chỉ là một người mẹ, một người bố hay một đứa trẻ — mà là cả một hệ thống quan hệ, nơi mỗi thành viên giữ vai trò riêng và ảnh hưởng lẫn nhau. Khi một người đau, cả gia đình đau theo.\n\nGói Tư vấn Gia đình của Mind Guide tiếp cận vấn đề từ góc nhìn hệ thống, tôn trọng động lực nội tại và nguồn lực sẵn có của từng gia đình. Chuyên gia giúp các thành viên hiểu rõ mối quan hệ đang vận hành như thế nào, chỉ ra những mẫu thức lặp lại đang gây khó khăn, và cùng gia đình thiết kế những thay đổi nhỏ nhưng bền vững mà mọi người cùng thực hiện được. Đây là không gian để gia đình gắn kết lại thay vì chia rẽ.",
                'audience' => "Gia đình gặp khó khăn trong giao tiếp, chuyển tiếp vai trò, nuôi dạy con hoặc thích nghi sau biến cố.\n\nDấu hiệu thường gặp:\n• Xung đột thường xuyên giữa vợ chồng, cha mẹ và con cái\n• Khó giao tiếp, mọi cuộc trò chuyện dẫn tới cãi vã hoặc im lặng\n• Con cái khó nghe lời, thụt lui hoặc hành vi phản kháng\n• Khó khăn trong việc nuôi dạy con vị thành\n• Gia đình đang thích nghi sau ly hôn, mất người thân hoặc biến động lớn\n• Người thân gần gũi nhất lại là người ít được lắng nghe nhất",
                'process' => "Buổi 1 — Khám phá hệ thống quan hệ: Lập bản đồ các vai trò, ranh giới, kỳ vọng và nguồn lực của từng thành viên trong gia đình.\n\nBuổi 2 — Nhận diện mẫu thức lặp lại: Tìm hiểu vòng lặp giao tiếp gây tổn thương và những niềm tin hạn chế sự gần gũi trong gia đình.\n\nBuổi 3 — Thiết kế thay đổi: Cùng các thành viên xác định những hành vi cụ thể, thực tế và đo lường được để thay đổi tại nhà.\n\nBuổi 4 trở đi — Củng cố và theo dõi: Ôn lại tiến triển, điều chỉnh kế hoạch, hỗ trợ các thành viên duy trì thay đổi.",
                'benefits' => [
                    'Nhìn thấy bức tranh toàn cảnh về gia đình thay vì chỉ nhìn vào lỗi của từng người.',
                    'Cải thiện giao tiếp và hiểu biết giữa các thành viên trong gia đình.',
                    'Có chiến lược nuôi dạy con cụ thể, phù hợp với độ tuổi và hoàn cảnh.',
                    'Giảm xung đột và xây dựng nền tảng cảm xúc an toàn cho các thế hệ trong gia đình.',
                    'Gắn kết gia đình bền chặt hơn sau những biến cố lớn.',
                ],
                'symptoms' => [
                    'Xung đột gia đình thường xuyên, lặp lại',
                    'Con cái không nghe lời hoặc hành vi phản kháng',
                    'Khó giao tiếp trong gia đình',
                    'Vai trò cha mẹ và con cái lộn xộn',
                    'Gia đình chưa thích nghi sau biến cố',
                ],
            ],
            [
                'key' => 'teen',
                'summary' => 'Đồng hành cùng học sinh, sinh viên trước áp lực học tập, bạn bè và định hướng tương lai.',
                'category' => 'teen',
                'name' => 'Hỗ trợ Tâm lý Học đường',
                'duration' => 45,
                'price' => 550000,
                'image' => 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?auto=format&fit=crop&w=1200&q=85',
                'introduction' => "Tuổi học đường là giai đoạn nhiều thay đổi nhất trong đời: cơ thể thay đổi, bạn bè thay đổi, kỳ vọng của gia đình thay đổi và bản thân bắt đầu đặt ra câu hỏi về mình. Nhiều học sinh, sinh viên không nói ra nỗi lo ấy — hoặc biểu hiện nó qua điểm số giảm sút, cơn giận bất thường, mất ngủ hoặc thu mình trong im lặng.\n\nGói Hỗ trợ Tâm lý Học đường của Mind Guide là nơi an toàn để học sinh và sinh viên được lắng nghe mà không bị phán xét. Chuyên gia làm việc với phương pháp phù hợp lứa tuổi, kết hợp với hỗ trợ phụ huynh một cách tôn trọng và có sự đồng ý của người trẻ. Mục tiêu là giúp các em tìm lại sự tự tin, khả năng quản lý cảm xúc và một hướng đi rõ ràng cho tương lai.",
                'audience' => "Học sinh, sinh viên và phụ huynh cần hỗ trợ về áp lực học tập, bạn bè, tự tin và định hướng tương lai.\n\nDấu hiệu thường gặp:\n• Giảm sút đột ngột kết quả học tập, buột xuống điểm\n• Lo lắng trước kỳ thi, mất ngủ hoặc mệt mỏi kéo dài\n• Rút lui khỏi bạn bè và các hoạt động nhóm\n• Tự trách, so sánh bản thân với bạn bè, mất tự tin\n• Bùng nổ cảm xúc hoặc thu mình trong im lặng\n• Không biết mình muốn học và làm nghề gì",
                'process' => "Buổi 1 — Tạo không gian an toàn: Lắng nghe góc nhìn của học sinh, tìm hiểu áp lực và các mối quan tâm không được nói ra.\n\nBuổi 2 — Làm việc với kỹ năng: Luyện tập quản lý cảm xúc, kỹ năng giải quyết vấn đề và kỹ năng giao tiếp bạn bè.\n\nBuổi 3 — Xây dựng lực đỡ: Phối hợp với phụ huynh khi phù hợp và có sự đồng thuận của người trẻ, hỗ trợ gia đình giao tiếp hiệu quả hơn.\n\nBuổi 4 trở đi — Định hướng và theo dõi: Cùng các em xác định mục tiêu học tập, nghề nghiệp và kế hoạch theo dõi tiến triển.",
                'benefits' => [
                    'Có nơi an toàn để chia sẻ những điều đang làm mình lo lắng mà không bị phán xét.',
                    'Nâng cao kỹ năng quản lý cảm xúc và áp lực học tập.',
                    'Cải thiện mối quan hệ với bạn bè và sự tự tin trong giao tiếp.',
                    'Xây dựng nền tảng tự tin để đối mặt với kỳ thi và các lựa chọn tương lai.',
                    'Cải thiện sự phối hợp giữa gia đình và nhà trường trong việc hỗ trợ người trẻ.',
                ],
                'symptoms' => [
                    'Giảm sút kết quả học tập đột ngột',
                    'Mất ngủ, mệt mỏi trước kỳ thi',
                    'Rút lui khỏi bạn bè và hoạt động nhóm',
                    'So sánh bản thân, mất tự tin',
                    'Bùng nổ cảm xúc hoặc thu mình',
                ],
            ],
            [
                'key' => 'child',
                'summary' => 'Hỗ trợ trẻ và thanh thiếu niên qua đánh giá phù hợp lứa tuổi và kế hoạch cho gia đình.',
                'category' => 'teen',
                'name' => 'Đồng hành Học sinh & Tuổi teen',
                'duration' => 45,
                'price' => 600000,
                'image' => 'https://images.unsplash.com/photo-1503454537195-1dcabb73ffb9?auto=format&fit=crop&w=1200&q=85',
                'introduction' => "Trẻ em không nói ra điều sai, chúng ta chỉ nhìn thấy hành vi. Một đứa trẻ đột nhiên khóc nhiều hơn, một đứa khác không còn thích đến trường, một đứa nữa nổi giận với mọi thứ — đó là tín hiệu, không phải lỗi.\n\nGói Đồng hành Học sinh & Tuổi teen của Mind Guide dành cho trẻ em và thanh thiếu niên đang trải qua những thay đổi về cảm xúc, hành vi, giấc ngủ hoặc học tập. Chuyên gia quan sát, đánh giá phù hợp với lứa tuổi và phối hợp chặt chẽ với người chăm sóc để xây dựng kế hoạch hỗ trợ dễ thực hiện trong đời sống hằng ngày — nơi trẻ được cảm thấy an toàn, được hiểu và được trao quyền.",
                'audience' => "Trẻ có thay đổi về cảm xúc, hành vi, giấc ngủ, học tập hoặc khó thích nghi với môi trường mới.\n\nDấu hiệu thường gặp:\n• Thay đổi khí tính bất thường: khóc nhiều, nổi giận hoặc buồn tủ thầm\n• Khó tách rời khỏi bố mẹ, sợ ngủ một mình, ám ảnh về nỗi sợ cụ thể\n• Khó tập trung, hay giật đầu, cắn móng tay hoặc đau bụng không rõ nguyên nhân\n• Không muốn đến trường, giảm kết quả học tập\n• Khó thích nghi sau ly hôn, chuyển trường hoặc sinh em\n• Rối loạn ăn uống, đặc biệt ở tuổi vị thành",
                'process' => "Buổi 1 — Trao đổi với người chăm sóc: Tìm hiểu lịch sử phát triển, môi trường sống và các dấu hiệu thay đổi gần đây.\n\nBuổi 2 — Quan sát và đánh giá: Đánh giá phù hợp với lứa tuổi qua trò chơi, hoạt động và bài tập phù hợp để trẻ thể hiện cảm xúc.\n\nBuổi 3 — Xác định yếu tố bảo vệ: Tìm những nguồn lực, mối quan hệ và điểm mạnh sẵn có của trẻ để dựa vào đó.\n\nBuổi 4 trở đi — Hướng dẫn cho gia đình: Đưa ra các kỹ thuật xử lý cụ thể, dễ áp dụng tại nhà và kế hoạch theo dõi tiến triển.",
                'benefits' => [
                    'Hiểu được ngôn ngữ hành vi của trẻ và ý nghĩa đằng sau hành động đó.',
                    'Có kế hoạch hỗ trợ cụ thể, dễ áp dụng trong sinh hoạt hằng ngày của gia đình.',
                    'Giảm các hành vi khó khăn như nổi giận, chống đối hay hành vi tự gây hại.',
                    'Cải thiện giấc ngủ, khả năng tập trung và thái độ với trường học.',
                    'Tăng cường sự an toàn và gắn bó trong mối quan hệ gia đình.',
                ],
                'symptoms' => [
                    'Thay đổi khí tính, khóc nhiều hoặc nổi giận',
                    'Hành vi lặp lại như cắn móng tay, giật đầu',
                    'Khó tách rời khỏi người chăm sóc',
                    'Sợ hãi, ám ảnh về một chủ đề cụ thể',
                    'Khó thích nghi sau thay đổi trong gia đình',
                ],
            ],
            [
                'key' => 'corporate',
                'summary' => 'Tìm lại nhịp sống bền vững giữa công việc, gia đình và nhu cầu chăm sóc bản thân.',
                'category' => 'corporate',
                'name' => 'Tìm điểm Cân bằng trong Cuộc sống',
                'duration' => 60,
                'price' => 1200000,
                'image' => 'https://images.unsplash.com/photo-1556761175-b413da4baf72?auto=format&fit=crop&w=1200&q=85',
                'introduction' => "Người trưởng thành trong thời đại số thường sống một đời đầy vai trò: vừa là người lao động, vừa là người chồng, người cha mẹ, người con trong gia đình vừa núi. Khi vai trò càng nhiều, ranh giới càng mờ — và cơ thể là thứ đầu tiên báo hiệu sự quá tải.\n\nGói Tìm điểm Cân bằng trong Cuộc sống của Mind Guide giúp bạn dừng lại, nhìn lại toàn bộ bức tranh và xây dựng một nhịp sống bền vững mà bạn có thể duy trì lâu dài. Chuyên gia làm việc cùng bạn về ranh giới cá nhân, nguồn gây quá tải, kỹ năng phục hồi năng lượng và các thói quen bền vững. Đây không phải buổi tư vấn để thêm một danh sách việc phải làm, mà là buổi tư vấn để bạn sống ít hối hận hơn.",
                'audience' => "Dành cho người đang mất cân bằng giữa công việc, học tập, gia đình và nhu cầu chăm sóc bản thân.\n\nDấu hiệu thường gặp:\n• Không có khoảng nghỉ trong ngày, cuối tuần cũng dành cho công việc\n• Mệt mỏi kéo dài dù đã ngủ đủ giấc, cần nhiều caffeine để tỉnh\n• Cảm giác mất kiểm soát với thời gian và danh sách việc\n• Dễ cáu kỹ, mất kiên nhẫn với người thân\n• Lo lắng thường trực về công việc kể cả khi nghỉ\n• Không còn thời gian cho bản thân hoặc cho những điều mình thật sự muốn",
                'process' => "Buổi 1 — Nhìn lại bức tranh toàn cảnh: Xác định các vai trò, nguồn tải và giá trị cốt lõi bạn đang hướng tới.\n\nBuổi 2 — Nhận diện nguồn gây quá tải: Phân tích các nguồn tiêu tốn năng lượng theo thời gian và xác định điểm nghẽn thực sự.\n\nBuổi 3 — Thiết kế ranh giới: Xây dựng ranh giới rõ ràng, cách nói «không» và cách thương lượng lịch trong công việc.\n\nBuổi 4 trở đi — Lộ trình bền vững: Thiết kế nhịp sinh hoạt và kế hoạch phục hồi năng lượng có thể duy trì trong bối cảnh thực tế.",
                'benefits' => [
                    'Xác định rõ nguồn gây quá tải thay vì cố gắng chịu đựng nhiều hơn.',
                    'Có ranh giới cá nhân cụ thể và cách thương lượng lịch trong công việc.',
                    'Cải thiện chất lượng giấc ngủ, năng lượng và sự tỉnh táo ban ngày.',
                    'Giảm xung đột với người thân nhờ giao tiếp rõ ràng và cảm thông hơn.',
                    'Tìm lại ý nghĩa và hướng đi dài hạn thay vì chỉ giải quyết triệu chứng.',
                ],
                'symptoms' => [
                    'Lịch làm việc không có khoảng trống',
                    'Mệt mỏi dù đã ngủ đủ',
                    'Cảm giác mất kiểm soát về thời gian',
                    'Dễ cáu kỳ, mất kiên nhẫn',
                    'Lo lắng thường trực kể cả khi nghỉ',
                ],
            ],
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
                    // description = tóm tắt ngắn cho thẻ dịch vụ và kết quả tìm kiếm.
                    'description' => $serviceData['summary'],
                    'introduction' => $serviceData['introduction'],
                    'image_url' => $serviceData['image'],
                    // target_audience = đối tượng phù hợp (không kèm danh sách dấu hiệu).
                    'target_audience' => strtok($serviceData['audience'], "\n\n"),
                    'session_process' => $serviceData['process'],
                    'benefits' => $serviceData['benefits'],
                    'symptoms' => $serviceData['symptoms'],
                    'duration_minutes' => $serviceData['duration'],
                    'buffer_minutes' => 10,
                    'price' => $serviceData['price'],
                    'status' => 'ACTIVE',
                    'display_order' => $index + 1,
                ]
            );
        }

        $models['sleep'] = Service::updateOrCreate(
            ['name' => 'Mất ngủ & Phục hồi giấc ngủ'],
            [
                'category_id' => $categoryModels['individual']->id,
                'description' => 'Hỗ trợ khôi phục giấc ngủ bằng CBT-I, vệ sinh giấc ngủ và điều hòa lo âu ban đêm.',
                'introduction' => "Mất ngủ thường không đến từ một nguyên nhân đơn lẻ mà là kết quả của nhiều yếu tố cộng lại: nhịp sinh hoạt lệch, lo âu kéo dài, thói quen trước khi ngủ, hoặc cơ thể đã quen với việc nằm tỉnh.\n\nGói Mất ngủ & Phục hồi giấc ngủ của Mind Guide kết hợp trị liệu nhận thức – hành vi (CBT-I) với các kỹ thuật vệ sinh giấc ngủ và điều hòa cảm xúc đêm. Chuyên gia cùng bạn phân tích chu kỳ thức – giấc, xử lý những lo lắng về đêm, thiết kế lại đồng hồ sinh học và xây dựng thói quen ngủ bền vững mà bạn có thể duy trì sau khi kết thúc gói.",
                'image_url' => 'https://images.unsplash.com/photo-1515894203077-9cd36032142f?auto=format&fit=crop&w=1200&q=85',
                'target_audience' => 'Người trưởng thành đang gặp khó khăn với giấc ngủ hoặc lịch sinh hoạt thất thường.',
                'session_process' => "Buổi 1 — Tìm hiểu nhịp ngủ: Khảo sát lịch sinh hoạt, thói quen trước khi ngủ và các yếu tố kích hoạt mất ngủ.\n\nBuổi 2 — Nhận diện lo âu và nhận thức sai lệch: Xử lý nỗi lo về giấc ngủ và các niềm tin cứng nhắc về việc phải ngủ đủ giờ.\n\nBuổi 3 — Thiết kế vệ sinh giấc ngủ: Xây dựng lịch ngủ – thức, hạn chế caffeine và ánh sáng màn hình, điều chỉnh môi trường ngủ.\n\nBuổi 4 trở đi — Củng cố và theo dõi: Đánh giá chất lượng giấc ngủ bằng nhật ký, điều chỉnh kế hoạch và xây dựng quy trình tự duy trì.",
                'benefits' => [
                    'Hiểu rõ cơ chế khiến bạn mất ngủ thay vì chỉ dùng thuốc ngủ.',
                    'Xây dựng lịch ngủ – thức ổn định, bền vững theo thói quen.',
                    'Giảm thời gian nằm tỉnh trên giường và cải thiện chất lượng giấc ngủ sâu.',
                    'Tăng tỉnh táo và khả năng tập trung trong ngày.',
                    'Giảm lo lắng về giấc ngủ — yếu tố gây mất ngủ thứ cấp.',
                ],
                'symptoms' => [
                    'Khó đi vào giấc ngủ',
                    'Thức giấc giữa đêm nhiều lần',
                    'Thức dậy quá sớm',
                    'Buồn ngủ ban ngày, mệt mỏi',
                    'Phụ thuộc thuốc ngủ',
                ],
                'duration_minutes' => 60,
                'buffer_minutes' => 10,
                'price' => 600000,
                'status' => 'ACTIVE',
                'display_order' => 9,
            ]
        );

        $activeNames = collect($services)->map(fn (array $service): string => $renamedNames[$service['name']] ?? $service['name'])->push('Mất ngủ & Phục hồi giấc ngủ')->all();
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
            ['key' => 'email_verification_required', 'value' => '1', 'type' => 'boolean', 'group' => 'authentication', 'label' => 'Bắt buộc xác thực email'],
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
