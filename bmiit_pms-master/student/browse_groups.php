<?php
session_start();
require_once '../db.php';
require_once '../email_system/email_helper.php';

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
    SELECT s.name, s.class_id, c.semester_id
    FROM students s
    JOIN classes c ON s.class_id = c.id
    WHERE s.student_id = ?
");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

// --- Check if student is already in a group ---
$stmt = $conn->prepare("SELECT group_id FROM group_members WHERE student_id=?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$existing_group = $stmt->get_result()->fetch_assoc();

if ($existing_group) {
    header("Location: student_home.php");
    exit();
}

// --- Fetch semester config ---
$stmt = $conn->prepare("SELECT team_size, interclass_allowed FROM semester_config WHERE semester_id=?");
$stmt->bind_param("i", $student['semester_id']);
$stmt->execute();
$config = $stmt->get_result()->fetch_assoc();
$team_size_limit = $config['team_size'] ?? 5;
$interclass_allowed = $config['interclass_allowed'] ?? 0;

// --- Handle Send Join Request ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_request'])) {
    $group_id = intval($_POST['group_id']);
    
    // Get group leader
    $stmt = $conn->prepare("SELECT leader_id, finalized FROM groups WHERE group_id=?");
    $stmt->bind_param("i", $group_id);
    $stmt->execute();
    $group = $stmt->get_result()->fetch_assoc();
    
    if (!$group) {
        $message = "Group not found.";
        $alert_type = "error";
    } elseif ($group['finalized']) {
        $message = "This group is already finalized.";
        $alert_type = "error";
    } else {
        // Check if already sent request
        $stmt = $conn->prepare("SELECT invite_id FROM group_invites WHERE group_id=? AND sender_id=? AND receiver_id=? AND status='pending'");
        $stmt->bind_param("iii", $group_id, $student_id, $group['leader_id']);
        $stmt->execute();
        $existing_request = $stmt->get_result()->fetch_assoc();
        
        if ($existing_request) {
            $message = "You already sent a request to this group.";
            $alert_type = "error";
        } else {
            // Get next invite_id
            $result = $conn->query("SELECT MAX(invite_id) AS max_id FROM group_invites");
            $row = $result->fetch_assoc();
            $next_invite_id = $row['max_id'] ? $row['max_id'] + 1 : 1;
            
            // Send join request (sender = student, receiver = leader)
            $status = 'pending';
            $stmt = $conn->prepare("INSERT INTO group_invites (invite_id, group_id, sender_id, receiver_id, status) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("iiiis", $next_invite_id, $group_id, $student_id, $group['leader_id'], $status);
            
            if ($stmt->execute()) {
                // Send email to leader
                $stmt = $conn->prepare("SELECT name, email FROM students WHERE student_id=?");
                $stmt->bind_param("i", $group['leader_id']);
                $stmt->execute();
                $leader = $stmt->get_result()->fetch_assoc();
                
                // Get student's enrollment number and class info
                $stmt = $conn->prepare("
                    SELECT u.username, c.name AS class_name 
                    FROM students s 
                    JOIN users u ON s.user_id = u.id 
                    JOIN classes c ON s.class_id = c.id 
                    WHERE s.student_id = ?
                ");
                $stmt->bind_param("i", $student_id);
                $stmt->execute();
                $student_info = $stmt->get_result()->fetch_assoc();
                
                $messageLine = "A student has requested to join your Group #{$group_id}. Student: {$student['name']}, Enrollment No: {$student_info['username']}, Class: {$student_info['class_name']}.";
                list($htmlBody, $plainBody) = buildSimpleEmailBody($leader['name'], $messageLine);
                $r = sendEmail($leader['email'], $leader['name'], 'New Join Request for Your Group', $htmlBody, $plainBody);
                
                $message = "Join request saved successfully!";
                if (is_array($r) && !empty($r['success'])) {
                    $message = "Join request sent successfully!";
                    $alert_type = "success";
                } elseif (is_array($r) && !empty($r['paused'])) {
                    $message .= " Emails were not sent as the email service is disabled right now.";
                    $alert_type = "warning";
                } else {
                    $message .= " (Warning: failed to send email to leader)";
                    $alert_type = "warning";
                }
            } else {
                $message = "Error sending request: " . $stmt->error;
                $alert_type = "error";
            }
        }
    }
}

// --- Fetch available groups ---
if ($interclass_allowed) {
    // All groups in semester
    $stmt = $conn->prepare("
        SELECT g.group_id, g.leader_id, g.finalized, s.name AS leader_name, c.name AS class_name,
               (SELECT COUNT(*) FROM group_members WHERE group_id = g.group_id) AS current_members,
               (SELECT status FROM group_invites WHERE group_id = g.group_id AND sender_id = ? AND status='pending') AS request_status
        FROM groups g
        JOIN students s ON g.leader_id = s.student_id
        JOIN classes c ON s.class_id = c.id
        WHERE g.semester_id = ? AND g.finalized = 0
        ORDER BY g.group_id
    ");
    $stmt->bind_param("ii", $student_id, $student['semester_id']);
} else {
    // Only groups from same class
    $stmt = $conn->prepare("
        SELECT g.group_id, g.leader_id, g.finalized, s.name AS leader_name, c.name AS class_name,
               (SELECT COUNT(*) FROM group_members WHERE group_id = g.group_id) AS current_members,
               (SELECT status FROM group_invites WHERE group_id = g.group_id AND sender_id = ? AND status='pending') AS request_status
        FROM groups g
        JOIN students s ON g.leader_id = s.student_id
        JOIN classes c ON s.class_id = c.id
        WHERE g.class_id = ? AND g.finalized = 0
        ORDER BY g.group_id
    ");
    $stmt->bind_param("ii", $student_id, $student['class_id']);
}
$stmt->execute();
$available_groups = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Setup session user array
if (!isset($_SESSION['user'])) {
    $_SESSION['user'] = [
        'user_id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'] ?? 'student',
        'role' => 'student',
        'name' => $student['name']
    ];
}

$page_title = 'Browse Groups';
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
                <h1 class="card-title">Browse Available Groups</h1>
                <p class="card-subtitle">Find a group and request to join</p>
            </div>

            <?php if (empty($available_groups)): ?>
                <div class="alert alert-error">
                    <i data-feather="users"></i>
                    <span>No groups available. Try creating your own group!</span>
                </div>
            <?php else: ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Group ID</th>
                                <th>Leader</th>
                                <th>Class</th>
                                <th>Members</th>
                                <th>Available Slots</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($available_groups as $group): ?>
                                <?php 
                                $available_slots = $team_size_limit - $group['current_members'];
                                ?>
                                <tr>
                                    <td><strong>#<?= $group['group_id'] ?></strong></td>
                                    <td><?= $group['leader_name'] ?></td>
                                    <td><?= $group['class_name'] ?></td>
                                    <td><?= $group['current_members'] ?>/<?= $team_size_limit ?></td>
                                    <td>
                                        <?php if ($available_slots > 0): ?>
                                            <span class="badge badge-success"><?= $available_slots ?> slots</span>
                                        <?php else: ?>
                                            <span class="badge badge-error">Full</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($available_slots > 0): ?>
                                            <?php if ($group['request_status'] === 'pending'): ?>
                                                <span class="badge badge-warning" style="font-size:12px;">
                                                    <i data-feather="clock" style="width:14px;height:14px"></i>
                                                    Request Pending
                                                </span>
                                            <?php else: ?>
                                                <form method="post" style="display: inline;">
                                                    <input type="hidden" name="group_id" value="<?= $group['group_id'] ?>">
                                                    <button type="submit" name="send_request" class="btn" style="font-size:12px; padding:6px 12px;">
                                                        <i data-feather="send" style="width:14px;height:14px"></i>
                                                        Request to Join
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span style="color: var(--text-light); font-size:12px;">No slots</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <div class="form-actions">
                <a href="student_home.php" class="btn-secondary">
                    <i data-feather="arrow-left"></i>
                    Back to Dashboard
                </a>
            </div>
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
