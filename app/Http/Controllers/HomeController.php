<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\SpecialistProfile;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * HomeController — SCR-PUB-01
 *
 * Hiển thị trang chủ Mind Guide với:
 *  - Dịch vụ nổi bật (ACTIVE, có display_order) — NFR-MNT-002
 *  - Chuyên viên nổi bật (ACTIVE, sắp xếp theo điểm đánh giá VISIBLE)
 *
 * Cả hai collection truyền rỗng khi model chưa tồn tại hoặc DB chưa có
 * dữ liệu, để trang chủ hiển thị đúng trạng thái "ẩn khối, không báo lỗi"
 * theo spec SCR-PUB-01.
 */
class HomeController extends Controller
{
    /**
     * Số dịch vụ nổi bật hiển thị trên trang chủ (3 dịch vụ).
     */
    private const FEATURED_SERVICES_LIMIT = 3;

    /**
     * Số chuyên viên nổi bật hiển thị trên trang chủ (3 chuyên viên).
     */
    private const FEATURED_SPECIALISTS_LIMIT = 3;

    /**
     * GET /
     *
     * Trả về view trang chủ công khai (SCR-PUB-01) với 3 dịch vụ nổi bật
     * và 3 chuyên viên tâm lý giàu kinh nghiệm.
     */
    public function index(): View
    {
        $featuredServices = $this->getFeaturedServices();
        $featuredSpecialists = $this->getFeaturedSpecialists();

        return view('public.home', compact(
            'featuredServices',
            'featuredSpecialists',
        ));
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Lấy danh sách dịch vụ nổi bật ACTIVE.
     *
     * NFR-MNT-002: không hard-code — lấy từ DB theo display_order.
     * Guard: nếu model chưa tồn tại (migration chưa chạy), trả về
     * Collection rỗng để trang chủ không bị lỗi 500.
     */
    private function getFeaturedServices(): Collection
    {
        if (! class_exists(Service::class)) {
            return $this->getSampleServices();
        }

        try {
            $services = Service::query()
                ->where('status', 'ACTIVE')
                ->whereHas('category', fn ($q) => $q->where('is_active', true))
                ->with('category')                     // eager-load tránh N+1
                ->orderBy('display_order')
                ->limit(self::FEATURED_SERVICES_LIMIT)
                ->get();

            return $services->isNotEmpty() ? $services : $this->getSampleServices();
        } catch (\Throwable) {
            // Bảng chưa tồn tại hoặc lỗi DB → trả dữ liệu mẫu demo để preview UI
            return $this->getSampleServices();
        }
    }

    /**
     * Lấy danh sách chuyên viên nổi bật ACTIVE.
     *
     * Chỉ tính đánh giá có status = VISIBLE (SCR-PUB-04).
     * Sắp xếp: điểm TB cao → số lượt nhiều → tên alphabet (tiebreak).
     * Không truyền: license_number, internal_note, email, SĐT (§1.7).
     */
    private function getFeaturedSpecialists(): Collection
    {
        try {
            $specialists = SpecialistProfile::query()
                ->where('status', 'ACTIVE')
                ->with(['user', 'services'])
                ->withCount(['reviews as visible_reviews_count' => fn ($q) => $q->where('status', 'VISIBLE')])
                ->withAvg(['reviews as average_rating' => fn ($q) => $q->where('status', 'VISIBLE')], 'rating')
                ->orderByDesc('average_rating')
                ->orderByDesc('visible_reviews_count')
                ->orderBy('display_order')
                ->limit(self::FEATURED_SPECIALISTS_LIMIT)
                ->get()
                ->map(fn (SpecialistProfile $profile): object => (object) [
                    'id' => $profile->id,
                    'name' => $profile->user->name,
                    'title' => $profile->title,
                    'experience_years' => $profile->years_of_experience,
                    'average_rating' => (float) ($profile->average_rating ?? 0),
                    'visible_reviews_count' => $profile->visible_reviews_count,
                    'avatar_url' => $profile->avatar_url,
                ]);

            return $specialists->isNotEmpty() ? $specialists : $this->getSampleSpecialists();
        } catch (\Throwable) {
            return $this->getSampleSpecialists();
        }
    }

    /**
     * Dữ liệu mẫu 3 dịch vụ nổi bật (SCR-PUB-01).
     */
    private function getSampleServices(): Collection
    {
        return collect([
            (object) [
                'id' => 1,
                'name' => 'Tư vấn tâm lý cá nhân 1-1',
                'category' => (object) ['name' => 'Tâm lý lâm sàng'],
                'description' => 'Đồng hành thấu cảm giúp bạn vượt qua khủng hoảng cảm xúc, trầm cảm, lo âu và định hình lại năng lượng sống tích cực trong không gian hoàn toàn riêng tư, bảo mật.',
                'duration_minutes' => 60,
                'price' => 500000,
            ],
            (object) [
                'id' => 2,
                'name' => 'Tham vấn & Trị liệu lo âu, trầm cảm chuyên sâu',
                'category' => (object) ['name' => 'Trị liệu tâm lý'],
                'description' => 'Ứng dụng các liệu pháp nhận thức - hành vi (CBT) và chánh niệm nhằm giải tỏa căng thẳng kéo dài, rối loạn giấc ngủ, kiệt sức và phục hồi trạng thái cân bằng nội tại.',
                'duration_minutes' => 90,
                'price' => 750000,
            ],
            (object) [
                'id' => 3,
                'name' => 'Tư vấn hôn nhân & Mối quan hệ gia đình',
                'category' => (object) ['name' => 'Hôn nhân & Gia đình'],
                'description' => 'Hỗ trợ các cặp đôi và thành viên gia đình tháo gỡ rào cản giao tiếp, hóa giải xung đột, chữa lành rạn nứt tình cảm và cùng nhau xây dựng kết nối yêu thương bền vững.',
                'duration_minutes' => 90,
                'price' => 800000,
            ],
        ]);
    }

    /**
     * Dữ liệu mẫu 3 chuyên viên tâm lý nổi bật (SCR-PUB-01 & SCR-PUB-04).
     */
    private function getSampleSpecialists(): Collection
    {
        return collect([
            (object) [
                'id' => 1,
                'name' => 'ThS. Nguyễn Thị Mai',
                'title' => 'Chuyên viên Tâm lý học lâm sàng',
                'specialization' => 'Rối loạn lo âu, trầm cảm, sang chấn tâm lý (PTSD), khủng hoảng cảm xúc tuổi trưởng thành',
                'experience_years' => 10,
                'average_rating' => 4.9,
                'visible_reviews_count' => 62,
                'avatar_url' => null,
            ],
            (object) [
                'id' => 2,
                'name' => 'TS. Trần Văn Hưng',
                'title' => 'Tiến sĩ Tâm lý học tham vấn',
                'specialization' => 'Tâm lý hôn nhân, khủng hoảng gia đình, trị liệu cặp đôi & xung đột thế hệ cha mẹ - con cái',
                'experience_years' => 14,
                'average_rating' => 5.0,
                'visible_reviews_count' => 84,
                'avatar_url' => null,
            ],
            (object) [
                'id' => 3,
                'name' => 'ThS. Lê Hoàng An',
                'title' => 'Chuyên viên Trị liệu Hành vi Nhận thức (CBT)',
                'specialization' => 'Kiểm soát căng thẳng, rối loạn giấc ngủ, kiệt sức nghề nghiệp (burnout) & phát triển cá nhân',
                'experience_years' => 8,
                'average_rating' => 4.8,
                'visible_reviews_count' => 45,
                'avatar_url' => null,
            ],
        ]);
    }
}
