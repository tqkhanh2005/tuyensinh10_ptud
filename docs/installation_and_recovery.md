# Cài đặt và khôi phục

## Môi trường bàn giao

PHP 8.1 trở lên, MySQL/MariaDB hỗ trợ InnoDB, UTF-8; các phần mở rộng PHP: PDO MySQL, mbstring, ZIP, cURL, SimpleXML và fileinfo. Máy hiện tại sử dụng PHP 8.2 trong XAMPP, múi giờ Asia/Ho_Chi_Minh. Không cần Composer hoặc Node để chạy PHP và tài nguyên CSS/JS đã lưu trong dự án.

Chạy `C:/xampp/php/php.exe -m` để xem phần mở rộng. Công cụ sao lưu/kiểm tra hiện dùng `C:/xampp/mysql/bin/mysql.exe` và `mysqldump.exe`; nếu chuyển sang môi trường khác, điều chỉnh đường dẫn hai chương trình này trong các công cụ trước khi kiểm tra lại.

## Cài trên máy nhận

1. Chép mã nguồn vào thư mục dự án. Không chép các bản SQL cũ trong `database`, tệp tài khoản riêng, báo cáo kiểm tra hay phiên đăng nhập để đưa lên web. Các tệp SQL đánh số là lịch sử cấu trúc, không phải bộ dữ liệu bàn giao đã làm sạch.
2. Nhận riêng bản sao SQL mới nhất cùng tệp `.sql.json` đã có `restore_verified=true`. Bảo quản ngoài thư mục web, chỉ cấp quyền người vận hành.
3. Tạo CSDL mới và tài khoản riêng; nhập bản sao vào CSDL mới. Không nhập đè CSDL đang sử dụng. Bản sao hiện tại gồm cả cấu trúc 001–019; không chạy lại toàn bộ migration trên bản đã khôi phục.
4. Thiết lập `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS` cho tiến trình PHP/Apache. Ví dụ đặt `DB_NAME` là tên CSDL mới rồi khởi động lại máy chủ. Không ghi mật khẩu vào mã nguồn.
5. Mở trang chủ, đăng nhập bằng tài khoản bàn giao riêng. Hai tài khoản giáo viên mô phỏng là `gv.thcs.nguyendu`, `gv.thcs.lequydon`; mật khẩu được cung cấp qua tệp riêng ngoài web. Đổi mật khẩu trước khi dùng với dữ liệu thật.
6. Kiểm tra quyền: quản trị vào khu quản trị, giáo viên vào đúng trường, học sinh chỉ thấy hồ sơ mình. Kiểm tra các đường dẫn `/database/`, `/tests/`, `/includes/`, `/docs/` trả 403 hoặc 404.

Apache cần cho phép `.htaccess`, bật `mod_rewrite` và tắt liệt kê thư mục. Khi dùng máy chủ PHP tích hợp phải truyền `router.php`; không chạy thiếu router vì sẽ mất chính sách chặn tệp riêng. Dữ liệu nhạy cảm không được bảo vệ chỉ bằng việc “không biết đường dẫn”.

## Sao lưu và thử phục hồi

Ví dụ dưới đây dùng thư mục riêng đã tồn tại `D:/TS10-private`, không đặt trong htdocs:

```powershell
C:/xampp/php/php.exe database/backup_database.php D:/TS10-private before_workflow_checks
C:/xampp/php/php.exe database/verify_database_backup.php D:/TS10-private/<ten-ban-sao>.sql
```

Lệnh thứ nhất trả đường dẫn SQL và mã SHA-256. Lệnh thứ hai tạo CSDL tạm có tên `ts10_verify_...`, nhập lại SQL, so sánh toàn bộ bản ghi với CSDL nguồn rồi xóa đúng CSDL tạm đó. Chỉ khi thành công, metadata mới được đánh dấu đã kiểm chứng. Tài khoản kiểm tra cần quyền tạo/xóa CSDL tạm; tách quyền này khỏi tài khoản chạy web khi triển khai.

