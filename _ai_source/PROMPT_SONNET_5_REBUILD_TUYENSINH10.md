Bạn là Sonnet 5, đang được giao rebuild một hệ thống tuyển sinh vào lớp 10 bằng PHP/MySQL từ một codebase cũ.

========================
I. NGUỒN DỮ LIỆU BẮT BUỘC
========================

Bạn sẽ nhận 2 nguồn:

SOURCE A — repomix-output.xml
- Đây là snapshot codebase gốc.
- Dùng để hiểu UI, layout, CSS, JS, assets, routing hiện có, cách tổ chức PHP và dữ liệu tĩnh.
- Không được coi snapshot này là đặc tả nghiệp vụ.
- Không tự suy ra nghiệp vụ mới chỉ vì code cũ đang làm như vậy.

SOURCE B — Use Case specification: “Tuần 4_Báo cáo_Nhóm 4.docx”
- 72 trang.
- Có 38 Use Case được đặc tả, cùng các sơ đồ Use Case, Actor, tiền điều kiện, hậu điều kiện, Basic Flow, Alternative Flow và Exception Flow.
- Đây là nguồn chính để xác định nghiệp vụ và phạm vi chức năng.
- Thuật ngữ và tên Use Case trong tài liệu phải được giữ nguyên về mặt nghiệp vụ.

THỨ TỰ ƯU TIÊN:
1. Use Case specification: nghiệp vụ, quyền, điều kiện, luồng.
2. Codebase gốc: UI/UX, giao diện, assets, routing và các hành vi đã tồn tại.
3. Không dùng kiến thức bên ngoài để tự bịa nghiệp vụ.

Nếu hai nguồn mâu thuẫn:
- Không tự chọn một bên.
- Ghi rõ [SPEC_CONFLICT].
- Nêu hai thông tin đang mâu thuẫn.
- Đề xuất phương án cần xác nhận.
- Chỉ triển khai sau khi có quyết định rõ ràng.

Nếu thông tin cần thiết không có trong cả hai nguồn:
- Ghi [MISSING SPECIFICATION].
- Không tự bịa formula, quota rule, priority rule, permission rule, trạng thái hay dữ liệu.

========================
II. TÓM TẮT CODEBASE ĐÃ KIỂM TRA
========================

Snapshot hiện tại có 36 file và khoảng 25.270 dòng trong repomix-output.xml.

