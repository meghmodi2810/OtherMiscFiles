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

$page_title = 'Manage Groups';
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
.filters-bar {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    margin-bottom: 20px;
    align-items: center;
    background: var(--card);
    padding: 16px;
    border-radius: var(--radius);
    box-shadow: var(--shadow);
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

.btn-danger {
    background: #DC2626;
    color: white;
}

.btn-danger:hover {
    background: #B91C1C;
    transform: translateY(-1px);
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
    background: white;
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
    white-space: nowrap;
}

td {
    padding: 12px 16px;
    border-bottom: 1px solid var(--border);
    font-size: 14px;
}

tr:hover {
    background: var(--bg);
}

.badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}

.badge-finalized {
    background: #D1FAE5;
    color: #065F46;
}

.badge-not-finalized {
    background: #FFF9E6;
    color: #92400E;
}

.badge-full {
    background: #D1FAE5;
    color: #065F46;
}

.badge-incomplete {
    background: #FEE2E2;
    color: #991B1B;
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

.btn-action.btn-view {
    background: #E8F4FD;
    color: #0B4DA0;
}

.btn-action.btn-view:hover {
    background: #D1E7F9;
}

.btn-action.btn-transfer {
    background: #D4F4F6;
    color: #0E7E8A;
}

.btn-action.btn-transfer:hover {
    background: #B8EDEF;
}

.btn-action.btn-finalize {
    background: #FFF9E6;
    color: #92400E;
}

.btn-action.btn-finalize:hover {
    background: #FFF4CC;
}

.btn-action.btn-dissolve {
    background: #FEE2E2;
    color: #991B1B;
}

.btn-action.btn-dissolve:hover {
    background: #FECACA;
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

.member-list {
    list-style: none;
    padding: 0;
}

.member-item {
    padding: 12px;
    background: var(--bg);
    border-radius: 8px;
    margin-bottom: 8px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.member-info {
    flex: 1;
}

.member-name {
    font-weight: 600;
    color: var(--text);
}

.member-email {
    font-size: 13px;
    color: var(--text-light);
}

.member-role {
    padding: 4px 8px;
    background: var(--primary);
    color: white;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
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

.form-group select {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid var(--border);
    border-radius: 8px;
    font-size: 14px;
}

.modal-actions {
    display: flex;
    gap: 12px;
    justify-content: flex-end;
    margin-top: 24px;
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
    white-space: pre-line;
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

.notice-card {
    background: #EFF6FF;
    border-left: 4px solid #3B82F6;
    padding: 16px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.notice-card h3 {
    font-size: 16px;
    margin-bottom: 8px;
    color: #1E40AF;
}

.notice-card p {
    font-size: 14px;
    color: var(--text-light);
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

.warning-box {
    background: #FEF3C7;
    border: 2px solid #F59E0B;
    padding: 16px;
    border-radius: 8px;
    margin-bottom: 16px;
}

.warning-box h4 {
    color: #92400E;
    margin-bottom: 8px;
}

.warning-box p {
    color: #92400E;
    font-size: 14px;
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
                <h1 class="card-title">Manage Groups</h1>
                <p class="card-subtitle">View and manage all student groups</p>
            </div>
        </div>

        <div class="filters-bar">
            <div class="search-box">
                <input type="search" id="groups-search" placeholder="Search by Group #, Leader Name, Roll No..." onkeyup="searchGroups(this.value)">
            </div>
            <div class="filter-group">
                <label>Semester:</label>
                <select id="semester-filter" onchange="updateClassFilter(); filterGroups()">
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
                <select id="class-filter" onchange="filterGroups()">
                    <option value="">All</option>
                </select>
            </div>
            <div class="filter-group">
                <label>Status:</label>
                <select id="status-filter" onchange="filterGroups()">
                    <option value="">All</option>
                    <option value="1">Finalized</option>
                    <option value="0">Not Finalized</option>
                </select>
            </div>
        </div>

        <div id="bulk-info" class="bulk-actions-info">
            <strong id="selected-count">0</strong> group(s) selected
        </div>

        <div class="actions-bar">
            <button class="btn-small btn-danger" onclick="bulkDissolve()">
                <i data-feather="trash-2"></i>
                Dissolve Selected Groups
            </button>
            <button class="btn-small btn-export" onclick="exportCSV()">
                <i data-feather="download"></i>
                <span id="export-text">Export to CSV</span>
            </button>
        </div>

        <div class="table-container">
            <div id="groups-table">
                <div class="loading">Loading groups...</div>
            </div>
        </div>
    </div>
</main>

<!-- View Members Modal -->
<div id="members-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Group Members</h2>
            <button class="close-modal" onclick="closeMembersModal()">&times;</button>
        </div>
        <div id="members-list">
            <div class="loading">Loading members...</div>
        </div>
    </div>
</div>

<!-- Transfer Leadership Modal -->
<div id="transfer-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Transfer Leadership</h2>
            <button class="close-modal" onclick="closeTransferModal()">&times;</button>
        </div>
        <form id="transfer-form">
            <input type="hidden" id="transfer-group-id" name="group_id">
            
            <div class="form-group">
                <label>Select New Leader</label>
                <select id="transfer-new-leader" name="new_leader_id" required>
                    <option value="">Select a member...</option>
                </select>
            </div>
            
            <div class="modal-actions">
                <button type="button" class="btn-small" style="background: var(--text-light); color: white;" onclick="closeTransferModal()">Cancel</button>
                <button type="submit" class="btn-small" style="background: var(--primary); color: white;">Transfer Leadership</button>
            </div>
        </form>
    </div>
</div>

<script>
// Store all classes data for filtering
const allClasses = <?= json_encode($classes) ?>;

// Per-page toast implementation removed. Use global showAlert/showToast (from /js/alerts.js).

// Confirmation modal system
let confirmCallback = null;

// Delegate local per-page confirm calls to the global modal implementation
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
    if (confirmCallback) {
        confirmCallback(false);
        confirmCallback = null;
    }
}

function confirmAction() {
    document.getElementById('confirm-modal').classList.remove('active');
    if (confirmCallback) {
        confirmCallback(true);
        confirmCallback = null;
    }
}

// Load groups data
function loadGroups() {
    const xhr = new XMLHttpRequest();
    xhr.open('GET', 'manage_groups_data.php?t=' + Date.now(), true);
    
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            document.getElementById('groups-table').innerHTML = xhr.responseText;
            updateSelectAllCheckbox();
            // Re-initialize Feather icons for dynamically loaded content
            if (typeof feather !== 'undefined') {
                feather.replace();
            }
        }
    };
    
    xhr.send();
}

// Search groups
function searchGroups(searchQuery) {
    const semesterFilter = document.getElementById('semester-filter').value;
    const classFilter = document.getElementById('class-filter').value;
    const statusFilter = document.getElementById('status-filter').value;
    
    let url = 'manage_groups_data.php?search=' + searchQuery + 
              '&semester=' + semesterFilter + 
              '&class=' + classFilter + 
              '&status=' + statusFilter + '&t=' + Date.now();
    
    const xhr = new XMLHttpRequest();
    xhr.open('GET', url, true);
    
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            document.getElementById('groups-table').innerHTML = xhr.responseText;
            updateSelectAllCheckbox();
            // Re-initialize Feather icons for dynamically loaded content
            if (typeof feather !== 'undefined') {
                feather.replace();
            }
        }
    };
    
    xhr.send();
}

// Filter groups
function filterGroups() {
    const searchQuery = document.getElementById('groups-search').value;
    searchGroups(searchQuery);
}

// Update class filter based on semester selection
function updateClassFilter() {
    const semesterId = document.getElementById('semester-filter').value;
    const classSelect = document.getElementById('class-filter');
    
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

// View group members
function viewMembers(groupId) {
    const xhr = new XMLHttpRequest();
    xhr.open('GET', 'manage_groups_actions.php?action=get_members&group_id=' + groupId, true);
    xhr.send();
    
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            document.getElementById('members-list').innerHTML = xhr.responseText;
            document.getElementById('members-modal').classList.add('active');
        }
    };
}

function closeMembersModal() {
    document.getElementById('members-modal').classList.remove('active');
}

// Transfer leadership
function openTransferModal(groupId) {
    const xhr = new XMLHttpRequest();
    xhr.open('GET', 'manage_groups_actions.php?action=get_members_for_transfer&group_id=' + groupId, true);
    xhr.send();
    
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            const members = JSON.parse(xhr.responseText);
            const select = document.getElementById('transfer-new-leader');
            select.innerHTML = '<option value="">Select a member...</option>';
            
            members.forEach(member => {
                const option = document.createElement('option');
                option.value = member.student_id;
                option.textContent = member.name + ' (' + member.username + ')';
                select.appendChild(option);
            });
            
            document.getElementById('transfer-group-id').value = groupId;
            document.getElementById('transfer-modal').classList.add('active');
        }
    };
}

