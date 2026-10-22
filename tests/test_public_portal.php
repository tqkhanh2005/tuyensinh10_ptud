<?php
/**
 * Test Suite: Public Portal Inspection & Security Verification
 */
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/portal_helper.php';

echo "===============================================\n";
echo "TEST SUITE: PUBLIC PORTAL INSPECTION\n";
echo "===============================================\n";

$passCount = 0;
$failCount = 0;

function assertCondition(bool $cond, string $msg, ?string $details = null): void {
    global $passCount, $failCount;
    if ($cond) {
        $passCount++;
        echo " [PASS] " . $msg . "\n";
    } else {
        $failCount++;
        echo " [FAIL] " . $msg . "\n";
        if ($details) {
            echo "        -> Details: " . $details . "\n";
        }
    }
}

// 1. Guest render test
$_SESSION = []; // Clear session as guest
ob_start();
include __DIR__ . '/../index.php';
$html = ob_get_clean();

assertCondition(!empty($html), "Public Portal renders HTML output without fatal error");
assertCondition(str_contains($html, '<!DOCTYPE html>'), "Public Portal returns valid HTML document");

// 2. Zone isolation test: No Admin / Teacher sidebars or admin controls
assertCondition(!str_contains($html, 'id="adminSidebar"') && !str_contains($html, 'admin_sidebar') && !str_contains($html, 'admin-sidebar'), "No Admin sidebar rendered on Public Portal");
assertCondition(!str_contains($html, 'id="teacherSidebar"') && !str_contains($html, 'teacher_sidebar') && !str_contains($html, 'teacher-sidebar'), "No Teacher sidebar rendered on Public Portal");
assertCondition(!str_contains($html, 'href="/schools/"') && !str_contains($html, 'href="/staff/"') && !str_contains($html, 'href="/councils/"'), "No administrative module menus rendered for Guest");

// 3. Navbar public elements test
assertCondition(str_contains($html, '#calendar'), "Public Navbar contains anchor to Admissions Timeline (#calendar)");
assertCondition(str_contains($html, '#guides'), "Public Navbar contains anchor to Guides (#guides)");
assertCondition(str_contains($html, '#targets'), "Public Navbar contains anchor to Quotas (#targets)");
assertCondition(str_contains($html, '#schools'), "Public Navbar contains anchor to Schools (#schools)");
assertCondition(str_contains($html, '/ket-qua.php'), "Public Navbar contains link to Results Lookup (/ket-qua.php)");
assertCondition(str_contains($html, '/auth/login.php'), "Public Navbar contains Login button for Guest");
assertCondition(str_contains($html, 'id="mobileNavToggle"') && str_contains($html, 'id="mobileNavMenu"'), "Responsive mobile navigation toggle and drawer exist");

// 4. Section structure test
assertCondition(str_contains($html, 'id="hero"'), "Hero section exists");
assertCondition(str_contains($html, 'id="calendar"'), "Admissions timeline section exists");
assertCondition(str_contains($html, 'id="guides"'), "Guides section exists");
assertCondition(str_contains($html, 'id="targets"'), "Quotas section exists");
assertCondition(str_contains($html, 'id="schools"'), "Schools network section exists");
assertCondition(str_contains($html, 'id="tra-cuu"'), "Admissions lookup CTA section exists");

// 5. Empty states test
assertCondition(str_contains($html, 'id="quotaEmptyState"'), "Quota search empty state exists");
assertCondition(str_contains($html, 'id="thptEmptyState"'), "THPT search empty state exists");
assertCondition(str_contains($html, 'id="thcsEmptyState"'), "THCS search empty state exists");

// 6. Data hygiene: No batch test strings leak into public portal
$testKeywords = ['BATCHI_', 'Batch B Test', 'G1_0920102433', 'BTEST-'];
$leaked = [];
foreach ($testKeywords as $kw) {
    if (str_contains($html, $kw)) {
        $leaked[] = $kw;
    }
}
assertCondition(empty($leaked), "No test batch records leak into Public Portal", empty($leaked) ? null : "Leaked: " . implode(', ', $leaked));

// 7. Navbar behavior when logged in as Admin
$_SESSION = [
    'auth' => [
        'user_id' => 999,
        'username' => 'admin_test',
        'role' => 'admin',
        'roles' => ['admin']
    ]
];
ob_start();
include __DIR__ . '/../includes/navbar.php';
$adminNavbarHtml = ob_get_clean();

assertCondition(str_contains($adminNavbarHtml, '/admin/index.php'), "Navbar provides portal return link for Admin (/admin/index.php)");
assertCondition(str_contains($adminNavbarHtml, 'Quản trị'), "Navbar displays Admin badge/button");

// 8. Navbar behavior when logged in as Teacher
$_SESSION = [
    'auth' => [
        'user_id' => 998,
        'username' => 'teacher_test',
        'role' => 'teacher',
        'roles' => ['teacher']
    ]
];
ob_start();
include __DIR__ . '/../includes/navbar.php';
$teacherNavbarHtml = ob_get_clean();

assertCondition(str_contains($teacherNavbarHtml, '/teacher/index.php'), "Navbar provides portal return link for Teacher (/teacher/index.php)");
assertCondition(str_contains($teacherNavbarHtml, 'Cổng giáo viên'), "Navbar displays Teacher badge/button");

// 9. Navbar behavior when logged in as Candidate
$_SESSION = [
    'auth' => [
        'user_id' => 997,
        'username' => 'candidate_test',
        'role' => 'candidate',
        'roles' => ['candidate']
    ]
];
ob_start();
include __DIR__ . '/../includes/navbar.php';
$candidateNavbarHtml = ob_get_clean();

assertCondition(str_contains($candidateNavbarHtml, '/#candidate-portal-dashboard'), "Navbar returns Candidate to their dashboard instead of the admin-only candidate list");
assertCondition(str_contains($candidateNavbarHtml, 'Cổng thí sinh'), "Navbar displays Candidate badge/button");

echo "===============================================\n";
echo "SUMMARY: Total: " . ($passCount + $failCount) . " | PASS: {$passCount} | FAIL: {$failCount}\n";
echo "===============================================\n";

if ($failCount > 0) {
    exit(1);
}
exit(0);
