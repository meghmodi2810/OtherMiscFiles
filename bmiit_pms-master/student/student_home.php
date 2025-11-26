<?php
require_once '../db.php';
session_start();

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// --- Session & role check ---
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'student') {
    header("Location: ../login.php");
    exit();
}

$student_id = $_SESSION['student_id'] ?? null;
if (!$student_id) {
    session_destroy();
    header("Location: ../login.php");
    exit();
}

// --- Fetch student info ---
$stmt = $conn->prepare("
    SELECT s.name, c.name AS class_name, c.id AS class_id, sem.id AS semester_id, sem.semester_no, sem.year, u.username AS enrollment_no
    FROM students s
    JOIN classes c ON s.class_id = c.id
    JOIN semesters sem ON c.semester_id = sem.id
    JOIN users u ON s.user_id = u.id
    WHERE s.student_id = ?
");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

if (!$student) {
    die("Student record not found. Please contact admin.");
}

// --- Fetch semester config ---
$stmt = $conn->prepare("SELECT team_size, interclass_allowed FROM semester_config WHERE semester_id=?");
$stmt->bind_param("i", $student['semester_id']);
$stmt->execute();
$config = $stmt->get_result()->fetch_assoc();
$team_size_limit = $config['team_size'] ?? 5;
$interclass_allowed = $config['interclass_allowed'] ?? 0;

// --- Count total students based on interclass rule ---
if ($interclass_allowed) {
    $stmt = $conn->prepare("SELECT COUNT(*) AS total_students FROM students WHERE class_id IN (SELECT id FROM classes WHERE semester_id=?)");
    $stmt->bind_param("i", $student['semester_id']);
} else {
    $stmt = $conn->prepare("SELECT COUNT(*) AS total_students FROM students WHERE class_id=?");
    $stmt->bind_param("i", $student['class_id']);
}
$stmt->execute();
$total_students = $stmt->get_result()->fetch_assoc()['total_students'];

// --- Count current groups (both forming and finalized) ---
if ($interclass_allowed) {
    $stmt = $conn->prepare("SELECT COUNT(*) AS total_groups FROM groups WHERE semester_id=?");
    $stmt->bind_param("i", $student['semester_id']);
} else {
    $stmt = $conn->prepare("SELECT COUNT(*) AS total_groups FROM groups WHERE semester_id=? AND (class_id=? OR class_id IS NULL)");
    $stmt->bind_param("ii", $student['semester_id'], $student['class_id']);
}
$stmt->execute();
$current_groups = $stmt->get_result()->fetch_assoc()['total_groups'];

// --- Determine leader eligibility ---
$max_groups = ceil($total_students / $team_size_limit);
$can_be_leader = ($current_groups < $max_groups);

// --- Fetch group info ---
$stmt = $conn->prepare("
    SELECT g.group_id, gm.role, g.finalized, g.semester_id,
           (SELECT COUNT(*) FROM groups g2 WHERE g2.semester_id = g.semester_id AND g2.group_id <= g.group_id) AS group_number
    FROM group_members gm
    JOIN groups g ON gm.group_id = g.group_id
    WHERE gm.student_id = ?
");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$group = $stmt->get_result()->fetch_assoc();

// If the user is in a group, fetch member count for nicer display on dashboard
if ($group) {
    $mstmt = $conn->prepare("SELECT COUNT(*) AS member_count FROM group_members WHERE group_id=?");
    $mstmt->bind_param("i", $group['group_id']);
    $mstmt->execute();
    $mc = $mstmt->get_result()->fetch_assoc();
    $group['member_count'] = isset($mc['member_count']) ? intval($mc['member_count']) : 0;
    $mstmt->close();
}

// --- Count pending invites ---
$stmt = $conn->prepare("SELECT COUNT(*) AS pending_invites FROM group_invites WHERE receiver_id=? AND status='pending'");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$pending_invites = $stmt->get_result()->fetch_assoc()['pending_invites'] ?? 0;

// Setup session user array for new includes
if (!isset($_SESSION['user'])) {
    $_SESSION['user'] = [
        'user_id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'] ?? 'student',
        'role' => 'student',
        'name' => $student['name']
    ];
}

$page_title = 'Student Dashboard';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<main class="main-wrapper">
    <div class="container fade-in">
        <div class="card">
            <div class="card-header">
                <h1 class="card-title">Welcome, <?= $student['name'] ?>!</h1>
                <p class="card-subtitle">
                    Enrollment: <strong><?= $student['enrollment_no'] ?></strong> • 
                    <?= $student['class_name'] ?> • 
                    Semester <?= $student['semester_no'] ?> • 
                    Year <?= $student['year'] ?>
                </p>
            </div>
        </div>

        <div class="dashboard-cards">
            <!-- Semester Info -->
            <div class="stat-card">
                <h3>Max Team Size</h3>
                <p><?= $team_size_limit ?></p>
            </div>
            <div class="stat-card">
                <h3>Interclass</h3>
                <p><?= $interclass_allowed ? 'Allowed' : 'Not Allowed' ?></p>
            </div>

            <!-- Group Status -->
            <div class="stat-card">
                <h3>Group Status</h3>
                <?php if ($group): ?>
                    <p style="font-size:20px;font-weight:700">Group #<?= $group['group_number'] ?> &middot; <?= intval($group['member_count'] ?? 0) ?> member<?= (intval($group['member_count'] ?? 0) === 1) ? '' : 's' ?></p>
                    <span class="badge badge-success"><?= ucfirst($group['role']) ?></span>
                    <span class="badge <?= $group['finalized'] ? 'badge-success' : 'badge-warning' ?>" style="margin-left:8px">
                        <?= $group['finalized'] ? 'Finalized' : 'Forming' ?>
                    </span>
                <?php else: ?>
                    <p style="color:var(--text-light)">Not in a group yet</p>
                <?php endif; ?>
            </div>

            <!-- Pending Invites -->
            <div class="stat-card">
                <h3>Pending Invites</h3>
                <p><?= $pending_invites ?></p>
                <?php if ($pending_invites > 0): ?>
                    <a href="/bmiit_pms/student/accept_invite.php" class="btn mt-2" style="font-size:13px;padding:6px 12px">
                        <i data-feather="mail" style="width:14px;height:14px"></i>
                        View Invites
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Quick Actions</h2>
                <p class="card-subtitle">Manage your group and projects</p>
            </div>
            <div class="quick-actions">
                <?php if (!$group): ?>
                    <?php if ($can_be_leader): ?>
                        <a href="/bmiit_pms/student/create_group.php" class="btn">
                            <i data-feather="users"></i>
                            Create Group (Be Leader)
                        </a>
                    <?php else: ?>
                        <?php /* Use the global modal for this informational message so it matches site-wide modals.
                                 Falls back to an inline alert if JS modal API isn't present. */ ?>
                        <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            const message = 'All leader slots are currently filled. You can still join a team — either wait for an invitation or browse groups and send a join request.';
                            // If global modal API is available, show a modal with action to browse groups
                            if (window && typeof window.showConfirm === 'function') {
                                window.showConfirm({
                                    title: 'Leader slots',
                                    message: message,
                                    okText: 'Browse Groups',
                                    cancelText: 'Close',
                                    danger: false
                                }).then(function(confirmed) {
                                    if (confirmed) {
                                        window.location.href = '/bmiit_pms/student/browse_groups.php';
                                    }
                                }).catch(function(){});
                            } else if (window && typeof window.showAlert === 'function') {
                                // Fallback to page-level inline alert via global API
                                window.showAlert(message, 'warning', { anchor: '.container' });
                            } else {
                                // Final fallback: inject the existing inline alert markup
                                const container = document.querySelector('.dashboard-cards');
                                if (container) {
                                    const div = document.createElement('div');
                                    div.className = 'alert alert-error';
                                    div.innerHTML = '<i data-feather="alert-circle"></i>' +
                                                    '<span> ' + message + ' <a href="/bmiit_pms/student/browse_groups.php" style="text-decoration:underline;">browse groups and send a join request</a>.</span>';
                                    container.insertBefore(div, container.firstChild);
                                    if (typeof feather !== 'undefined' && feather.replace) feather.replace();
                                }
                            }
                        });
                        </script>
                    <?php endif; ?>
                <?php elseif ($group && !$group['finalized']): ?>
                    <a href="/bmiit_pms/student/manage_group.php" class="btn">
                        <i data-feather="settings"></i>
                        Manage Group
                    </a>
                    <?php if ($group['role'] == 'leader'): ?>
                        <a href="/bmiit_pms/student/invite.php" class="btn">
                            <i data-feather="user-plus"></i>
                            Invite Members
                        </a>
                        <a href="/bmiit_pms/student/transfer_leader.php" class="btn-secondary">
                            <i data-feather="repeat"></i>
                            Transfer Leadership
                        </a>
                        <a href="/bmiit_pms/student/dissolve_group.php" class="btn-secondary" style="border-color:#ef4444;color:#ef4444">
                            <i data-feather="x-circle"></i>
                            Dissolve Group
                        </a>
                    <?php endif; ?>
                <?php elseif ($group && $group['finalized']): ?>
                    <a href="/bmiit_pms/student/submit_project_idea.php" class="btn">
                        <i data-feather="file-plus"></i>
                        Submit Project Idea
                    </a>
                    <a href="/bmiit_pms/student/submit_milestone.php" class="btn">
                        <i data-feather="upload-cloud"></i>
                        Submit Milestone
                    </a>
                    <a href="/bmiit_pms/student/progress_tracker.php" class="btn-secondary">
                        <i data-feather="trending-up"></i>
                        View Progress
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
