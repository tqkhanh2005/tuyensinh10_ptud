# Bước 5 — Xét tuyển và duyệt kết quả

> Luồng xem trước và quy tắc xét tuyển đã được cập nhật theo đặc tả mới tại [selection_preview_workflow.md](selection_preview_workflow.md). Các mô tả lưu ngay, chạy lại, ngưỡng vòng sau và xử lý đồng điểm bên dưới là tài liệu lịch sử.

Mở **Xét tuyển → Chạy xét tuyển**. Phạm vi triển khai hiện tại là mô phỏng lớp 10 thường THPT công lập TP.HCM 2026–2027. Kỳ chính thức vẫn bị chặn chạy tự động cho đến khi có quy tắc đầy đủ và được nghiệm thu; không đổi nhãn mô phỏng thành kết quả chính thức.

## Quy trình

1. Kỳ phải đang công bố; điểm đã rà soát, ghép phách và công bố. Phải kết thúc cửa sổ phúc khảo và xử lý hết các đơn đang chờ/đang chấm. Cần nguyện vọng và chỉ tiêu THPT hợp lệ. Kỳ nháp không được chạy.
2. Điểm sau phúc khảo hoàn tất thay thế điểm chấm lần đầu. Mô hình áp dụng mức xử phạt đã ghi trên bài đúng một lần, làm tròn điểm từng môn tới hai chữ số rồi cộng ba môn hệ số 1. Đây là quy ước mô phỏng của phần mềm; việc nghiệm thu chính thức phải bao gồm quy tắc xử phạt/làm tròn và phương thức xét tuyển.
3. Dự thi đủ ba môn và mọi điểm môn sau xử lý phải lớn hơn 0. Vắng thi, điểm 0, hồ sơ/tài khoản không hoạt động hoặc cờ đủ điều kiện không đạt được ghi lý do, không trúng tuyển. Bài thi chưa điểm danh, thiếu điểm, chưa ghép phách, điểm sai thang/bước hoặc nguyện vọng sai cấu trúc làm dừng cả lượt để sửa dữ liệu; không tự coi điểm thiếu là 0. Cờ đủ điều kiện là kết quả đối chiếu hồ sơ của người vận hành, không suy đoán ngoại lệ tuổi chỉ từ năm sinh.
4. Trong **Đối chiếu điểm ưu tiên và khuyến khích**, chọn nhóm cao nhất của từng loại và ghi minh chứng/biên bản. Phần mềm tính theo bảng tham chiếu, tổng điểm cộng tối đa 3,5. Không cộng dồn nhiều mức cùng loại. Điểm cộng cũ khác 0 mà chưa đối chiếu sẽ chặn chạy; bản đối chiếu mới là nguồn dùng để tính. Lịch sử đối chiếu có người thực hiện, giá trị và minh chứng trước/sau.
5. Mô hình xét NV1 rồi NV2 rồi NV3, xếp điểm từ cao xuống thấp trong từng trường/vòng. Học sinh đã trúng tuyển không xét tiếp. Chỉ tiêu dùng chung cho cả ba vòng, trừ số đã nhận trước khi xét vòng sau. Ngưỡng tiếp nhận mô phỏng của cùng trường không giảm ở vòng sau; nếu không có người đạt ngưỡng thì để chỗ trống. Mô hình này không thay thế quy trình xác định điểm chuẩn do Sở ban hành.
6. Nếu nhóm đồng điểm vượt số chỗ còn lại, lưu lượt **Cần rà soát đồng điểm**, giữ bản chụp dữ liệu và nhóm cần quyết định; không lưu danh sách trúng tuyển một phần. Chọn đúng số người thuộc nhóm theo biên bản mô phỏng rồi chạy lại. Không tự dùng mã học sinh, SBD, thứ tự dữ liệu hoặc số ngẫu nhiên để quyết định. Biên bản chỉ áp dụng đúng dữ liệu, trường, vòng, điểm và số chỗ của nhóm; dữ liệu thay đổi sẽ không tái sử dụng quyết định cũ.
7. Lượt hoàn tất lưu kết quả cho mọi nguyện vọng, kèm tổng điểm, điểm cộng và trạng thái. Trang quản trị có thống kê theo trường, ngưỡng mô phỏng và lý do loại; phân trang 50 dòng, CSV đủ danh sách và nhãn mô phỏng. Mỗi lượt giữ riêng dữ liệu đầu vào và báo cáo để kiểm tra lại.
8. **Duyệt lượt xét tuyển hiện tại** sẽ kiểm tra lại dữ liệu đang dùng, bản chụp, kết quả và quyết định đồng điểm. Sau duyệt, vào **Công bố kết quả**. Chỉ công bố lượt mới nhất đã tính xong, đã duyệt và còn khớp dữ liệu. Thay đổi dữ liệu sau tính/duyệt buộc chạy lại. Các bước tính, duyệt, công bố dùng khóa kỳ và khóa dữ liệu đầu vào để tránh thay đổi đồng thời.
9. Học sinh/giáo viên chỉ thấy kết quả đã công bố của lượt hoàn tất hiện tại, không thấy các lượt tính thử hoặc bị chặn. Học sinh chỉ xem kết quả của mình; giáo viên chỉ xem phạm vi trường. Không cộng gộp lịch sử nhiều lượt. Chặn nhập học từ kết quả xem trước. Lượt mô phỏng không ghi trạng thái trúng tuyển/nhập học vào trường hồ sơ chung của học sinh.

