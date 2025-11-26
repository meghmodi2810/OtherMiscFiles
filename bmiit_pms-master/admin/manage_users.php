<?php
session_start();
require_once '../db.php';

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

if ($_SESSION['user_role'] != 'admin') {
    exit("Access denied");
}

// Setup session user array for new includes
if (!isset($_SESSION['user'])) {
    $_SESSION['user'] = [
        'user_id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'] ?? 'admin',
        'role' => $_SESSION['user_role'],
        'name' => $_SESSION['username'] ?? 'Administrator'
    ];
}

$page_title = 'Manage Users';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

// Fetch semesters for filters
$semestersQuery = "SELECT s.id, c.name as course_name, s.semester_no, s.year 
                   FROM semesters s 
                   JOIN courses c ON s.course_id = c.id 
                   ORDER BY c.name, s.semester_no";
$semestersResult = $conn->query($semestersQuery);
$semesters = [];
while ($row = $semestersResult->fetch_assoc()) {
    $semesters[] = $row;
}

// Fetch classes for filters
$classesQuery = "SELECT c.id, c.name, s.id as semester_id, co.name as course_name, s.semester_no 
                 FROM classes c 
                 JOIN semesters s ON c.semester_id = s.id 
                 JOIN courses co ON s.course_id = co.id 
                 ORDER BY co.name, s.semester_no, c.name";
$classesResult = $conn->query($classesQuery);
$classes = [];
while ($row = $classesResult->fetch_assoc()) {
    $classes[] = $row;
}
?>

<style>
.tabs-container {
    background: var(--card);
    border-radius: var(--radius);
    padding: 0;
    margin-bottom: 24px;
    box-shadow: var(--shadow);
}

.tabs {
    display: flex;
    border-bottom: 2px solid var(--border);
}

.tab-button {
    flex: 1;
    padding: 16px 24px;
    background: none;
    border: none;
    cursor: pointer;
    font-size: 15px;
    font-weight: 600;
    color: var(--text-light);
    transition: var(--transition);
    border-bottom: 3px solid transparent;
    margin-bottom: -2px;
}

.tab-button:hover {
    color: var(--primary);
    background: var(--bg);
}

.tab-button.active {
    color: var(--primary);
    border-bottom-color: var(--primary);
}

.tab-content {
    display: none;
    padding: 24px;
}

.tab-content.active {
    display: block;
}

.filters-bar {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    margin-bottom: 20px;
    align-items: center;
}

.filter-group {
    display: flex;
    gap: 8px;
    align-items: center;
}

.filter-group label {
    font-size: 14px;
    font-weight: 500;
    color: var(--text);
}

.filter-group select, .filter-group input {
    padding: 8px 12px;
    border: 1px solid var(--border);
    border-radius: 8px;
    font-size: 14px;
    min-width: 180px;
}

.search-box {
    flex: 1;
    min-width: 250px;
}

.search-box input {
    width: 100%;
    padding: 10px 16px;
    border: 1px solid var(--border);
    border-radius: 8px;
    font-size: 14px;
}

.actions-bar {
    display: flex;
    gap: 12px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}