function closeTransferModal() {
    document.getElementById('transfer-modal').classList.remove('active');
}

// Safely attach submit handler if the form exists to avoid runtime errors
const _transferForm = document.getElementById('transfer-form');
if (_transferForm) {
    _transferForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        formData.append('action', 'transfer_leadership');
        
        const confirmed = await showConfirm(
            'Are you sure you want to transfer leadership to the selected member?',
            'Transfer Leadership',
            false
        );
        
        if (!confirmed) {
            return;
        }
        
        const xhr = new XMLHttpRequest();
        xhr.open('POST', 'manage_groups_actions.php', true);
        
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4 && xhr.status === 200) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        showAlert(response.message, 'success', { anchor: '#confirm-modal .confirm-modal-content' });
                        closeTransferModal();
                        loadGroups();
                    } else {
                        showAlert(response.message, 'error', { anchor: '#confirm-modal .confirm-modal-content' });
                    }
                } catch (err) {
                    document.getElementById('groups-table').innerHTML = '<div class="loading">Response parse error: ' + err.message + '</div>';
                }
            }
        };
        
        xhr.send(formData);
    });
}

// Force finalize group
async function forceFinalize(groupId, currentMembers, requiredMembers) {
    let confirmMsg = 'Are you sure you want to finalize this group?';
    let isDanger = false;
    
    if (currentMembers < requiredMembers) {
        confirmMsg = `⚠️ WARNING: This group has only ${currentMembers} member(s) out of ${requiredMembers} required.\n\nAs an admin, you can still force finalize, but it's not full.\n\nDo you really want to proceed?`;
        isDanger = true;
    }
    
    const confirmed = await showConfirm(confirmMsg, 'Force Finalize Group', isDanger);
    
    if (!confirmed) {
        return;
    }
    
    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'manage_groups_actions.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            const response = JSON.parse(xhr.responseText);
                if (response.success) {
                showAlert(response.message, 'success', { anchor: '#confirm-modal .confirm-modal-content' });
                loadGroups();
            } else {
                showAlert(response.message, 'error', { anchor: '#confirm-modal .confirm-modal-content' });
            }
        }
    };
    
    xhr.send('action=force_finalize&group_id=' + groupId);
}

