# Tuyển sinh lớp 10 TP.HCM

Ứng dụng PHP/MySQL phục vụ đồ án tuyển sinh lớp 10 thường THPT công lập. Bản hiện tại dùng dữ liệu mô phỏng; không phải cổng tuyển sinh chính thức. Giao diện Cần Thơ chỉ là tham khảo thiết kế.

## Tài liệu bàn giao

- [Tệp đã dọn và quy ước bàn giao tài khoản](docs/project_cleanup.md)

- [Mở hai bản demo, tài khoản và kịch bản 15 phút](docs/demo_environment.md)

- [Báo cáo phạm vi, 38 use case và 5 sơ đồ hiện thực](docs/scope_and_implementation.md)
- [Bản PDF phục vụ báo cáo và bảo vệ](docs/report/bao_cao_pham_vi_hien_thuc_TPHCM.pdf)

- [Cài đặt, sao lưu và khôi phục](docs/installation_and_recovery.md)
- [Sử dụng theo từng vai trò và kịch bản trình diễn](docs/handover_guide.md)
- [Thứ tự vận hành và chạy kiểm tra](docs/operating_workflow.md)
- [Phạm vi và giới hạn](docs/project_context.md)

## Chạy tại máy hiện tại

Khởi động MySQL và Apache trong XAMPP, mở `http://localhost/tuyensinh10_ptud_copy/`.

Hoặc từ thư mục dự án chạy:

```powershell
C:/xampp/php/php.exe -S 127.0.0.1:4000 router.php
```

Sau đó mở `http://127.0.0.1:4000/`. Máy chủ PHP này chỉ phục vụ phát triển cục bộ. Không cần cài Node để chạy ứng dụng PHP; `npm start` chỉ là lựa chọn khởi động máy chủ PHP qua Node.

Kết nối CSDL nhận các biến môi trường `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`. Giá trị mặc định dành cho XAMPP cục bộ, không phải cấu hình triển khai Internet. Không lưu mật khẩu hoặc bản SQL vào kho mã hay thư mục web.

## Kiểm tra bàn giao

1. Tạo bản sao bằng `database/backup_database.php` vào thư mục riêng ngoài web.
2. Thử khôi phục bằng `database/verify_database_backup.php`.
3. Chạy toàn bộ `tests/run_isolated_workflows.php` trên các bản sao tạm.
4. Chạy `tests/test_post_import_portal.php` với máy chủ và thư mục phiên riêng.
5. Dùng `database/check_handover.php` đối chiếu bằng chứng với đúng mã nguồn và CSDL hiện tại.

Tham số chi tiết nằm trong hướng dẫn cài đặt. Công cụ bàn giao đánh dấu khả năng bàn giao **mô phỏng**, không chứng nhận thuật toán xét tuyển chính thức.
