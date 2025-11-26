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

if (!$my_group) {
    header("Location: student_home.php");
    exit();
}

$group_id = $my_group['group_id'];
$group_number = $my_group['group_number'];
$is_leader = ($my_group['role'] === 'leader');
$is_finalized = $my_group['finalized'];

// --- Fetch semester config ---
$stmt = $conn->prepare("SELECT team_size FROM semester_config WHERE semester_id=?");
$stmt->bind_param("i", $my_group['semester_id']);
$stmt->execute();
$config = $stmt->get_result()->fetch_assoc();
$team_size_limit = $config['team_size'] ?? 5;

// --- Handle Remove Member ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_member']) && $is_leader && !$is_finalized) {
    $member_to_remove = intval($_POST['member_id']);
    
    // Cannot remove leader
    if ($member_to_remove === $student_id) {
        $message = "You cannot remove yourself as leader. Transfer leadership or dissolve the group.";
        $alert_type = "error";
    } else {
        // Remove member
        $stmt = $conn->prepare("DELETE FROM group_members WHERE group_id=? AND student_id=?");
        $stmt->bind_param("ii", $group_id, $member_to_remove);
        
        if ($stmt->execute()) {
            // Get removed member's info for notification
            $stmt = $conn->prepare("SELECT name, email FROM students WHERE student_id=?");
            $stmt->bind_param("i", $member_to_remove);
            $stmt->execute();
            $removed_member = $stmt->get_result()->fetch_assoc();
            
            // Send email notification (standardized body)
            $messageLine = "You have been removed from Group #{$group_number} by the group leader. You can now join or create other groups.";
            list($htmlBody, $plainBody) = buildSimpleEmailBody($removed_member['name'], $messageLine);
            $rRemoved = sendEmail($removed_member['email'], $removed_member['name'], 'Removed from Group', $htmlBody, $plainBody);

            // Notify remaining group members about the removal
            $stmt2 = $conn->prepare("SELECT s.name, s.email FROM group_members gm JOIN students s ON gm.student_id = s.student_id WHERE gm.group_id = ?");
            $stmt2->bind_param("i", $group_id);
            $stmt2->execute();
            $remaining = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);

            $sent = 0; $paused = 0; $failed = 0;
            foreach ($remaining as $m) {
                if ($m['email'] === $removed_member['email']) continue;
                $messageLine = "{$removed_member['name']} has been removed from Group #{$group_number} by the group leader.";
                list($htmlBody, $plainBody) = buildSimpleEmailBody($m['name'], $messageLine);
                $r = sendEmail($m['email'], $m['name'], 'Member Removed - Group #' . intval($group_number), $htmlBody, $plainBody);
                if (is_array($r) && !empty($r['success'])) {
                    $sent++;
                } elseif (is_array($r) && !empty($r['paused'])) {
                    $paused++;
                } else {
                    $failed++;
                }
            }

            // Determine removed member email result
            $removedNote = '';
            if (is_array($rRemoved) && !empty($rRemoved['paused'])) {
                $removedNote = '';
            } elseif (is_array($rRemoved) && empty($rRemoved['success'])) {
                $removedNote = ' (Warning: failed to notify removed member by email)';
            }

            $message = "Member removed successfully." . $removedNote;
            if ($paused > 0) {
                $message .= " Emails were not sent as the email service is disabled right now.";
            }
            if ($failed > 0) {
                $message .= " However, $failed member notification(s) failed to send.";
            }
            $alert_type = "success";
        } else {
            $message = "Error removing member: " . $stmt->error;
            $alert_type = "error";
        }
    }
}

