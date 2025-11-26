-- ============================================================================
-- GUIDE ALLOCATION SCHEMA
-- ============================================================================
-- This file adds guide allocation functionality to the system
-- Run this after NEW_TABLES_SCHEMA.sql
-- ============================================================================

USE `bmiit_pms`;

-- ============================================================================
-- 1. ADD GUIDE COLUMN TO GROUPS TABLE
-- ============================================================================
-- Add guide_id to track which faculty is assigned as guide
ALTER TABLE `groups` 
ADD COLUMN `guide_id` INT(11) NULL AFTER `finalized`,
ADD FOREIGN KEY (`guide_id`) REFERENCES `faculty`(`faculty_id`) ON DELETE SET NULL;

-- ============================================================================
-- 2. GUIDE ALLOCATION STATUS
-- ============================================================================
-- Track guide allocation status per semester (finalized or not)
DROP TABLE IF EXISTS `guide_allocation`;
CREATE TABLE `guide_allocation` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `semester_id` INT(11) NOT NULL,
  `is_finalized` TINYINT(1) DEFAULT 0,
  `total_groups` INT(11) DEFAULT 0,
  `allocated_groups` INT(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_semester` (`semester_id`),
  FOREIGN KEY (`semester_id`) REFERENCES `semesters`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Guide allocation status per semester';

-- ============================================================================
-- NOTES:
-- ============================================================================
-- - guide_id in groups table: Current guide assigned to the group
-- - guide_allocation: Track overall allocation status per semester
-- ============================================================================
