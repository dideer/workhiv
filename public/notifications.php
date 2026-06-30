<?php
require_once __DIR__ . '/../src/Helpers/Session.php';
require_once __DIR__ . '/../src/Models/Notification.php';

Session::start();

if (empty($_SESSION['user_id']) || empty($_SESSION['role'])) {
    header('Location: login.php');
    exit;
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function notificationTimeAgo(?string $value): string
{
    if (!$value) {
        return 'Just now';
    }

    $diff = max(0, time() - strtotime($value));
    if ($diff < 60) {
        return 'Just now';
    }
    if ($diff < 3600) {
        $minutes = (int) floor($diff / 60);
        return $minutes . ' minute' . ($minutes === 1 ? '' : 's') . ' ago';
    }
    if ($diff < 86400) {
        $hours = (int) floor($diff / 3600);
        return $hours . ' hour' . ($hours === 1 ? '' : 's') . ' ago';
    }

    $days = (int) floor($diff / 86400);
    return $days . ' day' . ($days === 1 ? '' : 's') . ' ago';
}

$userId = (int) $_SESSION['user_id'];
$role = (string) $_SESSION['role'];
$notificationModel = new Notification();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'mark_all_read') {
    $notificationModel->markAllAsRead($userId);
    header('Location: notifications.php');
    exit;
}

if (isset($_GET['read'])) {
    $notificationId = (int) $_GET['read'];
    $notification = $notificationModel->getByIdForUser($notificationId, $userId);
    if ($notification) {
        $notificationModel->markAsRead($notificationId, $userId);
        $target = trim((string) ($notification['link_url'] ?? ''));
        header('Location: ' . ($target !== '' ? $target : 'notifications.php'));
        exit;
    }
}

$notifications = $notificationModel->getAllByUser($userId);
$pageClass = in_array($role, ['admin', 'employer'], true) ? 'admin-body' : '';
$activePage = 'notifications';
$adminName = (string) ($_SESSION['full_name'] ?? 'Admin User');
$employerName = (string) ($_SESSION['full_name'] ?? 'Employer');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Notifications | WorkHive</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <?php if (in_array($role, ['admin', 'employer'], true)): ?>
        <link rel="stylesheet" href="<?php echo $role === 'admin' ? 'admin/assets/admin.css' : 'admin/assets/admin.css'; ?>">
    <?php endif; ?>
</head>
<body class="<?php echo e($pageClass); ?>">
    <?php if ($role === 'admin'): ?>
        <aside class="admin-sidebar" aria-label="Admin navigation">
            <div class="admin-brand">
                <a href="admin/dashboard.php">WorkHive</a>
                <span>Admin</span>
            </div>
            <nav class="admin-nav">
                <a class="admin-nav-link" href="admin/dashboard.php"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 13h7V4H4v9Zm0 7h7v-5H4v5Zm9 0h7v-9h-7v9Zm0-16v5h7V4h-7Z"></path></svg><span>Dashboard</span></a>
                <a class="admin-nav-link" href="admin/approvals.php"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9.6 16.2 5.8 12.4l1.4-1.4 2.4 2.4 7.2-7.2 1.4 1.4-8.6 8.6ZM4 4h10v2H6v12h12v-8h2v10H4V4Z"></path></svg><span>Approvals</span></a>
                <a class="admin-nav-link" href="admin/reports.php"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 19h14v2H3V3h2v16Zm3-2V9h3v8H8Zm5 0V5h3v12h-3Zm5 0v-6h3v6h-3Z"></path></svg><span>Reports</span></a>
            </nav>
            <div class="admin-profile">
                <?php require __DIR__ . '/partials/notification-bell.php'; ?>
                <div class="admin-avatar" aria-hidden="true">AU</div>
                <div><p><?php echo e($adminName); ?></p><a href="logout.php">Log out</a></div>
            </div>
        </aside>
    <?php elseif ($role === 'employer'): ?>
        <?php require __DIR__ . '/employer/partials/sidebar.php'; ?>
    <?php elseif ($role === 'job_seeker'): ?>
        <?php require __DIR__ . '/partials/seeker-nav.php'; ?>
    <?php else: ?>
        <header class="site-header">
            <nav class="site-nav" aria-label="Employee navigation">
                <a class="site-logo" href="index.php" aria-label="WorkHive home">WorkHive</a>
                <div class="nav-actions">
                    <span>Hi, <?php echo e((string) ($_SESSION['full_name'] ?? 'there')); ?></span>
                    <?php require __DIR__ . '/partials/notification-bell.php'; ?>
                    <a class="nav-button nav-button-secondary" href="logout.php">Log out</a>
                </div>
            </nav>
        </header>
    <?php endif; ?>

    <main class="<?php echo in_array($role, ['admin', 'employer'], true) ? 'admin-main' : 'profile-main'; ?>">
        <section class="<?php echo in_array($role, ['admin', 'employer'], true) ? 'admin-panel' : 'profile-card'; ?>" aria-labelledby="notifications-title">
            <header class="notifications-page-header">
                <div>
                    <h1 id="notifications-title">Notifications</h1>
                    <p>Recent updates for your WorkHive account.</p>
                </div>
                <form method="post">
                    <input type="hidden" name="action" value="mark_all_read">
                    <button class="button-primary" type="submit">Mark all as read</button>
                </form>
            </header>

            <div class="notification-list-full">
                <?php if ($notifications === []): ?>
                    <div class="empty-state">
                        <h3>No notifications yet</h3>
                        <p>Updates about applications, approvals, contracts, and exchanges will appear here.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($notifications as $notification): ?>
                        <a class="notification-card <?php echo (int) $notification['is_read'] === 0 ? 'is-unread' : ''; ?>" href="notifications.php?read=<?php echo e((string) $notification['notif_id']); ?>">
                            <span class="notification-type notification-type-<?php echo e(statusClassSafe((string) $notification['type'])); ?>"><?php echo e(ucfirst((string) $notification['type'])); ?></span>
                            <strong><?php echo e((string) $notification['message']); ?></strong>
                            <time><?php echo e(notificationTimeAgo($notification['created_at'] ?? null)); ?></time>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
    </main>
</body>
</html>
<?php
function statusClassSafe(string $value): string
{
    return preg_replace('/[^a-z0-9-]+/', '-', strtolower($value)) ?: 'general';
}
?>
