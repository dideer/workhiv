<header class="site-header">
    <nav class="site-nav" aria-label="Job seeker navigation">
        <a class="site-logo" href="index.php" aria-label="WorkHive home">WorkHive</a>
        <div class="nav-actions seeker-nav-actions">
            <span>Hi, <?php echo e((string) ($_SESSION['full_name'] ?? 'there')); ?></span>
            <a class="nav-link" href="seeker-dashboard.php">Dashboard</a>
            <a class="nav-link" href="my-profile.php">My Profile</a>
            <a class="nav-link" href="my-applications.php">My Applications</a>
            <a class="nav-button nav-button-secondary" href="logout.php">Log out</a>
        </div>
    </nav>
</header>