// --- Handle Finalize Group ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['finalize_group']) && $is_leader && !$is_finalized) {
    
    // Count current members
    $stmt = $conn->prepare("SELECT COUNT(*) AS member_count FROM group_members WHERE group_id=?");
    $stmt->bind_param("i", $group_id);
    $stmt->execute();
    $member_count = $stmt->get_result()->fetch_assoc()['member_count'];
    
    // Check pending invites sent BY this group (leader inviting students)
    $stmt = $conn->prepare("SELECT COUNT(*) AS pending_sent FROM group_invites WHERE group_id=? AND sender_id=? AND status='pending'");
    $stmt->bind_param("ii", $group_id, $student_id);
    $stmt->execute();
    $pending_sent = $stmt->get_result()->fetch_assoc()['pending_sent'];
    
    // Check pending join requests received BY this group (students requesting to join)
    $stmt = $conn->prepare("SELECT COUNT(*) AS pending_received FROM group_invites WHERE receiver_id=? AND status='pending'");
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $pending_received = $stmt->get_result()->fetch_assoc()['pending_received'];
    
    $total_pending = $pending_sent + $pending_received;
    
    // Validation with detailed feedback
    $validation_errors = [];
    
    // Use team_size_limit as the max (which comes from semester_config)
    // Minimum should be at least 3 for a functional team
    $min_group_size = 3; // Standard minimum for project teams
    
    if ($member_count < $min_group_size) {
        $validation_errors[] = "⚠️ Minimum $min_group_size members required (Current: $member_count)";
    }
    
    if ($member_count > $team_size_limit) {
        $validation_errors[] = "⚠️ Exceeds maximum size of $team_size_limit (Current: $member_count)";
    }
    
    if ($pending_sent > 0) {
        $validation_errors[] = "⚠️ $pending_sent pending invitation(s) sent. Wait for responses or cancel them.";
    }
    
    if ($pending_received > 0) {
        $validation_errors[] = "⚠️ $pending_received pending join request(s). Accept or reject them first.";
    }
    
    if (!empty($validation_errors)) {
        $message = "<strong>Cannot finalize group:</strong><br>" . implode("<br>", $validation_errors);
        $alert_type = "error";
    } else {
        // Finalize the group
        $stmt = $conn->prepare("UPDATE groups SET finalized=1 WHERE group_id=?");
        $stmt->bind_param("i", $group_id);
        
        if ($stmt->execute()) {
            $message = "🎉 Group finalized successfully! You can now start working on your project.";
            $alert_type = "success";
            $is_finalized = 1;
            
            // Send email notification to all current members about finalization (capture results)
            $stmt = $conn->prepare("SELECT s.name, s.email FROM group_members gm JOIN students s ON gm.student_id = s.student_id WHERE gm.group_id = ?");
            $stmt->bind_param("i", $group_id);
            $stmt->execute();
            $all_members = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $sentFinal = 0; $pausedFinal = 0; $failedFinal = 0;
                foreach ($all_members as $m) {
                $messageLine = "Your group (Group #{$group_number}) has been finalized by the leader. You can now start working on your project submissions and milestones.";
                list($htmlBody, $plainBody) = buildSimpleEmailBody($m['name'], $messageLine);
                $r = sendEmail($m['email'], $m['name'], 'Group Finalized - Group #' . intval($group_number), $htmlBody, $plainBody);
                if (is_array($r) && !empty($r['success'])) {
                    $sentFinal++;
                } elseif (is_array($r) && !empty($r['paused'])) {
                    $pausedFinal++;
                } else {
                    $failedFinal++;
                }
            }
            if ($pausedFinal > 0) {
                $message .= " Emails were not sent as the email service is disabled right now.";
            }
            if ($failedFinal > 0) {
                $message .= " (Warning: $failedFinal notification(s) failed to send)";
            }
        } else {
            $message = "Error finalizing group: " . $stmt->error;
            $alert_type = "error";
        }
    }
}

// --- Handle Cancel Invitation (leader only) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_invite']) && $is_leader && !$is_finalized) {
    $invite_id = intval($_POST['invite_id']);
    // Verify invite belongs to this group and was sent by this leader and is pending
    $stmt = $conn->prepare("SELECT receiver_id FROM group_invites WHERE invite_id=? AND group_id=? AND sender_id=? AND status='pending'");
    $stmt->bind_param("iii", $invite_id, $group_id, $student_id);
    $stmt->execute();
    $inv = $stmt->get_result()->fetch_assoc();
    if ($inv) {
        // Fetch receiver info so we can notify them after cancellation
        $stmt2 = $conn->prepare("SELECT name, email FROM students WHERE student_id=?");
        $stmt2->bind_param("i", $inv['receiver_id']);
        $stmt2->execute();
        $receiver = $stmt2->get_result()->fetch_assoc();

        $stmt = $conn->prepare("DELETE FROM group_invites WHERE invite_id=?");
        $stmt->bind_param("i", $invite_id);
        if ($stmt->execute()) {
            $message = "Invitation cancelled successfully.";
            $alert_type = "success";

            // Send short standardized cancellation email to the invitee
            if (!empty($receiver) && !empty($receiver['email'])) {
                $messageLine = "Your invitation to join Group #{$group_number} has been cancelled by the group leader.";
                list($htmlBody, $plainBody) = buildSimpleEmailBody($receiver['name'], $messageLine);
                $r = sendEmail($receiver['email'], $receiver['name'], 'Invitation Cancelled - Group #' . intval($group_number), $htmlBody, $plainBody);

                $paused = is_array($r) && !empty($r['paused']);
                $failed = !(is_array($r) && !empty($r['success']));
                if ($paused) {
                    $message .= " Emails were not sent as the email service is disabled right now.";
                }
                if ($failed && !$paused) {
                    // Only append this when actual failure occurred (and not already covered by paused)
                    $message .= " Some notifications also failed to send.";
                }
            }
        } else {
            $message = "Error cancelling invitation: " . $stmt->error;
            $alert_type = "error";
        }
    } else {
        $message = "Invite not found or cannot be cancelled.";
        $alert_type = "error";
    }
}

