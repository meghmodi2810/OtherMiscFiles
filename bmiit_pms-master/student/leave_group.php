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
$stmt = $conn->prepare("SELECT name, email FROM students WHERE student_id=?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

// --- Get group info ---
$stmt = $conn->prepare("
    SELECT gm.group_id, gm.role, g.finalized, g.leader_id, g.semester_id,
           (SELECT COUNT(*) FROM groups g2 WHERE g2.semester_id = g.semester_id AND g2.group_id <= g.group_id) AS group_number
    FROM group_members gm
    JOIN groups g ON gm.group_id = g.group_id
    WHERE gm.student_id = ?
");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$my_group = $stmt->get_result()->fetch_assoc();

if (!$my_group) {
    $_SESSION['flash_message'] = "You are not in any group.";
    $_SESSION['flash_type'] = "info";
    header("Location: student_home.php");
    exit();
}

$group_id = $my_group['group_id'];
$group_number = $my_group['group_number'];
$is_leader = ($my_group['role'] === 'leader');
$is_finalized = $my_group['finalized'];

// --- Get semester config ---
$stmt = $conn->prepare("SELECT team_size FROM semester_config WHERE semester_id=?");
$stmt->bind_param("i", $my_group['semester_id']);
$stmt->execute();
$config = $stmt->get_result()->fetch_assoc();
$min_team_size = $config['team_size'] ?? 4; // fallback to 4 if not set

// --- Leaders cannot leave, they must transfer leadership first ---
if ($is_leader) {
    $_SESSION['flash_message'] = "Leaders cannot leave the group. Transfer leadership first or dissolve the group.";
    $_SESSION['flash_type'] = "error";
    header("Location: manage_group.php");
    exit();
}

// --- Cannot leave finalized groups ---
if ($is_finalized) {
    $_SESSION['flash_message'] = "Cannot leave a finalized group. Contact admin for assistance.";
    $_SESSION['flash_type'] = "error";
    header("Location: manage_group.php");
    exit();
}

// --- Handle Leave Group ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_leave'])) {
    
    // Remove member from group
    $stmt = $conn->prepare("DELETE FROM group_members WHERE group_id=? AND student_id=?");
    $stmt->bind_param("ii", $group_id, $student_id);
    
    if ($stmt->execute()) {
        // Get leader info for notification
        $stmt = $conn->prepare("SELECT name, email FROM students WHERE student_id=?");
        $stmt->bind_param("i", $my_group['leader_id']);
        $stmt->execute();
        $leader = $stmt->get_result()->fetch_assoc();
        
        // Send standardized email to leader
        $messageLine = "{$student['name']} has left Group #{$group_number}. You can invite new members to fill the vacancy.";
        list($htmlBody, $plainBody) = buildSimpleEmailBody($leader['name'], $messageLine);
        $rLeader = sendEmail($leader['email'], $leader['name'], 'Member Left Your Group', $htmlBody, $plainBody);
        
    // Notify remaining group members about the departure
    $stmt2 = $conn->prepare("SELECT s.name, s.email FROM group_members gm JOIN students s ON gm.student_id = s.student_id WHERE gm.group_id = ?");
    $stmt2->bind_param("i", $group_id);
    $stmt2->execute();
    $remaining = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);

    $sent = 0; $paused = 0; $failed = 0;
    foreach ($remaining as $m) {
        if ($m['email'] === $leader['email'] || $m['email'] === $student['email']) continue;
        $messageLine = "{$student['name']} has left Group #{$group_number}.";
        list($htmlBody, $plainBody) = buildSimpleEmailBody($m['name'], $messageLine);
        $r = sendEmail($m['email'], $m['name'], 'Member Left - Group #' . intval($group_number), $htmlBody, $plainBody);
        if (is_array($r) && !empty($r['success'])) {
            $sent++;
        } elseif (is_array($r) && !empty($r['paused'])) {
            $paused++;
        } else {
            $failed++;
        }
    }

    $flashMsg = "You have successfully left Group #{$group_number}.";
    $emailPausedOccurred = false;
    if (is_array($rLeader) && !empty($rLeader['paused'])) {
        $emailPausedOccurred = true;
    } elseif (is_array($rLeader) && empty($rLeader['success'])) {
        $flashMsg .= " Warning: failed to notify leader by email.";
    }

    if ($failed > 0) {
        $flashMsg .= " However, $failed member notification(s) failed to send.";
    }

    if ($paused > 0 || $emailPausedOccurred) {
        $flashMsg .= " Emails were not sent as the email service is disabled right now.";
    }

        $_SESSION['flash_message'] = $flashMsg;
        $_SESSION['flash_type'] = "success";
        header("Location: student_home.php");
        exit();
    } else {
        $message = "Error leaving group: " . $stmt->error;
        $alert_type = "error";
    }
}

// --- Get group member count ---
$stmt = $conn->prepare("SELECT COUNT(*) AS member_count FROM group_members WHERE group_id=?");
$stmt->bind_param("i", $group_id);
$stmt->execute();
$member_count = $stmt->get_result()->fetch_assoc()['member_count'];

// Setup session user array for new includes
if (!isset($_SESSION['user'])) {
    $_SESSION['user'] = [
        'user_id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'] ?? 'student',
        'role' => 'student',
        'name' => $student['name']
    ];
}

$page_title = 'Leave Group';
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
                <h1 class="card-title">Leave Group #<?= $group_number ?></h1>
                <p class="card-subtitle">Are you sure you want to leave this group?</p>
            </div>

            <div class="card-body">
                <div class="alert alert-warning" style="margin-bottom: 24px;">
                    <i data-feather="alert-triangle"></i>
                    <div>
                        <strong>Warning:</strong> This action cannot be undone.
                        <ul style="margin: 12px 0 0 24px;">
                            <li>You will be removed from Group #<?= $group_number ?></li>
                            <li>The group leader will be notified</li>
                            <li>Current group size: <?= $member_count ?> members</li>
                            <?php if (($member_count - 1) < $min_team_size): ?>
                                <li style="color: #ef4444; font-weight: 600;">⚠️ Leaving will bring the group below minimum size (<?= $min_team_size ?> members) - New size: <?= $member_count - 1 ?> members</li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>

                <div style="background: var(--background); padding: 16px; border-radius: 8px; margin-bottom: 24px;">
                    <h3 style="margin-bottom: 12px; color: var(--text);">What happens next?</h3>
                    <ul style="margin: 0; padding-left: 24px; color: var(--text-light);">
                        <li>You'll return to the dashboard</li>
                        <li>You can create a new group or join another group</li>
                        <li>Your pending invites will remain active</li>
                    </ul>
                </div>

                <form method="post">
                    <div class="form-actions">
                        <a href="manage_group.php" class="btn-secondary">
                            <i data-feather="arrow-left"></i>
                            Cancel
                        </a>
                        <button type="submit" name="confirm_leave" class="btn-secondary" style="border-color:#ef4444; color:#ef4444;" onclick="(function(e,btn){ e.preventDefault(); window.confirmAsync('Are you absolutely sure you want to leave this group?','Confirm', true).then(function(ok){ if(ok) btn.form.submit(); }); })(event,this); return false;">
                            <i data-feather="log-out"></i>
                            Yes, Leave Group
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

<script>
// Cache-busting: Prevent form resubmission on back button
window.onpageshow = function(event) {
    if (event.persisted) {
        window.location.reload();
    }
};
</script>
