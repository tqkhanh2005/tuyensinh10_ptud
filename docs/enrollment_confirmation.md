# Bước 6: xác nhận nhập học

Dự án tuyển sinh lớp 10 TP.HCM. Kỳ mô phỏng luôn có nhãn trên màn hình, biên nhận và tệp xuất. Chỉ sử dụng thời hạn đã lưu trong kế hoạch; phần mềm không tự đặt lịch tuyển sinh chính thức.

- Học sinh mở **Xác nhận nhập học**, chọn kỳ đã công bố, kiểm tra trường và nguyện vọng rồi đánh dấu đồng ý. Chỉ chính chủ đang có quyền xác nhận mới được thực hiện.
- Hệ thống kiểm tra kết quả thuộc lượt đã duyệt, đã hoàn tất và đã công bố; tài khoản, hồ sơ, trường còn hoạt động; thời gian nằm trong khoảng đã cấu hình. Hai nguồn thời hạn trong kỳ và kế hoạch phải khớp nhau.
- Xác nhận được lưu theo từng kỳ, có thời điểm, người thực hiện và mã biên nhận. Gửi lại cùng yêu cầu trả về bản ghi cũ, không tạo thêm thông báo hay lịch sử. Không ghi đè trạng thái tuyển sinh toàn cục của học sinh khi mô phỏng.
- Học sinh xem/in biên nhận tại trang xác nhận, kể cả sau hạn hoặc khi kỳ đã đóng. Biên nhận chỉ truy cập bằng tài khoản chính chủ; không có tra cứu công khai bằng mã.
- Quản trị mở **Theo dõi nhập học** để theo dõi số đã/chưa xác nhận, lọc trường, trạng thái và xuất toàn bộ kết quả lọc bằng CSV/XLSX. Màn hình giới hạn 50 dòng mỗi trang. CSV bảo vệ ô có thể bị hiểu thành công thức.
- Giáo viên THCS chỉ xem học sinh trường mình; giáo viên THPT chỉ xem học sinh trúng tuyển vào trường mình, gồm trạng thái và thời điểm xác nhận.
- “Quá hạn chưa xác nhận” là nhãn suy ra từ thời hạn. Phần mềm không tự hủy trúng tuyển, rút hồ sơ hay phân phối lại chỗ. Các nghiệp vụ đó cần quy trình riêng được phê duyệt.

## Cập nhật cấu trúc

`database/configure_enrollment.php --backup=<bản SQL>` yêu cầu bản sao hiện tại đã thử khôi phục thành công. Cập nhật `019_enrollment_confirmation.sql` thay khóa duy nhất toàn học sinh bằng khóa theo kỳ và học sinh; bổ sung người thực hiện, mã biên nhận. Bản ghi cũ được liên kết với kỳ của kết quả gốc; giữ nguyên thời gian và không tự tạo người thực hiện hay mã cũ.

`tests/run_isolated_workflows.php --suite=Enrollment --backup=<bản SQL> --report-dir=<thư mục riêng>` khôi phục CSDL tạm và kiểm tra từ tổ chức thi, chấm thi, xét tuyển đến nhập học, phân quyền, thời hạn, gửi lặp và tham gia kỳ khác. Dữ liệu thử không được đưa vào CSDL chính.
