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
    SELECT gm.group_id, gm.role, g.finalized, g.leader_id, g.semester_id,
           (SELECT COUNT(*) FROM groups g2 WHERE g2.semester_id = g.semester_id AND g2.group_id <= g.group_id) AS group_number
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
$group_number = $my_group['group_number'];

// --- Handle Transfer Leadership ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['transfer_leader'])) {
    $new_leader_id = intval($_POST['new_leader_id']);
    
    // Verify new leader is in the group
    $stmt = $conn->prepare("SELECT member_id FROM group_members WHERE group_id=? AND student_id=? AND role='member'");
    $stmt->bind_param("ii", $group_id, $new_leader_id);
    $stmt->execute();
    $new_leader = $stmt->get_result()->fetch_assoc();
    
    if (!$new_leader) {
        $message = "Selected student is not a member of this group.";
        $alert_type = "error";
    } else {
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Update old leader to member
            $stmt = $conn->prepare("UPDATE group_members SET role='member' WHERE group_id=? AND student_id=?");
            $stmt->bind_param("ii", $group_id, $student_id);
            $stmt->execute();
            
            // Update new leader role
            $stmt = $conn->prepare("UPDATE group_members SET role='leader' WHERE group_id=? AND student_id=?");
            $stmt->bind_param("ii", $group_id, $new_leader_id);
            $stmt->execute();
            
            // Update group leader_id
            $stmt = $conn->prepare("UPDATE groups SET leader_id=? WHERE group_id=?");
            $stmt->bind_param("ii", $new_leader_id, $group_id);
            $stmt->execute();
            
            $conn->commit();
            
            // Send email to new leader
            $stmt = $conn->prepare("SELECT name, email FROM students WHERE student_id=?");
            $stmt->bind_param("i", $new_leader_id);
            $stmt->execute();
            $new_leader = $stmt->get_result()->fetch_assoc();
            
            list($htmlBody, $plainBody) = buildSimpleEmailBody($new_leader['name'], "{$student['name']} has transferred leadership of Group #{$group_number} to you. You now have full leader privileges.");
            $emailResult = sendEmail($new_leader['email'], $new_leader['name'], 'You are now the Group Leader', $htmlBody, $plainBody);
            
            $message = "Leadership transferred successfully! You are now a regular member.";
            $alert_type = "success";
            // track if email service paused anywhere in this flow
            $emailPausedOccurred = false;
            if (isset($emailResult['paused']) && $emailResult['paused']) {
                $emailPausedOccurred = true;
            }
            
            header("refresh:2;url=student_home.php");
            
            // Notify all group members about new leader (excluding the new leader who already got their email)
            $stmt = $conn->prepare("SELECT s.name, s.email FROM group_members gm JOIN students s ON gm.student_id = s.student_id WHERE gm.group_id = ? AND gm.student_id != ?");
            $stmt->bind_param("ii", $group_id, $new_leader_id);
            $stmt->execute();
            $all_members = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

            $membersSent = 0;
            $membersPaused = 0;
            $membersFailed = 0;
            foreach ($all_members as $m) {
                list($htmlBody, $plainBody) = buildSimpleEmailBody($m['name'], "Leadership of Group #{$group_number} has been transferred to {$new_leader['name']}.");
                $r = sendEmail($m['email'], $m['name'], 'Leadership Transferred - Group #' . intval($group_number), $htmlBody, $plainBody);
                if (is_array($r) && !empty($r['success'])) {
                    $membersSent++;
                } elseif (is_array($r) && !empty($r['paused'])) {
                    $membersPaused++;
                } else {
                    $membersFailed++;
                }
            }

            // Append notice if some notifications were paused or failed
            if ($membersPaused > 0) {
                $emailPausedOccurred = true;
            }
            if ($membersFailed > 0) {
                $message .= " (Warning: $membersFailed member notification(s) failed to send)";
            }

            if (!empty($emailPausedOccurred)) {
                $message .= " Emails were not sent as the email service is disabled right now.";
            }
        } catch (Exception $e) {
            $conn->rollback();
            $message = "Error transferring leadership: " . $e->getMessage();
            $alert_type = "error";
        }
    }
}

// --- Fetch group members (excluding current leader) ---
$stmt = $conn->prepare("
    SELECT gm.student_id, s.name, s.email, c.name AS class_name
    FROM group_members gm
    JOIN students s ON gm.student_id = s.student_id
    JOIN classes c ON s.class_id = c.id
    WHERE gm.group_id = ? AND gm.role = 'member'
    ORDER BY s.name
");
$stmt->bind_param("i", $group_id);
$stmt->execute();
$members = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Setup session user array
if (!isset($_SESSION['user'])) {
    $_SESSION['user'] = [
        'user_id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'] ?? 'student',
        'role' => 'student',
        'name' => $student['name']
    ];
}

$page_title = 'Transfer Leadership';
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
                <h1 class="card-title">Transfer Leadership - Group #<?= $group_number ?></h1>
                <p class="card-subtitle">Choose a new leader from your group members</p>
            </div>

            <div class="alert alert-warning" style="margin-bottom: 24px;">
                <i data-feather="alert-triangle"></i>
                <span>
                    <strong>Warning:</strong> Once you transfer leadership, you will become a regular member and will no longer have leader privileges.
                    This action cannot be undone!
                </span>
            </div>

            <?php if (empty($members)): ?>
                <div class="alert alert-error">
                    <i data-feather="users"></i>
                    <span>You need at least one other member in your group to transfer leadership.</span>
                </div>
            <?php else: ?>
                <form method="post" onsubmit="(function(e){ e.preventDefault(); window.confirmAsync('⚠️ Are you sure you want to transfer leadership? This action cannot be undone!','Confirm', true).then(function(ok){ if(ok) e.target.submit(); }); })(event); return false;">
                    <div class="form-group">
                        <label for="new_leader_id">Select New Leader</label>
                        <select name="new_leader_id" id="new_leader_id" required>
                            <option value="">-- Choose a member --</option>
                            <?php foreach ($members as $member): ?>
                                <option value="<?= $member['student_id'] ?>">
                                    <?= $member['name'] ?> (<?= $member['class_name'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-actions">
                        <button type="submit" name="transfer_leader" class="btn-accent">
                            <i data-feather="repeat"></i>
                            Transfer Leadership
                        </button>
                        <a href="manage_group.php?group_id=<?= $group_id ?>" class="btn-secondary">
                            <i data-feather="arrow-left"></i>
                            Cancel
                        </a>
                    </div>
                </form>
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