// Dissolve group
async function dissolveGroup(groupId) {
    const confirmed = await showConfirm(
        'Are you sure you want to dissolve this group?\n\nAll members will be notified via email and the group will be permanently deleted.',
        'Dissolve Group',
        true
    );
    
    if (!confirmed) {
        return;
    }
    
    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'manage_groups_actions.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            const response = JSON.parse(xhr.responseText);
            if (response.success) {
                showAlert(response.message, 'warning', { anchor: '.container' });
                loadGroups();
            } else {
                showAlert(response.message, 'error', { anchor: '.container' });
            }
        }
    };
    
    xhr.send('action=dissolve_group&group_id=' + groupId);
}

// Checkbox handling
function toggleSelectAll() {
    const checkboxes = document.querySelectorAll('input[name="group_ids[]"]');
    const selectAllCheckbox = document.getElementById('groups-select-all');
    
    checkboxes.forEach(cb => {
        cb.checked = selectAllCheckbox.checked;
    });
    
    updateBulkInfo();
}

function updateSelectAllCheckbox() {
    const checkboxes = document.querySelectorAll('input[name="group_ids[]"]');
    const selectAllCheckbox = document.getElementById('groups-select-all');
    
    if (selectAllCheckbox && checkboxes.length > 0) {
        const allChecked = Array.from(checkboxes).every(cb => cb.checked);
        selectAllCheckbox.checked = allChecked;
    }
    
    updateBulkInfo();
}

