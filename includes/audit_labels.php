<?php
declare(strict_types=1);
function period_event_label(string $action): string {
    return ['save_draft'=>'Lưu bản nháp','published'=>'Công bố kỳ','closed'=>'Đóng kỳ','quota_saved'=>'Lưu chỉ tiêu','quota_published'=>'Công bố chỉ tiêu','exam_schedule'=>'Lưu lịch thi','exam_released'=>'Phát hành phiếu dự thi','grading_assign'=>'Phân công chấm thi','grading_review'=>'Rà soát và khóa điểm','selection_bonus'=>'Đối chiếu điểm cộng','selection_calculated'=>'Chạy xét tuyển','selection_tie'=>'Rà soát đồng điểm mô phỏng','selection_review'=>'Duyệt lượt xét tuyển','enrollment_confirmed'=>'Xác nhận nhập học'][$action]??$action;
}
