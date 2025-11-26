<?php
/**
 * FACULTY PROJECT IDEAS REVIEW PAGE
 * 
 * PURPOSE:
 * Faculty dashboard for reviewing submitted project proposals with comprehensive evaluation tools, approval/rejection workflow, and feedback mechanisms.
 * 
 * CORE FUNCTIONALITY:
 * 1. Fetch all project ideas assigned to logged-in faculty where status='pending' or 'revision_requested'
 * 2. Display paginated table with columns: Group Name, Project Title, Submitted Date, Similarity Score, Status, Actions
 * 3. For each project, provide action buttons: View Details, Approve, Reject, Request Revision
 * 4. View Details modal/page showing: full description, objectives, technologies, scope, outcomes, similarity report, group member details
 * 5. Approve action: update project_ideas.status='approved', create entry in projects table, send notification to students
 * 6. Reject action: require faculty to provide detailed rejection reason (min 20 characters), update status='rejected', notify students
 * 7. Request Revision: faculty specifies what needs improvement, status='revision_requested', students can resubmit
 * 8. Display similarity warnings prominently if similarity_score > 70%, show list of similar existing projects
 * 9. Allow faculty to download proposal document if attached
 * 10. Filter options: All/Pending/Approved/Rejected, Search by title/group, Sort by date/similarity
 * 11. Bulk actions: Approve multiple/Reject multiple with common comment
 * 12. Log all review actions to audit_logs table
 * 
 * UI/UX REQUIREMENTS: Responsive table with expandable rows, color-coded status badges (pending=yellow, approved=green, rejected=red), similarity score progress bars, modal for detailed view, inline comment form, action confirmation dialogs, pagination with page size selector, export to Excel functionality
 * 
 * SECURITY: Faculty can only see projects assigned to them, prepared statements for all queries, XSS prevention in comments, CSRF protection on all forms, role verification on every action
 * 
 * DATABASE OPERATIONS: SELECT from project_ideas JOIN groups JOIN group_members JOIN students WHERE reviewed_by=faculty_id OR reviewed_by IS NULL, UPDATE project_ideas SET status=?, reviewed_by=?, reviewed_at=NOW(), review_comments=?, INSERT INTO projects if approved, INSERT INTO notifications for students
 * 
 * STATUS: 🔴 NOT IMPLEMENTED
 */
session_start();
require_once '../db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'faculty') {
    header("Location: ../login.php");
    exit();
}
$faculty_id = $_SESSION['faculty_id'];
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Review Project Ideas - BMIIT PMS</title>
    <link rel="icon" type="image/ico" href="/bmiit_pms/assets/bmiitfavicol.ico">
    <link rel="stylesheet" href="/bmiit_pms/css/dashboard.css">
</head>
<body>
    <?php include __DIR__ . '/faculty_header_menu.php'; ?>
    <div class="main">
        <header class="topbar">
            <h1>🔴 Review Project Ideas</h1>
            <form action="../logout.php" method="post" class="logout-form">
                <input type="submit" name="logout" value="Logout">
            </form>
        </header>
        <div style="padding: 20px;">
            <div style="background: #fff3cd; padding: 15px; margin-bottom: 20px; border-left: 4px solid #ffc107;">
                <strong>⚠️ PLACEHOLDER:</strong> Faculty project review interface not implemented. Will include approval/rejection workflow, similarity reports, and feedback mechanism.
            </div>
            <table style="width: 100%; background: white; border-collapse: collapse;">
                <thead>
                    <tr style="background: #f8f9fa;">
                        <th style="padding: 12px; border: 1px solid #ddd;">Group</th>
                        <th style="padding: 12px; border: 1px solid #ddd;">Title</th>
                        <th style="padding: 12px; border: 1px solid #ddd;">Submitted</th>
                        <th style="padding: 12px; border: 1px solid #ddd;">Similarity</th>
                        <th style="padding: 12px; border: 1px solid #ddd;">Status</th>
                        <th style="padding: 12px; border: 1px solid #ddd;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="6" style="padding: 40px; text-align: center; color: #999;">
                            No project ideas submitted yet. Waiting for student submissions...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