Khi khôi phục sự cố: dừng các thao tác ghi trên hệ thống, giữ lại bản sao trạng thái sự cố, kiểm tra mã SHA-256 của bản định phục hồi, nhập vào một CSDL mới và đối chiếu trước khi đổi cấu hình sang CSDL đó. Giữ CSDL cũ để có thể quay lại. Công cụ thử khôi phục không phải lệnh khôi phục đè dữ liệu chính.

Sao lưu trước thay đổi cấu trúc/công bố kỳ và định kỳ theo lượng dữ liệu thay đổi. Giữ ít nhất một bản ở thiết bị khác có kiểm soát truy cập. Xóa bản quá hạn theo quy định của đơn vị; không dùng dữ liệu học sinh thật trong kịch bản trình diễn.

## Bộ kiểm tra bàn giao

```powershell
C:/xampp/php/php.exe tests/run_isolated_workflows.php --backup=D:/TS10-private/<ten-ban-sao>.sql --report-dir=D:/TS10-private
```

Mỗi nhóm dùng CSDL tạm riêng, gồm Handover chạy từ tổ chức thi → điểm/phúc khảo → xét tuyển → nhập học → đối chiếu báo cáo và thu hồi quyền. CSDL nguồn được so sánh lại sau kiểm tra. Không chạy trực tiếp các tệp batch trên CSDL đang dùng.

Để kiểm tra web, tạo thư mục phiên riêng rồi mở máy chủ kiểm tra ở một cửa sổ:

```powershell
C:/xampp/php/php.exe -d session.save_path=D:/TS10-private/sessions -S 127.0.0.1:4012 router.php
```

Ở cửa sổ khác:

```powershell
C:/xampp/php/php.exe tests/test_post_import_portal.php --base-url=http://127.0.0.1:4012 --session-dir=D:/TS10-private/sessions --report=D:/TS10-private/portal_handover.json
C:/xampp/php/php.exe database/check_handover.php --backup=D:/TS10-private/<ten-ban-sao>.sql --workflow-report=D:/TS10-private/workflow_report.json --portal-report=D:/TS10-private/portal_handover.json --report=D:/TS10-private/handover_check.json
```

Dừng máy chủ kiểm tra khi xong. Nếu mã nguồn hoặc CSDL thay đổi sau khi chạy, công cụ bàn giao từ chối dùng bằng chứng cũ; tạo sao lưu/chạy lại phần kiểm chứng cần thiết. Báo cáo không chứa mật khẩu.

Kiểm tra bố cục tự động tùy chọn: với Node và Playwright đã có, chạy `node tests/test_responsive_handover.cjs http://127.0.0.1:4012 D:/TS10-private/sessions D:/TS10-private`. Công cụ dùng Edge nếu máy có sẵn, kiểm tra chiều rộng 390/1440 ở bốn vai trò và lưu ảnh cùng `responsive_handover.json` ngoài web. `TS10_PLAYWRIGHT` có thể chỉ tới gói Playwright đã cài, `TS10_PHP` chỉ tới PHP nếu khác đường dẫn XAMPP. Phiên thử được xóa khi xong; không thay đổi CSDL. Kiểm tra tự động đo tràn ngang và lỗi JavaScript; vẫn cần xem ảnh và thử trên thiết bị thực tế của đơn vị tiếp nhận.

## Khi triển khai Internet

Sử dụng Apache/Nginx và HTTPS; cấu hình cookie secure qua kết nối HTTPS thực tế. Tắt `display_errors`, bật `log_errors`, lưu log và phiên ngoài web. Dùng tài khoản CSDL có quyền cần thiết, không dùng root mặc định; chỉ công cụ bảo trì riêng mới có quyền tạo/xóa CSDL. Cấu hình firewall, chống dò đăng nhập tại máy chủ và giám sát theo hạ tầng triển khai thực tế. Kiểm tra lại chặn tệp, tải nhập file, gửi biểu mẫu và phân quyền trên chính máy đích. Bản bàn giao cục bộ không tự thiết lập hạ tầng Internet.

Quy tắc chính thức, xử phạt/làm tròn và đồng điểm vẫn cần được đối chiếu/nghiệm thu cho đúng năm trước khi dùng để quyết định kết quả tuyển sinh thật.
