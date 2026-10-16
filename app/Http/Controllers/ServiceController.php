<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Pagination\AbstractPaginator;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * ServiceController — SCR-PUB-02 & SCR-PUB-03
 *
 * Quản lý hiển thị danh sách dịch vụ công khai (SCR-PUB-02):
 *  - Tìm kiếm theo từ khóa (tên, mô tả)
 *  - Lọc theo danh mục (category)
 *  - Sắp xếp (thứ tự hiển thị, giá, thời lượng)
 *  - Phân trang chuẩn 20 mục/trang
 *  - Chỉ hiển thị dịch vụ ACTIVE trong danh mục ACTIVE
 */
class ServiceController extends Controller
{
    /**
     * Số dịch vụ trên mỗi trang phân trang theo đặc tả SCR-PUB-02.
     */
    private const PER_PAGE = 20;

    /**
     * GET /dich-vu (services.index) — SCR-PUB-02
     */
    public function index(Request $request): View
    {
        $search = trim((string) $request->input('q', ''));
        $category = trim((string) $request->input('category', ''));
        $sort = trim((string) $request->input('sort', 'default'));

        // Lấy danh sách danh mục để hiển thị thanh lọc
        $categories = $this->getCategories();

        // Lấy danh sách dịch vụ (có tìm kiếm, lọc danh mục, phân trang 20/trang)
        $services = $this->getServices($search, $category, $sort, $request);

        return view('public.services', compact(
            'services',
            'categories',
            'search',
            'category',
            'sort'
        ));
    }

    /**
     * GET /dich-vu/{service} (services.show) — SCR-PUB-03
     *
     * Hiển thị chi tiết dịch vụ tư vấn tâm lý, thông tin thời lượng, giá,
     * danh sách chuyên viên phụ trách và nút Đặt lịch dẫn sang SCR-CUS-03.
     */
    public function show(string|int $service): View
    {
        $serviceItem = null;

        // 1. Tìm trong DB nếu Model Service tồn tại
        if (class_exists(Service::class)) {
            try {
                $serviceItem = Service::query()
                    ->where('status', 'ACTIVE')
                    ->where(function ($q) use ($service) {
                        $q->where('id', $service);
                        if (! is_numeric($service)) {
                            $q->orWhere('slug', $service);
                        }
                    })
                    ->whereHas('category', fn ($c) => $c->where('is_active', true))
                    ->with(['category', 'specialists' => fn ($sp) => $sp->where('status', 'ACTIVE')])
                    ->first();
            } catch (\Throwable) {
                // DB lỗi hoặc chưa migrate
            }
        }

        // 2. Fallback tìm trong dữ liệu mẫu demo
        if (! $serviceItem) {
            $samples = $this->getSampleServices();
            $serviceItem = $samples->first(function ($item) use ($service) {
                return (string) $item->id === (string) $service
                    || (isset($item->slug) && $item->slug === $service);
            });
        }

        // Nếu không tìm thấy dịch vụ hoặc không ACTIVE -> 404
        if (! $serviceItem) {
            abort(404, 'Không tìm thấy dịch vụ tư vấn yêu cầu.');
        }

        // 3. Lấy danh sách chuyên viên cung cấp dịch vụ này
        $specialists = $this->getSpecialistsForService($serviceItem->id);

        // 4. Lấy các dịch vụ liên quan cùng danh mục
        $relatedServices = $this->getSampleServices()
            ->filter(fn ($item) => $item->id != $serviceItem->id && ($item->category_id ?? null) == ($serviceItem->category_id ?? null))
            ->take(3);

        return view('public.service-detail', [
            'service' => $serviceItem,
            'specialists' => $specialists,
            'relatedServices' => $relatedServices,
        ]);
    }

