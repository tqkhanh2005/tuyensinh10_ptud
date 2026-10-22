# Thiết lập quy tắc và kỳ tuyển sinh

Trong **Xét tuyển → Tạo kỳ/kế hoạch mới**, nhập năm học, chọn **Mô phỏng** hoặc **Chính thức** và lưu bản nháp. Mở lại bằng nút **Cấu hình / trạng thái** ở danh sách kỳ.

## Quy tắc theo năm

Bộ tham chiếu hiện áp dụng cho lớp 10 thường THPT công lập TP.HCM năm học 2026–2027. Không bao gồm chuyên, tích hợp, tuyển thẳng hay xét tuyển đặc thù. Xem **Quy tắc và nguồn văn bản** trên màn hình cấu hình để mở các căn cứ đã đối chiếu. Không tự dùng hồ sơ quy tắc 2026–2027 cho năm khác ở chế độ chính thức.

Tham số được lưu trong `data/admission_policy_hcm_2026.json`. Hàm `admission_policy_score()` hỗ trợ kiểm tra phép tính tham chiếu; hồ sơ hưởng chính sách và điều kiện dự tuyển vẫn phải được đối chiếu minh chứng. Không sử dụng phép tính này thay quyết định tuyển sinh.

Đồng điểm tại ranh giới chỉ tiêu dừng để rà soát; chưa có quy tắc phân xử được xác nhận trong các nguồn đã đọc. Bộ máy xét tuyển cũ tiếp tục phục vụ mô phỏng, bị chặn chạy với kỳ chính thức cho đến khi nghiệm thu đầy đủ ở giai đoạn hoàn thiện xét tuyển.

## Thời hạn và chỉ tiêu

Thiết lập riêng thời gian đăng ký ban đầu, điều chỉnh nguyện vọng, phúc khảo và nhập học. Tất cả dùng giờ Việt Nam. Bản nháp cho phép để trống; công bố yêu cầu đủ bốn giai đoạn. Mỗi cặp phải có đủ hai mốc và kết thúc sau bắt đầu. Các giai đoạn sau không được bắt đầu trước khi đăng ký ban đầu kết thúc.

Giai đoạn điều chỉnh chỉ nhận học sinh đã có nguyện vọng trong kỳ. Ngoài hai cửa sổ đăng ký/điều chỉnh, hệ thống từ chối thay đổi. Chỉ những THPT được đặt chỉ tiêu trong kỳ mới xuất hiện ở biểu mẫu của học sinh.

Mở **Thiết lập chỉ tiêu từng trường THPT**, nhập số nguyên dương cho các trường tham gia rồi lưu. Không cần nhập số cho trường không tham gia. Chỉ tiêu không hợp lệ được hoàn tác cả lần lưu. Kỳ đã công bố khóa cấu hình và chỉ tiêu.

Chế độ chính thức yêu cầu đường dẫn HTTPS đến kế hoạch, lịch đăng ký, quyết định chỉ tiêu, thời hạn phúc khảo và thời hạn nhập học; quản trị xác nhận đã đối chiếu đúng văn bản. Đây là trách nhiệm kiểm chứng của người vận hành, không phải xác thực nội dung tự động chỉ dựa trên đường dẫn.

## Trạng thái và công khai

Trình tự: **Bản nháp → Công bố → Đóng**. Lưu cấu hình không tự công bố. Chỉ một kỳ được công bố tại một thời điểm; đóng kỳ dừng các giao dịch mới, không xóa hồ sơ và lịch sử đã lưu. Không mở lại kỳ đã đóng. Sau công bố, chỉ tiêu chính thức còn cần thao tác **Công bố chỉ tiêu chính thức** riêng.

Kỳ mô phỏng có nhãn cảnh báo và không xuất hiện trong chỉ tiêu công khai. Cổng công khai chỉ lấy chỉ tiêu của kỳ chính thức đã công bố. Hệ thống ghi nhận người, thời điểm và nội dung thay đổi; bản cấu hình cũ đang mở bị từ chối nếu người khác đã lưu trước đó.

Kế hoạch chuẩn bị cho bộ 720 học sinh giả lập được giữ ở bản nháp, không đặt lịch hoặc chỉ tiêu minh họa vào CSDL chính. Muốn chạy thử, chọn lịch và chỉ tiêu mô phỏng rõ ràng hoặc dùng bộ kiểm tra riêng.

## Kiểm tra và cài đặt

Migration bổ sung: `database/015_admission_period_configuration.sql`. Công cụ dòng lệnh `database/configure_admission_periods.php --backup=<SQL đã thử khôi phục và khớp dữ liệu hiện tại>` tạo hai bảng cấu hình/nhật ký và chuẩn bị bản nháp khi chưa có cấu hình nào.

Kiểm tra bằng `tests/run_isolated_workflows.php --suite=Period --backup=<SQL đã thử khôi phục> --report-dir=<thư mục ngoài web>`. Bộ kiểm tra tự tạo và xóa CSDL mô phỏng, bao gồm xác thực thời hạn, quy tắc tính điểm tham chiếu, nguyện vọng, chỉ tiêu, quyền truy cập, biểu mẫu HTTP và ngăn công khai dữ liệu mô phỏng.
