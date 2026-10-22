ALTER TABLE enrollment_confirmations
 ADD COLUMN admission_period_id INT UNSIGNED NULL,
 ADD COLUMN actor_user_id BIGINT UNSIGNED NULL,
 ADD COLUMN receipt_code CHAR(32) NULL,
 DROP INDEX uq_enrollment_candidate,
 ADD KEY idx_enrollment_candidate(candidate_id),
 ADD UNIQUE KEY uq_enrollment_period_candidate(admission_period_id,candidate_id),
 ADD UNIQUE KEY uq_enrollment_receipt(receipt_code),
 ADD CONSTRAINT fk_enrollment_period FOREIGN KEY(admission_period_id) REFERENCES admission_periods(id) ON DELETE RESTRICT,
 ADD CONSTRAINT fk_enrollment_actor FOREIGN KEY(actor_user_id) REFERENCES users(id) ON DELETE RESTRICT;
UPDATE enrollment_confirmations ec JOIN admission_results ar ON ar.id=ec.admission_result_id
 SET ec.admission_period_id=ar.admission_period_id
 WHERE ec.admission_period_id IS NULL;