// --- Fetch all group members ---
$stmt = $conn->prepare("
    SELECT gm.member_id, gm.student_id, gm.role, gm.joined_at, s.name, s.email, c.name AS class_name, u.username AS enrollment_no
    FROM group_members gm
    JOIN students s ON gm.student_id = s.student_id
    JOIN classes c ON s.class_id = c.id
    JOIN users u ON s.user_id = u.id
    WHERE gm.group_id = ?
    ORDER BY gm.role DESC, gm.joined_at ASC
");
$stmt->bind_param("i", $group_id);
$stmt->execute();
$members = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// --- Fetch pending invites sent by this group ---
if ($is_leader) {
    $stmt = $conn->prepare("
        SELECT gi.invite_id, gi.receiver_id, gi.status, gi.sent_at, s.name, s.email, u.username AS enrollment_no
        FROM group_invites gi
        JOIN students s ON gi.receiver_id = s.student_id
        JOIN users u ON s.user_id = u.id
        WHERE gi.group_id = ? AND gi.status = 'pending'
        ORDER BY gi.sent_at DESC
    ");
    $stmt->bind_param("i", $group_id);
    $stmt->execute();
    $pending_invites = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} else {
    $pending_invites = [];
}

// --- Fetch join requests (students requesting to join) ---
if ($is_leader) {
    $stmt = $conn->prepare("
        SELECT gi.invite_id, gi.sender_id, gi.status, gi.sent_at, s.name, s.email, c.name AS class_name, u.username AS enrollment_no
        FROM group_invites gi
        JOIN students s ON gi.sender_id = s.student_id
        JOIN classes c ON s.class_id = c.id
        JOIN users u ON s.user_id = u.id
        WHERE gi.group_id = ? AND gi.receiver_id = ? AND gi.status = 'pending'
        ORDER BY gi.sent_at DESC
    ");
    $stmt->bind_param("ii", $group_id, $my_group['leader_id']);
    $stmt->execute();
    $join_requests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} else {
    $join_requests = [];
}

// --- Compute current state used for UI (finalize eligibility) ---
$member_count = count($members);
$pending_sent = count($pending_invites);
$pending_received = count($join_requests);
$min_group_size = 3; // same policy as server-side
$finalize_reasons = [];
if ($member_count < $min_group_size) {
    $finalize_reasons[] = "Minimum $min_group_size members required (Current: $member_count)";
}
if ($member_count > $team_size_limit) {
    $finalize_reasons[] = "Exceeds maximum size of $team_size_limit (Current: $member_count)";
}
if ($pending_sent > 0) {
    $finalize_reasons[] = "$pending_sent pending invitation(s) sent. Wait for responses or cancel them.";
}
if ($pending_received > 0) {
    $finalize_reasons[] = "$pending_received pending join request(s). Accept or reject them first.";
}
$can_finalize = empty($finalize_reasons);

// Setup session user array for new includes
if (!isset($_SESSION['user'])) {
    $_SESSION['user'] = [
        'user_id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'] ?? 'student',
        'role' => 'student',
        'name' => $student['name']
    ];
}

$page_title = 'Manage Group';
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
                <h1 class="card-title">Group #<?= $group_number ?></h1>
                <p class="card-subtitle">
                    <span class="badge badge-<?= $is_leader ? 'success' : 'info' ?>"><?= ucfirst($my_group['role']) ?></span>
                    <span class="badge badge-<?= $is_finalized ? 'success' : 'warning' ?>" style="margin-left:8px">
                        <?= $is_finalized ? 'Finalized' : 'Forming' ?>
                    </span>
                </p>
            </div>

            <!-- Group Members -->
            <div style="margin-bottom: 24px;">
                <h3 style="margin-bottom: 16px; display: flex; align-items: center; gap: 8px;">
                    <i data-feather="users"></i>
                    Group Members (<?= count($members) ?>/<?= $team_size_limit ?>)
                </h3>
                
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Enrollment No</th>
                                <th>Email</th>
                                <th>Class</th>
                                <th>Role</th>
                                <th>Joined</th>
                                <?php if (!$is_finalized): ?>
                                    <th>Actions</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($members as $member): ?>
                                <tr>
                                    <td><?= $member['name'] ?></td>
                                    <td><?= $member['enrollment_no'] ?></td>
                                    <td><?= $member['email'] ?></td>
                                    <td><?= $member['class_name'] ?></td>
                                    <td>
                                        <span class="badge badge-<?= $member['role'] === 'leader' ? 'success' : 'info' ?>">
                                            <?= ucfirst($member['role']) ?>
                                        </span>
                                    </td>
                                    <td><?= date('M d, Y', strtotime($member['joined_at'])) ?></td>
                                    <?php if (!$is_finalized): ?>
                                        <td>
                                                    <?php if ($is_leader && $member['role'] !== 'leader'): ?>
                                                        <!-- Leader can remove members -->
                                                        <form method="post" style="display: inline;" onsubmit="(function(e){ e.preventDefault(); window.confirmAsync('Remove this member from the group?','Confirm').then(function(ok){ if(ok) e.target.submit(); }); })(event); return false;">
                                                            <input type="hidden" name="member_id" value="<?= $member['student_id'] ?>">
                                                            <button type="submit" name="remove_member" class="btn-secondary" style="font-size:12px; padding:4px 8px; border-color:#ef4444; color:#ef4444;">
                                                                <i data-feather="x" style="width:14px;height:14px"></i>
                                                                Remove
                                                            </button>
                                                        </form>
                                                    <?php elseif (!$is_leader && $member['student_id'] == $student_id): ?>
                                                        <!-- Member can leave (see themselves) -->
                                                        <a href="/bmiit_pms/student/leave_group.php" class="btn-secondary" style="font-size:12px; padding:4px 8px; border-color:#ef4444; color:#ef4444; text-decoration:none; display:inline-flex; align-items:center; gap:4px;">
                                                            <i data-feather="log-out" style="width:14px;height:14px"></i>
                                                            Leave
                                                        </a>
                                                    <?php else: ?>
                                                        <span style="color: var(--text-light); font-size: 12px;">-</span>
                                                    <?php endif; ?>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pending Invites (Leader Only) -->
            <?php if ($is_leader && !empty($pending_invites)): ?>
                <div style="margin-bottom: 24px;">
                        <button id="pendingInvitesHeader" aria-controls="pendingInvitesSection" aria-expanded="false" style="margin-bottom: 8px; display: flex; align-items: center; gap: 8px; cursor: pointer; background: none; border: none; padding:0; font: inherit; color: inherit; text-align: left;">
                            <i data-feather="mail"></i>
                            <span>Pending Invitations (<?= count($pending_invites) ?>)</span>
                            <span style="margin-left:8px; color:var(--text-muted); font-size:12px">(click to show)</span>
                        </button>

                    <div id="pendingInvitesSection" class="table-container" style="display:none;">
                        <table>
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Enrollment No</th>
                                    <th>Email</th>
                                    <th>Sent</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pending_invites as $invite): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($invite['name']) ?></td>
                                        <td><?= htmlspecialchars($invite['enrollment_no']) ?></td>
                                        <td><?= htmlspecialchars($invite['email']) ?></td>
                                        <td><?= date('M d, Y H:i', strtotime($invite['sent_at'])) ?></td>
                                        <td><span class="badge badge-warning">Pending</span></td>
                                        <td>
                                            <form method="post" style="display:inline;" onsubmit="(function(e,btn){ e.preventDefault(); window.confirmAsync('Cancel this invitation?','Confirm').then(function(ok){ if(ok) btn.form.submit(); }); })(event,this); return false;">
                                                <input type="hidden" name="invite_id" value="<?= intval($invite['invite_id']) ?>">
                                                <button type="submit" name="cancel_invite" class="btn-secondary" style="font-size:12px; padding:4px 8px;">
                                                    <i data-feather="x" style="width:14px;height:14px"></i>
                                                    Cancel
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Join Requests (Leader Only) -->
            <?php if ($is_leader && !empty($join_requests)): ?>
                <div style="margin-bottom: 24px;">
                    <h3 style="margin-bottom: 16px; display: flex; align-items: center; gap: 8px;">
                        <i data-feather="user-plus"></i>
                        Join Requests (<?= count($join_requests) ?>)
                    </h3>
                    
                    <div class="alert alert-info">
                        <i data-feather="info"></i>
                        <span>Students are requesting to join your group. Review them in the <a href="accept_invite.php" style="text-decoration:underline">Manage Invites</a> page.</span>
                    </div>
                    
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Enrollment No</th>
                                    <th>Email</th>
                                    <th>Class</th>
                                    <th>Requested</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($join_requests as $request): ?>
                                    <tr>
                                        <td><?= $request['name'] ?></td>
                                        <td><?= $request['enrollment_no'] ?></td>
                                        <td><?= $request['email'] ?></td>
                                        <td><?= $request['class_name'] ?></td>
                                        <td><?= date('M d, Y H:i', strtotime($request['sent_at'])) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Actions -->
            <div class="form-actions">
                <?php if ($is_leader && !$is_finalized): ?>
                    <a href="invite.php?group_id=<?= $group_id ?>" class="btn">
                        <i data-feather="user-plus"></i>
                        Invite Members
                    </a>
                    
                    <?php if ($can_finalize): ?>
                        <form id="finalizeForm" method="post" style="display: inline;">
                            <button type="submit" name="finalize_group" class="btn-accent">
                                <i data-feather="lock"></i>
                                Finalize Group
                            </button>
                        </form>
                    <?php else: ?>
                        <form id="finalizeForm" method="post" style="display: inline;">
                            <button type="button" class="btn-accent" id="finalizeBtn">
                                <i data-feather="lock"></i>
                                Finalize Group
                            </button>
                        </form>
                    <?php endif; ?>
                    
                    <a href="transfer_leader.php?group_id=<?= $group_id ?>" class="btn-secondary">
                        <i data-feather="repeat"></i>
                        Transfer Leadership
                    </a>
                    
                    <a href="dissolve_group.php?group_id=<?= $group_id ?>" class="btn-secondary" style="border-color:#ef4444; color:#ef4444;">
                        <i data-feather="x-circle"></i>
                        Dissolve Group
                    </a>
                <?php endif; ?>
                
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
    
    // Handle finalize form submission
    document.addEventListener('DOMContentLoaded', function(){
        var finalizeForm = document.getElementById('finalizeForm');
        var finalizeBtn = document.getElementById('finalizeBtn');
        
        // If button is disabled, show reasons
        if (finalizeBtn) {
            finalizeBtn.addEventListener('click', function(e){
                e.preventDefault();
                var reasons = <?= json_encode($finalize_reasons) ?>;
                var msg = "Cannot finalize group yet:\n\n" + reasons.join("\n");
                if (window && typeof window.showAlert === 'function') {
                    window.showAlert(msg.replace(/\n/g, '<br>'), 'warning');
                } else {
                    alert(msg);
                }
            });
        }
        
        // If form exists and can finalize, add confirmation
        if (finalizeForm && !finalizeBtn) {
            finalizeForm.addEventListener('submit', async function(e){
                e.preventDefault();
                var groupNumber = <?= json_encode($group_number) ?>;
                var memberCount = <?= json_encode($member_count) ?>;
                var msg = "Are you ready to finalize Group #" + groupNumber + "?\n\n" +
                          "This will:\n" +
                          "• Lock the group membership (" + memberCount + " members)\n" +
                          "• Prevent adding/removing members\n" +
                          "• Enable project submissions\n" +
                          "• Notify all members\n\n" +
                          "This action cannot be undone!";
                
                var confirmed = false;
                if (window && typeof window.showConfirm === 'function') {
                    confirmed = await window.showConfirm({
                        title: 'Finalize Group',
                        message: msg,
                        okText: 'Yes, Finalize',
                        cancelText: 'Cancel'
                    });
                } else {
                    confirmed = confirm(msg);
                }
                
                if (confirmed) {
                    finalizeForm.submit();
                }
            });
        }
        
        // Simple toggle for pending invites section
        try {
            var header = document.getElementById('pendingInvitesHeader');
            var section = document.getElementById('pendingInvitesSection');
            if (header && section) {
                header.addEventListener('click', function(){
                    var isOpen = header.getAttribute('aria-expanded') === 'true';
                    if (!isOpen) {
                        section.style.display = '';
                        header.setAttribute('aria-expanded', 'true');
                    } else {
                        section.style.display = 'none';
                        header.setAttribute('aria-expanded', 'false');
                    }
                });
                // allow Enter/Space to toggle when header has focus
                header.addEventListener('keydown', function(e){
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        header.click();
                    }
                });
            }
        } catch (e) { /* ignore */ }
    });
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
