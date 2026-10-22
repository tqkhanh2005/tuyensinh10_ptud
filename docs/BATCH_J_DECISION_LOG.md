> **Tài liệu lịch sử Batch J.** Nội dung bên dưới phản ánh giai đoạn lập tài liệu trước đây; số kiểm tra và trạng thái lúc đó không phải kết luận cho phiên bản hiện tại. Xem [bản đối chiếu hiện thực ngày 22/10/2026](scope_and_implementation.md). Các quyết định nghiệp vụ chưa được đáp ứng đầy đủ vẫn là yêu cầu cần xử lý, không tự đóng theo kết quả kiểm tra kỹ thuật.

BATCH J — DECISION LOG

Project:<span>tuyensinh10_ptud</span>
Batch: J — Chốt nghiệp vụ và cập nhật tài liệu
Phạm vi: DOCUMENTATION-ONLY
Ngày quyết định: 25/09/2026
Trạng thái: APPROVED / DECIDED BY PROJECT OWNER

Ghi nhận tài liệu không có nghĩa runtime đã triển khai.

DEC-01 — Xác định kỳ tuyển sinh hiện tại

Kỳ tuyển sinh hiện tại phải được xác định từ cấu hình hoặc trạng thái nghiệp vụ chính thức; không suy ra chỉ từ ID lớn nhất, năm lớn nhất, ngày hiện tại hoặc kỳ tạo gần nhất. Nếu không có hoặc có nhiều kỳ cùng được đánh dấu hiện tại, thao tác yêu cầu kỳ hiện tại phải bị từ chối với thông báo phù hợp.
Ảnh hưởng: UC19; <span>admission_periods</span> và các bảng có <span>admission_period_id</span>.
Khoảng trống: cần xác định mô hình hiện tại có biểu diễn duy nhất “kỳ hiện tại” hay không; không thiết kế schema mới trong Batch J.

DEC-02 — Dependency khi xử lý trường THPT

Khi xem xét xóa/ngừng sử dụng trường THPT, phải đánh giá dữ liệu liên quan trong kỳ tương ứng: <span>admission_quotas.school_id</span>, <span>aspirations.school_id</span>, <span>admission_results.school_id</span>, <span>exam_sites.school_id</span> và dữ liệu địa điểm thi, <span>enrollment_confirmations.school_id</span>, cùng các quan hệ khác nếu được xác minh.
Phải phân biệt FK trực tiếp, quan hệ gián tiếp, dependency nghiệp vụ đã xác nhận và dependency chưa đủ bằng chứng.
Ảnh hưởng: UC19; <span>schools</span>, <span>admission_quotas</span>, <span>aspirations</span>, <span>admission_results</span>, <span>exam_sites</span>, <span>enrollment_confirmations</span>.
Lưu ý:<span>candidates.middle_school_id</span> chưa được coi là dependency theo kỳ.

DEC-03 — Trường chỉ còn dữ liệu lịch sử

Dữ liệu trường đã phát sinh hồ sơ, kết quả, điểm, xác nhận nhập học hoặc dữ liệu lịch sử phải được bảo toàn. Không hard-delete nếu việc xóa phá vỡ truy vết. Ưu tiên ngừng tuyển sinh/chuyển inactive và giữ dữ liệu lịch sử.
Ảnh hưởng: UC19; <span>schools</span> và các bảng phụ thuộc.
Giới hạn: không triển khai soft-delete/schema trong Batch J.

DEC-04 — Trường THCS của ứng viên

<span>candidates.middle_school_id</span> phải tham chiếu trường có <span>schools.level = 'THCS'</span>; không chấp nhận THPT hoặc cấp học không xác định.
Ảnh hưởng: UC31, UC32, UC13, UC14; <span>candidates.middle_school_id</span>, <span>schools.level</span>.

DEC-05 — Trạng thái hoạt động của trường THCS

Trường THCS inactive không được chọn làm trường mới hoặc trường thay thế. Tham chiếu lịch sử phải được giữ nguyên; không tự động NULL/xóa chỉ vì trường inactive.
Ảnh hưởng: UC31, UC32, UC13, UC14; <span>schools.active</span>, <span>candidates.middle_school_id</span>.

DEC-06 — Kiểm tra trường THCS trong UC13 và UC14

Kiểm tra trường THCS tại UC14 khi gửi yêu cầu và UC13 khi duyệt/ap dụng. Nếu trường mới không còn hợp lệ tại approval thì không áp dụng thay đổi. Không tự đặt trạng thái mới; nếu flow hiện hành không hỗ trợ thì ghi <span>[CẦN BỔ SUNG ĐẶC TẢ]</span>.
Ảnh hưởng: UC13, UC14; <span>adjustment_requests</span>, <span>candidates</span>, <span>schools</span>.