.btn-small {
    padding: 10px 16px;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: var(--transition);
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-small i {
    width: 18px;
    height: 18px;
}

.btn-activate {
    background: var(--primary);
    color: white;
}

.btn-activate:hover {
    background: #0A3E82;
    transform: translateY(-1px);
}

.btn-activate:disabled {
    background: #9CA3AF;
    cursor: not-allowed;
    transform: none;
}

.btn-deactivate {
    background: #DC2626;
    color: white;
}

.btn-deactivate:hover {
    background: #B91C1C;
    transform: translateY(-1px);
}

.btn-deactivate:disabled {
    background: #9CA3AF;
    cursor: not-allowed;
    transform: none;
}

.btn-export {
    background: var(--secondary);
    color: white;
}

.btn-export:hover {
    background: #138996;
    transform: translateY(-1px);
}

.table-container {
    overflow-x: auto;
    border-radius: var(--radius);
    border: 1px solid var(--border);
}

table {
    width: 100%;
    border-collapse: collapse;
    background: white;
}

th {
    background: var(--bg);
    padding: 12px 16px;
    text-align: left;
    font-size: 13px;
    font-weight: 600;
    color: var(--text);
    border-bottom: 2px solid var(--border);
}

td {
    padding: 12px 16px;
    border-bottom: 1px solid var(--border);
    font-size: 14px;
}

tr:hover {
    background: var(--bg);
}

.hoverable-row {
    transition: background-color 0.2s ease;
}

.hoverable-row:hover {
    background: #F0F9FF !important;
    cursor: pointer;
}

.sortable {
    cursor: pointer;
    user-select: none;
    transition: background-color 0.2s ease;
}

.sortable:hover {
    background: #E5E7EB !important;
}

.sortable i {
    vertical-align: middle;
    margin-left: 4px;
}

.truncate {
    display: inline-block;
    max-width: 200px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

/* Tooltip CSS */
[title] {
    position: relative;
}

.table-info {
    margin-bottom: 16px;
    padding: 12px 16px;
    background: linear-gradient(135deg, #0B4DA0 0%, #19B4C4 100%);
    border-radius: 8px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.results-count {
    font-size: 14px;
    color: white;
    margin: 0;
    font-weight: 500;
}

.results-count strong {
    color: white;
    font-size: 22px;
    font-weight: 700;
}

/* Pagination styles */
.pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 16px;
    margin-top: 20px;
    padding: 16px;
    background: white;
    border-radius: var(--radius);
    border: 1px solid var(--border);
}

.btn-page {
    padding: 8px 16px;
    border: 1px solid var(--border);
    background: white;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    color: var(--primary);
    cursor: pointer;
    transition: var(--transition);
}

.btn-page:hover {
    background: var(--primary);
    color: white;
    border-color: var(--primary);
}

.page-info {
    font-size: 14px;
    color: var(--text);
    font-weight: 500;
}


.badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}

.badge-active {
    background: #D1FAE5;
    color: #065F46;
}

.badge-inactive {
    background: #FEE2E2;
    color: #991B1B;
}

.badge-passkey {
    background: #FFF9E6;
    color: #92400E;
}

.badge-set {
    background: #D1FAE5;
    color: #065F46;
}

.action-buttons {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.btn-action {
    padding: 6px 12px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    transition: var(--transition);
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 13px;
    font-weight: 500;
    white-space: nowrap;
}

.btn-action i {
    width: 16px;
    height: 16px;
}

.btn-action.btn-toggle-active {
    background: #E8F4FD;
    color: #0B4DA0;
}

.btn-action.btn-toggle-active:hover {
    background: #D1E7F9;
}

.btn-action.btn-toggle-inactive {
    background: #FEE2E2;
    color: #991B1B;
}

.btn-action.btn-toggle-inactive:hover {
    background: #FECACA;
}

.btn-action.btn-reset {
    background: #FFF9E6;
    color: #92400E;
}

.btn-action.btn-reset:hover {
    background: #FFF4CC;
}

.btn-action.btn-edit {
    background: #E8F4FD;
    color: #0B4DA0;
}

.btn-action.btn-edit:hover {
    background: #D1E7F9;
}

.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    z-index: 9999;
    align-items: center;
    justify-content: center;
}

.modal.active {
    display: flex;
}

.modal-content {
    background: white;
    border-radius: var(--radius);
    padding: 24px;
    width: 90%;
    max-width: 600px;
    max-height: 90vh;
    overflow-y: auto;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.modal-header h2 {
    font-size: 20px;
    color: var(--text);
}

.close-modal {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: var(--text-light);
}

.close-modal:hover {
    color: var(--text);
}

.form-group {
    margin-bottom: 16px;
}

.form-group label {
    display: block;
    margin-bottom: 6px;
    font-size: 14px;
    font-weight: 500;
    color: var(--text);
}

.form-group input, .form-group select {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid var(--border);
    border-radius: 8px;
    font-size: 14px;
}

.form-group .help-text {
    font-size: 12px;
    color: var(--text-light);
    margin-top: 4px;
}

.modal-actions {
    display: flex;
    gap: 12px;
    justify-content: flex-end;
    margin-top: 24px;
}

.notice-card {
    background: #E8F4FD;
    border-left: 4px solid #0B4DA0;
    padding: 16px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.notice-card h3 {
    font-size: 16px;
    margin-bottom: 8px;
    color: #0B4DA0;
}

.notice-card p {
    font-size: 14px;
    color: var(--text-light);
    margin-bottom: 12px;
}

.notice-links {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
}

.notice-links a {
    font-size: 14px;
    font-weight: 600;
    color: #0B4DA0;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.notice-links a:hover {
    color: #19B4C4;
    text-decoration: underline;
}

.loading {
    text-align: center;
    padding: 40px;
    color: var(--text-light);
}

.checkbox-cell {
    width: 40px;
    text-align: center;
}

input[type="checkbox"] {
    width: 18px;
    height: 18px;
    cursor: pointer;
}

.bulk-actions-info {
    background: #FEF3C7;
    padding: 12px 16px;
    border-radius: 8px;
    margin-bottom: 16px;
    font-size: 14px;
    color: #92400E;
    display: none;
}

.bulk-actions-info.active {
    display: block;
}

/* (Legacy per-page toast styles removed) Use global site alerts via /js/alerts.js and /css/alerts.css */

/* Confirmation Modal */
.confirm-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    z-index: 10000;
    align-items: center;
    justify-content: center;
}

.confirm-modal.active {
    display: flex;
}

.confirm-modal-content {
    background: white;
    border-radius: 12px;
    padding: 24px;
    width: 90%;
    max-width: 450px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
}

.confirm-modal-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 16px;
}

.confirm-modal-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #FEF3C7;
    color: #92400E;
}

.confirm-modal-title {
    font-size: 18px;
    font-weight: 600;
    color: var(--text);
}

.confirm-modal-message {
    font-size: 14px;
    color: var(--text-light);
    line-height: 1.6;
    margin-bottom: 24px;
}

.confirm-modal-actions {
    display: flex;
    gap: 12px;
    justify-content: flex-end;
}

.confirm-modal-actions button {
    padding: 10px 20px;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: var(--transition);
}

.confirm-modal-actions .btn-cancel {
    background: var(--bg);
    color: var(--text);
}

.confirm-modal-actions .btn-cancel:hover {
    background: var(--border);
}

.confirm-modal-actions .btn-confirm {
    background: var(--primary);
    color: white;
}

.confirm-modal-actions .btn-confirm:hover {
    background: #0A3E82;
}

.confirm-modal-actions .btn-confirm.danger {
    background: #DC2626;
}

.confirm-modal-actions .btn-confirm.danger:hover {
    background: #B91C1C;
}
</style>

<main class="main-wrapper">
    <!-- Per-page toast container removed; using global page alerts -->
    
    <!-- Confirmation Modal -->
    <div class="confirm-modal" id="confirm-modal">
        <div class="confirm-modal-content">
            <div class="confirm-modal-header">
                <div class="confirm-modal-icon">
                    <i data-feather="alert-triangle" style="width: 20px; height: 20px;"></i>
                </div>
                <div class="confirm-modal-title" id="confirm-title">Confirm Action</div>
            </div>
            <div class="confirm-modal-message" id="confirm-message"></div>
            <div class="confirm-modal-actions">
                <button class="btn-cancel" onclick="closeConfirmModal()">Cancel</button>
                <button class="btn-confirm" id="confirm-btn" onclick="confirmAction()">Confirm</button>
            </div>
        </div>
    </div>
    
    <div class="container fade-in">
        <div class="card">
            <div class="card-header">
                <h1 class="card-title">Manage Users</h1>
                <p class="card-subtitle">View, edit, and manage all system users</p>
            </div>
        </div>

        <div class="notice-card">
            <h3>Quick Actions</h3>
            <p>Add new users to the system:</p>
            <div class="notice-links">
                <a href="add_student_manual.php"><i data-feather="user-plus" style="width:14px;height:14px;"></i> Add Student</a>
                <a href="add_student_bulk.php"><i data-feather="upload" style="width:14px;height:14px;"></i> Bulk Upload Students</a>
                <a href="add_faculty_manual.php"><i data-feather="user-plus" style="width:14px;height:14px;"></i> Add Faculty</a>
                <a href="add_faculty_bulk.php"><i data-feather="upload" style="width:14px;height:14px;"></i> Bulk Upload Faculty</a>
                <a href="add_admin_manual.php"><i data-feather="shield" style="width:14px;height:14px;"></i> Add Administrator</a>
            </div>
        </div>

        <div class="tabs-container">
            <div class="tabs">
                <button class="tab-button active" onclick="switchTab('students')">Students</button>
                <button class="tab-button" onclick="switchTab('faculty')">Faculty</button>
                <button class="tab-button" onclick="switchTab('admins')">Administrators</button>
            </div>

            <!-- Students Tab -->
            <div id="students-tab" class="tab-content active">
                <div class="filters-bar">
                    <div class="search-box">
                        <input type="search" id="students-search" placeholder="Search by name, email, enrollment..." onkeyup="searchUsers('students', this.value)">
                    </div>
                    <div class="filter-group">
                        <label>Status:</label>
                        <select id="students-status-filter" onchange="filterUsers('students')">
                            <option value="">All</option>
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Semester:</label>
                        <select id="students-semester-filter" onchange="updateClassFilter('students'); filterUsers('students')">
                            <option value="">All</option>
                            <?php foreach ($semesters as $sem): ?>
                                <option value="<?= $sem['id'] ?>">
                                    <?= "{$sem['course_name']} - Sem {$sem['semester_no']} {$sem['year']}" ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Class:</label>
                        <select id="students-class-filter" onchange="filterUsers('students')">
                            <option value="">All</option>
                        </select>
                    </div>
                </div>

                <div id="students-bulk-info" class="bulk-actions-info">
                    <strong id="students-selected-count">0</strong> user(s) selected
                </div>

                <div class="actions-bar">
                    <button class="btn-small btn-activate" onclick="bulkAction('students', 'activate')">
                        <i data-feather="check-circle"></i>
                        Activate Selected
                    </button>
                    <button class="btn-small btn-deactivate" onclick="bulkAction('students', 'deactivate')">
                        <i data-feather="x-circle"></i>
                        Deactivate Selected
                    </button>
                    <button class="btn-small btn-export" onclick="exportCSV('students')">
                        <i data-feather="download"></i>
                        <span id="students-export-text">Export to CSV</span>
                    </button>
                </div>

                <div class="table-container">
                    <div id="students-table">
                        <div class="loading">Loading students...</div>
                    </div>
                </div>
            </div>

            <!-- Faculty Tab -->
            <div id="faculty-tab" class="tab-content">
                <div class="filters-bar">
                    <div class="search-box">
                        <input type="search" id="faculty-search" placeholder="Search by name, email..." onkeyup="searchUsers('faculty', this.value)">
                    </div>
                    <div class="filter-group">
                        <label>Status:</label>
                        <select id="faculty-status-filter" onchange="filterUsers('faculty')">
                            <option value="">All</option>
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                </div>

                <div id="faculty-bulk-info" class="bulk-actions-info">
                    <strong id="faculty-selected-count">0</strong> user(s) selected
                </div>

                <div class="actions-bar">
                    <button class="btn-small btn-activate" onclick="bulkAction('faculty', 'activate')">
                        <i data-feather="check-circle"></i>
                        Activate Selected
                    </button>
                    <button class="btn-small btn-deactivate" onclick="bulkAction('faculty', 'deactivate')">
                        <i data-feather="x-circle"></i>
                        Deactivate Selected
                    </button>
                    <button class="btn-small btn-export" onclick="exportCSV('faculty')">
                        <i data-feather="download"></i>
                        <span id="faculty-export-text">Export to CSV</span>
                    </button>
                </div>

                <div class="table-container">
                    <div id="faculty-table">
                        <div class="loading">Loading faculty...</div>
                    </div>
                </div>
            </div>

            <!-- Admins Tab -->
            <div id="admins-tab" class="tab-content">
                <div class="filters-bar">
                    <div class="search-box">
                        <input type="search" id="admins-search" placeholder="Search by name, email..." onkeyup="searchUsers('admins', this.value)">
                    </div>
                    <div class="filter-group">
                        <label>Status:</label>
                        <select id="admins-status-filter" onchange="filterUsers('admins')">
                            <option value="">All</option>
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                </div>

                <div id="admins-bulk-info" class="bulk-actions-info">
                    <strong id="admins-selected-count">0</strong> user(s) selected
                </div>

                <div class="actions-bar">
                    <button class="btn-small btn-activate" onclick="bulkAction('admins', 'activate')">
                        <i data-feather="check-circle"></i>
                        Activate Selected
                    </button>
                    <button class="btn-small btn-deactivate" onclick="bulkAction('admins', 'deactivate')">
                        <i data-feather="x-circle"></i>
                        Deactivate Selected
                    </button>
                    <button class="btn-small btn-export" onclick="exportCSV('admins')">
                        <i data-feather="download"></i>
                        <span id="admins-export-text">Export to CSV</span>
                    </button>
                </div>

                <div class="table-container">
                    <div id="admins-table">
                        <div class="loading">Loading administrators...</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Edit User Modal -->
<div id="edit-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Edit User</h2>
            <button class="close-modal" onclick="closeEditModal()">&times;</button>
        </div>
        <form id="edit-form">
            <input type="hidden" id="edit-user-id" name="user_id">
            <input type="hidden" id="edit-user-type" name="user_type">
            
            <div class="form-group">
                <label>Name</label>
                <input type="text" id="edit-name" name="name" required>
            </div>
            
            <div class="form-group">
                <label>Email</label>
                <input type="email" id="edit-email" name="email" required>
            </div>
            
            <div class="form-group">
                <label>Phone</label>
                <input type="tel" id="edit-phone" name="phone" pattern="[0-9]{10}" required>
            </div>
            
            <div id="student-fields" style="display: none;">
                <div class="form-group">
                    <label>Enrollment Number</label>
                    <input type="text" id="edit-enrollment" name="enrollment" pattern="[0-9]{15}" placeholder="202307100110087" required>
                    <small class="help-text">15-digit enrollment number</small>
                </div>
                <div class="form-group">
                    <label>Semester</label>
                    <select id="edit-semester" name="semester_id" onchange="updateEditClassDropdown()">
                        <option value="">Select Semester</option>
                        <?php foreach ($semesters as $sem): ?>
                            <option value="<?= $sem['id'] ?>">
                                <?= "{$sem['course_name']} - Sem {$sem['semester_no']} {$sem['year']}" ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Class</label>
                    <select id="edit-class" name="class_id" required>
                        <option value="">Select Class</option>
                    </select>
                </div>
            </div>
            
            <div id="faculty-fields" style="display: none;">
                <div class="form-group">
                    <label>Specialization</label>
                    <input type="text" id="edit-specialization" name="specialization">
                </div>
                <div class="form-group">
                    <label>Experience (years)</label>
                    <input type="number" id="edit-experience" name="experience" min="0" max="99">
                </div>
            </div>
            
            <div class="modal-actions">
                <button type="button" class="btn-small" style="background: var(--text-light); color: white;" onclick="closeEditModal()">Cancel</button>
                <button type="submit" class="btn-small" style="background: var(--primary); color: white;">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
// Store all classes data for filtering
const allClasses = <?= json_encode($classes) ?>;
let currentTab = 'students';
let currentPage = {students: 1, faculty: 1, admins: 1};
let currentSort = {students: '', faculty: '', admins: ''};
let currentSortOrder = {students: 'ASC', faculty: 'ASC', admins: 'ASC'};
let searchTimers = {};

// Per-page toast implementation removed. Use global showAlert/showToast (from /js/alerts.js).

// Confirmation modal system
let confirmCallback = null;

// Use the global modal implementation from /js/alerts.js/includes/global_modal.php
// This wrapper keeps existing call-sites intact but delegates to the centralized modal.
function showConfirm(message, title = 'Confirm Action', isDanger = false) {
    if (window && typeof window.showConfirm === 'function') {
        return window.showConfirm({ title: title, message: message, danger: !!isDanger });
    }
    // Fallback: create a lightweight DOM-based confirm (avoid native confirm())
    return new Promise((resolve) => {
        const modal = document.createElement('div');
        modal.className = 'modal confirm-fallback active';
        modal.setAttribute('role', 'dialog');
        modal.setAttribute('aria-modal', 'true');
        modal.innerHTML = `
            <div class="modal-content">
                <div class="modal-header">
                    <h2>${title}</h2>
                    <button class="close-modal" aria-label="Close">&times;</button>
                </div>
                <div class="modal-body">${String(message).replace(/\n/g, '<br>')}</div>
                <div class="modal-actions">
                    <button class="btn-small btn-cancel">Cancel</button>
                    <button class="btn-small btn-confirm${isDanger ? ' danger' : ''}">OK</button>
                </div>
            </div>`;
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
        function onKey(e) { if (e.key === 'Escape') cleanup(false); if (e.key === 'Enter') cleanup(true); }
        btnOk.addEventListener('click', onOk);
        btnCancel.addEventListener('click', onCancel);
        btnClose.addEventListener('click', onClose);
        document.addEventListener('keydown', onKey);
    });
}

function closeConfirmModal() {
    document.getElementById('confirm-modal').classList.remove('active');
    document.body.style.overflow = '';
    if (confirmCallback) {
        confirmCallback(false);
        confirmCallback = null;
    }
}

function confirmAction() {
    document.getElementById('confirm-modal').classList.remove('active');
    document.body.style.overflow = '';
    if (confirmCallback) {
        confirmCallback(true);
        confirmCallback = null;
    }
}

// Switch between tabs
function switchTab(tabName) {
    currentTab = tabName;
    
    // Update tab buttons
    document.querySelectorAll('.tab-button').forEach(btn => {
        btn.classList.remove('active');
    });
    event.target.classList.add('active');
    
    // Update tab content
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.remove('active');
    });
    document.getElementById(tabName + '-tab').classList.add('active');
    
    // Load data for the tab
    loadUsers(tabName);
}

