ALTER TABLE education
MODIFY COLUMN field_of_study ENUM(
    'Computer Science',
    'Business',
    'Engineering',
    'Health Sciences',
    'Education',
    'Agriculture',
    'Law',
    'Arts & Social Sciences',
    'Other'
) DEFAULT NULL;
