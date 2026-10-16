# ĐẶC TẢ GIAO DIỆN (UI SPECIFICATION) — MHCA

**Dự án:** Xây dựng ứng dụng đặt lịch tư vấn sức khỏe tinh thần và quản lý phòng khám tâm lý trên nền tảng Web
**Căn cứ:** `MASTER-SRS.md` v0.2 (đã gồm CR-001 thanh toán và hoàn tiền) · **Phiên bản tài liệu:** 0.1 (bản nháp để rà soát)
**Phạm vi:** mô tả *mỗi trang cần hiển thị gì, nhập gì, bấm gì, và hiển thị thế nào ở từng trạng thái*. Đây là tài liệu BA, **không chứa mã nguồn** (không HTML, CSS, Blade hay thiết kế đồ họa chi tiết). Bản vẽ wireframe sẽ làm sau dựa trên tài liệu này.

> **Ghi chú trung thực về nguồn.** `MASTER-SRS.md` hiện chưa có mục "UI/UX Specification" (các mục 42–57 chưa viết). Các mã màn hình `SCR-…` được lấy từ phần "Related" của từng FR (tổng cộng 51 màn hình). **Tên và nội dung màn hình** trong tài liệu này do mình suy ra từ các FR đó và đánh dấu `[ASSUMPTION]` khi không có căn cứ trực tiếp. Chatbot **không** có màn hình nào trong MVP.

Quy ước nhãn: `[MUST]` bắt buộc cho MVP · `[SHOULD]` nên có · `[ASSUMPTION]` giả định chờ xác nhận · `[OQ-n]` câu hỏi mở số n trong SRS.

---

## 1. Nguyên tắc giao diện chung (áp dụng cho mọi trang)

### 1.1. Ngôn ngữ, định dạng, múi giờ
- Toàn bộ nhãn, thông báo, lỗi bằng **tiếng Việt** (NFR-USA-001). Lỗi hiển thị thông điệp dễ hiểu, **không** hiện mã lỗi kỹ thuật hay stack trace (NFR-ERR-001).
- Tiền tệ: VND, dấu chấm ngăn nghìn, hậu tố "đ" (ví dụ `500.000 đ`).
- Ngày giờ: `dd/MM/yyyy HH:mm`, một múi giờ thống nhất (NFR-TIME-001, AS-01). Đồng hồ đếm ngược thanh toán lấy **thời gian máy chủ** làm gốc (FR-PAY-002).
- Số điện thoại, email hiển thị đúng định dạng nhập; danh sách che bớt khi cần (xem 1.7).

### 1.2. Khung trang
| Khu vực | Nội dung |
|---|---|
| Header | Logo/tên phòng khám; menu theo vai trò; chuông thông báo kèm số chưa đọc (khi đã đăng nhập); tên người dùng, menu "Hồ sơ / Đổi mật khẩu / Đăng xuất" |
| Thanh bên (Staff, Specialist, Admin) | Menu chức năng theo vai trò; thu gọn được trên màn hình nhỏ |
| Vùng nội dung | Tiêu đề trang, đường dẫn (breadcrumb) nếu từ cấp 2, thanh công cụ (lọc, tìm kiếm, nút chính), nội dung |
| Footer (trang công khai) | Liên hệ phòng khám, chính sách quyền riêng tư, **thông báo an toàn không dành cho tình huống khẩn cấp** (NFR-USA-003, `[RECOMMENDATION]`, nội dung chờ phòng khám xác nhận `[OQ-18]`) |

Thông báo an toàn phải hiển thị ở **trang chủ (SCR-PUB-01) và bước đặt lịch (SCR-CUS-03)**.

### 1.3. Responsive và hỗ trợ tiếp cận
- Dùng được trên điện thoại (NFR-USA-001): trên màn hình hẹp, bảng chuyển thành thẻ (card) xếp dọc; thanh bên thành menu bật/tắt; nút chính nằm dưới cùng khi dễ chạm.
- Nhãn gắn với ô nhập, điều hướng bằng bàn phím, tương phản đủ, ảnh có văn bản thay thế (NFR-ACC-001, mức `[TARGET]`).
- **Không dùng màu làm dấu hiệu duy nhất:** mọi chip trạng thái có chữ kèm màu.

### 1.4. Bốn trạng thái bắt buộc của mọi trang dữ liệu
| Trạng thái | Cách hiển thị |
|---|---|
| Đang tải | Khung xương (skeleton) hoặc biểu tượng xoay; nút gửi bị vô hiệu khi đang gửi để tránh bấm đúp |
| Rỗng | Hình minh họa nhỏ + một câu giải thích + nút hành động gợi ý (ví dụ "Chưa có lịch hẹn. Đặt lịch ngay") |
| Lỗi | Khung cảnh báo có nút "Thử lại"; lỗi từng ô nằm ngay dưới ô đó, lỗi chung ở đầu form |
| Thành công | Thông báo thoáng (toast) 3–5 giây hoặc chuyển trang kèm thông báo; thao tác quan trọng dùng trang kết quả |

### 1.5. Form và kiểm tra dữ liệu
- Kiểm tra ngay khi rời ô nhập (bên máy khách) **và** luôn kiểm tra lại ở máy chủ; hiển thị lỗi máy chủ theo từng ô khi có (mã 422).
- Đánh dấu `*` ô bắt buộc. Hiển thị bộ đếm ký tự cho ô có giới hạn dài (ghi chú 500, nhận xét 1000, tóm tắt tư vấn 5000).
- Ô mật khẩu có nút hiện/ẩn; hiển thị gợi ý quy tắc mật khẩu (BR-012).
- Nội dung người dùng nhập là **văn bản thuần**; hiển thị luôn được escape, không bao giờ render HTML (NFR-SEC-004).
- Chống gửi trùng: nút gửi bị khóa sau lần bấm đầu; thao tác tạo lịch gửi kèm khóa idempotency (BR-024).

### 1.6. Hộp xác nhận và thông báo
- Thao tác **không hoàn tác** bắt buộc có hộp xác nhận (NFR-USA-002): hủy lịch, ẩn đánh giá, khóa hoặc vô hiệu hóa tài khoản, xác nhận đã thu tiền, xác nhận đã hoàn tiền, xác nhận thanh toán thủ công, từ chối yêu cầu đổi lịch.
- Hộp xác nhận nêu rõ **đối tượng và hậu quả** (ví dụ "Hủy lịch 15/10 09:00 với chuyên viên A. Bạn sẽ được hoàn 250.000 đ.") và có hai nút: nút nguy hiểm (tên hành động) và "Quay lại".
- Khi cần lý do (hủy do phòng khám, ẩn đánh giá, khóa tài khoản…): ô lý do bắt buộc, tối đa 500 ký tự (255 với lý do nghỉ).

### 1.7. Bảo mật và quyền riêng tư thể hiện trên giao diện
| Quy tắc | Áp dụng |
|---|---|
| Che số tài khoản ngân hàng ở danh sách (chỉ còn 4 số cuối); chỉ người đang xử lý yêu cầu hoàn tiền mới thấy đủ | SCR-ADM-17, SCR-STF-03 (BR-081) |
| Staff và Admin **không** thấy `customer_note`, nội dung hồ sơ tư vấn, ngày sinh khách trong màn hình quản lý | SCR-STF-03/04, SCR-ADM-10 (BR-050, 058) |
| Nội dung hồ sơ tư vấn chỉ chuyên viên phụ trách thấy | SCR-SPE-06 (BR-049); chưa có quyền khẩn cấp cho Admin `[OQ-11]` |
| Đánh giá hiển thị công khai là **ẩn danh** | SCR-PUB-05 (BR-047) |
| Không có chức năng nào hiển thị `license_number`, `internal_note`, email chuyên viên ở trang công khai | SCR-PUB-04/05 |
| Không bao giờ hiển thị API Key hay khóa SePay trên giao diện | SCR-ADM-15 |
| Thông báo lỗi đăng nhập và quên mật khẩu **trung tính**, không tiết lộ tài khoản có tồn tại hay không | SCR-PUB-06, 08 |
| Khi lỗi 403/404 đối với tài nguyên của người khác: dùng cùng trang "Không tìm thấy" để không lộ sự tồn tại | SCR-COM-03/04 |

### 1.8. Bảng chip trạng thái dùng thống nhất

**Trạng thái lịch hẹn** (nhãn tiếng Việt `[ASSUMPTION]`)

| Mã | Nhãn hiển thị | Sắc thái |
|---|---|---|
| PENDING | Chờ thanh toán | Vàng |
| CONFIRMED | Đã xác nhận | Xanh dương |
| RESCHEDULE_REQUESTED | Chờ duyệt đổi lịch | Vàng |
| RESCHEDULED | Đã đổi lịch | Xám |
| IN_PROGRESS | Đang diễn ra | Xanh lá |
| COMPLETED | Đã hoàn thành | Xanh lá đậm |
| CANCELLED_BY_CUSTOMER | Khách đã hủy | Xám |
| CANCELLED_BY_CLINIC | Phòng khám đã hủy (kèm lý do, ví dụ "Hết thời gian thanh toán") | Đỏ nhạt |
| NO_SHOW | Vắng mặt | Đỏ |

**Trạng thái thanh toán**

| Mã | Nhãn | Sắc thái |
|---|---|---|
| UNPAID | Chưa thanh toán | Vàng |
| PAID | Đã thanh toán | Xanh lá |
| REFUND_PENDING | Chờ hoàn tiền | Cam |
| REFUNDED | Đã hoàn tiền | Xám xanh |
| NOT_REQUIRED | Không cần thanh toán | Xám |

**Phương thức:** CASH = "Tiền mặt tại quầy"; BANK_QR = "Chuyển khoản QR".

**Trạng thái tài khoản:** PENDING_VERIFICATION "Chờ xác thực email" · ACTIVE "Đang hoạt động" · LOCKED "Tạm khóa" · DISABLED "Đã vô hiệu hóa".

### 1.9. Điều hướng theo vai trò (menu chính)

