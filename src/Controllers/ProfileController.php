<?php

require_once __DIR__ . '/../Config/Database.php';
require_once __DIR__ . '/../Models/Profile.php';
require_once __DIR__ . '/../Models/Education.php';
require_once __DIR__ . '/../Models/Experience.php';
require_once __DIR__ . '/../Models/Skill.php';
require_once __DIR__ . '/../Models/Company.php';

class ProfileController
{
    private PDO $db;
    private Profile $profiles;
    private Education $education;
    private Experience $experience;
    private Skill $skills;
    private Company $companies;

    public function __construct()
    {
        $this->db = Database::getConnection();
        $this->profiles = new Profile($this->db);
        $this->education = new Education($this->db);
        $this->experience = new Experience($this->db);
        $this->skills = new Skill($this->db);
        $this->companies = new Company($this->db);
    }

    public function saveJobSeekerProfile(int $userId, array $data): array
    {
        try {
            $this->db->beginTransaction();

            $profileData = [
                'user_id' => $userId,
                'date_of_birth' => trim((string) ($data['date_of_birth'] ?? '')),
                'gender' => trim((string) ($data['gender'] ?? '')),
                'address' => trim((string) ($data['address'] ?? '')),
                'profile_photo' => $data['profile_photo'] ?? null,
            ];

            if ($this->profiles->findByUserId($userId)) {
                $this->profiles->update($userId, $profileData);
            } else {
                $this->profiles->create($profileData);
            }

            foreach ($data['education'] ?? [] as $entry) {
                if ($this->isBlankEntry($entry, ['education_level', 'field_of_study', 'institution', 'year_completed'])) {
                    continue;
                }

                $this->education->create([
                    'user_id' => $userId,
                    'education_level' => trim((string) ($entry['education_level'] ?? '')),
                    'field_of_study' => trim((string) ($entry['field_of_study'] ?? '')),
                    'institution' => trim((string) ($entry['institution'] ?? '')),
                    'year_completed' => (int) ($entry['year_completed'] ?? 0),
                    'proof_file' => $entry['proof_file'] ?? null,
                ]);
            }

            foreach ($data['experience'] ?? [] as $entry) {
                if ($this->isBlankEntry($entry, ['company_name', 'job_title', 'start_date'])) {
                    continue;
                }

                $this->experience->create([
                    'user_id' => $userId,
                    'company_name' => trim((string) ($entry['company_name'] ?? '')),
                    'job_title' => trim((string) ($entry['job_title'] ?? '')),
                    'start_date' => trim((string) ($entry['start_date'] ?? '')),
                    'end_date' => trim((string) ($entry['end_date'] ?? '')),
                    'is_current' => !empty($entry['is_current']),
                    'description' => trim((string) ($entry['description'] ?? '')),
                    'proof_file' => $entry['proof_file'] ?? null,
                ]);
            }

            foreach ($data['skills'] ?? [] as $entry) {
                if ($this->isBlankEntry($entry, ['skill_name', 'skill_level'])) {
                    continue;
                }

                $this->skills->create([
                    'user_id' => $userId,
                    'skill_name' => trim((string) ($entry['skill_name'] ?? '')),
                    'skill_level' => trim((string) ($entry['skill_level'] ?? '')),
                    'is_custom' => 1,
                ]);
            }

            $this->db->commit();

            return ['success' => true, 'message' => 'Profile saved.'];
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            return ['success' => false, 'message' => 'Profile could not be saved.'];
        }
    }

    public function saveEmployerCompanyDetails(int $userId, array $data): array
    {
        $updated = $this->companies->updateByUserId($userId, [
            'description' => trim((string) ($data['description'] ?? '')),
            'website' => trim((string) ($data['website'] ?? '')),
            'address' => trim((string) ($data['address'] ?? '')),
        ]);

        if (!$updated) {
            return ['success' => false, 'message' => 'Company details could not be saved.'];
        }

        return ['success' => true, 'message' => 'Company details saved.'];
    }

    private function isBlankEntry(array $entry, array $keys): bool
    {
        foreach ($keys as $key) {
            if (isset($entry[$key]) && trim((string) $entry[$key]) !== '') {
                return false;
            }
        }

        return true;
    }
}
