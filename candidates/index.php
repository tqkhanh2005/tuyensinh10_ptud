<?php
declare(strict_types=1);
require_once __DIR__.'/../includes/config.php';require_once __DIR__.'/../includes/auth.php';require_once __DIR__.'/../includes/candidate_service.php';
require_permission('candidate.manage');$rows=candidate_list();
?><!doctype html><html lang="vi"><head><meta charset="utf-8"><title>Quản lý thí sinh</title></head><body>
<h1>Quản lý danh sách thí sinh/Học sinh</h1><p><a href="<?=e(url('/candidates/create.php'))?>">Thêm mới</a></p>
<table border="1" cellpadding="8"><tr><th>CCCD</th><th>Họ tên</th><th>THCS</th><th>Đủ điều kiện</th><th>Trạng thái</th><th>Thao tác</th></tr>
<?php foreach($rows as $r):?><tr><td><?=e($r['national_id'])?></td><td><?=e($r['full_name'])?></td><td><?=e($r['middle_school_name']??'')?></td><td><?=((int)$r['eligible']===1?'Có':'Không')?></td><td><?=e($r['status'])?></td><td><a href="<?=e(url('/candidates/edit.php?id='.(int)$r['id']))?>">Sửa</a> <form method="post" action="<?=e(url('/candidates/delete.php'))?>" style="display:inline"><?=csrf_field()?><input type="hidden" name="id" value="<?=$r['id']?>"><button onclick="return confirm('Xác nhận xóa/chuyển không hoạt động?')">Xóa</button></form></td></tr><?php endforeach;?></table></body></html>
