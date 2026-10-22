> **Tài liệu lịch sử Batch J.** Nội dung bên dưới phản ánh giai đoạn lập tài liệu trước đây; số kiểm tra và trạng thái lúc đó không phải kết luận cho phiên bản hiện tại. Xem [bản đối chiếu hiện thực ngày 22/10/2026](scope_and_implementation.md). Các quyết định nghiệp vụ chưa được đáp ứng đầy đủ vẫn là yêu cầu cần xử lý, không tự đóng theo kết quả kiểm tra kỹ thuật.


# BATCH J — DOCUMENTATION UPDATE REPORT

**Ngày:** 26/09/2026
**Phạm vi:** DOCUMENTATION-ONLY

## A. Tài liệu đã đọc

1. `<span>_ai_source/_uc_extract.txt</span>` — nguồn đặc tả Use Case được đồng bộ.
2. `<span>_ai_source/PROMPT_SONNET_5_REBUILD_TUYENSINH10.md</span>` — kiểm tra ngữ cảnh/prompt nguồn khi cần.
3. `<span>docs/BATCH_J_DECISION_LOG.md</span>`.
4. `<span>docs/BATCH_J_IMPACT_TRACE.md</span>`.
5. `<span>docs/BATCH_J_DOCUMENTATION_UPDATE_REPORT.md</span>` bản trước.

`<span>_uc_extract.txt</span>` là bản trích xuất từ bộ đặc tả nguồn; không ghi đè và không sửa file này.

## B. Tài liệu đã sửa/tạo

* **Sửa:**`<span>docs/BATCH_J_DECISION_LOG.md</span>` — chỉ bổ sung phần làm rõ cách áp dụng DEC-01..09, không đảo ngược quyết định.
* **Sửa:**`<span>docs/BATCH_J_IMPACT_TRACE.md</span>` — cập nhật trace theo từng UC, căn cứ nguồn và trạng thái DONE/PARTIAL/BLOCKED.
* **Sửa:**`<span>docs/BATCH_J_DOCUMENTATION_UPDATE_REPORT.md</span>` — báo cáo đồng bộ hiện tại.
* **Tạo:**`<span>docs/BATCH_J_SYNCHRONIZED_USE_CASE_SPEC.md</span>` — bản đặc tả đồng bộ, không thay thế nguồn.

## C. UC đã đồng bộ

| UC   | Nội dung đồng bộ                                                                                                       | Trạng thái tài liệu |
| ---- | -------------------------------------------------------------------------------------------------------------------------- | ----------------------- |
| UC08 | Kiểm tra điểm thi + kỳ tuyển sinh; không thêm`<span>schools.active</span>`; giữ truy vết hội đồng lịch sử. | PARTIAL                 |
| UC09 | Không thêm school-state dependency.                                                                                      | PARTIAL                 |
| UC10 | Không thêm school-state dependency.                                                                                      | PARTIAL                 |
| UC11 | Không thêm school-state dependency.                                                                                      | PARTIAL                 |
| UC13 | Revalidate trường THCS active tại approval; invalid thì không apply.                                                  | PARTIAL                 |
| UC14 | Validate trường THCS active tại submit.                                                                                 | PARTIAL                 |
| UC17 | Không tự thêm dependency current-period/active.                                                                         | DONE                    |
| UC18 | Ghi nhận gap khi đổi cấp học có thể ảnh hưởng`<span>middle_school_id</span>`.                                  | PARTIAL                 |
| UC19 | Current-period chính thức; dependency theo kỳ; bảo toàn lịch sử; không hard-delete khi phá traceability.          | PARTIAL                 |
| UC31 | `<span>maTruongTHCS</span>`phải là THCS active cho thao tác mới.                                                     | PARTIAL                 |
| UC32 | Trường thay thế phải là THCS active.                                                                                  | PARTIAL                 |
| UC33 | Không coi`<span>middle_school_id</span>`là dependency theo kỳ khi chưa có liên kết kỳ.                           | PARTIAL                 |

## D. Các điểm còn [CẦN BỔ SUNG ĐẶC TẢ]

1. **UC19:** định nghĩa chính thức và duy nhất của “kỳ tuyển sinh hiện tại”; tập dependency nghiệp vụ hoàn chỉnh; hành vi cụ thể khi trường chỉ còn dữ liệu lịch sử; cách biểu diễn việc ngừng tuyển sinh/inactive nếu không hard-delete.
2. **UC13–14:** trạng thái/exception khi trường mới hợp lệ lúc gửi nhưng không còn hợp lệ lúc duyệt. Không tự tạo trạng thái mới.
3. **UC18:** xử lý khi thay đổi `<span>capHoc</span>` của trường đang được tham chiếu bởi `<span>candidates.middle_school_id</span>`.
4. **UC31–33:** chưa xác định được candidate thuộc kỳ nào một cách đáng tin cậy; không thêm `<span>admission_period_id</span>` trong Batch J.

## E. Phân biệt quan hệ

