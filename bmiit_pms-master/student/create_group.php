<?php
session_start();
require_once '../db.php';

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
    die("Student session missing. Please login again.");
}

$message = "";
$alert_type = "";

// --- Fetch student info ---
$stmt = $conn->prepare("
    SELECT s.name, s.class_id, c.name AS class_name, c.semester_id, sem.semester_no, sem.year
    FROM students s
    JOIN classes c ON s.class_id = c.id
    JOIN semesters sem ON c.semester_id = sem.id
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

// --- Check if student is already in a group ---
$stmt = $conn->prepare("SELECT gm.group_id, gm.role FROM group_members gm WHERE gm.student_id=?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$existing_group = $stmt->get_result()->fetch_assoc();

if ($existing_group) {
    // User is already in a group, redirect to manage group
    $_SESSION['flash_message'] = "You are already in a group! Here's your group dashboard.";
    $_SESSION['flash_type'] = "info";
    header("Location: manage_group.php");
    exit();
}

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

// --- Count forming and finalized groups separately ---
if ($interclass_allowed) {
    $stmt = $conn->prepare("SELECT COUNT(*) AS forming_groups FROM groups WHERE semester_id=? AND finalized=0");
    $stmt->bind_param("i", $student['semester_id']);
} else {
    $stmt = $conn->prepare("SELECT COUNT(*) AS forming_groups FROM groups WHERE semester_id=? AND (class_id=? OR class_id IS NULL) AND finalized=0");
    $stmt->bind_param("ii", $student['semester_id'], $student['class_id']);
}
$stmt->execute();
$forming_groups = $stmt->get_result()->fetch_assoc()['forming_groups'];

if ($interclass_allowed) {
    $stmt = $conn->prepare("SELECT COUNT(*) AS finalized_groups FROM groups WHERE semester_id=? AND finalized=1");
    $stmt->bind_param("i", $student['semester_id']);
} else {
    $stmt = $conn->prepare("SELECT COUNT(*) AS finalized_groups FROM groups WHERE semester_id=? AND (class_id=? OR class_id IS NULL) AND finalized=1");
    $stmt->bind_param("ii", $student['semester_id'], $student['class_id']);
}
$stmt->execute();
$finalized_groups = $stmt->get_result()->fetch_assoc()['finalized_groups'];

// --- Determine leader eligibility ---
$max_groups = ceil($total_students / $team_size_limit);
$can_be_leader = ($current_groups < $max_groups);

if (!$can_be_leader) {
    $message = "Maximum groups already created. Please join an existing group.";
    $alert_type = "error";
}

// --- Handle form submission ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_group']) && $can_be_leader) {
    
    // Get next group_id
    $result = $conn->query("SELECT MAX(group_id) AS max_id FROM groups");
    $row = $result->fetch_assoc();
    $next_group_id = $row['max_id'] ? $row['max_id'] + 1 : 1;
    
    // Determine class_id for group
    $group_class_id = $interclass_allowed ? NULL : $student['class_id'];
    
    // Insert group
    $stmt = $conn->prepare("INSERT INTO groups (group_id, leader_id, semester_id, class_id, finalized) VALUES (?, ?, ?, ?, 0)");
    $stmt->bind_param("iiii", $next_group_id, $student_id, $student['semester_id'], $group_class_id);
    
    if ($stmt->execute()) {
        // Get next member_id
        $result = $conn->query("SELECT MAX(member_id) AS max_id FROM group_members");
        $row = $result->fetch_assoc();
        $next_member_id = $row['max_id'] ? $row['max_id'] + 1 : 1;
        
        // Add leader as first member
        $role = 'leader';
        $stmt = $conn->prepare("INSERT INTO group_members (member_id, group_id, student_id, role) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiis", $next_member_id, $next_group_id, $student_id, $role);
        
        if ($stmt->execute()) {
            $message = "Group created successfully! You are now the leader of Group #$next_group_id.";
            $alert_type = "success";
            
            // Redirect to manage group after 2 seconds
            header("refresh:2;url=manage_group.php?group_id=$next_group_id");
        } else {
            $message = "Error adding you as leader: " . $stmt->error;
            $alert_type = "error";
        }
    } else {
        $message = "Error creating group: " . $stmt->error;
        $alert_type = "error";
    }
}

// Setup session user array for new includes
if (!isset($_SESSION['user'])) {
    $_SESSION['user'] = [
        'user_id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'] ?? 'student',
        'role' => 'student',
        'name' => $student['name']
    ];
}

$page_title = 'Create Group';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<main class="main-wrapper">
    <div class="container">
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?= $alert_type ?>">
                <i data-feather="<?= $alert_type === 'success' ? 'check-circle' : 'alert-circle' ?>"></i>
                <span><?= $message ?></span>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h1 class="card-title">Create New Group</h1>
                <p class="card-subtitle">Become a group leader and start building your team</p>
            </div>

            <div class="info-box" style="margin-bottom: 24px; padding: 16px; background: var(--bg-secondary); border-radius: 8px; border-left: 4px solid var(--primary);">
                <h3 style="margin: 0 0 12px 0; font-size: 16px;">📋 Group Creation Rules</h3>
                <ul style="margin: 0; padding-left: 20px; line-height: 1.8;">
                    <li>Maximum team size: <strong><?= $team_size_limit ?></strong> members</li>
                    <li>Interclass groups: <strong><?= $interclass_allowed ? 'Allowed' : 'Not Allowed' ?></strong></li>
                    <li>Total students in pool: <strong><?= $total_students ?></strong></li>
                    <li>Maximum groups allowed: <strong><?= $max_groups ?></strong></li>
                    <li>Current forming groups: <strong><?= $forming_groups ?></strong></li>
                    <li>Current finalized groups: <strong><?= $finalized_groups ?></strong></li>
                    <li>Total groups: <strong><?= $current_groups ?></strong></li>
                    <li>Available leader slots: <strong><?= max(0, $max_groups - $current_groups) ?></strong></li>
                </ul>
            </div>

            <?php if ($can_be_leader): ?>
                <form method="post" class="form-grid">
                    <div class="alert alert-info">
                        <i data-feather="info"></i>
                        <span>
                            By creating a group, you will become the <strong>Group Leader</strong>. 
                            You will be responsible for inviting members and finalizing the group.
                        </span>
                    </div>

                    <div class="form-actions">
                        <button type="submit" name="create_group" class="btn">
                            <i data-feather="users"></i>
                            Create Group & Become Leader
                        </button>
                        <a href="student_home.php" class="btn-secondary">
                            <i data-feather="arrow-left"></i>
                            Back to Dashboard
                        </a>
                    </div>
                </form>
            <?php else: ?>
                <div class="alert alert-error">
                    <i data-feather="x-circle"></i>
                    <span>
                        All group leader slots are full (<?= $current_groups ?>/<?= $max_groups ?>). 
                        You can browse and join existing groups instead.
                    </span>
                </div>
                <div class="form-actions">
                    <a href="browse_groups.php" class="btn">
                        <i data-feather="search"></i>
                        Browse Available Groups
                    </a>
                    <a href="student_home.php" class="btn-secondary">
                        <i data-feather="arrow-left"></i>
                        Back to Dashboard
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<script>
    window.onpageshow = function (event) {
        if (event.persisted) {
            window.location.reload();
        }
    };
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
