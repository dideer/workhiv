<?php
$employerNavBase = str_contains(str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '')), '/employer/') ? '' : 'employer/';
$employerLogoutHref = $employerNavBase === '' ? '../logout.php' : 'logout.php';
?>
<aside class="admin-sidebar" aria-label="Employer navigation">
    <div class="admin-brand">
        <a href="<?php echo e($employerNavBase); ?>dashboard.php">WorkHive</a>
        <span>Employer</span>
    </div>

    <nav class="admin-nav">
        <a class="admin-nav-link <?php echo $activePage === 'dashboard' ? 'is-active' : ''; ?>" href="<?php echo e($employerNavBase); ?>dashboard.php" <?php echo $activePage === 'dashboard' ? 'aria-current="page"' : ''; ?>>
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 13h7V4H4v9Zm0 7h7v-5H4v5Zm9 0h7v-9h-7v9Zm0-16v5h7V4h-7Z"></path></svg>
            <span>Dashboard</span>
        </a>
        <a class="admin-nav-link <?php echo $activePage === 'vacancies' ? 'is-active' : ''; ?>" href="<?php echo e($employerNavBase); ?>vacancies.php" <?php echo $activePage === 'vacancies' ? 'aria-current="page"' : ''; ?>>
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 5h14v4H5V5Zm0 6h14v8H5v-8Zm2 2v4h10v-4H7ZM7 3h10v2H7V3Z"></path></svg>
            <span>My Vacancies</span>
        </a>
        <a class="admin-nav-link <?php echo $activePage === 'applications' ? 'is-active' : ''; ?>" href="<?php echo e($employerNavBase); ?>applications.php" <?php echo $activePage === 'applications' ? 'aria-current="page"' : ''; ?>>
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 4h16v16H4V4Zm2 2v12h12V6H6Zm2 2h8v2H8V8Zm0 4h8v2H8v-2Z"></path></svg>
            <span>Applications</span>
        </a>
        <a class="admin-nav-link <?php echo $activePage === 'our-employees' ? 'is-active' : ''; ?>" href="<?php echo e($employerNavBase); ?>our-employees.php" <?php echo $activePage === 'our-employees' ? 'aria-current="page"' : ''; ?>>
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm8.5 0a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7ZM8 13c-3.3 0-6 1.8-6 4v3h12v-3c0-2.2-2.7-4-6-4Zm8.5 0c-.8 0-1.5.1-2.2.4 1.1.9 1.7 2.1 1.7 3.6v3h6v-2.5c0-2.5-2.5-4.5-5.5-4.5Z"></path></svg>
            <span>Our employees</span>
        </a>
        <a class="admin-nav-link <?php echo $activePage === 'find-employees' ? 'is-active' : ''; ?>" href="<?php echo e($employerNavBase); ?>find-employees.php" <?php echo $activePage === 'find-employees' ? 'aria-current="page"' : ''; ?>>
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm0 2c-3.3 0-6 1.7-6 3.8V20h9.1a6.9 6.9 0 0 1-.1-1 6 6 0 0 1 6-6c.3 0 .7 0 1 .1V13h-2v1.1A7.8 7.8 0 0 0 10 13Zm9 2v3h3v2h-3v3h-2v-3h-3v-2h3v-3h2Z"></path></svg>
            <span>Find employees</span>
        </a>
        <a class="admin-nav-link <?php echo $activePage === 'exchange-requests' ? 'is-active' : ''; ?>" href="<?php echo e($employerNavBase); ?>exchange-requests.php" <?php echo $activePage === 'exchange-requests' ? 'aria-current="page"' : ''; ?>>
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 7h10l-2.5-2.5L16 3l5 5-5 5-1.5-1.5L17 9H7V7Zm10 10H7l2.5 2.5L8 21l-5-5 5-5 1.5 1.5L7 15h10v2Z"></path></svg>
            <span>Exchange requests</span>
        </a>
    </nav>

    <div class="admin-profile">
        <?php require __DIR__ . '/../../partials/notification-bell.php'; ?>
        <div class="admin-avatar" aria-hidden="true">EM</div>
        <div>
            <p><?php echo e($employerName); ?></p>
            <a href="<?php echo e($employerLogoutHref); ?>">Log out</a>
        </div>
    </div>
</aside>
