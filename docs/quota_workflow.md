# Thiết lập chỉ tiêu tuyển sinh

Phạm vi: chỉ tiêu cho trường THPT đang hoạt động tại TP.HCM, theo từng kỳ tuyển sinh. Dùng nguyên cấu trúc `admission_quotas`, `schools`, `admission_periods`, `exam_subjects` trong file SQL được cung cấp; không cần migration hay nhập đè dữ liệu.

- Sở GD&ĐT hoặc tài khoản quản trị có quyền `admission.manage` mở chỉ tiêu từ một kỳ tuyển sinh.
- Chỉ sửa khi kỳ còn là bản nháp và chưa bắt đầu môn thi nào. Lấy thời điểm từ `exam_subjects.exam_date/start_time` theo múi giờ TP.HCM; thiếu giờ thì khóa từ đầu ngày thi. Môn đã hoàn thành cũng khóa sửa. Khi chưa có lịch, cho thiết lập ở bản nháp; không tự tạo ngày thi.
- Bảng hiển thị trường THPT đang hoạt động, trạng thái đã lưu, tìm kiếm, lọc trạng thái và phân trang 20/50/100 dòng. Lọc và phân trang trên giao diện giữ tất cả ô đang nhập trong cùng form để không mất thay đổi.
- Chỉ tiêu là số nguyên lớn hơn 0, giới hạn theo cột SQL `INT UNSIGNED` (4.294.967.295). Ô trống ở trường chưa đặt được bỏ qua; xóa trắng chỉ tiêu cũ bị báo lỗi.
- Nút Nhập từ Excel mở hộp thoại chọn `.xlsx` (tối đa 2 MB), có mẫu gồm đúng hai cột Mã trường và Chỉ tiêu. Kiểm tra toàn bộ file trước khi nối dữ liệu vào form; từ chối mã không hợp lệ, mã lặp, cột thừa và chỉ tiêu sai. Không lưu database khi nhập file.
- Lưu tất cả kiểm tra toàn bộ ô, đánh dấu tất cả dòng lỗi và giữ dữ liệu. Dữ liệu hợp lệ mở hộp xác nhận với tổng số trường thực sự thay đổi và số trường ghi đè chỉ tiêu đã đặt.
- Xác nhận lưu dùng token trong session, hết hạn sau 30 phút, ràng buộc đúng dữ liệu đã rà soát. Lưu trong transaction; kiểm tra lại thời hạn, phiên bản cấu hình và dấu vân tay danh sách/chỉ tiêu trước khi ghi. Bất kỳ lỗi nào đều rollback.
- Đặt chỉ tiêu không tự công bố. Trang công khai và giáo viên dùng chung truy vấn, chỉ đọc chỉ tiêu đã công bố thuộc một kỳ chính thức; không cộng chỉ tiêu nhiều kỳ và không hiển thị dữ liệu mô phỏng.

Kiểm tra:

```powershell
D:\xampp\php\php.exe tests/quota_workflow_runtime.php
node tests/quota_frontend_runtime.cjs
```

Kiểm thử database dùng bảng tạm theo kết nối, không chỉnh bản ghi thật. Kiểm thử JavaScript mô phỏng DOM để kiểm tra lọc, phân trang, giữ dữ liệu, xác nhận và cảnh báo rời trang; không thay thế kiểm tra trực quan trên trình duyệt.
