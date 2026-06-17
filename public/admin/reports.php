<?php
require_once __DIR__ . '/../../src/Helpers/Session.php';

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
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reports | WorkHive Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="assets/admin.css">
</head>
<body class="admin-body" data-admin-page="reports">
    <aside class="admin-sidebar" aria-label="Admin navigation">
        <div class="admin-brand">
            <a href="dashboard.php">WorkHive</a>
            <span>Admin</span>
        </div>

        <nav class="admin-nav">
            <a class="admin-nav-link" href="dashboard.php">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 13h7V4H4v9Zm0 7h7v-5H4v5Zm9 0h7v-9h-7v9Zm0-16v5h7V4h-7Z"></path></svg>
                <span>Dashboard</span>
            </a>
            <a class="admin-nav-link" href="approvals.php">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9.6 16.2 5.8 12.4l1.4-1.4 2.4 2.4 7.2-7.2 1.4 1.4-8.6 8.6ZM4 4h10v2H6v12h12v-8h2v10H4V4Z"></path></svg>
                <span>Approvals</span>
            </a>
            <a class="admin-nav-link is-active" href="reports.php" aria-current="page">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 19h14v2H3V3h2v16Zm3-2V9h3v8H8Zm5 0V5h3v12h-3Zm5 0v-6h3v6h-3Z"></path></svg>
                <span>Reports</span>
            </a>
        </nav>

        <div class="admin-profile">
            <div class="admin-avatar" aria-hidden="true">AU</div>
            <div>
                <p><?php echo e($adminName); ?></p>
                <a href="../logout.php">Log out</a>
            </div>
        </div>
    </aside>

    <main class="admin-main">
        <header class="admin-page-header">
            <h1>Reports</h1>
            <p>Generate reports on employment trends and exchange activity.</p>
        </header>

        <section class="admin-panel" aria-labelledby="report-filter-title">
            <div class="admin-section-heading">
                <h2 id="report-filter-title">Report filters</h2>
                <p>Select a report type and period to preview results once report queries are connected.</p>
            </div>

            <form class="report-filter-form">
                <div class="form-field">
                    <label for="report-type">Report type</label>
                    <select id="report-type" name="reportType">
                        <option>Employment trends</option>
                        <option>Vacancy demand by sector</option>
                        <option>Exchange activity</option>
                    </select>
                </div>
                <div class="form-field">
                    <label for="date-from">Date from</label>
                    <input type="date" id="date-from" name="dateFrom">
                </div>
                <div class="form-field">
                    <label for="date-to">Date to</label>
                    <input type="date" id="date-to" name="dateTo">
                </div>
                <button class="button-primary" type="button" id="generate-report">Generate report</button>
            </form>
        </section>

        <section class="admin-panel report-preview" aria-labelledby="report-preview-title">
            <div class="admin-section-heading">
                <h2 id="report-preview-title">Report preview</h2>
            </div>

            <div class="empty-state">
                <h3>No report generated yet</h3>
                <p>Choose a type and date range above.</p>
            </div>

            <div class="report-actions">
                <button class="button-primary" type="button" data-download="PDF">Download as PDF</button>
                <button class="button-outline" type="button" data-download="Excel">Download as Excel</button>
            </div>
        </section>
    </main>

    <script src="assets/admin.js"></script>
</body>
</html>
