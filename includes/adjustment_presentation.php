<?php
declare(strict_types=1);
function adjustment_field_label(string $field): string {
    return ['national_id'=>'CCCD/CMND','full_name'=>'Họ và tên','date_of_birth'=>'Ngày sinh','gender'=>'Giới tính','address'=>'Địa chỉ cư trú','phone'=>'Số điện thoại','email'=>'Email','middle_school_id'=>'Trường THCS'][$field]??'Thông tin khác';
}
function adjustment_visible_changes(array $old,array $new): array {
    $changes=[];foreach($new as $field=>$value)if((string)($old[$field]??'')!==(string)$value)$changes[]=['label'=>adjustment_field_label((string)$field),'before'=>(string)($old[$field]??''),'after'=>(string)$value];return $changes;
}
