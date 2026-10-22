> **Tài liệu lịch sử Batch J.** Nội dung bên dưới phản ánh giai đoạn lập tài liệu trước đây; số kiểm tra và trạng thái lúc đó không phải kết luận cho phiên bản hiện tại. Xem [bản đối chiếu hiện thực ngày 22/10/2026](scope_and_implementation.md). Các quyết định nghiệp vụ chưa được đáp ứng đầy đủ vẫn là yêu cầu cần xử lý, không tự đóng theo kết quả kiểm tra kỹ thuật.

# BATCH J — IMPACT TRACE / BLOCKER STATUS

**Phạm vi:** đồng bộ đặc tả tài liệu theo DEC-01..09; không triển khai runtime.

**Cập nhật trạng thái:** 08/10/2026. Batch I/UC03 đã đạt `46 PASS, 0 FAIL,
0 BLOCKED`; đây là kết quả runtime độc lập và không giải quyết các blocker
BJ-01..BJ-06. Batch G1 vẫn có TEST 11 `BLOCKED` do thiếu failure hook rollback
hợp lệ.

## UC19 / BJ-01..03

| Mã   | UC   | Nội dung đối chiếu                                                                                                                                                                                                                                                                      | Căn cứ nguồn/DEC | Trạng thái      |
| ----- | ---- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------- | ----------------- |
| BJ-01 | UC19 | Nguồn chỉ ghi “Đăng nhập thành công”; chưa định nghĩa duy nhất kỳ hiện tại.                                                                                                                                                                                                | UC19 nguồn; DEC-01 | **PARTIAL** |
| BJ-02 | UC19 | Nguồn có exception “dữ liệu liên quan trong kỳ tuyển sinh hiện tại” nhưng chưa định nghĩa dependency. Phải rà soát quotas, aspirations, admission_results, exam_sites và dữ liệu địa điểm thi, enrollment_confirmations; phân biệt FK với business dependency. | UC19 nguồn; DEC-02 | **PARTIAL** |
| BJ-03 | UC19 | Nguồn yêu cầu xóa trường khỏi CSDL. Điều này phải bị giới hạn nếu làm mất lịch sử/traceability; historical-only phải được bảo toàn. Cách biểu diễn inactive/ngừng tuyển sinh cụ thể chưa được nguồn quy định.                                        | UC19 nguồn; DEC-03 | **PARTIAL** |

### Đồng bộ UC19

* **Nội dung nguồn:** bước 6 ghi “Xóa thông tin của trường tương ứng khỏi CSDL”; exception 6.1 ghi trường không thể xóa nếu có dữ liệu liên quan trong kỳ tuyển sinh hiện tại.
* **Mâu thuẫn/thiếu:** chưa có định nghĩa chính thức về current period, dependency set và trường hợp chỉ còn dữ liệu lịch sử.
* **Câu chữ đồng bộ:** “Hệ thống xác định kỳ tuyển sinh hiện tại theo cấu hình/trạng thái nghiệp vụ chính thức. Nếu không xác định được duy nhất kỳ hiện tại thì từ chối thao tác. Trước khi xử lý xóa/ngừng sử dụng trường, hệ thống kiểm tra các dependency nghiệp vụ đã được xác định trong kỳ tương ứng. Nếu trường chỉ còn dữ liệu lịch sử hoặc việc xóa làm mất khả năng truy vết thì không hard-delete dữ liệu trường; bảo toàn dữ liệu lịch sử. [CẦN BỔ SUNG ĐẶC TẢ] về trạng thái/nghiệp vụ cụ thể dùng để thay cho hard-delete nếu nguồn chưa quy định.”

## UC08–11 / BJ-04

| Mã    | UC   | Nội dung đối chiếu                                                                                                                                               | Căn cứ nguồn/DEC | Trạng thái      |
| ------ | ---- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------- | ----------------- |
| BJ-04a | UC08 | Nguồn yêu cầu danh sách điểm thi nhưng chưa nêu kiểm tra kỳ. Bổ sung kiểm tra điểm thi thuộc đúng kỳ tuyển sinh; không bổ sung schools.active. | UC08 nguồn; DEC-08 | **PARTIAL** |
| BJ-04b | UC09 | Nguồn dựa trên kế hoạch, kỳ thi, cán bộ/môn; không có điều kiện trường. Không thêm schools.active.                                                 | UC09 nguồn; DEC-09 | **PARTIAL** |
| BJ-04c | UC10 | Nguồn dựa trên kết quả thi, phúc khảo, cán bộ/môn; không có điều kiện trường. Không thêm schools.active.                                          | UC10 nguồn; DEC-09 | **PARTIAL** |
| BJ-04d | UC11 | Nguồn dựa trên kế hoạch/phương thức thi và cán bộ; không có điều kiện trường. Không thêm schools.active.                                         | UC11 nguồn; DEC-09 | **PARTIAL** |

