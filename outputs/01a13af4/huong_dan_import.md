# Bộ học sinh tuyển sinh lớp 10 TP.HCM

Đã chuẩn hóa và import thành công 720 học sinh giả lập vào CSDL dự án ngày 18/10/2026. Mỗi trường có 360 học sinh, gồm 9 lớp 9A1–9A9, mỗi lớp 40 em. Mỗi workbook Excel có đúng 9 sheet tương ứng; CSV chứa 360 em của một trường vì CSV không có nhiều sheet.

## Trường và mã nội bộ

- Trường THCS Nguyễn Du, mã THCS_NGUYEN_DU. Địa chỉ trường: 139 Nguyễn Du, phường Bến Thành, TP.HCM. Nguồn: https://thcsnguyenduq1.hcm.edu.vn/
- Trường THCS Lê Quý Đôn, mã THCS_LE_QUY_DON. Địa chỉ trường: Số 2 Lê Quý Đôn, phường Xuân Hòa, TP.HCM. Nguồn: https://thcslequydonq3.hcm.edu.vn/

Mã trên là mã nội bộ của dự án, không phải mã trường do cơ quan quản lý cấp. Tên và địa chỉ trường là thông tin công khai. Tất cả họ tên, CCCD, ngày sinh, địa chỉ học sinh, điện thoại và email trong bộ học sinh là giả lập. Email dùng example.com. Năm sinh 2011 được chọn cho bộ mô phỏng tuyển sinh năm 2026.

## Sử dụng

Ứng dụng hiện nhận CSV UTF-8. Hai workbook Excel dùng để xem, chỉnh sửa theo lớp. Các bản tương ứng trong data/import và thư mục đầu ra có cùng nội dung. CCCD và điện thoại trong Excel được giữ dưới dạng văn bản để không mất số 0 đầu; ngày sinh hiển thị yyyy-mm-dd.

720 học sinh đã được nhập qua đúng luồng tải CSV → xem trước → xác nhận. Không cần nhập lại hai file. Nếu tải lại, hệ thống báo trùng CCCD và không cho xác nhận. Không dùng script dọn batch để xóa bộ mới vì bộ này không mang nhãn batch cũ.

Hệ thống hiện chưa có trường dữ liệu riêng cho lớp. Lớp được thể hiện ở tên sheet và đầu địa chỉ học sinh. Mỗi học sinh có tài khoản candidate liên kết: tên đăng nhập là CCCD trong file, mật khẩu mặc định của chức năng import hiện tại là 1111. Tài khoản quản trị cũ được giữ nguyên.

## Kết quả kiểm tra

52 kiểm tra đạt: đọc file, mã trường, sĩ số, tiếng Việt, ngày sinh, số 0 đầu, liên kết tài khoản và vai trò, file sai cấu trúc, dữ liệu không hợp lệ, trùng trong file, trùng khi nhập lại, giới hạn 5.000 dòng, xác nhận lại batch và hoàn tác khi một dòng gặp lỗi CSDL. Workbook được đối chiếu đủ 720 dòng với CSV đã nhập.

Dữ liệu thử cũ đã được dọn 231 bản ghi sau khi sao lưu và thử khôi phục đủ 39 bảng. Danh mục quyền, vai trò, tài khoản quản trị và hai trường sẵn có không thuộc batch được giữ nguyên.