// Load users data with pagination and sorting
function loadUsers(userType, page = 1) {
    currentPage[userType] = page;
    const statusFilter = document.getElementById(userType + '-status-filter').value;
    const searchQuery = document.getElementById(userType + '-search').value;
    
    let url = 'manage_users_data.php?type=' + userType + '&search=' + encodeURIComponent(searchQuery) + 
              '&status=' + statusFilter + '&page=' + page;
    
    // Add sorting
    if (currentSort[userType]) {
        url += '&sort=' + currentSort[userType] + '&order=' + currentSortOrder[userType];
    }
    
    if (userType === 'students') {
        const semFilter = document.getElementById('students-semester-filter').value;
        const classFilter = document.getElementById('students-class-filter').value;
        url += '&semester=' + semFilter + '&class=' + classFilter;
    }
    
    const xhr = new XMLHttpRequest();
    xhr.open('GET', url, true);
    xhr.send();
    
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            document.getElementById(userType + '-table').innerHTML = xhr.responseText;
            updateSelectAllCheckbox(userType);
            attachSortListeners(userType);
            // Re-initialize Feather icons for dynamically loaded content
            if (typeof feather !== 'undefined') {
                feather.replace();
            }
        }
    };
}

// Debounced search - waits 300ms after user stops typing
function searchUsers(userType, searchQuery) {
    // Clear existing timer
    if (searchTimers[userType]) {
        clearTimeout(searchTimers[userType]);
    }
    
    // Set new timer
    searchTimers[userType] = setTimeout(() => {
        currentPage[userType] = 1; // Reset to first page
        loadUsers(userType, 1);
    }, 300);
}

