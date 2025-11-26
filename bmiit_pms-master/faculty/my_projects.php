<?php
/**
 * FACULTY MY PROJECTS PAGE
 * 
 * PURPOSE:
 * Faculty-specific dashboard showing all assigned project groups with quick access to submissions, grading tasks, and student communication.
 * 
 * CORE FUNCTIONALITY:
 * 1. Display all groups assigned to logged-in faculty in card/table format
 * 2. For each group show: Group ID, project title, group leader, member count, current status, pending tasks count
 * 3. Pending tasks indicators: Ungraded submissions (red badge), new messages (blue badge), overdue evaluations (yellow warning)
 * 4. Quick action buttons per project: View Details, Grade Submissions, Send Message, View Progress
 * 5. Filter projects: All/Active/Completed/Pending Approval
 * 6. Sort by: Newest first, Oldest first, Pending tasks count, Group name
 * 7. Search functionality: Find groups by project title, leader name, group ID
 * 8. Workload summary card: Total assigned projects, pending evaluations count, average grading time
 * 9. Calendar view: Show upcoming evaluation deadlines across all assigned groups
 * 10. Export list: Download assigned projects list with contact information as Excel
 * 
 * UI/UX REQUIREMENTS: Card-based grid layout for projects, each card shows project thumbnail/icon with key info, color-coded status badges, notification badges for pending tasks, expandable cards showing full project details and group members, responsive grid (3 cols desktop, 2 tablet, 1 mobile), quick filter pills at top, floating action button for common actions
 * 
 * SECURITY: Faculty only see projects assigned to them (WHERE faculty_id = ?), prepared statements, validate faculty_id matches session, prevent unauthorized access to other faculty's projects
 * 
 * DATABASE QUERY:
 * SELECT p.project_id, p.title, p.status, g.group_id, s.name as leader_name,
 *        (SELECT COUNT(*) FROM group_members WHERE group_id = g.group_id) as member_count,
 *        (SELECT COUNT(*) FROM milestone_submissions ms JOIN milestones m ON ms.milestone_id=m.milestone_id WHERE ms.group_id=g.group_id AND ms.submission_status='submitted' AND NOT EXISTS (SELECT 1 FROM grades WHERE submission_id=ms.submission_id)) as pending_grading
 * FROM projects p
 * JOIN groups g ON p.group_id = g.group_id
 * JOIN students s ON g.leader_id = s.student_id
 * WHERE p.faculty_id = ?
 * ORDER BY p.updated_at DESC
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
    <title>My Projects - BMIIT PMS</title>
    <link rel="icon" type="image/ico" href="/bmiit_pms/assets/bmiitfavicol.ico">
    <link rel="stylesheet" href="/bmiit_pms/css/dashboard.css">
</head>
<body>
    <?php include __DIR__ . '/faculty_header_menu.php'; ?>
    <div class="main">
        <header class="topbar">
            <h1>🔴 My Assigned Projects</h1>
            <form action="../logout.php" method="post" class="logout-form">
                <input type="submit" name="logout" value="Logout">
            </form>
        </header>
        <div style="padding: 20px;">
            <div style="background: #fff3cd; padding: 15px; margin-bottom: 20px; border-left: 4px solid #ffc107; border-radius: 5px;">
                <strong>⚠️ PLACEHOLDER:</strong> Faculty projects view not implemented. Will show assigned groups, pending grading tasks, quick actions, and workload summary.
            </div>

            <!-- Workload Summary -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 25px;">
                <div style="background: white; padding: 20px; border-radius: 8px; border-left: 4px solid #007bff;">
                    <div style="font-size: 13px; color: #666; text-transform: uppercase; margin-bottom: 5px;">Assigned Projects</div>
                    <div style="font-size: 32px; font-weight: bold;">0</div>
                </div>
                <div style="background: white; padding: 20px; border-radius: 8px; border-left: 4px solid #dc3545;">
                    <div style="font-size: 13px; color: #666; text-transform: uppercase; margin-bottom: 5px;">Pending Evaluations</div>
                    <div style="font-size: 32px; font-weight: bold;">0</div>
                </div>
                <div style="background: white; padding: 20px; border-radius: 8px; border-left: 4px solid #28a745;">
                    <div style="font-size: 13px; color: #666; text-transform: uppercase; margin-bottom: 5px;">Completed This Month</div>
                    <div style="font-size: 32px; font-weight: bold;">0</div>
                </div>
            </div>

            <!-- Projects Grid -->
            <div style="background: white; padding: 25px; border-radius: 8px;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 20px;">
                    <h2>Assigned Groups</h2>
                    <input type="text" placeholder="Search projects..." style="padding: 8px 15px; border: 1px solid #ddd; border-radius: 5px;">
                </div>
                <div style="text-align: center; padding: 60px; color: #999;">
                    <h3>No projects assigned yet</h3>
                    <p>You will see your assigned project groups here once admin assigns them.</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
