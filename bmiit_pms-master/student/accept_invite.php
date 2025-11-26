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

// --- Handle Accept Invite (Student accepting leader's invitation) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accept_invite'])) {
    $invite_id = intval($_POST['invite_id']);
    
    // Get invite details
    $stmt = $conn->prepare("SELECT group_id, sender_id, receiver_id FROM group_invites WHERE invite_id=? AND receiver_id=? AND status='pending'");
    $stmt->bind_param("ii", $invite_id, $student_id);
    $stmt->execute();
    $invite = $stmt->get_result()->fetch_assoc();
    
    if ($invite) {
        // Check if student is already in a group
        $stmt = $conn->prepare("SELECT group_id FROM group_members WHERE student_id=?");
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        $existing_group = $stmt->get_result()->fetch_assoc();
        
        if ($existing_group) {
            $message = "You are already in a group!";
            $alert_type = "error";
        } else {
            // Update invite status to accepted
            $stmt = $conn->prepare("UPDATE group_invites SET status='accepted' WHERE invite_id=?");
            $stmt->bind_param("i", $invite_id);
            $stmt->execute();
            
            // Get next member_id
            $result = $conn->query("SELECT MAX(member_id) AS max_id FROM group_members");
            $row = $result->fetch_assoc();
            $next_member_id = $row['max_id'] ? $row['max_id'] + 1 : 1;
            
            // Add student to group
            $role = 'member';
            $stmt = $conn->prepare("INSERT INTO group_members (member_id, group_id, student_id, role) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiis", $next_member_id, $invite['group_id'], $student_id, $role);
            
            if ($stmt->execute()) {
                // Notify all existing group members (except the student who just joined)
                $stmt = $conn->prepare(
                    "SELECT s.student_id, s.name, s.email FROM group_members gm JOIN students s ON gm.student_id = s.student_id WHERE gm.group_id = ?"
                );
                $stmt->bind_param("i", $invite['group_id']);
                $stmt->execute();
                $group_members = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

                $sent = 0; $paused = 0; $failed = 0;
                foreach ($group_members as $m) {
                    // Skip notifying the student who just joined
                    if (intval($m['student_id']) === intval($student_id)) continue;

                    $messageLine = "{$student['name']} has accepted an invitation and joined Group #{$invite['group_id']}.";
                    list($htmlBody, $plainBody) = buildSimpleEmailBody($m['name'], $messageLine);
                    $r = sendEmail($m['email'], $m['name'], 'Member Joined - Group #' . intval($invite['group_id']), $htmlBody, $plainBody);
                    if (is_array($r) && !empty($r['success'])) {
                        $sent++;
                    } elseif (is_array($r) && !empty($r['paused'])) {
                        $paused++;
                    } else {
                        $failed++;
                    }
                }

                $message = "You have joined Group #" . $invite['group_id'] . "!";
                if ($paused > 0) {
                    $message .= " Emails were not sent as the email service is disabled right now.";
                }
                if ($failed > 0) {
                    $message .= " (Warning: $failed member notification(s) failed to send)";
                }
                $alert_type = "success";
                header("refresh:2;url=student_home.php");
            } else {
                $message = "Error joining group: " . $stmt->error;
                $alert_type = "error";
            }
        }
    } else {
        $message = "Invalid invitation.";
        $alert_type = "error";
    }
}

