# Công bố điểm thi trước phúc khảo

Vào **Công bố kết quả → Xem điểm và công bố** (`publication/exam.php`). Chọn kỳ để xem số báo danh, họ tên, điểm gốc từng môn và trạng thái Chưa công bố/Đã công bố. Dữ liệu lấy từ bài thi đã ghép về thí sinh, không dùng điểm phúc khảo để thay điểm trong bảng này.

Nút Công bố mở hộp thoại xác nhận. Hủy hoặc Escape đóng hộp thoại, không gửi POST và không ghi dữ liệu. Xác nhận gửi POST có CSRF; máy chủ kiểm tra lại quyền `publication.manage`, kỳ, dữ liệu điểm và ghép phách. Kỳ có cấu hình quản lý vẫn phải hoàn tất bước rà soát và khóa điểm hiện có. Chưa đủ dữ liệu thì hiển thị lý do, không công bố một phần.

Bài vắng thi phải được điểm danh rõ ràng và không có điểm; không tạo điểm giả cho bài vắng. Bài có mặt phải có điểm hợp lệ và mã phách khớp. Danh sách kiểm tra bao gồm mọi thí sinh xếp phòng × môn thi để phát hiện bài thi còn thiếu.

Trạng thái được lưu bằng sự kiện `publication_events` loại `exam_results`, trạng thái `published`, kèm người thực hiện và thời điểm theo SQL hiện tại. Toàn bộ thao tác công bố dùng transaction và khóa dòng kỳ, chống hai phiên cùng công bố. Lỗi rollback; công bố lặp bị chặn. Thành công chuyển hướng GET để tránh gửi lại biểu mẫu khi tải lại trang.

Các luồng nhập điểm, ghép phách và điểm danh dùng chung khóa kỳ, kiểm tra công bố trước khi sửa kể cả kỳ không có cấu hình quản lý mới. Các trigger bảo vệ `scores` có sẵn trong SQL tiếp tục chặn thêm/sửa/xóa điểm sau công bố. Không cần migration mới. Điểm phúc khảo của kỳ có cấu hình tiếp tục lưu riêng tại `appeal_results`; điểm gốc không bị ghi đè. Thí sinh xem điểm qua luồng tra cứu có kiểm tra trạng thái công bố hiện có.

Kiểm thử: `tests/exam_publication_runtime.php` có 15 kiểm tra dữ liệu; `tests/exam_publication_frontend.cjs` có 5 kiểm tra hộp thoại và chống gửi lặp. Đã kiểm tra cú pháp các file PHP thay đổi. Chưa chạy kiểm thử tích hợp với CSDL hoặc trình duyệt thật; không công bố hoặc sửa điểm trong CSDL thực khi triển khai.
