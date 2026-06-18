CREATE TABLE exam_scores (
  score_id INT(11) NOT NULL AUTO_INCREMENT,
  app_id INT(11) NOT NULL,
  score DECIMAL(5,2) NOT NULL,
  recorded_by INT(11) NOT NULL,
  recorded_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (score_id),
  UNIQUE KEY unique_app_score (app_id),
  KEY fk_exam_recorded_by (recorded_by),
  CONSTRAINT fk_exam_app FOREIGN KEY (app_id) REFERENCES
    applications(app_id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_exam_recorded_by FOREIGN KEY (recorded_by) REFERENCES
    users(user_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