// --- Handle Reject Invite (Student rejecting leader's invitation) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reject_invite'])) {
    $invite_id = intval($_POST['invite_id']);
    
    // First fetch invite to get sender (leader) so we can notify them
    $q = $conn->prepare("SELECT sender_id, group_id FROM group_invites WHERE invite_id=? AND receiver_id=? AND status='pending'");
    $q->bind_param("ii", $invite_id, $student_id);
    $q->execute();
    $inv = $q->get_result()->fetch_assoc();

    $stmt = $conn->prepare("UPDATE group_invites SET status='rejected' WHERE invite_id=? AND receiver_id=?");
    $stmt->bind_param("ii", $invite_id, $student_id);
    
    if ($stmt->execute()) {
        $message = "Invitation rejected.";
        $alert_type = "success";

        // Notify the leader that their invitation was rejected (if invite existed)
        if ($inv && !empty($inv['sender_id'])) {
            $ld = $conn->prepare("SELECT name, email FROM students WHERE student_id=?");
            $ld->bind_param("i", $inv['sender_id']);
            $ld->execute();
            $leader = $ld->get_result()->fetch_assoc();

            if ($leader) {
                $messageLine = "{$student['name']} has rejected your invitation to join Group #" . intval($inv['group_id']) . ".";
                list($htmlBody, $plainBody) = buildSimpleEmailBody($leader['name'], $messageLine);
                $rLeader = sendEmail($leader['email'], $leader['name'], 'Invitation Rejected - Group #' . intval($inv['group_id']), $htmlBody, $plainBody);
                if (is_array($rLeader) && !empty($rLeader['paused'])) {
                    $message .= " Emails were not sent as the email service is disabled right now.";
                }
            }
        }

    } else {
        $message = "Error: " . $stmt->error;
        $alert_type = "error";
    }
}

// --- Handle Accept Join Request (Leader accepting student's request) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accept_request'])) {
    $invite_id = intval($_POST['invite_id']);
    
    // Get request details (sender is the student, receiver is the leader)
    $stmt = $conn->prepare("SELECT group_id, sender_id, receiver_id FROM group_invites WHERE invite_id=? AND receiver_id=? AND status='pending'");
    $stmt->bind_param("ii", $invite_id, $student_id);
    $stmt->execute();
    $request = $stmt->get_result()->fetch_assoc();
    
    if ($request) {
        // Check if student is already in a group
        $stmt = $conn->prepare("SELECT group_id FROM group_members WHERE student_id=?");
        $stmt->bind_param("i", $request['sender_id']);
        $stmt->execute();
        $existing_group = $stmt->get_result()->fetch_assoc();
        
        if ($existing_group) {
            $message = "This student is already in another group!";
            $alert_type = "error";
        } else {
            // Update request status
            $stmt = $conn->prepare("UPDATE group_invites SET status='accepted' WHERE invite_id=?");
            $stmt->bind_param("i", $invite_id);
            $stmt->execute();
            
            // Get next member_id
            $result = $conn->query("SELECT MAX(member_id) AS max_id FROM group_members");
            $row = $result->fetch_assoc();
            $next_member_id = $row['max_id'] ? $row['max_id'] + 1 : 1;
            
            // Add student to group
            $role = 'member';
            $stmt = $conn->prepare("INSERT INTO group_members (member_id, group_id, student_id, role) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiis", $next_member_id, $request['group_id'], $request['sender_id'], $role);
            
            if ($stmt->execute()) {
                $message = "Student added to your group!";
                $alert_type = "success";

                // Notify the student that their request was accepted
                $sd = $conn->prepare("SELECT name, email FROM students WHERE student_id=?");
                $sd->bind_param("i", $request['sender_id']);
                $sd->execute();
                $student_rec = $sd->get_result()->fetch_assoc();

                if ($student_rec) {
                    $messageLine = "Your request to join Group #{$request['group_id']} has been accepted by the group leader. You are now a member of the group.";
                    list($htmlBody, $plainBody) = buildSimpleEmailBody($student_rec['name'], $messageLine);
                    $rAccepted = sendEmail($student_rec['email'], $student_rec['name'], 'Join Request Accepted - Group #' . intval($request['group_id']), $htmlBody, $plainBody);
                    if (is_array($rAccepted) && !empty($rAccepted['paused'])) {
                        $message .= " Emails were not sent as the email service is disabled right now.";
                    }
                }

                // Notify all existing group members (except the newly added student)
                $stmt2 = $conn->prepare(
                    "SELECT s.student_id, s.name, s.email FROM group_members gm JOIN students s ON gm.student_id = s.student_id WHERE gm.group_id = ?"
                );
                $stmt2->bind_param("i", $request['group_id']);
                $stmt2->execute();
                $group_members = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);

                    $sent = 0; $paused = 0; $failed = 0;
                    foreach ($group_members as $m) {
                    if (intval($m['student_id']) === intval($request['sender_id'])) continue;
                    $messageLine = "{$student_rec['name']} has joined Group #{$request['group_id']}.";
                    list($htmlBody, $plainBody) = buildSimpleEmailBody($m['name'], $messageLine);
                    $r = sendEmail($m['email'], $m['name'], 'Member Joined - Group #' . intval($request['group_id']), $htmlBody, $plainBody);
                    if (is_array($r) && !empty($r['success'])) {
                        $sent++;
                    } elseif (is_array($r) && !empty($r['paused'])) {
                        $paused++;
                    } else {
                        $failed++;
                    }
                }
                if ($paused > 0) {
                    $message .= " Emails were not sent as the email service is disabled right now.";
                }
                if ($failed > 0) {
                    $message .= " (Warning: $failed member notification(s) failed to send)";
                }
            } else {
                $message = "Error adding student: " . $stmt->error;
                $alert_type = "error";
            }
        }
    } else {
        $message = "Invalid request.";
        $alert_type = "error";
    }
}

