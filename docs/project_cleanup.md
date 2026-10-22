# Dọn tệp phục vụ bàn giao — 22/10/2026

Đã đưa 51 tệp phát sinh khi phát triển ra khỏi thư mục dự án:

- `scratch/`: 11 tệp dò dữ liệu, mã chạy thử và bản in dữ liệu cũ.
- `outputs/01a13af4/support/`: mã sinh/kiểm tra Excel, ảnh xem trước, dữ liệu kiểm tra và phiên PHP tạm. Liên kết thư viện trong thư mục này được chuyển cùng thư mục, không đi theo liên kết để xóa thư viện dùng chung.
- `outputs/ui-review/`: ảnh kiểm tra giao diện cũ.
- Ba bản SQL không đánh số trong `database/`: `backup_before_test_cleanup.sql`, `tuyen_sinh_lop10_after_batch_tests.sql`, `tuyen_sinh_lop10_before_exam_allocations_repair.sql`.

Các tệp được chuyển vào `cleanup-archive` trong thư mục bàn giao riêng ngoài web. Manifest lưu đường dẫn cũ, dung lượng và SHA-256; đã kiểm tra lại toàn bộ tệp sau chuyển. Có thể phục hồi nếu cần đối chiếu, không phải tệp triển khai của ứng dụng.

Giữ lại hai bộ dữ liệu nhập TP.HCM, hướng dẫn import, bản Word tuần 5 cùng nguồn tham khảo ban đầu, lịch sử quyết định, migration 001–019, mã ứng dụng và bộ kiểm tra nghiệp vụ. File tuần 5 không phải báo cáo cuối.

Danh sách tài khoản được xuất thành một tệp riêng `handover-accounts/TAI_KHOAN_BAN_GIAO.txt` bên ngoài web, bao gồm CSDL dự án và hai bản demo. Mật khẩu được đối chiếu với hash đang lưu trước khi ghi; tệp không xuất hash và không tự đặt lại mật khẩu. Tài khoản thiếu mật khẩu gốc được ghi rõ. Tệp chỉ phản ánh trạng thái lúc xuất, cần tạo lại nếu thành viên đổi mật khẩu hoặc thêm tài khoản.

Việc dọn tệp không xóa tài khoản hoặc bản ghi CSDL. Bằng chứng kiểm tra được chạy lại theo bộ tệp còn sử dụng.
