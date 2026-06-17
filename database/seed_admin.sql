-- Seed admin account for WorkHive.
-- Login checks the `email` field against the literal string "admin" for this seeded account.
-- Password "admin" was hashed with PHP password_hash('admin', PASSWORD_DEFAULT):
-- $2y$10$ha5qfsbny6trlbfrQKs4feeF5haAZEpAvPsyGnr2gPIY2US4EUsty

INSERT INTO users (full_name, email, password, role, status)
VALUES (
    'System Administrator',
    'admin',
    '$2y$10$ha5qfsbny6trlbfrQKs4feeF5haAZEpAvPsyGnr2gPIY2US4EUsty',
    'admin',
    'active'
);
