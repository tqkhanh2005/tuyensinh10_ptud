# Tiếp tục vận hành sau khi nhập học sinh

Bản đối chiếu phạm vi hiện thực ngày 22/10/2026: [38 use case, kiến trúc, dữ liệu và giới hạn](scope_and_implementation.md), kèm [báo cáo PDF](report/bao_cao_pham_vi_hien_thuc_TPHCM.pdf). Tài liệu này không chứng nhận xét tuyển chính thức.

Dự án tuyển sinh lớp 10 tại TP.HCM. Dữ liệu hiện có gồm 720 học sinh giả lập của hai trường THCS. Không dùng dữ liệu minh họa làm lịch thi, chỉ tiêu hay kết quả chính thức.

## Thứ tự thiết lập

1. Mở **Tổng quan quản trị** để xem các mục đã có dữ liệu và các mục còn thiếu. Các chỉ số chỉ phản ánh dữ liệu đã lưu, không chứng nhận đủ điều kiện vận hành.
2. Trong **Trường học**, bổ sung các trường THPT tại TP.HCM từ nguồn xác thực. Mã trường nội bộ không mặc nhiên là mã tuyển sinh chính thức.
3. Trong **Giáo viên**, thêm hoặc nhập giáo viên; liên kết đúng trường và tài khoản có vai trò giáo viên đang hoạt động.
4. Trong **Xét tuyển**, tạo kế hoạch ở trạng thái bản nháp. Chỉ điền thời hạn đăng ký, phúc khảo, nhập học và chỉ tiêu theo hồ sơ được phê duyệt; kiểm tra trước khi công bố.
5. Tiếp nhận nguyện vọng, thiết lập môn thi, điểm thi, phòng thi, hội đồng và phân công. Kiểm tra sức chứa và phạm vi kỳ tuyển sinh trước khi xác nhận.
6. Sau thi, hoàn tất điểm danh, số phách và điểm thi; xử lý phúc khảo trước khi xét tuyển. Kiểm tra kết quả trước khi công bố và mở xác nhận nhập học.

## Chạy lại kiểm tra mà không tạo dữ liệu rác trong CSDL chính

`tests/run_isolated_workflows.php` chỉ chạy bằng dòng lệnh. Công cụ yêu cầu bản sao lưu đã được `database/verify_database_backup.php` thử khôi phục, có dữ liệu khớp với CSDL hiện tại, cùng thư mục báo cáo nằm ngoài thư mục web.

Truyền `--backup=<đường dẫn SQL>` và `--report-dir=<thư mục riêng>`. Có thể truyền thêm `--suite=B` hoặc C, D, E, F, G1, G2, H, I, School, Period, Organization, Grading, Selection. Mỗi nhóm dùng CSDL tạm riêng được khôi phục từ bản sao, kế thừa biến cấu hình kết nối; công cụ xóa đúng bản sao vừa tạo khi kết thúc và đối chiếu dữ liệu chính.

`tests/test_post_import_portal.php` kiểm tra các trang qua HTTP cục bộ và không sửa dữ liệu CSDL. Truyền `--base-url=<địa chỉ cục bộ>`, `--session-dir=<thư mục phiên riêng của máy chủ kiểm tra>` và tùy chọn `--report=<đường dẫn JSON riêng>`. Không dùng thư mục phiên của người đang vận hành để tránh ảnh hưởng phiên đăng nhập của họ.

Các bộ kiểm tra xác nhận hành vi của phần mềm hiện tại. Chúng không thay thế đối chiếu quy chế tuyển sinh TP.HCM của năm áp dụng, đặc biệt các quy tắc ưu tiên và xử lý đồng điểm.

Các thư mục `database`, `tests`, `scratch`, `includes`, `data`, `docs`, `outputs` cùng tệp cấu hình và bản sao lưu bị chặn truy cập trực tiếp qua web trên cả Apache và máy chủ PHP cục bộ. Tệp nhập liệu vẫn có thể chọn từ máy tính qua biểu mẫu import.

Chi tiết bước tổ chức thi, cấp SBD và phát hành phiếu: [exam_organization.md](exam_organization.md).

Chi tiết bước chấm thi, khóa điểm và phúc khảo: [exam_grading.md](exam_grading.md).

Chi tiết bước xét tuyển, đồng điểm và duyệt kết quả: [admission_selection.md](admission_selection.md).

Chi tiết bước xác nhận nhập học, biên nhận và theo dõi danh sách: [enrollment_confirmation.md](enrollment_confirmation.md). Bộ kiểm tra riêng: `--suite=Enrollment`.

Bước 7 bàn giao: [cài đặt và phục hồi](installation_and_recovery.md), [hướng dẫn theo vai trò và kịch bản trình diễn](handover_guide.md). Bộ kiểm tra xuyên suốt: `--suite=Handover`. Công cụ `database/check_handover.php` đối chiếu sao lưu, kết quả kiểm tra, mã nguồn và dữ liệu hiện tại trước khi xác nhận bàn giao mô phỏng.

## Nghiệm thu thao tác trực tiếp

`tests/prepare_role_uat.php --backup=<bản SQL đã kiểm chứng> --private-dir=<thư mục riêng ngoài web>` khôi phục hai CSDL tạm: đăng ký/hồ sơ và kết quả/nhập học. Tệp `role_uat.json` ghi đúng tên CSDL, kỳ và hồ sơ mẫu để trỏ tiến trình web thử riêng; không đổi cấu hình máy chủ đang sử dụng CSDL nguồn. Bộ dữ liệu kết quả được tạo qua kịch bản Enrollment. Hai môi trường chỉ phục vụ mô phỏng, không phải dữ liệu tuyển sinh chính thức.

Kiểm tra lần lượt: học sinh lưu nguyện vọng và thử trùng trường; hai giáo viên tìm học sinh, xem nguyện vọng và gửi điều chỉnh; quản trị vào **Duyệt điều chỉnh hồ sơ**, so sánh trước/sau và duyệt; giáo viên xem phản hồi. Ở CSDL kết quả, học sinh xác nhận đúng trường/nguyện vọng, mở biên nhận; giáo viên và quản trị đối chiếu trạng thái, thời điểm, nhật ký. Tách việc kiểm tra luồng sau đăng nhập bằng phiên thử khỏi kiểm tra đăng nhập thực tế; mã xác nhận đăng nhập do người kiểm thử hoàn tất.

Dừng máy chủ thử và đóng tab trước khi dọn dẹp bằng `tests/prepare_role_uat.php --cleanup=<đường dẫn role_uat.json>`. Công cụ chỉ xóa các CSDL tạm có tên hợp lệ trong manifest và đối chiếu dữ liệu nguồn không thay đổi. Không đưa manifest, phiên đăng nhập hay bộ định tuyến thử vào thư mục web hoặc bản bàn giao công khai.