// Filter users
function filterUsers(userType) {
    currentPage[userType] = 1; // Reset to first page
    loadUsers(userType, 1);
}

// Sort column functionality using event delegation
function attachSortListeners(userType) {
    const tableContainer = document.getElementById(userType + '-table');
    if (!tableContainer) return;
    
    // Remove any existing listener to avoid duplicates
    tableContainer.onclick = null;
    
    // Use event delegation on the container
    tableContainer.onclick = function(e) {
        // Find if clicked element or its parent is a sortable header
        let target = e.target;
        let sortableHeader = null;
        
        // Check if clicked on icon inside th
        if (target.tagName === 'I' || target.tagName === 'svg' || target.tagName === 'path') {
            sortableHeader = target.closest('.sortable');
        } else if (target.classList.contains('sortable')) {
            sortableHeader = target;
        }
        
        if (!sortableHeader) return;
        
        const sortField = sortableHeader.getAttribute('data-sort');
        if (!sortField) return;
        
        // Toggle sort order
        if (currentSort[userType] === sortField) {
            currentSortOrder[userType] = currentSortOrder[userType] === 'ASC' ? 'DESC' : 'ASC';
        } else {
            currentSort[userType] = sortField;
            currentSortOrder[userType] = 'ASC';
        }
        
        // Update sort indicators for all sortable headers
        const allSortable = tableContainer.querySelectorAll('.sortable');
        allSortable.forEach(h => {
            const icon = h.querySelector('i');
            if (icon) {
                if (h === sortableHeader) {
                    icon.setAttribute('data-feather', currentSortOrder[userType] === 'ASC' ? 'chevron-down' : 'chevron-up');
                } else {
                    icon.setAttribute('data-feather', 'chevron-down');
                }
            }
        });
        
        feather.replace();
        loadUsers(userType, currentPage[userType]);
    };
}

