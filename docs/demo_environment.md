# Môi trường trình diễn TP.HCM

Đã chuẩn bị hai CSDL mô phỏng riêng từ bản sao đã thử phục hồi. Mỗi bản có 720 học sinh giả lập thuộc THCS Nguyễn Du và THCS Lê Quý Đôn, 360 học sinh/trường. Tên trường có thật; người, lịch, điểm, chỉ tiêu và kết quả trình diễn là giả lập.

## Mở và dùng

- Hồ sơ, nguyện vọng, duyệt điều chỉnh: http://127.0.0.1:4021/auth/login.php
- Điểm, kết quả đã công bố và xác nhận nhập học: http://127.0.0.1:4022/auth/login.php

Bộ điều khiển và tài khoản nằm trong thư mục riêng ngoài web: `C:/Users/tqkha/.codex/visualizations/2026/10/14/01a13af4-a2a7-70b2-bb20-55dc28bbb25c/demo/`.

Mở `TAI_KHOAN.md` để lấy tài khoản theo vai trò. Mỗi bản có quản trị, hai giáo viên THCS, hai học sinh và một giáo viên THPT. Đây là mật khẩu riêng của bản demo; không dùng để đăng nhập CSDL nguồn và không chiếu tệp mật khẩu khi trình bày.

- `Mo-demo.cmd`: mở hai máy chủ cục bộ, làm mới thời hạn khi khởi động.
- `Dung-demo.cmd`: dừng đúng hai máy chủ đã lưu mã tiến trình.
- `Lam-lai-demo.cmd`: dừng, khôi phục hai bản về mốc ban đầu rồi mở lại. Các thao tác vừa tập trên bản demo sẽ bị xóa; CSDL nguồn không bị sửa.

MySQL trong XAMPP phải đang chạy. Máy chủ demo chỉ nghe trên 127.0.0.1. Dùng cửa sổ riêng tư hoặc hồ sơ trình duyệt khác nhau cho từng vai trò. Cookie trên cùng địa chỉ IP được dùng chung giữa các cổng; đăng xuất trước khi đổi bản, hoặc dùng hai hồ sơ trình duyệt riêng.

## Kịch bản khoảng 15 phút

1. Bản 4021, giáo viên Nguyễn Du: xem 360 học sinh; chuyển giáo viên Lê Quý Đôn để minh họa phạm vi trường.
2. Bản 4021, học sinh A: đăng ký ba trường THPT khác nhau theo thứ tự. Thử trùng trường để thấy thông báo rồi lưu dữ liệu hợp lệ.
3. Bản 4021, giáo viên A: gửi đề nghị sửa một thông tin hồ sơ. Quản trị mở **Duyệt điều chỉnh hồ sơ**, so sánh trước/sau và duyệt. Tài khoản giáo viên không sửa trực tiếp hồ sơ thay quản trị.
4. Chuyển bản 4022, quản trị: chọn kỳ ghi trong `TAI_KHOAN.md`; xem tổ chức thi, điểm, lượt xét đã duyệt và kết quả công bố. Đây là dữ liệu đã chuẩn bị để trình diễn tiếp, không phải dữ liệu tự đồng bộ từ bản 4021.
5. Bản 4022, học sinh A hoặc B: xem kết quả, xác nhận nhập học, mở/in biên nhận. Gửi lại để minh họa không tạo bản trùng. Hai học sinh được chọn đều có kết quả hợp lệ, chưa xác nhận ở mốc ban đầu.
6. Bản 4022, giáo viên THPT: xem đúng danh sách của trường được giao. Quản trị lọc trạng thái xác nhận, xuất danh sách và xem nhật ký theo kỳ.

Không trình bày toàn bộ quy trình tạo đề, thi hoặc chấm trực tiếp trong 15 phút. Nếu cần thao tác từng bước tổ chức thi–phúc khảo–xét tuyển, dùng hướng dẫn nghiệp vụ và một bản sao mới; bản 4022 đã có kết quả công bố nên các dữ liệu liên quan đã khóa.

## Thời hạn và làm lại

Khi khởi động, bản hồ sơ mở đăng ký trong 24 giờ; các mốc tiếp theo nằm sau đăng ký. Bản kết quả mở xác nhận nhập học trong 24 giờ. Thời gian theo Asia/Ho_Chi_Minh, không thay đổi đồng hồ máy. Nếu để máy chủ qua ngày hoặc muốn tập lại, dùng `Lam-lai-demo.cmd`. Việc làm mới thời hạn/bản chụp chỉ thuộc bộ dựng dữ liệu demo riêng, không phải cách điều chỉnh kết quả chính thức.

Mốc SQL và SHA-256 lưu ngoài web, được kiểm tra trước khi khôi phục. Công cụ chỉ cho phép hai tên CSDL tạm trong manifest và đối chiếu toàn bộ CSDL nguồn trước/sau. Nếu CSDL nguồn đã thay đổi, công cụ dừng để tránh tiếp tục dựa trên mốc cũ; cần chuẩn bị lại bản demo theo bản sao mới.

## Kiểm chứng

Đã thử khôi phục cả hai bản và đăng nhập qua HTTP cho 12 tổ hợp bản/vai trò, có CAPTCHA và CSRF thật. Kiểm tra trang được phân quyền, học sinh bị chặn khu quản trị và chặn đường dẫn nội bộ. Kết quả chi tiết nằm tại `demo_http_report.json` trong thư mục riêng. Đây là kiểm tra chức năng HTTP, không thay thế đánh giá trực quan trên thiết bị trình chiếu.

Bộ trình diễn không sửa phần mềm, tài khoản hoặc dữ liệu CSDL nguồn. Word tuần 5 vẫn là nguồn lý thuyết ban đầu, không phải báo cáo cuối. Xem thêm [hướng dẫn trình diễn và vận hành](handover_guide.md).
