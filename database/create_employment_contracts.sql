CREATE TABLE employment_contracts (
  contract_id INT(11) NOT NULL AUTO_INCREMENT,
  app_id INT(11) NOT NULL,
  contract_text TEXT NOT NULL,
  status ENUM('pending','agreed','disagreed') NOT NULL DEFAULT
    'pending',
  generated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  responded_at TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (contract_id),
  UNIQUE KEY unique_app_contract (app_id),
  CONSTRAINT fk_contract_app FOREIGN KEY (app_id) REFERENCES
    applications(app_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