// Update class filter based on semester selection
function updateClassFilter(userType) {
    const semesterId = document.getElementById(userType + '-semester-filter').value;
    const classSelect = document.getElementById(userType + '-class-filter');
    
    classSelect.innerHTML = '<option value="">All</option>';
    
    if (semesterId) {
        const filteredClasses = allClasses.filter(c => c.semester_id == semesterId);
        filteredClasses.forEach(c => {
            const option = document.createElement('option');
            option.value = c.id;
            option.textContent = c.name;
            classSelect.appendChild(option);
        });
    } else {
        allClasses.forEach(c => {
            const option = document.createElement('option');
            option.value = c.id;
            option.textContent = c.course_name + ' - Sem ' + c.semester_no + ' - ' + c.name;
            classSelect.appendChild(option);
        });
    }
}

// Select All functionality
function toggleSelectAll(userType) {
    const selectAllCheckbox = document.getElementById(userType + '-select-all');
    const checkboxes = document.querySelectorAll(`#${userType}-table input[name="user_ids[]"]`);
    
    checkboxes.forEach(cb => {
        cb.checked = selectAllCheckbox.checked;
    });
    
    updateBulkInfo(userType);
}

function updateSelectAllCheckbox(userType) {
    const selectAllCheckbox = document.getElementById(userType + '-select-all');
    if (selectAllCheckbox) {
        selectAllCheckbox.checked = false;
    }
}

