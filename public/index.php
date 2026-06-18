<?php
require_once __DIR__ . '/../src/Helpers/Session.php';
require_once __DIR__ . '/../src/Models/Vacancy.php';

Session::start();

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function dateText(?string $value): string
{
    return $value ? date('M j, Y', strtotime($value)) : 'Not set';
}

function excerpt(string $value, int $limit = 140): string
{
    $value = trim($value);
    if (strlen($value) <= $limit) {
        return $value;
    }

    return substr($value, 0, $limit - 3) . '...';
}

function pageUrl(int $page, array $filters): string
{
    return 'index.php?' . http_build_query(array_filter([
        'search' => $filters['search'] ?? '',
        'sector' => $filters['sector'] ?? '',
        'education' => $filters['education'] ?? '',
        'page' => $page,
    ], fn($value) => $value !== '' && $value !== null)) . '#jobs';
}

$vacancyModel = new Vacancy();
$filters = [
    'search' => trim((string) ($_GET['search'] ?? '')),
    'sector' => trim((string) ($_GET['sector'] ?? '')),
    'education' => trim((string) ($_GET['education'] ?? '')),
];
$page = max(1, (int) ($_GET['page'] ?? 1));
$pageSize = 12;
$totalResults = $vacancyModel->countActivePublic($filters);
$totalPages = max(1, (int) ceil($totalResults / $pageSize));
$page = min($page, $totalPages);
$jobs = $vacancyModel->getActivePublic($filters, $pageSize, ($page - 1) * $pageSize);
$filterOptions = $vacancyModel->getPublicFilterOptions();
$role = (string) ($_SESSION['role'] ?? '');
$isLoggedIn = isset($_SESSION['user_id']);
$canApply = in_array($role, ['job_seeker', 'employee'], true);
$dashboardUrl = match ($role) {
    'admin' => 'admin/dashboard.php',
    'employer' => 'employer/dashboard.php',
    'job_seeker' => 'seeker-dashboard.php',
    'employee' => 'employee-dashboard.php',
    default => '',
};
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>WorkHive | Rwanda Employment Exchange</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header class="site-header">
        <nav class="site-nav" aria-label="Primary navigation">
            <a class="site-logo" href="index.php" aria-label="WorkHive home">WorkHive</a>
            <div class="nav-links">
                <a class="nav-link" href="#jobs">Jobs</a>
            </div>
            <div class="nav-actions">
                <?php if ($isLoggedIn): ?>
                    <span>Hi, <?php echo e((string) ($_SESSION['full_name'] ?? 'there')); ?></span>
                    <?php if ($dashboardUrl !== ''): ?>
                        <a class="nav-link" href="<?php echo e($dashboardUrl); ?>">Dashboard</a>
                    <?php endif; ?>
                    <a class="nav-button nav-button-secondary" href="logout.php">Log out</a>
                <?php else: ?>
                    <a class="nav-button nav-button-secondary" href="login.php">Login</a>
                    <a class="nav-button nav-button-primary" href="register.php">Register</a>
                <?php endif; ?>
            </div>
        </nav>
    </header>

    <main>
        <section class="hero-section" aria-labelledby="hero-title">
            <div class="hero-layout">
                <div class="hero-content">
                    <p class="section-kicker">Rwanda Employment Exchange</p>
                    <h1 id="hero-title">Find trusted employment opportunities across Rwanda</h1>
                    <p>Search verified roles from employers and institutions through WorkHive.</p>
                </div>
                <div class="hero-image" role="img" aria-label="Rwandan professionals in a workplace setting"></div>
            </div>
        </section>

        <section class="job-search-section" aria-labelledby="search-title">
            <div class="section-heading">
                <p class="section-kicker">Open opportunities</p>
                <h2 id="search-title">Search jobs</h2>
            </div>
            <form class="job-search-form" method="get" action="index.php#jobs">
                <div class="form-field">
                    <label for="job-search">Search by title or location</label>
                    <input type="search" id="job-search" name="search" value="<?php echo e($filters['search']); ?>" placeholder="Job title or location" autocomplete="off">
                </div>

                <div class="form-field">
                    <label for="sector-filter">Sector</label>
                    <select id="sector-filter" name="sector">
                        <option value="">All sectors</option>
                        <?php foreach ($filterOptions['sectors'] as $sector): ?>
                            <option value="<?php echo e($sector); ?>" <?php echo $filters['sector'] === $sector ? 'selected' : ''; ?>><?php echo e($sector); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-field">
                    <label for="education-filter">Education level</label>
                    <select id="education-filter" name="education">
                        <option value="">All education levels</option>
                        <?php foreach ($filterOptions['education'] as $education): ?>
                            <option value="<?php echo e($education); ?>" <?php echo $filters['education'] === $education ? 'selected' : ''; ?>><?php echo e($education); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button class="button-primary" type="submit">Search</button>
            </form>
        </section>

        <section class="job-listings-section" id="jobs" aria-labelledby="job-listings-title">
            <div class="listings-header">
                <div class="section-heading">
                    <p class="section-kicker">Latest postings</p>
                    <h2 id="job-listings-title">Available jobs</h2>
                </div>
                <p class="result-count" aria-live="polite"><?php echo e((string) $totalResults); ?> result<?php echo $totalResults === 1 ? '' : 's'; ?></p>
            </div>

            <div class="job-listings" aria-live="polite">
                <?php if ($jobs === []): ?>
                    <div class="empty-state">
                        <h3>No active vacancies found</h3>
                        <p>Try a different keyword, sector, or education level.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($jobs as $job): ?>
                        <?php
                        $jobData = [
                            'id' => (int) $job['vacancy_id'],
                            'title' => (string) $job['title'],
                            'companyName' => (string) $job['company_name'],
                            'sector' => (string) $job['sector'],
                            'location' => (string) $job['location'],
                            'salaryRange' => (string) $job['salary_range'],
                            'postedDate' => dateText($job['created_at']),
                            'deadline' => dateText($job['deadline']),
                            'descriptionFull' => (string) $job['description'],
                            'educationLevel' => (string) ($job['education_level'] ?? 'Not specified'),
                            'fieldOfStudy' => (string) ($job['field_of_study'] ?? 'Not specified'),
                            'minExperienceYears' => (int) ($job['min_experience_years'] ?? 0),
                            'skillsRequired' => array_values(array_filter(array_map('trim', explode(',', (string) ($job['skills_required'] ?? ''))))),
                            'otherRequirements' => (string) ($job['other_requirements'] ?? 'Not specified'),
                            'companyAbout' => (string) ($job['company_about'] ?? 'No company description provided.'),
                            'applyUrl' => $canApply ? 'apply.php?vacancy_id=' . (int) $job['vacancy_id'] : '',
                            'canApply' => $canApply,
                        ];
                        ?>
                        <article class="job-card" data-id="<?php echo e((string) $job['vacancy_id']); ?>">
                            <button class="job-card-button" type="button" data-job="<?php echo e(json_encode($jobData, JSON_HEX_APOS | JSON_HEX_QUOT)); ?>" aria-label="View details for <?php echo e((string) $job['title']); ?>">
                                <header class="job-card-header">
                                    <h3><?php echo e((string) $job['title']); ?></h3>
                                    <p class="job-company"><?php echo e((string) $job['company_name']); ?></p>
                                </header>
                                <dl class="job-meta">
                                    <div>
                                        <dt>Location</dt>
                                        <dd><?php echo e((string) $job['location']); ?></dd>
                                    </div>
                                    <div>
                                        <dt>Salary range</dt>
                                        <dd><?php echo e((string) $job['salary_range']); ?></dd>
                                    </div>
                                    <div>
                                        <dt>Posted</dt>
                                        <dd><?php echo e(dateText($job['created_at'])); ?></dd>
                                    </div>
                                    <div>
                                        <dt>Deadline</dt>
                                        <dd><?php echo e(dateText($job['deadline'])); ?></dd>
                                    </div>
                                </dl>
                                <p class="job-excerpt"><?php echo e(excerpt((string) $job['description'])); ?></p>
                            </button>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <?php if ($totalPages > 1): ?>
                <nav class="pagination" aria-label="Job listings pagination">
                    <a class="pagination-button" href="<?php echo e(pageUrl(max(1, $page - 1), $filters)); ?>" <?php echo $page === 1 ? 'aria-disabled="true"' : ''; ?>>Previous</a>
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <a class="pagination-button <?php echo $i === $page ? 'is-current' : ''; ?>" href="<?php echo e(pageUrl($i, $filters)); ?>" <?php echo $i === $page ? 'aria-current="page"' : ''; ?>><?php echo e((string) $i); ?></a>
                    <?php endfor; ?>
                    <a class="pagination-button" href="<?php echo e(pageUrl(min($totalPages, $page + 1), $filters)); ?>" <?php echo $page === $totalPages ? 'aria-disabled="true"' : ''; ?>>Next</a>
                </nav>
            <?php endif; ?>
        </section>
    </main>

    <div class="modal" id="job-modal" hidden>
        <div class="modal-backdrop" data-modal-close></div>
        <section class="modal-card" role="dialog" aria-modal="true" aria-labelledby="modal-title" tabindex="-1">
            <button class="modal-close" type="button" aria-label="Close job details" data-modal-close>
                <svg aria-hidden="true" viewBox="0 0 24 24" focusable="false">
                    <path d="M6.4 5.3 12 10.9l5.6-5.6 1.1 1.1-5.6 5.6 5.6 5.6-1.1 1.1-5.6-5.6-5.6 5.6-1.1-1.1 5.6-5.6-5.6-5.6 1.1-1.1Z"></path>
                </svg>
            </button>

            <div class="modal-content">
                <p class="section-kicker" id="modal-sector"></p>
                <h2 id="modal-title"></h2>
                <p class="modal-company" id="modal-company"></p>
                <dl class="modal-summary">
                    <div><dt>Location</dt><dd id="modal-location"></dd></div>
                    <div><dt>Salary</dt><dd id="modal-salary"></dd></div>
                    <div><dt>Posted</dt><dd id="modal-posted"></dd></div>
                    <div><dt>Deadline</dt><dd id="modal-deadline"></dd></div>
                </dl>
                <div class="modal-section">
                    <h3>Job description</h3>
                    <p id="modal-description"></p>
                </div>
                <div class="modal-section">
                    <h3>Requirements</h3>
                    <dl class="requirements-list">
                        <div><dt>Education level</dt><dd id="modal-education"></dd></div>
                        <div><dt>Field of study</dt><dd id="modal-field-of-study"></dd></div>
                        <div><dt>Minimum experience</dt><dd id="modal-experience"></dd></div>
                        <div><dt>Other requirements</dt><dd id="modal-other-requirements"></dd></div>
                    </dl>
                    <div class="skills-group" id="modal-skills"></div>
                </div>
                <div class="modal-section">
                    <h3>Company information</h3>
                    <p id="modal-company-about"></p>
                </div>
                <div class="modal-actions">
                    <a class="button-primary" id="apply-link" href="login.php">Apply Now</a>
                    <p class="apply-message" id="apply-message" hidden></p>
                </div>
            </div>
        </section>
    </div>

    <footer class="site-footer">
        <p>WorkHive</p>
        <p>In partnership with MIFOTRA</p>
    </footer>

    <script src="assets/js/script.js"></script>
</body>
</html>