| Vai trò | Mục menu |
|---|---|
| Khách (Guest) | Trang chủ · Dịch vụ · Chuyên viên · Đăng nhập · Đăng ký |
| Customer | Trang tổng quan · Đặt lịch · Lịch hẹn của tôi · Hồ sơ cá nhân · Thông báo |
| Specialist | Tổng quan · Lịch hẹn · Lịch làm việc · Ngày nghỉ và chặn giờ · Thông báo |
| Staff | Tổng quan · Lịch hẹn · Điều phối lịch (lịch cần xử lý) · Đặt lịch hộ · Khách hàng · Thanh toán và hoàn tiền · Thông báo |
| Admin | Tổng quan · Người dùng · Chuyên viên · Dịch vụ · Danh mục · Lịch và ngày lễ · Lịch hẹn · Đánh giá · Thanh toán và hoàn tiền · Thông báo · Báo cáo · Nhật ký audit · Cấu hình · Vai trò |

> Sau khi đăng nhập: chuyển đến màn tổng quan đúng vai trò (FR-AUTH-002). Menu **chỉ hiển thị mục được phép**, nhưng quyền thật luôn kiểm tra ở máy chủ (NFR-SEC-002), không dựa vào việc ẩn menu.

---

## 2. Danh mục 51 màn hình

| Nhóm | Mã | Tên màn hình | Vai trò | FR chính |
|---|---|---|---|---|
| Công khai | SCR-PUB-01 | Trang chủ `[ASSUMPTION]` | Mọi người | NFR-USA-003 |
| | SCR-PUB-02 | Danh sách dịch vụ | Mọi người | FR-SERVICE-001 |
| | SCR-PUB-03 | Chi tiết dịch vụ | Mọi người | FR-SERVICE-001 |
| | SCR-PUB-04 | Danh sách chuyên viên | Mọi người | FR-SPECIALIST-002 |
| | SCR-PUB-05 | Chi tiết chuyên viên và đánh giá | Mọi người | FR-SPECIALIST-002, FR-REVIEW-002 |
| | SCR-PUB-06 | Đăng nhập | Mọi người | FR-AUTH-002 |
| | SCR-PUB-07 | Đăng ký | Guest | FR-AUTH-001 |
| | SCR-PUB-08 | Quên mật khẩu | Mọi người | FR-AUTH-004 |
| | SCR-PUB-09 | Đặt lại mật khẩu | Mọi người | FR-AUTH-004 |
| | SCR-PUB-10 | Xác thực email | Customer | FR-AUTH-006 |
| Dùng chung | SCR-COM-01 | Thông báo | Đã đăng nhập | FR-NOTI-002 |
| | SCR-COM-02 | Đổi mật khẩu | Đã đăng nhập | FR-AUTH-005 |
| | SCR-COM-03 | Không có quyền truy cập (403) `[ASSUMPTION]` | Đã đăng nhập | FR-RBAC |
| | SCR-COM-04 | Không tìm thấy / lỗi hệ thống (404, 500) `[ASSUMPTION]` | Mọi người | NFR-ERR-001 |
| Customer | SCR-CUS-01 | Tổng quan khách hàng | Customer | FR-DASH-002 |
| | SCR-CUS-02 | Hồ sơ cá nhân | Customer | FR-CUSTOMER-001 |
| | SCR-CUS-03 | Đặt lịch (kèm thanh toán) | Customer | FR-APPT-001, FR-PAY-001/002/006 |
| | SCR-CUS-04 | Lịch hẹn của tôi | Customer | FR-APPT-004 |
| | SCR-CUS-05 | Chi tiết lịch hẹn (hủy, hoàn tiền, lịch sử) | Customer | FR-APPT-004/006/014, FR-PAY-009/010 |
| | SCR-CUS-06 | Yêu cầu đổi lịch | Customer | FR-APPT-008 |
| | SCR-CUS-07 | Đánh giá của tôi | Customer | FR-REVIEW-001/002 |
| Specialist | SCR-SPE-01 | Tổng quan chuyên viên | Specialist | FR-DASH-002 |
| | SCR-SPE-02 | Lịch làm việc hằng tuần | Specialist | FR-SCHEDULE-001 |
| | SCR-SPE-03 | Ngày nghỉ và chặn giờ | Specialist | FR-SCHEDULE-002 |
| | SCR-SPE-04 | Danh sách lịch hẹn được giao | Specialist | FR-APPT-004 |
| | SCR-SPE-05 | Chi tiết lịch hẹn (bắt đầu, hoàn thành, vắng mặt) | Specialist | FR-APPT-011/012/013 |
| | SCR-SPE-06 | Hồ sơ buổi tư vấn | Specialist | FR-CONSULT-001/002 |
| Staff | SCR-STF-01 | Tổng quan Staff | Staff | FR-DASH-002 |
| | SCR-STF-02 | Quản lý lịch hẹn | Staff | FR-APPT-004, FR-PAY-007 |
| | SCR-STF-03 | Chi tiết lịch hẹn (điều phối) | Staff | FR-APPT-005…014, FR-PAY-005/007/008/011 |
| | SCR-STF-04 | Danh sách khách hàng | Staff | FR-CUSTOMER-003 |
| | SCR-STF-05 | Tạo và sửa hồ sơ khách | Staff | FR-CUSTOMER-002 |
| | SCR-STF-06 | Đặt lịch hộ khách | Staff | FR-APPT-002 |
| | SCR-STF-07 | Điều phối lịch bị ảnh hưởng | Staff | FR-SCHEDULE-005 |
| Admin | SCR-ADM-01 | Tổng quan quản trị | Admin | FR-DASH-001 |
| | SCR-ADM-02 | Danh sách người dùng | Admin | FR-ADMIN-001, FR-AUTH-007 |
| | SCR-ADM-03 | Tạo và sửa người dùng nội bộ | Admin | FR-ADMIN-001 |
| | SCR-ADM-04 | Danh sách chuyên viên | Admin | FR-SPECIALIST-003 |
| | SCR-ADM-05 | Tạo và sửa chuyên viên | Admin | FR-SPECIALIST-001/004/005 |
| | SCR-ADM-06 | Danh sách dịch vụ | Admin | FR-SERVICE-002 |
| | SCR-ADM-07 | Tạo và sửa dịch vụ | Admin | FR-SERVICE-002 |
| | SCR-ADM-08 | Danh mục dịch vụ | Admin | FR-SERVICE-003 |
| | SCR-ADM-09 | Lịch làm việc và ngày lễ phòng khám | Admin | FR-SCHEDULE-001/006 |
| | SCR-ADM-10 | Danh sách lịch hẹn (toàn hệ thống) | Admin | FR-APPT-004 |
| | SCR-ADM-11 | Kiểm duyệt đánh giá | Admin | FR-REVIEW-003 |
| | SCR-ADM-12 | Thông báo thủ công và nhật ký gửi `[ASSUMPTION]` | Admin | FR-NOTI-005 |
| | SCR-ADM-13 | Báo cáo | Admin | FR-REPORT-001/002 |
| | SCR-ADM-14 | Nhật ký audit | Admin | FR-ADMIN-003 |
| | SCR-ADM-15 | Cấu hình hệ thống | Admin | FR-ADMIN-002 |
| | SCR-ADM-16 | Vai trò và quyền `[ASSUMPTION]` | Admin | FR-ADMIN-001 |
| | SCR-ADM-17 | Thanh toán và hoàn tiền (mới, CR-001) | Staff, Admin | FR-PAY-005/011/012 |

---

## 3. Đặc tả từng màn hình — Nhóm công khai và dùng chung

### SCR-PUB-01 — Trang chủ `[ASSUMPTION]`
- **Mục đích:** giới thiệu phòng khám, dẫn khách đến đặt lịch.
- **Bố cục (trên xuống):** (1) tiêu đề + mô tả ngắn + nút "Đặt lịch ngay" (chưa đăng nhập thì chuyển đến đăng nhập rồi quay lại); (2) khối **thông báo an toàn không dành cho tình huống khẩn cấp** (nội dung do phòng khám xác nhận, `[OQ-18]`); (3) vài dịch vụ nổi bật (lấy từ danh sách dịch vụ ACTIVE, **không hard-code**, NFR-MNT-002); (4) vài chuyên viên nổi bật (ACTIVE); (5) quy trình 3 bước đặt lịch; (6) footer.
- **Trạng thái:** chưa có dịch vụ/chuyên viên → ẩn khối đó, không báo lỗi.

### SCR-PUB-02 — Danh sách dịch vụ (FR-SERVICE-001)
- **Hiển thị:** thẻ dịch vụ ACTIVE trong danh mục ACTIVE, nhóm theo danh mục, theo `display_order`. Mỗi thẻ: tên, mô tả ngắn (cắt ~120 ký tự), thời lượng (phút), giá (nếu có, ngược lại ẩn dòng giá), nút "Xem chi tiết".
- **Lọc:** danh mục (danh sách chọn). **Phân trang:** 20 mục/trang.
- **Rỗng:** "Hiện chưa có dịch vụ nào." Không hiển thị dịch vụ INACTIVE.

### SCR-PUB-03 — Chi tiết dịch vụ
- **Hiển thị:** tên, danh mục, mô tả đầy đủ, thời lượng, giá, danh sách chuyên viên cung cấp (thẻ nhỏ có liên kết), nút "Đặt lịch dịch vụ này" (mang sẵn `service_id` sang SCR-CUS-03).
- **Lỗi:** dịch vụ không tồn tại hoặc INACTIVE → SCR-COM-04.

### SCR-PUB-04 — Danh sách chuyên viên (FR-SPECIALIST-002)
- **Hiển thị:** thẻ gồm ảnh (ảnh mặc định nếu chưa có), họ tên, chức danh, chuyên môn, số năm kinh nghiệm, điểm đánh giá trung bình (chỉ tính đánh giá VISIBLE) kèm số lượt.
- **Lọc:** theo dịch vụ. **Không hiển thị:** `license_number`, `internal_note`, email, SĐT.
- **Rỗng:** "Chưa có chuyên viên phù hợp."

### SCR-PUB-05 — Chi tiết chuyên viên và đánh giá (FR-SPECIALIST-002, FR-REVIEW-002)
- **Hiển thị:** ảnh lớn, họ tên, chức danh, chuyên môn, giới thiệu (`bio`), kinh nghiệm, danh sách dịch vụ cung cấp; điểm trung bình + phân bố sao; danh sách đánh giá VISIBLE (số sao, nhận xét, thời điểm, **không có tên khách**), phân trang; nút "Đặt lịch với chuyên viên này".
- **Rỗng đánh giá:** "Chưa có đánh giá."

