# Xét tuyển theo đặc tả cập nhật

Trang `admission/calculate.php` chạy ba vòng NV1, NV2, NV3 dùng chung chỉ tiêu mỗi trường. Người đã trúng không xét tiếp. Không áp dụng sàn điểm từ vòng trước cho vòng sau.

- Chạy xét tuyển chỉ đọc CSDL và giữ bản xem trước trong session. Hủy xóa bản xem trước, không ghi kết quả hay nhật ký vào CSDL.
- Xác nhận kiểm tra quyền `admission.manage`, CSRF, token bản xem trước, khóa kỳ và dữ liệu đầu vào; kiểm tra lại tiền điều kiện và hash rồi tính lại. Kết quả, điểm chuẩn trong JSON báo cáo `admission_run_snapshots.report.cutoffs`, bản chụp và nhật ký được lưu cùng một transaction. Lỗi sẽ rollback.
- Chọn E4: chặn khi đã có lượt `calculated` hoặc `finalized`. `calculated` hiển thị “Đã xét tuyển, chờ duyệt”; không thay enum SQL. Các lượt cũ cần quy trình quản trị riêng, không xóa qua màn hình này.
- Chưa công bố/chốt điểm, chưa hết hạn phúc khảo, phúc khảo chưa xử lý, không có nguyện vọng hoặc trường trong nguyện vọng thiếu chỉ tiêu đều chặn chạy.
- Thiếu điểm, thiếu SBD, nguyện vọng không đủ NV1/NV2/NV3, vắng thi, điểm 0, hồ sơ không hợp lệ được loại và hiển thị lý do.
- Ba môn hệ số 1, dùng điểm phúc khảo đã hoàn tất và xử phạt hiện có. Giữ bảng nhóm ưu tiên/khuyến khích đã đối chiếu của dự án. Đồng tổng điểm thì so tổng điểm gốc ba môn (không gồm bất kỳ điểm cộng nào), kể cả khi chỉ còn một chỗ. Nếu cả hai tổng vẫn bằng nhau tại ranh giới thì chưa cho lưu, không tự chọn theo SBD.
- Điểm chuẩn từng trường là điểm xét tuyển nhỏ nhất của người trúng; trường chưa đủ chỉ tiêu cũng dùng quy tắc này, không ai trúng thì để trống. Điểm chuẩn từng NV vẫn lưu cùng báo cáo.

SQL người dùng cung cấp đã có các bảng cần dùng; không cần đổi cấu trúc. Không import hoặc sửa dữ liệu thực trong quá trình triển khai. Phạm vi mô phỏng TP.HCM hiện tại vẫn được giữ; đây không phải xác nhận quy tắc tuyển sinh chính thức.

Kiểm tra thuật toán: `D:\xampp\php\php.exe tests/selection_preview_runtime.php`. Bộ kiểm thử Selection lịch sử kiểm tra hành vi cũ (ghi ngay khi chạy, cho phép chạy lại, thiếu điểm chặn cả kỳ), không còn là tiêu chí cho luồng mới. Chưa kiểm chứng tích hợp transaction/rollback bằng CSDL bản sao hay trình duyệt.