    /**
     * Lấy danh sách chuyên viên cung cấp một dịch vụ cụ thể.
     */
    private function getSpecialistsForService(int|string $serviceId): Collection
    {
        $allSpecialists = collect([
            (object) [
                'id' => 1,
                'name' => 'ThS. Nguyễn Thị Mai',
                'title' => 'Chuyên viên Tâm lý học lâm sàng',
                'specialization' => 'Rối loạn lo âu, trầm cảm, sang chấn tâm lý, căng thẳng kéo dài',
                'experience_years' => 10,
                'average_rating' => 4.9,
                'visible_reviews_count' => 62,
                'avatar_url' => null,
                'service_ids' => [1, 2, 7],
            ],
            (object) [
                'id' => 2,
                'name' => 'TS. Trần Văn Hưng',
                'title' => 'Tiến sĩ Tâm lý học tham vấn',
                'specialization' => 'Tâm lý hôn nhân, khủng hoảng gia đình, trị liệu cặp đôi',
                'experience_years' => 14,
                'average_rating' => 5.0,
                'visible_reviews_count' => 84,
                'avatar_url' => null,
                'service_ids' => [3, 5],
            ],
            (object) [
                'id' => 3,
                'name' => 'ThS. Lê Hoàng An',
                'title' => 'Chuyên viên Trị liệu Hành vi Nhận thức (CBT)',
                'specialization' => 'Kiểm soát căng thẳng, rối loạn giấc ngủ, kiệt sức nghề nghiệp',
                'experience_years' => 7,
                'average_rating' => 4.8,
                'visible_reviews_count' => 41,
                'avatar_url' => null,
                'service_ids' => [1, 2, 8],
            ],
            (object) [
                'id' => 4,
                'name' => 'Chuyên viên Phạm Thu Hà',
                'title' => 'Chuyên viên Tham vấn Thanh thiếu niên',
                'specialization' => 'Áp lực học đường, giao tiếp cha mẹ - con cái, cảm xúc tuổi dậy thì',
                'experience_years' => 6,
                'average_rating' => 4.9,
                'visible_reviews_count' => 35,
                'avatar_url' => null,
                'service_ids' => [4, 9],
            ],
            (object) [
                'id' => 5,
                'name' => 'ThS. Vũ Đức Minh',
                'title' => 'Chuyên viên Đánh giá & Chẩn đoán Tâm lý',
                'specialization' => 'Sàng lọc rối loạn cảm xúc, trắc nghiệm nhân cách, định hướng phát triển',
                'experience_years' => 9,
                'average_rating' => 4.8,
                'visible_reviews_count' => 29,
                'avatar_url' => null,
                'service_ids' => [5, 6],
            ],
            (object) [
                'id' => 6,
                'name' => 'ThS. Đỗ Thúy Hằng',
                'title' => 'Chuyên viên Trị liệu Tâm lý Gia đình',
                'specialization' => 'Hàn gắn rạn nứt gia đình, trầm cảm sau sinh, khủng hoảng sau biến cố',
                'experience_years' => 8,
                'average_rating' => 4.9,
                'visible_reviews_count' => 53,
                'avatar_url' => null,
                'service_ids' => [1, 3],
            ],
        ]);

        return $allSpecialists->filter(function ($sp) use ($serviceId) {
            return in_array((int) $serviceId, $sp->service_ids ?? []);
        })->values();
    }

    // -------------------------------------------------------------------------
    // Private Helpers
    // -------------------------------------------------------------------------

    /**
     * Lấy danh sách danh mục dịch vụ ACTIVE.
     */
    private function getCategories(): Collection
    {
        if (class_exists(Category::class)) {
            try {
                $dbCategories = Category::query()
                    ->where('is_active', true)
                    ->orderBy('display_order')
                    ->get(['id', 'name', 'slug']);

                if ($dbCategories->isNotEmpty()) {
                    return $dbCategories;
                }
            } catch (\Throwable) {
                // DB chưa migrate hoặc chưa có dữ liệu
            }
        }

        // Danh mục mẫu cho chế độ demo / phát triển
        return collect([
            (object) ['id' => 1, 'name' => 'Tất cả dịch vụ', 'slug' => ''],
            (object) ['id' => 2, 'name' => 'Tâm lý lâm sàng', 'slug' => 'tam-ly-lam-sang'],
            (object) ['id' => 3, 'name' => 'Trị liệu tâm lý', 'slug' => 'tri-lieu-tam-ly'],
            (object) ['id' => 4, 'name' => 'Hôn nhân & Gia đình', 'slug' => 'hon-nhan-gia-dinh'],
            (object) ['id' => 5, 'name' => 'Tâm lý thanh thiếu niên', 'slug' => 'tam-ly-thanh-thieu-nien'],
            (object) ['id' => 6, 'name' => 'Phát triển cá nhân', 'slug' => 'phat-trien-ca-nhan'],
            (object) ['id' => 7, 'name' => 'Đánh giá tâm lý', 'slug' => 'danh-gia-tam-ly'],
        ]);
    }

