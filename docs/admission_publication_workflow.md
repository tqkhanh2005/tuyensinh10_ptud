# UC23 — Công bố kết quả tuyển sinh

Vào **Công bố kết quả → Xem kết quả và công bố**, chọn kỳ tuyển sinh. Trang `publication/admission.php` hiển thị số báo danh, họ tên, điểm từng môn dùng khi xét tuyển, trạng thái điểm, NV1/NV2/NV3, trạng thái xét tuyển và kết quả. Nguồn là bản chụp của lượt xét tuyển đã lưu, không phải bản xem trước chưa xác nhận.

Điều kiện: tài khoản có quyền `publication.manage`; kỳ có cấu hình hợp lệ; lượt xét tuyển đã tính xong, được duyệt theo quy trình dự án hiện có; bản chụp và danh sách kết quả khớp dữ liệu hiện tại. Màn hình dẫn về trang duyệt nếu còn thiếu điều kiện. Phạm vi mô phỏng TP.HCM và nhãn mô phỏng được giữ nguyên.

Nút Công bố mở hộp thoại cảnh báo dữ liệu kết quả bị khóa và không thể xét tuyển lại. Hủy hoặc Escape đóng hộp thoại, trở lại danh sách với nút Công bố; không gửi yêu cầu lưu. Xác nhận gửi POST có CSRF và mã lượt đã xem.

Máy chủ kiểm tra quyền, khóa kỳ, kiểm tra chưa công bố, kiểm tra lại lượt xét tuyển/bản chụp/dữ liệu. Mã lượt không khớp yêu cầu tải lại. Một transaction cập nhật `admission_runs.status=finalized`, thời điểm công bố và thêm `publication_events` loại `admission_results`, trạng thái `published`. Trạng thái hiển thị là **Đã công bố**. Không cần thay enum hoặc cấu trúc SQL.

Các trường hợp lỗi: chưa xét, lượt còn lỗi/đồng điểm, chưa duyệt, dữ liệu đã thay đổi, công bố lặp, không có quyền hoặc lỗi lưu. Lỗi lưu rollback toàn bộ. Thành công chuyển hướng GET và hiện thông báo. Luồng xét tuyển/duyệt/đối chiếu điểm cộng tiếp tục bị chặn bằng kiểm tra công bố và khóa kỳ hiện có. Khóa áp dụng qua các luồng ứng dụng; không bổ sung trigger chống thao tác SQL trực tiếp của quản trị CSDL. Xác nhận nhập học vẫn là nghiệp vụ riêng được phép sau công bố.

Kiểm chứng: `tests/admission_publication_runtime.php` kiểm tra quyền, công bố lặp, kỳ không tồn tại và rollback bằng PDO giả; `tests/exam_publication_frontend.cjs` kiểm tra hộp thoại dùng chung. Đã kiểm tra cú pháp PHP. Chưa kiểm thử tích hợp công bố thành công/rollback lỗi ghi trên CSDL bản sao hoặc trình duyệt thật; không công bố kết quả trên CSDL thực trong quá trình triển khai.
