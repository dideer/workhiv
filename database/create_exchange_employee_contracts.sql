CREATE TABLE exchange_employee_contracts (
  contract_id INT(11) NOT NULL AUTO_INCREMENT,
  request_id INT(11) NOT NULL,
  employee_id INT(11) NOT NULL,
  new_company_id INT(11) NOT NULL,
  contract_text TEXT NOT NULL,
  status ENUM('pending','agreed','disagreed') NOT NULL DEFAULT 'pending',
  generated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  responded_at TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (contract_id),
  UNIQUE KEY unique_request_employee (request_id, employee_id),
  CONSTRAINT fk_exc_request FOREIGN KEY (request_id) REFERENCES exchange_requests(request_id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_exc_employee FOREIGN KEY (employee_id) REFERENCES users(user_id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_exc_company FOREIGN KEY (new_company_id) REFERENCES companies(company_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