    /**
     * Lấy danh sách dịch vụ ACTIVE theo bộ lọc và phân trang 20/trang.
     */
    private function getServices(
        string $search,
        string $category,
        string $sort,
        Request $request
    ): LengthAwarePaginator|AbstractPaginator {
        if (class_exists(Service::class)) {
            try {
                $query = Service::query()
                    ->where('status', 'ACTIVE')
                    ->whereHas('category', fn ($q) => $q->where('is_active', true))
                    ->with('category');

                if ($search !== '') {
                    $query->where(function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                            ->orWhere('description', 'like', "%{$search}%");
                    });
                }

                if ($category !== '') {
                    $query->where(function ($q) use ($category) {
                        $q->where('category_id', $category)
                            ->orWhereHas('category', fn ($c) => $c->where('slug', $category)->orWhere('name', $category));
                    });
                }

                switch ($sort) {
                    case 'price_asc':
                        $query->orderBy('price', 'asc');
                        break;
                    case 'price_desc':
                        $query->orderBy('price', 'desc');
                        break;
                    case 'duration_desc':
                        $query->orderBy('duration_minutes', 'desc');
                        break;
                    case 'name_asc':
                        $query->orderBy('name', 'asc');
                        break;
                    default:
                        $query->orderBy('display_order', 'asc');
                        break;
                }

                $results = $query->paginate(self::PER_PAGE)->withQueryString();
                if ($results->total() > 0) {
                    return $results;
                }
            } catch (\Throwable) {
                // Fallback nếu bảng chưa có
            }
        }

        // Lấy danh sách dịch vụ mẫu và lọc in-memory
        $all = $this->getSampleServices();

        if ($search !== '') {
            $searchLower = mb_strtolower($search);
            $all = $all->filter(function ($item) use ($searchLower) {
                return str_contains(mb_strtolower($item->name), $searchLower)
                    || str_contains(mb_strtolower($item->description), $searchLower)
                    || str_contains(mb_strtolower($item->category->name ?? ''), $searchLower);
            });
        }

        if ($category !== '') {
            $all = $all->filter(function ($item) use ($category) {
                $catName = mb_strtolower($item->category->name ?? '');
                $catSlug = $item->category->slug ?? '';
                $catId = (string) ($item->category_id ?? '');

                return $catId === $category
                    || str_contains($catSlug, mb_strtolower($category))
                    || str_contains($catName, mb_strtolower($category));
            });
        }

        switch ($sort) {
            case 'price_asc':
                $all = $all->sortBy('price');
                break;
            case 'price_desc':
                $all = $all->sortByDesc('price');
                break;
            case 'duration_desc':
                $all = $all->sortByDesc('duration_minutes');
                break;
            case 'name_asc':
                $all = $all->sortBy('name');
                break;
            default:
                $all = $all->sortBy('display_order');
                break;
        }

        // Phân trang 20 mục/trang
        $page = (int) $request->input('page', 1);
        $total = $all->count();
        $items = $all->slice(($page - 1) * self::PER_PAGE, self::PER_PAGE)->values();

