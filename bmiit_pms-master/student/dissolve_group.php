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
    SELECT gm.group_id, gm.role, g.finalized
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

// --- Handle Dissolve Group ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['dissolve_group'])) {
    
    // Get all group members for notification
    $stmt = $conn->prepare("
        SELECT s.student_id, s.name, s.email
        FROM group_members gm
        JOIN students s ON gm.student_id = s.student_id
        WHERE gm.group_id = ?
    ");
    $stmt->bind_param("i", $group_id);
    $stmt->execute();
    $members = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Delete all group members
        $stmt = $conn->prepare("DELETE FROM group_members WHERE group_id=?");
        $stmt->bind_param("i", $group_id);
        $stmt->execute();
        
        // Delete all pending invites
        $stmt = $conn->prepare("DELETE FROM group_invites WHERE group_id=?");
        $stmt->bind_param("i", $group_id);
        $stmt->execute();
        
        // Delete the group
        $stmt = $conn->prepare("DELETE FROM groups WHERE group_id=?");
        $stmt->bind_param("i", $group_id);
        $stmt->execute();
        
        $conn->commit();
        
        // Send email to all members (capture results)
        $sent = 0; $paused = 0; $failed = 0;
        foreach ($members as $member) {
            $messageLine = "Group #{$group_id} has been dissolved by the group leader. You are now free to join or create other groups.";
            list($htmlBody, $plainBody) = buildSimpleEmailBody($member['name'], $messageLine);
            $r = sendEmail($member['email'], $member['name'], 'Group Dissolved', $htmlBody, $plainBody);
            if (is_array($r) && !empty($r['success'])) {
                $sent++;
            } elseif (is_array($r) && !empty($r['paused'])) {
                $paused++;
            } else {
                $failed++;
            }
        }

        $message = "Group dissolved successfully.";
        if ($failed === 0 && $paused === 0) {
            $message .= " All members have been notified.";
        } else {
            if ($sent > 0) {
                $message .= " $sent notification(s) sent.";
            }
            if ($paused > 0) {
                $message .= " Emails were not sent as the email service is disabled right now.";
            }
            if ($failed > 0) {
                $message .= " However, $failed notification(s) failed to send.";
            }
        }
        $alert_type = "success";
        
        header("refresh:2;url=student_home.php");
    } catch (Exception $e) {
        $conn->rollback();
        $message = "Error dissolving group: " . $e->getMessage();
        $alert_type = "error";
    }
}

// --- Fetch all group members ---
$stmt = $conn->prepare("
    SELECT gm.student_id, s.name, s.email, c.name AS class_name, gm.role
    FROM group_members gm
    JOIN students s ON gm.student_id = s.student_id
    JOIN classes c ON s.class_id = c.id
    WHERE gm.group_id = ?
    ORDER BY gm.role DESC, s.name
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

$page_title = 'Dissolve Group';
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
                <h1 class="card-title">Dissolve Group #<?= $group_id ?></h1>
                <p class="card-subtitle" style="color: #ef4444;">Permanently delete this group</p>
            </div>

            <div class="alert alert-error" style="margin-bottom: 20px;">
                <i data-feather="alert-triangle"></i>
                <div>
                    <strong>⚠️ DANGER ZONE</strong>
                    <p style="margin: 8px 0 0 0;">
                        Dissolving this group will:
                    </p>
                    <ul style="margin: 8px 0 0 20px; line-height: 1.6;">
                        <li>Permanently delete the group</li>
                        <li>Remove all members (including you)</li>
                        <li>Cancel all pending invitations</li>
                        <li>Notify all members via email</li>
                        <li><strong>This action CANNOT be undone!</strong></li>
                    </ul>
                </div>
            </div>

            <div style="margin-bottom: 24px;">
                <h3 style="margin-bottom: 16px;">Group Members (<?= count($members) ?>)</h3>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Class</th>
                                <th>Role</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($members as $member): ?>
                                <tr>
                                    <td><?= $member['name'] ?></td>
                                    <td><?= $member['email'] ?></td>
                                    <td><?= $member['class_name'] ?></td>
                                    <td>
                                        <span class="badge badge-<?= $member['role'] === 'leader' ? 'success' : 'info' ?>">
                                            <?= ucfirst($member['role']) ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <form id="dissolveForm" method="post">
                <div class="form-actions">
                    <button type="submit" name="dissolve_group" class="btn" style="background:#ef4444; border-color:#ef4444;">
                        <i data-feather="x-circle"></i>
                        Dissolve Group Permanently
                    </button>
                    <a href="manage_group.php?group_id=<?= $group_id ?>" class="btn-secondary">
                        <i data-feather="arrow-left"></i>
                        Cancel (Go Back)
                    </a>
                </div>
            </form>
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

<script>
// Intercept the dissolve form and use the global confirm modal for consistent UI
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('dissolveForm');
    if (!form) return;
    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        const groupId = <?= json_encode($group_id) ?>;
        const memberCount = <?= json_encode(count($members)) ?>;
        const msg = `⚠️⚠️⚠️ FINAL WARNING ⚠️⚠️⚠️\n\nAre you absolutely sure you want to dissolve Group #${groupId}?\n\nAll ${memberCount} members will be removed and this action CANNOT be undone!`;
        let confirmed = false;
        if (window && typeof window.showConfirm === 'function') {
            confirmed = await window.showConfirm({ title: 'Dissolve Group', message: msg, okText: 'Yes, Dissolve', cancelText: 'Cancel', danger: true });
        } else {
            // Fallback: create a lightweight DOM modal instead of using native confirm()
            confirmed = await new Promise((resolve) => {
                const modal = document.createElement('div');
                modal.className = 'modal confirm-fallback active';
                modal.setAttribute('role', 'dialog');
                modal.setAttribute('aria-modal', 'true');
                const content = document.createElement('div');
                content.className = 'modal-content';
                content.innerHTML = `
                    <div class="modal-header">
                        <h2>Dissolve Group</h2>
                        <button class="close-modal" aria-label="Close">&times;</button>
                    </div>
                    <div class="modal-body">${msg.replace(/\n/g, '<br>')}</div>
                    <div class="modal-actions">
                        <button class="btn-small btn-cancel">Cancel</button>
                        <button class="btn-small btn-confirm danger">Yes, Dissolve</button>
                    </div>`;
                modal.appendChild(content);
                document.body.appendChild(modal);

                const btnOk = modal.querySelector('.btn-confirm');
                const btnCancel = modal.querySelector('.btn-cancel');
                const btnClose = modal.querySelector('.close-modal');

                function cleanup(result) {
                    btnOk.removeEventListener('click', onOk);
                    btnCancel.removeEventListener('click', onCancel);
                    btnClose.removeEventListener('click', onClose);
                    document.removeEventListener('keydown', onKey);
                    if (modal.parentNode) modal.parentNode.removeChild(modal);
                    resolve(result);
                }
                function onOk(e) { e && e.preventDefault(); cleanup(true); }
                function onCancel(e) { e && e.preventDefault(); cleanup(false); }
                function onClose(e) { e && e.preventDefault(); cleanup(false); }
                function onKey(e) { if (e.key === 'Escape') cleanup(false); }

                btnOk.addEventListener('click', onOk);
                btnCancel.addEventListener('click', onCancel);
                btnClose.addEventListener('click', onClose);
                document.addEventListener('keydown', onKey);
            });
        }
        if (confirmed) {
            form.submit();
        }
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
