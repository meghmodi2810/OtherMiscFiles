<?php
/**
 * STUDENT PROJECT IDEA SUBMISSION PAGE
 * 
 * PURPOSE:
 * Student-facing form for submitting innovative project proposals with comprehensive details, automatic similarity detection, and workflow integration for faculty approval.
 * 
 * CORE FUNCTIONALITY:
 * 1. Verify student is logged in, has active group, and group is finalized (finalized=1 in groups table)
 * 2. Check if student's group already has a submitted/approved project (prevent multiple submissions)
 * 3. Display project submission form with fields: Title, Description, Objectives, Technologies, Scope, Expected Outcomes
 * 4. On submission, validate all required fields: title (5-200 chars), description (50-2000 chars), objectives (min 20 chars)
 * 5. Perform automatic similarity/plagiarism check against existing projects in database:
 *    - Compare title and description using Levenshtein distance or cosine similarity
 *    - Flag if similarity > 70% with existing projects
 *    - Show warning to student but allow submission with acknowledgment checkbox
 * 6. Insert into project_ideas table with fields: idea_id, group_id, title, description, objectives, technologies, scope, outcomes, status ('pending'), submitted_at, similarity_score, similar_projects_json
 * 7. Send notification to assigned faculty guide (if already assigned) or admin for review
 * 8. Send email to faculty with project details and approval link
 * 9. Display success message with submission ID and next steps
 * 10. Redirect to student dashboard showing "Project Idea Pending Approval" status
 * 11. Log submission action in audit_logs table
 * 
 * VALIDATION RULES:
 * - Group must be finalized before submitting ideas
 * - Only group leader can submit project idea
 * - Title: unique within semester, 5-200 characters
 * - Description: detailed explanation, 50-2000 characters
 * - Technologies: comma-separated list (e.g., "PHP, MySQL, JavaScript")
 * - Objectives: minimum 3 bullet points or 20 words
 * - Scope: clear definition of what will/won't be done
 * - Expected Outcomes: measurable deliverables
 * - File attachments: optional proposal document (PDF/DOCX, max 2MB)
 * 
 * SIMILARITY DETECTION:
 * - Algorithm: Calculate Levenshtein distance for title and description
 * - Compare against all existing projects in same semester/course
 * - Generate similarity percentage (0-100%)
 * - If >70% similar, show warning with similar project titles
 * - Store similarity data in JSON format for admin review
 * - Allow override with mandatory justification field
 * 
 * UI/UX REQUIREMENTS:
 * - Multi-section form with clear labels and placeholders
 * - Character counters for title, description, objectives
 * - Rich text editor for description and objectives (basic formatting)
 * - Technology stack suggestions/autocomplete (predefined list)
 * - Scope definition wizard: "In Scope" vs "Out of Scope" sections
 * - File upload with drag-and-drop for proposal document
 * - Real-time similarity check as user types (AJAX)
 * - Similarity warning modal with "Proceed Anyway" option
 * - Save as draft functionality (not submitted, can edit later)
 * - Preview mode before final submission
 * - Responsive design for mobile/tablet
 * 
 * SECURITY:
 * - Only authenticated students can access
 * - Leader-only submission enforcement
 * - Group finalization check (prevent premature submissions)
 * - SQL injection prevention (prepared statements)
 * - XSS prevention in form inputs
 * - File upload validation (type, size, malware scan optional)
 * - Rate limiting: max 3 submissions per group per day
 * 
 * WORKFLOW INTEGRATION:
 * - After submission, status = 'pending'
 * - Notification sent to faculty/admin
 * - Faculty reviews in faculty/review_project_ideas.php
 * - Faculty can approve/reject/request modifications
 * - On approval: status = 'approved', project officially created
 * - On rejection: status = 'rejected', student can resubmit with changes
 * 
 * DATABASE SCHEMA NEEDED:
 * CREATE TABLE project_ideas (
 *   idea_id INT AUTO_INCREMENT PRIMARY KEY,
 *   group_id INT NOT NULL,
 *   title VARCHAR(200) NOT NULL,
 *   description TEXT NOT NULL,
 *   objectives TEXT NOT NULL,
 *   technologies VARCHAR(500),
 *   scope TEXT,
 *   expected_outcomes TEXT,
 *   proposal_file VARCHAR(255),
 *   status ENUM('draft','pending','approved','rejected','revision_requested') DEFAULT 'pending',
 *   similarity_score DECIMAL(5,2) DEFAULT 0,
 *   similar_projects JSON,
 *   submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 *   reviewed_at TIMESTAMP NULL,
 *   reviewed_by INT NULL,
 *   review_comments TEXT,
 *   FOREIGN KEY (group_id) REFERENCES groups(group_id) ON DELETE CASCADE,
 *   FOREIGN KEY (reviewed_by) REFERENCES faculty(faculty_id) ON DELETE SET NULL
 * );
 * 
 * STATUS: 🔴 NOT IMPLEMENTED
 */

session_start();
require_once '../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'student') {
    header("Location: ../login.php");
    exit();
}

$student_id = $_SESSION['student_id'];
$message = "";
$alert_type = "";

// TODO: Check if student has finalized group
$has_group = false;
$is_leader = false;
$group_id = null;

// PLACEHOLDER: Group validation logic
$message = "🔴 PLACEHOLDER: Project idea submission not implemented. See code comments for requirements.";
$alert_type = "info";

