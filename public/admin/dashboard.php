<?php
require_once __DIR__ . '/../../src/Helpers/Session.php';
require_once __DIR__ . '/../../src/Models/Admin.php';

Session::start();

if (($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../login.php');
    exit;
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

$adminName = (string) ($_SESSION['full_name'] ?? 'Admin User');
$adminModel = new Admin();
$pendingEmployers = count($adminModel->pendingEmployers());
$pendingExchanges = $adminModel->countExchangeRequestsByStatus('pending');
$stats = [
    'pendingApprovals' => $pendingEmployers + $pendingExchanges,
    'activeJobSeekers' => $adminModel->countUsersByRoleAndStatus('job_seeker', 'active'),
    'activeEmployers' => $adminModel->countUsersByRoleAndStatus('employer', 'active'),
    'openExchangeRequests' => $pendingExchanges,
];
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Dashboard | WorkHive</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="assets/admin.css">
</head>
<body class="admin-body" data-admin-page="dashboard">
    <aside class="admin-sidebar" aria-label="Admin navigation">
        <div class="admin-brand">
            <a href="dashboard.php">WorkHive</a>
            <span>Admin</span>
        </div>

        <nav class="admin-nav">
            <a class="admin-nav-link is-active" href="dashboard.php" aria-current="page">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 13h7V4H4v9Zm0 7h7v-5H4v5Zm9 0h7v-9h-7v9Zm0-16v5h7V4h-7Z"></path></svg>
                <span>Dashboard</span>
            </a>
            <a class="admin-nav-link" href="approvals.php">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9.6 16.2 5.8 12.4l1.4-1.4 2.4 2.4 7.2-7.2 1.4 1.4-8.6 8.6ZM4 4h10v2H6v12h12v-8h2v10H4V4Z"></path></svg>
                <span>Approvals</span>
            </a>
            <a class="admin-nav-link" href="reports.php">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 19h14v2H3V3h2v16Zm3-2V9h3v8H8Zm5 0V5h3v12h-3Zm5 0v-6h3v6h-3Z"></path></svg>
                <span>Reports</span>
            </a>
        </nav>

        <div class="admin-profile">
            <?php require __DIR__ . '/../partials/notification-bell.php'; ?>
            <div class="admin-avatar" aria-hidden="true">AU</div>
            <div>
                <p><?php echo e($adminName); ?></p>
                <a href="../logout.php">Log out</a>
            </div>
        </div>
    </aside>

    <main class="admin-main">
        <header class="admin-page-header panel-header">
            <div>
                <h1>Dashboard</h1>
                <p>An overview of activity across WorkHive.</p>
            </div>
            <a class="button-primary" href="approvals.php">Review company approvals</a>
        </header>

        <section class="admin-stats" aria-label="Platform statistics">
            <article class="admin-stat-card">
                <strong><?php echo e((string) $stats['pendingApprovals']); ?></strong>
                <span>Pending approvals</span>
            </article>
            <article class="admin-stat-card">
                <strong><?php echo e((string) $stats['activeJobSeekers']); ?></strong>
                <span>Active job seekers</span>
            </article>
            <article class="admin-stat-card">
                <strong><?php echo e((string) $stats['activeEmployers']); ?></strong>
                <span>Active employers</span>
            </article>
            <article class="admin-stat-card">
                <strong><?php echo e((string) $stats['openExchangeRequests']); ?></strong>
                <span>Open exchange requests</span>
            </article>
        </section>

        <section class="admin-panel" aria-labelledby="activity-title">
            <div class="admin-section-heading">
                <h2 id="activity-title">Recent activity</h2>
                <p>Latest platform events requiring visibility or follow-up.</p>
            </div>
            <div class="activity-list">
                <div class="empty-state">
                    <h3>No activity yet</h3>
                    <p>Recent platform activity will appear here once activity logging is connected.</p>
                </div>
            </div>
        </section>
    </main>

    <script src="assets/admin.js"></script>
</body>
</html>
