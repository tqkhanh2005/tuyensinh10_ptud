# Bước 4 — Chấm thi và phúc khảo

Phạm vi dự án: tuyển sinh lớp 10 thường TP.HCM, bộ quy tắc tham chiếu 2026–2027. Kỳ hiện tại vẫn là bản nháp mô phỏng. Không tự tạo điểm thi hoặc dữ liệu phúc khảo trong CSDL chính.

## Vận hành

1. Chọn **Nhập điểm → Chấm thi và phúc khảo** hoặc nút cùng tên trong trang **Tổ chức thi**. Chọn kỳ và môn bằng tên.
2. Vào từng phòng để tạo danh sách bài thi từ danh sách xếp phòng đã phát hành. Mỗi học sinh/môn có một bài, gắn đúng kỳ/phòng. Danh sách dùng SBD riêng của kỳ thay cho mã nhập hồ sơ.
3. Khi đến giờ thi, ghi điểm danh cho toàn bộ phòng: có mặt, có mặt kèm mức xử phạt, vắng. Phải điểm danh trước khi cấp phách; bài vắng không cấp phách hoặc nhập điểm. Sau cấp phách, không thay đổi điểm danh/xử phạt bằng màn hình này.
4. Sau khi kết thúc môn, xem trước và xác nhận phách ngẫu nhiên hoặc thủ công. Phách duy nhất trong kỳ, giữ số đã cấp khi chạy lại. Không có chức năng đánh lại phách đã lưu. Kiểm tra độ dài để tránh yêu cầu quá nhiều số trong một dải nhỏ.
5. Thành lập hội đồng chấm đúng kỳ/môn, dùng giáo viên có tài khoản và trường liên kết đang hoạt động. Trên trang chấm thi, phân công người trực tiếp chấm cho toàn môn hoặc từng phòng. Người nhập điểm lưu riêng trong `scores.entered_by`; người chấm ban đầu lưu trong `exam_paper_grader_assignments`.
6. Nhập điểm theo phách trực tiếp hoặc từ XLSX/CSV theo mẫu của ứng dụng. Với kỳ được quản lý theo năm: điểm trong thang 10, theo bước 0,25; môn một phần không nhận phần 2 hoặc điểm thành phần khác tổng. Biểu mẫu trực tiếp tối đa 50 dòng/trang. Đọc/ghi XLSX giữ phách dạng chuỗi, gồm cả số 0 đầu.
7. Xem lỗi và xác nhận các dòng hợp lệ. Khi xác nhận, máy chủ kiểm tra lại phách, kỳ/môn, điểm danh, người chấm và trạng thái bài. Nếu preview không còn khớp, toàn bộ lần ghi được hoàn tác. Không ghi đè điểm đã nhập hoặc xác nhận lại một lần import đã lưu. Khi đủ điểm có mặt của môn, ghép phách tự động.
8. Rà soát đủ tất cả học sinh × ba môn, phân biệt bài vắng với thiếu điểm. Kiểm tra phách, người chấm, trạng thái ghép và tài khoản/hội đồng còn hoạt động. Xác nhận rà soát sẽ lưu người/thời điểm và khóa dữ liệu chấm lần đầu. Chỉ sau đó mới công bố điểm. Các mức xử phạt được ghi cùng bài; quy tắc tổng hợp điểm xét tuyển thuộc bước 5.

## Phúc khảo

- Học sinh chọn kỳ của mình tại **Xem điểm**, **Đăng ký phúc khảo**, **Theo dõi phúc khảo**. Chỉ đăng ký trong cửa sổ của kỳ đang công bố, sau công bố điểm, cho bài có mặt và đã có điểm; mỗi bài chỉ một đơn.
- Người quản lý chọn người chấm từ hội đồng phúc khảo đúng môn/kỳ, đang hoạt động và có tài khoản. Người chấm lần đầu không được phúc khảo chính bài đó.
- Giáo viên mở **Phúc khảo → Chấm các đơn được phân công**; chỉ thấy các đơn được giao. Chuyển sang Đang chấm, nhập điểm mới và biên bản, hoặc nhập danh sách theo mẫu. Máy chủ đối chiếu người chấm tại thời điểm ghi; điểm phải hợp lệ theo quy tắc của kỳ.
- Kỳ có cấu hình theo năm giữ nguyên điểm gốc trong `scores`. Kết quả phúc khảo hoàn tất ở `appeal_results` là điểm thay thế, có lịch sử `score_change_logs` và thông báo riêng cho học sinh. Trang xem điểm, tra cứu, bảng điểm giáo viên và dữ liệu đầu vào xét tuyển dùng kết quả hoàn tất này.
- Không bỏ hoặc sửa ba trigger bảo vệ điểm gốc sau công bố. Không ghi đè kết quả phúc khảo hoàn tất. Khi đã có lượt xét tuyển, chặn thay đổi điểm cho đến khi có quy trình xử lý lượt xét tuyển tương ứng. Kỳ cũ chưa có cấu hình theo năm giữ cơ chế bảo vệ cũ.
- Dữ liệu mô phỏng có nhãn tại màn hình liên quan. Không dùng kết quả mô phỏng làm kết quả chính thức.

## Kiểm chứng và cập nhật

`tests/batchGrading_runtime.php` tái sử dụng ca tổ chức thi với đủ 720 học sinh trong CSDL bản sao. Sau khi chuyển bộ dữ liệu thử sang giai đoạn sau thi, tạo 2.160 bài thi, ghi nhận một bài vắng và chấm 2.159 bài có mặt. Kiểm tra cả đọc file XLSX thật, stale preview, khóa điểm, quyền và phạm vi giáo viên, phúc khảo sau công bố, điểm gốc không đổi, nhật ký, thông báo và nguồn điểm cho xét tuyển.

Chạy qua `tests/run_isolated_workflows.php --suite=Grading` với bản sao lưu khớp hiện tại đã thử khôi phục. Các CSDL tạm được xóa và đối chiếu CSDL chính sau kiểm tra.

`database/configure_exam_grading.php --backup=<file.sql>` chỉ thêm bảng `exam_grading_states` sau khi kiểm tra sao lưu. Công cụ không tạo bài thi, điểm, giám khảo hoặc đơn phúc khảo trong dữ liệu đang vận hành.

Kết quả xác minh ngày 21/10/2026: 406 kiểm tra quy trình trước đó và 106 kiểm tra của bộ Grading đều đạt, cùng 57 kiểm tra HTTP trên dữ liệu hiện tại. CSDL chính giữ nguyên mọi dòng ở 43 bảng cũ; bảng rà soát mới chưa có dữ liệu. Bản sao lưu sau cập nhật đã thử khôi phục và đối chiếu đủ 44 bảng.
