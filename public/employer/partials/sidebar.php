<aside class="admin-sidebar" aria-label="Employer navigation">
    <div class="admin-brand">
        <a href="dashboard.php">WorkHive</a>
        <span>Employer</span>
    </div>

    <nav class="admin-nav">
        <a class="admin-nav-link <?php echo $activePage === 'dashboard' ? 'is-active' : ''; ?>" href="dashboard.php" <?php echo $activePage === 'dashboard' ? 'aria-current="page"' : ''; ?>>
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 13h7V4H4v9Zm0 7h7v-5H4v5Zm9 0h7v-9h-7v9Zm0-16v5h7V4h-7Z"></path></svg>
            <span>Dashboard</span>
        </a>
        <a class="admin-nav-link <?php echo $activePage === 'vacancies' ? 'is-active' : ''; ?>" href="vacancies.php" <?php echo $activePage === 'vacancies' ? 'aria-current="page"' : ''; ?>>
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 5h14v4H5V5Zm0 6h14v8H5v-8Zm2 2v4h10v-4H7ZM7 3h10v2H7V3Z"></path></svg>
            <span>My Vacancies</span>
        </a>
        <a class="admin-nav-link <?php echo $activePage === 'applications' ? 'is-active' : ''; ?>" href="applications.php" <?php echo $activePage === 'applications' ? 'aria-current="page"' : ''; ?>>
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 4h16v16H4V4Zm2 2v12h12V6H6Zm2 2h8v2H8V8Zm0 4h8v2H8v-2Z"></path></svg>
            <span>Applications</span>
        </a>
    </nav>

    <div class="admin-profile">
        <div class="admin-avatar" aria-hidden="true">EM</div>
        <div>
            <p><?php echo e($employerName); ?></p>
            <a href="../logout.php">Log out</a>
        </div>
    </div>
</aside>
