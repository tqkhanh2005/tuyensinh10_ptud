# Bước 3 — Tổ chức thi

Phạm vi: tuyển sinh lớp 10 thường TP.HCM. CSDL chính giữ kỳ mô phỏng ở trạng thái nháp; chưa tự điền lịch thi, điểm thi hay giám thị. Tên trường thật không có nghĩa trường đã được chỉ định làm điểm thi chính thức.

## Thao tác

1. Trong **Xét tuyển → Thiết lập kế hoạch → Tổ chức thi**, chọn đúng kỳ. Cũng có thể vào **Quản lý kỳ thi → Tổ chức thi**.
2. Tạo điểm thi có địa chỉ, sức chứa nguyên dương; tạo các phòng. Tổng sức chứa phòng không vượt sức chứa điểm thi.
3. Nhập lịch Ngữ văn, Toán và Ngoại ngữ theo kế hoạch áp dụng. Thời lượng tham chiếu lần lượt 120, 120 và 90 phút. Chặn trùng giờ, ngày sai, qua nửa đêm, thi trước khi hết điều chỉnh hoặc thi trong giai đoạn phúc khảo. Môn đã phân công giám thị không được đổi lịch trực tiếp.
4. Khi học sinh đã đăng ký nguyện vọng, xếp điểm và phòng tự động. Giữ phân bố đã có, chỉ bổ sung học sinh chưa xếp; thiếu sức chứa ở bất kỳ điểm nào sẽ hoàn tác toàn bộ lần xếp mới. Các màn hình phân bổ thủ công vẫn có thể dùng trước khi phát hành.
5. Cấp SBD với tiền tố nội bộ gồm 1–10 chữ in hoa/chữ số. Số có sáu chữ số thứ tự tối thiểu, duy nhất trong kỳ, lưu riêng với hồ sơ học sinh. Chạy lại giữ số cũ và chỉ bổ sung học sinh mới. Đây không phải quy ước SBD chính thức của Sở.
6. Lập hội đồng coi thi cho từng điểm, chọn giáo viên đang hoạt động rồi phân công hai người khác nhau cho từng phòng/môn. Chặn một người coi hai phòng trùng giờ, kể cả khác kỳ. Có chức năng thay thế trước phát hành.
7. Rà soát các điều kiện trên trang tổ chức thi. Chỉ phát hành khi kỳ đã công bố, hết điều chỉnh nguyện vọng, chưa bắt đầu thi, đủ học sinh hợp lệ được xếp phòng/cấp số và đủ giám thị thuộc đúng hội đồng. Sau phát hành, cấu hình tổ chức thi khóa; chưa có quy trình mở khóa hay thay giám thị khẩn cấp sau phát hành.
8. Học sinh vào **Thẻ dự thi** để xem và in/lưu PDF bằng hộp thoại in của trình duyệt. Phiếu có SBD, điểm thi, địa chỉ, phòng và lịch ba môn; dữ liệu theo đúng kỳ và tài khoản. Quản trị tải CSV đủ danh sách; màn hình chỉ hiển thị 50 học sinh/trang. Phiếu và CSV của kỳ mô phỏng có nhãn **MÔ PHỎNG**.

## Kiểm chứng

`tests/batchOrganization_runtime.php` chỉ chạy trong CSDL bản sao đúng mẫu của `tests/run_isolated_workflows.php`, không chạy trực tiếp trên CSDL chính. Bộ thử dùng đủ 720 học sinh, 2 điểm/18 phòng/40 học sinh mỗi phòng và 36 giám thị giả lập để phân công 108 vị trí cho ba môn. Giáo viên giả lập chỉ tồn tại trong bản sao, được xóa cùng bản sao sau kiểm tra.

Kiểm tra bao gồm sức chứa và hoàn tác nguyên khối, số duy nhất/ổn định, trùng lịch giám thị, điều kiện phát hành, khóa sau phát hành, phiếu riêng theo kỳ, quyền truy cập, CSRF, phân trang và CSV 720 dòng. Các trường hồ sơ học sinh được đối chiếu trước/sau để tránh ghi đè SBD vào hồ sơ chung.

`database/configure_exam_organization.php --backup=<file.sql>` thêm hai bảng lưu SBD và trạng thái phát hành; yêu cầu sao lưu hiện tại đã thử khôi phục. Công cụ đối chiếu toàn bộ bảng có trước, không tạo dữ liệu tổ chức thi.

Kết quả xác minh ngày 21/10/2026: 406 kiểm tra quy trình (53 mục tổ chức thi) và 51 kiểm tra HTTP đạt. CSDL chính giữ nguyên toàn bộ dữ liệu của 41 bảng cũ; hai bảng mới còn trống. Bản sao lưu sau cập nhật đã khôi phục và đối chiếu đủ 43 bảng.
