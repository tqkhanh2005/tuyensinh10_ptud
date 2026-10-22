<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/school_batch_service.php';
require_permission('school.manage');

if (isset($_GET['template'])) {
    excel_csv_download('mau_them_truong.csv', array_values(school_batch_fields()), []);
}
$rows = school_batch_rows([]);
$errors = [];
$general = '';
$notice = '';
$posted = $_SERVER['REQUEST_METHOD'] === 'POST';
if ($posted) {
    require_csrf();
    $input = $_POST['rows'] ?? [];
    $rows = school_batch_rows(is_array($input) ? $input : []);
    try {
        if (count($rows) > 100) throw new InvalidArgumentException('Mỗi lần chỉ được nhập tối đa 100 trường.');
        if (($_POST['action'] ?? '') === 'import') {
            $imported = school_batch_import($_FILES['excel'] ?? []);
            $current = array_values(array_filter($rows, static fn($r) => count(array_filter($r, static fn($v) => $v !== '')) > 0));
            if (count($current) + count($imported) > 100) throw new InvalidArgumentException('Tổng số dòng không được vượt quá 100.');
            $rows = array_merge($current, $imported);
            $notice = 'Đã nhập dữ liệu vào bảng. Vui lòng rà soát và chọn Lưu tất cả.';
        } elseif (($_POST['action'] ?? '') === 'save') {
            $pdo = db();
            $pdo->beginTransaction();
            try {
                $errors = school_batch_validate($pdo, $rows);
                if ($errors) {
                    $pdo->rollBack();
                } else {
                    foreach ($rows as $row) {
                        // SQL requires a unique school_code; the use case does not ask the actor to enter it.
                        $row['school_code'] = $row['level'] . '_' . strtoupper(bin2hex(random_bytes(10)));
                        school_create($pdo, $row);
                    }
                    $pdo->commit();
                    header('Location: ' . url('/schools/index.php?created=1'));
                    exit;
                }
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                throw $e;
            }
        } else {
            throw new InvalidArgumentException('Thao tác không hợp lệ.');
        }
    } catch (InvalidArgumentException $e) {
        $general = $e->getMessage();
    } catch (Throwable $e) {
        error_log('School batch create: ' . $e->getMessage());
        $general = 'Không thể xử lý dữ liệu. Chưa lưu trường nào. Vui lòng thử lại hoặc kiểm tra cấu hình đọc Excel của máy chủ.';
    }
}
require_once __DIR__ . '/../includes/admin_layout.php';
admin_layout_start('Thêm trường THCS, THPT', 'schools', ['Quản lý trường học' => url('/schools/index.php'), 'Thêm trường' => '']);
?>
<div class="bg-white rounded-4 shadow-sm p-4">
  <h1 class="h3">Thêm trường THCS, THPT</h1>
  <p class="text-muted">Nhập các trường tại TP.HCM. Tất cả 5 thông tin đều bắt buộc. Mã trường được tạo tự động khi lưu. Tối đa 100 dòng mỗi lần.</p>
  <?php if ($general !== ''): ?><div class="alert alert-danger" role="alert"><?= e($general) ?></div><?php endif; ?>
  <?php if ($notice !== ''): ?><div class="alert alert-info" role="status"><?= e($notice) ?></div><?php endif; ?>
  <?php if ($errors): ?>
    <div class="alert alert-danger" role="alert"><strong>Chưa lưu trường nào. Vui lòng sửa các dòng lỗi:</strong><ul>
      <?php foreach ($errors as $i => $messages): ?><li><a href="#school-row-<?= $i ?>">Dòng <?= $i + 1 ?></a>: <?= e(implode(' ', array_unique($messages))) ?></li><?php endforeach; ?>
    </ul></div>
  <?php endif; ?>
  <form method="post" enctype="multipart/form-data" id="school-form" novalidate>
    <?= csrf_field() ?>
    <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
      <button type="button" id="add-row" class="btn btn-outline-primary">Thêm dòng</button>
      <label for="excel" class="form-label mb-0">File Excel</label>
      <input type="file" id="excel" name="excel" accept=".xlsx,.csv" class="form-control w-auto">
      <button type="submit" name="action" value="import" class="btn btn-outline-primary">Nhập từ Excel</button>
      <a href="?template=1" id="download-template">Tải mẫu CSV</a>
    </div>
    <p class="small text-muted">Hỗ trợ .xlsx và CSV UTF-8 (tối đa 2 MB). Cột: Tên trường, Cấp học, Địa chỉ, SĐT, Email. Định dạng cột SĐT là văn bản để giữ số 0 đầu. Dữ liệu nhập từ file được nối vào bảng để rà soát trước khi lưu.</p>
    <div class="table-responsive">
      <table class="table table-bordered align-middle">
        <thead><tr><th scope="col">Dòng</th><?php foreach (school_batch_fields() as $label): ?><th scope="col"><?= e($label) ?> *</th><?php endforeach; ?><th scope="col">Thao tác</th></tr></thead>
        <tbody id="school-rows">
        <?php foreach ($rows as $i => $row): ?>
          <tr id="school-row-<?= $i ?>" class="<?= isset($errors[$i]) ? 'table-danger' : '' ?>">
            <td class="row-number"><?= $i + 1 ?></td>
            <?php foreach (school_batch_fields() as $key => $label): ?>
            <td style="min-width:<?= $key === 'level' ? '110' : '190' ?>px">
              <?php if ($key === 'level'): ?>
                <select class="form-select" data-field="level" name="rows[<?= $i ?>][level]" aria-label="Cấp học dòng <?= $i + 1 ?>" required>
                  <option value="">Chọn cấp học</option>
                  <?php foreach (['THCS', 'THPT'] as $level): ?><option value="<?= $level ?>" <?= $row['level'] === $level ? 'selected' : '' ?>><?= $level ?></option><?php endforeach; ?>
                </select>
              <?php else: ?>
                <input class="form-control" data-field="<?= $key ?>" name="rows[<?= $i ?>][<?= $key ?>]" aria-label="<?= e($label) ?> dòng <?= $i + 1 ?>" type="<?= $key === 'email' ? 'email' : ($key === 'phone' ? 'tel' : 'text') ?>" maxlength="<?= ['name'=>150,'address'=>255,'phone'=>30,'email'=>150][$key] ?>" value="<?= e($row[$key]) ?>" required>
              <?php endif; ?>
              <?php if ($key === 'name' && isset($errors[$i])): ?><div class="small text-danger mt-1"><?= e(implode(' ', array_unique($errors[$i]))) ?></div><?php endif; ?>
            </td>
            <?php endforeach; ?>
            <td><button type="button" class="btn btn-outline-danger remove-row">Xóa dòng</button></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div class="d-flex gap-2 mt-3">
      <button type="submit" name="action" value="save" class="btn btn-primary">Lưu tất cả</button>
      <a class="btn btn-outline-secondary" href="<?= e(url('/schools/index.php')) ?>">Quay lại danh sách</a>
    </div>
  </form>
