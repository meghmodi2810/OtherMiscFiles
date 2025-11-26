<?php
session_start();
require_once '../db.php';
require_once '../email_system/email_helper.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit();
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Toggle Active/Inactive Status
if ($action === 'toggle_active') {
    $userId = intval($_POST['user_id']);
    $userType = $_POST['user_type'];
    $newStatus = intval($_POST['new_status']);
    
    $stmt = $conn->prepare("UPDATE users SET is_active = ? WHERE id = ?");
    $stmt->bind_param("ii", $newStatus, $userId);
    
    if ($stmt->execute()) {
        $statusText = $newStatus == 1 ? 'activated' : 'deactivated';
        echo json_encode(['success' => true, 'message' => 'User ' . $statusText . ' successfully!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    }
    
    $stmt->close();
    exit();
}

// Reset Password
if ($action === 'reset_password') {
    $userId = intval($_POST['user_id']);
    $userType = $_POST['user_type'];
    
    // Generate new temp passkey
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $passkey = '';
    for ($i = 0; $i < 8; $i++) {
        $passkey .= $chars[random_int(0, strlen($chars) - 1)];
    }
    
    $hashed_password = password_hash($passkey, PASSWORD_BCRYPT);
    
    // Get user details
    $userTable = ($userType === 'students') ? 'students' : (($userType === 'faculty') ? 'faculty' : 'admin');
    $userIdCol = ($userType === 'students') ? 'student_id' : (($userType === 'faculty') ? 'faculty_id' : 'admin_id');
    
    $userQuery = "SELECT name, email, username FROM $userTable 
                  LEFT JOIN users ON $userTable.user_id = users.id 
                  WHERE users.id = ?";
    $stmt = $conn->prepare($userQuery);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit();
    }
    
    // Update password in users table
    $stmt = $conn->prepare("UPDATE users SET password_hash = ?, first_login = 1 WHERE id = ?");
    $stmt->bind_param("si", $hashed_password, $userId);
    
    if (!$stmt->execute()) {
        echo json_encode(['success' => false, 'message' => 'Failed to update password']);
        $stmt->close();
        exit();
    }
    $stmt->close();
    
    // Update temp_passkey in user's table
    $stmt = $conn->prepare("UPDATE $userTable SET temp_passkey = ? WHERE user_id = ?");
    $stmt->bind_param("si", $passkey, $userId);
    $stmt->execute();
    $stmt->close();
    
    // Send standardized password reset email (no links)
    $messageLine = "Your password has been reset by the administrator.\n\nUsername: {$user['username']}\nTemporary Passkey: {$passkey}\n\nPlease login and change your password on first use.";
    list($htmlBody, $plainBody) = buildSimpleEmailBody($user['name'], $messageLine);
    $emailResult = sendEmail($user['email'], $user['name'], 'Password Reset - BMIIT PMS', $htmlBody, $plainBody);
    
    if (isset($emailResult['success']) && $emailResult['success']) {
        echo json_encode(['success' => true, 'message' => 'Password reset successfully! New credentials sent to ' . $user['email']]);
    } elseif (isset($emailResult['paused']) && $emailResult['paused']) {
        // Email service is paused/disabled — follow requested direct phrasing
        echo json_encode(['success' => true, 'message' => 'Password reset successfully. Emails were not sent as the email service is disabled right now. Please share the credentials manually.']);
    } else {
        echo json_encode(['success' => true, 'message' => 'Password reset successfully, but email could not be sent: ' . ($emailResult['message'] ?? 'Unknown error')]);
    }
    
    exit();
}

// Get User Details for Editing
if ($action === 'get_user') {
    $userId = intval($_GET['user_id']);
    $userType = $_GET['user_type'];
    
    $userTable = ($userType === 'students') ? 'students' : (($userType === 'faculty') ? 'faculty' : 'admin');
    
    if ($userType === 'students') {
        $query = "SELECT s.name, s.email, s.phone, s.class_id, c.semester_id, u.username as enrollment
                  FROM students s 
                  JOIN classes c ON s.class_id = c.id 
                  JOIN users u ON s.user_id = u.id
                  WHERE s.user_id = ?";
    } elseif ($userType === 'faculty') {
        $query = "SELECT name, email, phone, specialization, experience FROM faculty WHERE user_id = ?";
    } else {
        $query = "SELECT name, email, phone FROM admin WHERE user_id = ?";
    }
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    
    echo json_encode($user);
    exit();
}

// Edit User
if ($action === 'edit_user') {
    $userId = intval($_POST['user_id']);
    $userType = $_POST['user_type'];
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    
    // Validate inputs
    if (empty($name) || empty($email) || empty($phone)) {
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        exit();
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email address']);
        exit();
    }
    
    if (!preg_match('/^[0-9]{10}$/', $phone)) {
        echo json_encode(['success' => false, 'message' => 'Invalid phone number (must be 10 digits)']);
        exit();
    }
    
    $userTable = ($userType === 'students') ? 'students' : (($userType === 'faculty') ? 'faculty' : 'admin');
    
    if ($userType === 'students') {
        $classId = intval($_POST['class_id']);
        $enrollment = trim($_POST['enrollment']);
        
        if (empty($classId)) {
            echo json_encode(['success' => false, 'message' => 'Please select a class']);
            exit();
        }
        
        if (!preg_match('/^[0-9]{15}$/', $enrollment)) {
            echo json_encode(['success' => false, 'message' => 'Invalid enrollment number (must be 15 digits)']);
            exit();
        }
        
        // Check if enrollment already exists (excluding current user)
        $checkEnrollment = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
        $checkEnrollment->bind_param("si", $enrollment, $userId);
        $checkEnrollment->execute();
        $checkEnrollment->store_result();
        
        if ($checkEnrollment->num_rows > 0) {
            echo json_encode(['success' => false, 'message' => 'Enrollment number already exists']);
            $checkEnrollment->close();
            exit();
        }
        $checkEnrollment->close();
        
        // Check if student is in a finalized group
        $checkGroup = $conn->query("SELECT g.group_id, g.finalized 
                                     FROM group_members gm 
                                     JOIN groups g ON gm.group_id = g.group_id 
                                     WHERE gm.student_id = (SELECT student_id FROM students WHERE user_id = $userId) 
                                     AND g.finalized = 1");
        
        if ($checkGroup && $checkGroup->num_rows > 0) {
            // Get current class
            $currentClass = $conn->query("SELECT class_id FROM students WHERE user_id = $userId")->fetch_assoc()['class_id'];
            
            if ($currentClass != $classId) {
                echo json_encode(['success' => false, 'message' => 'Cannot change class: Student is in a finalized group']);
                exit();
            }
        }
        
        // Update username in users table
        $stmtUser = $conn->prepare("UPDATE users SET username = ? WHERE id = ?");
        $stmtUser->bind_param("si", $enrollment, $userId);
        
        if (!$stmtUser->execute()) {
            echo json_encode(['success' => false, 'message' => 'Failed to update enrollment number']);
            $stmtUser->close();
            exit();
        }
        $stmtUser->close();
        
        // Update students table
        $stmt = $conn->prepare("UPDATE students SET name = ?, email = ?, phone = ?, class_id = ? WHERE user_id = ?");
        $stmt->bind_param("sssii", $name, $email, $phone, $classId, $userId);
        
    } elseif ($userType === 'faculty') {
        $specialization = trim($_POST['specialization']);
        $experience = intval($_POST['experience']);
        
        $stmt = $conn->prepare("UPDATE faculty SET name = ?, email = ?, phone = ?, specialization = ?, experience = ? WHERE user_id = ?");
        $stmt->bind_param("sssiii", $name, $email, $phone, $specialization, $experience, $userId);
        
    } else {
        $stmt = $conn->prepare("UPDATE admin SET name = ?, email = ?, phone = ? WHERE user_id = ?");
        $stmt->bind_param("sssi", $name, $email, $phone, $userId);
    }
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'User updated successfully!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    }
    
    $stmt->close();
    exit();
}

// Bulk Toggle Active/Inactive
if ($action === 'bulk_toggle') {
    $userIds = json_decode($_POST['user_ids']);
    $userType = $_POST['user_type'];
    $bulkAction = $_POST['bulk_action']; // 'activate' or 'deactivate'
    
    if (empty($userIds)) {
        echo json_encode(['success' => false, 'message' => 'No users selected']);
        exit();
    }
    
    $newStatus = ($bulkAction === 'activate') ? 1 : 0;
    $placeholders = implode(',', array_fill(0, count($userIds), '?'));
    
    $stmt = $conn->prepare("UPDATE users SET is_active = ? WHERE id IN ($placeholders)");
    $types = str_repeat('i', count($userIds) + 1);
    $params = array_merge([$newStatus], $userIds);
    $stmt->bind_param($types, ...$params);
    
    if ($stmt->execute()) {
        $count = $stmt->affected_rows;
        $actionText = $bulkAction === 'activate' ? 'activated' : 'deactivated';
        echo json_encode(['success' => true, 'message' => "$count user(s) $actionText successfully!"]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    }
    
    $stmt->close();
    exit();
}

// Export to CSV
if ($action === 'export_csv') {
    $userType = $_GET['user_type'] ?? 'students';
    $search = $_GET['search'] ?? '';
    $status = $_GET['status'] ?? '';
    $semester = $_GET['semester'] ?? '';
    $classFilter = $_GET['class'] ?? '';
    
    $date = date('Y-m-d');
    $filename = $userType . '_export_' . $date . '.csv';
    
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    
    if ($userType === 'students') {
        fputcsv($output, ['Enrollment No.', 'Name', 'Email', 'Phone', 'Class', 'Semester', 'Course', 'Status', 'Password Status']);
        
        $query = "SELECT s.student_id, s.name, s.email, s.phone, s.temp_passkey,
                         u.username, u.is_active,
                         c.name as class_name, sem.semester_no, co.name as course_name, sem.year
                  FROM students s
                  JOIN users u ON s.user_id = u.id
                  JOIN classes c ON s.class_id = c.id
                  JOIN semesters sem ON c.semester_id = sem.id
                  JOIN courses co ON sem.course_id = co.id
                  WHERE 1=1";
        
        // Apply filters
        if ($search !== '') {
            $search = $conn->real_escape_string($search);
            $query .= " AND (s.name LIKE '%$search%' OR s.email LIKE '%$search%' OR u.username LIKE '%$search%' OR s.phone LIKE '%$search%')";
        }
        
        if ($status !== '') {
            $query .= " AND u.is_active = " . intval($status);
        }
        
        if ($semester !== '') {
            $query .= " AND sem.id = " . intval($semester);
        }
        
        if ($classFilter !== '') {
            $query .= " AND c.id = " . intval($classFilter);
        }
        
        $query .= " ORDER BY u.username ASC";
        
        $result = $conn->query($query);
        
        if (!$result) {
            die("Query failed: " . $conn->error . "\nQuery: " . $query);
        }
        
        while ($row = $result->fetch_assoc()) {
            $statusText = $row['is_active'] ? 'Active' : 'Inactive';
            $passwordStatus = $row['temp_passkey'] ? 'Not Set (Key: ' . $row['temp_passkey'] . ')' : 'Set';
            $semesterText = $row['course_name'] . ' - Sem ' . $row['semester_no'] . ' ' . $row['year'];
            
            fputcsv($output, [
                $row['username'],
                $row['name'],
                $row['email'],
                $row['phone'],
                $row['class_name'],
                $semesterText,
                $row['course_name'],
                $statusText,
                $passwordStatus
            ]);
        }
        
    } elseif ($userType === 'faculty') {
        fputcsv($output, ['Faculty ID', 'Email', 'Name', 'Phone', 'Specialization', 'Experience (Years)', 'Status', 'Password Status']);
        
        $query = "SELECT f.faculty_id, f.name, f.email, f.phone, f.specialization, f.experience, f.temp_passkey,
                         u.is_active
                  FROM faculty f
                  JOIN users u ON f.user_id = u.id
                  WHERE 1=1";
        
        // Apply filters
        if ($search !== '') {
            $search = $conn->real_escape_string($search);
            $query .= " AND (f.name LIKE '%$search%' OR f.email LIKE '%$search%' OR f.phone LIKE '%$search%')";
        }
        
        if ($status !== '') {
            $query .= " AND u.is_active = " . intval($status);
        }
        
        $query .= " ORDER BY f.faculty_id ASC";
        
        $result = $conn->query($query);
        while ($row = $result->fetch_assoc()) {
            $status = $row['is_active'] ? 'Active' : 'Inactive';
            $passwordStatus = $row['temp_passkey'] ? 'Not Set (Key: ' . $row['temp_passkey'] . ')' : 'Set';
            
            fputcsv($output, [
                $row['faculty_id'],
                $row['email'],
                $row['name'],
                $row['phone'],
                $row['specialization'],
                $row['experience'],
                $status,
                $passwordStatus
            ]);
        }
        
    } elseif ($userType === 'admins') {
        fputcsv($output, ['Admin ID', 'Email', 'Name', 'Phone', 'Status', 'Password Status']);
        
        $query = "SELECT a.admin_id, a.name, a.email, a.phone, a.temp_passkey,
                         u.is_active
                  FROM admin a
                  JOIN users u ON a.user_id = u.id
                  WHERE 1=1";
        
        // Apply filters
        if ($search !== '') {
            $search = $conn->real_escape_string($search);
            $query .= " AND (a.name LIKE '%$search%' OR a.email LIKE '%$search%' OR a.phone LIKE '%$search%')";
        }
        
        if ($status !== '') {
            $query .= " AND u.is_active = " . intval($status);
        }
        
        $query .= " ORDER BY a.admin_id ASC";
        
        $result = $conn->query($query);
        while ($row = $result->fetch_assoc()) {
            $status = $row['is_active'] ? 'Active' : 'Inactive';
            $passwordStatus = $row['temp_passkey'] ? 'Not Set (Key: ' . $row['temp_passkey'] . ')' : 'Set';
            
            fputcsv($output, [
                $row['admin_id'],
                $row['email'],
                $row['name'],
                $row['phone'],
                $status,
                $passwordStatus
            ]);
        }
    }
    
    fclose($output);
    exit();
}

echo json_encode(['success' => false, 'message' => 'Invalid action']);
?>