// Toggle active status
async function toggleActive(userId, userType, currentStatus) {
    const newStatus = currentStatus == 1 ? 0 : 1;
    const action = newStatus == 1 ? 'activate' : 'deactivate';
    
    const confirmed = await showConfirm(
        `Are you sure you want to ${action} this user?`,
        'Confirm Status Change',
        newStatus == 0
    );
    
    if (!confirmed) {
        return;
    }
    
    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'manage_users_actions.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.send('action=toggle_active&user_id=' + userId + '&user_type=' + userType + '&new_status=' + newStatus);
    
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            const response = JSON.parse(xhr.responseText);
            if (response.success) {
                        showAlert(response.message, 'success', { anchor: '.container' });
                loadUsers(userType, currentPage[userType]);
            } else {
                        showAlert(response.message, 'error', { anchor: '.container' });
            }
        }
    };
}

// Reset password
async function resetPassword(userId, userType, userEmail) {
    const confirmed = await showConfirm(
        `Are you sure you want to reset the password for this user?\n\nA new temporary passkey will be generated and sent to: ${userEmail}`,
        'Reset Password',
        false
    );
    
    if (!confirmed) {
        return;
    }
    
    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'manage_users_actions.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.send('action=reset_password&user_id=' + userId + '&user_type=' + userType);
    
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            const response = JSON.parse(xhr.responseText);
            if (response.success) {
                showAlert(response.message, 'success', { anchor: '.container' });
                loadUsers(userType);
            } else {
                showAlert(response.message, 'error', { anchor: '.container' });
            }
        }
    };
}