**Phân biệt quan hệ:** UC08 có đường dẫn schema gián tiếp `<span>councils → exam_sites → exam_sites.school_id → schools</span>`; đây không được nâng thành direct business dependency về trạng thái trường. UC09–11 không có direct school business dependency được nguồn chứng minh.

## UC13–14 / BJ-06

| Mã    | UC   | Nội dung đối chiếu                                                                                                                                         | Căn cứ nguồn/DEC       | Trạng thái      |
| ------ | ---- | -------------------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------- | ----------------- |
| BJ-06a | UC14 | Nguồn chỉ nói kiểm tra tính đầy đủ/hợp lệ. Bổ sung: trường mới phải là THCS và active tại lúc gửi yêu cầu.                              | UC14 nguồn; DEC-04,05,06 | **PARTIAL** |
| BJ-06b | UC13 | Nguồn chỉ nói cán bộ kiểm tra hợp lệ rồi duyệt. Bổ sung revalidate trường mới là THCS và active tại approval; nếu invalid thì không apply. | UC13 nguồn; DEC-04,05,06 | **PARTIAL** |

**Khoảng trống:** nguồn có các trạng thái Chờ duyệt/Đã duyệt/Từ chối nhưng chưa có trạng thái/exception riêng cho trường hợp yêu cầu hợp lệ lúc gửi nhưng invalid lúc duyệt. Đánh dấu  **[CẦN BỔ SUNG ĐẶC TẢ]** ; không tự tạo trạng thái mới.

## UC31–33 / BJ-05

| Mã    | UC   | Nội dung đối chiếu                                                                                                                                             | Căn cứ nguồn/DEC    | Trạng thái      |
| ------ | ---- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------ | ---------------------- | ----------------- |
| BJ-05a | UC31 | Nguồn có`<span>maTruongTHCS</span>`nhưng chưa quy định kiểm tra cấp học/trạng thái. Bổ sung chỉ nhận trường THCS active cho thao tác mới.      | UC31 nguồn; DEC-04,05 | **PARTIAL** |
| BJ-05b | UC32 | Nguồn cho phép sửa`<span>maTruongTHCS</span>`; bổ sung không chọn trường inactive hoặc không phải THCS cho thay thế mới.                            | UC32 nguồn; DEC-04,05 | **PARTIAL** |
| BJ-05c | UC33 | Nguồn xóa thí sinh và xử lý dữ liệu liên quan; không có admission period cho candidate. Không coi`<span>maTruongTHCS</span>`là dependency theo kỳ. | UC33 nguồn; DEC-07    | **PARTIAL** |

## UC17–18 và quan hệ trường

| UC   | Nội dung                                                                                                                                                             | Căn cứ                                                          | Trạng thái      |
| ---- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ----------------------------------------------------------------- | ----------------- |
| UC17 | Quản lý tạo trường; nguồn đã có cấp học và mã trường. Không tự thêm dependency current-period/active ngoài nghiệp vụ tạo trường.              | UC17 nguồn; DEC-04/05 không trực tiếp điều chỉnh flow này | **DONE**    |
| UC18 | Nguồn cho phép đổi cấp học. Nếu trường đang được tham chiếu làm`<span>middle_school_id</span>`, đặc tả chưa nói xử lý việc đổi THCS↔THPT. | UC18 nguồn; DEC-04                                               | **PARTIAL** |

## Tổng hợp blocker

* **BJ-01:** PARTIAL — current-period model gap.
* **BJ-02:** PARTIAL — dependency nghiệp vụ chưa được định nghĩa đầy đủ.
* **BJ-03:** PARTIAL — historical preservation đã chốt, nhưng cách biểu diễn nghiệp vụ chưa được đặc tả.
* **BJ-04:** PARTIAL — UC08 đã xác định kiểm tra site + period; UC09–11 không thêm school-state dependency; runtime chưa kiểm chứng.
* **BJ-05:** PARTIAL — THCS/active rule đã rõ; candidate-period gap còn.
* **BJ-06:** PARTIAL — validation points đã rõ; exception/status khi invalid tại approval còn thiếu.

**Runtime:** Batch I đã có evidence `46 PASS, 0 FAIL, 0 BLOCKED`. Fresh
regression toàn bộ suite chưa được coi là hoàn tất vì G1 TEST 11 vẫn
`BLOCKED`. Documentation sync không làm thay đổi trạng thái runtime.