DEC-07 — Xác định kỳ của ứng viên

Quy tắc phụ thuộc kỳ chỉ áp dụng khi mối liên hệ kỳ được xác minh. Không thêm <span>admission_period_id</span> vào <span>candidates</span> trong Batch J.
Ảnh hưởng: UC31–33; <span>candidates</span>.
Khoảng trống: chưa đủ bằng chứng để xác định duy nhất kỳ của ứng viên từ <span>candidates</span>.

DEC-08 — Trường học và hội đồng trong UC08

UC08 phải kiểm tra địa điểm thi và kỳ tuyển sinh theo đặc tả hiện hành. Không tự thêm điều kiện <span>schools.active</span>. Phải phân biệt hoạt động mới với dữ liệu hội đồng lịch sử và giữ khả năng truy vết.
Ảnh hưởng: UC08; <span>councils</span>, <span>exam_sites</span>, <span>admission_periods</span>, <span>schools</span>.
Source trace:<span>councils → exam_sites → exam_sites.school_id → schools</span> là quan hệ schema gián tiếp, chưa phải dependency nghiệp vụ trực tiếp về <span>schools.active</span>.

DEC-09 — UC09, UC10, UC11

Không thêm dependency bắt buộc với trạng thái trường vào UC09–11 khi chưa có căn cứ nghiệp vụ. Tiếp tục dựa trên các điều kiện đã xác minh như môn thi, cán bộ và dữ liệu trực tiếp. Nếu có đường dẫn gián tiếp qua hội đồng/địa điểm thi thì ghi rõ và không biến thành direct business dependency.

Tình trạng triển khai

Tất cả DEC-01..09:  APPROVED / DECIDED BY PROJECT OWNER — NOT IMPLEMENTED IN RUNTIME .

BATCH J — APPLICATION CLARIFICATIONS

Các ghi chú dưới đây chỉ làm rõ cách áp dụng DEC-01..09 vào đặc tả nguồn; không thay đổi quyết định đã được phê duyệt.

UC19: câu "Xóa thông tin của trường tương ứng khỏi CSDL" trong đặc tả nguồn không còn phù hợp với DEC-03 nếu trường đã có dữ liệu lịch sử. Bản đặc tả đồng bộ thay bằng quy tắc: chỉ xử lý xóa khi không có dependency nghiệp vụ trong kỳ tương ứng và không làm mất traceability; trường hợp chỉ còn dữ liệu lịch sử phải bảo toàn dữ liệu. Cách biểu diễn trạng thái/nghiệp vụ cụ thể còn [CẦN BỔ SUNG ĐẶC TẢ] nếu bộ nguồn chưa quy định.

UC08: bổ sung điều kiện kiểm tra địa điểm thi thuộc đúng kỳ tuyển sinh trước khi thành lập hội đồng; không bổ sung schools.active. Việc giữ dữ liệu hội đồng lịch sử được ghi nhận, không tạo trạng thái mới.

UC09–UC11: không bổ sung điều kiện schools.active; đường dẫn gián tiếp qua hội đồng/địa điểm thi, nếu có, không được coi là direct business dependency.

UC13–UC14: bổ sung kiểm tra trường tham chiếu là THCS và đang active tại các điểm tương ứng. Nếu trường trở nên invalid sau khi UC14 gửi nhưng trước UC13 duyệt, không áp dụng thay đổi; trạng thái/exception cụ thể chưa có trong nguồn và được đánh dấu [CẦN BỔ SUNG ĐẶC TẢ].

UC31–UC32: trường maTruongTHCS phải là THCS; trường inactive không được chọn cho thao tác tạo/thay thế mới. Không thêm admission_period_id vào ứng viên.

UC33: không coi maTruongTHCS là dependency theo kỳ khi chưa có mô hình liên kết kỳ được xác minh; không thay đổi flow xóa ngoài phạm vi DEC-07.

UC17–UC18: không tự thêm dependency trường active/current-period. Riêng UC18 cho phép đổi capHoc; việc đổi cấp học khi trường đang được tham chiếu như middle_school_id chưa có quy tắc nguồn/DEC tương ứng, nên ghi [CẦN BỔ SUNG ĐẶC TẢ] thay vì tự suy luận.

Nguồn đồng bộ: _ai_source/_uc_extract.txt, là bản trích xuất từ bộ đặc tả Use Case nguồn được sử dụng trong Batch J.
