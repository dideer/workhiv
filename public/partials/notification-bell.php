<?php
require_once __DIR__ . '/../../src/Models/Notification.php';

if (!function_exists('notificationBellEscape')) {
    function notificationBellEscape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('notificationBellTimeAgo')) {
    function notificationBellTimeAgo(?string $value): string
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
}

$notificationUserId = (int) ($_SESSION['user_id'] ?? 0);
$notificationModel = new Notification();
$notificationRecent = $notificationUserId > 0 ? $notificationModel->getRecentByUser($notificationUserId, 5) : [];
$notificationUnreadCount = $notificationUserId > 0 ? $notificationModel->getUnreadCount($notificationUserId) : 0;
$notificationScript = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
$notificationBase = str_contains($notificationScript, '/admin/') || str_contains($notificationScript, '/employer/') || str_contains($notificationScript, '/employee/')
    ? '../notifications.php'
    : 'notifications.php';
?>
<div class="notification-bell" data-notification-bell>
    <button class="notification-bell-button" type="button" aria-label="Notifications" aria-expanded="false" data-notification-toggle>
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 22a2.5 2.5 0 0 0 2.4-2h-4.8A2.5 2.5 0 0 0 12 22Zm7-6V10a7 7 0 0 0-5-6.7V2h-4v1.3A7 7 0 0 0 5 10v6l-2 2v1h18v-1l-2-2Z"></path></svg>
        <?php if ($notificationUnreadCount > 0): ?>
            <span class="notification-badge"><?php echo notificationBellEscape((string) $notificationUnreadCount); ?></span>
        <?php endif; ?>
    </button>

    <div class="notification-dropdown" hidden data-notification-dropdown>
        <div class="notification-dropdown-header">
            <strong>Notifications</strong>
        </div>
        <div class="notification-dropdown-list">
            <?php if ($notificationRecent === []): ?>
                <div class="notification-empty">No notifications yet.</div>
            <?php else: ?>
                <?php foreach ($notificationRecent as $notification): ?>
                    <?php $messagePreview = strlen((string) $notification['message']) > 92 ? substr((string) $notification['message'], 0, 89) . '...' : (string) $notification['message']; ?>
                    <a class="notification-row <?php echo (int) $notification['is_read'] === 0 ? 'is-unread' : ''; ?>" href="<?php echo notificationBellEscape($notificationBase . '?read=' . (int) $notification['notif_id']); ?>">
                        <span><?php echo notificationBellEscape($messagePreview); ?></span>
                        <time><?php echo notificationBellEscape(notificationBellTimeAgo($notification['created_at'] ?? null)); ?></time>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <a class="notification-view-all" href="<?php echo notificationBellEscape($notificationBase); ?>">View all notifications</a>
    </div>
</div>
<script>
(() => {
    document.querySelectorAll('[data-notification-bell]').forEach((root) => {
        if (root.dataset.notificationReady === '1') {
            return;
        }
        root.dataset.notificationReady = '1';
        const toggle = root.querySelector('[data-notification-toggle]');
        const dropdown = root.querySelector('[data-notification-dropdown]');

        function close() {
            dropdown.hidden = true;
            toggle.setAttribute('aria-expanded', 'false');
        }

        function open() {
            dropdown.hidden = false;
            toggle.setAttribute('aria-expanded', 'true');
            dropdown.classList.remove('align-right');
            const rect = dropdown.getBoundingClientRect();
            if (rect.right > window.innerWidth - 12) {
                dropdown.classList.add('align-right');
            }
        }

        toggle.addEventListener('click', (event) => {
            event.stopPropagation();
            dropdown.hidden ? open() : close();
        });

        document.addEventListener('click', (event) => {
            if (!root.contains(event.target)) {
                close();
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                close();
            }
        });
    });
})();
</script>