### SCR-PUB-06 — Đăng nhập (FR-AUTH-002)
- **Trường:** Email* (định dạng email), Mật khẩu* (có nút hiện/ẩn). Liên kết "Quên mật khẩu?", "Chưa có tài khoản? Đăng ký".
- **Hành động:** Đăng nhập → chuyển về trang đích nội bộ hợp lệ nếu có, ngược lại dashboard theo vai trò.
- **Lỗi hiển thị (chung, không tiết lộ tài khoản):**
  - Sai thông tin: "Email hoặc mật khẩu không đúng."
  - Bị khóa tạm (5 lần sai, khóa 15 phút): "Tài khoản tạm khóa do đăng nhập sai nhiều lần. Vui lòng thử lại sau 15 phút."
  - Bị vô hiệu hóa: "Tài khoản đã bị vô hiệu hóa. Vui lòng liên hệ phòng khám."
  - Chờ xác thực: "Vui lòng xác thực email trước khi đăng nhập" kèm nút gửi lại.
  - Quá nhiều yêu cầu (429): "Bạn thao tác quá nhanh, vui lòng thử lại sau."
- **Khi hết phiên:** hiển thị thông báo "Phiên đăng nhập đã hết hạn, vui lòng đăng nhập lại."

### SCR-PUB-07 — Đăng ký (FR-AUTH-001)
| Trường | Loại | Bắt buộc | Quy tắc |
|---|---|---|---|
| Họ và tên | Văn bản | Có | 2–100 ký tự |
| Email | Email | Có | Đúng định dạng, ≤ 255, duy nhất không phân biệt hoa thường |
| Mật khẩu | Mật khẩu | Có | Theo BR-012; hiển thị gợi ý quy tắc |
| Xác nhận mật khẩu | Mật khẩu | Có | Khớp mật khẩu |
| Ngày sinh | Ngày | Có | Ngày quá khứ, **tuổi ≥ 18** |
| Số điện thoại | Văn bản | Không | 9–15 chữ số, cho phép tiền tố `+` |
| Đồng ý chính sách quyền riêng tư | Ô tích | Có | Phải tích; có liên kết xem chính sách (ghi phiên bản và thời điểm đồng ý) |

- **Kết quả:** nếu `email_verification_required` bật → trang "Vui lòng kiểm tra email để xác thực"; ngược lại chuyển đến đăng nhập kèm thông báo thành công.
- **Lỗi riêng:** email đã dùng (thông điệp trung tính), mật khẩu yếu, dưới 18 tuổi ("Hiện chưa hỗ trợ khách dưới 18 tuổi"), chưa đồng ý chính sách, 429.
- **Không thu:** giới tính, địa chỉ, liên hệ khẩn cấp, bệnh sử (NFR-PRV-003).

### SCR-PUB-08 — Quên mật khẩu (FR-AUTH-004)
- **Trường:** Email*. **Hành động:** Gửi liên kết đặt lại.
- **Kết quả luôn giống nhau:** "Nếu email tồn tại trong hệ thống, chúng tôi đã gửi hướng dẫn đặt lại mật khẩu." (không tiết lộ email có tồn tại hay không).

### SCR-PUB-09 — Đặt lại mật khẩu
- **Truy cập qua:** liên kết trong email (chứa token một lần, hạn 60 phút `[TARGET]`).
- **Trường:** Mật khẩu mới*, Xác nhận mật khẩu* (quy tắc BR-012).
- **Kết quả:** thành công → về đăng nhập, mọi phiên cũ bị hủy. Token sai/hết hạn/đã dùng → "Liên kết không hợp lệ hoặc đã hết hạn" + nút "Yêu cầu liên kết mới".

### SCR-PUB-10 — Xác thực email (FR-AUTH-006, SHOULD)
- **Truy cập qua:** liên kết trong email (hạn 24 giờ `[TARGET]`); hoặc từ trang đăng nhập khi tài khoản đang chờ xác thực.
- **Hiển thị:** kết quả xác thực (thành công → nút "Vào trang tổng quan"; thất bại → nút "Gửi lại email xác thực").
- **Giới hạn gửi lại:** tối đa 3 lần/giờ; hiển thị đếm ngược khi hết lượt.
- **Khi tính năng tắt:** màn hình này không cần dùng.

### SCR-COM-01 — Thông báo (FR-NOTI-002)
- **Hai điểm vào:** (1) **chuông** ở header: bật khung thả xuống 5 thông báo mới nhất + liên kết "Xem tất cả"; (2) **trang đầy đủ** có danh sách phân trang.
- **Mỗi dòng:** biểu tượng loại, tiêu đề, nội dung ngắn, thời điểm, chấm "chưa đọc". Bấm vào → đánh dấu đã đọc và chuyển tới chi tiết lịch liên quan (nếu có `appointment_id`).
- **Công cụ:** lọc "Tất cả / Chưa đọc"; nút "Đánh dấu tất cả đã đọc".
- **Nội dung thông báo:** theo ma trận mục 30.2 và 28A.14 của SRS; **không chứa ghi chú của khách**; tên dịch vụ chỉ xuất hiện ở thông báo trong ứng dụng của chính khách (BR-051).
- **Rỗng:** "Bạn chưa có thông báo nào."

### SCR-COM-02 — Đổi mật khẩu (FR-AUTH-005)
- **Trường:** Mật khẩu hiện tại*, Mật khẩu mới* (khác mật khẩu cũ, BR-012), Xác nhận*.
- **Kết quả:** thành công → thông báo, các phiên khác bị hủy. Sai mật khẩu hiện tại → lỗi tại ô, tính vào giới hạn thử.

### SCR-COM-03 — Không có quyền truy cập `[ASSUMPTION]`
- **Hiển thị:** "Bạn không có quyền truy cập trang này." + nút "Về trang tổng quan". Không hiển thị chi tiết về tài nguyên.

### SCR-COM-04 — Không tìm thấy / lỗi hệ thống `[ASSUMPTION]`
- **Hai biến thể:** (a) 404: "Không tìm thấy nội dung bạn yêu cầu." (cũng dùng khi truy cập tài nguyên của người khác); (b) 500: "Hệ thống đang gặp sự cố, vui lòng thử lại sau." Có nút "Về trang chủ". **Không** hiển thị chi tiết kỹ thuật.

---

## 4. Đặc tả từng màn hình — Customer

### SCR-CUS-01 — Tổng quan khách hàng (FR-DASH-002)
- **Khối 1:** "Lịch hẹn sắp tới" — tối đa 3 lịch gần nhất đang hoạt động, mỗi thẻ gồm ngày giờ, chuyên viên, dịch vụ, chip trạng thái và chip thanh toán; lịch **chờ thanh toán còn hạn** hiện nút "Thanh toán ngay" kèm đồng hồ đếm ngược.
- **Khối 2:** thông báo mới (5 mục) + liên kết "Xem tất cả".
- **Khối 3:** nút "Đặt lịch mới".
- **Rỗng:** "Bạn chưa có lịch hẹn sắp tới" + nút đặt lịch.

### SCR-CUS-02 — Hồ sơ cá nhân (FR-CUSTOMER-001)
- **Chỉ đọc:** mã khách, ngày sinh, vai trò, ngày tham gia.
- **Sửa được:** họ tên (2–100), số điện thoại (9–15 chữ số), email (đổi email có thể yêu cầu xác thực lại khi tính năng bật).
- **Hành động:** Lưu thay đổi; liên kết "Đổi mật khẩu".
- **Lỗi:** email trùng; định dạng sai.

### SCR-CUS-03 — Đặt lịch, kèm bước thanh toán (FR-APPT-001, FR-SCHEDULE-004, FR-PAY-001/002/006)

Màn hình dạng **trình hướng dẫn nhiều bước** có thanh tiến trình. Dữ liệu đã chọn **không mất** khi quay lại bước trước hoặc khi tải lại slot do xung đột.

| Bước | Nội dung |
|---|---|
| 1. Dịch vụ | Chọn dịch vụ (danh sách thẻ, lọc theo danh mục). Nếu đi từ trang chi tiết dịch vụ thì đã chọn sẵn |
| 2. Chuyên viên | Chỉ liệt kê chuyên viên ACTIVE **có cung cấp dịch vụ đã chọn**. Hiển thị điểm đánh giá |
| 3. Ngày | Lịch chọn ngày: ngày không có slot bị làm mờ; ngày trước giờ tối thiểu (2 giờ) hoặc sau 30 ngày bị vô hiệu hóa. Không có slot → gợi ý "Ngày gần nhất còn trống: …" |
| 4. Giờ | Danh sách slot theo giờ bắt đầu, hiển thị giờ bắt đầu và kết thúc. Slot trùng lịch của chính khách hiển thị mờ, chú thích "Bạn đã có lịch trùng giờ" |
| 5. Ghi chú | Ô ghi chú tùy chọn (≤ 500 ký tự, văn bản thuần) |
| 6. Thanh toán | Chọn **phương thức**: "Chuyển khoản QR" hoặc "Tiền mặt tại quầy". Hiển thị tóm tắt: dịch vụ, chuyên viên, giờ, **số tiền cần trả**, **chính sách hủy và hoàn tiền** (bảng 3 mốc) |
| 7. Xác nhận | Tóm tắt toàn bộ + tick đồng ý chính sách hủy/hoàn + nút "Xác nhận đặt lịch" |

**Thông báo an toàn không dành cho tình huống khẩn cấp** hiển thị cố định ở đầu màn hình (NFR-USA-003).

**Nhánh "Chuyển khoản QR"** — sau khi xác nhận, hệ thống tạo lịch PENDING và chuyển sang **màn thanh toán**:
- Mã **VietQR động** (lớn, căn giữa).
- Thông tin chuyển thủ công: ngân hàng, số tài khoản (có nút sao chép), chủ tài khoản, **số tiền** (có nút sao chép), **nội dung chuyển khoản `DATLICH{mã lịch}`** (có nút sao chép; **không sửa được**).
- **Đồng hồ đếm ngược 10 phút** (giữ chỗ). Dòng chú thích: "Vui lòng chuyển đúng số tiền và đúng nội dung để được xác nhận tự động."
- Hệ thống tự kiểm tra trạng thái định kỳ (khoảng 3 giây, `[TARGET]`). Khi PAID → chuyển sang **trang thành công** (mã lịch, ngày giờ, chuyên viên, "Đã thanh toán", nút "Xem lịch hẹn").
- **Hết hạn:** thông báo "Hết thời gian giữ chỗ", ẩn QR, nút "Đặt lại".
- **Không tải được QR:** hiển thị số tài khoản và nội dung dạng chữ để chuyển tay.
- **Thanh toán muộn/sai số tiền/chưa khớp:** người dùng không thấy xử lý nội bộ; màn hình hiển thị "Chúng tôi đang đối soát giao dịch, vui lòng chờ hoặc liên hệ phòng khám" nếu khách báo đã chuyển nhưng chưa xác nhận (mô tả chi tiết ở SCR-ADM-17).

