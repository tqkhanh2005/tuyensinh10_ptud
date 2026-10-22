<?php
declare(strict_types=1);
require_once __DIR__.'/excel_service.php';
/** CSV spreadsheet applications interpret cells beginning with these characters as formulas. */
function enrollment_csv_cell(mixed $value): mixed {
    return is_string($value) && preg_match('/^[\s\x00-\x1f]*[=+@-]/u',$value) ? "'".$value : $value;
}
function enrollment_roster_download(string $format,int $period,array $rows,?string $end,bool $simulation): void {
    $headers=['SBD','Họ tên','Trường','NV','Điểm xét tuyển','Nhập học','Thời điểm xác nhận','Mã biên nhận','Loại dữ liệu'];$out=[];
    foreach($rows as $r)$out[]=[(string)$r['candidate_code'],$r['full_name'],$r['school_name'],(int)$r['priority_order'],$r['admission_score'],enrollment_state_label($r['enrollment_status'],$end),$r['confirmed_at']??'',$r['receipt_code']??'',$simulation?'MÔ PHỎNG':'Đã công bố'];
    if($format==='csv')excel_csv_download('nhap_hoc_'.$period.'.csv',$headers,array_map(static fn($row)=>array_map('enrollment_csv_cell',$row),$out));
    if($format==='xlsx')excel_xlsx_download('nhap_hoc_'.$period.'.xlsx',$headers,$out);
    throw new InvalidArgumentException('Định dạng tải xuống không hợp lệ.');
}
