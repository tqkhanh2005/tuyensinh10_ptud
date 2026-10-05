-- Batch B: Exam Foundation invariants for UC16 / UC37 / UC38
-- Dependency: Batch A + existing exam schema
-- Database: tuyen_sinh_lop10
--
-- This migration does NOT modify score publication triggers or score tables.

USE `tuyen_sinh_lop10`;

DROP PROCEDURE IF EXISTS `batchB_add_exam_invariants`;
DELIMITER $$
CREATE PROCEDURE `batchB_add_exam_invariants`()
BEGIN
  /* A site id is paired with its admission period for composite FK checks. */
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'exam_sites'
      AND index_name = 'uq_exam_site_id_period'
  ) THEN
    ALTER TABLE `exam_sites`
      ADD UNIQUE KEY `uq_exam_site_id_period` (`id`,`admission_period_id`);
  END IF;

  /* A room id is paired with its owning site for composite FK checks. */
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'exam_rooms'
      AND index_name = 'uq_exam_room_id_site'
  ) THEN
    ALTER TABLE `exam_rooms`
      ADD UNIQUE KEY `uq_exam_room_id_site` (`id`,`exam_site_id`);
  END IF;

  /* Needed so a room assignment can reference exactly the allocated site. */
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.statistics
    WHERE table_schema = DATABASE()
      AND table_name = 'exam_allocations'
      AND index_name = 'uq_exam_allocation_period_candidate_site'
  ) THEN
    ALTER TABLE `exam_allocations`
      ADD UNIQUE KEY `uq_exam_allocation_period_candidate_site`
        (`admission_period_id`,`candidate_id`,`exam_site_id`);
  END IF;

  /* Allocation site must belong to the same admission period as the allocation. */
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.table_constraints
    WHERE constraint_schema = DATABASE()
      AND table_name = 'exam_allocations'
      AND constraint_name = 'fk_exam_allocation_site_period'
      AND constraint_type = 'FOREIGN KEY'
  ) THEN
    ALTER TABLE `exam_allocations`
      ADD CONSTRAINT `fk_exam_allocation_site_period`
      FOREIGN KEY (`exam_site_id`,`admission_period_id`)
      REFERENCES `exam_sites` (`id`,`admission_period_id`)
      ON UPDATE CASCADE ON DELETE RESTRICT;
  END IF;

  /* Room assignment site must belong to the same admission period. */
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.table_constraints
    WHERE constraint_schema = DATABASE()
      AND table_name = 'room_assignments'
      AND constraint_name = 'fk_room_assignment_site_period'
      AND constraint_type = 'FOREIGN KEY'
  ) THEN
    ALTER TABLE `room_assignments`
      ADD CONSTRAINT `fk_room_assignment_site_period`
      FOREIGN KEY (`exam_site_id`,`admission_period_id`)
      REFERENCES `exam_sites` (`id`,`admission_period_id`)
      ON UPDATE CASCADE ON DELETE RESTRICT;
  END IF;

  /* A room assignment must reference a room owned by that exact site. */
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.table_constraints
    WHERE constraint_schema = DATABASE()
      AND table_name = 'room_assignments'
      AND constraint_name = 'fk_room_assignment_room_site'
      AND constraint_type = 'FOREIGN KEY'
  ) THEN
    ALTER TABLE `room_assignments`
      ADD CONSTRAINT `fk_room_assignment_room_site`
      FOREIGN KEY (`exam_room_id`,`exam_site_id`)
      REFERENCES `exam_rooms` (`id`,`exam_site_id`)
      ON UPDATE CASCADE ON DELETE RESTRICT;
  END IF;

  /* A room assignment site must equal the candidate's allocation site in the same period. */
  IF NOT EXISTS (
    SELECT 1 FROM information_schema.table_constraints
    WHERE constraint_schema = DATABASE()
      AND table_name = 'room_assignments'
      AND constraint_name = 'fk_room_assignment_allocation'
      AND constraint_type = 'FOREIGN KEY'
  ) THEN
    ALTER TABLE `room_assignments`
      ADD CONSTRAINT `fk_room_assignment_allocation`
      FOREIGN KEY (`admission_period_id`,`candidate_id`,`exam_site_id`)
      REFERENCES `exam_allocations` (`admission_period_id`,`candidate_id`,`exam_site_id`)
      ON UPDATE CASCADE ON DELETE RESTRICT;
  END IF;
END$$
DELIMITER ;

CALL `batchB_add_exam_invariants`();
DROP PROCEDURE IF EXISTS `batchB_add_exam_invariants`;

-- Capacity remains a server-side aggregate invariant: it cannot be expressed
-- safely with a normal CHECK constraint because it depends on row counts.
-- Candidate-to-period membership is not a first-class relation in the current
-- schema; see includes/exam_service.php for the explicit [MISSING SPECIFICATION].