**Nhánh "Tiền mặt tại quầy"** — lịch vào CONFIRMED ngay, **không có đồng hồ đếm ngược**. Trang thành công hiển thị thông điệp bắt buộc: **"Đặt lịch thành công! Vui lòng đến trước 10 phút để thực hiện dịch vụ."** kèm mã lịch, ngày giờ, số tiền phải trả tại quầy.

**Lỗi riêng của màn hình:**
| Tình huống | Hiển thị |
|---|---|
| Slot vừa bị người khác đặt (409) | "Khung giờ này vừa được người khác đặt. Vui lòng chọn khung giờ khác." Tải lại slot, **giữ nguyên** dịch vụ, chuyên viên, ghi chú; đề xuất tối đa 5 slot gần nhất |
| Trùng lịch của chính khách | "Bạn đã có lịch hẹn trùng khung giờ này." |
| Quá 3 lịch đang hoạt động | "Bạn đã đạt số lịch hẹn đang hoạt động tối đa (3)." |
| Ngoài cửa sổ đặt (dưới 2 giờ hoặc quá 30 ngày) | "Thời gian đặt không hợp lệ." kèm giới hạn cho phép |
| Dịch vụ hoặc chuyên viên không còn khả dụng | "Dịch vụ/chuyên viên hiện không khả dụng." và quay về bước chọn |
| Chưa xác thực email (khi bật) | Chặn đặt lịch, hiện nút gửi lại email xác thực |

### SCR-CUS-04 — Lịch hẹn của tôi (FR-APPT-004)
- **Hiển thị:** danh sách thẻ hoặc bảng; mỗi dòng: mã lịch, ngày giờ, chuyên viên, dịch vụ, chip trạng thái, chip thanh toán, nút "Xem chi tiết".
- **Lọc:** trạng thái, khoảng ngày (mặc định "sắp tới"). **Sắp xếp:** theo ngày hẹn. **Phân trang** 20.
- **Chỉ thấy lịch của chính mình.**
- **Rỗng:** "Bạn chưa có lịch hẹn nào" + nút đặt lịch.

### SCR-CUS-05 — Chi tiết lịch hẹn: hủy, hoàn tiền, lịch sử (FR-APPT-004/006/014, FR-PAY-002/009/010)
- **Thông tin lịch:** mã lịch, dịch vụ, chuyên viên, ngày giờ bắt đầu và kết thúc, ghi chú của khách (chỉ khách và chuyên viên phụ trách thấy; có nút "Sửa ghi chú" khi PENDING hoặc CONFIRMED).
- **Khối thanh toán:** phương thức, số tiền, trạng thái thanh toán; nếu có hoàn tiền: số tiền hoàn, tỷ lệ, trạng thái yêu cầu hoàn (Chờ hoàn / Đã hoàn), thời gian dự kiến nhận tiền `[ASSUMPTION: 3–7 ngày làm việc, chờ chốt]`.
- **Lịch sử trạng thái:** dòng thời gian (thời điểm, từ → đến, ai thực hiện: Khách/Hệ thống/Phòng khám). Khách thấy lý do hủy do phòng khám; **không** thấy lý do nội bộ.
- **Hành động hiển thị theo trạng thái** (xem bảng ở mục 8).

**Quy trình "Yêu cầu hủy" (pop-up hai bước):**
1. Bấm "Hủy lịch" → hệ thống gọi xem trước hoàn tiền → hiển thị **pop-up tính hoàn tiền** gồm: giờ hẹn, thời gian còn lại, **số tiền đã trả**, **tỷ lệ hoàn (100% / 50% / 0%)**, **số tiền hoàn**, ô lý do (tùy chọn ≤ 500).
2. Quy tắc hiển thị theo tình huống:
   - Còn từ 24 giờ: hoàn 100%. Từ 12 đến dưới 24 giờ: hoàn 50%. Dưới 12 giờ: hoàn 0% (nêu rõ "Bạn sẽ không được hoàn tiền").
   - Lịch tiền mặt chưa thu hoặc lịch chưa thanh toán: "Hủy trực tiếp, không phát sinh hoàn tiền."
3. **Khi số tiền hoàn > 0:** hiện form thông tin ngân hàng nhận hoàn: Tên ngân hàng*, Số tài khoản* (6–20 chữ số), Tên chủ tài khoản* (2–100 ký tự). Dòng chú thích: "Thông tin chỉ dùng để hoàn tiền và được bảo mật."
4. Nút "Xác nhận hủy" (nguy hiểm) và "Quay lại". Hệ thống tính lại và **chốt** số tiền khi xác nhận.
5. **Nếu số tiền thay đổi giữa lúc xem và lúc xác nhận** (qua mốc 12 hoặc 24 giờ): hiển thị "Số tiền hoàn đã thay đổi thành … đ. Bạn có đồng ý hủy?" để khách xác nhận lại.
6. **Kết quả:** trạng thái "Khách đã hủy"; nếu có hoàn: "Chờ hoàn tiền" + thông điệp "Phòng khám sẽ chuyển khoản trong …". Slot được nhả ngay.

**Khi phòng khám hủy lịch đã thanh toán:** khách nhận thông báo; lịch hiển thị "Chờ hoàn tiền 100%" và **thiếu thông tin ngân hàng** → màn hình hiện khung "Vui lòng bổ sung thông tin ngân hàng để nhận hoàn tiền" (cùng ba ô như trên, lưu bằng thao tác cập nhật).

**Thanh toán tiếp:** lịch PENDING còn hạn có nút "Thanh toán" mở lại màn QR (SCR-CUS-03); hết hạn thì ẩn nút.

### SCR-CUS-06 — Yêu cầu đổi lịch (FR-APPT-008)
- **Điều kiện hiển thị nút:** lịch CONFIRMED, còn **≥ 24 giờ** trước giờ hẹn, đã đổi **< 2 lần**. Không đủ điều kiện → nút vô hiệu kèm giải thích ("Chỉ đổi lịch trước giờ hẹn ít nhất 24 giờ" / "Bạn đã dùng hết số lần đổi lịch").
- **Hiển thị:** lịch hiện tại (chỉ đọc); chọn ngày; chọn giờ mới (cùng dịch vụ và chuyên viên); ô lý do (tùy chọn). Số lần đã đổi / tối đa.
- **Hành động:** "Gửi yêu cầu đổi lịch". **Kết quả:** trạng thái "Chờ duyệt đổi lịch"; cả slot cũ và slot mới đều đang được giữ; thông báo "Yêu cầu đã gửi tới phòng khám".
- **Hành động sau gửi:** "Rút yêu cầu" (quay lại CONFIRMED, nhả slot mới).
- **Lỗi:** slot mới vừa bị đặt (409), quá số lần đổi, quá hạn đổi lịch, trùng lịch khác của khách.
- **Khi bị từ chối hoặc được duyệt:** nhận thông báo; lịch cũ về CONFIRMED hoặc lịch mới xuất hiện (liên kết lịch cũ).

### SCR-CUS-07 — Đánh giá của tôi (FR-REVIEW-001/002)
- **Danh sách:** các buổi COMPLETED của khách; mỗi dòng có trạng thái "Chưa đánh giá — còn X ngày" / "Đã đánh giá" / "Hết hạn đánh giá" (30 ngày).
- **Form đánh giá:** chọn sao 1–5* (bắt buộc), nhận xét (tùy chọn ≤ 1000, văn bản thuần). Lưu một lần, **không sửa hoặc xóa**.
- **Lưu ý hiển thị:** "Đánh giá của bạn sẽ được hiển thị công khai ẩn danh trên trang chuyên viên."
- **Lỗi:** đã đánh giá (409), hết hạn, lịch chưa hoàn thành.

---

## 5. Đặc tả từng màn hình — Specialist

### SCR-SPE-01 — Tổng quan chuyên viên (FR-DASH-002)
- **Khối:** "Lịch hôm nay" (theo giờ, mỗi dòng: giờ, tên khách, dịch vụ, chip trạng thái, nút vào chi tiết) · "Lịch tuần này" (dạng lịch tuần) · thông báo mới.
- **Chỉ lịch của chính mình.** Rỗng: "Hôm nay bạn không có lịch hẹn."

### SCR-SPE-02 — Lịch làm việc hằng tuần (FR-SCHEDULE-001)
- **Hiển thị:** lưới 7 ngày trong tuần; mỗi ngày có các ca làm việc.
- **Mỗi ca:** thứ trong tuần*, giờ bắt đầu*, giờ kết thúc* (> giờ bắt đầu), nghỉ giữa ca bắt đầu/kết thúc (tùy chọn, nằm trong ca), hiệu lực từ/đến (tùy chọn), bật/tắt ca.
- **Kiểm tra:** các ca cùng ngày **không chồng nhau**; break nằm trong ca.
- **Khi rút ngắn hoặc bỏ ca đang có lịch tương lai:** hiển thị **xem trước ảnh hưởng** (số lịch và danh sách) trước khi lưu; người dùng xác nhận; các lịch ảnh hưởng bị gắn cờ "cần xử lý" chứ không bị hủy tự động.
- **Lỗi:** chồng ca (422); sửa lịch người khác (403).

### SCR-SPE-03 — Ngày nghỉ và chặn giờ (FR-SCHEDULE-002)
- **Danh sách:** các ngoại lệ sắp tới (loại, ngày, giờ, lý do, nút xóa).
- **Form thêm:** loại* (Nghỉ cả ngày / Chặn một khoảng giờ), từ ngày*, đến ngày* (≥ từ ngày; không hoàn toàn trong quá khứ), giờ bắt đầu và kết thúc (chỉ khi chặn giờ, một ngày), lý do (≤ 255), ô tích "Nghỉ đột xuất".
- **Xem trước ảnh hưởng (bắt buộc):** trước khi lưu, hiển thị danh sách lịch giữ slot bị chồng; nút "Xác nhận nghỉ". Hiệu lực **ngay**.
- **Sau lưu:** thông báo "Đã ghi nhận. Phòng khám đã được báo về X lịch bị ảnh hưởng."
- **Xóa ngoại lệ:** slot trở lại khả dụng; lịch đã hủy trước đó **không** tự khôi phục (hiển thị cảnh báo này trong hộp xác nhận).