* FK trực tiếp không mặc nhiên là dependency nghiệp vụ.
* UC08 có đường dẫn schema gián tiếp `<span>councils → exam_sites → schools</span>`; không chuyển thành điều kiện `<span>schools.active</span>`.
* UC09–11 không có direct school business dependency được nguồn chứng minh.
* `<span>candidates.middle_school_id</span>` là tham chiếu trường, nhưng chưa được coi là dependency theo kỳ theo DEC-07.
* Dữ liệu lịch sử phải được phân biệt với dữ liệu đang phục vụ kỳ tuyển sinh hiện tại.

## F. Safety Check

| Hạng mục                         | Kết quả                            |
| ---------------------------------- | ------------------------------------ |
| PHP/JS/HTML/CSS                    | **KHÔNG SỬA**                |
| SQL/schema/data/migration          | **KHÔNG SỬA**                |
| Test/fixture                       | **KHÔNG SỬA / KHÔNG CHẠY** |
| DB write/regression                | **KHÔNG THỰC HIỆN**         |
| Auth/RBAC                          | **KHÔNG SỬA**                |
| Protected triggers                 | **KHÔNG TÁC ĐỘNG**         |
| UC03 ↔ UC22                       | **UNCHANGED / UNRESOLVED**     |
| Git commit/push/reset/revert/clean | **KHÔNG THỰC HIỆN**         |
| Phase 0.9                          | **CHƯA HOÀN THÀNH**         |
| Phase 1                            | **CHƯA CHUYỂN**              |

## G. Runtime verification status

Phần dưới đây được cập nhật sau khi chạy runtime suite trong môi trường XAMPP/MySQL
vào ngày 08/10/2026. Kết quả này là bằng chứng kiểm thử riêng cho các batch; không
biến các quyết định tài liệu DEC-01..09 thành triển khai runtime.

| Batch | Kết quả gần nhất | Trạng thái |
| --- | --- | --- |
| B | 14 PASS, 0 FAIL, 0 BLOCKED | PASS |
| C | 21 PASS, 0 FAIL, 0 BLOCKED | PASS |
| D | 34 PASS, 0 FAIL, 0 BLOCKED | PASS |
| E | 30 PASS, 0 FAIL, 0 BLOCKED | PASS; có SPEC_CONFLICT đã ghi nhận |
| F | 32 PASS, 0 FAIL, 0 BLOCKED | PASS; còn warning session/header |
| G1 | 29 PASS, 0 FAIL, 1 BLOCKED | CHƯA HOÀN TẤT; TEST 11 thiếu test hook rollback |
| G2 | 29 PASS, 0 FAIL, 0 BLOCKED | PASS |
| H | 22 PASS, 0 FAIL, 0 BLOCKED | PASS |
| I | 46 PASS, 0 FAIL, 0 BLOCKED | COMPLETE |

Batch I đã được kiểm tra cả service, parser CSV/XLSX và route child-process cho
auth/upload/confirm. Kiểm thử browser/manual với dữ liệu appeal thực tế được
**DEFERRED**, không phải lỗi runtime.

Lệnh chạy một batch:

```powershell
Set-Location 'C:\xampp\htdocs\tuyensinh10_ptud_copy'
& 'C:\xampp\php\php.exe' '.\tests\batchI_runtime.php'
```

Kết quả chấp nhận cho Batch I:

```text
TOTAL: 46 PASS: 46 FAIL: 0 BLOCKED: 0
Protected triggers present: YES
```

Lệnh chạy toàn bộ runtime suite:

```powershell
Get-ChildItem '.\tests\batch*_runtime.php' |
    Sort-Object Name |
    ForEach-Object {
        & 'C:\xampp\php\php.exe' $_.FullName
        if ($LASTEXITCODE -ne 0) { throw "FAILED: $($_.Name)" }
    }
```

Overall runtime suite hiện **chưa hoàn tất tuyệt đối** vì G1 TEST 11 vẫn
`BLOCKED` do chưa có failure hook hợp lệ cho rollback bên trong
`adjustment_review()`.

## H. Phạm vi và việc còn lại

Batch J chỉ là documentation sync. Không được hiểu rằng các quy tắc DEC-01..09
đã được triển khai trong production. Việc tiếp theo được xếp theo thứ tự:

1. Bổ sung test hook hợp lệ cho G1 TEST 11, không hack production.
2. Chạy lại toàn bộ runtime suite và xử lý warning session/header.
3. Thực hiện manual/browser smoke test UC03 khi có fixture appeal ở trạng thái
   `grading`.
4. Chỉ sau khi có đặc tả bổ sung mới triển khai các gap UC19, UC13–14 và UC18.

## I. Kết luận

Batch J đã thực hiện đồng bộ tài liệu theo DEC-01..09 trong phạm vi tài liệu. Việc đồng bộ  **không đồng nghĩa nghiệp vụ đã được triển khai runtime** , và không làm giảm các blocker BJ-01..BJ-06. Fresh regression chưa chạy và không được đánh dấu PASS.

Không có thay đổi code/CSDL/test trong phạm vi đồng bộ này. Không commit hoặc push Git.
