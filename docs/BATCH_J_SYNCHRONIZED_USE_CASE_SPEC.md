> **Tài liệu lịch sử Batch J.** Nội dung bên dưới phản ánh giai đoạn lập tài liệu trước đây; số kiểm tra và trạng thái lúc đó không phải kết luận cho phiên bản hiện tại. Xem [bản đối chiếu hiện thực ngày 22/10/2026](scope_and_implementation.md). Các quyết định nghiệp vụ chưa được đáp ứng đầy đủ vẫn là yêu cầu cần xử lý, không tự đóng theo kết quả kiểm tra kỹ thuật.


# BATCH J — SYNCHRONIZED USE CASE SPECIFICATION

**Nguồn:**`<span>_ai_source/_uc_extract.txt</span>`
**Quyết định áp dụng:** DEC-01 đến DEC-09, APPROVED / DECIDED BY PROJECT OWNER
**Phạm vi:** documentation-only; tài liệu này là bản đồng bộ, không thay thế nguồn gốc.

> Quy tắc: nội dung gốc được giữ thuật ngữ và cấu trúc UC. Chỉ bổ sung/chỉnh các điểm được DEC phê duyệt. Điểm chưa đủ đặc tả được đánh dấu  **[CẦN BỔ SUNG ĐẶC TẢ]** .

## UC08 — Thành lập hội đồng coi thi

### Đồng bộ tiền điều kiện

* Người dùng đã đăng nhập thành công.
* Sở GD&ĐT đã thiết lập kế hoạch tuyển sinh.
* Danh sách điểm thi đã được xác định.
* **Bổ sung theo DEC-08:** điểm thi được sử dụng để thành lập Hội đồng coi thi phải được kiểm tra thuộc đúng kỳ tuyển sinh đang được xử lý. Nếu không xác định được kỳ tuyển sinh duy nhất theo quy tắc chính thức, thao tác phải bị từ chối theo DEC-01.

### Đồng bộ Basic Flow

Tại bước kiểm tra tính hợp lệ của thông tin hội đồng, hệ thống kiểm tra điểm thi và kỳ tuyển sinh tương ứng. **Không bổ sung điều kiện **`<strong><span>schools.active</span></strong>`.

### Lịch sử

Thông tin hội đồng đã thành lập phải giữ khả năng truy vết lịch sử; không suy diễn việc trường inactive thành điều kiện hủy dữ liệu hội đồng.

**[CẦN BỔ SUNG ĐẶC TẢ]:** hành vi/UI cụ thể khi điểm thi không thuộc kỳ tuyển sinh được xác định hoặc khi không xác định được duy nhất kỳ hiện tại.

## UC09 — Thành lập hội đồng chấm thi

Giữ nguyên các tiền điều kiện, hậu điều kiện và flow nguồn về kế hoạch tuyển sinh, kỳ thi, môn thi và cán bộ/giáo viên. **Không bổ sung điều kiện **`<strong><span>schools.active</span></strong>`** hoặc điều kiện trạng thái trường.** Nếu có quan hệ gián tiếp qua dữ liệu hội đồng/địa điểm thi thì đó không phải direct business dependency nếu chưa có căn cứ nghiệp vụ.

**[CẦN BỔ SUNG ĐẶC TẢ]:** nếu về sau cần ràng buộc trường theo nghiệp vụ UC09 thì phải có căn cứ riêng; DEC-09 không phê duyệt ràng buộc đó.

## UC10 — Thành lập hội đồng thi phúc khảo

Giữ nguyên điều kiện kết quả thi đã công bố, đăng ký phúc khảo và cán bộ/giáo viên. Không bổ sung điều kiện `<span>schools.active</span>`. Quan hệ gián tiếp, nếu có, không được biến thành direct business dependency theo DEC-09.

**[CẦN BỔ SUNG ĐẶC TẢ]:** không có quy tắc trường được phê duyệt để bổ sung vào UC10.

## UC11 — Thành lập hội đồng ra đề