### SCR-SPE-04 — Danh sách lịch hẹn được giao (FR-APPT-004)
- **Hiển thị:** mã lịch, ngày giờ, **tên khách**, dịch vụ, chip trạng thái. **Không** hiển thị tiền hay thông tin thanh toán `[ASSUMPTION]`.
- **Lọc:** trạng thái, khoảng ngày (mặc định hôm nay trở đi). **Phân trang** 20.

### SCR-SPE-05 — Chi tiết lịch hẹn: bắt đầu, hoàn thành, vắng mặt (FR-APPT-011/012/013)
- **Thông tin:** mã lịch, tên khách, dịch vụ, giờ, **ghi chú của khách** (chuyên viên phụ trách được xem), lịch sử trạng thái.
- **Nút theo trạng thái:**
  - CONFIRMED: "Bắt đầu buổi tư vấn" — chỉ bật từ **15 phút trước giờ hẹn** đến hết giờ; ngoài khung thì vô hiệu kèm giải thích. "Đánh dấu vắng mặt" — chỉ bật khi **quá 15 phút** sau giờ hẹn.
  - IN_PROGRESS: "Hoàn thành buổi tư vấn", liên kết "Ghi hồ sơ tư vấn".
  - COMPLETED: liên kết "Xem/sửa hồ sơ tư vấn".
- **Hộp xác nhận** cho Hoàn thành và Vắng mặt. Vắng mặt có ô ghi chú tùy chọn.
- **Lỗi:** "Chưa đến giờ bắt đầu", "Chưa quá thời gian chờ", sai trạng thái (409).

### SCR-SPE-06 — Hồ sơ buổi tư vấn (FR-CONSULT-001/002)
- **Chỉ chuyên viên phụ trách truy cập được.** Người khác (kể cả Admin, Staff, khách) nhận SCR-COM-03/04.
- **Trường:** Tóm tắt buổi tư vấn* (≤ 5000), Kế hoạch theo dõi (≤ 2000, tùy chọn), Cần theo dõi tiếp (có/không). Không để cả ba cùng rỗng.
- **Hiển thị:** banner "Nội dung này được mã hóa và chỉ bạn xem được. Mọi lần xem và sửa đều được ghi nhật ký." Bộ đếm ký tự. Lưu nháp nhiều lần; hiển thị "Đã lưu lúc hh:mm".
- **Khi chưa có hồ sơ:** trạng thái rỗng + nút "Tạo hồ sơ".
- **Khi hai người cùng sửa (xung đột phiên bản):** "Hồ sơ đã được cập nhật ở nơi khác. Vui lòng tải lại." — không ghi đè.
- **Khi ghi nhật ký audit thất bại:** thao tác bị hủy, hiển thị lỗi chung (cơ chế an toàn: không đọc và không ghi khi không ghi được audit).
- **Không có:** nút xuất, in, sao chép hàng loạt, chia sẻ.

---

## 6. Đặc tả từng màn hình — Staff

> Staff **không** thấy ghi chú của khách, nội dung hồ sơ tư vấn, ngày sinh khách ở bất kỳ màn nào (BR-050, 058).

### SCR-STF-01 — Tổng quan Staff (FR-DASH-002)
- **Các thẻ đếm bấm được (dẫn tới danh sách đã lọc sẵn):** Lịch hôm nay · Yêu cầu đổi lịch đang chờ · **Lịch cần xử lý** (bị ảnh hưởng do chuyên viên nghỉ, ngày lễ…) · **Giao dịch cần đối soát** · **Lịch tiền mặt đã hoàn thành chưa thu tiền** · **Yêu cầu hoàn tiền đang chờ**.
- **Danh sách nhanh:** 10 lịch hôm nay theo giờ; 5 yêu cầu đổi lịch cũ nhất.
- **Rỗng từng thẻ:** hiện số 0, không ẩn thẻ.

### SCR-STF-02 — Quản lý lịch hẹn (FR-APPT-004, FR-PAY-007)
- **Tìm kiếm:** một ô tìm theo **tên khách hoặc số điện thoại** (≤ 100 ký tự), hoặc mã lịch.
- **Bộ lọc:** trạng thái; ngày (từ–đến); chuyên viên; dịch vụ; **"Cần xử lý"**; **"Đã hoàn thành — chưa thu tiền"** (tiền mặt, COMPLETED, UNPAID); trạng thái thanh toán; phương thức.
- **Cột bảng:** mã lịch, ngày giờ, khách (tên, SĐT), chuyên viên, dịch vụ, chip trạng thái, chip thanh toán (kèm phương thức), cờ "Cần xử lý".
- **Hành động nhanh trên dòng:** "Xem chi tiết"; với dòng thuộc bộ lọc "chưa thu tiền" có nút **"Xác nhận đã thu tiền"** (mở hộp xác nhận hiển thị số tiền).
- **Phân trang** 20 (tối đa 100). **Rỗng:** "Không có lịch hẹn phù hợp bộ lọc."

### SCR-STF-03 — Chi tiết lịch hẹn: điều phối (FR-APPT-005…014, FR-PAY-005/007/008/011, FR-NOTI-005)
- **Khối thông tin:** mã lịch, khách (tên, SĐT, email), chuyên viên, dịch vụ, ngày giờ, nguồn đặt (Online/Staff), người tạo. **Không có** ghi chú khách.
- **Khối thanh toán:** phương thức, số tiền, trạng thái; người thu và thời điểm thu (tiền mặt); hình thức xác nhận (Tự động qua webhook / Thủ công / Tiền mặt); mã giao dịch ngân hàng; yêu cầu hoàn tiền kèm trạng thái.
- **Khối lịch sử trạng thái** (đầy đủ, kể cả lý do nội bộ và người thực hiện).
- **Khối "Giao dịch liên quan":** các giao dịch ngân hàng đã khớp hoặc đang chờ đối soát với lịch này.
- **Các nút theo trạng thái:** xem mục 8. Mỗi nút mở hộp thoại riêng:

| Nút | Hộp thoại |
|---|---|
| Xác nhận đã nhận tiền (thủ công, dự phòng khi webhook lỗi) | Bắt buộc nhập **mã tham chiếu ngân hàng***; ghi chú tùy chọn; cảnh báo "Hãy đối chiếu sao kê trước khi xác nhận". Nếu lịch đã hết hạn và slot không còn: thông báo sẽ tạo yêu cầu hoàn 100% |
| Hủy / Từ chối lịch | Lý do* (≤ 500). Nếu lịch đã thanh toán: nêu "Khách sẽ được hoàn 100%, cần bổ sung thông tin ngân hàng" |
| Đổi lịch (do phòng khám) | Chọn ngày, giờ mới (slot hợp lệ); lý do*; hiển thị lưu ý "Khách được hủy không bị phạt" `[ASSUMPTION]` |
| Duyệt yêu cầu đổi lịch | Hiển thị slot cũ → slot mới; nút "Duyệt" |
| Từ chối yêu cầu đổi lịch | Lý do* |
| Bắt đầu hộ / Đánh dấu vắng mặt | Như SCR-SPE-05, ghi rõ người thực hiện |
| Xác nhận đã thu tiền (tiền mặt, COMPLETED) | Hiển thị số tiền; ghi chú tùy chọn; ghi `collected_by` là Staff đang đăng nhập; **chỉ một lần** |
| Xác nhận đã hoàn tiền | Xem SCR-ADM-17 (cùng hộp thoại) |
| Gửi thông báo thủ công | Nội dung* (≤ 500, không HTML); gửi tới khách; có ghi nhật ký người gửi |

- **Lỗi thường gặp:** hai Staff cùng xử lý một lịch → "Lịch đã được xử lý bởi người khác, vui lòng tải lại" (409).

### SCR-STF-04 — Danh sách khách hàng (FR-CUSTOMER-003)
- **Tìm kiếm:** tên, email, SĐT hoặc mã khách. **Lọc:** trạng thái tài khoản. **Phân trang** 20.
- **Cột:** mã khách, họ tên, email, SĐT, trạng thái tài khoản, số lịch hẹn.
- **Hồ sơ khách (khi bấm vào):** thông tin liên hệ + danh sách lịch hẹn của khách; **không** có ngày sinh, ghi chú, nội dung tư vấn. Việc mở hồ sơ được ghi audit `[RECOMMENDATION]`.
- **Nút:** "Thêm khách hàng" (sang SCR-STF-05); "Đặt lịch hộ" từ hồ sơ.
- **Rỗng:** "Không tìm thấy khách hàng." kèm nút "Thêm khách hàng mới".

### SCR-STF-05 — Tạo và sửa hồ sơ khách (FR-CUSTOMER-002)
- **Trường:** Họ tên* (2–100), Email* (bắt buộc, duy nhất, `[OQ-14]`), Số điện thoại (9–15 chữ số), Ngày sinh* (tuổi ≥ 18), ô tích **"Tôi đã thông báo chính sách quyền riêng tư cho khách"***.
- **Khi sửa:** không đổi được ngày sinh (chỉ Admin).
- **Kết quả:** tạo tài khoản ACTIVE; hệ thống gửi liên kết đặt mật khẩu cho khách. Hiển thị "Đã gửi liên kết đặt mật khẩu tới email của khách."
- **Lỗi:** email trùng → "Khách này đã có tài khoản" + nút "Mở hồ sơ hiện có".

### SCR-STF-06 — Đặt lịch hộ khách (FR-APPT-002)
- **Luồng:** chọn khách (tìm theo tên/SĐT/email hoặc "Thêm khách mới") → dịch vụ → chuyên viên → ngày → giờ → **phương thức thanh toán** (Tiền mặt, hoặc Chuyển khoản nếu khách trả ngay) → xác nhận.
- **Khác với khách tự đặt:** Staff **không có ô ghi chú của khách**; lịch được tạo ở trạng thái CONFIRMED, nguồn "Staff"; ghi người tạo.
- **Lỗi:** như SCR-CUS-03 (slot xung đột, trùng lịch khách…).
- **Kết quả:** trang thành công có mã lịch; khách nhận thông báo.

