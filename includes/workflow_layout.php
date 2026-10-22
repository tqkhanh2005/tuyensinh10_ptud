<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/portal_helper.php';

/** Shared presentation for workflow pages. Each route retains its own permission checks. */
function workflow_layout_start(string $title, string $activePage = ''): void
{
    if (auth_is_admin()) {
        require_once __DIR__ . '/admin_layout.php';
        admin_layout_start($title, $activePage);
    } elseif (auth_is_teacher()) {
        require_once __DIR__ . '/teacher_layout.php';
        teacher_layout_start($title, $activePage);
    } else {
        $pageTitle = $title . ' - ' . SITE_NAME;
        require __DIR__ . '/header.php';
        require __DIR__ . '/navbar.php';
        echo '<main class="container py-4 py-md-5" style="margin-top:5rem">';
    }
    echo '<div class="workflow-content">';
    require_once __DIR__.'/admission_period_configuration.php';
    $contextPeriod=(int)($_GET['period_id'] ?? $_POST['period_id'] ?? 0);
    $contextConfig=$contextPeriod>0 ? period_configuration(db(),$contextPeriod) : null;
    if($contextConfig && $contextConfig['settings']['mode']==='simulation') echo '<div class="alert alert-warning"><strong>MÔ PHỎNG</strong> — Dữ liệu trong kỳ này không phải kết quả hoặc thông báo tuyển sinh chính thức.</div>';
}

function workflow_layout_end(): void
{
    echo '</div>';
    if (auth_is_admin()) {
        admin_layout_end();
    } elseif (auth_is_teacher()) {
        teacher_layout_end();
    } else {
        echo '</main>';
        require __DIR__ . '/footer.php';
        require __DIR__ . '/scripts.php';
        echo '</body></html>';
    }
}
