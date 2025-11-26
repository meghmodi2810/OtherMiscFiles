<?php
/**
 * ADMIN MILESTONE CREATION & MANAGEMENT PAGE
 * 
 * PURPOSE:
 * Administrative interface for defining project milestones with deadlines, evaluation windows, weightage, and deliverable requirements across semesters.
 * 
 * CORE FUNCTIONALITY:
 * 1. Create milestones for specific semester/course with fields: milestone_name, description, deadline, evaluation_window_start, evaluation_window_end, weightage (percentage), mandatory_documents
 * 2. Milestone types: Proposal, Design Document, Mid-Term Demo, Final Report, Final Presentation, Code Submission
 * 3. Set submission deadline and separate evaluation window (e.g., submit by Dec 1, faculty must grade by Dec 10)
 * 4. Define weightage for each milestone (total must equal 100% across all milestones)
 * 5. Specify required deliverables: documents (PDF), code (ZIP/GitHub link), presentation slides (PPT/PDF), demo video (optional)
 * 6. Bulk milestone creation: create standard milestone template and apply to multiple semesters at once
 * 7. Edit existing milestones: can modify dates/descriptions if no submissions yet, otherwise only extend deadlines
 * 8. Delete milestones: only if no submissions exist, otherwise archive instead
 * 9. Clone milestones from previous semester: copy entire milestone structure with updated dates
 * 10. Set auto-reminders: email/notification sent to students X days before deadline (configurable: 7 days, 3 days, 1 day)
 * 11. Lock/unlock milestones: prevent editing by students after deadline, admin can reopen for late submissions
 * 12. View milestone completion statistics: how many groups submitted, pending count, average submission time
 * 
 * UI/UX REQUIREMENTS: Tabbed interface per semester, timeline visualization showing all milestones on calendar, drag-to-reorder milestones, weightage calculator with live total (must sum to 100%), color-coded status (upcoming=blue, open=green, closed=gray), milestone template library, quick actions dropdown (edit/delete/clone/lock), responsive table with expandable rows for details
 * 
 * SECURITY: Admin-only access, validate weightage totals to 100%, prevent deadline in past for new milestones, check no overlapping evaluation windows, CSRF protection, transaction-based updates
 * 
 * DATABASE SCHEMA NEEDED:
 * CREATE TABLE milestones (
 *   milestone_id INT AUTO_INCREMENT PRIMARY KEY,
 *   semester_id INT NOT NULL,
 *   milestone_name VARCHAR(100) NOT NULL,
 *   description TEXT,
 *   milestone_order INT NOT NULL,
 *   submission_deadline DATETIME NOT NULL,
 *   evaluation_start DATETIME NOT NULL,
 *   evaluation_end DATETIME NOT NULL,
 *   weightage DECIMAL(5,2) NOT NULL,
 *   required_documents JSON,
 *   status ENUM('upcoming','open','closed','archived') DEFAULT 'upcoming',
 *   created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 *   created_by INT,
 *   FOREIGN KEY (semester_id) REFERENCES semesters(id) ON DELETE CASCADE,
 *   FOREIGN KEY (created_by) REFERENCES users(id)
 * );
 * 
 * STATUS: 🔴 NOT IMPLEMENTED
 */
session_start();
require_once '../db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Manage Milestones - BMIIT PMS</title>
    <link rel="icon" type="image/ico" href="/bmiit_pms/assets/bmiitfavicol.ico">
    <link rel="stylesheet" href="/bmiit_pms/css/dashboard.css">
</head>
<body>
    <?php include __DIR__ . '/admin_header_menu.php'; ?>
    <div class="main">
        <header class="topbar">
            <h1>🔴 Manage Milestones</h1>
            <form action="../logout.php" method="post" class="logout-form">
                <input type="submit" name="logout" value="Logout">
            </form>
        </header>
        <div style="padding: 20px;">
            <div style="background: #fff3cd; padding: 15px; margin-bottom: 20px; border-left: 4px solid #ffc107;">
                <strong>⚠️ PLACEHOLDER:</strong> Milestone management system not implemented. Will include creation, deadlines, weightage, evaluation windows, and auto-reminders.
            </div>
            <div style="background: white; padding: 20px; border-radius: 8px;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 20px;">
                    <h3>Milestones Overview</h3>
                    <button style="padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 5px;">
                        + Create New Milestone
                    </button>
                </div>
                <p style="color: #999; text-align: center; padding: 40px;">
                    No milestones defined yet. Click "Create New Milestone" to get started.
                </p>
            </div>
        </div>
    </div>
</body>
</html>
