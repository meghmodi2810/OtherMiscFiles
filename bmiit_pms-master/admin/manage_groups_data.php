<?php
session_start();
require_once '../db.php';
// Require admin session
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') != 'admin') {
    exit("Access denied");
}

$search = $_GET['search'] ?? '';
$semester = $_GET['semester'] ?? '';
$classFilter = $_GET['class'] ?? '';
$status = $_GET['status'] ?? '';

// Build query - no projects/faculty columns
$query = "SELECT g.group_id, g.finalized, g.created_at,
                 s.student_id as leader_id, s.name as leader_name, u.username as leader_username,
                 c.name as class_name, sem.semester_no, co.name as course_name, sem.year, sem.id as semester_id,
                 sc.team_size,
                 COUNT(gm.member_id) as member_count
          FROM groups g
          JOIN students s ON g.leader_id = s.student_id
          JOIN users u ON s.user_id = u.id
          LEFT JOIN classes c ON g.class_id = c.id
          JOIN semesters sem ON g.semester_id = sem.id
          JOIN courses co ON sem.course_id = co.id
          LEFT JOIN semester_config sc ON sem.id = sc.semester_id
          LEFT JOIN group_members gm ON g.group_id = gm.group_id
          WHERE 1=1";

if ($search !== '') {
    $search = $conn->real_escape_string($search);
    $query .= " AND (g.group_id LIKE '%$search%' OR s.name LIKE '%$search%' OR u.username LIKE '%$search%')";
}

if ($semester !== '') {
    $query .= " AND sem.id = " . intval($semester);
}

if ($classFilter !== '') {
    $query .= " AND c.id = " . intval($classFilter);
}

if ($status !== '') {
    $query .= " AND g.finalized = " . intval($status);
}

$query .= " GROUP BY g.group_id, g.finalized, g.created_at, s.student_id, s.name, u.username, 
                     c.name, sem.semester_no, co.name, sem.year, sem.id, sc.team_size
            ORDER BY g.group_id ASC";

$result = $conn->query($query);

if (!$result) {
    echo "Query failed: " . $conn->error . "<br>";
    exit;
}

echo '<table>';
echo '<thead>';
echo '<tr>';
echo '<th class="checkbox-cell"><input type="checkbox" id="groups-select-all" onchange="toggleSelectAll()"></th>';
echo '<th>Group #</th>';
echo '<th>Leader</th>';
echo '<th>Members</th>';
echo '<th>Class</th>';
echo '<th>Semester</th>';
echo '<th>Status</th>';
echo '<th>Actions</th>';
echo '</tr>';
echo '</thead>';
echo '<tbody>';

if ($result && $result->num_rows > 0) {
    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    
    // Sort by semester_id, then by group_id
    usort($rows, function($a, $b) {
        if ($a['semester_id'] == $b['semester_id']) {
            return $a['group_id'] <=> $b['group_id'];
        }
        return $a['semester_id'] <=> $b['semester_id'];
    });
    
    // Assign group_number per semester
    $current_sem = null;
    $group_num = 0;
    foreach ($rows as &$row) {
        if ($row['semester_id'] != $current_sem) {
            $current_sem = $row['semester_id'];
            $group_num = 1;
        } else {
            $group_num++;
        }
        $row['group_number'] = $group_num;
    }
    
    foreach ($rows as $row) {
        $statusBadge = $row['finalized'] ? '<span class="badge badge-finalized">Finalized</span>' : '<span class="badge badge-not-finalized">Not Finalized</span>';
        
        $teamSize = $row['team_size'] ?? 4; // Default to 4 if not set
        $memberCount = $row['member_count'];
        
        if ($memberCount >= $teamSize) {
            $membersBadge = '<span class="badge badge-full">✓ ' . $memberCount . '/' . $teamSize . '</span>';
        } else {
            $membersBadge = '<span class="badge badge-incomplete">⚠️ ' . $memberCount . '/' . $teamSize . '</span>';
        }
        
        $className = $row['class_name'] ? $row['class_name'] : 'Inter-class';
        $semester = $row['course_name'] . ' - Sem ' . $row['semester_no'] . ' ' . $row['year'];
        
        echo '<tr>';
        echo '<td class="checkbox-cell"><input type="checkbox" name="group_ids[]" value="' . $row['group_id'] . '" onchange="updateBulkInfo()"></td>';
        echo '<td><strong>' . $row['group_number'] . '</strong></td>';
        echo '<td>' . $row['leader_name'] . '<br><small style="color: var(--text-light);">' . $row['leader_username'] . '</small></td>';
        echo '<td>' . $membersBadge . '</td>';
        echo '<td>' . $className . '</td>';
        echo '<td>' . $semester . '</td>';
        echo '<td>' . $statusBadge . '</td>';
        echo '<td>';
        echo '<div class="action-buttons">';
        echo '<button class="btn-action btn-view" onclick="viewMembers(' . $row['group_id'] . ')"><i data-feather="users"></i> View Members</button>';
        
        if (!$row['finalized']) {
            echo '<button class="btn-action btn-transfer" onclick="openTransferModal(' . $row['group_id'] . ')"><i data-feather="repeat"></i> Transfer Leader</button>';
            echo '<button class="btn-action btn-finalize" onclick="forceFinalize(' . $row['group_id'] . ', ' . $memberCount . ', ' . $teamSize . ')"><i data-feather="check-circle"></i> Finalize Group</button>';
        } else {
            echo '<button class="btn-action btn-transfer" onclick="openTransferModal(' . $row['group_id'] . ')"><i data-feather="repeat"></i> Transfer Leader</button>';
        }
        
        echo '<button class="btn-action btn-dissolve" onclick="dissolveGroup(' . $row['group_id'] . ')"><i data-feather="trash-2"></i> Dissolve</button>';
        echo '</div>';
        echo '</td>';
        echo '</tr>';
    }
} else {
    echo '<tr><td colspan="8" style="text-align: center; padding: 40px; color: var(--text-light);">No groups found</td></tr>';
}

echo '</tbody>';
echo '</table>';
?>