Giữ nguyên điều kiện kế hoạch tuyển sinh/phương thức thi và cán bộ/giáo viên. Không bổ sung điều kiện `<span>schools.active</span>` hoặc trạng thái trường theo DEC-09.

**[CẦN BỔ SUNG ĐẶC TẢ]:** nếu cần dependency trường trong tương lai phải có căn cứ nghiệp vụ riêng.

## UC13 — Duyệt yêu cầu điều chỉnh hồ sơ

### Đồng bộ Basic Flow

Giữ nguyên bước cán bộ xem thông tin cũ, thông tin đề xuất mới, lý do và file minh chứng. Tại bước kiểm tra tính hợp lệ trước khi nhấn “Duyệt”, hệ thống phải kiểm tra lại trường mới được tham chiếu bởi hồ sơ là **trường THCS** và đang **active** theo DEC-04, DEC-05, DEC-06.

Nếu trường mới không còn hợp lệ tại thời điểm duyệt, hệ thống  **không áp dụng thay đổi** .

### Exception/Status gap

Nguồn chỉ có các trạng thái `<span>Chờ duyệt</span>`, `<span>Đã duyệt</span>`, `<span>Từ chối</span>`; chưa có trạng thái/exception riêng cho trường hợp invalid tại approval.  **[CẦN BỔ SUNG ĐẶC TẢ]** . Không tự tạo trạng thái mới trong Batch J.

## UC14 — Gửi yêu cầu điều chỉnh hồ sơ

### Đồng bộ Basic Flow

Tại bước hệ thống kiểm tra tính đầy đủ và hợp lệ của dữ liệu, nếu `<span>thông tin mới</span>` có thay đổi trường THCS thì hệ thống phải kiểm tra trường được chọn là **THCS** và đang **active** tại thời điểm gửi theo DEC-04, DEC-05, DEC-06.

Yêu cầu vẫn được lưu với trạng thái “Chờ duyệt” chỉ khi các điều kiện hiện hành hợp lệ.

**[CẦN BỔ SUNG ĐẶC TẢ]:** hành vi cụ thể khi trường hợp hợp lệ lúc gửi nhưng invalid trước khi UC13 duyệt được xử lý ở UC13; nguồn chưa quy định thông báo/exception tương ứng.

## UC17 — Thêm trường THCS, THPT

Giữ nguyên flow nguồn về thêm nhiều trường, Excel, kiểm tra dữ liệu, phát sinh mã trường và lưu tất cả. Không tự thêm dependency current-period hoặc `<span>schools.active</span>` vào flow này.

**Trạng thái đồng bộ:** DONE về phạm vi DEC-01..09.

## UC18 — Cập nhật thông tin trường THCS, THPT

Giữ nguyên flow nguồn cho phép sửa Tên trường, Cấp học, Địa chỉ, Số điện thoại và Email liên hệ. Không tự thêm quy tắc current-period hoặc school active chưa được DEC phê duyệt.

Tuy nhiên DEC-04 yêu cầu `<span>candidates.middle_school_id</span>` phải tham chiếu trường THCS. Vì UC18 cho phép đổi `<span>capHoc</span>`, nguồn chưa quy định xử lý khi đổi cấp học của trường đang được tham chiếu làm trường THCS của thí sinh. **[CẦN BỔ SUNG ĐẶC TẢ]** trước khi coi quy tắc này đã đầy đủ.

## UC19 — Xóa trường THCS, THPT

### Tiền điều kiện đồng bộ

* Đăng nhập thành công.
* Hệ thống phải xác định được **duy nhất kỳ tuyển sinh hiện tại** theo cấu hình/trạng thái nghiệp vụ chính thức; không suy ra từ ID lớn nhất, năm lớn nhất, ngày hiện tại hoặc kỳ tạo gần nhất. Nếu không xác định được duy nhất, thao tác yêu cầu kỳ hiện tại bị từ chối.

### Basic Flow đồng bộ