// --- Handle Reject Join Request (Leader rejecting student's request) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reject_request'])) {
    $invite_id = intval($_POST['invite_id']);
    
    // Fetch request to get sender info for notification
    $q = $conn->prepare("SELECT sender_id, group_id FROM group_invites WHERE invite_id=? AND receiver_id=? AND status='pending'");
    $q->bind_param("ii", $invite_id, $student_id);
    $q->execute();
    $req = $q->get_result()->fetch_assoc();

    $stmt = $conn->prepare("UPDATE group_invites SET status='rejected' WHERE invite_id=? AND receiver_id=?");
    $stmt->bind_param("ii", $invite_id, $student_id);

    if ($stmt->execute()) {
        $message = "Request rejected.";
        $alert_type = "success";

        // Notify the requesting student that their request was rejected
        if ($req && !empty($req['sender_id'])) {
            $sd = $conn->prepare("SELECT name, email FROM students WHERE student_id=?");
            $sd->bind_param("i", $req['sender_id']);
            $sd->execute();
            $requester = $sd->get_result()->fetch_assoc();

                if ($requester) {
                $messageLine = "Your request to join Group #" . intval($req['group_id']) . " has been rejected by the group leader.";
                list($htmlBody, $plainBody) = buildSimpleEmailBody($requester['name'], $messageLine);
                $rReq = sendEmail($requester['email'], $requester['name'], 'Join Request Rejected - Group #' . intval($req['group_id']), $htmlBody, $plainBody);
                if (is_array($rReq) && !empty($rReq['paused'])) {
                    $message .= " Emails were not sent as the email service is disabled right now.";
                }
            }
        }
    } else {
        $message = "Error: " . $stmt->error;
        $alert_type = "error";
    }
}