// Open edit modal
function openEditModal(userId, userType) {
    const xhr = new XMLHttpRequest();
    xhr.open('GET', 'manage_users_actions.php?action=get_user&user_id=' + userId + '&user_type=' + userType, true);
    xhr.send();
    
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            const user = JSON.parse(xhr.responseText);
            
            document.getElementById('edit-user-id').value = userId;
            document.getElementById('edit-user-type').value = userType;
            document.getElementById('edit-name').value = user.name;
            document.getElementById('edit-email').value = user.email;
            document.getElementById('edit-phone').value = user.phone;
            
            // Show/hide specific fields
            document.getElementById('student-fields').style.display = 'none';
            document.getElementById('faculty-fields').style.display = 'none';
            
            if (userType === 'students') {
                document.getElementById('student-fields').style.display = 'block';
                document.getElementById('edit-enrollment').value = user.enrollment;
                document.getElementById('edit-semester').value = user.semester_id;
                updateEditClassDropdown();
                setTimeout(() => {
                    document.getElementById('edit-class').value = user.class_id;
                }, 100);
            } else if (userType === 'faculty') {
                document.getElementById('faculty-fields').style.display = 'block';
                document.getElementById('edit-specialization').value = user.specialization;
                document.getElementById('edit-experience').value = user.experience;
            }
            
            document.getElementById('edit-modal').classList.add('active');
            // Prevent background scroll
            document.body.style.overflow = 'hidden';
        }
    };
}

// Close edit modal
function closeEditModal() {
    document.getElementById('edit-modal').classList.remove('active');
    // Restore background scroll
    document.body.style.overflow = '';
}

// Update class dropdown in edit modal
function updateEditClassDropdown() {
    const semesterId = document.getElementById('edit-semester').value;
    const classSelect = document.getElementById('edit-class');
    
    classSelect.innerHTML = '<option value="">Select Class</option>';
    
    if (semesterId) {
        const filteredClasses = allClasses.filter(c => c.semester_id == semesterId);
        filteredClasses.forEach(c => {
            const option = document.createElement('option');
            option.value = c.id;
            option.textContent = c.name;
            classSelect.appendChild(option);
        });
    }
}

// Handle edit form submission
document.getElementById('edit-form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    formData.append('action', 'edit_user');
    
    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'manage_users_actions.php', true);
    xhr.send(formData);
    
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            const response = JSON.parse(xhr.responseText);
            if (response.success) {
                showAlert(response.message, 'success', { anchor: '.container' });
                closeEditModal();
                loadUsers(document.getElementById('edit-user-type').value);
            } else {
                showAlert(response.message, 'error', { anchor: '.container' });
            }
        }
    };
});

// Checkbox handling
function toggleSelectAll(userType) {
    const checkboxes = document.querySelectorAll('input[name="user_ids[]"]');
    const selectAllCheckbox = document.getElementById(userType + '-select-all');
    
    checkboxes.forEach(cb => {
        cb.checked = selectAllCheckbox.checked;
    });
    
    updateBulkInfo(userType);
}

function updateSelectAllCheckbox(userType) {
    const checkboxes = document.querySelectorAll('input[name="user_ids[]"]');
    const selectAllCheckbox = document.getElementById(userType + '-select-all');
    
    if (selectAllCheckbox && checkboxes.length > 0) {
        const allChecked = Array.from(checkboxes).every(cb => cb.checked);
        selectAllCheckbox.checked = allChecked;
    }
    
    updateBulkInfo(userType);
}

function updateBulkInfo(userType) {
    const checkboxes = document.querySelectorAll('input[name="user_ids[]"]:checked');
    const count = checkboxes.length;
    
    document.getElementById(userType + '-selected-count').textContent = count;
    
    if (count > 0) {
        document.getElementById(userType + '-bulk-info').classList.add('active');
        
        // Check which users are selected (active vs inactive)
        let hasActive = false;
        let hasInactive = false;
        
        checkboxes.forEach(cb => {
            const row = cb.closest('tr');
            const statusBadge = row.querySelector('.badge-active, .badge-inactive');
            if (statusBadge) {
                if (statusBadge.classList.contains('badge-active')) {
                    hasActive = true;
                } else if (statusBadge.classList.contains('badge-inactive')) {
                    hasInactive = true;
                }
            }
        });
        
        // Enable/disable bulk action buttons based on selection
        const activateBtn = document.querySelector('#' + userType + '-tab .btn-activate');
        const deactivateBtn = document.querySelector('#' + userType + '-tab .btn-deactivate');
        
        if (activateBtn) {
            activateBtn.disabled = !hasInactive;
            activateBtn.style.opacity = hasInactive ? '1' : '0.5';
            activateBtn.style.cursor = hasInactive ? 'pointer' : 'not-allowed';
        }
        
        if (deactivateBtn) {
            deactivateBtn.disabled = !hasActive;
            deactivateBtn.style.opacity = hasActive ? '1' : '0.5';
            deactivateBtn.style.cursor = hasActive ? 'pointer' : 'not-allowed';
        }
    } else {
        document.getElementById(userType + '-bulk-info').classList.remove('active');
        
        // Reset button states when nothing is selected
        const activateBtn = document.querySelector('#' + userType + '-tab .btn-activate');
        const deactivateBtn = document.querySelector('#' + userType + '-tab .btn-deactivate');
        
        if (activateBtn) {
            activateBtn.disabled = false;
            activateBtn.style.opacity = '1';
            activateBtn.style.cursor = 'pointer';
        }
        
        if (deactivateBtn) {
            deactivateBtn.disabled = false;
            deactivateBtn.style.opacity = '1';
            deactivateBtn.style.cursor = 'pointer';
        }
    }
}

