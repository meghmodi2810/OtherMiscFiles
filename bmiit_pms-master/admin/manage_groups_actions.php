<?php
session_start();
require_once '../db.php';
require_once '../email_system/email_helper.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit();
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Get Group Members (for viewing)
if ($action === 'get_members') {
    $groupId = intval($_GET['group_id']);
    
    $query = "SELECT s.student_id, s.name, s.email, u.username, gm.role
              FROM group_members gm
              JOIN students s ON gm.student_id = s.student_id
              JOIN users u ON s.user_id = u.id
              WHERE gm.group_id = ?
              ORDER BY gm.role DESC, s.name ASC";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $groupId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo '<ul class="member-list">';
        while ($row = $result->fetch_assoc()) {
            echo '<li class="member-item">';
            echo '<div class="member-info">';
            echo '<div class="member-name">' . $row['name'] . '</div>';
            echo '<div class="member-email">' . $row['email'] . ' (' . $row['username'] . ')</div>';
            echo '</div>';
            if ($row['role'] === 'leader') {
                echo '<span class="member-role">LEADER</span>';
            }
            echo '</li>';
        }
        echo '</ul>';
    } else {
        echo '<p style="text-align: center; color: var(--text-light);">No members found</p>';
    }
    
    $stmt->close();
    exit();
}

// Get Members for Transfer Leadership (exclude current leader)
if ($action === 'get_members_for_transfer') {
    $groupId = intval($_GET['group_id']);
    
    $query = "SELECT s.student_id, s.name, u.username
              FROM group_members gm
              JOIN students s ON gm.student_id = s.student_id
              JOIN users u ON s.user_id = u.id
              WHERE gm.group_id = ? AND gm.role = 'member'
              ORDER BY s.name ASC";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $groupId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $members = [];
    while ($row = $result->fetch_assoc()) {
        $members[] = $row;
    }
    
    echo json_encode($members);
    $stmt->close();
    exit();
}

// Transfer Leadership
if ($action === 'transfer_leadership') {
    $groupId = intval($_POST['group_id']);
    $newLeaderId = intval($_POST['new_leader_id']);
    
    // Get current leader info
    $currentLeaderQuery = "SELECT s.student_id, s.name, s.email 
                           FROM groups g 
                           JOIN students s ON g.leader_id = s.student_id 
                           WHERE g.group_id = ?";
    $stmt = $conn->prepare($currentLeaderQuery);
    $stmt->bind_param("i", $groupId);
    $stmt->execute();
    $currentLeader = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    // Get new leader info
    $newLeaderQuery = "SELECT name, email FROM students WHERE student_id = ?";
    $stmt = $conn->prepare($newLeaderQuery);
    $stmt->bind_param("i", $newLeaderId);
    $stmt->execute();
    $newLeader = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if (!$currentLeader || !$newLeader) {
        echo json_encode(['success' => false, 'message' => 'Leader not found']);
        exit();
    }
    
    // Update group leader
    $stmt = $conn->prepare("UPDATE groups SET leader_id = ? WHERE group_id = ?");
    $stmt->bind_param("ii", $newLeaderId, $groupId);
    
    if (!$stmt->execute()) {
        echo json_encode(['success' => false, 'message' => 'Failed to transfer leadership']);
        $stmt->close();
        exit();
    }
    $stmt->close();
    
    // Update group_members roles
    $stmt = $conn->prepare("UPDATE group_members SET role = 'member' WHERE group_id = ? AND student_id = ?");
    $stmt->bind_param("ii", $groupId, $currentLeader['student_id']);
    $stmt->execute();
    $stmt->close();
    
    $stmt = $conn->prepare("UPDATE group_members SET role = 'leader' WHERE group_id = ? AND student_id = ?");
    $stmt->bind_param("ii", $groupId, $newLeaderId);
    $stmt->execute();
    $stmt->close();
    
    // Send email to old leader (standardized)
    $messageLineOld = "The leadership of your group (Group ID: {$groupId}) has been transferred to {$newLeader['name']} by the administrator. You remain a member of the group.";
    list($htmlBodyOld, $plainBodyOld) = buildSimpleEmailBody($currentLeader['name'], $messageLineOld);
    $resOld = sendEmail($currentLeader['email'], $currentLeader['name'], 'Leadership Transfer - Group ' . $groupId, $htmlBodyOld, $plainBodyOld);

    // Send email to new leader
    $messageLineNew = "You have been appointed as the new leader of Group ID: {$groupId} by the administrator. As the group leader, you have additional responsibilities and permissions.";
    list($htmlBodyNew, $plainBodyNew) = buildSimpleEmailBody($newLeader['name'], $messageLineNew);
    $resNew = sendEmail($newLeader['email'], $newLeader['name'], 'You are now Group Leader - Group ' . $groupId, $htmlBodyNew, $plainBodyNew);

    // Summarize email results (treat paused as neutral)
    $sentCount = 0;
    $pausedCount = 0;
    $failedCount = 0;
    foreach ([$resOld, $resNew] as $r) {
        if (is_array($r) && !empty($r['success'])) {
            $sentCount++;
        } elseif (is_array($r) && !empty($r['paused'])) {
            $pausedCount++;
        } else {
            $failedCount++;
        }
    }

    $msg = 'Leadership transferred successfully!';
    if ($failedCount === 0 && $pausedCount === 0) {
        $msg .= ' Both members have been notified via email.';
    } else {
        if ($sentCount > 0) {
            $msg .= " $sentCount notification(s) sent.";
        }
        if ($pausedCount > 0) {
            $msg .= " Emails were not sent as the email service is disabled right now.";
        }
        if ($failedCount > 0) {
            $msg .= " However, $failedCount notification(s) failed to send.";
        }
    }

    echo json_encode(['success' => true, 'message' => $msg]);
    exit();
}

