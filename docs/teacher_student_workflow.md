# Giáo viên THCS, hồ sơ và nguyện vọng

## Dữ liệu đã chuẩn bị

Hai tài khoản `gv.thcs.nguyendu` và `gv.thcs.lequydon` liên kết lần lượt với THCS Nguyễn Du và THCS Lê Quý Đôn. Tên giáo viên là dữ liệu mô phỏng, không thể hiện nhân sự thực tế của các trường. Mật khẩu được tạo riêng cho từng tài khoản và lưu trong `teacher_accounts.json` ở thư mục sao lưu cá nhân ngoài thư mục web.

720 học sinh đã nhập vẫn giữ nguyên. Mỗi giáo viên xem 360 học sinh thuộc trường mình, tìm kiếm theo họ tên hoặc giấy tờ, lọc điều kiện và trạng thái. Danh sách có phân trang 50 dòng để xem đủ toàn bộ học sinh.

## Thử luồng hồ sơ

1. Chọn thẻ **Giáo viên / Quản trị** ở trang đăng nhập, dùng tài khoản giáo viên và mở **Quản lý thí sinh / Học sinh**.
2. Chọn **Báo sai sót** ở học sinh cần đính chính. Nhập thông tin mới và lý do. Trường để trống giữ nguyên dữ liệu cũ.
3. Kiểm tra yêu cầu ở trang **Yêu cầu điều chỉnh**. Hồ sơ gốc chưa thay đổi khi đang chờ duyệt.
4. Đăng nhập quản trị, mở **Duyệt điều chỉnh hồ sơ** trong menu, đối chiếu thông tin trước/sau rồi duyệt hoặc từ chối. Dùng bộ lọc để xem lại yêu cầu đã xử lý. Người gửi nhận thông báo kết quả xử lý.

Giáo viên trường khác không được xem biểu mẫu hay gửi yêu cầu cho hồ sơ đó. Giáo viên không được sửa hồ sơ qua đường dẫn quản trị, tự duyệt yêu cầu hoặc chỉnh nguyện vọng thay học sinh.

## Thử luồng nguyện vọng

Danh mục đã bổ sung ba trường có thông tin xác minh ngày 18/10/2026 từ website chính thức: [THPT Nguyễn Thị Minh Khai](https://thptnguyenthiminhkhai.hcm.edu.vn/), [THPT Trần Phú](https://thpttranphu.hcm.edu.vn/), [THPT Marie Curie](https://thptmariecurie.hcm.edu.vn/). Mã dạng `THPT_...` là mã nội bộ của ứng dụng, không phải xác nhận mã tuyển sinh chính thức hay danh sách trường được tuyển trong một kỳ cụ thể.

CSDL đang sử dụng chưa có kỳ tuyển sinh được công bố. Vì chưa có lịch được xác thực, biểu mẫu nguyện vọng hiện hiển thị trạng thái chờ và không cho lưu.

Khi có kế hoạch được phê duyệt, quản trị thiết lập kỳ và thời hạn; học sinh đăng nhập để chọn trường ở các vị trí NV1, NV2, NV3 theo ưu tiên. Không bỏ trống vị trí ở giữa hoặc chọn trùng trường. Số nguyện vọng tối đa chưa được đặc tả trong dự án nên biểu mẫu có nút thêm vị trí, chưa coi ba vị trí mặc định là giới hạn pháp lý.

Giáo viên mở **Theo dõi nguyện vọng**, chọn đúng kỳ và rà soát học sinh trường mình. Kỳ cũ và kỳ hiện tại có danh sách, thống kê tách riêng.

## Kiểm tra lại trên môi trường mô phỏng riêng

Chạy `tests/run_isolated_workflows.php` với `--suite=School`, `--backup=<bản sao lưu đã thử khôi phục và khớp CSDL hiện tại>` và `--report-dir=<thư mục ngoài web>`. Bộ kiểm tra tự khôi phục bản sao, tạo kỳ mô phỏng, lưu 2.160 nguyện vọng của 720 học sinh, kiểm tra phân quyền và duyệt hồ sơ qua HTTP rồi xóa bản sao. Dữ liệu mô phỏng không được đưa vào CSDL đang sử dụng.