// Bulk actions
async function bulkAction(userType, action) {
    const checkboxes = document.querySelectorAll('input[name="user_ids[]"]:checked');
    
    if (checkboxes.length === 0) {
    showAlert('Please select at least one user.', 'warning', { anchor: '.container' });
        return;
    }
    
    // Validate that the selected users are appropriate for this action
    let validCount = 0;
    checkboxes.forEach(cb => {
        const row = cb.closest('tr');
        const statusBadge = row.querySelector('.badge-active, .badge-inactive');
        if (statusBadge) {
            if (action === 'activate' && statusBadge.classList.contains('badge-inactive')) {
                validCount++;
            } else if (action === 'deactivate' && statusBadge.classList.contains('badge-active')) {
                validCount++;
            }
        }
    });
    
    if (validCount === 0) {
        const actionText = action === 'activate' ? 'activate already active' : 'deactivate already inactive';
    showAlert('You cannot ' + actionText + ' users. Please select appropriate users.', 'error', { anchor: '.container' });
        return;
    }
    
    const userIds = Array.from(checkboxes).map(cb => cb.value);
    const actionText = action === 'activate' ? 'activate' : 'deactivate';
    
    const skipMessage = checkboxes.length - validCount > 0 ? 
        `\n\n${checkboxes.length - validCount} user(s) will be skipped (already in target state).` : '';
    
    const confirmed = await showConfirm(
        `Are you sure you want to ${actionText} ${validCount} user(s)?${skipMessage}`,
        'Confirm Bulk Action',
        action === 'deactivate'
    );
    
    if (!confirmed) {
        return;
    }
    
    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'manage_users_actions.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.send('action=bulk_toggle&user_ids=' + JSON.stringify(userIds) + '&user_type=' + userType + '&bulk_action=' + action);
    
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            const response = JSON.parse(xhr.responseText);
            if (response.success) {
                showAlert(response.message, 'success', { anchor: '.container' });
                loadUsers(userType);
            } else {
                showAlert(response.message, 'error', { anchor: '.container' });
            }
        }
    };
}

// Export to CSV
function exportCSV(userType) {
    // Get current filters
    const searchQuery = document.getElementById(userType + '-search').value;
    const statusFilter = document.getElementById(userType + '-status-filter').value;
    
    let url = 'manage_users_actions.php?action=export_csv&user_type=' + userType;
    let filterCount = 0;
    let filterDesc = [];
    
    // Add search parameter
    if (searchQuery) {
        url += '&search=' + encodeURIComponent(searchQuery);
        filterDesc.push('Search: "' + searchQuery + '"');
        filterCount++;
    }
    
    // Add status filter
    if (statusFilter) {
        url += '&status=' + statusFilter;
        filterDesc.push('Status: ' + (statusFilter === '1' ? 'Active' : 'Inactive'));
        filterCount++;
    }
    
    // Add semester and class filters for students
    if (userType === 'students') {
        const semesterFilter = document.getElementById('students-semester-filter').value;
        const classFilter = document.getElementById('students-class-filter').value;
        
        if (semesterFilter) {
            url += '&semester=' + semesterFilter;
            const semText = document.getElementById('students-semester-filter').selectedOptions[0].text;
            filterDesc.push('Semester: ' + semText);
            filterCount++;
        }
        if (classFilter) {
            url += '&class=' + classFilter;
            const classText = document.getElementById('students-class-filter').selectedOptions[0].text;
            filterDesc.push('Class: ' + classText);
            filterCount++;
        }
    }
    
    // Show toast with filter info
    if (filterCount > 0) {
    showAlert('Exporting filtered data: ' + filterDesc.join(', '), 'info', { anchor: '.container' });
    } else {
    showAlert('Exporting all ' + userType + ' data', 'info', { anchor: '.container' });
    }
    
    // Trigger download
    window.location.href = url;
}

// Load students data on page load
loadUsers('students');

// Initialize class filter
updateClassFilter('students');
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