## UC19 / BJ-01..03

| Mã   | Use Case | Quan hệ/dữ liệu                                                                                                                                                                                                        | Kết luận                                                                                                      | Trạng thái      |
| ----- | -------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------- | ----------------- |
| BJ-01 | UC19     | `<span>admission_periods</span>`                                                                                                                                                                                        | Current period phải đến từ trạng thái/cấu hình chính thức; model gap chưa giải quyết               | **PARTIAL** |
| BJ-02 | UC19     | `<span>admission_quotas.school_id</span>`,`<span>aspirations.school_id</span>`,`<span>admission_results.school_id</span>`,`<span>exam_sites.school_id</span>`,`<span>enrollment_confirmations.school_id</span>` | Đã phân loại FK/gián tiếp/dependency nghiệp vụ; chưa được phép coi mọi FK là business dependency | **PARTIAL** |
| BJ-03 | UC19     | `<span>schools</span>`và dữ liệu lịch sử                                                                                                                                                                           | Không hard-delete nếu phá traceability; ưu tiên inactive/không tuyển sinh                                | **PARTIAL** |

## UC08–11 / BJ-04

| Mã    | Use Case | Quan hệ/dữ liệu                                                                                                | Kết luận                                                                      | Trạng thái      |
| ------ | -------- | ----------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------- | ----------------- |
| BJ-04a | UC08     | `<span>councils → exam_sites → schools</span>`                                                                | Kiểm tra site + period; không tự thêm`<span>schools.active</span>`        | **PARTIAL** |
| BJ-04b | UC09–11 | `<span>councils</span>`,`<span>council_subjects</span>`,`<span>exam_subjects</span>`,`<span>staff</span>` | Không xác minh direct school dependency; indirect path phải ghi nhận riêng | **PARTIAL** |

## UC31–33 / BJ-05

| Mã    | Use Case | Quan hệ/dữ liệu                                                           | Kết luận                                                    | Trạng thái      |
| ------ | -------- | ---------------------------------------------------------------------------- | ------------------------------------------------------------- | ----------------- |
| BJ-05a | UC31     | `<span>candidates.middle_school_id</span>`,`<span>schools.level</span>`  | Chỉ THCS                                                     | **PARTIAL** |
| BJ-05b | UC32     | `<span>candidates.middle_school_id</span>`,`<span>schools.active</span>` | Không chọn inactive cho mới/thay thế; giữ lịch sử      | **PARTIAL** |
| BJ-05c | UC33     | `<span>candidates.middle_school_id</span>`                                 | Không coi là period dependency khi chưa có liên kết kỳ | **PARTIAL** |

## UC13–14 / BJ-06

| Mã    | Use Case | Quan hệ/dữ liệu                                                                                          | Kết luận                                          | Trạng thái      |
| ------ | -------- | ----------------------------------------------------------------------------------------------------------- | --------------------------------------------------- | ----------------- |
| BJ-06a | UC14     | `<span>adjustment_requests.new_data.middle_school_id</span>`                                              | Validate THCS tại submit                           | **PARTIAL** |
| BJ-06b | UC13     | `<span>candidates.middle_school_id</span>`,`<span>schools.level</span>`,`<span>schools.active</span>` | Revalidate tại approval; invalid thì không apply | **PARTIAL** |

## Tổng hợp

Không blocker nào được đánh dấu **RESOLVED** chỉ vì documentation đã cập nhật.

* **BJ-01:** PARTIAL — current-period model gap.
* **BJ-02:** PARTIAL — business dependency chưa được chứng minh cho mọi FK.
* **BJ-03:** PARTIAL — history rule đã chốt nhưng chưa runtime.
* **BJ-04:** PARTIAL — indirect school path rõ hơn, runtime chưa được kiểm chứng mới.
* **BJ-05:** PARTIAL — THCS/active rule rõ hơn, candidate-period gap còn.
* **BJ-06:** PARTIAL — validation points rõ, exception/status flow còn thiếu.

### Khoảng trống

**Nghiệp vụ:** current-period definition; xử lý request khi school trở nên invalid trước approval.

**Mô hình dữ liệu:**`<span>candidates</span>` chưa có `<span>admission_period_id</span>`; không được tự thêm trong Batch J.

**Đặc tả:** UC13/14 cần exception/status behavior; UC19 cần historical-only behavior.

**Runtime:** Batch I đã có evidence `46/46 PASS`; fresh regression toàn bộ suite
chưa được coi là hoàn tất vì G1 TEST 11 vẫn `BLOCKED`.
