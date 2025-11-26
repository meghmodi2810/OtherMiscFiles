<?php
session_start();
require_once '../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    exit("Access denied");
}

$userType = $_GET['type'] ?? 'students';
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';
$semester = $_GET['semester'] ?? '';
$classFilter = $_GET['class'] ?? '';
$sortBy = $_GET['sort'] ?? '';
$sortOrder = $_GET['order'] ?? 'ASC';

// Pagination
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 50;
$offset = ($page - 1) * $perPage;

// Sanitize sort order
$sortOrder = strtoupper($sortOrder) === 'DESC' ? 'DESC' : 'ASC';

// Build query based on user type
if ($userType === 'students') {
    // Count query for pagination
    $countQuery = "SELECT COUNT(*) as total
              FROM students s
              JOIN users u ON s.user_id = u.id
              JOIN classes c ON s.class_id = c.id
              JOIN semesters sem ON c.semester_id = sem.id
              JOIN courses co ON sem.course_id = co.id
              WHERE 1=1";
    
    $params = [];
    $types = '';
    
    if ($search !== '') {
        $searchParam = '%' . $search . '%';
        $countQuery .= " AND (s.name LIKE ? OR s.email LIKE ? OR u.username LIKE ? OR s.phone LIKE ?)";
        $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam]);
        $types .= 'ssss';
    }
    
    if ($status !== '') {
        $countQuery .= " AND u.is_active = ?";
        $params[] = intval($status);
        $types .= 'i';
    }
    
    if ($semester !== '') {
        $countQuery .= " AND sem.id = ?";
        $params[] = intval($semester);
        $types .= 'i';
    }
    
    if ($classFilter !== '') {
        $countQuery .= " AND c.id = ?";
        $params[] = intval($classFilter);
        $types .= 'i';
    }
    
    // Get total count
    $stmt = $conn->prepare($countQuery);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $countResult = $stmt->get_result();
    $totalRecords = $countResult->fetch_assoc()['total'];
    $stmt->close();
    
    // Main query with sorting
    $query = "SELECT s.student_id, s.user_id, s.name, s.email, s.phone, s.temp_passkey, s.class_id,
                     u.username, u.is_active,
                     c.name as class_name, sem.semester_no, co.name as course_name, sem.year
              FROM students s
              JOIN users u ON s.user_id = u.id
              JOIN classes c ON s.class_id = c.id
              JOIN semesters sem ON c.semester_id = sem.id
              JOIN courses co ON sem.course_id = co.id
              WHERE 1=1";
    
    // Rebuild params for main query (reset from count query)
    $params = [];
    $types = '';
    
    if ($search !== '') {
        $searchParam = '%' . $search . '%';
        $query .= " AND (s.name LIKE ? OR s.email LIKE ? OR u.username LIKE ? OR s.phone LIKE ?)";
        $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam]);
        $types .= 'ssss';
    }
    
    if ($status !== '') {
        $query .= " AND u.is_active = ?";
        $params[] = intval($status);
        $types .= 'i';
    }
    
    if ($semester !== '') {
        $query .= " AND sem.id = ?";
        $params[] = intval($semester);
        $types .= 'i';
    }
    
    if ($classFilter !== '') {
        $query .= " AND c.id = ?";
        $params[] = intval($classFilter);
        $types .= 'i';
    }
    
    // Add sorting - Only enrollment and name
    $allowedSortColumns = ['username' => 'u.username', 'name' => 's.name'];
    if (isset($allowedSortColumns[$sortBy])) {
        $query .= " ORDER BY {$allowedSortColumns[$sortBy]} $sortOrder";
    } else {
        $query .= " ORDER BY u.username ASC";
    }
    
    $query .= " LIMIT ? OFFSET ?";
    
    // Add pagination params
    $params[] = $perPage;
    $params[] = $offset;
    $types .= 'ii';
    
    $stmt = $conn->prepare($query);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Return total count as data attribute
    echo '<div class="table-info" data-total="' . $totalRecords . '">';
    echo '<i data-feather="users" style="width: 20px; height: 20px; color: white;"></i>';
    echo '<p class="results-count"><strong>' . number_format($totalRecords) . '</strong> student' . ($totalRecords != 1 ? 's' : '') . ' found</p>';
    echo '</div>';
    
    echo '<table>';
    echo '<thead>';
    echo '<tr>';
    echo '<th class="checkbox-cell"><input type="checkbox" id="students-select-all" onchange="toggleSelectAll(\'students\')"></th>';
    echo '<th class="sortable" data-sort="username">Enrollment No. <i data-feather="chevron-down"></i></th>';
    echo '<th class="sortable" data-sort="name">Name <i data-feather="chevron-down"></i></th>';
    echo '<th>Email</th>';
    echo '<th>Phone</th>';
    echo '<th>Class</th>';
    echo '<th>Semester</th>';
    echo '<th>Status</th>';
    echo '<th>Password</th>';
    echo '<th>Actions</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $statusBadge = $row['is_active'] ? '<span class="badge badge-active">Active</span>' : '<span class="badge badge-inactive">Inactive</span>';
            $passwordBadge = $row['temp_passkey'] ? '<span class="badge badge-passkey">🔑 ' . $row['temp_passkey'] . '</span>' : '<span class="badge badge-set">✓ Password Set</span>';
            
            if ($row['is_active']) {
                $toggleButton = '<button class="btn-action btn-toggle-inactive" onclick="toggleActive(' . $row['user_id'] . ', \'students\', ' . $row['is_active'] . ')"><i data-feather="x-circle"></i> Deactivate</button>';
            } else {
                $toggleButton = '<button class="btn-action btn-toggle-active" onclick="toggleActive(' . $row['user_id'] . ', \'students\', ' . $row['is_active'] . ')"><i data-feather="check-circle"></i> Activate</button>';
            }
            
            echo '<tr class="hoverable-row">';
            echo '<td class="checkbox-cell"><input type="checkbox" name="user_ids[]" value="' . $row['user_id'] . '" data-active="' . $row['is_active'] . '" onchange="updateBulkInfo(\'students\')"></td>';
            echo '<td>' . $row['username'] . '</td>';
            echo '<td>' . $row['name'] . '</td>';
            echo '<td><span class="truncate">' . $row['email'] . '</span></td>';
            echo '<td>' . $row['phone'] . '</td>';
            echo '<td>' . $row['class_name'] . '</td>';
            echo '<td>' . $row['course_name'] . ' - Sem ' . $row['semester_no'] . ' ' . $row['year'] . '</td>';
            echo '<td>' . $statusBadge . '</td>';
            echo '<td>' . $passwordBadge . '</td>';
            echo '<td>';
            echo '<div class="action-buttons">';
            echo $toggleButton;
            echo '<button class="btn-action btn-reset" onclick="resetPassword(' . $row['user_id'] . ', \'students\', \'' . $row['email'] . '\')"><i data-feather="refresh-cw"></i> Reset Password</button>';
            echo '<button class="btn-action btn-edit" onclick="openEditModal(' . $row['user_id'] . ', \'students\')"><i data-feather="edit-2"></i> Edit</button>';
            echo '</div>';
            echo '</td>';
            echo '</tr>';
        }
    } else {
        echo '<tr><td colspan="10" style="text-align: center; padding: 40px; color: var(--text-light);">No students found</td></tr>';
    }
    
    echo '</tbody>';
    echo '</table>';
    
    // Pagination controls
    $totalPages = ceil($totalRecords / $perPage);
    if ($totalPages > 1) {
        echo '<div class="pagination">';
        
        if ($page > 1) {
            echo '<button class="btn-page" onclick="loadUsers(\'students\', ' . ($page - 1) . ')">← Previous</button>';
        }
        
        echo '<span class="page-info">Page ' . $page . ' of ' . $totalPages . '</span>';
        
        if ($page < $totalPages) {
            echo '<button class="btn-page" onclick="loadUsers(\'students\', ' . ($page + 1) . ')">Next →</button>';
        }
        
        echo '</div>';
    }
    
    $stmt->close();
    
} elseif ($userType === 'faculty') {
    // Count query
    $countQuery = "SELECT COUNT(*) as total
              FROM faculty f
              JOIN users u ON f.user_id = u.id
              WHERE 1=1";
    
    $params = [];
    $types = '';
    
    if ($search !== '') {
        $searchParam = '%' . $search . '%';
        $countQuery .= " AND (f.name LIKE ? OR f.email LIKE ? OR u.username LIKE ? OR f.phone LIKE ?)";
        $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam]);
        $types .= 'ssss';
    }
    
    if ($status !== '') {
        $countQuery .= " AND u.is_active = ?";
        $params[] = intval($status);
        $types .= 'i';
    }
    
    // Get total count
    $stmt = $conn->prepare($countQuery);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $countResult = $stmt->get_result();
    $totalRecords = $countResult->fetch_assoc()['total'];
    $stmt->close();
    
    // Main query
    $query = "SELECT f.faculty_id, f.user_id, f.name, f.email, f.phone, f.temp_passkey, f.specialization, f.experience,
                     u.username, u.is_active
              FROM faculty f
              JOIN users u ON f.user_id = u.id
              WHERE 1=1";
    
    // Reset params for main query
    $params = [];
    $types = '';
    
    if ($search !== '') {
        $searchParam = '%' . $search . '%';
        $query .= " AND (f.name LIKE ? OR f.email LIKE ? OR u.username LIKE ? OR f.phone LIKE ?)";
        $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam]);
        $types .= 'ssss';
    }
    
    if ($status !== '') {
        $query .= " AND u.is_active = ?";
        $params[] = intval($status);
        $types .= 'i';
    }
    
    // Add sorting - Only name
    $allowedSortColumns = ['name' => 'f.name'];
    if (isset($allowedSortColumns[$sortBy])) {
        $query .= " ORDER BY {$allowedSortColumns[$sortBy]} $sortOrder";
    } else {
        $query .= " ORDER BY f.faculty_id ASC";
    }
    
    $query .= " LIMIT ? OFFSET ?";
    $params[] = $perPage;
    $params[] = $offset;
    $types .= 'ii';
    
    $stmt = $conn->prepare($query);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Display count
    echo '<div class="table-info" data-total="' . $totalRecords . '">';
    echo '<i data-feather="users" style="width: 20px; height: 20px; color: white;"></i>';
    echo '<p class="results-count"><strong>' . number_format($totalRecords) . '</strong> faculty member' . ($totalRecords != 1 ? 's' : '') . ' found</p>';
    echo '</div>';
    
    echo '<table>';
    echo '<thead>';
    echo '<tr>';
    echo '<th class="checkbox-cell"><input type="checkbox" id="faculty-select-all" onchange="toggleSelectAll(\'faculty\')"></th>';
    echo '<th>Email</th>';
    echo '<th class="sortable" data-sort="name">Name <i data-feather="chevron-down"></i></th>';
    echo '<th>Phone</th>';
    echo '<th>Specialization</th>';
    echo '<th>Experience</th>';
    echo '<th>Status</th>';
    echo '<th>Password</th>';
    echo '<th>Actions</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $statusBadge = $row['is_active'] ? '<span class="badge badge-active">Active</span>' : '<span class="badge badge-inactive">Inactive</span>';
            $passwordBadge = $row['temp_passkey'] ? '<span class="badge badge-passkey">🔑 ' . $row['temp_passkey'] . '</span>' : '<span class="badge badge-set">✓ Password Set</span>';
            
            if ($row['is_active']) {
                $toggleButton = '<button class="btn-action btn-toggle-inactive" onclick="toggleActive(' . $row['user_id'] . ', \'faculty\', ' . $row['is_active'] . ')"><i data-feather="x-circle"></i> Deactivate</button>';
            } else {
                $toggleButton = '<button class="btn-action btn-toggle-active" onclick="toggleActive(' . $row['user_id'] . ', \'faculty\', ' . $row['is_active'] . ')"><i data-feather="check-circle"></i> Activate</button>';
            }
            
            echo '<tr class="hoverable-row">';
            echo '<td class="checkbox-cell"><input type="checkbox" name="user_ids[]" value="' . $row['user_id'] . '" data-active="' . $row['is_active'] . '" onchange="updateBulkInfo(\'faculty\')"></td>';
            echo '<td><span class="truncate">' . $row['email'] . '</span></td>';
            echo '<td>' . $row['name'] . '</td>';
            echo '<td>' . $row['phone'] . '</td>';
            echo '<td>' . $row['specialization'] . '</td>';
            echo '<td>' . $row['experience'] . ' years</td>';
            echo '<td>' . $statusBadge . '</td>';
            echo '<td>' . $passwordBadge . '</td>';
            echo '<td>';
            echo '<div class="action-buttons">';
            echo $toggleButton;
            echo '<button class="btn-action btn-reset" onclick="resetPassword(' . $row['user_id'] . ', \'faculty\', \'' . $row['email'] . '\')"><i data-feather="refresh-cw"></i> Reset Password</button>';
            echo '<button class="btn-action btn-edit" onclick="openEditModal(' . $row['user_id'] . ', \'faculty\')"><i data-feather="edit-2"></i> Edit</button>';
            echo '</div>';
            echo '</td>';
            echo '</tr>';
        }
    } else {
        echo '<tr><td colspan="9" style="text-align: center; padding: 40px; color: var(--text-light);">No faculty found</td></tr>';
    }
    
    echo '</tbody>';
    echo '</table>';
    
    // Pagination
    $totalPages = ceil($totalRecords / $perPage);
    if ($totalPages > 1) {
        echo '<div class="pagination">';
        
        if ($page > 1) {
            echo '<button class="btn-page" onclick="loadUsers(\'faculty\', ' . ($page - 1) . ')">← Previous</button>';
        }
        
        echo '<span class="page-info">Page ' . $page . ' of ' . $totalPages . '</span>';
        
        if ($page < $totalPages) {
            echo '<button class="btn-page" onclick="loadUsers(\'faculty\', ' . ($page + 1) . ')">Next →</button>';
        }
        
        echo '</div>';
    }
    
    $stmt->close();
    
} elseif ($userType === 'admins') {
    // Count query
    $countQuery = "SELECT COUNT(*) as total
              FROM admin a
              JOIN users u ON a.user_id = u.id
              WHERE 1=1";
    
    $params = [];
    $types = '';
    
    if ($search !== '') {
        $searchParam = '%' . $search . '%';
        $countQuery .= " AND (a.name LIKE ? OR a.email LIKE ? OR u.username LIKE ? OR a.phone LIKE ?)";
        $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam]);
        $types .= 'ssss';
    }
    
    if ($status !== '') {
        $countQuery .= " AND u.is_active = ?";
        $params[] = intval($status);
        $types .= 'i';
    }
    
    // Get total count
    $stmt = $conn->prepare($countQuery);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $countResult = $stmt->get_result();
    $totalRecords = $countResult->fetch_assoc()['total'];
    $stmt->close();
    
    // Main query
    $query = "SELECT a.admin_id, a.user_id, a.name, a.email, a.phone, a.temp_passkey,
                     u.username, u.is_active
              FROM admin a
              JOIN users u ON a.user_id = u.id
              WHERE 1=1";
    
    // Reset params for main query
    $params = [];
    $types = '';
    
    if ($search !== '') {
        $searchParam = '%' . $search . '%';
        $query .= " AND (a.name LIKE ? OR a.email LIKE ? OR u.username LIKE ? OR a.phone LIKE ?)";
        $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam]);
        $types .= 'ssss';
    }
    
    if ($status !== '') {
        $query .= " AND u.is_active = ?";
        $params[] = intval($status);
        $types .= 'i';
    }
    
    // Add sorting - Only name
    $allowedSortColumns = ['name' => 'a.name'];
    if (isset($allowedSortColumns[$sortBy])) {
        $query .= " ORDER BY {$allowedSortColumns[$sortBy]} $sortOrder";
    } else {
        $query .= " ORDER BY a.admin_id ASC";
    }
    
    $query .= " LIMIT ? OFFSET ?";
    $params[] = $perPage;
    $params[] = $offset;
    $types .= 'ii';
    
    $stmt = $conn->prepare($query);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Display count
    echo '<div class="table-info" data-total="' . $totalRecords . '">';
    echo '<i data-feather="shield" style="width: 20px; height: 20px; color: white;"></i>';
    echo '<p class="results-count"><strong>' . number_format($totalRecords) . '</strong> administrator' . ($totalRecords != 1 ? 's' : '') . ' found</p>';
    echo '</div>';
    
    echo '<table>';
    echo '<thead>';
    echo '<tr>';
    echo '<th class="checkbox-cell"><input type="checkbox" id="admins-select-all" onchange="toggleSelectAll(\'admins\')"></th>';
    echo '<th>Email</th>';
    echo '<th class="sortable" data-sort="name">Name <i data-feather="chevron-down"></i></th>';
    echo '<th>Phone</th>';
    echo '<th>Status</th>';
    echo '<th>Password</th>';
    echo '<th>Actions</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $statusBadge = $row['is_active'] ? '<span class="badge badge-active">Active</span>' : '<span class="badge badge-inactive">Inactive</span>';
            $passwordBadge = $row['temp_passkey'] ? '<span class="badge badge-passkey">🔑 ' . $row['temp_passkey'] . '</span>' : '<span class="badge badge-set">✓ Password Set</span>';
            
            if ($row['is_active']) {
                $toggleButton = '<button class="btn-action btn-toggle-inactive" onclick="toggleActive(' . $row['user_id'] . ', \'admins\', ' . $row['is_active'] . ')"><i data-feather="x-circle"></i> Deactivate</button>';
            } else {
                $toggleButton = '<button class="btn-action btn-toggle-active" onclick="toggleActive(' . $row['user_id'] . ', \'admins\', ' . $row['is_active'] . ')"><i data-feather="check-circle"></i> Activate</button>';
            }
            
            echo '<tr class="hoverable-row">';
            echo '<td class="checkbox-cell"><input type="checkbox" name="user_ids[]" value="' . $row['user_id'] . '" data-active="' . $row['is_active'] . '" onchange="updateBulkInfo(\'admins\')"></td>';
            echo '<td><span class="truncate">' . $row['email'] . '</span></td>';
            echo '<td>' . $row['name'] . '</td>';
            echo '<td>' . $row['phone'] . '</td>';
            echo '<td>' . $statusBadge . '</td>';
            echo '<td>' . $passwordBadge . '</td>';
            echo '<td>';
            echo '<div class="action-buttons">';
            echo $toggleButton;
            echo '<button class="btn-action btn-reset" onclick="resetPassword(' . $row['user_id'] . ', \'admins\', \'' . $row['email'] . '\')"><i data-feather="refresh-cw"></i> Reset Password</button>';
            echo '<button class="btn-action btn-edit" onclick="openEditModal(' . $row['user_id'] . ', \'admins\')"><i data-feather="edit-2"></i> Edit</button>';
            echo '</div>';
            echo '</td>';
            echo '</tr>';
        }
    } else {
        echo '<tr><td colspan="7" style="text-align: center; padding: 40px; color: var(--text-light);">No administrators found</td></tr>';
    }
    
    echo '</tbody>';
    echo '</table>';
    
    // Pagination
    $totalPages = ceil($totalRecords / $perPage);
    if ($totalPages > 1) {
        echo '<div class="pagination">';
        
        if ($page > 1) {
            echo '<button class="btn-page" onclick="loadUsers(\'admins\', ' . ($page - 1) . ')">← Previous</button>';
        }
        
        echo '<span class="page-info">Page ' . $page . ' of ' . $totalPages . '</span>';
        
        if ($page < $totalPages) {
            echo '<button class="btn-page" onclick="loadUsers(\'admins\', ' . ($page + 1) . ')">Next →</button>';
        }
        
        echo '</div>';
    }
    
    $stmt->close();
}
?>