Sau bước xác nhận xóa, hệ thống không mặc nhiên hard-delete trường. Trước tiên phải đánh giá dependency nghiệp vụ trong kỳ tương ứng, gồm các quan hệ đã được DEC-02 yêu cầu rà soát như `<span>admission_quotas.school_id</span>`, `<span>aspirations.school_id</span>`, `<span>admission_results.school_id</span>`, `<span>exam_sites.school_id</span>` và dữ liệu địa điểm thi liên quan, `<span>enrollment_confirmations.school_id</span>`, cùng quan hệ khác chỉ khi được xác minh. FK trực tiếp không mặc nhiên là dependency nghiệp vụ.

Nếu trường chỉ còn dữ liệu lịch sử hoặc hard-delete làm mất khả năng truy vết,  **không hard-delete** ; phải bảo toàn dữ liệu lịch sử theo DEC-03.

### Exception Flow đồng bộ

* Trường có dependency nghiệp vụ trong kỳ tương ứng: không thực hiện xóa và giữ nguyên dữ liệu.
* Trường chỉ còn dữ liệu lịch sử: không hard-delete nếu làm mất traceability.
* Không xác định được duy nhất kỳ hiện tại: từ chối thao tác.

**[CẦN BỔ SUNG ĐẶC TẢ]:** nguồn chưa quy định trạng thái/nghiệp vụ cụ thể thay thế cho hard-delete trong trường hợp chỉ còn dữ liệu lịch sử; không tự tạo trạng thái mới.

## UC31 — Thêm thí sinh/Học sinh

Tại bước kiểm tra tính đầy đủ và hợp lệ, `<span>maTruongTHCS</span>` phải tham chiếu một trường có cấp học **THCS** theo DEC-04. Trường **inactive** không được chọn cho thao tác tạo mới theo DEC-05.

Không thêm `<span>admission_period_id</span>` vào lớp Thí sinh trong bản đồng bộ này theo DEC-07.

## UC32 — Sửa thí sinh/Học sinh

Trong danh sách thuộc tính được phép sửa, `<span>maTruongTHCS</span>` phải tiếp tục đáp ứng điều kiện: trường được chọn là **THCS** và đang **active** nếu đây là lựa chọn/thay thế mới. Tham chiếu lịch sử không tự động NULL/xóa chỉ vì trường inactive.

Không thêm `<span>admission_period_id</span>` vào Thí sinh.

**[CẦN BỔ SUNG ĐẶC TẢ]:** nguồn chưa quy định rõ cách phân biệt thao tác thay thế mới với việc chỉ hiển thị/giữ tham chiếu lịch sử khi trường inactive.

## UC33 — Xóa thí sinh/Học sinh

Giữ nguyên flow nguồn về xóa, hủy, lỗi và dữ liệu liên quan. Theo DEC-07, không coi `<span>maTruongTHCS</span>`/`<span>candidates.middle_school_id</span>` là dependency theo kỳ khi chưa có liên kết kỳ của candidate được xác minh. Không thêm `<span>admission_period_id</span>` vào Thí sinh.

**Trạng thái:** PARTIAL — quy tắc kỳ của candidate còn thiếu bằng chứng mô hình dữ liệu.

## Quan hệ với các UC liên quan

* **Nguyện vọng/kết quả/xác nhận nhập học:** các quan hệ `<span>school_id</span>` trong kỳ tuyển sinh hiện tại phải được xem xét khi xử lý UC19 theo DEC-02; không đồng nhất mọi FK với business dependency.
* **Dữ liệu lịch sử:** phải được phân biệt với dependency của kỳ hiện tại và được bảo toàn theo DEC-03.
* **Candidate:** không tự suy ra kỳ từ candidate và không thêm `<span>admission_period_id</span>` theo DEC-07.

## Trạng thái tổng thể

BJ-01..BJ-06 vẫn **PARTIAL** theo `<span>BATCH_J_IMPACT_TRACE.md</span>`. Documentation sync không làm chúng thành RESOLVED. Fresh regression chưa chạy; Phase 0.9 chưa hoàn tất; Phase 1 chưa bắt đầu.
