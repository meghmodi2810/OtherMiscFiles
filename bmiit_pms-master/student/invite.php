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

// --- Get group info ---
$stmt = $conn->prepare("
    SELECT gm.group_id, gm.role, g.finalized, g.semester_id, g.class_id
    FROM group_members gm
    JOIN groups g ON gm.group_id = g.group_id
    WHERE gm.student_id = ?
");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$my_group = $stmt->get_result()->fetch_assoc();

if (!$my_group || $my_group['role'] !== 'leader' || $my_group['finalized']) {
    header("Location: student_home.php");
    exit();
}

$group_id = $my_group['group_id'];

// --- Fetch semester config ---
$stmt = $conn->prepare("SELECT team_size, interclass_allowed FROM semester_config WHERE semester_id=?");
$stmt->bind_param("i", $my_group['semester_id']);
$stmt->execute();
$config = $stmt->get_result()->fetch_assoc();
$team_size_limit = $config['team_size'] ?? 5;
$interclass_allowed = $config['interclass_allowed'] ?? 0;

// --- Count current members ---
$stmt = $conn->prepare("SELECT COUNT(*) AS member_count FROM group_members WHERE group_id=?");
$stmt->bind_param("i", $group_id);
$stmt->execute();
$current_members = $stmt->get_result()->fetch_assoc()['member_count'];

$available_slots = $team_size_limit - $current_members;

// --- Handle Send Invitation ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_invite'])) {
    $receiver_id = intval($_POST['receiver_id']);
    
    // Check if group is full
    if ($available_slots <= 0) {
        $message = "Group is full. Cannot send more invitations.";
        $alert_type = "error";
    } else {
        // Check if student already invited or in group
        $stmt = $conn->prepare("SELECT invite_id FROM group_invites WHERE group_id=? AND receiver_id=? AND status='pending'");
        $stmt->bind_param("ii", $group_id, $receiver_id);
        $stmt->execute();
        $existing_invite = $stmt->get_result()->fetch_assoc();
        
        if ($existing_invite) {
            $message = "You already sent an invitation to this student.";
            $alert_type = "error";
        } else {
            // Check if student is already in a group
            $stmt = $conn->prepare("SELECT group_id FROM group_members WHERE student_id=?");
            $stmt->bind_param("i", $receiver_id);
            $stmt->execute();
            $student_group = $stmt->get_result()->fetch_assoc();
            
            if ($student_group) {
                $message = "This student is already in a group.";
                $alert_type = "error";
            } else {
                // Get next invite_id
                $result = $conn->query("SELECT MAX(invite_id) AS max_id FROM group_invites");
                $row = $result->fetch_assoc();
                $next_invite_id = $row['max_id'] ? $row['max_id'] + 1 : 1;
                
                // Send invitation
                $status = 'pending';
                $stmt = $conn->prepare("INSERT INTO group_invites (invite_id, group_id, sender_id, receiver_id, status) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("iiiis", $next_invite_id, $group_id, $student_id, $receiver_id, $status);
                
                if ($stmt->execute()) {
                    // Get receiver's info for email
                    $stmt = $conn->prepare("SELECT name, email FROM students WHERE student_id=?");
                    $stmt->bind_param("i", $receiver_id);
                    $stmt->execute();
                    $receiver = $stmt->get_result()->fetch_assoc();
                    
                    // Get leader's enrollment number and class info
                    $stmt = $conn->prepare("
                        SELECT u.username, c.name AS class_name 
                        FROM students s 
                        JOIN users u ON s.user_id = u.id 
                        JOIN classes c ON s.class_id = c.id 
                        WHERE s.student_id = ?
                    ");
                    $stmt->bind_param("i", $student_id);
                    $stmt->execute();
                    $leader_info = $stmt->get_result()->fetch_assoc();
                    
                    // Send a short, standardized invite without any web links
                    $messageLine = "You have been invited to join Group #{$group_id}. Leader: {$student['name']}, Enrollment No: {$leader_info['username']}, Class: {$leader_info['class_name']}.";
                    list($htmlBody, $plainBody) = buildSimpleEmailBody($receiver['name'], $messageLine);
                    // Send email and check result
                    $emailResult = sendEmail($receiver['email'], $receiver['name'], 'Group Invitation - Group #'.$group_id, $htmlBody, $plainBody);
                    
                    if ($emailResult['success']) {
                        $message = "✅ Invitation sent successfully to " . $receiver['name'] . "!";
                        $alert_type = "success";
                    } elseif (isset($emailResult['paused']) && $emailResult['paused']) {
                        $message = "✅ Invitation saved! ⚠️ " . $emailResult['message'];
                        $alert_type = "warning";
                    } elseif (isset($emailResult['limit_reached']) && $emailResult['limit_reached']) {
                        $message = "❌ Invitation not sent: " . $emailResult['message'] . " (Sent today: {$emailResult['daily_count']})";
                        $alert_type = "error";
                    } else {
                        $message = "⚠️ Invitation saved but email failed: " . $emailResult['message'];
                        $alert_type = "warning";
                    }
                } else {
                    $message = "Error sending invitation: " . $stmt->error;
                    $alert_type = "error";
                }
            }
        }
    }
}

// --- Fetch available students to invite ---
if ($interclass_allowed) {
    $stmt = $conn->prepare("
        SELECT s.student_id, s.name, s.email, c.name AS class_name, u.username AS enrollment_no
        FROM students s
        JOIN classes c ON s.class_id = c.id
        JOIN users u ON s.user_id = u.id
        WHERE c.semester_id = ?
        AND s.student_id NOT IN (SELECT student_id FROM group_members)
        AND s.student_id NOT IN (SELECT receiver_id FROM group_invites WHERE group_id=? AND status='pending')
        AND s.student_id != ?
        ORDER BY u.username
    ");
    $stmt->bind_param("iii", $my_group['semester_id'], $group_id, $student_id);
} else {
    $stmt = $conn->prepare("
        SELECT s.student_id, s.name, s.email, c.name AS class_name, u.username AS enrollment_no
        FROM students s
        JOIN classes c ON s.class_id = c.id
        JOIN users u ON s.user_id = u.id
        WHERE s.class_id = ?
        AND s.student_id NOT IN (SELECT student_id FROM group_members)
        AND s.student_id NOT IN (SELECT receiver_id FROM group_invites WHERE group_id=? AND status='pending')
        AND s.student_id != ?
        ORDER BY u.username
    ");
    $stmt->bind_param("iii", $my_group['class_id'], $group_id, $student_id);
}
$stmt->execute();
$available_students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Setup session user array
if (!isset($_SESSION['user'])) {
    $_SESSION['user'] = [
        'user_id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'] ?? 'student',
        'role' => 'student',
        'name' => $student['name']
    ];
}

$page_title = 'Invite Members';
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
                <h1 class="card-title">Invite Members to Group #<?= $group_id ?></h1>
                <p class="card-subtitle">Current Members: <?= $current_members ?>/<?= $team_size_limit ?> • Available Slots: <?= $available_slots ?></p>
            </div>

            <?php if ($available_slots <= 0): ?>
                <div class="alert alert-error">
                    <i data-feather="alert-circle"></i>
                    <span>Your group is full.</span>
                </div>
            <?php elseif (empty($available_students)): ?>
                <div class="alert alert-error">
                    <i data-feather="users"></i>
                    <span>No students available to invite.</span>
                </div>
            <?php else: ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Enrollment No</th>
                                <th>Email</th>
                                <th>Class</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($available_students as $stu): ?>
                                <tr>
                                    <td><?= $stu['name'] ?></td>
                                    <td><?= $stu['enrollment_no'] ?></td>
                                    <td><?= $stu['email'] ?></td>
                                    <td><?= $stu['class_name'] ?></td>
                                    <td>
                                        <form method="post" style="display: inline;">
                                            <input type="hidden" name="receiver_id" value="<?= $stu['student_id'] ?>">
                                            <button type="submit" name="send_invite" class="btn" style="font-size:12px; padding:6px 12px;">
                                                <i data-feather="mail" style="width:14px;height:14px"></i>
                                                Send Invite
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <div class="form-actions">
                <a href="manage_group.php?group_id=<?= $group_id ?>" class="btn-secondary">
                    <i data-feather="arrow-left"></i>
                    Back to Group
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