// --- Fetch invitations received by student (leader invited them) ---
// These are invites where: sender = leader, receiver = this student
$stmt = $conn->prepare("
    SELECT gi.invite_id, gi.group_id, gi.sent_at, s.name AS sender_name, g.leader_id,
           (SELECT COUNT(*) FROM group_members WHERE group_id = gi.group_id) AS current_members
    FROM group_invites gi
    JOIN groups g ON gi.group_id = g.group_id
    JOIN students s ON gi.sender_id = s.student_id
    WHERE gi.receiver_id = ? 
    AND gi.status = 'pending'
    AND gi.sender_id = g.leader_id
    ORDER BY gi.sent_at DESC
");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$received_invites = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// --- Fetch join requests received by leader (students requesting to join their group) ---
// These are requests where: sender = requesting student, receiver = this student (who is leader)
$stmt = $conn->prepare("
    SELECT gi.invite_id, gi.group_id, gi.sender_id, gi.sent_at, s.name AS requester_name, s.email, c.name AS class_name
    FROM group_invites gi
    JOIN students s ON gi.sender_id = s.student_id
    JOIN classes c ON s.class_id = c.id
    JOIN groups g ON gi.group_id = g.group_id
    WHERE gi.receiver_id = ? 
    AND gi.status = 'pending'
    AND g.leader_id = ?
    AND gi.sender_id != g.leader_id
    ORDER BY gi.sent_at DESC
");
$stmt->bind_param("ii", $student_id, $student_id);
$stmt->execute();
$join_requests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Setup session user array
if (!isset($_SESSION['user'])) {
    $_SESSION['user'] = [
        'user_id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'] ?? 'student',
        'role' => 'student',
        'name' => $student['name']
    ];
}

$page_title = 'Manage Invites';
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

        <!-- Received Invitations -->
        <div class="card">
            <div class="card-header">
                <h1 class="card-title">Group Invitations</h1>
                <p class="card-subtitle">Invitations from group leaders (<?= count($received_invites) ?>)</p>
            </div>

            <?php if (empty($received_invites)): ?>
                <div class="alert alert-info">
                    <i data-feather="inbox"></i>
                    <span>No pending invitations.</span>
                </div>
            <?php else: ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Group ID</th>
                                <th>Leader Name</th>
                                <th>Current Members</th>
                                <th>Sent</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($received_invites as $invite): ?>
                                <tr>
                                    <td><strong>#<?= $invite['group_id'] ?></strong></td>
                                    <td><?= $invite['sender_name'] ?></td>
                                    <td><?= $invite['current_members'] ?></td>
                                    <td><?= date('M d, Y H:i', strtotime($invite['sent_at'])) ?></td>
                                    <td>
                                        <form method="post" style="display: inline; margin-right: 8px;">
                                            <input type="hidden" name="invite_id" value="<?= $invite['invite_id'] ?>">
                                            <button type="submit" name="accept_invite" class="btn" style="font-size:12px; padding:6px 12px;">
                                                <i data-feather="check" style="width:14px;height:14px"></i>
                                                Accept
                                            </button>
                                        </form>
                                        <form method="post" style="display: inline;">
                                            <input type="hidden" name="invite_id" value="<?= $invite['invite_id'] ?>">
                                            <button type="submit" name="reject_invite" class="btn-secondary" style="font-size:12px; padding:6px 12px; border-color:#ef4444; color:#ef4444;">
                                                <i data-feather="x" style="width:14px;height:14px"></i>
                                                Reject
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Join Requests (Leader Only) -->
        <?php if (!empty($join_requests)): ?>
            <div class="card">
                <div class="card-header">
                    <h1 class="card-title">Join Requests</h1>
                    <p class="card-subtitle">Students requesting to join your group (<?= count($join_requests) ?>)</p>
                </div>

                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Class</th>
                                <th>Requested</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($join_requests as $request): ?>
                                <tr>
                                    <td><?= $request['requester_name'] ?></td>
                                    <td><?= $request['email'] ?></td>
                                    <td><?= $request['class_name'] ?></td>
                                    <td><?= date('M d, Y H:i', strtotime($request['sent_at'])) ?></td>
                                    <td>
                                        <form method="post" style="display: inline; margin-right: 8px;">
                                            <input type="hidden" name="invite_id" value="<?= $request['invite_id'] ?>">
                                            <button type="submit" name="accept_request" class="btn" style="font-size:12px; padding:6px 12px;">
                                                <i data-feather="check" style="width:14px;height:14px"></i>
                                                Accept
                                            </button>
                                        </form>
                                        <form method="post" style="display: inline;">
                                            <input type="hidden" name="invite_id" value="<?= $request['invite_id'] ?>">
                                            <button type="submit" name="reject_request" class="btn-secondary" style="font-size:12px; padding:6px 12px; border-color:#ef4444; color:#ef4444;">
                                                <i data-feather="x" style="width:14px;height:14px"></i>
                                                Reject
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

        <div class="form-actions">
            <a href="student_home.php" class="btn-secondary">
                <i data-feather="arrow-left"></i>
                Back to Dashboard
            </a>
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
