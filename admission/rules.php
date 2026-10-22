<?php
declare(strict_types=1);
require_once __DIR__.'/../includes/workflow_layout.php';
require_once __DIR__.'/../includes/admission_period_configuration.php';
$policy=admission_policy(); workflow_layout_start('Quy tắc tuyển sinh TP.HCM','admission');
?>
<h1 class="h3">Tuyển sinh lớp 10 thường TP.HCM — 2026–2027</h1>
<p><?=e($policy['scope'])?>.</p>
<ul><li>Từ 1 đến 3 nguyện vọng, NV1 ưu tiên cao nhất.</li><li><?=e($policy['formula'])?>; hệ số mỗi môn bằng 1.</li><li>Dự thi đủ ba môn, không vi phạm quy chế; mọi điểm môn lớn hơn 0. Điểm thi thang 10, bước 0,25.</li><li>Ưu tiên nhóm 1/2/3: 2 / 1,5 / 1 điểm. Khuyến khích giải nhất/nhì/ba đủ điều kiện: 1,5 / 1 / 0,5 điểm. Chọn mức cao nhất mỗi loại, cộng tối đa 3,5.</li><li>Điểm chuẩn NV1 không cao hơn NV2; NV2 không cao hơn NV3 ở cùng trường.</li></ul>
<p class="alert alert-warning"><?=e($policy['tie_note'])?></p>
<p>Độ tuổi và các diện ngoại lệ phải được xét hồ sơ. Các trường hợp chuyên, tích hợp và tuyển thẳng cần quy trình riêng.</p>
<h2 class="h5">Nguồn đối chiếu</h2><ul><?php foreach($policy['source_urls'] as $index=>$url):?><li><a href="<?=e($url)?>" rel="noopener noreferrer" target="_blank"><?=$index===0?'Quyết định 1700/QĐ-UBND — kế hoạch 2026–2027':'Công văn 3663/SGDĐT-QLCL — hướng dẫn lớp 10 thường'?></a></li><?php endforeach;?></ul>
<p class="small text-muted">Đối chiếu ngày 20/10/2026. Đây là bộ quy tắc tham chiếu theo năm; không phải thông báo mở đăng ký hiện tại. Lịch và chỉ tiêu của từng kỳ do quản trị thiết lập kèm nguồn.</p>
<?php workflow_layout_end(); ?>
