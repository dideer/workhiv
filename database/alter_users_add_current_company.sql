ALTER TABLE users ADD COLUMN current_company_id INT(11) DEFAULT NULL AFTER role,
ADD CONSTRAINT fk_users_current_company FOREIGN KEY (current_company_id) REFERENCES companies(company_id) ON DELETE SET NULL ON UPDATE CASCADE;

UPDATE users u
INNER JOIN (
    SELECT a.user_id, v.company_id
    FROM applications a
    INNER JOIN vacancies v ON v.vacancy_id = a.vacancy_id
    INNER JOIN (
        SELECT user_id, MAX(app_id) AS latest_app_id
        FROM applications
        WHERE status = 'hired'
        GROUP BY user_id
    ) latest ON latest.latest_app_id = a.app_id
    WHERE a.status = 'hired'
) current_jobs ON current_jobs.user_id = u.user_id
SET u.current_company_id = current_jobs.company_id
WHERE u.role = 'employee';
