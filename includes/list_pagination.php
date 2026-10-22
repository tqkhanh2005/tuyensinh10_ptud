<?php
// The caller supplies the counted result set, page and route; preserve only scalar filters.
$pageCount = $pageCount ?? 1;
$page = $page ?? 1;
$query = array_filter($_GET, static fn($value) => is_scalar($value));
$pageUrl = static function(int $number) use ($query,$pagePath): string {
    return url($pagePath . '?' . http_build_query(array_merge($query,['page'=>$number])));
};
?>
<nav class="d-flex flex-wrap align-items-center justify-content-between gap-2 my-3" aria-label="Phân trang">
  <span class="small text-muted">Trang <?= (int)$page ?> / <?= (int)$pageCount ?> · Mỗi trang tối đa 50 dòng</span>
  <div class="d-flex gap-2">
    <?php if ($page>1): ?><a class="btn btn-sm btn-outline-secondary" href="<?= e($pageUrl($page-1)) ?>">Trang trước</a><?php endif; ?>
    <?php if ($page<$pageCount): ?><a class="btn btn-sm btn-outline-primary" href="<?= e($pageUrl($page+1)) ?>">Trang sau</a><?php endif; ?>
  </div>
</nav>