### SCR-STF-07 — Điều phối lịch bị ảnh hưởng (FR-SCHEDULE-005)
- **Danh sách lịch có cờ "cần xử lý"**, nhóm theo nguyên nhân (chuyên viên nghỉ, ngày lễ, ngừng hoạt động chuyên viên, rút ngắn ca).
- **Cột:** ngày giờ, khách, chuyên viên, nguyên nhân, trạng thái, thời điểm bị gắn cờ.
- **Hành động trên dòng:** "Đổi lịch do phòng khám" hoặc "Hủy do phòng khám" (hộp thoại như SCR-STF-03). Xử lý xong → cờ biến mất và khách được thông báo.
- **Lọc:** chuyên viên, ngày. **Mục tiêu hiển thị:** bộ đếm còn lại; hoàn tất trong ngày làm việc `[TARGET]`.
- **Rỗng:** "Không có lịch nào cần xử lý."

---

## 7. Đặc tả từng màn hình — Admin

> Mọi hành động Admin đều ghi audit. Admin **không** đọc được nội dung hồ sơ tư vấn và `customer_note`.

### SCR-ADM-01 — Tổng quan quản trị (FR-DASH-001, FR-PAY-013)
- **Bộ chọn khoảng ngày** (mặc định 30 ngày; có "Hôm nay").
- **Thẻ số liệu:** tổng khách hàng · chuyên viên đang hoạt động · tổng lịch hẹn · lịch hôm nay · theo trạng thái (Chờ thanh toán, Đã xác nhận, Hoàn thành, Đã hủy, Vắng mặt) · **lịch cần xử lý** · **lịch chờ thanh toán quá hạn** · **doanh thu thuần** (= tổng đã thu trừ tổng đã hoàn) · **số tiền đang chờ hoàn** · **số lịch tiền mặt chờ thu**.
- **Biểu đồ đơn giản:** lịch theo ngày; doanh thu theo phương thức.
- **Quy tắc:** chỉ số liệu tổng hợp, **không có dữ liệu định danh**, **không có chỉ số giả**. Không có dữ liệu → hiển thị 0 và trạng thái rỗng cho biểu đồ.

### SCR-ADM-02 — Danh sách người dùng (FR-ADMIN-001, FR-AUTH-007)
- **Tìm kiếm:** tên, email. **Lọc:** vai trò, trạng thái tài khoản.
- **Cột:** họ tên, email, vai trò, trạng thái (chip), lần đăng nhập cuối.
- **Hành động trên dòng:** Sửa; Khóa/Mở khóa; Vô hiệu hóa/Kích hoạt lại; Gửi liên kết đặt lại mật khẩu.
- **Khi khóa hoặc vô hiệu hóa:** hộp xác nhận + **lý do bắt buộc**; cảnh báo "Mọi phiên đang đăng nhập của người này sẽ bị hủy."
- **Chặn:** không thao tác trên chính mình; không cho vô hiệu hóa **Admin hoạt động cuối cùng** ("Hệ thống phải còn ít nhất một quản trị viên đang hoạt động").
- **Phân trang** 20.

### SCR-ADM-03 — Tạo và sửa người dùng nội bộ (FR-ADMIN-001)
- **Trường:** Họ tên*, Email* (duy nhất), Vai trò* (Staff / Admin; **Specialist tạo ở SCR-ADM-05**), Trạng thái.
- **Quy tắc:** mỗi người đúng một vai trò; đổi vai trò làm hủy phiên của người đó (cảnh báo trong hộp xác nhận); không hạ quyền chính mình nếu là Admin cuối.
- **Kết quả tạo:** gửi liên kết đặt mật khẩu; hiển thị thông báo.

### SCR-ADM-04 — Danh sách chuyên viên (FR-SPECIALIST-003)
- **Cột:** ảnh nhỏ, họ tên, chức danh, số dịch vụ cung cấp, trạng thái (ACTIVE/INACTIVE), điểm đánh giá.
- **Hành động:** Thêm chuyên viên; Sửa; **Kích hoạt / Ngừng hoạt động**.
- **Kích hoạt:** chỉ được khi chuyên viên **có ít nhất một dịch vụ và một ca làm việc**; không đủ → hiển thị "Chuyên viên chưa sẵn sàng" kèm danh sách thiếu gì.
- **Ngừng hoạt động:** hiển thị **xem trước ảnh hưởng** (số lịch tương lai đang giữ slot); lý do bắt buộc; sau xác nhận, các lịch đó bị gắn cờ cần xử lý và Staff/Admin được thông báo.

### SCR-ADM-05 — Tạo và sửa chuyên viên (FR-SPECIALIST-001/004/005)
- **Tab "Thông tin":** Họ tên*, Email* (duy nhất), Chức danh* (≤ 100), Chuyên môn, Giới thiệu (≤ 2000), Số năm kinh nghiệm (0–60), Số giấy phép hành nghề (tùy chọn, nội bộ), **Ghi chú nội bộ** (tùy chọn, nội bộ), Thứ tự hiển thị.
- **Tab "Dịch vụ cung cấp":** danh sách đánh dấu chọn dịch vụ. Bỏ một dịch vụ đang có lịch tương lai → cảnh báo "Các lịch đã đặt vẫn hợp lệ, chỉ chặn đặt mới."
- **Tab "Ảnh":** tải ảnh (jpg, png, webp; ≤ 2 MB; ≤ 4000×4000 px), xem trước, nút "Xóa ảnh" (về ảnh mặc định). Sai định dạng/dung lượng → "Tệp ảnh không hợp lệ."
- **Ghi chú trên giao diện:** các ô "Số giấy phép" và "Ghi chú nội bộ" mang nhãn "Chỉ quản trị xem — không hiển thị công khai".
- **Tạo mới:** chuyên viên ở trạng thái **INACTIVE** cho đến khi đủ dịch vụ và lịch làm việc; hệ thống gửi liên kết đặt mật khẩu.

### SCR-ADM-06 — Danh sách dịch vụ (FR-SERVICE-002)
- **Cột:** tên, danh mục, thời lượng, giá, trạng thái, thứ tự hiển thị.
- **Lọc:** danh mục, trạng thái. **Hành động:** Thêm, Sửa, Đổi trạng thái, Xóa.
- **Xóa:** nếu dịch vụ chưa có lịch hẹn → xóa mềm; nếu đã có → bị chặn, đề xuất "Chuyển sang ngừng hoạt động" ("Dịch vụ đã có lịch hẹn nên không thể xóa").

### SCR-ADM-07 — Tạo và sửa dịch vụ (FR-SERVICE-002)
- **Trường:** Tên* (2–150, duy nhất trong danh mục), Danh mục*, Mô tả (≤ 2000), **Thời lượng (phút)*** (15–180), **Giá (VND)** (số nguyên ≥ 0, tùy chọn), Trạng thái, Thứ tự hiển thị.
- **Lưu ý hiển thị khi sửa thời lượng:** "Thay đổi chỉ áp dụng cho các lịch đặt mới, lịch đã đặt giữ nguyên thời lượng."
- **Lỗi:** trùng tên; giá âm.

### SCR-ADM-08 — Danh mục dịch vụ (FR-SERVICE-003)
- **Danh sách + form nhanh:** Tên* (2–100, duy nhất), Mô tả, Thứ tự hiển thị, Bật/tắt.
- **Tắt danh mục:** dịch vụ bên trong không hiển thị công khai (không tự chuyển INACTIVE) — nêu rõ trong hộp xác nhận.
- **Xóa:** danh mục còn dịch vụ → bị chặn ("Danh mục còn dịch vụ, không thể xóa").

### SCR-ADM-09 — Lịch làm việc và ngày lễ phòng khám (FR-SCHEDULE-001/006)
- **Tab "Lịch làm việc chuyên viên":** chọn chuyên viên → sửa ca hằng tuần (như SCR-SPE-02).
- **Tab "Ngày lễ toàn phòng khám":** danh sách ngày lễ; form thêm: Từ ngày*, Đến ngày*, Lý do*. **Xem trước ảnh hưởng cho tất cả chuyên viên** trước khi lưu; sau lưu, lịch bị ảnh hưởng gắn cờ và Staff/Admin được thông báo.
- **Lỗi:** ngày lễ trùng; ca chồng.

### SCR-ADM-10 — Danh sách lịch hẹn toàn hệ thống (FR-APPT-004)
- Giống SCR-STF-02 nhưng **chỉ đọc theo mặc định** (xem và lọc); thao tác điều phối thực hiện ở SCR-STF-03 nếu Admin có quyền Staff. **Không** hiển thị ghi chú khách.
- **Lọc thêm:** theo nguồn (Online/Staff), theo mã thanh toán.

### SCR-ADM-11 — Kiểm duyệt đánh giá (FR-REVIEW-003)
- **Cột:** thời điểm, chuyên viên, dịch vụ, số sao, nhận xét (rút gọn, bấm xem đủ), trạng thái hiển thị (Hiển thị / Đã ẩn), lý do ẩn.
- **Lọc:** trạng thái, số sao, chuyên viên.
- **Hành động:** "Ẩn" (hộp xác nhận, **lý do bắt buộc**); "Hiện lại". Admin **không sửa nội dung** đánh giá.
- **Ghi chú:** đánh giá đã ẩn không tính vào điểm trung bình công khai.

### SCR-ADM-12 — Thông báo thủ công và nhật ký gửi `[ASSUMPTION]`
- **Nội dung:** danh sách thông báo thủ công đã gửi (người gửi, lịch hẹn, thời điểm, trạng thái email); form gửi thông báo cho khách theo một lịch hẹn (chọn lịch, nội dung ≤ 500, không HTML).
- **Hiển thị trạng thái email:** Đã gửi / Thất bại (đã thử 3 lần) / Không bật.

### SCR-ADM-13 — Báo cáo (FR-REPORT-001/002, FR-PAY-013)
- **Bộ lọc:** khoảng ngày (tối đa 366 ngày), nhóm theo (ngày/tuần/tháng), chuyên viên, dịch vụ.
- **Tab "Vận hành":** lịch theo thời gian; theo trạng thái; theo chuyên viên; theo dịch vụ; tỷ lệ hủy; tỷ lệ hoàn thành; điểm đánh giá trung bình theo chuyên viên và dịch vụ.
- **Tab "Doanh thu và hoàn tiền":** doanh thu thuần theo kỳ (tổng đã thu trừ tổng đã hoàn), tách theo phương thức; số tiền chờ hoàn; số lịch tiền mặt chờ thu.
- **Nút "Xuất CSV":** tối đa 50.000 dòng; vượt → "Vui lòng thu hẹp bộ lọc"; tệp **chỉ gồm số liệu tổng hợp** (không tên, email, SĐT, ghi chú, nội dung tư vấn), chống chèn công thức độc hại vào ô; việc xuất được ghi audit.
- **Rỗng:** "Không có dữ liệu trong khoảng đã chọn."

