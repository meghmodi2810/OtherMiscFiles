<?php
session_start();
require_once '../db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'get_semesters':
            getSemesters($conn);
            break;
            
        case 'get_faculty':
            getFaculty($conn);
            break;
            
        case 'get_groups':
            getGroups($conn);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

/**
 * Get all semesters with group statistics
 */
function getSemesters($conn) {
    $query = "SELECT 
                s.id,
                c.name as course_name,
                s.semester_no,
                s.year,
                COUNT(g.group_id) as total_groups,
                SUM(CASE WHEN g.finalized = 1 THEN 1 ELSE 0 END) as finalized_groups,
                SUM(CASE WHEN g.guide_id IS NOT NULL THEN 1 ELSE 0 END) as allocated_groups,
                CASE 
                    WHEN SUM(CASE WHEN g.guide_id IS NOT NULL THEN 1 ELSE 0 END) > 0 
                    THEN 'allocated' 
                    ELSE 'pending' 
                END as allocation_status
              FROM semesters s
              JOIN courses c ON s.course_id = c.id
              LEFT JOIN `groups` g ON g.semester_id = s.id
              GROUP BY s.id, c.name, s.semester_no, s.year
              ORDER BY s.year DESC, c.name, s.semester_no DESC";
    
    $result = $conn->query($query);
    
    if (!$result) {
        throw new Exception("Database query failed: " . $conn->error);
    }
    
    $semesters = [];
    while ($row = $result->fetch_assoc()) {
        $semesters[] = [
            'id' => (int)$row['id'],
            'course_name' => $row['course_name'],
            'semester_no' => (int)$row['semester_no'],
            'year' => $row['year'],
            'total_groups' => (int)$row['total_groups'],
            'finalized_groups' => (int)$row['finalized_groups'],
            'allocated_groups' => (int)$row['allocated_groups'],
            'allocation_status' => $row['allocation_status']
        ];
    }
    
    echo json_encode(['success' => true, 'semesters' => $semesters]);
}

/**
 * Get all faculty with current guide load
 */
function getFaculty($conn) {
    $semesterId = $_GET['semester_id'] ?? 0;
    
    if (!$semesterId) {
        echo json_encode(['success' => false, 'message' => 'Semester ID required']);
        return;
    }
    
    // Get all faculty with their current guide count for this semester
    $query = "SELECT 
                f.faculty_id,
                f.name,
                f.email,
                f.phone,
                COUNT(g.group_id) as current_load
              FROM faculty f
              LEFT JOIN `groups` g ON g.guide_id = f.faculty_id AND g.semester_id = ?
              GROUP BY f.faculty_id, f.name, f.email, f.phone
              ORDER BY f.name ASC";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $semesterId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $faculty = [];
    while ($row = $result->fetch_assoc()) {
        $faculty[] = [
            'faculty_id' => (int)$row['faculty_id'],
            'name' => $row['name'],
            'email' => $row['email'],
            'phone' => $row['phone'],
            'current_load' => (int)$row['current_load']
        ];
    }
    
    echo json_encode(['success' => true, 'faculty' => $faculty]);
}

/**
 * Get all finalized groups for a semester
 */
function getGroups($conn) {
    $semesterId = $_GET['semester_id'] ?? 0;
    
    if (!$semesterId) {
        echo json_encode(['success' => false, 'message' => 'Semester ID required']);
        return;
    }
    
    // Check if there are existing guide allocations
    $checkQuery = "SELECT COUNT(*) as allocated_count 
                   FROM `groups` 
                   WHERE semester_id = ? AND guide_id IS NOT NULL";
    $stmt = $conn->prepare($checkQuery);
    $stmt->bind_param("i", $semesterId);
    $stmt->execute();
    $checkResult = $stmt->get_result();
    $checkRow = $checkResult->fetch_assoc();
    $hasExistingAllocation = $checkRow['allocated_count'] > 0;
    
    // Get all finalized groups with leader details
    $query = "SELECT 
                g.group_id,
                g.leader_id,
                g.guide_id,
                s.student_id,
                s.name as leader_name,
                u.username as leader_username,
                s.email as leader_email,
                s.phone as leader_phone,
                COUNT(gm.student_id) as member_count
              FROM `groups` g
              JOIN students s ON g.leader_id = s.student_id
              JOIN users u ON s.user_id = u.id
              LEFT JOIN group_members gm ON g.group_id = gm.group_id
              WHERE g.semester_id = ? AND g.finalized = 1
              GROUP BY g.group_id, g.leader_id, g.guide_id, s.student_id, 
                       s.name, u.username, s.email, s.phone
              ORDER BY g.group_id ASC";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $semesterId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $groups = [];
    while ($row = $result->fetch_assoc()) {
        $groups[] = [
            'group_id' => (int)$row['group_id'],
            'leader_id' => (int)$row['leader_id'],
            'leader_name' => $row['leader_name'],
            'leader_username' => $row['leader_username'],
            'leader_email' => $row['leader_email'],
            'leader_phone' => $row['leader_phone'],
            'member_count' => (int)$row['member_count'],
            'current_guide_id' => $row['guide_id'] ? (int)$row['guide_id'] : null
        ];
    }
    
    echo json_encode([
        'success' => true, 
        'groups' => $groups,
        'has_existing_allocation' => $hasExistingAllocation
    ]);
}
?>
