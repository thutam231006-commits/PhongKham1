<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\SpecialistProfile;
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
            $users[$email] = User::updateOrCreate(
                ['email' => $email],
                [
                    'name' => $data['name'],
                    'role' => $data['role'],
                    'phone' => $data['phone'],
                    'password' => Hash::make($data['password']),
                    'email_verified_at' => now(),
                ]
            );
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
            ['key' => 'individual_anxiety', 'category' => 'individual', 'name' => 'Tư vấn Lo âu & Cân bằng cảm xúc', 'duration' => 60, 'price' => 650000, 'image' => 'https://www.vinmec.com/static/uploads/chi_em_muon_biet_tre_kinh_uong_gi_cho_mau_ra_nen_gap_bac_si_1c54de7402.jpg', 'audience' => '
            <ul><li><span style="color: #000000;"><strong>Đã hỗ trợ tâm lý thành công cho hơn <span style="color: #ff0000;">3000 khách hàng</span> gặp các vấn đề trầm cảm, rối loạn lo âu</strong></span></li><li>Phương pháp <span style="color: #000000;"><strong>không sử dụng thuốc</strong></span>, an toàn, sức khỏe và tâm lý được hồi phục một cách tự nhiên <span style="color: #000000;">nhất.</span></li><li><span style="color: #000000;"><strong>Được bảo trợ chuyên môn</strong> bởi Viện Nghiên cứu Tâm lý và Phát triển Con người.</span></li><li style="text-align: justify;">Có thể áp dụng với phụ nữ đang có thai hoặc đang cho con bú, độ tuổi học sinh.</li><li>Phương pháp phù hợp với khách hàng từ 9 tuổi trở lên và tùy theo tình trạng. Các bạn trẻ dưới 18 tuổi cần có người thân đi cùng.</li></ul>
<p style="text-align: center;"><span style="color: #ff0000;"><strong>Không cần dùng thuốc – Không can thiệp cơ thể&nbsp;</strong></span></p>
<h2>Rối loạn lo âu – buồn chán là gì?</h2>
<p style="text-align: justify;">Dẫu biết, lo âu cũng là một trong những trạng thái bình thường của con người. Thế nhưng nếu nó xuất hiện một cách dày đặc và trở nên rối loạn thì sẽ vô cùng nguy hiểm.</p>
<p style="text-align: justify;"><a href="https://tamlynhc.vn/roi-loan-lo-au-570.html">Hội chứng rối loạn lo âu</a> là một vấn đề tâm lý khiến con người trở nên ám ảnh với các nỗi sợ hãi quá mức. Dù là các tình huống đơn giản, một khúc mắc vô lý cũng khiến người mắc chứng rối loạn lo âu rơi vào trạng thái căng thẳng quá độ.</p>
<p style="text-align: justify;">Những người mắc phải hội chứng này sẽ thường sống trong tâm trạng lo lắng thái quá, stress kéo dài mà không rõ nguyên nhân. Đa phần những người mắc phải đều là nữ giới.</p>
<p style="text-align: justify;">Rối loạn lo âu được chia thành 5 loại và con người có thể mắc một hoặc nhiều loại rối loạn lo âu cùng lúc, đó là:</p>
<ul style="text-align: justify;"><li><h3>Rối loạn lo âu lan tỏa</h3></li></ul>
<p style="text-align: justify;"><a href="https://tamlynhc.vn/roi-loan-lo-au-lan-toa-595.html">Rối loạn lo âu lan tỏa</a> (hay còn gọi là rối loạn lo âu toàn thể), sẽ khiến người mắc trở nên lo lắng với hầu hết các khía cạnh trong cuộc sống. Bất kỳ tác động nào đó xuất hiện cũng làm gia tăng sự căng thẳng, sợ hãi và kèm theo đó là triệu chứng suy nhược cơ thể, mất ngủ,…</p>
<ul style="text-align: justify;"><li><h3>Rối loạn lo âu hoảng loạn</h3></li></ul>
<p style="text-align: justify;">Rối loạn lo âu hoảng loạn sẽ khiến người mắc luôn sống trong tình trạng sợ hãi, hoảng loạn kinh hoàng. Những cảm xúc này có lúc thì âm ỉ nhưng đôi khi lại kéo đến một cách dữ dội, bất chợt mà không cần bất cứ tác động ngoại lực nào.</p>
<p style="text-align: justify;">Người mắc phải hội chứng này sẽ tự cô lập bản thân; không muốn tiếp xúc với mọi thứ xung quanh nhằm mục đích bảo vệ bản thân.</p>
<ul style="text-align: justify;"><li><h3>Rối loạn stress sau chấn thương</h3></li></ul>
<p style="text-align: justify;"><a href="https://tamlynhc.vn/roi-loan-cang-thang-sau-chan-thuong-tam-ly-2117.html">Rối loạn stress sau chấn thương</a> là tình trạng dễ gặp phải với những ai vừa trải qua một sự kiện kinh hoàng; hay một khoảng thời gian khó khăn nhưng sau đó không thể giải tỏa được tư duy tiêu cực và giải phóng cơ thể khỏi những ám ảnh đã qua.</p>
<ul style="text-align: justify;"><li><h3>Rối loạn ám ảnh cưỡng chế</h3></li></ul>
<p style="text-align: justify;">Chứng rối loạn ám ảnh cưỡng chế là chỉ những người luôn bị ám ảnh với một sự vật hay một sự việc nhất định như: sợ máu, sợ bẩn,… một cách quá độ.</p>
<ul style="text-align: justify;"><li><h3>Rối loạn lo âu xã hội</h3></li></ul>
<p style="text-align: justify;"><a href="https://tamlynhc.vn/roi-loan-lo-au-xa-hoi-2096.html">Chứng rối loạn lo âu xã hội</a> có biểu hiện như: sợ đám đông, sợ người lạ; luôn cảm thấy lo lắng và căng thẳng khi phải tiếp xúc với những người xa lạ hay những nơi đông người.</p>
<p style="text-align: justify;">Những người mắc hội chứng này sẽ luôn tìm mọi cách để tránh né dù là đến các sự kiện vui vẻ, thoải mái.</p>
<ul style="text-align: justify;"><li><h3>Rối loạn lo âu phân ly</h3></li></ul>
<p style="text-align: justify;">Hội chứng rối loạn lo âu phân ly thường gặp ở nhiều trẻ em đang trong các cột mốc phát triển quan trọng nhưng phải trải qua sự phân ly, chia cách từ gia đình, bố mẹ.</p>
<h2 style="text-align: justify;">Dấu hiệu nhận biết chứng rối loạn lo âu – buồn chán</h2>
<p style="text-align: justify;">Người mắc rối loạn lo âu cần được can thiệp sớm và nhận được tình yêu và sự quan tâm từ mọi người xung quanh. Để nhận biết chứng rối loạn lo âu, bạn có thể tham khảo các biểu hiện đặc trưng dưới đây:</p>
<ul style="text-align: justify;"><li><strong>Stress, lo lắng quá mức:</strong> Đây là triệu chứng mà 100% những người mắc rối loạn đều gặp phải. Những tâm lý này sẽ khiến họ bị dày vò liên tục đến mức kiệt quệ.</li><li><strong>Mất khả năng tập trung và đưa ra quyết định:</strong> Khi não bộ và hệ thần kinh phải làm việc một cách quá tải sẽ khiến khả năng tư duy, ghi nhớ của con người trở nên sa sút trầm trọng.</li><li><strong>Khó giữ bình tĩnh và kiểm soát hành vi:</strong> Đây được xem là dấu hiệu rõ rệt nhất của chứng rối loạn lo âu. Khi người mắc rối loạn trở nên stress, lo âu quá mức sẽ rất khó giữ bình tĩnh và dễ bị kích động. Những lúc như vậy họ thường đi lại liên tục, nói nhiều hơn nhưng não bộ lại không thể suy nghĩ bất kỳ điều gì.</li><li><strong>Rơi vào trạng thái sợ hãi vô cớ:</strong> Người mắc rối loạn như bị ám ảnh bởi một điều gì đó nhưng lại không rõ nguyên do. Việc sống trong nỗi sợ hãi lâu sẽ khiến tâm lý bị tổn thương cực kỳ nghiêm trọng. Chúng ta có thể nhận thấy dấu hiệu này nếu họ rơi vào một tình huống cụ thể.</li><li><strong>Thay đổi về sức khỏe:</strong> tim đập nhanh, hít thở không sâu, thở gấp chân tay có hiện tượng run rẩy, tê buốt; ra mồ hôi trộm; rối loạn tiêu hóa; rối loạn giấc ngủ; suy nhược cơ thể.</li></ul>
<h2 style="text-align: justify;">Những nguy hiểm từ chứng rối loạn lo âu</h2>
<p style="text-align: justify;">Theo nghiên cứu từ các vụ tự sát có thể thấy, 18% các cuộc tự vẫn đều có liên quan đến chứng rối loạn lo âu. Còn xét theo tổng số người mắc phải hội chứng này thì có tới 30% số người đều đã nhiều lần nghĩ tới việc tự sát để giải thoát.</p>
<p style="text-align: justify;">Có nhiều người còn chưa hiểu hết về dấu hiệu của rối loạn này nên chỉ xem đó là một lại tính cách dị biệt; một số người thì tin rằng, những biểu hiện đó sẽ sớm chấm dứt vào tương lai nên đã để lỡ cơ hội vàng để thoát khỏi rối loạn lo âu.</p>
<p style="text-align: justify;">Một số những tác hại mà hội chứng rối loạn lo âu có thể gây ra cho con người như:</p>
<ul><li style="text-align: justify;"><strong>Biến chứng thành các vấn đề sức khỏe nguy hiểm:</strong> suy nhược cơ thể, trầm cảm, các vấn đề về tim mạch, huyết áp, rối loạn tiêu hóa; suy giảm khả năng tình dục; mất dần khả năng tư duy, suy nghĩ.</li><li style="text-align: justify;"><strong>Ảnh hưởng tới bản thân người mắc rối loạn:</strong> Có thể xuất hiện mong muốn tự sát và tự hành hạ cơ thể.</li><li style="text-align: justify;"><strong>Ảnh hưởng đến công việc, mối quan hệ và xã hội:</strong> Người mắc hội chứng này thường rất khó chia sẻ, tự cô lập và luôn cáu gắt, đổ lỗi cho người khác dẫn tới các mối quan hệ trở nên xấu hơn rất nhiều.</li></ul>
<h2 style="text-align: justify;">Nguyên nhân gây ra vấn đề rối loạn lo âu – buồn chán</h2>
<p style="text-align: justify;">Nguyên nhân gây ra hội chứng rối loạn lo âu hiện vẫn chưa được làm rõ nhưng đa phần những người mắc phải rối loạn này đều có đặc điểm chung như:</p>
<ul><li style="text-align: justify;"><strong>Stress quá độ và sang chấn tâm lý:</strong> Đây là nguyên nhân lớn nhất mà đa số những người mắc rối loạn đều gặp phải.</li><li style="text-align: justify;"><strong>Sử dụng các chất kích kích – chất cấm:</strong> cafe, rượu, bia, ma túy,… nếu bị làm dụng trong một thời gian dài sẽ còn gây ra nhiều hậu quả nặng nề hơn cả chứng rối loạn lo âu.</li><li style="text-align: justify;"><strong>Thay đổi hormone hạnh phúc</strong>: Hormone là một chất dẫn truyền thân kinh và nếu bị thiếu hụt sẽ gây ra tình trạng rối loạn tâm lý và cảm xúc.</li><li style="text-align: justify;"><strong>Do yếu tố di truyền:</strong> Đã có nhiều công trình nghiên cứu cho thấy, những người có bố mẹ từng mắc phải hội chứng rối loạn lo âu sẽ có khả năng mắc rối loạn cao gấp 6 lần so với người bình thường.</li><li style="text-align: justify;"><strong>Yếu tố tác động từ các bệnh lý liên quan</strong>: HIV; bệnh về tim mạch; tiểu đường; bệnh về đường ruột,… là những bệnh lý có thể khiến con người rơi vào trạng thái lo lắng, căng thẳng kéo dài.</li></ul>
<blockquote><p style="text-align: center;"><span style="color: #008000; font-size: 14pt;"><strong>Nguyên nhân lớn nhất dẫn tới chứng rối loạn lo âu hiện nay là Stress – tâm lý bất ổn</strong></span></p></blockquote>
<h2>Giải pháp xử lý rối loạn lo âu hiệu quả tại Tâm lý NHC</h2>
<p style="text-align: justify;">Giải pháp xử lý rối loạn lo âu tại Tâm lý NHC được nhiều chuyên gia đầu ngành đánh giá cao về hiệu quả. Thực tế, Tâm lý NHC Việt Nam đã giúp hơn 3000 khách hàng thoát khỏi rối loạn lo âu, trầm cảm kéo dài nhiều năm, hồi phục sức khỏe một cách tự nhiên.</p>
<p style="text-align: justify;">Với phương pháp đồng hành này, người mắc rối loạn lo âu có thể được can thiệp từ vùng sâu của tâm trí, giải quyết tận gốc các nguyên nhân gây nên rối loạn lo âu. Từ đó, người mắc rối loạn có thể tự duy trì, cân bằng cảm xúc của chính mình để tránh tái phát về sau.</p>
<p style="text-align: justify;">Giải pháp này chính là một cứu tinh của những ai đã sử dụng thuốc nhiều năm nhưng không dứt điểm, hay tái phát.</p>
<p style="text-align: justify;">Chuyên gia tâm lý sẽ giúp bạn biết cách điều hòa lại cảm xúc, hiểu rõ nguồn gốc của rối loạn lo âu và biết cách chiến thắng nó. Các mục đích chính của việc gỡ rối tâm lý như:</p>
<ul><li>Hiểu nguyên nhân gây ra vấn đề rối loạn lo âu ở bản thân.</li><li>Chữa lành các tổn thương tâm lý trong quá khứ đang ảnh hưởng đến vấn đề lo âu, sợ hãi hiện tại.</li><li>Biết cách quan sát, bắt thóp những suy nghĩ lo âu, sợ hãi để điều chỉnh.</li><li>Tự tin trong cuộc sống và yêu thương bản thân nhiều hơn.</li><li>Có kỹ năng để đối mặt, ứng phó, giải quyết xung đột với các tình huống gây lo âu, sợ hãi hiện tại.</li><li style="text-align: justify;">Học được kỹ năng ứng phó với những khó khăn trong cuộc sống và học cách mạnh mẽ, toàn vẹn và an toàn trong tương lai.</li><li>Kết nối và hòa hợp hơn với người thân, xây dựng mối quan hệ tốt đẹp hơn với những người xung quanh.</li><li>Thay đổi những tư duy, niềm tin không phù hợp, không tích cực thành tư duy, niềm tin tích cực và tương hỗ.</li></ul>
<h2 style="text-align: justify;">Lộ trình gỡ rối tâm lý, vượt qua rối loạn lo âu của Trung tâm NHC Việt Nam</h2>
<p style="text-align: justify;">Trung tâm Tâm lý và Phát triển Con người NHC Việt Nam là một trong những đơn vị tiên phong trong lĩnh vực tâm lý tại Việt Nam với một quy mô lớn, chuyên nghiệp. Quá trình phục hồi sức khỏe tâm lý tại Trung tâm được xây dựng dựa trên nhiều nghiên cứu khoa học đã được kiểm chứng và được các chuyên gia tối ưu để tăng cao hiệu quả, rút ngắn thời gian đồng hành.</p>
<p>Mỗi lộ trình đồng hành cho từng trường hợp cụ thể sẽ được các chuyên gia của Tâm lý NHC thiết kế sao cho phù hợp nhất với nguyên nhân, tình trạng, mức độ riêng của từng khách hàng.</p>
<p><span style="color: #000000;">Dưới đây là một quy trình xử lý rối loạn lo âu điển hình tại NHC Việt Nam:</span></p>
<ul><li><span style="color: #000000;"><strong>Giai đoạn 1 – GIẢI TỎA</strong>: Giúp khách hàng nhìn rõ vấn đề tâm lý và “giải tỏa” – tức là xử lý vấn đề ở bề mặt, có tính chất tức thời và ngắn hạn. Mục tiêu là ổn định cảm xúc hiện tại.</span></li><li><span style="color: #000000;"><strong>Giai đoạn 2 – CÂN BẰNG</strong>: Giúp khách hàng khám phá nguyên nhân gốc rễ gây nên vấn đề tâm lý và tìm ra giải pháp để giải quyết vấn đề lớn hiện tại đang gặp. Mục tiêu là giúp khách hàng chủ động tìm lại được bình an, biết cân bằng cảm xúc.</span></li><li><span style="color: #000000;"><strong>Giai đoạn 3 – TÍCH CỰC</strong>: Giúp khách hàng thấu hiểu và biết yêu, thương mình hơn và tìm lại được niềm tin.</span></li><li><span style="color: #000000;"><strong>Giai đoạn 4 – HUẤN LUYỆN</strong>: Cài đặt, bổ sung và phát triển hơn những tư duy, niềm tin tích cực và tương hỗ, để trở nên: Hiểu, yêu, thương mình; Hiểu và biết yêu, thương người khác đúng cách.</span></li><li><span style="color: #000000;"><strong>Giai đoạn 5 – HUẤN LUYỆN CHUYÊN SÂU</strong>: Giúp khách hàng sống với mục tiêu, ước mơ, nhiệt huyết, khát khao và năng lực được đánh thức.</span></li></ul>
<p><span style="color: #000000;">Trên đây là chương trình chuyên sâu để xử lý rối loạn lo âu từ gốc rễ, nâng cấp tư duy, huấn luyện khách hàng trở thành chuyên gia của chính mình.</span></p>
<p><span style="color: #000000;">Tùy vào vấn đề tâm lý là nặng hay nhẹ, mỗi khách hàng sẽ được thiết kế một lộ trình riêng biệt (với phương pháp và quy trình cụ thể trong từng giai đoạn).</span></p>
<p><span style="color: #000000;">Đối với các trường hợp nhiều vấn đề thì sau khoảng 3 tuần mới có thể lên kế hoạch xử lý phù hợp.</span></p>
<p style="text-align: center;"><span style="font-size: 14pt; color: #008000;"><b>Liên hệ đặt lịch tham vấn và gỡ rối tâm lý cùng Chuyên gia: </b><span style="color: #ff0000; font-size: 18pt;"><b>096 589 8008</b></span></span></p>
<h2 style="text-align: justify;">Lợi ích của khách hàng khi đồng hành cùng Tâm lý NHC</h2>
<p style="text-align: justify;">Tâm lý NHC luôn chia sẻ chân thật và cởi mở với khách hàng, để họ biết rằng có thể kỳ vọng những gì từ chúng tôi và chúng tôi chắc chắn cố gắng trong mọi hành động, lời nói để có được sự hài lòng cao nhất từ khách hàng.</p>
<p style="text-align: justify;">Sau hơn 3.000 khách hàng được hỗ trợ tâm lý thành công, Tâm lý NHC mong muốn đồng hành cùng khách hàng trên hành trình hiểu mình, gỡ rối tâm lý và xây dựng nền tảng tinh thần vững vàng hơn:</p>
<ul><li><strong>Hiểu rõ hơn về chính mình</strong> và những vấn đề mà mình gặp phải.</li><li>Học được cách <strong>xử lý</strong>&nbsp;các tình huống tương tự để tránh tái phát.</li><li>Biết cách <strong>ổn định cảm xúc, tâm lý và tinh thần.</strong></li><li>Học cách <strong>kết nối với các mối quan hệ,</strong> giao tiếp với chính mình và mọi người.</li></ul>
<p>Trong quá trình đó, Tâm lý NHC luôn:</p>
<ul><li><strong>Bảo mật</strong>&nbsp;mọi thông tin khách hàng cung cấp.</li><li><strong>Không sử dụng thuốc</strong> hay can thiệp bất kỳ phương pháp nào lên cơ thể của khách hàng.</li><li>Luôn luôn <strong>đồng hành cùng khách hàng</strong> trong và sau thời gian đồng hành.</li></ul>
<blockquote><p style="text-align: center;"><span style="color: #008000; font-size: 14pt;"><strong>An toàn – Tự nhiên – Hiệu quả rõ ràng</strong></span></p></blockquote>
<h2>Câu chuyện khách hàng</h2>
<p><strong>Anh <a href="https://tamlynhc.vn/chua-roi-loan-lo-au-4-nam-khong-khoi-anh-huy-da-khoe-manh-nho-tri-lieu-tam-ly-18162.html">Lai Xuân Huy</a> (41 tuổi, Hồ Chí Minh)</strong>: <em>“Tôi đến với NHC Việt Nam trong tình trạng bị rối loạn lo âu và bị chứng nôn ói vào buổi sáng không rõ nguyên nhân. Các vấn đề sức khỏe của tôi đã kéo dài 4 năm. Tôi đã đi rất nhiều bệnh viện, gặp rất nhiều bác sĩ để chữa nhưng không có dấu hiệu thuyên giảm, cơ thể ngày một suy nhược hơn. Khi mới bắt đầu chương trình gỡ rối tâm lý, thực sự tôi không tin tưởng lắm vào phương pháp không dùng thuốc nhưng sau một tháng, tôi đã ăn ngon, ngủ ngon hơn, tinh thần phấn chấn hơn, chứng nôn ói cũng giảm nhiều. Sau 5 tháng, cơ thể tôi đã hoàn toàn khỏe mạnh, tôi có thể quay trở lại tiếp tục công việc kinh doanh khách sạn của gia đình. Chuyên gia cũng giúp vợ chồng tôi thấu hiểu và hòa hợp với nhau, sống hạnh phúc vui vẻ hơn. Tôi xin chân thành cảm ơn Trung tâm!”.</em></p>
<blockquote><p style="text-align: center;"><span style="font-size: 14pt; color: #008000;"><strong>Sức khỏe và hạnh phúc của khách hàng là sứ mệnh sống của NHC Việt Nam</strong></span></p></blockquote>
<h2 dir="ltr">Lý do khách hàng tin tưởng dịch vụ của NHC Việt Nam</h2>
<p>✅ Được bảo trợ chuyên môn bởi <strong>Viện Nghiên cứu Tâm lý và Phát triển Con người</strong>.</p>
<p><strong>✅ </strong>Đơn vị <strong>uy tín </strong>tại Việt Nam về chăm sóc sức khỏe tinh thần, tâm lý.</p>
<p>✅ Đã hỗ trợ thành công hơn <strong>3000 khách hàng</strong> gặp vấn đề trầm cảm, rối loạn lo âu…</p>
<p>✅ Được <strong>cấp phép</strong> hoạt động.</p>
<p>✅ Được nhiều người Việt ở nước ngoài (Mỹ, Bỉ, Đức, Hàn, Nhật…) <strong>tin tưởng</strong> và <strong>đồng hành</strong></p>
<p><strong>✅ An toàn</strong> và phù hợp với mọi đối tượng khách hàng, kể cả phụ nữ mang thai, sau sinh hoặc đang cho con bú.</p>
<p><strong>✅ Chính sách đồng hành</strong> cùng khách hàng và người thân rõ ràng.</p>
<p><strong>✅ Chính sách hoàn tiền</strong>&nbsp;nếu khách hàng không hài lòng</p>
<p><span style="color: #000000;"><strong>Nếu bản thân hay những người thân của bạn đang mắc chứng rối loạn lo âu, hãy liên hệ ngay Trung tâm để được hỗ trợ kịp thời, tìm lại sức khỏe và niềm vui sống!</strong></span></p>
<h2><span style="color: #003366;"><strong>TRUNG TÂM TÂM LÝ VÀ PHÁT TRIỂN CON NGƯỜI NHC VIỆT NAM</strong></span></h2>
<p><span style="color: #008000;"><em><b>Hỗ trợ tâm lý không dùng thuốc. Sức khỏe hồi phục tự nhiên, an toàn.</b></em></span></p>
<p><span style="color: #003366;"><b>Uy tín – Tận tâm – Trách nhiệm – Chuyên nghiệp</b></span></p>
<ul><li><strong>Cơ sở 1:</strong> Số 235 Quan Hoa, Phường Nghĩa Đô, Hà Nội</li><li><strong>Cơ sở 2: </strong>Số 37 Thâm Tâm, Phường Yên Hoà, Hà Nội</li><li><strong>Cơ sở 3:</strong> Số 18 Phan Chu Trinh nối dài, Phường Bình Lợi Trung, TP Hồ Chí Minh</li><li><strong>Cơ sở 4:</strong> Số 107 Hoàng Hoa Thám, Phường Gia Định, TP Hồ Chí Minh</li><li><strong>Hotline:</strong> 096 589 8008</li><li><strong>Website:</strong> <a href="http://tamlynhc.vn/">tamlynhc.vn</a></li><li><strong>Email:</strong> <a href="mailto:tamlyphattriennhc@gmail.com" target="_blank" rel="noopener">tamlyphattriennhc@gmail.com</a></li></ul>
<blockquote><h3><strong>Có thể bạn quan tâm:</strong></h3>
<p><a href="https://trilieutamly.com/bao-luc-hoc-duong-gay-am-anh-tam-tri-va-hau-qua-gi-khi-truong-thanh-33141.html">Rối loạn lo âu từ bạo lực học đường, bạn trẻ 2K2 vượt qua bằng cách nào?</a></p>
<p><a href="https://trilieutamly.com/roi-loan-am-anh-cuong-che-tu-nam-lop-9-chi-nhin-cap-toc-cung-khien-t-a-lo-lang-33368.html">Hành trình vượt qua rối loạn ám ảnh cưỡng chế từ năm lớp 9, chỉ nhìn cặp tóc cũng khiến T.A lo lắng</a></p>
<p><a href="https://tamlynhc.vn/tu-co-gai-tu-ti-song-khep-minh-den-hanh-trinh-xay-dung-noi-luc-va-lam-chu-cuoc-song-41392.html">Từ cô gái tự ti, sống khép mình đến hành trình xây dựng nội lực và làm chủ cuộc sống</a></p>
<p><a href="https://trilieutamly.com/hoang-so-vo-ly-va-mat-ngu-33065.html">“Ban ngày hoảng sợ, ban đêm mất ngủ triền miên”, cô gái 27 tuổi đã thoát khỏi vòng luẩn quẩn và xin được việc như ý sau 5 tháng hỗ trợ tâm lý</a></p></blockquote>
            ', 'process' => 'Lắng nghe bối cảnh; nhận diện vòng lặp suy nghĩ và cảm xúc; thống nhất bài tập ứng phó thực tế giữa các buổi.'],
            
            ['key' => 'individual_depression', 'category' => 'individual', 'name' => 'Đồng hành cùng Trầm cảm & Kiệt sức', 'duration' => 60, 'price' => 700000, 'image' => 'https://images.unsplash.com/photo-1544027993-37dbfe43562a?auto=format&fit=crop&w=1200&q=85', 'audience' => '
            <p><span style="font-size: 20pt; color: #65ad45;"><b>Xử lý trầm cảm không dùng thuốc – Giải pháp vàng cho vấn đề tâm lý dai dẳng</b></span></p>
<ul><li><span style="color: #000000;"><strong>2 KHÔNG</strong>: <strong>Không cần dùng thuốc</strong> – Không can thiệp cơ thể.</span></li><li><span style="color: #000000;">Phù hợp với cả những ai <strong>dùng thuốc nhiều năm không khỏi, hễ gặp biến cố là trầm cảm quay lại.</strong></span></li><li><span style="color: #000000;"><strong>Hiệu quả đồng hành 1-1 và chính sách hoàn chi phí được thể hiện rõ ràng trên giấy tờ.</strong></span></li><li><span style="color: #000000;">Giải quyết tận gốc nguyên nhân gây trầm cảm, rối loạn lo âu, hạn chế tái phát.</span></li><li><span style="color: #000000;">Phương pháp hỗ trợ tâm lý được bảo trợ chuyên môn bởi Viện Nghiên cứu Tâm lý và Phát triển Con người.</span></li><li><span style="color: #000000;">An toàn cho TRẺ vị thành niên và PHỤ NỮ đang mang thai hoặc đang cho con bú.</span></li><li><span style="color: #000000;">Phương pháp <strong>phù hợp với</strong> khách hàng từ 9 tuổi trở lên và tùy theo tình trạng. Các bạn trẻ dưới 18 tuổi cần có người thân đi cùng. </span></li></ul>
<h2>1. Trầm cảm là gì?</h2>
<p><span style="font-weight: 400; color: #000000;">Theo Tổ chức Y tế Thế giới (WHO),  <a href="https://trilieutamly.com/du-hoc-sinh-tram-cam-xn-tim-lai-ban-than-32966.html">trầm cảm</a> là một rối loạn sức khỏe tâm thần phổ biến, biểu hiện bằng tâm trạng buồn bã kéo dài, mất hứng thú hoặc mất niềm vui với những hoạt động từng yêu thích.</span></p>
<p><span></span></p>
<h2>2. Dấu hiệu và các giai đoạn của trầm cảm</h2>
<p><b></b><span style="color: #000000;"><b>Giai đoạn 1:</b></span></p>
<ul><li style="font-weight: 400;"><span style="color: #000000;">Thường xuyên cảm thấy buồn chán không rõ lý do.</span></li><li style="font-weight: 400;"><span style="color: #000000;">Bản thân không muốn làm gì.</span></li><li style="font-weight: 400;"><span style="color: #000000;">Cảm thấy cạn kiệt hết năng lượng.</span></li><li style="font-weight: 400;"><span style="color: #000000;">Bỏ hết những đam mê, sở thích.</span></li><li style="font-weight: 400;"><span style="color: #000000;">Bản thân không tự nhận thức được là mình có các triệu chứng của trầm cảm.</span></li><li style="font-weight: 400;"><span style="color: #000000;">Dần tách biệt với gia đình, bạn bè, đồng nghiệp, xã hội và thế giới xung quanh.</span></li><li style="font-weight: 400;"><span style="color: #000000;">Muốn xa lánh mọi thứ và thích ở một mình.</span></li><li style="font-weight: 400;"><span style="color: #000000;">Thay đổi thói quen ăn uống, ngủ nghỉ: Ăn nhiều hơn hoặc ít hơn, không cảm thấy ngon miệng; ngủ không ngon giấc, khó ngủ, mất ngủ hoặc ngủ li bì nhưng ngủ dậy vẫn mệt.</span></li></ul>
<p><span style="color: #000000;">=&gt; Đây là giai đoạn vàng để xử lý trầm cảm nhưng đa phần mọi người không nhận ra vấn đề tâm lý của mình ở giai đoạn này. </span></p>
<p><span style="color: #000000;"><b>Giai đoạn 2:</b><span style="font-weight: 400;"> </span></span></p>
<ul><li style="font-weight: 400;"><span style="color: #000000;">Cảm thấy sợ hãi, uể oải, thiếu sức sống.</span></li><li style="font-weight: 400;"><span style="color: #000000;">Muốn buông xuôi mọi thứ.</span></li><li style="font-weight: 400;"><span style="color: #000000;">Không muốn suy nghĩ hay làm việc.</span></li><li style="font-weight: 400;"><span style="color: #000000;">Có nhiều nỗi sợ hãi, ám ảnh: sợ người lạ, sợ đám đông, đôi khi sợ cả những người thân thiết, sợ bóng đêm, sợ ánh sáng…</span></li><li style="font-weight: 400;"><span style="color: #000000;">Tâm trạng thất thường, cau có, hay cáu giận vô cớ.</span></li><li style="font-weight: 400;"><span style="color: #000000;">Khó ngủ, mất ngủ, ngủ không sâu giấc.</span></li><li style="font-weight: 400;"><span style="color: #000000;">Cảm thấy không ai hiểu và không có ai giúp được mình, không tin tưởng ai.</span></li><li style="font-weight: 400;"><span style="color: #000000;">Mất niềm tin với người thân, bạn bè, đồng nghiệp, xã hội xung quanh.</span></li><li style="font-weight: 400;"><span style="color: #000000;">Không dám đối mặt với hiện tại.</span></li><li style="font-weight: 400;"><span style="color: #000000;">Xuất hiện ảo tưởng hoặc bắt đầu có những suy nghĩ muốn tự tử.</span></li></ul>
<p><span style="color: #000000;"><b>Giai đoạn 3: </b></span></p>
<ul><li><span style="font-weight: 400; color: #000000;">Tuyệt vọng, mất hết niềm tin vào bản thân, con người, cuộc sống, xã hội.<br/></span></li><li><span style="font-weight: 400; color: #000000;">Có xu hướng làm hại bản thân.<br/></span></li><li><span style="font-weight: 400; color: #000000;">Xuất hiện hoang tưởng, cảm thấy không có lối thoát.<br/></span></li><li><span style="font-weight: 400; color: #000000;">Tiêu cực, cảm thấy vô dụng, mặc cảm, tội lỗi.<br/></span></li><li><span style="font-weight: 400; color: #000000;">Thường xuyên nghĩ đến cái chết, khoảng 5-7 lần/ tuần trở lên.<br/></span></li><li><span style="font-weight: 400; color: #000000;">Ngủ li bì hoặc khó ngủ hơn bình thường, mất ngủ kéo dài.</span></li></ul>
<blockquote><p><strong><span style="color: #008000;">Trầm cảm có thể xuất phát từ những sang chấn tâm lý hoặc do stress – lo âu – dồn nén tâm lý trong thời gian dài.</span></strong></p>
</blockquote><h2>3. Trầm cảm có nguy hiểm không?</h2>
<h3>3.1. Trầm cảm – Kẻ sát nhân thầm lặng</h3>
<p><span style="font-weight: 400; color: #000000;">Chắc hẳn ai trong số chúng ta cũng đã từng nghe về các vụ tự tử, các vụ án thương tâm có liên quan đến trầm cảm như: mẹ giết con ruột; thím giết cháu; sát hại người cao tuổi,…</span></p>
<p><span style="font-weight: 400; color: #000000;">Người mắc trầm cảm sẽ tự sản sinh ra những tư duy tiêu cực dẫn tới nhiều hành vi làm tổn thương cơ thể và gây nguy hiểm đến những người xung quanh. </span></p>
<p><span style="font-weight: 400; color: #000000;">Vì vậy, việc cảnh giác với trầm cảm là điều nên làm của tất cả mọi người. </span></p>
<p><span style="font-weight: 400; color: #000000;">Nếu bạn hay người thân đang gặp phải một trong số các tình trạng trên, hãy liên hệ ngay Chuyên gia tâm lý để được hỗ trợ kịp thời.</span></p>
<h3>3.2. Mức độ nguy hiểm của trầm cảm</h3>
<p><span style="font-weight: 400; color: #000000;">Tổ chức Y tế Thế giới WHO (1) ghi nhận:</span></p>
<ul><li><span style="font-weight: 400; color: #000000;">Ước tính 4% dân số mắc chứng trầm cảm, bao gồm 5,7% người trưởng thành (4,6% nam giới và 6,9% nữ giới) và 5,9% người trưởng thành từ 70 tuổi trở lên.</span></li><li style="font-weight: 400;"><span style="font-weight: 400; color: #000000;">Khoảng 332 triệu người đang phải chịu nhiều ảnh hưởng của trầm cảm.</span></li><li><span style="font-weight: 400; color: #000000;">Trầm cảm phổ biến ở phụ nữ gấp khoảng 1,5 lần so với nam giới.</span></li><li><span style="font-weight: 400; color: #000000;">Trên toàn thế giới, hơn 10% phụ nữ mang thai và phụ nữ vừa sinh con mắc chứng trầm cảm.</span></li><li style="font-weight: 400;"><span style="color: #000000;">Năm 2021, ước tính có hơn 727.000 người chết vì tự tử mỗi năm.</span></li><li style="font-weight: 400;"><span style="color: #000000;">Tự tử là nguyên nhân thứ ba gây tử vong ở thanh thiếu niên 15-29 tuổi.</span></li></ul>
<p><span style="font-weight: 400; color: #000000;">Riêng tại Việt Nam, <strong>số người tự tử hàng năm vì trầm cảm ước tính khoảng 36.000 – 40.000 người</strong> (cao gấp 4 lần so với tử vong bởi tai nạn giao thông). </span></p>
<p><span style="font-weight: 400; color: #000000;">Đây đều là những con số đáng báo động mà chúng ta không được xem nhẹ.</span></p>
<h3>3.3. Trầm cảm ảnh hưởng nặng nề đến sức khỏe và cuộc sống của người trầm cảm</h3>
<h4>3.3.1. Xét thể mặt sức khỏe thể chất:</h4>
<p><span style="font-weight: 400; color: #000000;">Ban đầu, người mắc trầm cảm không còn muốn chăm sóc cho bản thân, kể cả về vẻ bề ngoài đến sinh hoạt tối thiểu cho thân thể. </span></p>
<p><span style="font-weight: 400; color: #000000;">Nếu kéo dài, hệ miễn dịch tinh thần và thể chất sẽ không thể đủ sức chống lại các tác nhân gây hại và biến chứng thành nhiều vấn đề sức khỏe nguy hiểm như:</span></p>
<ul><li style="font-weight: 400;"><span style="font-weight: 400; color: #000000;">Đau đầu, mất ngủ kéo dài.</span></li><li style="font-weight: 400;"><span style="font-weight: 400; color: #000000;">Suy giảm ham muốn tình dục.</span></li><li style="font-weight: 400;"><span style="font-weight: 400; color: #000000;">Tim, ung thư, tiểu đường, dạ dày…</span></li></ul>
<p><span style="color: #000000;">Một số người có những biểu hiện về thể chất (đau đầu, buồn nôn, khó thở,…) nhưng khi đi gặp bác sĩ thì lại không tìm ra vấn đề là gì.</span></p>
<h4>3.3.2. <strong>Xét về mặt tâm lý, tư duy:</strong></h4>
<ul><li style="font-weight: 400;"><span style="font-weight: 400; color: #000000;">Mất dần khả năng tập trung và lối tư duy tích cực, giảm hiệu quả học tập và làm việc.</span></li><li style="font-weight: 400;"><span style="font-weight: 400; color: #000000;">Mất trí tuệ và phát triển vấn đề Alzheimer ở giai đoạn sớm.</span></li><li style="font-weight: 400;"><span style="font-weight: 400; color: #000000;">Lạm dụng các chất kích thích, gây nghiện để tìm sự thoải mái dù là ngắn hạn.</span></li><li style="font-weight: 400;"><span style="font-weight: 400; color: #000000;">Thu hẹp cuộc sống, mối quan hệ xã hội, trở nên cô lập một mình.</span></li><li style="font-weight: 400;"><span style="font-weight: 400; color: #000000;">Tự làm hại chính bản thân mình bằng cách tự tử hoặc luôn nghĩ tới sự chết chóc, bạo lực.</span></li><li><span style="color: #000000;">Có thể làm hại đến những người xung quanh.</span></li></ul>
<h2>4. Đối tượng dễ rơi vào tình trạng trầm cảm</h2>
<ul><li style="font-weight: 400; text-align: justify;"><span style="font-weight: 400; color: #000000;">Người vừa trải qua một tổn thương thực tổn: chấn thương nặng ở não bộ, …</span></li><li style="font-weight: 400; text-align: justify;"><span style="font-weight: 400; color: #000000;">Trải qua một sự kiện trọng đại gây tổn thương tâm lý: Ly dị, phá sản, nợ nần, mất đi người thân, gia đình hay con cái không hòa hợp, bạn đời ngoại tình, áp lực công việc quá lớn,…</span></li><li style="font-weight: 400; text-align: justify;"><span style="font-weight: 400; color: #000000;">Phụ nữ trước hoặc sau khi sinh con (chiếm tỷ trọng cao nhất trong tất cả các nguyên nhân gây trầm cảm).</span></li><li style="font-weight: 400; text-align: justify;"><span style="font-weight: 400; color: #000000;">Học sinh/sinh viên đang chịu nhiều áp lực về học tập và thi cử.</span></li><li style="font-weight: 400; text-align: justify;"><span style="font-weight: 400; color: #000000;">Phụ nữ tiền mãn kinh, tuổi hưu trí.</span></li><li><span style="color: #000000;">Thường xuyên chứng kiến hoặc trải qua những sự kiện gây tổn thương tâm lý: Bị ba mẹ (người thân) đánh mắng, chì chiết, áp đặt… hay sống trong gia đình ba mẹ không hạnh phúc, thường xuyên có mâu thuẫn.</span></li></ul>
<h2>5. Nguyên nhân gây ra chứng trầm cảm</h2>
<h3>5.1. Do biến cố</h3>
<p><span style="font-weight: 400; color: #000000;">Các biến cố tác động đến tâm lý từ thời điểm quá khứ và hiện tại. Không nhất thiết phải là một sự việc lớn gây sang chấn tâm lý, đôi khi chỉ là những áp lực nhỏ nhưng diễn ra thường ngày dẫn đến tình trạng bị dồn nén lâu ngày và quá nhiều thứ cùng lúc. Hoặc những sự kiện đó từng là nỗi ám ảnh lớn trong quá khứ thì vẫn sẽ tác động mạnh tới tâm trí con người.</span></p>
<h3>5.2. Các yếu tố môi trường</h3>
<p><span style="font-weight: 400; color: #000000;"><a href="https://trilieutamly.com/vuot-qua-roi-loan-lo-au-cong-so-voi-ho-tro-tam-ly-khong-dung-thuoc-28565.html">Áp lực công việc,</a> áp lực từ gia đình, bạn bè, đối tác, mẫu thuẫn mối quan hệ với người thân, đồng nghiệp, bạn bè, hoàn cảnh, môi trường sống… là những nguyên nhân dẫn tới tình trạng stress, và sau đó là trầm cảm.</span></p>
<h3>5.3. Phụ nữ trong thời gian thai kỳ, nuôi con nhỏ</h3>
<ul><li style="font-weight: 400; text-align: justify;"><span style="font-weight: 400; color: #000000;">Thay đổi nồng độ <span style="font-weight: 400;">hormone trong cơ thể.</span></span></li><li style="font-weight: 400; text-align: justify;"><span style="font-weight: 400; color: #000000;">Mang thai không theo kế hoạch hay ngoài ý muốn, chưa thích nghi với việc sẽ có em bé. </span></li><li style="font-weight: 400; text-align: justify;"><span style="font-weight: 400; color: #000000;">Khi bé có vấn đề về sức khỏe hoặc phải hồi phục sức khỏe dài ngày trongchứng viện, người mẹ có thể trải qua những cảm xúc như buồn, giận, có lỗi. </span></li><li style="font-weight: 400; text-align: justify;"><span style="font-weight: 400; color: #000000;">Biến chứng thai kỳ: thai lưu, sẩy thai.</span></li><li style="font-weight: 400; text-align: justify;"><span style="font-weight: 400; color: #000000;">Cảm thấy vô cùng mệt mỏi sau khi sinh, khó hồi phục trở lại như trước.</span></li><li style="font-weight: 400; text-align: justify;"><span style="font-weight: 400; color: #000000;">Thiếu sự giúp đỡ của người thân, mâu thuẫn với bạn đời hay người thân. </span></li><li style="font-weight: 400; text-align: justify;"><span style="font-weight: 400; color: #000000;">Trải qua sự kiện căng thẳng như có người thân vừa qua đời, sức khỏe của người thân trong gia đình suy giảm đột ngột, thay đổi nơi ở, trường học..</span></li></ul>
<h2>6. Tác dụng phụ của thuốc</h2>
<p><span style="font-weight: 400; color: #000000;">Việc sử dụng thuốc tây có thể gây ra tác dụng phụ cho cơ thể và sức khỏe. Nhiều người gặp những tác dụng phụ không mong muốn như: </span></p>
<ul><li style="font-weight: 400;"><span style="font-weight: 400; color: #000000;">Ngủ li bì trong thời gian dài.</span></li><li style="font-weight: 400;"><span style="font-weight: 400; color: #000000;">Mập, tăng cân rất nhanh hoặc gầy yếu rất nhanh.</span></li><li style="font-weight: 400;"><span style="font-weight: 400; color: #000000;">Giảm trí nhớ từ từ.</span></li><li style="font-weight: 400;"><span style="font-weight: 400; color: #000000;">Nhiều trường hợp gây ra chứng mất ngủ, khó ngủ.</span></li><li style="font-weight: 400;"><span style="font-weight: 400; color: #000000;">Đau đầu, nhức đầu.</span></li><li style="font-weight: 400;"><span style="font-weight: 400; color: #000000;">Cơ thể dần mất đi sức lực, trí lực giảm sút.</span></li><li style="font-weight: 400;"><span style="font-weight: 400; color: #000000;">Nhớ nhớ quên quên, không minh mẫn.</span></li><li style="font-weight: 400;"><span style="font-weight: 400; color: #000000;">Gây nên các vấn đề sức khỏe nghiêm trọng khác như: suy thận, suy gan, viêm dạ dày, tim mạch… </span></li><li style="font-weight: 400;"><span style="font-weight: 400; color: #000000;">Làm khách hàng nghĩ đến tự sát nhiều hơn, đặc biệt là trong mấy tuần đầu sử dụng thuốc. </span></li><li style="font-weight: 400;"><span style="font-weight: 400; color: #000000;">Gây suy giảm thể lực, làm khách hàng trở nên mệt mỏi hơn. </span></li><li style="font-weight: 400;"><span style="font-weight: 400; color: #000000;">Nhờn thuốc, buộc phải dùng thuốc liên tục hoặc tăng liều trong thời gian dài để cảm thấy thoải mái, an thần… </span></li><li><span style="color: #000000;">Phụ thuộc vào thuốc, bỏ thuốc dễ tái phát.</span></li></ul>
<h2>7. Xử lý trầm cảm không dùng thuốc tại Tâm lý NHC – Giải quyết vấn đề từ gốc rễ</h2>
<p><span style="font-weight: 400; color: #000000;">Tâm lý NHC đã hỗ trợ xử lý trầm cảm an toàn, hiệu quả cho hơn 3000 người Việt trong và ngoài nước. Mỗi khách hàng là một lộ trình riêng biệt được thiết kế để phù hợp với tình trạng sức khỏe hiện tại, nguyên nhân, đặc điểm tính cách, hoàn cảnh và mong muốn của khách hàng.</span></p>
<h3>7.1. Xử lý trầm cảm bằng tâm lý – Những điều bạn nên biết</h3>
<ul><li><span style="font-weight: 400; color: #000000;">Phương pháp hỗ trợ tư vấn giải quyết vấn đề tâm lý không cần sử dụng thuốc, không can thiệp vào cơ thể.</span></li><li><span style="font-weight: 400; color: #000000;">Chữa lành tổn thương tâm lý thông qua các buổi gỡ rối tâm lý bằng ngôn ngữ trực tiếp, các quy trình phục hồi sức khỏe tinh thần chuyên sâu tại Trung tâm và các quy trình chăm sóc sức khỏe tinh thần hàng ngày do chuyên gia hướng dẫn khách hàng thực hiện tại nhà. </span></li><li><span style="font-weight: 400; color: #000000;">Phương pháp hỗ trợ tâm lý của NHC Việt Nam là thành quả nghiên cứu nhiều năm của Thạc sĩ Tâm lý Bùi Thị Hải Yến, Giám đốc Trung tâm Tâm lý và Phát triển Con người NHC Việt Nam cùng các chuyên gia tâm lý được đào tạo bài bản từ các Hiệp hội quốc tế: NLP Hoa Kỳ, Hypnotherapy Hoa Kỳ, Time Line Therapy.</span></li><li><span style="color: #000000;">Đồng thời phương pháp được hỗ trợ chuyên môn bởi Viện Nghiên cứu Tâm lý và Phát triển Con người. </span></li><li><span style="font-weight: 400; color: #000000;">Lộ trình đồng hành được thiết kế dựa trên nguyên nhân gốc rễ gây ra trầm cảm, giải quyết dứt điểm trầm cảm, sức khỏe hồi phục một cách tự nhiên, hiệu quả được thể hiện rõ ràng bằng văn bản. </span></li><li><span style="font-weight: 400; color: #000000;">Đã hỗ trợ thành công cho hơn 3000 khách hàng gặp vấn đề trầm cảm, rối loạn lo âu, rối loạn cảm xúc, căng thẳng…</span></li></ul>
<h3><b>7.2. Lợi ích từ phương pháp gỡ rối tâm lý đồng hành 1-1 tại NHC Việt Nam</b></h3>
<ul><li><span style="color: #000000;">Giúp bạn <strong>từng bước tháo gỡ những suy nghĩ tiêu cực, cảm xúc nặng nề, nỗi sợ, niềm tin giới hạn và những mâu thuẫn bên trong</strong> khiến bạn mệt mỏi lâu ngày.</span></li><li><span style="color: #000000;">Hỗ trợ <strong>thay đổi thói quen hoặc cách phản ứng chưa phù hợp</strong> để cuộc sống trở nên dễ chịu và tích cực hơn mỗi ngày.</span></li><li><span style="color: #000000;">Tăng <strong>khả năng nhận thức về bản thân và biết chịu trách nhiệm</strong> với cảm xúc, hành động cũng như cuộc sống của mình.</span></li><li><span style="color: #000000;">Giúp bạn hiểu rõ <strong>mình đang thật sự muốn gì, cần gì</strong>, từ đó có định hướng và mục tiêu rõ ràng hơn cho tương lai.</span></li><li><span style="color: #000000;"><strong>Tiếp thêm động lực</strong> để bạn có thêm năng lượng vượt qua giai đoạn khó khăn và kiên trì với hành trình chữa lành của mình.</span></li><li><span style="color: #000000;">Hỗ trợ <strong>cải thiện sự tập trung, khả năng sáng tạo và tăng hiệu quả</strong> trong công việc, học tập cũng như cuộc sống hằng ngày.</span></li><li><span style="color: #000000;">Giúp bạn <strong>khám phá điểm mạnh, khả năng và những giá trị bên trong</strong> mà có thể bạn đã quên mất sau nhiều tổn thương hoặc áp lực.</span></li><li><span style="color: #000000;"><strong>Hỗ trợ cải thiện mối quan hệ</strong> với người thân, bạn bè, vợ chồng hoặc con cái trở nên <strong>dễ kết nối và thấu hiểu nhau</strong> hơn.</span></li><li><span style="font-weight: 400; color: #000000;"><strong>Có kỹ năng</strong> quản lý cảm xúc, xử lý stress – căng thẳng trong cuộc sống.</span></li></ul>
<p><span style="color: #ff0000;"><strong><span style="color: #008000;"><span style="color: #000000;">Liên hệ đặt lịch tham vấn và gỡ rối tâm lý cùng Chuyên gia:</span> </span></strong><span style="font-size: 18pt;"><b>096 589 8008</b></span></span></p>
<blockquote><p><strong><span style="color: #008000;">Hỗ trợ giải quyết vấn đề tâm lý: Giúp người trầm cảm bình phục một cách tự nhiên – không cần sử dụng bất kỳ loại thuốc nào.</span></strong></p>
</blockquote><h3>7.3. Quy trình xử lý trầm cảm không dùng thuốc tại Trung tâm Tâm lý và Phát triển Con người NHC Việt Nam</h3>
<p><span style="color: #000000;">Với mỗi vấn đề của khách hàng, Chuyên gia Tâm lý sẽ đưa ra những phương pháp đồng hành phù hợp nhất với tình trạng sức khỏe, tâm lý hiện tại, hoàn cảnh và mong muốn của khách hàng.</span></p>
<p><span style="color: #000000;">Dưới đây là một quy trình xử lý trầm cảm điển hình tại NHC Việt Nam:</span></p>
<ul><li><span style="color: #000000;"><strong>Giai đoạn 1 – GIẢI TỎA</strong>: Giúp khách hàng nhìn rõ vấn đề tâm lý và “giải tỏa” – tức là xử lý vấn đề ở bề mặt, có tính chất tức thời và ngắn hạn. Mục tiêu là ổn định cảm xúc hiện tại.</span></li><li><span style="color: #000000;"><strong>Giai đoạn 2 – CÂN BẰNG</strong>: Giúp khách hàng khám phá nguyên nhân gốc rễ gây nên vấn đề tâm lý và tìm ra giải pháp để giải quyết vấn đề lớn hiện tại đang gặp. Mục tiêu là giúp khách hàng chủ động tìm lại được bình an, biết cân bằng cảm xúc.</span></li><li><span style="color: #000000;"><strong>Giai đoạn 3 – TÍCH CỰC</strong>: Giúp khách hàng thấu hiểu và biết yêu, thương mình hơn và tìm lại được niềm tin.</span></li><li><span style="color: #000000;"><strong>Giai đoạn 4 – HUẤN LUYỆN</strong>: Cài đặt, bổ sung và phát triển hơn những tư duy, niềm tin tích cực và tương hỗ, để trở nên: Hiểu, yêu, thương mình; Hiểu và biết yêu, thương người khác đúng cách.</span></li></ul>
<ul><li><span style="color: #000000;"><strong>Giai đoạn 5 – HUẤN LUYỆN CHUYÊN SÂU</strong>: Giúp khách hàng sống với mục tiêu, ước mơ, nhiệt huyết, khát khao và năng lực được đánh thức.</span></li></ul>
<p><span style="color: #000000;">Trên đây là chương trình chuyên sâu để xử lý trầm cảm từ gốc rễ, nâng cấp tư duy, huấn luyện khách hàng trở thành chuyên gia của chính mình.</span></p>
<p><span style="color: #000000;">Tùy vào vấn đề tâm lý là nặng hay nhẹ, mỗi khách hàng sẽ được thiết kế một lộ trình xử lý trầm cảm riêng biệt (với phương pháp và quy trình cụ thể trong từng giai đoạn).</span></p>
<p><span style="color: #000000;">Đối với các trường hợp nhiều vấn đề thì sau khoảng 3 tuần mới có thể lên kế hoạch xử lý phù hợp.</span></p>
<h3>7.4. Quyền lợi của khách hàng khi đồng hành 1-1 cùng Tâm lý NHC</h3>
<ul><li style="font-weight: 400; text-align: justify;"><span style="font-weight: 400; color: #000000;">Bạn được<strong> bảo mật thông tin cá nhân</strong>.</span></li><li style="font-weight: 400; text-align: justify;"><span style="color: #000000;">Đi kèm với lộ trình là lời hứa về hiệu quả được thể hiện rõ trên giấy tờ. </span></li><li style="font-weight: 400; text-align: justify;"><span style="font-weight: 400; color: #000000;">Mọi sự hồi phục đều đến một cách <strong>tự nhiên</strong>, <strong>không sử dụng bất kỳ loại thuốc nào.</strong></span></li><li style="font-weight: 400; text-align: justify;"><span style="font-weight: 400; color: #000000;">Sau khi kết thúc lộ trình, bạn sẽ cảm thấy cơ thể có năng lượng tích cực,<strong> biết yêu thương, chăm sóc bản thân, tự tin hơn trong cuộc sống. </strong></span></li></ul>
<h2>8. Câu chuyện khách hàng</h2>
<p><span style="font-weight: 400; color: #000000;">Có rất nhiều khách hàng đã vượt qua trầm cảm và sẵn sàng chia sẻ câu chuyện của mình với mong muốn có thể giúp được ai đó đang gặp vấn đề tương tự như mình có thêm động lực, tia sáng cho mình.  </span></p>
<p><span style="color: #000000;"><em><strong>Chia sẻ của T.A (28 tuổi, Hà Nội)</strong>: “Tôi là T.A, hiện đang quản lý 2 nhà hàng phong cách Tây Âu tại Hà Nội. Cách đây mấy năm, tôi gặp trục trặc trong cuộc sống cá nhân và tôi từng rơi vào trầm khá nặng và gặp một số vấn đề tâm lý khác nữa. Tôi cũng đã từng tự tử hụt và may mắn được cứu sống. Điều đó ảnh hưởng rất nhiều đến cuộc sống của tôi, đặc biệt là trong công việc. Tôi không thể nào làm bất kỳ công việc gì. Đó một giai đoạn rất nặng nề đối với tôi. Tôi rất may mắn khi được đồng hành cùng Chuyên gia Hải Yến để chữa lành tổn thương tâm lý và phát triển bản thân để có thể quản lý công việc gia đình, quản lý việc kinh doanh cá nhân. Trong quá trình đồng hành cùng chị Yến, tôi đã học được rất nhiều điều, tôi đã hiểu được mình là ai, xác định được mục tiêu của mình, định vị được nhân dạng của bản thân và rèn luyện được sự kỷ luật. Đó là một hành trình rất tuyệt vời vì trước đó tôi đã từng tìm đến b.viện hoặc một số chuyên gia tâm lý khác nhưng vấn đề của tôi vẫn không được cải thiện”.</em></span></p>
<p><span style="color: #000000;">Xem thêm về</span> <a href="https://trilieutamly.com/vuot-qua-tram-cam-co-gai-9x-tro-thanh-doanh-nhan-thanh-dat-va-hanh-phuc-23322.html">hành trình vượt qua trầm cảm của khách hàng T.A</a></p>
<p><span style="color: #000000;"><strong>Khách hàng X.N (22 tuổi, TP. Hồ Chí Minh)</strong>: <em>Ròng rã 5 năm (2020-2025), tôi uống thuốc nhưng vẫn không tìm được sự bình an. Tâm trạng vẫn đầy tiêu cực, đau khổ, khó chịu. Tôi nhận ra thuốc chỉ là giải pháp tạm thời, không giải quyết được vấn đề cốt lõi. Tôi và gia đình quyết định thử phương pháp tâm lý xem có hiệu quả không. Giai đoạn đầu đồng hành với Chuyên gia Thắm không hề dễ dàng, nhiều lúc tôi đã muốn bỏ cuộc. Nhưng vượt qua được tháng đầu tiên, mọi thứ dần sáng tỏ hơn. Tôi hiểu hơn về cảm xúc và những điều đã tác động đến bản thân mình trong quá khứ, hiện tại. Sau 5 tháng đồng hành và thực hành theo hướng dẫn của Chuyên gia, tôi đã có thể cười một cách vô tư, cơ thể khỏe mạnh để leo núi, chạy 21km và cảm nhận được tình yêu thương của gia đình.</em></span></p>
<p><span style="color: #000000;">Tìm hiểu thêm</span> <a href="https://trilieutamly.com/du-hoc-sinh-tram-cam-xn-tim-lai-ban-than-32966.html">câu chuyện của khách hàng X.N</a>.</p>
<p><span style="color: #000000;"><strong>T.U (25 tuổi) chia sẻ</strong>: “<em>Trước khi đến với NHC, em đã từng được hỗ trợ trầm cảm bằng uống thuốc tây 4 năm nhưng không có hiệu quả tích cực. Nó chỉ có hiệu quả tạm thời và em bị phụ thuộc vào thuốc. Giai đoạn đầu đồng hành với chị Thanh Phương với rất khó khăn bởi em luôn đa nghi, sợ hãi với người mà mình tiếp xúc. Nhưng chị Phương đã kiên trì và đồng hành cùng em vượt qua giai đoạn tâm lý khó khăn. Sau 5 tháng hỗ trợ tâm lý ở NHC, em đã chấm dứt việc dùng thuốc, biết tự quản lý cảm xúc và chủ động trong các công việc cá nhân. Em đã chủ động chuyển ra ngoài sống một mình, tự đi xe máy và tìm được công việc phù hợp với bản thân. Đối với những người khác, có thể điều này là bình thường nhưng với em là một sự nỗ lực rất lớn từ bản thân dưới sự hỗ trợ từ chị Phương”</em>.</span></p>
<p><span style="color: #000000;">Đọc thêm về</span> <a href="https://trilieutamly.com/cham-dut-4-nam-dung-thuoc-tram-cam-nho-tri-lieu-tam-ly-25224.html">hành trình vượt qua trầm cảm, ngừng phụ thuộc vào thuốc của khách hàng T.U</a></p>
<h3 dir="ltr">Lý do khách hàng tin tưởng và chọn dịch vụ của Tâm lý NHC</h3>
<p><span style="color: #000000;"><strong>✅ </strong></span><strong>Chính sách hoàn chi phí</strong> rõ ràng nếu khách hàng không hài lòng.</p>
<p>✅ <span style="color: #ff0000;"><strong>Có bảo hành trên giấy tờ.</strong></span></p>
<p><span style="color: #000000;">✅ Được bảo trợ chuyên môn bởi <strong>Viện Nghiên cứu Tâm lý và Phát triển Con người</strong>.</span></p>
<p><span style="color: #000000;">✅ Được <strong>cấp phép</strong> hoạt động.</span></p>
<p><span style="color: #000000;">✅ Được hơn <span style="color: #ff0000;"><strong>3000 khách hàng</strong></span> <strong>tin tưởng</strong> và <strong>đồng hành.</strong></span></p>
<p><span style="color: #000000;"><strong>✅ An toàn</strong> và phù hợp với mọi đối tượng khách hàng, kể cả phụ nữ mang thai, sau sinh hoặc đang cho con bú.</span></p>
<p><span style="color: #000000;"><strong>Nếu bản thân hay người thân của bạn đang mắc chứng Trầm cảm, hãy liên hệ ngay Trung tâm để được tư vấn và đồng hành kịp thời, tìm lại sức khỏe và niềm vui sống!</strong></span></p>
<h2><span style="color: #003366;"><strong>TRUNG TÂM TÂM LÝ VÀ PHÁT TRIỂN CON NGƯỜI NHC VIỆT NAM</strong></span></h2>
<p><span style="color: #008000;"><em><b>Một trong những đơn vị tiên phong tại Việt Nam áp dụng giải pháp hỗ trợ tâm lý không dùng thuốc</b></em></span></p>
<p><span style="color: #003366;"><b>Uy tín – Tận tâm – Trách nhiệm – Chuyên nghiệp</b></span></p>
<ul><li><span style="color: #000000;"><strong>Cơ sở 1:</strong> Số 235 Quan Hoa, Phường Nghĩa Đô, Hà Nội</span></li><li><span style="color: #000000;"><strong>Cơ sở 2: </strong>Số 37 Thâm Tâm, Phường Yên Hoà, Hà Nội</span></li><li><span style="color: #000000;"><strong>Cơ sở 3:</strong> Số 18 Phan Chu Trinh nối dài, Phường Bình Lợi Trung, TP Hồ Chí Minh</span></li><li><span style="color: #000000;"><strong>Cơ sở 4:</strong> Số 107 Hoàng Hoa Thám, Phường Gia Định, TP Hồ Chí Minh</span></li><li><span style="color: #000000;"><strong>Hotline:</strong> 096 589 8008</span></li><li><span style="color: #000000;"><strong>Website: </strong>tamlynhc.vn</span></li><li><span style="color: #000000;"><strong>Email:</strong></span> <a href="mailto:tamlyphattriennhc@gmail.com" rel="noopener" target="_blank">tamlyphattriennhc@gmail.com</a></li></ul>
<p><span style="font-weight: 400; color: #000000;">(1): https://www.who.int/news-room/fact-sheets/detail/depression cập nhật ngày 29/8/2025. </span></p>
<blockquote><h3>Có thể bạn quan tâm:</h3>
<p><span style="color: #ff0000;"> </span><a href="https://trilieutamly.com/5-thang-chuyen-hoa-ton-thuong-tam-ly-cua-9-nam-chim-trong-bong-toi-33484.html">9 năm mang trong mình những tổn thương tâm lý, bạn trẻ Kiên Giang đã thay đổi thế nào sau 5 tháng?</a><br/><span style="color: #ff0000;"> </span><a href="https://trilieutamly.com/dau-tim-kho-tho-nhung-kham-khong-ra-benh-bi-mat-duoc-giai-dap-nho-go-roi-tam-ly-32762.html">Hành trình vượt qua trầm cảm &amp; những cơn đau tim không rõ nguyên nhân của doanh nhân Sài Gòn</a><br/><span style="color: #ff0000;"> </span><a href="https://trilieutamly.com/cham-thuoc-la-vao-tay-de-giai-toa-cang-thang-do-mau-thuan-ban-be-n-t-h-da-vuot-qua-the-nao-33144.html">Mâu thuẫn bạn bè, sinh viên 20 tuổi vượt qua vấn đề tình trạng tự châm thuốc vào tay như thế nào?</a><br/><span style="color: #ff0000;"> </span><a href="https://trilieutamly.com/thoat-khoi-tram-cam-va-lam-chu-tai-chinh-o-tuoi-doi-muoi-nho-go-roi-tam-ly-25439.html">Hành trình thoát khỏi trầm cảm và làm chủ tài chính ở tuổi đôi mươi</a></p>
</blockquote>
            ', 'process' => 'Đánh giá nhu cầu ban đầu; xây dựng mục tiêu nhỏ an toàn; theo dõi tiến triển và điều chỉnh kế hoạch đồng hành.'],
            
            ['key' => 'cbt', 'category' => 'individual', 'name' => 'Trị liệu Nhận thức Hành vi CBT', 'duration' => 60, 'price' => 800000, 'image' => 'https://images.unsplash.com/photo-1576091160399-112ba8d25d1d?auto=format&fit=crop&w=1200&q=85', 'audience' => '
            <p><span style="font-size: 20pt; color: #65ad45;"><b>Trị liệu Nhận thức Hành vi (CBT) – Làm việc có hệ thống với suy nghĩ tự động, hành vi né tránh và phản ứng lo âu</b></span></p>
<ul>
<li><span style="color: #000000;"><strong>Phương pháp có cấu trúc rõ ràng</strong>: có mục tiêu, có bài tập và có đánh giá tiến triển qua từng giai đoạn.</span></li>
<li><span style="color: #000000;">Phù hợp với những ai muốn <strong>hiểu cơ chế của lo âu</strong> và học kỹ năng tự ứng phó lâu dài.</span></li>
<li><span style="color: #000000;">Tập trung vào <strong>suy nghĩ tự động, hành vi né tránh và các phản ứng cơ thể</strong> đang duy trì vấn đề.</span></li>
</ul>
<h2>Trị liệu Nhận thức Hành vi (CBT) là gì?</h2>
<p style="text-align: justify;">Trị liệu Nhận thức Hành vi (Cognitive Behavioral Therapy – CBT) là một hướng tiếp cận tâm lý được nghiên cứu rộng rãi trên thế giới, được sử dụng trong hỗ trợ các vấn đề như lo âu, hoảng sợ, ám ảnh, căng thẳng kéo dài và trầm cảm.</p>
<p style="text-align: justify;">Ý tưởng cốt lõi của CBT là <strong>suy nghĩ, cảm xúc, hành vi và phản ứng cơ thể luôn tác động qua lại với nhau</strong>. Khi một suy nghĩ lo lắng xuất hiện, cơ thể căng lên, ta có xu hướng tránh né tình huống đó. Việc tránh né giúp dễ chịu trong chốc lát nhưng lại khiến nỗi sợ ngày càng lớn hơn. CBT giúp bạn nhìn ra vòng lặp này và từng bước phá vỡ nó.</p>
<blockquote>
<p style="text-align: center;"><span style="color: #008000; font-size: 14pt;"><strong>Hiểu vòng lặp – Thay đổi từng mắt xích – Rèn kỹ năng để tự duy trì</strong></span></p>
</blockquote>
<h2>Ba trọng tâm làm việc của CBT với lo âu</h2>
<h3>1. Suy nghĩ tự động</h3>
<p style="text-align: justify;">Suy nghĩ tự động là những ý nghĩ xuất hiện rất nhanh trong đầu, gần như không cần chủ ý, ví dụ: “Mình sẽ làm hỏng hết”, “Mọi người đang đánh giá mình”, “Chắc chắn có chuyện xấu sắp xảy ra”. Trong CBT, bạn học cách:</p>
<ul>
<li>Nhận diện và ghi lại các suy nghĩ tự động trong từng tình huống cụ thể.</li>
<li>Gọi tên các kiểu suy nghĩ lệch lạc thường gặp như thảm họa hóa, đọc suy nghĩ người khác, nhìn mọi thứ theo kiểu tất cả hoặc không gì cả.</li>
<li>Kiểm chứng suy nghĩ bằng bằng chứng thực tế và xây dựng cách nhìn cân bằng hơn.</li>
</ul>
<h3>2. Hành vi né tránh</h3>
<p style="text-align: justify;">Né tránh có thể là tránh một địa điểm, tránh gặp người, trì hoãn công việc, hoặc những hành vi “an toàn” như kiểm tra liên tục, tìm kiếm sự trấn an. Những hành vi này làm dịu lo âu tức thì nhưng vô tình giữ cho nỗi sợ tồn tại. CBT hỗ trợ bạn:</p>
<ul>
<li>Lập danh sách các tình huống bị né tránh và xếp theo mức độ khó.</li>
<li>Đối diện dần dần, từng bậc một, theo tốc độ phù hợp với bạn.</li>
<li>Thực hiện các thực nghiệm hành vi để kiểm tra điều mình lo sợ có xảy ra như dự đoán hay không.</li>
</ul>
<h3>3. Phản ứng lo âu của cơ thể</h3>
<p style="text-align: justify;">Tim đập nhanh, thở gấp, run tay, căng cơ, khó ngủ… là những phản ứng cơ thể thường gặp khi lo âu. Hiểu rằng đây là phản ứng báo động quen thuộc của cơ thể giúp bạn bớt sợ chính các triệu chứng. Bạn sẽ được hướng dẫn các kỹ năng như thở chậm, thư giãn cơ và chú ý có chủ đích để điều hòa phản ứng này.</p>
<h2>CBT phù hợp với ai?</h2>
<ul>
<li>Người hay lo lắng quá mức, khó dừng những suy nghĩ lặp đi lặp lại.</li>
<li>Người có xu hướng tránh né các tình huống xã hội, công việc hoặc học tập vì sợ.</li>
<li>Người từng gặp những cơn hoảng sợ hoặc luôn lo sợ cơn hoảng sợ quay lại.</li>
<li>Người có các nỗi sợ cụ thể hoặc hành vi lặp lại gây ảnh hưởng sinh hoạt hằng ngày.</li>
<li>Người thích cách làm việc rõ ràng, có bài tập và muốn chủ động rèn kỹ năng cho bản thân.</li>
<li>Phụ huynh muốn tìm hướng hỗ trợ có cấu trúc cho con ở độ tuổi phù hợp, có người thân đồng hành.</li>
</ul>
<h2>Quy trình một lộ trình CBT điển hình</h2>
<ul>
<li><strong>Bước 1 – Tìm hiểu và đánh giá</strong>: trao đổi về vấn đề hiện tại, mức độ ảnh hưởng, tiền sử và mong muốn của bạn.</li>
<li><strong>Bước 2 – Xác định mục tiêu</strong>: cùng chuyên gia đặt mục tiêu cụ thể, đo lường được, ví dụ “có thể phát biểu trong cuộc họp mà không phải né tránh”.</li>
<li><strong>Bước 3 – Xây dựng mô hình cá nhân</strong>: vẽ ra vòng lặp giữa tình huống, suy nghĩ, cảm xúc, cơ thể và hành vi của riêng bạn.</li>
<li><strong>Bước 4 – Thực hành kỹ năng</strong>: ghi nhật ký suy nghĩ, tái cấu trúc nhận thức, thực nghiệm hành vi, đối diện từng bước và kỹ năng điều hòa cơ thể.</li>
<li><strong>Bước 5 – Củng cố và phòng ngừa tái phát</strong>: tổng kết điều đã học, nhận biết dấu hiệu sớm và xây dựng kế hoạch ứng phó khi căng thẳng quay lại.</li>
</ul>
<p style="text-align: justify;">Số buổi phụ thuộc vào mục tiêu và mức độ của từng người. Nhiều lộ trình CBT được thiết kế trong khoảng vài tuần đến vài tháng, với các bài tập thực hành giữa các buổi để kỹ năng được áp dụng vào đời sống thật.</p>
<h2>Lợi ích bạn có thể kỳ vọng</h2>
<ul>
<li><strong>Hiểu rõ hơn</strong> vì sao lo âu xuất hiện và điều gì đang duy trì nó.</li>
<li>Biết cách <strong>nhận ra và điều chỉnh suy nghĩ</strong> khi chúng trở nên quá tiêu cực.</li>
<li>Tự tin <strong>đối diện dần</strong> những tình huống từng né tránh.</li>
<li>Có bộ <strong>kỹ năng điều hòa cơ thể và cảm xúc</strong> để dùng lâu dài.</li>
<li>Giảm mức độ ảnh hưởng của lo âu đến học tập, công việc và các mối quan hệ.</li>
</ul>
<h2>Những điều cần lưu ý</h2>
<ul>
<li>CBT cần sự tham gia chủ động: <strong>bài tập giữa các buổi</strong> là một phần quan trọng của quá trình.</li>
<li>Mỗi người tiến triển với tốc độ khác nhau, và hiệu quả cụ thể phụ thuộc vào tình trạng cũng như sự phối hợp của bạn.</li>
<li>CBT có thể được kết hợp với chăm sóc y tế. Nếu bạn đang dùng thuốc theo chỉ định, <strong>không tự ý ngưng thuốc</strong>, hãy trao đổi với bác sĩ điều trị.</li>
<li>Nếu bạn có triệu chứng thể chất kéo dài, nên đi khám để loại trừ nguyên nhân bệnh lý.</li>
<li>Nếu bạn hoặc người thân có ý nghĩ tự làm hại bản thân, hãy liên hệ ngay người thân, cơ sở y tế hoặc dịch vụ hỗ trợ khẩn cấp gần nhất.</li>
</ul>
<h2>Câu hỏi thường gặp</h2>
<p><strong>CBT khác gì so với trò chuyện thông thường?</strong><br>CBT có cấu trúc: mỗi buổi có chủ đề, có kỹ năng cụ thể và có bài tập thực hành. Mục tiêu là giúp bạn tự áp dụng được kỹ năng ngoài buổi trị liệu.</p>
<p><strong>Tôi có phải kể hết mọi chuyện quá khứ không?</strong><br>CBT chủ yếu tập trung vào những gì đang duy trì vấn đề ở hiện tại. Chuyện quá khứ chỉ được đề cập ở mức bạn thấy thoải mái và cần thiết.</p>
<p><strong>Tôi sợ tình huống nào đó thì có bị ép phải đối diện không?</strong><br>Không. Việc đối diện được chia thành các bậc nhỏ, do bạn và chuyên gia cùng thống nhất, và luôn theo tốc độ phù hợp với bạn.</p>
<p><strong>Bao lâu thì thấy thay đổi?</strong><br>Nhiều người nhận ra sự khác biệt ban đầu sau một số buổi đầu khi đã hiểu rõ vòng lặp lo âu của mình. Kết quả bền vững đến từ việc luyện tập đều đặn.</p>
<p style="text-align: center;"><span style="font-size: 14pt; color: #008000;"><b>Hãy liên hệ để được tham vấn và xây dựng lộ trình CBT phù hợp với bạn</b></span></p>
            ', 'process' => 'Lập bản đồ vấn đề; thực hành kỹ thuật CBT; giao bài tập quan sát và thử nghiệm hành vi phù hợp.'],
            
            ['key' => 'couple', 'category' => 'couple', 'name' => 'Tham vấn Cặp đôi & Hôn nhân', 'duration' => 60, 'price' => 950000, 'image' => 'https://images.unsplash.com/photo-1529156069898-49953e39b3ac?auto=format&fit=crop&w=1200&q=85', 'audience' => '
            <p><span style="font-size: 20pt; color: #65ad45;"><b>Tham vấn Cặp đôi &amp; Hôn nhân – Cùng nhau hiểu nhau và xây lại sự kết nối</b></span></p>
<ul>
<li><span style="color: #000000;">Không gian <strong>an toàn, trung lập</strong> để cả hai cùng được lắng nghe, không ai bị phán xét hay "xử đúng sai".</span></li>
<li><span style="color: #000000;">Tập trung vào <strong>giao tiếp, xử lý xung đột, niềm tin và kỳ vọng</strong> giữa hai người.</span></li>
<li><span style="color: #000000;">Mỗi buổi kéo dài <strong>60 phút</strong>, có bài thực hành nhỏ để áp dụng ngay trong đời sống hằng ngày.</span></li>
</ul>
<h2>Tham vấn cặp đôi &amp; hôn nhân là gì?</h2>
<p style="text-align: justify;">Tham vấn cặp đôi là quá trình làm việc cùng một chuyên gia tâm lý để hai người hiểu rõ hơn cách mình đang tương tác với nhau, nhận ra những vòng lặp gây tổn thương và học các cách phản hồi mới. Mục tiêu không phải là phân định ai đúng ai sai, mà là giúp mối quan hệ trở nên an toàn và gần gũi hơn.</p>
<p style="text-align: justify;">Dịch vụ phù hợp với cặp đôi đang hẹn hò, đã đính hôn hay đã kết hôn. Bạn không cần phải đợi đến lúc khủng hoảng mới tìm đến tham vấn.</p>
<blockquote>
<p style="text-align: center;"><span style="color: #008000; font-size: 14pt;"><strong>Lắng nghe để hiểu – Hiểu để kết nối – Kết nối để cùng thay đổi</strong></span></p>
</blockquote>
<h2>Dịch vụ phù hợp với ai?</h2>
<p style="text-align: justify;">Các cặp đôi muốn cải thiện giao tiếp, xử lý xung đột, hàn gắn tổn thương niềm tin hoặc dung hòa những khác biệt về kỳ vọng. Một số tình huống thường gặp:</p>
<ul>
<li>Hai người hay cãi nhau về cùng một vấn đề mà không đi đến đâu.</li>
<li>Một trong hai hoặc cả hai thu mình, im lặng hoặc né tránh nói chuyện.</li>
<li>Niềm tin bị tổn thương sau những sự việc làm cả hai buồn lòng và muốn xây lại.</li>
<li>Khác biệt về quan điểm tiền bạc, con cái, công việc, gia đình hai bên hay cách chia sẻ trách nhiệm.</li>
<li>Cảm giác xa cách dần dù vẫn sống chung, hoặc đang đứng trước một quyết định lớn của mối quan hệ.</li>
<li>Muốn chuẩn bị tốt hơn trước khi kết hôn hoặc bước sang giai đoạn mới như có con.</li>
</ul>
<h2>Quy trình một lộ trình tham vấn</h2>
<ul>
<li><strong>Bước 1 – Thiết lập nguyên tắc đối thoại</strong>: cùng thống nhất cách nói chuyện an toàn trong buổi tham vấn, như lần lượt phát biểu, không ngắt lời, không công kích cá nhân.</li>
<li><strong>Bước 2 – Nhận diện chu kỳ xung đột</strong>: tìm hiểu vòng lặp quen thuộc của hai người, điều gì kích hoạt, mỗi người phản ứng ra sao và cần gì ở phía sau phản ứng đó.</li>
<li><strong>Bước 3 – Luyện tập cách phản hồi</strong>: thực hành cách bày tỏ cảm xúc, lắng nghe và đáp lại nhau theo hướng giảm căng thẳng, ngay trong buổi với sự hướng dẫn của chuyên gia.</li>
<li><strong>Bước 4 – Kế hoạch kết nối tại nhà</strong>: xây dựng những bài tập và thói quen nhỏ, vừa sức để hai người duy trì giữa các buổi.</li>
</ul>
<p style="text-align: justify;">Số buổi tùy thuộc vào mục tiêu và tình trạng của mối quan hệ. Chuyên gia sẽ cùng hai bạn rà soát tiến triển định kỳ và điều chỉnh hướng làm việc cho phù hợp.</p>
<h2>Lợi ích bạn có thể kỳ vọng</h2>
<ul>
<li><strong>Hiểu rõ hơn</strong> nhu cầu và cảm xúc của bản thân lẫn người kia.</li>
<li>Biết cách <strong>nói điều mình cần</strong> mà không làm tổn thương nhau.</li>
<li>Giảm các cuộc cãi vã lặp đi lặp lại, <strong>xử lý bất đồng</strong> bình tĩnh hơn.</li>
<li>Có thêm công cụ để <strong>xây lại sự tin cậy và gần gũi</strong> theo tốc độ cả hai thấy phù hợp.</li>
</ul>
<h2>Những điều cần lưu ý</h2>
<ul>
<li>Hiệu quả tốt nhất khi <strong>cả hai cùng tham gia</strong>. Nếu chỉ một người sẵn sàng, bạn vẫn có thể bắt đầu với buổi tham vấn cá nhân.</li>
<li>Mọi thông tin chia sẻ trong buổi được <strong>giữ bảo mật</strong>.</li>
<li>Tham vấn không thay thế việc hỗ trợ khẩn cấp. Nếu có bạo lực hoặc nguy cơ an toàn cho bản thân hay người thân, hãy liên hệ ngay cơ quan chức năng hoặc dịch vụ hỗ trợ gần nhất.</li>
</ul>
<h2>Câu hỏi thường gặp</h2>
<p><strong>Nếu chỉ mình tôi muốn đi thì sao?</strong><br>Bạn vẫn có thể bắt đầu một mình để hiểu rõ vấn đề và cách mình đang góp phần vào vòng lặp. Khi sẵn sàng, bạn có thể mời người kia cùng tham gia.</p>
<p><strong>Chuyên gia có đứng về phía một người không?</strong><br>Không. Chuyên gia giữ vai trò trung lập, giúp hai người hiểu nhau chứ không đánh giá ai đúng ai sai.</p>
<p><strong>Tham vấn có giúp tránh chia tay không?</strong><br>Mục tiêu là giúp hai người nhìn rõ mối quan hệ và đưa ra quyết định sáng suốt, dù là cùng hàn gắn hay kết thúc một cách tôn trọng. Kết quả phụ thuộc vào mong muốn và sự nỗ lực của cả hai.</p>
<p style="text-align: center;"><span style="font-size: 14pt; color: #008000;"><b>Hãy liên hệ để được tham vấn và xây dựng lộ trình phù hợp với hai bạn</b></span></p>
            ', 'process' => 'Thiết lập nguyên tắc đối thoại; nhận diện chu kỳ xung đột; luyện tập cách phản hồi và kế hoạch kết nối tại nhà.'],
            
            ['key' => 'family', 'category' => 'couple', 'name' => 'Trị liệu Hệ thống Gia đình', 'duration' => 60, 'price' => 900000, 'image' => 'https://images.unsplash.com/photo-1511895426328-dc8714191300?auto=format&fit=crop&w=1200&q=85', 'audience' => '
<p><span style="font-size: 20pt; color: #65ad45;"><b>Trị liệu Hệ thống Gia đình – Cùng nhìn lại gia đình như một thể thống nhất</b></span></p>
<ul>
<li><span style="color: #000000;">Nhìn vấn đề <strong>trong bối cảnh cả gia đình</strong>, thay vì quy cho riêng một thành viên.</span></li>
<li><span style="color: #000000;">Có sự <strong>tham gia của nhiều thành viên</strong> cùng thiết kế những thay đổi nhỏ, vừa sức.</span></li>
<li><span style="color: #000000;">Mỗi buổi kéo dài <strong>60 phút</strong>, tập trung vào giao tiếp, vai trò và nguồn lực của gia đình.</span></li>
</ul>
<h2>Trị liệu hệ thống gia đình là gì?</h2>
<p style="text-align: justify;">Trị liệu hệ thống gia đình là hướng tiếp cận xem gia đình như một hệ thống, trong đó mỗi thành viên ảnh hưởng và chịu ảnh hưởng bởi những người còn lại. Khi một người gặp khó khăn, thường có những mối quan hệ, vai trò hoặc cách giao tiếp trong gia đình đang liên quan đến điều đó.</p>
<p style="text-align: justify;">Thay vì hỏi "ai là người có vấn đề?", chuyên gia cùng gia đình tìm hiểu "mọi người đang tương tác với nhau như thế nào, và đâu là cách mới giúp cả nhà dễ thở hơn?".</p>
<blockquote>
<p style="text-align: center;"><span style="color: #008000; font-size: 14pt;"><strong>Mỗi thành viên đều được lắng nghe – Cả nhà cùng thay đổi, từng bước nhỏ</strong></span></p>
</blockquote>
<h2>Dịch vụ phù hợp với ai?</h2>
<p style="text-align: justify;">Gia đình gặp khó khăn trong giao tiếp, chuyển tiếp vai trò, nuôi dạy con hoặc thích nghi sau biến cố. Một số tình huống thường gặp:</p>
<ul>
<li>Các thành viên khó nói chuyện với nhau, dễ nảy sinh hiểu lầm hoặc cãi vã.</li>
<li>Gia đình đang chuyển giai đoạn: có con đầu lòng, con vào tuổi dậy thì, con rời nhà, sống chung nhiều thế hệ, hoặc có thành viên nghỉ hưu.</li>
<li>Cha mẹ bất đồng về cách nuôi dạy con, hoặc con và cha mẹ khó hiểu nhau.</li>
<li>Gia đình sau ly hôn, tái hợp, ghép gia đình hoặc thay đổi cấu trúc sống chung.</li>
<li>Cả nhà đang thích nghi sau biến cố như mất người thân, bệnh tật, mất việc hay chuyển nơi ở.</li>
<li>Một thành viên đang gặp khó khăn tâm lý và gia đình muốn biết cách đồng hành đúng cách.</li>
</ul>
<h2>Quy trình một lộ trình trị liệu</h2>
<ul>
<li><strong>Bước 1 – Khám phá mối quan hệ trong hệ thống</strong>: tìm hiểu cách các thành viên giao tiếp, phân chia vai trò, quy tắc ngầm và những điểm căng thẳng thường gặp.</li>
<li><strong>Bước 2 – Xác định nguồn lực của gia đình</strong>: ghi nhận những điều đang vận hành tốt, giá trị chung và những lúc cả nhà từng vượt qua khó khăn cùng nhau.</li>
<li><strong>Bước 3 – Thiết kế thay đổi nhỏ có sự tham gia của các thành viên</strong>: cùng thống nhất những thay đổi vừa sức, cụ thể, được thử nghiệm trong sinh hoạt hằng ngày.</li>
<li><strong>Bước 4 – Rà soát và điều chỉnh</strong>: nhìn lại điều đã hiệu quả, điều cần chỉnh và củng cố những thói quen mới.</li>
</ul>
<p style="text-align: justify;">Tùy tình huống, buổi trị liệu có thể có cả gia đình cùng tham gia hoặc chỉ một số thành viên. Số buổi phụ thuộc vào mục tiêu và mức độ phức tạp của vấn đề.</p>
<h2>Lợi ích bạn có thể kỳ vọng</h2>
<ul>
<li><strong>Hiểu nhau hơn</strong>, nhìn ra nhu cầu và áp lực của từng thành viên.</li>
<li>Giao tiếp <strong>rõ ràng và bớt căng thẳng</strong> hơn trong những chủ đề nhạy cảm.</li>
<li>Phân chia <strong>vai trò, kỳ vọng và ranh giới</strong> hợp lý hơn giữa các thế hệ.</li>
<li>Cùng nhau <strong>thích nghi với thay đổi</strong> và đồng hành khi một thành viên gặp khó khăn.</li>
</ul>
<h2>Những điều cần lưu ý</h2>
<ul>
<li>Trị liệu không nhằm tìm ra "người có lỗi" mà để cả gia đình cùng tìm hướng đi.</li>
<li>Không phải ai cũng cần có mặt ở mọi buổi. Chuyên gia sẽ cùng gia đình thống nhất cách tham gia phù hợp, và thành viên là trẻ nhỏ cần có người lớn đi cùng.</li>
<li>Thông tin chia sẻ trong buổi được <strong>giữ bảo mật</strong>.</li>
<li>Nếu trong gia đình có bạo lực hoặc nguy cơ an toàn, hãy liên hệ ngay cơ quan chức năng hoặc dịch vụ hỗ trợ gần nhất. Trị liệu không thay thế các biện pháp bảo vệ khẩn cấp.</li>
</ul>
<h2>Câu hỏi thường gặp</h2>
<p><strong>Có bắt buộc cả nhà phải đến đủ không?</strong><br>Không bắt buộc. Có thể bắt đầu với một vài thành viên sẵn sàng, các thành viên khác có thể tham gia sau.</p>
<p><strong>Con tôi không muốn tham gia thì sao?</strong><br>Điều này khá phổ biến. Chuyên gia sẽ làm việc nhẹ nhàng để con cảm thấy an toàn, và đôi khi bắt đầu bằng buổi cùng cha mẹ trước.</p>
<p><strong>Trị liệu gia đình khác gì tham vấn cá nhân?</strong><br>Tham vấn cá nhân tập trung vào trải nghiệm của một người, còn trị liệu gia đình tập trung vào cách các thành viên tương tác với nhau. Hai hình thức có thể bổ trợ cho nhau.</p>
<p style="text-align: center;"><span style="font-size: 14pt; color: #008000;"><b>Hãy liên hệ để được tham vấn và xây dựng lộ trình phù hợp với gia đình bạn</b></span></p>', 
                'process' => 'Khám phá mối quan hệ trong hệ thống; xác định nguồn lực gia đình; thiết kế thay đổi nhỏ có sự tham gia của các thành viên.'
            ],

            ['key' => 'teen', 'category' => 'teen', 'name' => 'Tâm lý Học đường & Tuổi teen', 'duration' => 45, 'price' => 550000, 'image' => 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?auto=format&fit=crop&w=1200&q=85', 'audience' => '
            <p><strong>Tâm lý học đường là hình thức hỗ trợ, cung cấp giải pháp chăm sóc sức khỏe tinh thần cho các em học sinh, sinh viên. Nhờ vào phương pháp này mà các em học sinh có thể nâng cao khả năng tự giải quyết vấn đề trong việc học tập, thi cử cùng các mối quan hệ trong gia đình, xã hội.</strong></p>
<p><span></span></p>
<h2>Hỗ trợ tâm lý học đường là gì?</h2>
<p>Hiểu một cách đơn giản, hỗ trợ tâm lý học đường cũng giống với hình thức hỗ trợ tâm lý bình thường, tuy nhiên phạm vi hoạt động sẽ được thu hẹp lại trong trường học. Đây là hoạt động có thể giúp cho học sinh có định hướng đúng đắn hơn về những vấn đề riêng tư, xã hội hoặc giúp xác định cụ thể về nghề nghiệp của bản thân trong tương lai.</p>
<p>Bên cạnh đó, hoạt động này giúp ích rất nhiều cho giáo viên, cán bộ nhà trường, phụ huynh trong việc nâng cao khả năng tự giải quyết các vấn đề có liên quan đến học đường cũng như các mối quan hệ giữa họ với học sinh. Hiện nay, tư vấn học đường còn mở rộng thêm nhiều khía cạnh khác nhau, ví dụ như trau dồi thêm nhiều kỹ năng cho học sinh: phản biện, định hướng tương lai, cải thiện/nâng cao thành tích cá nhân,…</p>
<p>Thực tế thì tư vấn học đường đã có từ rất lâu nhưng khoảng 10 năm trở lại đây mới phổ biến và được quan tâm nhiều tại nước ta. Nó được chia thành 3 phần chính, là: hỗ trợ tâm lý, tư vấn hướng nghiệp và tư vấn học đường căn bản.</p>
<h2>Thực trạng tâm lý học đường hiện nay</h2>
<p>Hiện nay, tâm lý học đường tại nước ta đang là vấn đề vô cùng nhạy cảm và cũng là áp lực rất lớn đối với phía nhà trường, các nhà chức trách cùng hội phụ huynh học sinh.</p>
<p>Kết quả khảo sát sức khỏe định kỳ cho các em học sinh THCS tại Hà Nội cho thấy có khoảng 25,7% trên tổng 1.727 em học sinh gặp phải các vấn đề sức khỏe tâm thần.Đặc biệt, tỷ lệ học sinh nữ gặp vấn đề tâm lý, sức khỏe tinh thần lại chiếm phần cao hơn so với học sinh nam. Ngoài ra, có đến 20,6% các học sinh chỉ mới bước vào lớp một đã phải thường xuyên lo lắng quá mức về kết quả học tập của bản thân, từ đó dẫn đến tình trạng trầm cảm.</p>
<p>Theo một nghiên cứu khác được thực hiện trên khoảng 1.314 các em học sinh (từ 6 đến 16 tuổi) tại khoảng 10 tỉnh thành của nước ta, có khoảng 9,6% các trẻ gặp phải những vấn đề tâm lý như trầm cảm, rối loạn lo âu ở giai đoạn nhẹ, do nhiều yếu tố khác nhau. Cụ thể có khoảng 16,29% các trẻ thường xuyên cảm thấy buồn bã, chán nản về chuyện tình cảm; khoảng 4,1% các trẻ cảm thấy không hài lòng, tự ti về ngoại hình và cơ thể của bản thân; khoảng 2,1% các trẻ có xu hướng sống khép kín, thu mình lại và có khoảng 1,8% các trẻ em nghiện chơi game, các trò chơi điện tử.</p>
<p>Đặc biệt vào năm 2000, các chuyên gia cũng đã thực hiện một cuộc khảo sát lớn với học sinh, sinh viên ở độ tuổi từ 15 đến 18 sinh sống và học tập tại TPHCM. Kết quả nhận thấy rằng:</p>
<ul><li>Hơn 50% số học sinh thấy mình không được thấu hiểu và đồng cảm.</li><li>Hơn 70% rơi vào trạng thái phải đối mặt với khó khăn về một hoặc nhiều vấn đề nào đó.</li><li>Khoảng 85% số học sinh lựa chọn tâm sự, chia sẻ với bạn bè nhiều hơn là với gia đình, phụ huynh.</li><li>Có đến gần 90% các em học sinh lựa chọn cách tự sát vì cảm thấy cha mẹ không hiểu và thông cảm cho mình.</li><li>Có khoảng 75% học sinh cấp 3 mất định hướng và cảm thấy hoang mang khi đứng trước sự lựa chọn chuyên ngành đại học.</li><li>Khoảng 30% các sinh viên năm nhất cảm thấy bế tắc, chán nản vì sai lầm trong lựa chọn ngành học. Tỉ lệ này tăng lên đến hơn 50% khi sinh viên bước vào năm 2 và năm 3.</li><li>Đến hơn 90% các trường hợp trẻ vị thành niên có những hành vi phạm pháp vì mất phương hướng và thiếu sự quan tâm của gia đình.</li></ul>
<h2>Hỗ trợ tâm lý học đường cho học sinh quan trọng như thế nào?</h2>
<p>Tăng cường hỗ trợ tâm lý học đường là giải pháp tốt và hữu hiệu để giải quyết thực trạng trên. Có thể kể đến các thời điểm quan trọng mà học sinh cần được can thiệp, tham gia hỗ trợ tâm lý từ sớm như: lớp 6, lớp 9, lớp 10, lớp 12, năm nhất đại học…</p>
<p>Đặc biệt là với các em trong giai đoạn dậy thì từ 12 đến 18 tuổi, phải đối mặt với áp lực và khó khăn trong học tập lẫn cuộc sống với nhiều thay đổi lớn về suy nghĩ, cảm xúc và cơ thể. Các em bắt đầu hình thành ranh giới riêng cho bản thân, ít giao tiếp với cha mẹ hơn và thích được làm người lớn.</p>
<p>Hoạt động hỗ trợ tâm lý học đường cho học sinh giúp các em vượt qua được những vấn đề nêu trên, hỗ trợ cải thiện tốt các mối quan hệ giữa con cái với cha mẹ, học trò với thầy cô, giữa bạn bè với nhau,…Khi những khó khăn, vướng mắc trong quá trình học tập cũng như cuộc sống sẽ được tháo gỡ, các em sẽ cảm thấy thoải mái, dễ chịu, giảm bớt các căng thẳng, mệt mỏi, áp lực. Từ đó, học tập hiệu quả hơn, cuộc sống cũng được cân bằng và vui vẻ hơn.</p>
<p>Hỗ trợ tâm lý học đường còn là quá trình đồng hành cùng các em định hướng tương lai, xác định công việc mơ ước và phù hợp với bản thân. Nhờ đó, các em tránh được tình trạng thất nghiệp hoặc làm trái ngành học sau khi ra trường hoặc thất bại do mất phương hướng trong cuộc sống.</p>
<p>Và hơn hết, áp dụng tư vấn học đường từ sớm sẽ giúp xử lý được các nguy cơ tiềm ẩn có thể khởi phát ở trẻ em như chán học, bỏ học, bạo lực học đường, các hành vi vi phạm pháp luật, tự hủy hoại bản thân, sa ngã vào các tệ nạn xã hội,…</p>
<p>Do đó, hỗ trợ tâm lý học đường là một hoạt động vô cùng quan trọng đối trong môi trường học tập, đòi hỏi sự tham gia đa chiều, chất lượng từ phía nhà trường, gia đình và các chuyên gia/chuyên gia tâm lý.</p>
<h2>Hệ lụy nguy hiểm khi học sinh không được hỗ trợ tâm lý học đường</h2>
<p>Mặc dù hỗ trợ tâm lý học đường đã xuất hiện từ rất lâu nhưng vẫn chưa nhận được sự quan tâm đúng mức. Hiện tại, tỷ lệ giáo viên và học sinh Việt Nam được tiếp cận tốt với tư vấn học đường là rất thấp, thậm chí là không có, ngay cả ở khu vực thành phố lớn.</p>
<p>Các em vẫn trong độ tuổi vị thành niên, chưa chín chắn trong suy nghĩ và chưa thể tự lập trong cuộc sống. Trong khi đó, cái tôi của mỗi cá nhân lại quá lớn nên dễ dàng hình thành các hành vi sai lầm khi phải thường xuyên đối mặt với nhiều căng thẳng, áp lực đến từ việc học tập, gia đình, xã hội, nhà trường, bạn bè,… Hơn nữa, cuộc sống hiện đại, mạng internet phát triển cũng kéo theo hàng loạt các vấn đề cám dỗ, tệ nạn xã hội, sự mông lung trong định hướng nghề nghiệp, tình yêu tuổi học trò,…</p>
<p>Chính vì vậy, các vấn đề trong tâm lý học đường ngày nay đang có xu hướng gia tăng cả về mức độ và số lượng như: tự sát, bạo lực học đường, giết người, trộm cướp, trấn lột,…. Và không ít các vụ việc mà người gây án là học sinh chưa thể chịu trách nhiệm truy cứu hình sự.</p>
<p>Thiếu sự sẻ chia và phải tự mình giải quyết các vấn đề xảy ra với bản thân, điều này làm gia tăng nguy cơ gây ra các hệ lụy nguy hiểm. Nếu không được đồng hành, hỗ trợ tâm lý học đường đúng cách và kịp thời, các em có thể ngày càng dần trở nên khép kín, tách biệt với xã hội, thậm chí là tự sát vì cho rằng như vậy bản thân sẽ được giải thoát.</p>
<h2>Quy trình tham vấn tâm lý học đường cho học sinh</h2>
<p>Để giúp cho quá trình hỗ trợ tâm lý học đường mang lại hiệu quả tốt thì các chuyên gia thường sẽ phải áp dụng đúng theo quy trình như sau:</p>
<ul><li><strong>Thiết lập mối quan hệ:</strong> Trước tiên, các nhà hỗ trợ tâm lý học đường sẽ tiến hành trao đổi và phỏng vấn với phụ huynh và giáo viên trực tiếp của trẻ để cùng nhau xây dựng và đặt mục tiêu giúp cho trẻ giải quyết tốt các vấn đề khó khăn đang gặp phải.</li><li><strong>Làm rõ vấn đề:</strong> Sau đó, nhà tâm lý sẽ trực tiếp gặp gỡ và trò chuyện với các em về những vướng mắc tâm lý. Các chuyên gia sẽ xác định vấn đề cần phải khắc phục và giải quyết bằng các phương pháp khám chuyên khoa tâm lý cho các em học sinh. Nhờ đó họ có thể giúp cho trẻ tự đặt ra mục tiêu của bản thân và thực hiện tốt các phương pháp cải thiện sức khỏe tâm lý.</li><li><strong>Phân tích vấn đề:</strong> Sau khi đã xác định được cụ thể những vấn đề tâm lý mà các em học sinh đang gặp phải thì chuyên gia tâm lý sẽ bắt đầu phân tích dựa trên các thông tin, dữ liệu thu thập được. Đặc biệt là sẽ tập trung vào việc phân tích, tìm hiểu các yếu tố xung quanh gây tác động đến trẻ, cụ thể như người thân, môi trường học tập, bạn bè,….để có thể tìm ra biện pháp giải quyết tốt.</li><li><strong>Đề xuất các giải pháp:</strong> Thông thường, các biện pháp, phương hướng giải quyết vấn đề sẽ được đề xuất từ trước. Sau khi đã hiểu rõ về các vấn đề tâm lý mà trẻ đang gặp phải thì nhà tâm lý học đường sẽ gặp gỡ và đề xuất với người thân và đối tượng về giải pháp giải quyết.</li><li><strong>Thảo luận và lựa chọn giải pháp:</strong> Các chuyên gia tâm lý học đường sẽ cùng với người thân, phụ huynh của trẻ để lựa chọn và quyết định cụ thể phương pháp phù hợp.</li><li><strong>Thực hiện chiến lược</strong>: Các chuyên gia tâm lý được sẽ tiến hành áp dụng các phương pháp điều trị tâm lý cho các em học sinh trong một khoảng thời gian cố định.</li><li><strong>Đánh giá hiệu quả can thiệp và theo dõi:</strong> Sau một khoảng thời gian thực hiện tư vấn và hỗ trợ tâm lý cho học sinh, các nhà tâm lý sẽ bắt đầu đánh giá về hiệu quả của các phương pháp, đồng thời xem xét về việc có cần áp dụng thêm các biện pháp khác hay không.</li></ul>
<h2>Giải pháp thực hiện hỗ trợ tâm lý học đường cho học sinh</h2>
<p>Hỗ trợ tâm lý học đường cho học sinh đóng một vai trò rất quan trọng đối với quá trình học tập và định hướng tương lai cho trẻ nhỏ. Để công tác tư vấn được hiệu quả và thành công thì các nhà tâm lý cũng cần phải biết được các giải pháp phù hợp cho từng độ tuổi và từng giai đoạn khác nhau:</p>
<h3>1. Đối với cấp tiểu học</h3>
<p>Thông thường, hỗ trợ tâm lý học đường bậc tiểu học sẽ cung cấp theo nhiều hình thức như tư vấn cá nhân, tư vấn gia đình, tư vấn nhóm học sinh để lắng nghe và tìm hiểu về các thông tin học tập cho đến những định hướng trong tương lai. Trong quá trình hỗ trợ tâm lý cấp tiểu học, chuyên gia tâm lý cần chú ý quan sát trẻ trong lúc học tập, vui chơi, trao đổi với giáo viên, phụ huynh để cùng nhau đánh giá đúng nhất về tình trạng sức khỏe, các khó khăn mà trẻ đang gặp phải cũng như các mong muốn, nhu cầu riêng của mỗi cá nhân. Đồng thời, nhà tâm lý cũng cần kết hợp cùng với nhà trường để đảm bảo và nâng cao khả năng học tập cho các em, lựa chọn đúng chương trình và nhu cầu phát triển của trẻ.</p>
<h3>2. Đối với cấp trung học cơ sở</h3>
<p>Đối với cấp trung học cơ sở thì công tác hỗ trợ tâm lý học đường cũng sẽ tương tự như cấp tiểu học. Tuy nhiên cần phải chú trọng đẩy mạnh hơn về việc khai thác các nguồn lực tự thân của mỗi trẻ, gia đình cùng xã hội. Nhờ đó có thể giúp cho các em học sinh học tập tốt hơn, giải quyết được các vấn đề về định hướng tương lai, lựa chọn nghề nghiệp, chọn trường phổ thông trung học,…</p>
<p>Dựa vào những thông tin giáo dục mới hiện nay thì nhà tâm lý cần phải tập trung vào việc tư vấn học tập, cân bằng cảm xúc tuổi dậy thì, tình cảm tuổi học trò và định hướng hướng nghiệp cho các em học sinh trung học cơ sở. Nhờ đó mà các em có thể xác định được loại hình học tập của mình trong tương lai và biết rõ về nghề nghiệp liên quan. Bên cạnh đó, cần phải hỗ trợ học sinh về một số vấn đề liên quan đến suy nghĩ, cảm xúc, hành vi, nhân cách để các em có sự phát triển lành mạnh trong tương lai.</p>
<h3>3. Đối với cấp trung học phổ thông</h3>
<p>Đối với cấp học này, các nhà tâm lý cần phải đi sâu hơn trong cách hướng dẫn cách học tập và giúp trẻ gia tăng hứng thú đến trường. Nhà trường cần phải có giải pháp để giúp cho các em học sinh cơ hội phát triển hướng nghiên cứu thực hành hoặc lý thuyết, có cái nhìn và đánh giá khách quan về những tiềm năng các những nguồn lực có sẵn. Đặc biệt cần phải chú ý nhiều đến hướng phát triển nghề nghiệp, định hướng về nhân cách và phát triển trí tuệ.</p>
<p>Qua đây bạn cũng thấy được tầm quan trọng của công tác hỗ trợ tâm lý học đường dành cho học sinh, sinh viên. Bên cạnh đó, các bậc phụ huynh cũng nên quan tâm, dành nhiều thời gian cho con cái hơn. Nếu nhận thấy các biểu hiện tâm lý bất thường bạn nên cho trẻ tham gia các buổi hỗ trợ tâm lý học đường hoặc được thăm khám tại các cơ sở, trung tâm, các chuyên gia về tâm lý, hỗ trợ gỡ rối vướng mắc tâm lý.</p>
<h3>4. Một số thời điểm tâm lý học sinh nhạy cảm cần được hỗ trợ</h3>
<p>Bên cạnh các giai đoạn được phân chia theo độ tuổi, học sinh còn gặp khó khăn tâm lý ở một số thời điểm nhạy cảm như năm học đầu tiên của cấp 1, cấp 2, cấp 3; năm học cuối cấp.</p>
<p>Ở giai đoạn mới chuyển cấp, dù là học sinh tiểu học hay học sinh cấp 3 thì hầu như các học sinh đều gặp những bỡ ngỡ, khó khăn nhất định do thay đổi môi trường học tập, cách học tập, cách giao tiếp với bạn bè, thầy cô.</p>
<p>Ở giai đoạn cuối cấp, học sinh thường có áp lực học tập lớn hơn, đặc biệt là lớp 9 và lớp 12. Các bạn học sinh lớp 9 phải đối mặt với kỳ thi chuyển cấp lên lớp 10, đặc biệt là học sinh ở thành phố có áp lực rất lớn. Các bạn học sinh lớp 12 phải đương đầu với kỳ thi tốt nghiệp và kỳ thi vào đại học, cao đẳng, phải xác định được nghề nghiệp, mục tiêu của mình trong tương lai.</p>
<h2>Trung tâm Tâm lý và Phát triển Con người NHC Việt Nam – Đơn vị hỗ trợ tâm lý học đường uy tín tại Hà Nội và Hồ Chí Minh</h2>
<p>Trung tâm Tâm lý và Phát triển Con người NHC Việt Nam là đơn vị uy tín trong lĩnh vực tâm lý, giúp khách hàng giải tỏa những áp lực, stress, chữa lành những nỗi đau, tổn thương tinh thần của con người để có sự tự chủ trong cuộc sống của chính mình, có sức khỏe tốt hơn và sống hạnh phúc hơn.</p>
<p>Trung tâm Tâm lý và Phát triển Con người NHC Việt Nam sở hữu đội ngũ chuyên gia tâm lý được đào tạo bài bởi các Hiệp hội quốc tế: NLP Hoa Kỳ, Hypnotherapy Hoa Kỳ, Time Line Therapy, luôn chuyên nghiệp và tận tâm, lắng nghe, thấu hiểu khách hàng. Đặc biệt, Trung tâm có nhiều chuyên gia có kinh nghiệm đồng hành với trẻ dậy thì, trẻ vị thành niên gặp các vấn đề trầm cảm, rối loạn lo âu, rối loạn ám ảnh cưỡng chế… và nhận được nhiều <a href="https://tamlynhc.vn/clb-khach-hang">phản hồi</a> tốt từ phía học sinh và phụ huynh.</p>
<p>Với các bạn trẻ chưa trưởng thành, Trung tâm Tâm lý và Phát triển Con người NHC Việt Nam có các chương trình đặc biệt để giúp cha mẹ đồng hành cùng con đúng cách. Không phải là những lời động viên chung chung “cố lên con, mẹ tin con làm được” mà là thấu hiểu con đúng cách để con dám chia sẻ với ba mẹ những khúc mắc, vấn đề của con ở trường, biết cách quan tâm, khuyến khích để con đạt được kết quả tốt hơn, hiểu tâm sinh lý ở tuổi của con để có giải pháp đồng hành phù hợp, xây dựng mối quan hệ giữa ba mẹ và con cái trở nên tốt đẹp và bền vững hơn.</p>
<h3>Các chương trình hỗ trợ tâm lý học đường chuyên nghiệp của Tâm lý NHC</h3>
<p>Hiện nay, NHC Việt Nam thiết kế nhiều chương trình hỗ trợ tâm lý học đường khác nhau dành cho cá nhân hoặc nhóm.</p>
<h3>Các chương trình hỗ trợ tâm lý học đường dành cho cá nhân</h3>
<h4>Chương trình tham vấn tâm lý học đường đánh giá sức khỏe tinh thần của học sinh</h4>
<p>Với hoạt động tham vấn tâm lý học đường, học sinh sẽ được trò chuyện 1:1 với chuyên gia tâm lý để chia sẻ, giãi bày các vấn đề mà mình đang gặp phải trong học tập cũng như sinh hoạt hàng ngày một cách riêng tư, an toàn, bảo mật mà không sợ bị phán xét.</p>
<p>Thông qua câu chuyện của học sinh và những câu hỏi khai vấn, chuyên gia tâm lý sẽ có đánh giá tổng quát về sức khỏe tinh thần, tâm lý của học sinh để trao đổi lại với cha mẹ về các vấn đề tâm lý mà học sinh đang gặp khó khăn, đề xuất phương án phù hợp với cha mẹ.</p>
<h4>Chương trình hỗ trợ tâm lý chuyên sâu, đồng hành lâu dài cùng học sinh và gia đình</h4>
<p>Chương trình hỗ trợ tâm lý chuyên sâu được thiết kế để hồi phục sức khỏe tinh thần cho học sinh gặp vấn đề trầm cảm học đường, rối loạn lo âu, căng thẳng – stress hay các rối loạn tâm lý khác.</p>
<p>Lộ trình hỗ trợ tâm lý được thiết kế riêng để phù hợp với tình trạng sức khỏe tinh thần, vấn đề tâm lý mà học sinh đó đang gặp phải. Bên cạnh việc hồi phục sức khỏe tinh thần, chữa lành những tổn thương tâm lý cho học sinh, chương trình hỗ trợ tâm lý chuyên sâu còn giúp các bạn học sinh phát triển bản thân, khám phá khả năng của chính mình và sống có mục tiêu rõ ràng.</p>
<h3>Các chương trình hỗ trợ tâm lý học đường theo nhóm, tập thể</h3>
<p>Bên cạnh các chương trình đồng hành 1:1 giữa chuyên gia và khách hàng, NHC Việt Nam còn có các chương trình hỗ trợ tâm lý học đường theo nhóm tại các trường tiểu học, THCS, THPT để giải quyết một vấn đề điển hình của học sinh, nhà trường, thầy cô, phụ huynh.</p>
<p><strong>Một số chương trình điển hình có thể kể đến như:</strong></p>
<p><strong>Chương trình giải tỏa tâm lý thi cử dành cho học sinh từ lớp 9 chuẩn bị thi lên lớp 10</strong> được thiết kế nhằm mục tiêu chính là giúp học sinh nhận biết sớm và học cách giải tỏa căng thẳng, áp lực học tập một cách lành mạnh; Biết cách chia sẻ, kết nối để tìm sự hỗ trợ từ gia đình, thầy cô, bạn bè; Học hiệu quả nhưng không áp lực.</p>
<p><strong>Chương trình hỗ trợ tâm lý, định hướng nghề nghiệp cho học sinh cấp 3</strong>, đặc biệt là học sinh lớp 12. Chương trình được thiết kế để giúp học sinh cấp 3 thấu hiểu bản thân để tìm được nghề nghiệp phù hợp với bản thân mình; Biết cách đặt mục tiêu và chinh phục mục tiêu đó.</p>
<p><strong>Chương trình hỗ trợ tâm lý học đường tuổi dậy thì dành cho học sinh cấp 2</strong>. Chương trình chủ yếu hỗ trợ học sinh thấu hiểu bản thân và cân bằng cảm xúc ở tuổi dậy thì; Xây dựng mối quan hệ lành mạnh với gia đình, bạn bè, thầy cô.</p>
<p><strong>Chương trình hỗ trợ tâm lý cho học sinh có sự đồng hành của cha mẹ</strong> nhằm giúp cha mẹ, học sinh kết nối và thấu hiểu nhau hơn, từ đó dễ dàng chia sẻ với cha mẹ những vấn đề khó khăn của bản thân.</p>
<p><strong>Chương trình hỗ trợ tâm lý học đường theo yêu cầu khác dành cho học sinh, thầy cô giáo.</strong></p>
<p>Để kết nối với Trung tâm Tâm lý và Phát triển Con người NHC Việt Nam, mời các bạn liên hệ qua <span style="color: #ff0000;"><strong>Hotline: 096 589 8008</strong></span><strong>.</strong></p>
<blockquote><p><strong>Có thể bạn quan tâm: </strong></p>
<p><span style="color: #ff0000;"> </span><a href="https://tamlynhc.vn/tam-ly-nhc-viet-nam-ho-tro-tam-ly-hoc-duong-tai-huyen-phuc-tho-30041.html">Tâm Lý NHC Việt Nam hỗ trợ tâm lý học đường tại huyện Phúc Thọ</a><br/><span style="color: #ff0000;"> <a href="https://trilieutamly.com/ho-tro-tam-ly-hoc-duong-cho-gan-300-hoc-sinh-tai-truong-thcs-cau-giay-29705.html"><span style="color: #000000;">Hỗ trợ tâm lý học đường cho gần 300 học sinh tại trường THCS Cầu Giấy</span></a></span><br/><span style="color: #ff0000;"> </span><a href="https://tamlynhc.vn/camp-hanh-trinh-chien-binh-2-ngay-1-dem-tai-ho-may-38918.html">[HTKNCTN HCM] CAMP HÀNH TRÌNH CHIẾN BINH 2 NGÀY 1 ĐÊM TẠI HỒ MÂY</a></p>
</blockquote>
            '
            , 'process' => 'Tạo không gian an toàn; lắng nghe góc nhìn của trẻ; phối hợp với phụ huynh khi phù hợp và thống nhất bước tiếp theo.'],
            
            ['key' => 'child', 'category' => 'teen', 'name' => 'Đánh giá & Hỗ trợ Tâm lý Trẻ em', 'duration' => 45, 'price' => 600000, 'image' => 'https://www.hsbc.com.sg/content/dam/hsbc/sg/images/16-9/doctor-mother-girl.jpg/_jcr_content/renditions/cq5dam.web.1280.1280.jpeg', 'audience' => '
            <p><span style="font-size: 20pt; color: #65ad45;"><b>Đánh giá &amp; Hỗ trợ Tâm lý Trẻ em – Hiểu con để đồng hành đúng cách</b></span></p>
<ul>
<li><span style="color: #000000;">Cách tiếp cận <strong>nhẹ nhàng, phù hợp lứa tuổi</strong>, giúp trẻ cảm thấy an toàn khi tham gia.</span></li>
<li><span style="color: #000000;">Có <strong>sự phối hợp chặt chẽ với cha mẹ và người chăm sóc</strong> trong suốt quá trình.</span></li>
<li><span style="color: #000000;">Mỗi buổi kéo dài <strong>45 phút</strong>, kết thúc bằng kế hoạch hỗ trợ <strong>dễ thực hiện tại nhà và ở trường</strong>.</span></li>
</ul>
<h2>Đánh giá &amp; hỗ trợ tâm lý trẻ em là gì?</h2>
<p style="text-align: justify;">Trẻ nhỏ thường chưa đủ ngôn ngữ để nói "con đang lo", "con thấy buồn" hay "con sợ". Thay vào đó, cảm xúc thể hiện qua hành vi, giấc ngủ, ăn uống, học tập hoặc các phản ứng thân thể. Dịch vụ giúp cha mẹ hiểu điều con đang trải qua và tìm cách hỗ trợ phù hợp với độ tuổi và tính cách của trẻ.</p>
<p style="text-align: justify;">Đây là buổi tìm hiểu và hỗ trợ tâm lý, không nhằm "gắn nhãn" cho trẻ. Với những dấu hiệu cần đánh giá y khoa chuyên sâu, chuyên gia sẽ tư vấn để gia đình tìm đến cơ sở y tế phù hợp.</p>
<blockquote>
<p style="text-align: center;"><span style="color: #008000; font-size: 14pt;"><strong>Lắng nghe trẻ – Đồng hành cùng cha mẹ – Hỗ trợ bằng những bước nhỏ, vừa sức</strong></span></p>
</blockquote>
<h2>Dịch vụ phù hợp với ai?</h2>
<p style="text-align: justify;">Trẻ có thay đổi về cảm xúc, hành vi, giấc ngủ, học tập hoặc khó thích nghi với môi trường mới. Một số dấu hiệu cha mẹ có thể để ý:</p>
<ul>
<li>Trẻ hay cáu gắt, khóc nhiều, dễ bùng nổ hoặc thu mình, ít nói hơn trước.</li>
<li>Trẻ lo lắng quá mức, sợ đi học, sợ xa cha mẹ hoặc hay kêu đau bụng, đau đầu mà không rõ nguyên nhân.</li>
<li>Giấc ngủ thay đổi: khó vào giấc, hay thức giấc, gặp ác mộng, sợ ngủ một mình.</li>
<li>Kết quả học tập sa sút, khó tập trung, mất hứng thú với việc từng yêu thích.</li>
<li>Khó thích nghi khi chuyển trường, vào lớp mới, có em, cha mẹ thay đổi công việc hoặc gia đình có biến động.</li>
<li>Khó kết bạn, hay xung đột với bạn bè hoặc từng gặp tình huống bị trêu chọc, bị bỏ rơi.</li>
</ul>
<h2>Quy trình một lộ trình đánh giá và hỗ trợ</h2>
<ul>
<li><strong>Bước 1 – Trao đổi với người chăm sóc</strong>: lắng nghe cha mẹ hoặc người nuôi dưỡng về những thay đổi của trẻ, hoàn cảnh gia đình, quá trình phát triển và điều bạn lo lắng.</li>
<li><strong>Bước 2 – Quan sát và đánh giá phù hợp lứa tuổi</strong>: tiếp xúc với trẻ qua trò chuyện, trò chơi, vẽ hoặc hoạt động tương tác, tùy độ tuổi và sự thoải mái của trẻ.</li>
<li><strong>Bước 3 – Phản hồi bằng kế hoạch hỗ trợ dễ thực hiện</strong>: chuyên gia chia sẻ nhận định ban đầu và cùng cha mẹ xây dựng những việc làm cụ thể, vừa sức trong sinh hoạt hằng ngày.</li>
<li><strong>Bước 4 – Theo dõi và điều chỉnh</strong>: nhìn lại sự thay đổi của trẻ, điều chỉnh kế hoạch hoặc đề xuất các buổi hỗ trợ tiếp theo nếu cần.</li>
</ul>
<p style="text-align: justify;">Với trẻ nhỏ, cha mẹ hoặc người chăm sóc luôn là một phần quan trọng của quá trình. Số buổi phụ thuộc vào độ tuổi và nhu cầu của từng trẻ.</p>
<h2>Lợi ích bạn có thể kỳ vọng</h2>
<ul>
<li><strong>Hiểu rõ hơn</strong> điều con đang cảm thấy và nhu cầu đằng sau hành vi.</li>
<li>Có <strong>cách phản hồi</strong> phù hợp khi con khóc, giận dữ hoặc lo sợ.</li>
<li>Giúp trẻ <strong>gọi tên cảm xúc</strong> và dần học các cách ứng phó lành mạnh.</li>
<li>Nhận được <strong>kế hoạch rõ ràng</strong> để phối hợp giữa gia đình và nhà trường.</li>
</ul>
<h2>Những điều cần lưu ý</h2>
<ul>
<li>Trẻ cần có <strong>người thân đi cùng</strong> trong các buổi, và thông tin về trẻ được giữ bảo mật.</li>
<li>Mỗi trẻ phát triển và phản ứng theo tốc độ riêng, nên cần thời gian để thấy thay đổi.</li>
<li>Nếu trẻ có triệu chứng thể chất kéo dài, chậm phát triển rõ rệt hoặc có biểu hiện cần đánh giá y khoa, hãy cho trẻ đi khám bác sĩ nhi hoặc chuyên khoa phù hợp.</li>
<li>Nếu trẻ có lời nói hoặc hành vi tự làm hại bản thân, hãy ở bên con và liên hệ ngay cơ sở y tế hoặc dịch vụ hỗ trợ khẩn cấp gần nhất.</li>
</ul>
<h2>Câu hỏi thường gặp</h2>
<p><strong>Trẻ không chịu nói chuyện thì sao?</strong><br>Điều này rất bình thường. Chuyên gia dùng trò chơi, vẽ và hoạt động tương tác để trẻ thoải mái, và không ép trẻ phải kể.</p>
<p><strong>Cha mẹ có được tham gia không?</strong><br>Có. Cha mẹ trao đổi với chuyên gia và nhận phản hồi cùng kế hoạch hỗ trợ cụ thể, vì sự đồng hành hằng ngày của gia đình là yếu tố rất quan trọng.</p>
<p><strong>Buổi này có chẩn đoán bệnh cho con không?</strong><br>Buổi đánh giá nhằm hiểu và hỗ trợ trẻ về mặt tâm lý, không thay thế chẩn đoán y khoa. Nếu cần, chuyên gia sẽ tư vấn gia đình đến cơ sở y tế phù hợp.</p>
<p style="text-align: center;"><span style="font-size: 14pt; color: #008000;"><b>Hãy liên hệ để được tham vấn và xây dựng kế hoạch hỗ trợ phù hợp với con bạn</b></span></p>
            ', 'process' => 'Trao đổi với người chăm sóc; quan sát và đánh giá phù hợp lứa tuổi; phản hồi bằng kế hoạch hỗ trợ dễ thực hiện.'],
            
            ['key' => 'corporate', 'category' => 'corporate', 'name' => 'Đánh giá Sức khỏe Tinh thần Doanh nghiệp', 'duration' => 60, 'price' => 1200000, 'image' => 'https://insider.tophr.vn/wp-content/uploads/2023/03/cham-soc-suc-khoe-tinh-than-nhan-vien-tophr-insight-03.jpg', 'audience' => '
            <p><span style="font-size: 20pt; color: #65ad45;"><b>Đánh giá Sức khỏe Tinh thần Doanh nghiệp – Hiểu đúng nhu cầu để hỗ trợ đúng người, đúng lúc</b></span></p>
<ul>
<li><span style="color: #000000;"><strong>Khảo sát bảo mật</strong>: kết quả được tổng hợp, không công bố thông tin định danh của từng nhân viên.</span></li>
<li><span style="color: #000000;">Cung cấp <strong>bức tranh tổng thể</strong> về rủi ro tâm lý và nhu cầu hỗ trợ trong tổ chức.</span></li>
<li><span style="color: #000000;">Đề xuất chương trình hỗ trợ <strong>phù hợp văn hóa và nguồn lực</strong> của doanh nghiệp.</span></li>
</ul>
<h2>Đánh giá sức khỏe tinh thần doanh nghiệp là gì?</h2>
<p style="text-align: justify;">Áp lực công việc, khối lượng việc lớn, thay đổi tổ chức hay xung đột trong đội nhóm đều có thể ảnh hưởng đến sức khỏe tinh thần của nhân viên, từ đó tác động đến động lực, sự gắn kết và hiệu suất chung. Việc đánh giá giúp doanh nghiệp nhìn rõ thực trạng thay vì phỏng đoán.</p>
<p style="text-align: justify;">Dịch vụ sử dụng khảo sát ẩn danh và phân tích tổng hợp để xác định các yếu tố rủi ro, nhóm cần quan tâm và những hình thức hỗ trợ phù hợp, trên cơ sở tôn trọng quyền riêng tư của nhân viên.</p>
<blockquote>
<p style="text-align: center;"><span style="color: #008000; font-size: 14pt;"><strong>Hiểu thực trạng – Tôn trọng riêng tư – Hành động có trọng tâm</strong></span></p>
</blockquote>
<h2>Dịch vụ phù hợp với ai?</h2>
<p style="text-align: justify;">Doanh nghiệp muốn khảo sát rủi ro tâm lý, sức khỏe tinh thần và nhu cầu hỗ trợ của nhân viên. Một số trường hợp thường gặp:</p>
<ul>
<li>Doanh nghiệp muốn xây dựng hoặc cải thiện chính sách chăm sóc sức khỏe tinh thần cho nhân viên.</li>
<li>Tổ chức đang trải qua thay đổi như tái cấu trúc, mở rộng, sáp nhập hoặc giai đoạn tăng tốc.</li>
<li>Bộ phận nhân sự nhận thấy dấu hiệu căng thẳng, kiệt sức, nghỉ việc hoặc vắng mặt tăng lên.</li>
<li>Đội ngũ quản lý cần hiểu rõ hơn nhu cầu của nhân viên để đồng hành và hỗ trợ đúng cách.</li>
<li>Doanh nghiệp muốn có cơ sở dữ liệu trước khi đầu tư vào các chương trình phúc lợi tinh thần.</li>
</ul>
<h2>Quy trình một lộ trình đánh giá</h2>
<ul>
<li><strong>Bước 1 – Xác định mục tiêu</strong>: trao đổi với đại diện doanh nghiệp về bối cảnh, mối quan tâm, phạm vi và nhóm nhân sự tham gia.</li>
<li><strong>Bước 2 – Khảo sát bảo mật</strong>: nhân viên tham gia trả lời khảo sát theo cách bảo vệ quyền riêng tư, thông tin cá nhân không được chia sẻ cho doanh nghiệp.</li>
<li><strong>Bước 3 – Phân tích tổng hợp</strong>: tổng hợp kết quả theo nhóm, nhận diện các yếu tố rủi ro và yếu tố bảo vệ trong tổ chức.</li>
<li><strong>Bước 4 – Báo cáo và đề xuất</strong>: trình bày kết quả và đề xuất chương trình hỗ trợ phù hợp văn hóa tổ chức, như tham vấn, đào tạo kỹ năng, hướng dẫn cho quản lý hoặc điều chỉnh môi trường làm việc.</li>
</ul>
<p style="text-align: justify;">Thời gian triển khai và quy mô khảo sát tùy thuộc vào số lượng nhân sự và mục tiêu của từng doanh nghiệp. Mỗi buổi trao đổi với đại diện doanh nghiệp kéo dài khoảng <strong>60 phút</strong>.</p>
<h2>Lợi ích bạn có thể kỳ vọng</h2>
<ul>
<li>Có <strong>bức tranh rõ ràng</strong> về mức độ căng thẳng và nhu cầu hỗ trợ trong tổ chức.</li>
<li>Xác định <strong>nhóm và yếu tố cần ưu tiên</strong> để dùng nguồn lực hiệu quả.</li>
<li>Xây dựng chương trình hỗ trợ <strong>dựa trên dữ liệu</strong> thay vì cảm tính.</li>
<li>Thể hiện sự quan tâm của doanh nghiệp, góp phần <strong>tăng niềm tin và sự gắn kết</strong> của nhân viên.</li>
</ul>
<h2>Những điều cần lưu ý</h2>
<ul>
<li>Việc tham gia khảo sát của nhân viên nên mang tính <strong>tự nguyện</strong> và được giải thích rõ về cách bảo mật thông tin.</li>
<li>Kết quả khảo sát chỉ nhằm mục đích hỗ trợ và cải thiện môi trường làm việc, <strong>không dùng để đánh giá hay xử lý cá nhân</strong>.</li>
<li>Đây là đánh giá ở cấp độ tổ chức, không thay thế chẩn đoán hay điều trị cá nhân. Nhân viên cần hỗ trợ riêng có thể được giới thiệu tham vấn hoặc cơ sở y tế phù hợp.</li>
<li>Hiệu quả lâu dài phụ thuộc vào việc doanh nghiệp thực hiện các đề xuất sau khảo sát.</li>
</ul>
<h2>Câu hỏi thường gặp</h2>
<p><strong>Doanh nghiệp có biết ai trả lời gì không?</strong><br>Không. Kết quả được báo cáo ở dạng tổng hợp, và thông tin định danh của từng nhân viên không được cung cấp cho doanh nghiệp.</p>
<p><strong>Doanh nghiệp nhỏ có làm được không?</strong><br>Có. Khảo sát và đề xuất được điều chỉnh theo quy mô, ngân sách và đặc thù của từng tổ chức.</p>
<p><strong>Sau khi có kết quả thì làm gì tiếp?</strong><br>Chuyên gia đề xuất các hướng hỗ trợ cụ thể, và doanh nghiệp có thể chọn triển khai từng phần theo mức độ ưu tiên.</p>
<p style="text-align: center;"><span style="font-size: 14pt; color: #008000;"><b>Hãy liên hệ để được tư vấn và xây dựng lộ trình đánh giá phù hợp với doanh nghiệp của bạn</b></span></p>
            ', 'process' => 'Xác định mục tiêu; khảo sát bảo mật; phân tích tổng hợp và đề xuất chương trình hỗ trợ phù hợp văn hóa tổ chức.'],
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
}