Các thành phần quan trọng:
- index.php
- login.php
- ket-qua.php
- router.php
- server.js
- auth/login.php
- api/captcha.php
- includes/config.php
- includes/header.php
- includes/navbar.php
- includes/footer.php
- includes/scripts.php
- data/quotas.php
- data/schools.php
- data/schools_thcs.php
- data/schools_thpt.php
- data/timeline.php
- views/home/*
- public/app.css
- public/tailwind.css
- archive/index.html
- archive/login.html
- archive/ket-qua.html
- public/vendor/*

Các file vendor/minified chỉ là dependency/asset, không được xem là business logic.

archive/index.html rất lớn và chứa nhiều UI/HTML/CSS cũ. Khi phân tích UI, phải xem nó là nguồn tham khảo giao diện, không phải nguồn nghiệp vụ.

========================
III. TÓM TẮT USE CASE ĐÃ KIỂM TRA
========================

Tài liệu có 38 Use Case:

1. Quản lý phách
2. Nhập điểm
3. Cập nhật kết quả phúc khảo
4. Phân công cán bộ coi thi
5. Thêm thông tin cán bộ - giáo viên
6. Sửa thông tin cán bộ - giáo viên
7. Xóa thông tin cán bộ - giáo viên
8. Thành lập hội đồng coi thi
9. Thành lập hội đồng chấm thi
10. Thành lập hội đồng thi phúc khảo
11. Thành lập hội đồng ra đề
12. Đăng xuất
13. Duyệt yêu cầu điều chỉnh hồ sơ
14. Gửi yêu cầu điều chỉnh hồ sơ
15. Đăng ký nguyện vọng
16. Xem thẻ dự thi
17. Thêm trường THCS, THPT
18. Cập nhật thông tin trường THCS, THPT
19. Xóa trường THCS, THPT
20. Đặt chỉ tiêu tuyển sinh
21. Xét tuyển thí sinh
22. Công bố điểm thi
23. Công bố kết quả tuyển sinh
24. Xem điểm thi
25. Xem KQ xét tuyển
26. Xác nhận nhập học
27. Đăng ký phúc khảo
28. Theo dõi trạng thái phúc khảo
29. Đăng nhập
30. Đổi mật khẩu
31. Thêm thí sinh/Học sinh
32. Sửa thí sinh/Học sinh
33. Xóa thí sinh/Học sinh
34. Xem/Tải danh sách học sinh trúng tuyển
35. Xem chỉ tiêu tuyển sinh
36. Xem thống kê
37. Phân bố thí sinh vào các điểm thi
38. Sắp xếp thí sinh vào phòng thi

Các nhóm Actor/chức năng chính được tài liệu thể hiện gồm:
- Người dùng
- Thí sinh/Học sinh
- Sở GD&ĐT
- Cán bộ tuyển sinh THCS
- Cán bộ xét duyệt
- Cán bộ nhập phách
- Cán bộ nhập điểm
- Chủ tịch Hội đồng coi thi
- Hội đồng chấm thi phúc khảo
- Cán bộ tuyển sinh/Quản trị viên
- Ban Giám hiệu
- Trường THCS/THPT
- các hội đồng ra đề/coi thi/chấm thi/phúc khảo

Tài liệu mô tả 4 hội đồng:
- Hội đồng coi thi
- Hội đồng chấm thi
- Hội đồng thi phúc khảo
- Hội đồng ra đề

Luồng nghiệp vụ tổng quát trong tài liệu:
- Sở GD&ĐT thiết lập kế hoạch tuyển sinh.
- Thiết lập phương thức và chỉ tiêu.
- Công bố chỉ tiêu.
- Thí sinh đăng nhập, đăng ký nguyện vọng.
- Yêu cầu điều chỉnh hồ sơ có thể cần trường THCS/cán bộ xét duyệt phê duyệt.
- Chốt danh sách thí sinh.
- Thành lập các hội đồng.
- Phân bố thí sinh vào điểm thi.
- Sắp xếp vào phòng thi.
- Phân công cán bộ coi thi.
- Quản lý phách.
- Nhập điểm theo số phách.
- Ghép phách.
- Công bố điểm thi.
- Mở phúc khảo.
- Đăng ký và theo dõi phúc khảo.
- Cập nhật điểm phúc khảo.
- Chỉ sau khi điểm chính thức được chốt mới xét tuyển.
- Xét tuyển theo điểm + ưu tiên + chỉ tiêu + thứ tự nguyện vọng theo đặc tả.
- Công bố kết quả tuyển sinh.
- Học sinh xem kết quả.
- Học sinh xác nhận nhập học.

========================
IV. CÁC ĐIỂM CẦN ĐẶC BIỆT CẨN TRỌNG
========================

Trong quá trình đọc Use Case, bạn phải giữ nguyên và kiểm tra các điểm sau, không được tự sửa âm thầm:

1. UC “Quản lý phách”
- Bài thi của thí sinh có mặt mới được phát sinh số phách.
- Thí sinh vắng mặt không có số phách.
- Có xử lý mức trừ điểm 25%, 50%, 100%.
- Có phát sinh phách ngẫu nhiên và nhập phách thủ công.
- Số phách phải duy nhất.
- Có xuất Excel.
- Có hủy thao tác.
- Trạng thái bài thi chuyển “Đã đánh phách”.

2. UC “Nhập điểm”
- Điểm nhập theo số phách.
- Có file mẫu.
- Có kiểm tra cấu trúc file.
- Kiểm tra số phách, môn thi, trạng thái nhập điểm, thang điểm.
- Nếu môn có nhiều phần thì kiểm tra tổng điểm.
- Có preview.
- Chỉ lưu dòng hợp lệ.
- Khi đủ điểm thì tự động ghép phách.

3. UC “Cập nhật kết quả phúc khảo”
- Phải lưu điểm cũ, điểm mới, người chấm, lý do.
- Nếu điểm thay đổi thì cập nhật điểm chính thức và log.
- Có thông báo kết quả.

4. UC “Phân công cán bộ coi thi”
- Mỗi phòng/môn phải có Giám thị 1 và Giám thị 2.
- Không được trùng người trong cùng phân công.
- Không được xung đột thời gian.
- Có xử lý thay thế cán bộ.

5. UC hội đồng
Có 4 loại hội đồng và phải thiết kế dữ liệu đủ để biểu diễn thành viên, vai trò/nhiệm vụ và trạng thái.

6. UC điều chỉnh hồ sơ
- Gửi yêu cầu.
- Trạng thái “Chờ duyệt”.
- Cán bộ/trường xét duyệt.
- Nếu duyệt thì cập nhật hồ sơ.
- Phải có lịch sử/ trạng thái rõ ràng.

7. UC nguyện vọng
- Có thứ tự ưu tiên.
- Cổng đăng ký phải có thời gian mở/đóng.
- Không được tự đặt số lượng nguyện vọng nếu Use Case không quy định rõ.

8. UC xét tuyển
ĐÂY LÀ UC NHẠY CẢM VỀ NGHIỆP VỤ.
Không được tự bịa công thức hoặc quy tắc xếp tuyển.
Phải kiểm tra toàn bộ tài liệu trước khi triển khai.
Phải bảo đảm:
- điểm chính thức đã hoàn tất,
- điều kiện thời gian phù hợp,
- chỉ tiêu đã thiết lập,
- nguyện vọng đã có,
- điểm ưu tiên nếu đặc tả yêu cầu,
- thứ tự nguyện vọng,
- trạng thái trúng tuyển,
- lưu kết quả.
Nếu thiếu công thức/điều kiện thì ghi [MISSING SPECIFICATION].

9. UC công bố
Phân biệt:
- Công bố điểm thi.
- Công bố kết quả tuyển sinh.
Không gộp hai nghiệp vụ này nếu Use Case không cho phép.

10. UC đăng nhập
Phải có authentication + authorization.
Không dùng hardcode quyền trong nhiều file.

11. UC quản lý thí sinh/học sinh
Tài liệu hiện có điểm cần kiểm tra:
- UC thêm, sửa, xóa được đặc tả riêng.
- Một số hậu điều kiện có nội dung về tài khoản thí sinh.
- Có chỗ mô tả cập nhật nhưng lại đề cập xóa tài khoản.
=> Phải đánh dấu [SPEC_CONFLICT] thay vì tự sửa.

12. UC “Xem chỉ tiêu tuyển sinh”
Trong tài liệu, tiền điều kiện có chỗ để trống.
=> Không được tự bịa tiền điều kiện.

13. UC “Xem/Tải danh sách học sinh trúng tuyển”
Phải kiểm tra đặc tả đầy đủ và quyền truy cập.

14. UC “Xem thống kê”
Phải giữ các loại thống kê mà tài liệu mô tả; không tự thêm dashboard nghiệp vụ mới.

15. UC “Phân bố thí sinh vào các điểm thi”
- Tự động theo sức chứa và điều kiện phân bố.
- Có thủ công.
- Có điều chỉnh.
- Không vượt sức chứa.

16. UC “Sắp xếp thí sinh vào phòng thi”
- Không vượt sức chứa.
- Một thí sinh chỉ thuộc một phòng.
- Có tự động, thủ công và điều chỉnh.
- Nếu tài liệu có bước/điều kiện thiếu thì đánh dấu [SPEC_CONFLICT] hoặc [MISSING SPECIFICATION].

========================
V. CÁC QUY TẮC VỀ DATABASE
========================

Tên bảng và tên cột phải:
- English
- snake_case
- không dấu
- nhất quán toàn dự án.

Ví dụ:
users
candidates
schools
admission_periods
aspirations
staff
councils
council_members
exam_sites
exam_rooms
exam_allocations
exam_papers
scores
appeals
admissions
adjustment_requests
import_logs

Cột ví dụ:
id
username
password_hash
full_name
role
email
phone
active
candidate_code
national_id
school_id
priority_order
total_score
result_status
created_at
updated_at

KHÔNG đổi tên nghiệp vụ tiếng Việt trong Use Case chỉ vì database dùng tiếng Anh.

Thiết kế database phải:
- chuẩn hóa hợp lý,
- có PK/FK,
- unique/index phù hợp,
- có trạng thái rõ ràng,
- có audit/log khi nghiệp vụ yêu cầu,
- có transaction cho thao tác nhiều bước,
- không lưu business data trong $_SESSION.

========================
VI. PHP / KIẾN TRÚC
========================

Mục tiêu:
- PHP 8.2+
- MySQL/MariaDB
- PDO + prepared statements
- $_SESSION chỉ giữ trạng thái xác thực/session cần thiết.
- Business data phải ở database.
- Không nối chuỗi SQL với input người dùng.
- Không hardcode password.
- Password phải hash bằng password_hash/password_verify.
- CSRF cho form thay đổi dữ liệu.
- Authorization phải kiểm tra server-side.
- Validate input server-side.
- Escape output phù hợp HTML context.
- Error handling rõ ràng, không lộ SQL/password/stack trace cho người dùng.
- Có transaction cho các nghiệp vụ cập nhật nhiều bảng.

KHÔNG:
- tự chuyển toàn bộ project sang framework.
- tự thay Bootstrap/Tailwind.
- tự đổi layout.
- tự viết lại toàn bộ frontend.
- tự bỏ archive chỉ vì file lớn.
- tự xóa dữ liệu tĩnh đang phục vụ UI.

========================
VII. UI/UX
========================

Mục tiêu là giữ giao diện gốc tối đa.

Ưu tiên:
1. Giữ layout.
2. Giữ màu sắc.
3. Giữ typography.
4. Giữ component.
5. Giữ asset.
6. Chỉ thay phần cần thiết để kết nối nghiệp vụ/database.

Nếu cần thay UI:
- thay tối thiểu,
- giải thích file nào và vì sao,
- không tạo một UI hoàn toàn mới nếu UI cũ đã có.

Không được coi archive HTML là nghiệp vụ.
Archive chỉ dùng để đối chiếu giao diện/hành vi cũ.

========================
VIII. QUY TẮC KHÔNG ĐƯỢC ĐOÁN
========================

Không tự bịa:
- công thức xét tuyển,
- cách tính điểm ưu tiên,
- điều kiện đậu/rớt,
- giới hạn nguyện vọng,
- số lượng phòng,
- số lượng giám thị,
- quy tắc phân bố điểm thi,
- thời hạn nếu tài liệu không ghi,
- quyền của Actor nếu tài liệu chưa xác định,
- trạng thái mới,
- bảng dữ liệu mới nếu không có lý do từ nghiệp vụ.

Nếu cần để hệ thống chạy:
- tạo TODO rõ ràng,
- hoặc hỏi người dùng,
- hoặc đánh dấu [MISSING SPECIFICATION].

========================
IX. PHÂN TÁCH BATCH
========================

Không làm toàn bộ project trong một lần.

Batch 0 — Phân tích:
- đọc toàn bộ hai nguồn,
- inventory codebase,
- inventory 38 UC,
- Actor matrix,
- permission matrix,
- business-rule matrix,
- dependency graph,
- database ERD đề xuất,
- traceability matrix.

Batch 1 — Foundation:
- database schema/migrations,
- PDO/config,
- session,
- authentication,
- authorization,
- login/logout,
- password change nếu đủ đặc tả.

Batch 2 — User/Actor:
- users,
- staff/teacher,
- role/permission,
- quản lý cán bộ giáo viên.

Batch 3 — School + Admission setup:
- schools,
- admission periods/plans,
- quotas,
- công bố chỉ tiêu.

Batch 4 — Candidate:
- candidates/students,
- hồ sơ,
- adjustment requests,
- aspirations,
- exam card.

Batch 5 — Exam:
- exam sites,
- exam rooms,
- allocations,
- invigilators,
- exam papers,
- anonymous codes,
- scores.

Batch 6 — Appeals:
- appeal registration,
- tracking,
- appeal result,
- official score update,
- audit log.

Batch 7 — Admission:
- admission calculation,
- ranking,
- quota,
- aspirations,
- admission result,
- confirm enrollment.

Batch 8 — Publication / Reporting:
- publish exam results,
- publish admission results,
- view results,
- statistics,
- admitted-student lists.

Batch nào cũng phải:
- chạy được,
- có migration SQL,
- có test,
- có rollback/recovery strategy,
- không phá batch trước.

========================
X. DATABASE MIGRATION
========================

Không gửi database bằng cách copy thư mục data của MySQL/MariaDB.

Tạo:
database/
  001_schema.sql
  002_seed.sql
  003_...
  README.md

Mỗi batch phải ghi rõ:
- SQL file nào được thêm/sửa,
- thứ tự import,
- dependency,
- seed account nếu có,
- cách rollback nếu cần.

Không được có tình trạng code yêu cầu bảng nhưng ZIP lại thiếu SQL tạo bảng.

========================
XI. TEST BẮT BUỘC
========================

Mỗi Use Case phải có test tối thiểu:
- Positive
- Negative
- Boundary
- Exception
- Authorization
- Validation
- Persistence
- Duplicate/conflict nếu có
- Cancel nếu Use Case có
- Multi-session/concurrency nếu nghiệp vụ có nguy cơ tranh chấp.

Đặc biệt:
- Không cho user không có quyền truy cập endpoint trực tiếp.
- Không tin dữ liệu từ frontend.
- Không cho trùng số phách.
- Không cho vượt sức chứa.
- Không cho sửa/xóa dữ liệu ở trạng thái không cho phép.
- Không xét tuyển trước khi đủ điều kiện theo đặc tả.
- Không dùng điểm cũ nếu đã có điểm phúc khảo chính thức.

========================
XII. TRACEABILITY
========================

Tạo bảng:

UC ID | Use Case | Actor chính | Actor phụ | Tables | Pages/Routes | PHP files | Business rules | Test cases | Status

Mọi chức năng phải truy ngược được:
Use Case -> route/page -> service/business logic -> repository/PDO -> table.

Không được có chức năng “mồ côi”.

========================
XIII. QUY TẮC LÀM VIỆC VỚI CODE
========================

Trước khi sửa file:
- đọc file hiện tại,
- hiểu dependency,
- chỉ sửa phần cần thiết.

Không:
- overwrite hàng loạt file,
- tạo bản sao `file_new.php`, `file_final.php`, `file_final2.php`,
- duplicate logic,
- hardcode dữ liệu nghiệp vụ trong HTML,
- duplicate SQL.

Nếu tạo service/repository:
- giữ cấu trúc đơn giản, phù hợp project hiện tại,
- không over-engineer.

========================
XIV. OUTPUT CỦA BATCH
========================

Mỗi batch phải trả về:

1. Tóm tắt đã làm.
2. Danh sách file tạo mới.
3. Danh sách file sửa.
4. Database migrations.
5. Business rules đã triển khai.
6. Use Cases đã hoàn thành.
7. Use Cases còn thiếu.
8. [MISSING SPECIFICATION].
9. [SPEC_CONFLICT].
10. Test cases đã chạy.
11. Kết quả test.
12. Hướng dẫn chạy.
13. Các rủi ro còn lại.
14. Commit message đề xuất.

Nếu tạo ZIP:
- ZIP phải chứa đầy đủ SQL/migration cần thiết.
- Kiểm tra ZIP trước khi trả.
- README phải chỉ rõ thứ tự import SQL.
- Không nói “đã có file X” nếu file X thực sự không nằm trong ZIP.

========================
XV. YÊU CẦU RIÊNG CHO PHẢN HỒI ĐẦU TIÊN
========================

Ở PHẢN HỒI ĐẦU TIÊN, CHƯA ĐƯỢC VIẾT CODE.

Hãy chỉ tạo một “Implementation Analysis Report” gồm:

A. Codebase inventory
B. UI/UX inventory
C. Routing inventory
D. Data inventory
E. Actor matrix
F. 38 Use Case matrix
G. Permission matrix
H. Business rule matrix
I. Dependency graph
J. Database ERD đề xuất
K. State/status model
L. Security model
M. Batch plan
N. Traceability plan
O. [MISSING SPECIFICATION]
P. [SPEC_CONFLICT]
Q. Các câu hỏi cần người dùng xác nhận

Đặc biệt phải kiểm tra lại toàn bộ 38 UC, không chỉ 4–5 UC đầu tiên.

Sau báo cáo:
DỪNG LẠI.
Không code.
Không tạo SQL.
Không sửa file.
Chờ người dùng xác nhận Batch 1.

========================
XVI. TIÊU CHÍ THÀNH CÔNG
========================

Hệ thống cuối cùng phải:
- đúng 38 Use Case trong phạm vi tài liệu,
- đúng Actor và quyền,
- nghiệp vụ truy xuất được từ Use Case,
- database nhất quán,
- PHP an toàn,
- không hardcode business logic,
- UI giữ gần codebase gốc,
- migration đầy đủ,
- test được,
- không phá chức năng cũ không liên quan,
- không tự ý thêm nghiệp vụ ngoài tài liệu.

Quan trọng nhất:
ĐỪNG ĐOÁN.
ĐỪNG TỰ ĐỔI NGHIỆP VỤ.
ĐỪNG VIẾT CODE TRƯỚC KHI HOÀN TẤT PHÂN TÍCH.