        return new LengthAwarePaginator(
            $items,
            $total,
            self::PER_PAGE,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );
    }

    /**
     * Dữ liệu mẫu dịch vụ chuẩn (SCR-PUB-02).
     */
    private function getSampleServices(): Collection
    {
        return collect([
            (object) [
                'id' => 1,
                'name' => 'Tư vấn tâm lý cá nhân 1-1',
                'category_id' => 2,
                'category' => (object) ['name' => 'Tâm lý lâm sàng', 'slug' => 'tam-ly-lam-sang'],
                'description' => 'Đồng hành cùng bạn nhận diện cảm xúc, giải tỏa bế tắc tinh thần và tìm lại điểm tựa bình an trong không gian hoàn toàn riêng tư, bảo mật.',
                'duration_minutes' => 60,
                'price' => 500000,
                'display_order' => 1,
            ],
            (object) [
                'id' => 2,
                'name' => 'Tham vấn & Trị liệu lo âu, trầm cảm',
                'category_id' => 3,
                'category' => (object) ['name' => 'Trị liệu tâm lý', 'slug' => 'tri-lieu-tam-ly'],
                'description' => 'Ứng dụng liệu pháp nhận thức - hành vi (CBT) và chánh niệm nhằm giải tỏa âu lo dai dẳng, phục hồi năng lượng sống tích cực.',
                'duration_minutes' => 90,
                'price' => 750000,
                'display_order' => 2,
            ],
            (object) [
                'id' => 3,
                'name' => 'Tư vấn hôn nhân & Mối quan hệ gia đình',
                'category_id' => 4,
                'category' => (object) ['name' => 'Hôn nhân & Gia đình', 'slug' => 'hon-nhan-gia-dinh'],
                'description' => 'Hóa giải xung đột giao tiếp vợ chồng, chữa lành tổn thương mối quan hệ và cùng nhau xây dựng mái ấm thấu hiểu, gắn kết.',
                'duration_minutes' => 90,
                'price' => 800000,
                'display_order' => 3,
            ],
            (object) [
                'id' => 4,
                'name' => 'Tư vấn tâm lý học đường & Thanh thiếu niên',
                'category_id' => 5,
                'category' => (object) ['name' => 'Tâm lý thanh thiếu niên', 'slug' => 'tam-ly-thanh-thieu-nien'],
                'description' => 'Hỗ trợ con vượt qua áp lực thi cử, biến động tâm sinh lý tuổi dậy thì và rút ngắn khoảng cách thế hệ giữa cha mẹ và con cái.',
                'duration_minutes' => 60,
                'price' => 450000,
                'display_order' => 4,
            ],
            (object) [
                'id' => 5,
                'name' => 'Quản trị cảm xúc & Khủng hoảng tuổi trưởng thành',
                'category_id' => 6,
                'category' => (object) ['name' => 'Phát triển cá nhân', 'slug' => 'phat-trien-ca-nhan'],
                'description' => 'Vượt qua cảm giác mất phương hướng, kiệt sức nghề nghiệp (burnout) và nâng cao sức bật tâm lý (resilience) trước áp lực hiện đại.',
                'duration_minutes' => 60,
                'price' => 550000,
                'display_order' => 5,
            ],
            (object) [
                'id' => 6,
                'name' => 'Đánh giá & Sàng lọc sức khỏe tinh thần tổng quát',
                'category_id' => 7,
                'category' => (object) ['name' => 'Đánh giá tâm lý', 'slug' => 'danh-gia-tam-ly'],
                'description' => 'Sử dụng các thang đo chuẩn quốc tế (DASS-21, GAD-7, PHQ-9) kết hợp tham vấn chuyên gia để định lượng trạng thái sức khỏe tinh thần.',
                'duration_minutes' => 75,
                'price' => 600000,
                'display_order' => 6,
            ],
            (object) [
                'id' => 7,
                'name' => 'Hỗ trợ phục hồi sau sang chấn tâm lý (PTSD)',
                'category_id' => 2,
                'category' => (object) ['name' => 'Tâm lý lâm sàng', 'slug' => 'tam-ly-lam-sang'],
                'description' => 'Liệu pháp chuyên sâu dành cho người từng trải qua biến cố đau buồn, tai nạn hoặc tổn thương tuổi thơ cần được tái lập an toàn nội tại.',
                'duration_minutes' => 90,
                'price' => 900000,
                'display_order' => 7,
            ],
            (object) [
                'id' => 8,
                'name' => 'Trị liệu tâm lý nhóm & Kỹ năng ứng phó căng thẳng',
                'category_id' => 3,
                'category' => (object) ['name' => 'Trị liệu tâm lý', 'slug' => 'tri-lieu-tam-ly'],
                'description' => 'Không gian chia sẻ an toàn cùng những người có chung hoàn cảnh, rèn luyện kỹ năng kiểm soát căng thẳng dưới sự dẫn dắt của chuyên gia.',
                'duration_minutes' => 120,
                'price' => 400000,
                'display_order' => 8,
            ],
            (object) [
                'id' => 9,
                'name' => 'Tư vấn hướng nghiệp & Định hướng đam mê cá nhân',
                'category_id' => 6,
                'category' => (object) ['name' => 'Phát triển cá nhân', 'slug' => 'phat-trien-ca-nhan'],
                'description' => 'Khám phá thế mạnh tính cách, động lực cốt lõi và lựa chọn con đường sự nghiệp tương thích với giá trị sống của bản thân.',
                'duration_minutes' => 60,
                'price' => 500000,
                'display_order' => 9,
            ],
        ]);
    }
}
