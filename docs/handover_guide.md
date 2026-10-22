# Sử dụng và trình diễn

Đã có [hai bản demo riêng và kịch bản 15 phút](demo_environment.md), kèm công cụ mở, dừng và làm lại dữ liệu ngoài thư mục web.

Bản đối chiếu phạm vi hiện thực ngày 22/10/2026: [38 use case, kiến trúc, dữ liệu và giới hạn](scope_and_implementation.md), kèm [báo cáo PDF](report/bao_cao_pham_vi_hien_thuc_TPHCM.pdf). Tài liệu này không chứng nhận xét tuyển chính thức.

## Phạm vi bàn giao

Dự án tuyển sinh lớp 10 thường THPT công lập TP.HCM. Dữ liệu chuẩn bị có 720 hồ sơ giả lập, 360 học sinh mỗi trường THCS Nguyễn Du và Lê Quý Đôn, cùng hai tài khoản giáo viên liên kết trường. Không hiển thị mật khẩu trong báo cáo hay buổi trình diễn.

Kỳ đang lưu trong CSDL nguồn là bản nháp mô phỏng, không tự đặt lịch, chỉ tiêu hoặc công bố kết quả. Bộ máy xét tuyển chính thức vẫn bị chặn đến khi được nghiệm thu đầy đủ. Các thông báo mô phỏng phải giữ nguyên nhãn trên trang và tệp xuất.

## Theo từng vai trò

- **Quản trị / Sở GD&ĐT:** quản lý trường, học sinh, giáo viên; duyệt yêu cầu điều chỉnh; cấu hình kỳ, thời hạn, chỉ tiêu; tổ chức thi; rà soát điểm, phúc khảo; chạy và duyệt lượt xét; công bố; theo dõi nhập học. Mở **Nhật ký kỳ tuyển sinh** để lọc kỳ/thao tác, xem người thực hiện và thời điểm. Nhật ký điểm/phúc khảo vẫn tra cứu tại nghiệp vụ tương ứng.
- **Giáo viên THCS:** chỉ xem học sinh trường mình, rà soát hồ sơ/nguyện vọng, gửi điều chỉnh, theo dõi kết quả và báo cáo theo kỳ.
- **Giáo viên THPT:** chỉ xem danh sách trúng tuyển vào trường mình cùng trạng thái/thời điểm xác nhận. Nếu được giao nhiệm vụ thi/chấm/phúc khảo, chỉ thao tác trong phạm vi phân công.
- **Học sinh:** xem hồ sơ cá nhân, đăng ký thứ tự nguyện vọng trong hạn, xem phiếu dự thi, điểm/kết quả đã công bố, đăng ký và theo dõi phúc khảo, xác nhận nhập học và in biên nhận.

Tham khảo [quy trình giáo viên–học sinh](teacher_student_workflow.md), [cấu hình kỳ](admission_period_configuration.md), [tổ chức thi](exam_organization.md), [chấm thi](exam_grading.md), [xét tuyển](admission_selection.md), [nhập học](enrollment_confirmation.md).

## Kịch bản trình diễn trên bản sao

1. **Chuẩn bị:** khôi phục bản sao vào CSDL mô phỏng riêng, dùng cấu hình kết nối riêng. Không đổi cấu hình tiến trình web đang sử dụng dữ liệu chính. Chụp lại số lượng hồ sơ ban đầu.
2. **Hồ sơ và nguyện vọng:** đăng nhập hai giáo viên, kiểm tra mỗi người chỉ thấy 360 hồ sơ trường mình. Đăng nhập một học sinh, đặt ba nguyện vọng; thử trùng trường, đảo thứ tự và ngoài hạn. Gửi một điều chỉnh để quản trị duyệt.
3. **Tổ chức thi:** dùng kế hoạch mô phỏng có thời hạn phù hợp, thiết lập ba môn, phòng/sức chứa, cấp SBD và phân công. Rà soát trước khi phát hành phiếu. Không dùng lịch mô phỏng làm lịch chính thức.
4. **Điểm và phúc khảo:** điểm danh, cấp phách, phân công nhập/rà soát điểm; khóa và công bố điểm. Xử lý một đơn phúc khảo; kiểm tra điểm gốc và lịch sử còn nguyên.
5. **Xét tuyển:** nhập chỉ tiêu mô phỏng, điểm cộng có minh chứng, chạy NV1→NV2→NV3. Nếu đồng điểm tại ranh giới thì ghi biên bản, không tự chọn theo mã học sinh. Duyệt lượt mới nhất, công bố và kiểm tra học sinh chỉ trúng một nguyện vọng.
6. **Nhập học:** học sinh xác nhận trong hạn, gửi lại để chứng minh không tạo bản trùng, mở/in biên nhận. Giáo viên xem đúng phạm vi trường; quản trị lọc trạng thái và xuất danh sách.
7. **Đối chiếu:** so sánh báo cáo theo kỳ với danh sách trúng tuyển; thống kê kết quả đếm người, không đếm 2.160 nguyện vọng thành 2.160 học sinh. Xem nhật ký theo kỳ và thử khóa tài khoản/thu hồi vai trò trên bản sao để xác nhận phiên cũ bị chặn.
8. **Kết thúc:** lưu bằng chứng kiểm tra trong thư mục riêng, dừng máy chủ mô phỏng, xóa đúng CSDL tạm hoặc giữ bản sao đã ghi nhãn phục vụ trình diễn. Kiểm tra CSDL nguồn không thay đổi.

Bộ kiểm tra tự động Handover thực hiện kịch bản thi–điểm–phúc khảo–xét tuyển–nhập học và đối chiếu bằng CSDL tạm. Không cần dựng dữ liệu thử trong CSDL nguồn để chứng minh chức năng.

## Dấu hiệu cần xử lý

- Trang chưa có dữ liệu: kiểm tra đúng kỳ và mốc đã công bố, không điền dữ liệu giả để bỏ trạng thái rỗng.
- Lưu bị chặn ngoài hạn: kiểm tra thời gian máy chủ và kế hoạch, không sửa đồng hồ để vượt khóa.
- Không thấy học sinh: kiểm tra vai trò đang hoạt động và liên kết trường; không mở quyền xem toàn hệ thống cho giáo viên.
- Kết quả thay đổi sau duyệt: chạy lại xét tuyển và duyệt lại theo thông báo; không sửa tay bảng kết quả.
- Quá hạn nhập học: nhãn quá hạn không tự hủy trúng tuyển hoặc cấp lại chỗ.
- Có lỗi PHP/kết nối: xem log máy chủ; không chia sẻ log có dữ liệu cá nhân qua trang công khai.