</div>
<script>
(() => {
  const form = document.getElementById('school-form');
  const body = document.getElementById('school-rows');
  let dirty = <?= $posted ? 'true' : 'false' ?>;
  let submitting = false;
  const warning = 'Có thay đổi chưa lưu, bạn có chắc muốn thoát?';
  const renumber = () => [...body.rows].forEach((row, i) => {
    row.id = `school-row-${i}`;
    row.querySelector('.row-number').textContent = i + 1;
    row.querySelectorAll('[data-field]').forEach(input => {
      input.name = `rows[${i}][${input.dataset.field}]`;
      input.setAttribute('aria-label', `${input.dataset.field} dòng ${i + 1}`);
    });
  });
  form.addEventListener('input', () => { dirty = true; });
  form.addEventListener('change', () => { dirty = true; });
  document.getElementById('add-row').addEventListener('click', () => {
    if (body.rows.length >= 100) { alert('Mỗi lần chỉ được nhập tối đa 100 trường.'); return; }
    const row = body.rows[0].cloneNode(true);
    row.classList.remove('table-danger');
    row.querySelectorAll('.text-danger').forEach(el => el.remove());
    row.querySelectorAll('[data-field]').forEach(input => { input.value = ''; });
    body.appendChild(row);
    renumber(); dirty = true;
    row.querySelector('input').focus();
  });
  body.addEventListener('click', event => {
    const button = event.target.closest('.remove-row');
    if (!button) return;
    if (body.rows.length === 1) { alert('Phải có ít nhất 1 dòng'); return; }
    button.closest('tr').remove(); renumber(); dirty = true;
  });
  form.addEventListener('submit', event => {
    if (event.submitter?.value === 'import' && !document.getElementById('excel').files.length) {
      event.preventDefault(); alert('Vui lòng chọn file Excel.'); return;
    }
    submitting = true;
  });
  document.addEventListener('click', event => {
    const link = event.target.closest('a[href]');
    if (!link || link.id === 'download-template' || link.getAttribute('href').startsWith('#') || !dirty) return;
    if (!confirm(warning)) { event.preventDefault(); return; }
    dirty = false;
  });
  window.addEventListener('beforeunload', event => {
    if (dirty && !submitting) { event.preventDefault(); event.returnValue = warning; }
  });
})();
</script>
<?php admin_layout_end();