### SCR-ADM-14 — Nhật ký audit (FR-ADMIN-003)
- **Chỉ đọc.** Bộ lọc: người thực hiện, hành động, loại đối tượng, mã đối tượng, khoảng thời gian (tối đa 90 ngày mỗi lần `[TARGET]`).
- **Cột:** thời điểm, người thực hiện (hoặc "Hệ thống"), vai trò, hành động, đối tượng, giá trị cũ/mới (đã loại trường nhạy cảm), địa chỉ IP.
- **Không có:** nút sửa, xóa. Nội dung hồ sơ tư vấn không bao giờ xuất hiện (chỉ tên trường đã đổi). Xuất tệp là `[COULD]`.

### SCR-ADM-15 — Cấu hình hệ thống (FR-ADMIN-002)
- **Hiển thị** mỗi tham số: tên dễ hiểu, giá trị, giải thích, đơn vị, giá trị mặc định, nút "Khôi phục mặc định".

| Nhóm | Tham số (key) | Mặc định | Quy tắc nhập |
|---|---|---|---|
| Tài khoản | `email_verification_required`, `login_max_attempts`, `login_lock_minutes` | Tắt, 5, 15 | Bật/tắt; số nguyên dương |
| Thông báo | `email_enabled`, `reminder_hours_before` | Tắt, 24 | Số nguyên dương |
| Lịch | `slot_step_minutes`, `buffer_minutes` | 60, 10 | Bước ∈ {15, 30, 45, 60}; đệm 0–60 |
| Đặt lịch | `min_booking_lead_hours`, `max_advance_booking_days`, `max_active_appointments` | 2, 30, 3 | Số nguyên dương |
| Đổi lịch, hủy | `cancellation_deadline_hours` (hạn đổi lịch), `max_reschedule_count` | 24, 2 | Số nguyên dương |
| Buổi tư vấn | `start_early_minutes`, `no_show_grace_minutes` | 15, 15 | Số nguyên dương |
| Đánh giá | `review_window_days` | 30 | Số nguyên dương |
| Thanh toán (CR-001) | `payment_hold_minutes`, tỷ lệ hoàn các mốc, thông tin tài khoản nhận (tên ngân hàng, số tài khoản, chủ tài khoản) | 10; 100/50/0 | Số nguyên dương; **không bao gồm API Key** |

- **Quy tắc:** lưu → ghi audit giá trị cũ và mới; thay đổi chỉ áp dụng cho yêu cầu mới, **không hồi tố** (hiển thị dòng lưu ý). Giá trị sai → lỗi từng ô.
- **API Key SePay:** **không có ô nhập** trên giao diện; cấu hình trong biến môi trường của máy chủ. Màn hình chỉ hiển thị trạng thái "Đã cấu hình / Chưa cấu hình" `[ASSUMPTION]`.

### SCR-ADM-16 — Vai trò và quyền `[ASSUMPTION]`
- **Chỉ đọc ở MVP:** bảng ma trận vai trò × chức năng (đúng như mục 16 của SRS) để Admin tra cứu. Không cho sửa quyền trên giao diện ở MVP.
- **Danh sách người dùng theo vai trò** (số lượng).

### SCR-ADM-17 — Thanh toán và hoàn tiền (mới, CR-001; FR-PAY-005/011/012)
Truy cập: **Staff và Admin**. Màn hình có **ba tab**, mỗi tab đủ bốn trạng thái (đang tải, rỗng, lỗi, thành công).

**Tab 1 — Yêu cầu hoàn tiền (FR-PAY-011)**
- **Lọc:** trạng thái (Chờ xử lý / Đã hoàn), khoảng ngày. **Mặc định:** "Chờ xử lý".
- **Cột:** mã lịch, khách, số tiền đã trả, **tỷ lệ hoàn**, **số tiền hoàn**, ngân hàng, số tài khoản **che còn 4 số cuối**, ngày tạo yêu cầu, trạng thái.
- **Khi bấm vào một yêu cầu (ngăn bên hoặc trang chi tiết):** hiển thị **đầy đủ** thông tin tài khoản nhận (tên ngân hàng, số tài khoản, tên chủ tài khoản — chỉ người đang xử lý thấy) kèm nút sao chép; nêu rõ số tiền cần chuyển.
- **Thiếu thông tin ngân hàng:** hiển thị "Chưa có thông tin ngân hàng", nút "Nhắc khách bổ sung" và **vô hiệu nút xác nhận**.
- **Nút "Xác nhận đã hoàn tiền":** hộp thoại nhập **mã giao dịch chuyển khoản** (khuyến nghị), ghi chú; nội dung "Hãy chắc chắn bạn đã chuyển … đ tới tài khoản trên"; sau xác nhận → "Đã hoàn tiền", ghi người xử lý và thời điểm, thông tin ngân hàng được che hoặc xóa, khách nhận thông báo. **Không hoàn tác.**
- **Khuyến nghị:** người tạo yêu cầu khác người xác nhận khi có từ hai nhân sự trở lên.

**Tab 2 — Đối soát giao dịch (FR-PAY-005, FR-PAY-012)**
- **Danh sách giao dịch ngân hàng nhận được** (từ webhook): thời điểm, ngân hàng, số tiền, nội dung chuyển khoản, **loại khớp**: `UNMATCHED` (không tìm thấy mã lịch), `AMOUNT_MISMATCH` (sai số tiền), `LATE` (đến sau khi lịch hết hạn), đã khớp bình thường; trạng thái xử lý.
- **Lọc:** loại khớp (mặc định "Cần xử lý"), khoảng ngày, mã thanh toán.
- **Hành động cho giao dịch bất thường** (hộp thoại chọn một):
  1. **Liên kết với lịch và xác nhận** (chọn lịch; kiểm tra lại slot).
  2. **Tạo yêu cầu hoàn tiền** (nêu lý do).
  3. **Bỏ qua** (lý do bắt buộc).
- **Thanh toán muộn:** nếu slot còn trống, hệ thống tự kích hoạt lại lịch; nếu slot đã có người đặt, hệ thống tạo yêu cầu hoàn 100%. Giao diện hiển thị kết quả kèm giải thích.
- **Xử lý trùng:** hai Staff mở cùng giao dịch → người xử lý sau nhận "Giao dịch đã được xử lý" (409).

**Tab 3 — Thanh toán (FR-PAY-012)**
- **Danh sách các khoản thanh toán:** mã lịch, khách, phương thức, số tiền, trạng thái thanh toán, hình thức xác nhận (Tự động/Thủ công/Tiền mặt), người thu (tiền mặt), thời điểm.
- **Lọc:** trạng thái, phương thức, khoảng ngày, mã thanh toán. **Xuất CSV số liệu tổng hợp** (không dữ liệu định danh).
- Số tài khoản của người gửi và người nhận **che bớt**.

---

## 8. Ma trận hành động theo trạng thái lịch hẹn và vai trò

Quy ước: ✔ hiển thị nút và bật · ○ hiển thị nhưng vô hiệu (kèm lý do) · — không hiển thị.

| Trạng thái lịch | Customer (SCR-CUS-05) | Specialist (SCR-SPE-05) | Staff / Admin (SCR-STF-03) |
|---|---|---|---|
| **PENDING** (chờ thanh toán) | ✔ Thanh toán (nếu còn hạn) · ✔ Hủy · ✔ Sửa ghi chú | — | ✔ Xác nhận đã nhận tiền (thủ công) · ✔ Hủy/Từ chối (lý do) · ✔ Gửi thông báo |
| **CONFIRMED** | ✔ Yêu cầu đổi lịch (≥ 24 giờ, < 2 lần, nếu không ○) · ✔ Hủy (kèm pop-up hoàn tiền) · ✔ Sửa ghi chú | ✔ Bắt đầu (từ 15 phút trước giờ hẹn) · ✔ Vắng mặt (sau 15 phút) | ✔ Đổi lịch · ✔ Hủy · ✔ Bắt đầu hộ · ✔ Vắng mặt · ✔ Gửi thông báo |
| **RESCHEDULE_REQUESTED** | ✔ Rút yêu cầu · ✔ Hủy | — | ✔ Duyệt · ✔ Từ chối (lý do) · ✔ Hủy · ✔ Đổi lịch (xử lý như từ chối rồi đổi) |
| **RESCHEDULED** | — (xem lịch mới liên kết) | — | — (xem liên kết hai chiều) |
| **IN_PROGRESS** | — | ✔ Hoàn thành · ✔ Ghi hồ sơ tư vấn | ✔ Xem (không hủy được) |
| **COMPLETED** | ✔ Đánh giá (≤ 30 ngày, chưa đánh giá) | ✔ Xem/sửa hồ sơ tư vấn | ✔ **Xác nhận đã thu tiền** (nếu CASH + UNPAID, chỉ Staff/Admin, một lần) |
| **CANCELLED_BY_CUSTOMER** | ✔ Xem tiến độ hoàn tiền | — | ✔ Xem · xử lý hoàn ở SCR-ADM-17 |
| **CANCELLED_BY_CLINIC** | ✔ Bổ sung thông tin ngân hàng (nếu đã trả và chưa có) | — | ✔ Xem · xử lý hoàn ở SCR-ADM-17 |
| **NO_SHOW** | Xem | — | Xem (không hoàn tiền) |

---

## 9. Truy vết: Chức năng → Màn hình

| Nhóm FR | Màn hình |
|---|---|
| FR-AUTH-001…007 | SCR-PUB-06…10, SCR-COM-02, SCR-ADM-02 |
| FR-CUSTOMER-001…003 | SCR-CUS-02, SCR-STF-04, SCR-STF-05 |
| FR-SPECIALIST-001…005 | SCR-ADM-04, SCR-ADM-05, SCR-PUB-04/05 |
| FR-SERVICE-001…003 | SCR-PUB-02/03, SCR-ADM-06/07/08 |
| FR-SCHEDULE-001…006 | SCR-SPE-02/03, SCR-ADM-09, SCR-CUS-03, SCR-STF-06/07 |
| FR-APPT-001…014 | SCR-CUS-03…06, SCR-SPE-04/05, SCR-STF-02/03/06, SCR-ADM-10 |
| FR-PAY-001…013 | SCR-CUS-03, SCR-CUS-05, SCR-STF-02/03, SCR-ADM-01/13/17 |
| FR-CONSULT-001…003 | SCR-SPE-06, SCR-ADM-14 |
| FR-NOTI-001…005 | SCR-COM-01, SCR-STF-03, SCR-ADM-12 |
| FR-REVIEW-001…003 | SCR-CUS-07, SCR-PUB-05, SCR-ADM-11 |
| FR-ADMIN-001…003 | SCR-ADM-02/03/14/15/16 |
| FR-DASH-001/002 | SCR-ADM-01, SCR-CUS-01, SCR-SPE-01, SCR-STF-01 |
| FR-REPORT-001/002 | SCR-ADM-13 |