function updateBulkInfo() {
    const checkboxes = document.querySelectorAll('input[name="group_ids[]"]:checked');
    const count = checkboxes.length;
    
    document.getElementById('selected-count').textContent = count;
    
    if (count > 0) {
        document.getElementById('bulk-info').classList.add('active');
    } else {
        document.getElementById('bulk-info').classList.remove('active');
    }
}

// Bulk dissolve
async function bulkDissolve() {
    const checkboxes = document.querySelectorAll('input[name="group_ids[]"]:checked');
    
    if (checkboxes.length === 0) {
    showAlert('Please select at least one group.', 'warning', { anchor: '.container' });
        return;
    }
    
    const groupIds = Array.from(checkboxes).map(cb => cb.value);
    
    const confirmed = await showConfirm(
        `Are you sure you want to dissolve ${groupIds.length} group(s)?\n\nAll members will be notified via email and the groups will be permanently deleted.`,
        'Bulk Dissolve Groups',
        true
    );
    
    if (!confirmed) {
        return;
    }
    
    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'manage_groups_actions.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            const response = JSON.parse(xhr.responseText);
            if (response.success) {
                showAlert(response.message, 'warning', { anchor: '.container' });
                loadGroups();
            } else {
                showAlert(response.message, 'error', { anchor: '.container' });
            }
        }
    };
    
    xhr.send('action=bulk_dissolve&group_ids=' + JSON.stringify(groupIds));
}

// Export to CSV
function exportCSV() {
    // Get current filters
    const searchQuery = document.getElementById('groups-search').value;
    const semesterFilter = document.getElementById('semester-filter').value;
    const classFilter = document.getElementById('class-filter').value;
    const statusFilter = document.getElementById('status-filter').value;
    
    let url = 'manage_groups_actions.php?action=export_csv';
    let filterCount = 0;
    let filterDesc = [];
    
    // Add search parameter
    if (searchQuery) {
        url += '&search=' + encodeURIComponent(searchQuery);
        filterDesc.push('Search: "' + searchQuery + '"');
        filterCount++;
    }
    
    // Add semester filter
    if (semesterFilter) {
        url += '&semester=' + semesterFilter;
        const semText = document.getElementById('semester-filter').selectedOptions[0].text;
        filterDesc.push('Semester: ' + semText);
        filterCount++;
    }
    
    // Add class filter
    if (classFilter) {
        url += '&class=' + classFilter;
        const classText = document.getElementById('class-filter').selectedOptions[0].text;
        filterDesc.push('Class: ' + classText);
        filterCount++;
    }
    
    // Add status filter
    if (statusFilter) {
        url += '&status=' + statusFilter;
        const statusText = statusFilter === 'finalized' ? 'Finalized' : 'Not Finalized';
        filterDesc.push('Status: ' + statusText);
        filterCount++;
    }
    
    // Show toast with filter info
    if (filterCount > 0) {
    showAlert('Exporting filtered groups: ' + filterDesc.join(', '), 'info', { anchor: '.container' });
    } else {
    showAlert('Exporting all groups data', 'info', { anchor: '.container' });
    }
    
    // Trigger download
    window.location.href = url;
}

// Global JS error handler: surface errors into the groups table for debugging
window.onerror = function(message, source, lineno, colno, error) {
    const msg = message || (error && error.message) || 'Unknown JS error';
    const el = document.getElementById('groups-table');
    if (el) {
        el.innerHTML = '<div class="loading">JS Error: ' + msg + ' (line ' + lineno + ')</div>';
    }
    // still return false to allow default handling in the console
    return false;
};

// Load groups on page load (wrap in try/catch so we can show errors in the UI)
try {
    loadGroups();
} catch (e) {
    const el = document.getElementById('groups-table');
    if (el) el.innerHTML = '<div class="loading">JS Exception: ' + (e && e.message) + '</div>';
}

// Initialize class filter
try {
    updateClassFilter();
} catch (e) {
    // ignore
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
