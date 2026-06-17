ALTER TABLE vacancy_requirements
ADD COLUMN field_of_study ENUM(
    'Computer Science',
    'Business',
    'Engineering',
    'Health Sciences',
    'Education',
    'Agriculture',
    'Law',
    'Arts & Social Sciences',
    'Other'
) DEFAULT NULL AFTER education_level;