// TODO: Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_idea'])) {
    // PLACEHOLDER: Project idea submission logic
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Submit Project Idea - BMIIT PMS</title>
    <link rel="icon" type="image/ico" href="/bmiit_pms/assets/bmiitfavicol.ico">
    <link rel="stylesheet" href="/bmiit_pms/css/dashboard.css">
    <link rel="stylesheet" href="/bmiit_pms/student/css/student_home.css">
</head>
<body>
    <?php include __DIR__ . '/student_header_menu.php'; ?>

    <div class="main">
        <header class="topbar">
            <h1>🔴 Submit Project Idea</h1>
            <form action="../logout.php" method="post" class="logout-form">
                <input type="submit" name="logout" value="Logout">
            </form>
        </header>

        <div style="max-width: 900px; margin: 20px auto; padding: 20px;">
            <?php if ($message): ?>
                <div class="<?= $msg_class ?>" style="padding: 15px; margin-bottom: 20px; border-radius: 5px;">
                    <?= $message ?>
                </div>
            <?php endif; ?>

            <div style="background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                <h2>📝 Project Proposal Form</h2>
                <p style="color: #666; margin-bottom: 30px;">
                    Submit your innovative project idea with comprehensive details. Your proposal will be reviewed by faculty.
                </p>

                <form method="POST" action="" enctype="multipart/form-data">
                    <!-- Project Title -->
                    <div class="form-group" style="margin-bottom: 20px;">
                        <label style="font-weight: bold; display: block; margin-bottom: 5px;">Project Title *</label>
                        <input type="text" name="title" placeholder="Enter a concise, descriptive title" required 
                               style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                        <small style="color: #666;">5-200 characters</small>
                    </div>

                    <!-- Description -->
                    <div class="form-group" style="margin-bottom: 20px;">
                        <label style="font-weight: bold; display: block; margin-bottom: 5px;">Description *</label>
                        <textarea name="description" rows="6" placeholder="Provide a detailed description of your project" required 
                                  style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;"></textarea>
                        <small style="color: #666;">50-2000 characters</small>
                    </div>

                    <!-- Objectives -->
                    <div class="form-group" style="margin-bottom: 20px;">
                        <label style="font-weight: bold; display: block; margin-bottom: 5px;">Objectives *</label>
                        <textarea name="objectives" rows="4" placeholder="List key objectives (minimum 3 bullet points)" required 
                                  style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;"></textarea>
                    </div>

                    <!-- Technologies -->
                    <div class="form-group" style="margin-bottom: 20px;">
                        <label style="font-weight: bold; display: block; margin-bottom: 5px;">Technologies/Tools *</label>
                        <input type="text" name="technologies" placeholder="e.g., PHP, MySQL, JavaScript, React" required 
                               style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                    </div>

                    <!-- Scope -->
                    <div class="form-group" style="margin-bottom: 20px;">
                        <label style="font-weight: bold; display: block; margin-bottom: 5px;">Scope (In/Out)</label>
                        <textarea name="scope" rows="4" placeholder="Define what is included and excluded from the project" 
                                  style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;"></textarea>
                    </div>

                    <!-- Expected Outcomes -->
                    <div class="form-group" style="margin-bottom: 20px;">
                        <label style="font-weight: bold; display: block; margin-bottom: 5px;">Expected Outcomes *</label>
                        <textarea name="outcomes" rows="3" placeholder="What deliverables do you expect to produce?" required 
                                  style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;"></textarea>
                    </div>

                    <!-- File Upload -->
                    <div class="form-group" style="margin-bottom: 20px;">
                        <label style="font-weight: bold; display: block; margin-bottom: 5px;">Proposal Document (Optional)</label>
                        <input type="file" name="proposal_file" accept=".pdf,.docx">
                        <small style="color: #666;">PDF or DOCX, max 2MB</small>
                    </div>

                    <div style="display: flex; gap: 10px; margin-top: 30px;">
                        <button type="submit" name="submit_idea" style="padding: 12px 30px; background: #28a745; color: white; border: none; border-radius: 5px; cursor: pointer;">
                            Submit for Review
                        </button>
                        <button type="button" style="padding: 12px 30px; background: #6c757d; color: white; border: none; border-radius: 5px; cursor: pointer;">
                            Save as Draft
                        </button>
                    </div>
                </form>
            </div>

            <!-- Implementation Guide -->
            <div style="margin-top: 30px; padding: 20px; background: #fff3cd; border-left: 4px solid #ffc107; border-radius: 5px;">
                <h3>⚠️ IMPLEMENTATION REQUIRED:</h3>
                <ul style="font-size: 14px; margin-top: 10px;">
                    <li>✅ Group finalization check</li>
                    <li>✅ Leader-only submission enforcement</li>
                    <li>✅ Similarity detection algorithm (Levenshtein/cosine)</li>
                    <li>✅ Real-time similarity check (AJAX)</li>
                    <li>✅ File upload validation & storage</li>
                    <li>✅ Notification to faculty/admin</li>
                    <li>✅ Email integration</li>
                    <li>✅ Draft save functionality</li>
                    <li>✅ Preview before submission</li>
                    <li>✅ Audit logging</li>
                </ul>
            </div>
        </div>
    </div>
</body>
</html>
