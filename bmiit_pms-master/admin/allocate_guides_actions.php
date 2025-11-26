<?php
session_start();
require_once '../db.php';
require_once '../email_system/email_helper.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Get JSON input
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
    exit();
}

$action = $data['action'] ?? '';

try {
    switch ($action) {
        case 'finalize_allocation':
            finalizeAllocation($conn, $data);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

/**
 * Finalize guide allocation and send emails
 */
function finalizeAllocation($conn, $data) {
    $semesterId = $data['semester_id'] ?? 0;
    $allocation = $data['allocation'] ?? [];
    $adminUserId = $_SESSION['user_id'];
    
    if (!$semesterId || empty($allocation)) {
        echo json_encode(['success' => false, 'message' => 'Invalid data provided']);
        return;
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        $totalGroups = 0;
        $allocatedGroups = 0;
        $facultyNotifications = []; // Track faculty notifications
        $studentNotifications = []; // Track student notifications
        
        // Process each faculty's allocation
        foreach ($allocation as $facultyId => $groups) {
            if (empty($groups)) continue;
            
            // Get faculty details
            $facultyQuery = "SELECT name, email, phone FROM faculty WHERE faculty_id = ?";
            $stmt = $conn->prepare($facultyQuery);
            $stmt->bind_param("i", $facultyId);
            $stmt->execute();
            $facultyResult = $stmt->get_result();
            $faculty = $facultyResult->fetch_assoc();
            
            if (!$faculty) {
                throw new Exception("Faculty not found: " . $facultyId);
            }
            
            $groupsForFaculty = [];
            
            // Update each group
            foreach ($groups as $group) {
                $groupId = $group['group_id'];
                $totalGroups++;
                
                // Update group with new guide
                $updateQuery = "UPDATE `groups` 
                               SET guide_id = ?
                               WHERE group_id = ?";
                $stmt = $conn->prepare($updateQuery);
                $stmt->bind_param("ii", $facultyId, $groupId);
                
                if (!$stmt->execute()) {
                    throw new Exception("Failed to update group: " . $groupId);
                }
                
                $allocatedGroups++;
                
                // Get all group members for student notifications
                $membersQuery = "SELECT s.name, s.email, s.phone, u.username 
                                FROM group_members gm
                                JOIN students s ON gm.student_id = s.student_id
                                JOIN users u ON s.user_id = u.id
                                WHERE gm.group_id = ?
                                UNION
                                SELECT s.name, s.email, s.phone, u.username
                                FROM `groups` g
                                JOIN students s ON g.leader_id = s.student_id
                                JOIN users u ON s.user_id = u.id
                                WHERE g.group_id = ?";
                $stmt = $conn->prepare($membersQuery);
                $stmt->bind_param("ii", $groupId, $groupId);
                $stmt->execute();
                $membersResult = $stmt->get_result();
                
                $members = [];
                while ($member = $membersResult->fetch_assoc()) {
                    $members[] = $member;
                    
                    // Queue student notification
                    $studentNotifications[] = [
                        'email' => $member['email'],
                        'name' => $member['name'],
                        'group_id' => $groupId,
                        'faculty_name' => $faculty['name'],
                        'faculty_email' => $faculty['email'],
                        'faculty_phone' => $faculty['phone']
                    ];
                }
                
                // Add to faculty's group list
                $groupsForFaculty[] = [
                    'group_id' => $groupId,
                    'leader_name' => $group['leader_name'],
                    'leader_username' => $group['leader_username'],
                    'leader_email' => $group['leader_email'],
                    'leader_phone' => $group['leader_phone'],
                    'members' => $members
                ];
            }
            
            // Queue faculty notification
            $facultyNotifications[] = [
                'email' => $faculty['email'],
                'name' => $faculty['name'],
                'groups' => $groupsForFaculty
            ];
        }
        
        // Update semester guide allocation status
        $semesterUpdateQuery = "INSERT INTO guide_allocation 
                               (semester_id, is_finalized, total_groups, allocated_groups)
                               VALUES (?, 1, ?, ?)
                               ON DUPLICATE KEY UPDATE 
                               is_finalized = 1, 
                               total_groups = ?,
                               allocated_groups = ?";
        $stmt = $conn->prepare($semesterUpdateQuery);
        $stmt->bind_param("iiiii", $semesterId, $totalGroups, $allocatedGroups, $totalGroups, $allocatedGroups);
        $stmt->execute();
        
        // Commit transaction
        $conn->commit();
        
        // Send emails (outside transaction to avoid delays)
        $emailErrors = [];

        // Track paused/failed/sent counts
        $pausedCount = 0;
        $failedCount = 0;

        // Send emails to faculty (capture errors returned by queueEmail)
        foreach ($facultyNotifications as $notification) {
            $res = sendFacultyGuideAssignmentEmail($notification);
            if (is_array($res) && (($res['paused'] ?? false) === true)) {
                $pausedCount++;
                continue;
            }
            if (!is_array($res) || (($res['success'] ?? false) !== true)) {
                $failedCount++;
                $emailErrors[] = "Failed to send email to faculty: " . $notification['name'];
            }
        }

        // Send emails to students
        foreach ($studentNotifications as $notification) {
            $res = sendStudentGuideAssignmentEmail($notification);
            if (is_array($res) && (($res['paused'] ?? false) === true)) {
                $pausedCount++;
                continue;
            }
            if (!is_array($res) || (($res['success'] ?? false) !== true)) {
                $failedCount++;
                $emailErrors[] = "Failed to send email to student: " . $notification['name'];
            }
        }

    $message = "Guide allocation finalized successfully. " . 
           "Allocated " . $allocatedGroups . " groups to " . count($facultyNotifications) . " faculty members.";

        // If there were failures, mention failures (no counts)
        if ($failedCount > 0) {
            $message .= " However, some notification emails failed to send.";
        }

        // If the email service is paused/disabled, state clearly that emails were not sent (no promises)
        if ($pausedCount > 0) {
            // Do not use 'some' when the service is disabled — be direct as requested
            $message .= " Emails were not sent as the email service is disabled right now.";
            if ($failedCount > 0) {
                $message .= " Some notifications also failed to send.";
            }
        }
        
        // Set a server-side flash so redirected pages (admin_home) can show a persistent message
        $_SESSION['flash_message'] = $message;
    // If there were real failures mark as warning, otherwise if only paused then warning as well
    $_SESSION['flash_type'] = (!empty($emailErrors) || !empty($pausedCount)) ? 'warning' : 'success';

        echo json_encode([
            'success' => true, 
            'message' => $message,
            'allocated_groups' => $allocatedGroups,
            'email_errors' => $emailErrors
        ]);
        
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
}

/**
 * Send email to faculty about guide assignment
 */
function sendFacultyGuideAssignmentEmail($data) {
    $facultyName = $data['name'];
    $facultyEmail = $data['email'];
    $groups = $data['groups'];
    
    $subject = "BMIIT PMS - Guide Assignment";

    // Plain, consistent format used across system; no links included
    $bodyHtml = "<p>Dear " . htmlspecialchars($facultyName) . ",</p>" .
                "<p>You have been assigned as Project Guide for " . count($groups) . " group(s).</p>" .
                "<p>Regards,<br/>BMIIT PMS</p>";

    $bodyPlain = "Dear {$facultyName},\n\nYou have been assigned as Project Guide for " . count($groups) . " group(s).\n\nRegards,\nBMIIT PMS";

    return queueEmail($facultyEmail, $facultyName, $subject, $bodyHtml, $bodyPlain);
}

/**
 * Send email to student about guide assignment
 */
function sendStudentGuideAssignmentEmail($data) {
    $studentName = $data['name'];
    $studentEmail = $data['email'];
    $groupId = $data['group_id'];
    $facultyName = $data['faculty_name'];
    $facultyEmail = $data['faculty_email'];
    $facultyPhone = $data['faculty_phone'];
    
    $subject = "BMIIT PMS - Guide Assigned";

    // Plain student email (no links)
    $bodyHtml = "<p>Dear " . htmlspecialchars($studentName) . ",</p>" .
                "<p>A Project Guide has been assigned to your group (Group #{$groupId}). Guide: " . htmlspecialchars($facultyName) . " (" . htmlspecialchars($facultyEmail) . ").</p>" .
                "<p>Regards,<br/>BMIIT PMS</p>";

    $bodyPlain = "Dear {$studentName},\n\nA Project Guide has been assigned to your group (Group #{$groupId}). Guide: {$facultyName} ({$facultyEmail}).\n\nRegards,\nBMIIT PMS";

    return queueEmail($studentEmail, $studentName, $subject, $bodyHtml, $bodyPlain);
}

/**
 * Get base URL for email links
 */
function getBaseUrl() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'];
    $baseUrl = $protocol . $host . dirname(dirname($_SERVER['SCRIPT_NAME']));
    return rtrim($baseUrl, '/');
}
?>