// Force Finalize Group
if ($action === 'force_finalize') {
    $groupId = intval($_POST['group_id']);
    
    $stmt = $conn->prepare("UPDATE groups SET finalized = 1 WHERE group_id = ?");
    $stmt->bind_param("i", $groupId);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Group finalized successfully!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to finalize group']);
    }
    
    $stmt->close();
    exit();
}

// Dissolve Group
if ($action === 'dissolve_group') {
    $groupId = intval($_POST['group_id']);
    
    // Get all members' info before deleting
    $membersQuery = "SELECT s.name, s.email 
                     FROM group_members gm 
                     JOIN students s ON gm.student_id = s.student_id 
                     WHERE gm.group_id = ?";
    $stmt = $conn->prepare($membersQuery);
    $stmt->bind_param("i", $groupId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $members = [];
    while ($row = $result->fetch_assoc()) {
        $members[] = $row;
    }
    $stmt->close();
    
    if (empty($members)) {
        echo json_encode(['success' => false, 'message' => 'Group not found']);
        exit();
    }
    
    // Delete group (cascade will delete group_members automatically)
    $stmt = $conn->prepare("DELETE FROM groups WHERE group_id = ?");
    $stmt->bind_param("i", $groupId);
    
    if (!$stmt->execute()) {
        echo json_encode(['success' => false, 'message' => 'Failed to dissolve group']);
        $stmt->close();
        exit();
    }
    $stmt->close();
    
    // Send email to all members (capture results)
        $sentNotifications = 0;
        $pausedNotifications = 0;
        $failedNotifications = 0;
        foreach ($members as $member) {
        $messageLine = "Your group (Group ID: {$groupId}) has been dissolved by the administrator. You are now free to create or join a new group.";
        list($htmlBody, $plainBody) = buildSimpleEmailBody($member['name'], $messageLine);
        $emailResult = sendEmail($member['email'], $member['name'], 'Group Dissolved - Group ' . $groupId, $htmlBody, $plainBody);
        if (is_array($emailResult) && !empty($emailResult['success'])) {
            $sentNotifications++;
        } elseif (is_array($emailResult) && !empty($emailResult['paused'])) {
            $pausedNotifications++;
        } else {
            $failedNotifications++;
        }
    }

    $msg = 'Group dissolved successfully!';
    if ($failedNotifications === 0 && $pausedNotifications === 0) {
        $msg .= ' All members have been notified via email.';
    } else {
        if ($sentNotifications > 0) {
            $msg .= " $sentNotifications notification(s) sent.";
        }
        if ($pausedNotifications > 0) {
            $msg .= " Emails were not sent as the email service is disabled right now.";
        }
        if ($failedNotifications > 0) {
            $msg .= " However, $failedNotifications notification(s) failed to send.";
        }
    }
    
    echo json_encode(['success' => true, 'message' => $msg]);
    exit();
}

// Bulk Dissolve Groups
if ($action === 'bulk_dissolve') {
    $groupIds = json_decode($_POST['group_ids']);
    
    if (empty($groupIds)) {
        echo json_encode(['success' => false, 'message' => 'No groups selected']);
        exit();
    }
    
    $dissolvedCount = 0;
    $emailsSent = 0;
    
    foreach ($groupIds as $groupId) {
        // Get all members' info before deleting
        $membersQuery = "SELECT s.name, s.email 
                         FROM group_members gm 
                         JOIN students s ON gm.student_id = s.student_id 
                         WHERE gm.group_id = ?";
        $stmt = $conn->prepare($membersQuery);
        $stmt->bind_param("i", $groupId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $members = [];
        while ($row = $result->fetch_assoc()) {
            $members[] = $row;
        }
        $stmt->close();
        
        if (empty($members)) {
            continue;
        }
        
        // Delete group
        $stmt = $conn->prepare("DELETE FROM groups WHERE group_id = ?");
        $stmt->bind_param("i", $groupId);
        
        if ($stmt->execute()) {
            $dissolvedCount++;
            
            // Send email to all members
                foreach ($members as $member) {
                $messageLine = "Your group (Group ID: {$groupId}) has been dissolved by the administrator. You are now free to create or join a new group.";
                list($htmlBody, $plainBody) = buildSimpleEmailBody($member['name'], $messageLine);
                $emailResult = sendEmail($member['email'], $member['name'], 'Group Dissolved - Group ' . $groupId, $htmlBody, $plainBody);
                if (isset($emailResult['success']) && $emailResult['success']) {
                    $emailsSent++;
                } elseif (isset($emailResult['paused']) && $emailResult['paused']) {
                    // Email service paused — count separately (do not treat as failure)
                    $emailsPaused = ($emailsPaused ?? 0) + 1;
                } else {
                    $emailsFailed = ($emailsFailed ?? 0) + 1;
                }
            }
        }
        $stmt->close();
    }
    
    $msg = "$dissolvedCount group(s) dissolved successfully! $emailsSent email notification(s) sent.";
    if (!empty($emailsPaused)) {
        // User-requested neutral phrasing when the email service is disabled.
        $msg .= " Emails were not sent as the email service is disabled right now.";
    }
    if (!empty($emailsFailed)) {
        // Keep a short note about actual failures, appended when present.
        $msg .= " Some notifications also failed to send.";
    }
    echo json_encode(['success' => true, 'message' => $msg]);
    exit();
}

// Export to CSV
if ($action === 'export_csv') {
    $search = $_GET['search'] ?? '';
    $semester = $_GET['semester'] ?? '';
    $classFilter = $_GET['class'] ?? '';
    $status = $_GET['status'] ?? '';
    
    $date = date('Y-m-d');
    $filename = 'groups_export_' . $date . '.csv';
    
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    
    fputcsv($output, ['Group ID', 'Leader Name', 'Leader Roll No', 'Members Count', 'Class', 'Semester', 'Status', 'Allocated Guide', 'Project Title', 'Created At']);
    
    $query = "SELECT g.group_id, g.finalized, g.created_at,
                     s.name as leader_name, u.username as leader_username,
                     c.name as class_name, sem.semester_no, co.name as course_name, sem.year,
                     COUNT(gm.member_id) as member_count,
                     p.title as project_title,
                     f.name as faculty_name
              FROM groups g
              JOIN students s ON g.leader_id = s.student_id
              JOIN users u ON s.user_id = u.id
              LEFT JOIN classes c ON g.class_id = c.id
              JOIN semesters sem ON g.semester_id = sem.id
              JOIN courses co ON sem.course_id = co.id
              LEFT JOIN group_members gm ON g.group_id = gm.group_id
              LEFT JOIN projects p ON g.group_id = p.group_id
              LEFT JOIN faculty f ON p.faculty_id = f.faculty_id
              WHERE 1=1";
    
    // Apply filters
    if ($search !== '') {
        $search = $conn->real_escape_string($search);
        $query .= " AND (g.group_id = '$search' OR s.name LIKE '%$search%' OR u.username LIKE '%$search%')";
    }
    
    if ($semester !== '') {
        $query .= " AND sem.id = " . intval($semester);
    }
    
    if ($classFilter !== '') {
        $query .= " AND c.id = " . intval($classFilter);
    }
    
    if ($status !== '') {
        if ($status === 'finalized') {
            $query .= " AND g.finalized = 1";
        } else if ($status === 'not_finalized') {
            $query .= " AND g.finalized = 0";
        }
    }
    
    $query .= " GROUP BY g.group_id, g.finalized, g.created_at, s.name, u.username, 
                       c.name, sem.semester_no, co.name, sem.year, p.title, f.name
              ORDER BY g.group_id ASC";
    
    $result = $conn->query($query);
    
    while ($row = $result->fetch_assoc()) {
        $status = $row['finalized'] ? 'Finalized' : 'Not Finalized';
        $className = $row['class_name'] ? $row['class_name'] : 'Inter-class';
        $semester = $row['course_name'] . ' - Sem ' . $row['semester_no'] . ' ' . $row['year'];
        $guide = $row['faculty_name'] ? $row['faculty_name'] : 'Not Assigned';
        $project = $row['project_title'] ? $row['project_title'] : 'Not Submitted';
        
        fputcsv($output, [
            $row['group_id'],
            $row['leader_name'],
            $row['leader_username'],
            $row['member_count'],
            $className,
            $semester,
            $status,
            $guide,
            $project,
            $row['created_at']
        ]);
    }
    
    fclose($output);
    exit();
}

echo json_encode(['success' => false, 'message' => 'Invalid action']);
?>
