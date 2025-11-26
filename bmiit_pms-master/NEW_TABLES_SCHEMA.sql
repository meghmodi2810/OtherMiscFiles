-- ============================================================================
-- BMIIT PMS - NEW DATABASE TABLES
-- ============================================================================
-- This file contains all NEW tables required for the placeholder pages.
-- Run this after the existing bmiit_pms.sql schema is in place.
-- 
-- Tables Included:
-- 1. password_reset_tokens - Password reset workflow
-- 2. project_ideas - Student project proposals
-- 3. projects - Approved projects
-- 4. milestones - Milestone definitions
-- 5. milestone_submissions - Student submissions
-- 6. grades - Faculty grading records
-- 7. notifications - System notifications
-- 8. conversations - Chat conversations
-- 9. messages - Chat messages
-- 10. audit_logs - System audit trail
-- ============================================================================

USE `bmiit_pms`;

-- ============================================================================
-- 1. PASSWORD RESET TOKENS
-- ============================================================================
-- Used by: forgot_password.php, reset_password.php
-- Purpose: Store one-time password reset tokens with expiration
DROP TABLE IF EXISTS `password_reset_tokens`;
CREATE TABLE `password_reset_tokens` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `token` VARCHAR(64) NOT NULL UNIQUE,
  `expires_at` DATETIME NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `used` TINYINT(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_token` (`token`),
  INDEX `idx_expires` (`expires_at`),
  INDEX `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Password reset tokens with 1-hour expiration';

-- ============================================================================
-- 2. PROJECT IDEAS
-- ============================================================================
-- Used by: submit_project_idea.php, review_project_ideas.php
-- Purpose: Store student project proposals before approval
DROP TABLE IF EXISTS `project_ideas`;
CREATE TABLE `project_ideas` (
  `idea_id` INT(11) NOT NULL AUTO_INCREMENT,
  `group_id` INT(11) NOT NULL,
  `title` VARCHAR(200) NOT NULL,
  `description` TEXT NOT NULL,
  `objectives` TEXT NOT NULL,
  `technologies` VARCHAR(500),
  `scope` TEXT,
  `expected_outcomes` TEXT,
  `proposal_file` VARCHAR(255),
  `status` ENUM('draft','pending','approved','rejected','revision_requested') DEFAULT 'pending',
  `similarity_score` DECIMAL(5,2) DEFAULT 0.00,
  `similar_projects` JSON,
  `submitted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `reviewed_at` TIMESTAMP NULL,
  `reviewed_by` INT(11) NULL,
  `review_comments` TEXT,
  PRIMARY KEY (`idea_id`),
  FOREIGN KEY (`group_id`) REFERENCES `groups`(`group_id`) ON DELETE CASCADE,
  FOREIGN KEY (`reviewed_by`) REFERENCES `faculty`(`faculty_id`) ON DELETE SET NULL,
  INDEX `idx_status` (`status`),
  INDEX `idx_group` (`group_id`),
  INDEX `idx_submitted` (`submitted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Student project idea submissions';

-- ============================================================================
-- 3. PROJECTS
-- ============================================================================
-- Used by: projects_dashboard.php, assign_guides.php, various pages
-- Purpose: Store approved projects with faculty assignments
DROP TABLE IF EXISTS `projects`;
CREATE TABLE `projects` (
  `project_id` INT(11) NOT NULL AUTO_INCREMENT,
  `idea_id` INT(11) NOT NULL,
  `group_id` INT(11) NOT NULL,
  `faculty_id` INT(11) NULL,
  `semester_id` INT(11) NOT NULL,
  `title` VARCHAR(200) NOT NULL,
  `description` TEXT,
  `status` ENUM('active','completed','on_hold','archived') DEFAULT 'active',
  `started_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `completed_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`project_id`),
  FOREIGN KEY (`idea_id`) REFERENCES `project_ideas`(`idea_id`) ON DELETE CASCADE,
  FOREIGN KEY (`group_id`) REFERENCES `groups`(`group_id`) ON DELETE CASCADE,
  FOREIGN KEY (`faculty_id`) REFERENCES `faculty`(`faculty_id`) ON DELETE SET NULL,
  FOREIGN KEY (`semester_id`) REFERENCES `semesters`(`id`) ON DELETE CASCADE,
  INDEX `idx_status` (`status`),
  INDEX `idx_faculty` (`faculty_id`),
  INDEX `idx_semester` (`semester_id`),
  INDEX `idx_group` (`group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Approved student projects';

-- ============================================================================
-- 4. MILESTONES
-- ============================================================================
-- Used by: manage_milestones.php, submit_milestone.php, grade_milestones.php
-- Purpose: Define project milestones with deadlines and weightage
DROP TABLE IF EXISTS `milestones`;
CREATE TABLE `milestones` (
  `milestone_id` INT(11) NOT NULL AUTO_INCREMENT,
  `semester_id` INT(11) NOT NULL,
  `milestone_name` VARCHAR(100) NOT NULL,
  `description` TEXT,
  `milestone_order` INT(11) NOT NULL,
  `submission_deadline` DATETIME NOT NULL,
  `evaluation_start` DATETIME NOT NULL,
  `evaluation_end` DATETIME NOT NULL,
  `weightage` DECIMAL(5,2) NOT NULL,
  `required_documents` JSON,
  `status` ENUM('upcoming','open','closed','archived') DEFAULT 'upcoming',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `created_by` INT(11),
  PRIMARY KEY (`milestone_id`),
  FOREIGN KEY (`semester_id`) REFERENCES `semesters`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  INDEX `idx_semester` (`semester_id`),
  INDEX `idx_deadline` (`submission_deadline`),
  INDEX `idx_status` (`status`),
  INDEX `idx_order` (`milestone_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Project milestones with deadlines';

-- ============================================================================
-- 5. MILESTONE SUBMISSIONS
-- ============================================================================
-- Used by: submit_milestone.php, grade_milestones.php, progress_tracker.php
-- Purpose: Store student milestone submissions with files and links
DROP TABLE IF EXISTS `milestone_submissions`;
CREATE TABLE `milestone_submissions` (
  `submission_id` INT(11) NOT NULL AUTO_INCREMENT,
  `milestone_id` INT(11) NOT NULL,
  `group_id` INT(11) NOT NULL,
  `submitted_by` INT(11) NOT NULL,
  `submission_files` JSON,
  `submission_links` JSON,
  `comments` TEXT,
  `submission_status` ENUM('draft','submitted','graded','revision_requested') DEFAULT 'draft',
  `submitted_at` TIMESTAMP NULL,
  `version` INT(11) DEFAULT 1,
  `is_late` TINYINT(1) DEFAULT 0,
  PRIMARY KEY (`submission_id`),
  FOREIGN KEY (`milestone_id`) REFERENCES `milestones`(`milestone_id`) ON DELETE CASCADE,
  FOREIGN KEY (`group_id`) REFERENCES `groups`(`group_id`) ON DELETE CASCADE,
  FOREIGN KEY (`submitted_by`) REFERENCES `students`(`student_id`) ON DELETE CASCADE,
  INDEX `idx_milestone` (`milestone_id`),
  INDEX `idx_group` (`group_id`),
  INDEX `idx_status` (`submission_status`),
  INDEX `idx_submitted` (`submitted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Student milestone submissions';

-- ============================================================================
-- 6. GRADES
-- ============================================================================
-- Used by: grade_milestones.php, progress_tracker.php, analytics_dashboard.php
-- Purpose: Store faculty grading with rubric scores and feedback
DROP TABLE IF EXISTS `grades`;
CREATE TABLE `grades` (
  `grade_id` INT(11) NOT NULL AUTO_INCREMENT,
  `submission_id` INT(11) NOT NULL,
  `graded_by` INT(11) NOT NULL,
  `rubric_scores` JSON,
  `total_marks` DECIMAL(5,2) NOT NULL,
  `feedback` TEXT NOT NULL,
  `strengths` TEXT,
  `improvements` TEXT,
  `grade_status` ENUM('draft','published') DEFAULT 'draft',
  `graded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `published_at` TIMESTAMP NULL,
  `revision_requested` TINYINT(1) DEFAULT 0,
  `revision_notes` TEXT,
  PRIMARY KEY (`grade_id`),
  FOREIGN KEY (`submission_id`) REFERENCES `milestone_submissions`(`submission_id`) ON DELETE CASCADE,
  FOREIGN KEY (`graded_by`) REFERENCES `faculty`(`faculty_id`) ON DELETE CASCADE,
  INDEX `idx_submission` (`submission_id`),
  INDEX `idx_graded_by` (`graded_by`),
  INDEX `idx_status` (`grade_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Faculty grading records';

-- ============================================================================
-- 7. NOTIFICATIONS
-- ============================================================================
-- Used by: notifications.php (all roles)
-- Purpose: System-wide notifications for users
DROP TABLE IF EXISTS `notifications`;
CREATE TABLE `notifications` (
  `notification_id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `notification_type` ENUM('deadline','approval','rejection','assignment','message','grade','system') NOT NULL,
  `title` VARCHAR(200) NOT NULL,
  `message` TEXT NOT NULL,
  `link_url` VARCHAR(500),
  `read_status` TINYINT(1) DEFAULT 0,
  `read_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `priority` ENUM('low','medium','high') DEFAULT 'medium',
  `related_id` INT(11),
  `related_type` VARCHAR(50),
  PRIMARY KEY (`notification_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_user_unread` (`user_id`, `read_status`),
  INDEX `idx_created` (`created_at` DESC),
  INDEX `idx_type` (`notification_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='User notifications';

-- ============================================================================
-- 8. CONVERSATIONS
-- ============================================================================
-- Used by: messages.php
-- Purpose: Chat conversation management
DROP TABLE IF EXISTS `conversations`;
CREATE TABLE `conversations` (
  `conversation_id` VARCHAR(50) NOT NULL,
  `participant1_id` INT(11) NOT NULL,
  `participant2_id` INT(11) NULL,
  `conversation_type` ENUM('direct','group') DEFAULT 'direct',
  `group_id` INT(11) NULL,
  `last_message_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`conversation_id`),
  FOREIGN KEY (`participant1_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`participant2_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`group_id`) REFERENCES `groups`(`group_id`) ON DELETE CASCADE,
  INDEX `idx_participant1` (`participant1_id`),
  INDEX `idx_participant2` (`participant2_id`),
  INDEX `idx_group` (`group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Chat conversations';

-- ============================================================================
-- 9. MESSAGES
-- ============================================================================
-- Used by: messages.php
-- Purpose: Store chat messages
DROP TABLE IF EXISTS `messages`;
CREATE TABLE `messages` (
  `message_id` INT(11) NOT NULL AUTO_INCREMENT,
  `sender_id` INT(11) NOT NULL,
  `receiver_id` INT(11) NOT NULL,
  `conversation_id` VARCHAR(50) NOT NULL,
  `message_text` TEXT,
  `attachment_url` VARCHAR(500),
  `attachment_type` VARCHAR(20),
  `message_status` ENUM('sent','delivered','read','deleted') DEFAULT 'sent',
  `sent_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `read_at` TIMESTAMP NULL,
  `is_group_message` TINYINT(1) DEFAULT 0,
  `group_id` INT(11) NULL,
  PRIMARY KEY (`message_id`),
  FOREIGN KEY (`sender_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`receiver_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`conversation_id`) REFERENCES `conversations`(`conversation_id`) ON DELETE CASCADE,
  FOREIGN KEY (`group_id`) REFERENCES `groups`(`group_id`) ON DELETE CASCADE,
  INDEX `idx_conversation` (`conversation_id`, `sent_at` DESC),
  INDEX `idx_sender` (`sender_id`),
  INDEX `idx_receiver` (`receiver_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Chat messages';

-- ============================================================================
-- 10. AUDIT LOGS
-- ============================================================================
-- Used by: All pages for security tracking
-- Purpose: System audit trail for all critical actions
DROP TABLE IF EXISTS `audit_logs`;
CREATE TABLE `audit_logs` (
  `log_id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `action` VARCHAR(100) NOT NULL,
  `details` TEXT,
  `ip_address` VARCHAR(45),
  `user_agent` VARCHAR(255),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`log_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_user` (`user_id`),
  INDEX `idx_action` (`action`),
  INDEX `idx_created` (`created_at` DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='System audit trail';

-- ============================================================================
-- HELPER FUNCTION: Generate Conversation ID
-- ============================================================================
-- Usage: For direct messages, conversation_id = CONCAT('user_', LEAST(user1_id, user2_id), '_', GREATEST(user1_id, user2_id))
--        For group messages, conversation_id = CONCAT('group_', group_id)

-- ============================================================================
-- VERIFICATION QUERIES (Run after table creation)
-- ============================================================================
-- SELECT COUNT(*) as table_count FROM information_schema.tables 
-- WHERE table_schema = 'bmiit_pms' AND table_name IN (
--   'password_reset_tokens', 'project_ideas', 'projects', 'milestones',
--   'milestone_submissions', 'grades', 'notifications', 'conversations', 
--   'messages', 'audit_logs'
-- );
-- Expected result: table_count = 10

-- ============================================================================
-- SAMPLE DATA FOR TESTING (Optional)
-- ============================================================================
-- Uncomment below to insert sample notification
-- INSERT INTO `notifications` (`user_id`, `notification_type`, `title`, `message`, `link_url`) 
-- VALUES (1, 'system', 'Welcome to BMIIT PMS', 'Your new project management system is ready!', '/admin/admin_home.php');

-- ============================================================================
-- END OF SCHEMA
-- ============================================================================
-- Total Tables Added: 10
-- Total Indexes Added: 40+
-- Total Foreign Keys: 20+
-- 
-- Next Steps:
-- 1. Run this SQL file on your database
-- 2. Verify all tables created successfully
-- 3. Update existing code to use new tables
-- 4. Implement placeholder pages one by one
-- ============================================================================