## Nguồn tham chiếu

Bộ quy tắc lưu ở `data/admission_policy_hcm_2026.json`. Đối chiếu lại ngày 21/10/2026 với [kế hoạch tuyển sinh trên hệ thống Sở GDĐT TP.HCM](https://f1.hcm.edu.vn/data/doc/2026/quanlychatluong/2026_3/30/20032026kehoachtuyensinhbanhanhkemkehoachsigned_303202611.pdf), mục 5.2: ba bài thi hệ số 1, điều kiện dự thi đủ và không có điểm 0, điểm cộng không quá 3,5, thứ tự ưu tiên nguyện vọng. Nguồn này không đủ để tự chứng nhận một thuật toán phân chỉ tiêu hoặc quy tắc phá đồng điểm chính thức. Không nạp lịch, chỉ tiêu hay điểm chuẩn từ địa phương khác.

## Kiểm chứng và dữ liệu chính

`tests/batchSelection_runtime.php` chỉ chạy trong CSDL bản sao do `tests/run_isolated_workflows.php` tạo. Bộ thử kế thừa ca 720 học sinh/2.160 bài thi, đối chiếu XLSX và phúc khảo, sau đó kiểm tra 2.160 nguyện vọng. Có trường hợp vắng, điểm 0, xử phạt, điểm cộng có minh chứng, đồng điểm ở nhiều vòng, dữ liệu thay đổi sau duyệt, bản chụp/kết quả bị sửa và cập nhật đồng thời. Một ca độc lập năm học sinh kiểm tra trường nhận ở cả NV1 và NV2 vẫn không vượt tổng chỉ tiêu; quyết định thủ công có thể chọn người mang mã lớn hơn, chứng minh mã không tự phân xử.

`database/configure_admission_selection.php --backup=<file.sql>` thêm ba bảng: `admission_bonus_reviews`, `admission_run_snapshots`, `admission_tie_resolutions`. Yêu cầu bản sao lưu hiện tại đã thử khôi phục; đối chiếu tất cả bảng cũ sau cập nhật. Công cụ không tạo chỉ tiêu, kết quả xét tuyển hoặc quyết định giả lập trong CSDL đang dùng.

Kết quả xác minh ngày 21/10/2026: các bộ quy trình trước đạt 512 kiểm tra; bộ Selection đạt 160 kiểm tra; 62 kiểm tra HTTP sau cập nhật đều đạt. CSDL chính giữ nguyên mọi dòng của 44 bảng cũ, ba bảng mới chưa có dữ liệu. Sao lưu sau cập nhật đã khôi phục và đối chiếu đủ 47 bảng.