---

## 10. Điểm cần bạn xác nhận (câu hỏi mở về giao diện)

| # | Nội dung | Gợi ý mặc định |
|---|---|---|
| UI-Q1 | Tên và nội dung các màn hình đánh dấu `[ASSUMPTION]` (SCR-PUB-01, COM-03/04, ADM-12, ADM-16) | Giữ như đã mô tả |
| UI-Q2 | Nhãn tiếng Việt cho 9 trạng thái lịch hẹn (mục 1.8) | Giữ như bảng |
| UI-Q3 | Thông báo an toàn không dành cho tình huống khẩn cấp: nội dung và số điện thoại hỗ trợ `[OQ-18]` | Phòng khám cung cấp |
| UI-Q4 | Thời gian dự kiến nhận hoàn tiền hiển thị cho khách (ví dụ 3–7 ngày làm việc) | Ghi "3–7 ngày làm việc (bản demo không chuyển tiền thật)" |
| UI-Q5 | Specialist có xem được số tiền và trạng thái thanh toán của lịch không | Không hiển thị |
| UI-Q6 | Có cho Admin xem chi tiết lịch ở SCR-ADM-10 và thao tác trực tiếp hay chỉ xem | Chỉ xem, thao tác qua SCR-STF-03 |
| UI-Q7 | Phong cách trực quan (màu chủ đạo, logo, phông chữ) | Chưa chốt, làm ở bước wireframe |
| UI-Q8 | Có cần ngôn ngữ thứ hai (tiếng Anh) không | Không, chỉ tiếng Việt |

---

## 11. Danh mục kiểm tra khi bàn giao cho thiết kế và lập trình

- [ ] Mỗi trong 51 màn hình có wireframe và đối chiếu được với mục tương ứng ở trên.
- [ ] Mọi màn hình dữ liệu có đủ 4 trạng thái: đang tải, rỗng, lỗi, thành công.
- [ ] Mọi thao tác không hoàn tác có hộp xác nhận nêu rõ hậu quả.
- [ ] Không màn hình nào hiển thị: ghi chú khách (với Staff/Admin), nội dung tư vấn (với ai ngoài chuyên viên phụ trách), `license_number`/`internal_note`/email chuyên viên ở trang công khai, API Key.
- [ ] Số tài khoản ngân hàng che ở danh sách; chỉ hiện đủ cho người xử lý hoàn tiền.
- [ ] Pop-up hủy lịch hiển thị đúng 3 mốc hoàn tiền và xử lý trường hợp số tiền đổi giữa chừng.
- [ ] Màn VietQR có đồng hồ đếm ngược theo giờ máy chủ, tự cập nhật, xử lý hết hạn và lỗi tải QR.
- [ ] Thông điệp tiền mặt đúng nguyên văn: "Đặt lịch thành công! Vui lòng đến trước 10 phút để thực hiện dịch vụ."
- [ ] Responsive trên điện thoại; chip trạng thái có chữ, không chỉ màu.
- [ ] **Không có màn hình chatbot** trong MVP (chỉ nằm ở mục "Future Extension").
# QUY ĐỊNH BẢNG MÃ MÀU (DESIGN SYSTEM - COLOR PALETTE)
**Dự án:** Xây dựng ứng dụng đặt lịch tư vấn sức khỏe tinh thần và quản lý phòng khám tâm lý (MHCA)  
**Phiên bản:** 1.0  
**Phong cách:** Soft Medical / Healing Minimalist  

---

## 1. Bảng màu thương hiệu & Giao diện chính (Brand & Layout Colors)

| Thành phần UI | Tên màu | Mã HEX | Mã RGB | Ứng dụng & Ý nghĩa |
| :--- | :--- | :--- | :--- | :--- |
| **Primary (Chủ đạo)** | Deep Teal | `#1D4B43` | `rgb(29, 75, 67)` | Header, tiêu đề lớn, nút CTA chính. Tạo cảm giác tin cậy, vững chãi, chuyên nghiệp. |
| **Primary Hover** | Dark Teal | `#14352F` | `rgb(20, 53, 47)` | Trạng thái hover/active của các nút bấm thuộc màu primary. |
| **Secondary (Phụ)** | Sage Green | `#709782` | `rgb(112, 151, 130)` | Khung thẻ chuyên viên, hình minh họa, icon phụ. Tạo sự thư thái, phục hồi. |
| **Secondary Light** | Muted Sage | `#E8F0EC` | `rgb(232, 240, 236)` | Nền thẻ (card), background khối thông tin phụ. |
| **Background (Nền)** | Warm Off-White | `#F7FAFC` | `rgb(247, 250, 252)` | Nền toàn bộ trang web (thay thế màu trắng thuần `#FFFFFF` để tránh chói mắt). |
| **Surface (Bề mặt)** | Pure White | `#FFFFFF` | `rgb(255, 255, 255)` | Nền các modal, dropdown, form nhập liệu để phân lớp với background. |
| **Text Primary** | Dark Slate | `#1A202C` | `rgb(26, 32, 44)` | Văn bản chính, tiêu đề, văn bản nhập liệu. Đảm bảo tương phản cao (WCAG AA). |
| **Text Secondary** | Muted Slate | `#4A5568` | `rgb(74, 85, 104)` | Nhãn phụ, mô tả ngắn, placeholder, breadcrumb. |

---

## 2. Mã màu Chip Trạng thái Lịch hẹn & Thanh toán (Status Palette - Mục 1.8)

> **Nguyên tắc:** 100% chip trạng thái phải chứa văn bản đi kèm màu sắc, không dùng màu làm dấu hiệu duy nhất.

### 2.1. Trạng thái Lịch hẹn

| Trạng thái (Code) | Nhãn hiển thị | Màu chữ (Text) | Màu nền (Background) | Viền (Border) |
| :--- | :--- | :--- | :--- | :--- |
| `PENDING` | Chờ thanh toán | `#B45309` | `#FEF3C7` | `#FDE68A` |
| `CONFIRMED` | Đã xác nhận | `#1D4ED8` | `#EFF6FF` | `#BFDBFE` |
| `RESCHEDULE_REQUESTED` | Chờ duyệt đổi lịch | `#D97706` | `#FFFBEB` | `#FDE68A` |
| `RESCHEDULED` | Đã đổi lịch | `#4B5563` | `#F3F4F6` | `#E5E7EB` |
| `IN_PROGRESS` | Đang diễn ra | `#15803D` | `#F0FDF4` | `#BBF7D0` |
| `COMPLETED` | Đã hoàn thành | `#166534` | `#DCFCE7` | `#86EFAC` |
| `CANCELLED_BY_CUSTOMER` | Khách đã hủy | `#6B7280` | `#F9FAFB` | `#E5E7EB` |
| `CANCELLED_BY_CLINIC` | Phòng khám đã hủy | `#B91C1C` | `#FEF2F2` | `#FCA5A5` |
| `NO_SHOW` | Vắng mặt | `#991B1B` | `#FEE2E2` | `#F87171` |

### 2.2. Trạng thái Thanh toán

| Trạng thái (Code) | Nhãn hiển thị | Màu chữ (Text) | Màu nền (Background) | Viền (Border) |
| :--- | :--- | :--- | :--- | :--- |
| `UNPAID` | Chưa thanh toán | `#B45309` | `#FEF3C7` | `#FDE68A` |
| `PAID` | Đã thanh toán | `#15803D` | `#F0FDF4` | `#BBF7D0` |
| `REFUND_PENDING` | Chờ hoàn tiền | `#C2410C` | `#FFEDD5` | `#FED7AA` |
| `REFUNDED` | Đã hoàn tiền | `#374151` | `#F3F4F6` | `#D1D5DB` |
| `NOT_REQUIRED` | Không cần thanh toán | `#4B5563` | `#F9FAFB` | `#E5E7EB` |

---

## 3. Mã màu Cảnh báo & Hệ thống (System Feedback Colors)

| Loại thông báo | Màu chữ | Màu nền | Ý nghĩa & Ứng dụng |
| :--- | :--- | :--- | :--- |
| **Emergency Banner** | `#7C2D12` | `#FFEDD5` | Thanh cảnh báo an toàn khẩn cấp 115 (đầu trang). Tone cam dịu gây chú ý nhưng không tạo tâm lý hoảng loạn. |
| **Success Toast** | `#14532D` | `#F0FDF4` | Thông báo thao tác thành công (Đặt lịch thành công, lưu thông tin thành công). |
| **Error / Alert** | `#7F1D1D` | `#FEF2F2` | Thông báo lỗi form (Validate), hết hạn giữ chỗ VietQR, lỗi 403/404/500. |
| **Info Alert** | `#1E40AF` | `#EFF6FF` | Khung thông tin lưu ý (mã hóa hồ sơ tư vấn, hướng dẫn thao tác). |

---

## 4. Cấu hình Tailwind CSS (Dành cho Developer)

Sao chép đoạn mã cấu hình dưới đây vào tệp `tailwind.config.js`:

```javascript
module.exports = {
  theme: {
    extend: {
      colors: {
        mhca: {
          primary: '#1D4B43',
          'primary-hover': '#14352F',
          secondary: '#709782',
          'secondary-light': '#E8F0EC',
          bg: '#F7FAFC',
          surface: '#FFFFFF',
          text: '#1A202C',
          'text-muted': '#4A5568',
        },
        status: {
          pending: { text: '#B45309', bg: '#FEF3C7', border: '#FDE68A' },
          confirmed: { text: '#1D4ED8', bg: '#EFF6FF', border: '#BFDBFE' },
          completed: { text: '#166534', bg: '#DCFCE7', border: '#86EFAC' },
          cancelled: { text: '#B91C1C', bg: '#FEF2F2', border: '#FCA5A5' },
        }
      }
    }
  }
}