# Thành lập hội đồng ra đề — chuẩn bị báo cáo

Phạm vi: dự án tuyển sinh lớp 10 TP.HCM; dữ liệu trình diễn phải được ghi rõ là mô phỏng.

## Luồng demo

1. Đăng nhập bằng tài khoản có quyền council.manage, mở Hội đồng và chọn kỳ tuyển sinh.
2. Chọn Thành lập hội đồng → Hội đồng ra đề, đặt tên và chọn đúng một môn của kỳ.
3. Thêm thành viên đang hoạt động; chọn vai trò ra đề, phản biện hoặc thẩm định. Có thể ghi nhiệm vụ cụ thể.
4. Nhập thời gian bắt đầu/kết thúc hoặc để trống cả hai. Kết thúc phải sau bắt đầu.
5. Chọn trùng người để kiểm tra cảnh báo; sửa lại. Lưu tên trùng để kiểm tra lỗi từ máy chủ và việc giữ nguyên thành viên.
6. Lưu hợp lệ; kiểm tra thông báo thành công và mở Phân công trong danh sách.
7. Tải lại trang danh sách không tạo thêm hội đồng.

## Kiểm tra và giới hạn

- Tên tối đa 200 ký tự, địa điểm/nhiệm vụ tối đa 255, vai trò tự nhập của loại khác tối đa 100.
- Máy chủ kiểm tra ngày giờ thực, thành viên, môn thuộc kỳ, tên trùng; lưu đồng thời ba bảng bằng transaction.
- Kiểm tra lại cán bộ trong transaction có khóa dòng, tránh sử dụng trạng thái cũ khi có người sửa nhân sự đồng thời.
- Không dựng HTML từ tên cán bộ hoặc nhiệm vụ; phần hiển thị dùng textContent/Option và escape khi xuất PHP.
- Chưa có quy tắc phê duyệt bắt buộc đủ ba vai trò, số thành viên tối thiểu theo cơ cấu, chuyên môn giáo viên, xung đột lịch hội đồng hoặc hạn thành lập theo lịch thi. Không tuyên bố những quy tắc này đã được triển khai. Hệ thống yêu cầu ít nhất một thành viên để tránh hội đồng rỗng.
- Lỗi user_roles của database phải được phục hồi trước khi demo đăng nhập. Không chạy lại migration hoặc nhập đè database để kiểm tra chức năng này.

Kiểm thử database an toàn: `C:/xampp/php/php.exe tests/council_question_runtime.php` dùng toàn bộ bảng tạm theo kết nối, không ghi vào bảng dữ liệu thật.

## Kết quả kiểm chứng 28/10/2026

- 43 kiểm tra trên bảng tạm đã qua, gồm cả lỗi ghi muộn và rollback hội đồng/môn.
- Đã kiểm tra biểu mẫu bằng trình duyệt với dữ liệu mô phỏng tách riêng: giữ hai thành viên và vai trò sau lỗi; đổi loại không gửi điểm thi ẩn; thêm/xóa dòng; không có lỗi JavaScript.
- Chưa xác nhận toàn bộ luồng đăng nhập → lưu → danh sách trên database thật do sự cố user_roles đã phát hiện trước đó. Không đổi bảng hoặc dữ liệu thật trong lần sửa này.
