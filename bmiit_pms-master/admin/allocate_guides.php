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

$page_title = 'Allocate Guides';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

// Fetch semesters for filter
$semestersQuery = "SELECT s.id, c.name as course_name, s.semester_no, s.year 
                   FROM semesters s 
                   JOIN courses c ON s.course_id = c.id 
                   ORDER BY s.year DESC, c.name, s.semester_no DESC";
$semestersResult = $conn->query($semestersQuery);
$semesters = [];
while ($row = $semestersResult->fetch_assoc()) {
    $semesters[] = $row;
}
?>

<style>
:root {
    --success-light: #D1FAE5;
    --success-dark: #065F46;
    --warning-light: #FFF9E6;
    --warning-dark: #92400E;
    --danger-light: #FEE2E2;
    --danger-dark: #991B1B;
    --info-light: #E8F4FD;
    --info-dark: #0B4DA0;
}

.wizard-container {
    max-width: 1400px;
    margin: 0 auto;
}

.wizard-steps {
    display: flex;
    justify-content: space-between;
    margin-bottom: 40px;
    background: white;
    padding: 24px;
    border-radius: var(--radius);
    box-shadow: var(--shadow);
}

.wizard-step {
    flex: 1;
    text-align: center;
    position: relative;
    padding: 0 20px;
}

.wizard-step:not(:last-child)::after {
    content: '';
    position: absolute;
    top: 20px;
    right: -50%;
    width: 100%;
    height: 2px;
    background: var(--border);
    z-index: -1;
}

.wizard-step.active:not(:last-child)::after {
    background: var(--primary);
}

.wizard-step.completed:not(:last-child)::after {
    background: var(--success-dark);
}

.step-circle {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: var(--bg);
    border: 2px solid var(--border);
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 12px;
    font-weight: 600;
    color: var(--text-light);
    transition: var(--transition);
}

.wizard-step.active .step-circle {
    background: var(--primary);
    border-color: var(--primary);
    color: white;
}

.wizard-step.completed .step-circle {
    background: var(--success-dark);
    border-color: var(--success-dark);
    color: white;
}

.step-label {
    font-size: 14px;
    font-weight: 600;
    color: var(--text-light);
}

.wizard-step.active .step-label {
    color: var(--primary);
}

.wizard-step.completed .step-label {
    color: var(--success-dark);
}

.wizard-content {
    background: white;
    border-radius: var(--radius);
    box-shadow: var(--shadow);
    padding: 32px;
    margin-bottom: 24px;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
    padding-bottom: 16px;
    border-bottom: 2px solid var(--border);
}

.section-title {
    font-size: 20px;
    font-weight: 600;
    color: var(--text);
    display: flex;
    align-items: center;
    gap: 12px;
}

.section-title i {
    color: var(--primary);
}

/* Step 1: Select Semester */
.semester-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 16px;
}

.semester-card {
    padding: 20px;
    border: 2px solid var(--border);
    border-radius: 12px;
    cursor: pointer;
    transition: var(--transition);
    background: var(--bg);
}

.semester-card:hover {
    border-color: var(--primary);
    background: var(--info-light);
    transform: translateY(-2px);
}

.semester-card.selected {
    border-color: var(--primary);
    background: var(--info-light);
    box-shadow: 0 0 0 3px rgba(11, 77, 160, 0.1);
}

.semester-card-header {
    display: flex;
    justify-content: space-between;
    align-items: start;
    margin-bottom: 12px;
}

.semester-name {
    font-weight: 600;
    font-size: 16px;
    color: var(--text);
}

.semester-badge {
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}

.semester-badge.allocated {
    background: var(--success-light);
    color: var(--success-dark);
}

.semester-badge.pending {
    background: var(--warning-light);
    color: var(--warning-dark);
}

.semester-info {
    font-size: 14px;
    color: var(--text-light);
    margin-bottom: 8px;
}

.semester-stats {
    display: flex;
    gap: 16px;
    margin-top: 12px;
    padding-top: 12px;
    border-top: 1px solid var(--border);
}

.stat-item {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 13px;
}

.stat-label {
    color: var(--text-light);
}

.stat-value {
    font-weight: 600;
    color: var(--text);
}

/* Step 2: Select Faculty */
.faculty-selection-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding: 16px;
    background: var(--info-light);
    border-radius: 8px;
}

.selection-info {
    font-size: 14px;
    color: var(--info-dark);
    font-weight: 500;
}

.selection-actions {
    display: flex;
    gap: 12px;
}

.btn-selection {
    padding: 8px 16px;
    border: none;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: var(--transition);
}

.btn-select-all {
    background: var(--primary);
    color: white;
}

.btn-select-all:hover {
    background: #0A3E82;
}

.btn-clear-all {
    background: white;
    color: var(--text);
    border: 1px solid var(--border);
}

.btn-clear-all:hover {
    background: var(--bg);
}

.faculty-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 16px;
}

.faculty-card {
    padding: 16px;
    border: 2px solid var(--border);
    border-radius: 12px;
    cursor: pointer;
    transition: var(--transition);
    background: white;
}

.faculty-card:hover {
    border-color: var(--primary);
    transform: translateY(-2px);
}

.faculty-card.selected {
    border-color: var(--success-dark);
    background: var(--success-light);
}

.faculty-card-header {
    display: flex;
    align-items: start;
    gap: 12px;
    margin-bottom: 12px;
}

.faculty-checkbox {
    width: 20px;
    height: 20px;
    margin-top: 2px;
    cursor: pointer;
}

.faculty-info {
    flex: 1;
}

.faculty-name {
    font-weight: 600;
    font-size: 15px;
    color: var(--text);
    margin-bottom: 4px;
}

.faculty-details {
    font-size: 13px;
    color: var(--text-light);
    margin-bottom: 2px;
}

.faculty-load {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 12px;
    padding-top: 12px;
    border-top: 1px solid var(--border);
}

.load-label {
    font-size: 12px;
    color: var(--text-light);
    font-weight: 500;
}

.load-badge {
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}

.load-badge.none {
    background: var(--success-light);
    color: var(--success-dark);
}

.load-badge.light {
    background: var(--info-light);
    color: var(--info-dark);
}

.load-badge.moderate {
    background: var(--warning-light);
    color: var(--warning-dark);
}

.load-badge.heavy {
    background: var(--danger-light);
    color: var(--danger-dark);
}

/* Step 3: Allocate */
.allocation-mode {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-bottom: 32px;
}

.mode-card {
    padding: 24px;
    border: 2px solid var(--border);
    border-radius: 12px;
    cursor: pointer;
    transition: var(--transition);
    background: white;
}

.mode-card:hover {
    border-color: var(--primary);
    transform: translateY(-2px);
}

.mode-card.active {
    border-color: var(--primary);
    background: var(--info-light);
    box-shadow: 0 0 0 3px rgba(11, 77, 160, 0.1);
}

.mode-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 12px;
}

.mode-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    background: var(--primary);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
}

.mode-title {
    font-size: 18px;
    font-weight: 600;
    color: var(--text);
}

.mode-description {
    font-size: 14px;
    color: var(--text-light);
    line-height: 1.6;
}

.allocation-preview {
    background: var(--bg);
    border-radius: 12px;
    padding: 24px;
    margin-bottom: 24px;
}

.preview-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.preview-title {
    font-size: 18px;
    font-weight: 600;
    color: var(--text);
}

.preview-stats {
    display: flex;
    gap: 24px;
}

.preview-stat {
    text-align: center;
}

.preview-stat-value {
    font-size: 24px;
    font-weight: 700;
    color: var(--primary);
}

.preview-stat-label {
    font-size: 12px;
    color: var(--text-light);
    margin-top: 4px;
}

.faculty-allocation-grid {
    display: grid;
    gap: 16px;
}

.faculty-allocation-card {
    background: white;
    border-radius: 8px;
    padding: 20px;
    border: 1px solid var(--border);
}

.faculty-allocation-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
}

.faculty-allocation-name {
    font-size: 16px;
    font-weight: 600;
    color: var(--text);
}

.faculty-allocation-count {
    padding: 6px 12px;
    background: var(--primary);
    color: white;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 600;
}

.groups-list {
    display: grid;
    gap: 8px;
}

.group-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 12px;
    background: var(--bg);
    border-radius: 6px;
    font-size: 14px;
}

.group-info {
    display: flex;
    align-items: center;
    gap: 12px;
}

.group-number {
    font-weight: 600;
    color: var(--primary);
}

.group-leader {
    color: var(--text-light);
}

.btn-change-guide {
    padding: 6px 12px;
    border: 1px solid var(--border);
    background: white;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 500;
    cursor: pointer;
    transition: var(--transition);
}

.btn-change-guide:hover {
    background: var(--primary);
    color: white;
    border-color: var(--primary);
}

.empty-allocation {
    text-align: center;
    padding: 40px;
    color: var(--text-light);
}

.empty-allocation i {
    width: 64px;
    height: 64px;
    margin-bottom: 16px;
    opacity: 0.3;
}

/* Wizard Navigation */
.wizard-navigation {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 32px;
    background: white;
    border-radius: var(--radius);
    box-shadow: var(--shadow);
}

.btn-wizard {
    padding: 12px 24px;
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

.btn-wizard:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.btn-back {
    background: var(--bg);
    color: var(--text);
}

.btn-back:hover:not(:disabled) {
    background: var(--border);
}

.btn-next {
    background: var(--primary);
    color: white;
}

.btn-next:hover:not(:disabled) {
    background: #0A3E82;
}

.btn-finalize {
    background: var(--success-dark);
    color: white;
}

.btn-finalize:hover:not(:disabled) {
    background: #047857;
}

/* Modals */
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
    border-radius: 12px;
    padding: 24px;
    width: 90%;
    max-width: 500px;
    max-height: 90vh;
    overflow-y: auto;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.modal-title {
    font-size: 20px;
    font-weight: 600;
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
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-size: 14px;
    font-weight: 600;
    color: var(--text);
}

.form-group select,
.form-group textarea {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid var(--border);
    border-radius: 8px;
    font-size: 14px;
}

.form-group textarea {
    min-height: 100px;
    resize: vertical;
    font-family: inherit;
}

.modal-actions {
    display: flex;
    gap: 12px;
    justify-content: flex-end;
    margin-top: 24px;
}

/* (Legacy per-page toast styles removed) Use global site alerts via /js/alerts.js and /css/alerts.css */

.loading {
    text-align: center;
    padding: 40px;
    color: var(--text-light);
}

.notice-box {
    background: var(--warning-light);
    border: 2px solid #F59E0B;
    padding: 16px;
    border-radius: 8px;
    margin-bottom: 24px;
}

.notice-box h4 {
    color: var(--warning-dark);
    margin-bottom: 8px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.notice-box p {
    color: var(--warning-dark);
    font-size: 14px;
    line-height: 1.6;
}

.hidden {
    display: none;
}
</style>

<main class="main-wrapper">
    <!-- Per-page toast container removed; using global page alerts -->
    <?php
    // Server-rendered flash area: show messages set by PHP (or session flash)
    $__flash_msg = '';
    $__flash_type = '';
    if (!empty($_SESSION['flash_message'])) {
        $__flash_msg = $_SESSION['flash_message'];
        $__flash_type = $_SESSION['flash_type'] ?? 'info';
        // clear after showing
        unset($_SESSION['flash_message'], $_SESSION['flash_type']);
    } elseif (!empty($message)) {
        $__flash_msg = $message;
        $__flash_type = $alert_type ?? 'info';
    }
    if (!empty($__flash_msg)): ?>
        <div class="container fade-in">
            <div class="alert alert-<?= htmlspecialchars($__flash_type) ?>">
                <?= htmlspecialchars($__flash_msg) ?>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Change Guide Modal -->
    <div id="change-guide-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Change Guide</h2>
                <button class="close-modal" onclick="closeChangeGuideModal()">&times;</button>
            </div>
            <form id="change-guide-form">
                <input type="hidden" id="change-group-id" name="group_id">
                
                <div class="form-group">
                    <label>Group Details</label>
                    <div id="change-group-details" style="padding: 12px; background: var(--bg); border-radius: 8px; margin-bottom: 12px;">
                        <div style="font-weight: 600;" id="change-group-info"></div>
                        <div style="font-size: 13px; color: var(--text-light); margin-top: 4px;" id="change-leader-info"></div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Select New Guide</label>
                    <select id="change-new-guide" name="new_guide_id" required>
                        <option value="">Select a faculty...</option>
                    </select>
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn-wizard btn-back" onclick="closeChangeGuideModal()">Cancel</button>
                    <button type="submit" class="btn-wizard btn-next">Change Guide</button>
                </div>
            </form>
        </div>
    </div>
    
    <div class="wizard-container fade-in">
        <div class="card">
            <div class="card-header">
                <h1 class="card-title">Allocate Project Guides</h1>
                <p class="card-subtitle">Assign faculty guides to student groups</p>
            </div>
        </div>

        <!-- Wizard Steps -->
        <div class="wizard-steps">
            <div class="wizard-step active" data-step="1">
                <div class="step-circle">1</div>
                <div class="step-label">Select Semester</div>
            </div>
            <div class="wizard-step" data-step="2">
                <div class="step-circle">2</div>
                <div class="step-label">Select Faculty</div>
            </div>
            <div class="wizard-step" data-step="3">
                <div class="step-circle">3</div>
                <div class="step-label">Allocate & Finalize</div>
            </div>
        </div>

        <!-- Step 1: Select Semester -->
        <div id="step-1" class="wizard-content">
            <div class="section-header">
                <div class="section-title">
                    <i data-feather="calendar"></i>
                    Select Semester for Guide Allocation
                </div>
            </div>
            
            <div class="semester-grid" id="semester-grid">
                <div class="loading">Loading semesters...</div>
            </div>
        </div>

        <!-- Step 2: Select Faculty -->
        <div id="step-2" class="wizard-content hidden">
            <div class="section-header">
                <div class="section-title">
                    <i data-feather="users"></i>
                    Select Faculty Members
                </div>
            </div>
            
            <div class="faculty-selection-header">
                <div class="selection-info">
                    <strong id="selected-faculty-count">0</strong> faculty selected
                </div>
                <div class="selection-actions">
                    <button class="btn-selection btn-select-all" onclick="selectAllFaculty()">
                        Select All
                    </button>
                    <button class="btn-selection btn-clear-all" onclick="clearAllFaculty()">
                        Clear All
                    </button>
                </div>
            </div>
            
            <div class="faculty-grid" id="faculty-grid">
                <div class="loading">Loading faculty...</div>
            </div>
        </div>

        <!-- Step 3: Allocate & Preview -->
        <div id="step-3" class="wizard-content hidden">
            <div class="section-header">
                <div class="section-title">
                    <i data-feather="git-branch"></i>
                    Allocation Mode
                </div>
            </div>
            
            <div class="allocation-mode">
                <div class="mode-card active" id="mode-card-automatic" onclick="setAllocationMode('automatic')">
                    <div class="mode-header">
                        <div class="mode-icon">
                            <i data-feather="shuffle" style="width: 24px; height: 24px;"></i>
                        </div>
                        <div class="mode-title">Automatic</div>
                    </div>
                    <div class="mode-description">
                        Randomly distribute groups equally among selected faculty. Click "Generate" to auto-assign all groups.
                    </div>
                </div>
                
                <div class="mode-card" id="mode-card-manual" onclick="setAllocationMode('manual')">
                    <div class="mode-header">
                        <div class="mode-icon">
                            <i data-feather="edit" style="width: 24px; height: 24px;"></i>
                        </div>
                        <div class="mode-title">Manual</div>
                    </div>
                    <div class="mode-description">
                        Manually assign each group to faculty one by one. Start assigning from the unassigned groups list.
                    </div>
                </div>
            </div>
            
            <div id="notice-reallocation" class="notice-box hidden">
                <h4>
                    <i data-feather="alert-triangle" style="width: 20px; height: 20px;"></i>
                    Reallocation Warning
                </h4>
                <p>This semester already has guide allocations. Proceeding will <strong>overwrite existing allocations</strong> and notify all affected faculty and students.</p>
            </div>
            
            <div class="allocation-preview">
                <div class="preview-header">
                    <div class="preview-title">Allocation Preview</div>
                    <div class="preview-stats">
                        <div class="preview-stat">
                            <div class="preview-stat-value" id="total-groups-stat">0</div>
                            <div class="preview-stat-label">Total Groups</div>
                        </div>
                        <div class="preview-stat">
                            <div class="preview-stat-value" id="total-faculty-stat">0</div>
                            <div class="preview-stat-label">Faculty Selected</div>
                        </div>
                        <div class="preview-stat">
                            <div class="preview-stat-value" id="avg-load-stat">0</div>
                            <div class="preview-stat-label">Avg per Faculty</div>
                        </div>
                    </div>
                </div>
                
                <div class="faculty-allocation-grid" id="allocation-preview-grid">
                    <div class="empty-allocation">
                        <i data-feather="inbox"></i>
                        <p id="empty-allocation-text">Click "Generate Allocation" to auto-assign all groups</p>
                    </div>
                </div>
            </div>
            
            <div style="text-align: center; margin-top: 24px;" id="generate-btn-container">
                <button class="btn-wizard btn-next" onclick="generateAllocation()" id="btn-generate">
                    <i data-feather="refresh-cw"></i>
                    Generate Allocation
                </button>
            </div>
        </div>

        <!-- Wizard Navigation -->
        <div class="wizard-navigation">
            <button class="btn-wizard btn-back" onclick="previousStep()" id="btn-back" disabled>
                <i data-feather="arrow-left"></i>
                Back
            </button>
            
            <div style="color: var(--text-light); font-size: 14px;">
                Step <span id="current-step">1</span> of 3
            </div>
            
            <button class="btn-wizard btn-next" onclick="nextStep()" id="btn-next" disabled>
                Next
                <i data-feather="arrow-right"></i>
            </button>
            
            <button class="btn-wizard btn-finalize hidden" onclick="finalizeAllocation()" id="btn-finalize">
                <i data-feather="check-circle"></i>
                Finalize Allocation
            </button>
        </div>
    </div>
</main>

<script>
// Global state
let currentStep = 1;
let selectedSemester = null;
let selectedFaculty = [];
let allocationData = null;
let allocationMode = 'automatic';
let semesterData = null;
let facultyData = [];
let groupsData = [];

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    // Initialize page (no persistent state)
    loadSemesters();
});
// No persistent state: all state is in-memory and resets on page reload

// Per-page toast implementation removed. Use global showAlert/showToast (from /js/alerts.js).

// Load semesters
function loadSemesters() {
    fetch('allocate_guides_data.php?action=get_semesters')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderSemesters(data.semesters);
            } else {
                showAlert(data.message || 'Failed to load semesters', 'error', { anchor: '.wizard-container' });
            }
        })
        .catch(error => {
            showAlert('Error loading semesters: ' + error.message, 'error', { anchor: '.wizard-container' });
        });
}

// Render semesters
function renderSemesters(semesters) {
    const grid = document.getElementById('semester-grid');
    
    if (semesters.length === 0) {
        grid.innerHTML = '<div class="empty-allocation"><i data-feather="inbox"></i><p>No semesters found</p></div>';
        feather.replace();
        return;
    }
    
    grid.innerHTML = semesters.map(sem => `
        <div class="semester-card" onclick="selectSemester(${sem.id})">
            <div class="semester-card-header">
                <div class="semester-name">${sem.course_name} - Sem ${sem.semester_no}</div>
                <div class="semester-badge ${sem.allocation_status === 'allocated' ? 'allocated' : 'pending'}">
                    ${sem.allocation_status === 'allocated' ? 'Allocated' : 'Pending'}
                </div>
            </div>
            <div class="semester-info">Academic Year: ${sem.year}</div>
            <div class="semester-stats">
                <div class="stat-item">
                    <span class="stat-label">Groups:</span>
                    <span class="stat-value">${sem.total_groups}</span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Finalized:</span>
                    <span class="stat-value">${sem.finalized_groups}</span>
                </div>
            </div>
        </div>
    `).join('');
    
    feather.replace();
    
    // Restore selected semester if returning to step 1
    if (selectedSemester) {
        setTimeout(() => {
            const selectedCard = document.querySelector(`.semester-card[onclick*="${selectedSemester}"]`);
            if (selectedCard) {
                selectedCard.classList.add('selected');
                document.getElementById('btn-next').disabled = false;
            }
        }, 100);
    }
}

// Select semester
function selectSemester(semesterId) {
    selectedSemester = semesterId;
    
    // Update UI
    document.querySelectorAll('.semester-card').forEach(card => {
        card.classList.remove('selected');
    });
    event.target.closest('.semester-card').classList.add('selected');
    
    // Enable next button
    document.getElementById('btn-next').disabled = false;
    // Selection feedback suppressed in wizard (server-rendered flash used for important messages)
}

// Load faculty for selected semester
function loadFaculty() {
    if (!selectedSemester) return;
    
    fetch(`allocate_guides_data.php?action=get_faculty&semester_id=${selectedSemester}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                facultyData = data.faculty;
                renderFaculty(data.faculty);
            } else {
                showAlert(data.message || 'Failed to load faculty', 'error', { anchor: '.wizard-container' });
            }
        })
        .catch(error => {
            showAlert('Error loading faculty: ' + error.message, 'error', { anchor: '.wizard-container' });
        });
}

// Render faculty
function renderFaculty(faculty) {
    const grid = document.getElementById('faculty-grid');
    
    if (faculty.length === 0) {
        grid.innerHTML = '<div class="empty-allocation"><i data-feather="inbox"></i><p>No faculty found</p></div>';
        feather.replace();
        return;
    }
    
    grid.innerHTML = faculty.map(fac => {
        const loadClass = fac.current_load === 0 ? 'none' : 
                         fac.current_load <= 3 ? 'light' : 
                         fac.current_load <= 6 ? 'moderate' : 'heavy';
        
        return `
            <div class="faculty-card" onclick="toggleFaculty(${fac.faculty_id})">
                <div class="faculty-card-header">
                    <input type="checkbox" class="faculty-checkbox" 
                           id="faculty-${fac.faculty_id}" 
                           value="${fac.faculty_id}"
                           onchange="event.stopPropagation(); updateFacultySelection()">
                    <div class="faculty-info">
                        <div class="faculty-name">${fac.name}</div>
                        <div class="faculty-details">${fac.email}</div>
                        <div class="faculty-details">${fac.phone}</div>
                    </div>
                </div>
                <div class="faculty-load">
                    <span class="load-label">Current Load:</span>
                    <span class="load-badge ${loadClass}">${fac.current_load} groups</span>
                </div>
            </div>
        `;
    }).join('');
    
    feather.replace();
    
    // Restore selected faculty if returning to step 2
    if (selectedFaculty.length > 0) {
        setTimeout(() => {
            selectedFaculty.forEach(facultyId => {
                const checkbox = document.getElementById(`faculty-${facultyId}`);
                if (checkbox) {
                    checkbox.checked = true;
                }
            });
            updateFacultySelection();
        }, 100);
    }
}

// Toggle faculty selection
function toggleFaculty(facultyId) {
    const checkbox = document.getElementById(`faculty-${facultyId}`);
    checkbox.checked = !checkbox.checked;
    updateFacultySelection();
}

// Update faculty selection
function updateFacultySelection() {
    const checkboxes = document.querySelectorAll('.faculty-checkbox:checked');
    selectedFaculty = Array.from(checkboxes).map(cb => parseInt(cb.value));
    
    document.getElementById('selected-faculty-count').textContent = selectedFaculty.length;
    
    // Update card styling
    document.querySelectorAll('.faculty-card').forEach(card => {
        const checkbox = card.querySelector('.faculty-checkbox');
        if (checkbox.checked) {
            card.classList.add('selected');
        } else {
            card.classList.remove('selected');
        }
    });
    
    // Enable/disable next button
    document.getElementById('btn-next').disabled = selectedFaculty.length === 0;
    
}

// Select all faculty
function selectAllFaculty() {
    document.querySelectorAll('.faculty-checkbox').forEach(cb => {
        cb.checked = true;
    });
    updateFacultySelection();
}

// Clear all faculty
function clearAllFaculty() {
    document.querySelectorAll('.faculty-checkbox').forEach(cb => {
        cb.checked = false;
    });
    updateFacultySelection();
}

// Load groups for allocation
function loadGroups() {
    if (!selectedSemester) return;
    
    // First ensure faculty data is loaded
    const loadGroupsData = () => {
        fetch(`allocate_guides_data.php?action=get_groups&semester_id=${selectedSemester}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    groupsData = data.groups;
                    updatePreviewStats();
                    
                    // Check if reallocation
                    if (data.has_existing_allocation) {
                        document.getElementById('notice-reallocation').classList.remove('hidden');
                    } else {
                        document.getElementById('notice-reallocation').classList.add('hidden');
                    }
                    
                    // Only try to render allocation if we have faculty data
                    if (facultyData.length > 0) {
                        // If in manual mode, initialize manual allocation
                        if (allocationMode === 'manual') {
                            initializeManualAllocation();
                            document.getElementById('generate-btn-container').style.display = 'none';
                        } else if (allocationData) {
                            // Automatic mode: restore previous allocation if exists
                            renderAllocationPreview();
                            document.getElementById('btn-finalize').classList.remove('hidden');
                            document.getElementById('generate-btn-container').style.display = 'block';
                        } else {
                            // Automatic mode: show empty state with generate button
                            document.getElementById('generate-btn-container').style.display = 'block';
                        }
                    }
                } else {
                    showAlert(data.message || 'Failed to load groups', 'error', { anchor: '.wizard-container' });
                }
            })
            .catch(error => {
                showAlert('Error loading groups: ' + error.message, 'error', { anchor: '.wizard-container' });
            });
    };
    
    // If faculty data not loaded yet, load it first
    if (facultyData.length === 0 && selectedSemester) {
        fetch(`allocate_guides_data.php?action=get_faculty&semester_id=${selectedSemester}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    facultyData = data.faculty;
                    loadGroupsData();
                } else {
                    showAlert(data.message || 'Failed to load faculty', 'error', { anchor: '.wizard-container' });
                }
            })
            .catch(error => {
                showAlert('Error loading faculty: ' + error.message, 'error', { anchor: '.wizard-container' });
            });
    } else {
        // Faculty already loaded — just load groups for the selected semester
        loadGroupsData();
    }
}

// Update preview stats
function updatePreviewStats() {
    document.getElementById('total-groups-stat').textContent = groupsData.length;
    document.getElementById('total-faculty-stat').textContent = selectedFaculty.length;
    
    if (selectedFaculty.length > 0) {
        const avg = Math.ceil(groupsData.length / selectedFaculty.length);
        document.getElementById('avg-load-stat').textContent = avg;
    } else {
        document.getElementById('avg-load-stat').textContent = 0;
    }
}

// Set allocation mode
function setAllocationMode(mode) {
    allocationMode = mode;
    
    document.querySelectorAll('.mode-card').forEach(card => {
        card.classList.remove('active');
    });
    
    document.getElementById(`mode-card-${mode}`).classList.add('active');
    
    // Clear existing allocation when switching modes
    allocationData = null;
    
    // Update UI based on mode
    if (mode === 'manual') {
        // Manual mode: show unassigned groups immediately
        if (groupsData.length > 0) {
            initializeManualAllocation();
        }
        document.getElementById('generate-btn-container').style.display = 'none';
    } else {
        // Automatic mode: show generate button
        document.getElementById('generate-btn-container').style.display = 'block';
        document.getElementById('allocation-preview-grid').innerHTML = `
            <div class="empty-allocation">
                <i data-feather="inbox"></i>
                <p>Click "Generate Allocation" to auto-assign all groups</p>
            </div>
        `;
        document.getElementById('btn-finalize').classList.add('hidden');
        feather.replace();
    }
}

// Initialize manual allocation with empty assignments
function initializeManualAllocation() {
    // Check if we have required data
    if (facultyData.length === 0 || groupsData.length === 0) {
        const grid = document.getElementById('allocation-preview-grid');
        grid.innerHTML = '<div class="loading">Loading data for manual allocation...</div>';
        return;
    }
    
    // Initialize allocation data structure if it doesn't exist or is invalid
    if (!allocationData || typeof allocationData !== 'object') {
        allocationData = {};
        selectedFaculty.forEach(fid => {
            allocationData[fid] = [];
        });
    } else {
        // Ensure all selected faculty have an entry in allocationData
        selectedFaculty.forEach(fid => {
            if (!allocationData[fid]) {
                allocationData[fid] = [];
            }
        });
    }
    
    // Create a simple list-based UI for manual assignment
    const grid = document.getElementById('allocation-preview-grid');
    
    // Get assigned groups
    const assignedGroupIds = new Set();
    Object.values(allocationData).forEach(groups => {
        groups.forEach(g => assignedGroupIds.add(g.group_id));
    });
    
    const unassignedGroups = groupsData.filter(g => !assignedGroupIds.has(g.group_id));
    const assignedGroups = groupsData.filter(g => assignedGroupIds.has(g.group_id));
    
    let html = `
        <div style="display: grid; gap: 24px;">
            <!-- Instructions Card -->
            <div style="background: var(--info-light); border: 2px solid var(--info-dark); border-radius: 12px; padding: 20px;">
                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
                    <i data-feather="info" style="color: var(--info-dark);"></i>
                    <h3 style="margin: 0; color: var(--info-dark); font-size: 16px;">Manual Assignment Mode</h3>
                </div>
                <p style="margin: 0; color: var(--info-dark); font-size: 14px;">
                    Assign each group to a faculty member using the dropdown below. Progress: 
                    <strong>${assignedGroups.length} / ${groupsData.length} groups assigned</strong>
                </p>
            </div>
            
            <!-- Groups Table -->
            <div style="background: white; border-radius: 12px; overflow: hidden; border: 1px solid var(--border);">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: var(--bg);">
                            <th style="padding: 16px; text-align: left; font-size: 14px; font-weight: 600; border-bottom: 2px solid var(--border); width: 15%;">Group #</th>
                            <th style="padding: 16px; text-align: left; font-size: 14px; font-weight: 600; border-bottom: 2px solid var(--border); width: 35%;">Leader Details</th>
                            <th style="padding: 16px; text-align: left; font-size: 14px; font-weight: 600; border-bottom: 2px solid var(--border); width: 35%;">Assign Guide</th>
                            <th style="padding: 16px; text-align: center; font-size: 14px; font-weight: 600; border-bottom: 2px solid var(--border); width: 15%;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
    `;
    
    groupsData.forEach(group => {
        // Find if group is assigned
        let assignedFacultyId = null;
        for (const [facultyId, groups] of Object.entries(allocationData)) {
            if (groups.find(g => g.group_id === group.group_id)) {
                assignedFacultyId = parseInt(facultyId);
                break;
            }
        }
        
        const isAssigned = assignedFacultyId !== null;
        const statusBadge = isAssigned ? 
            '<span class="badge badge-finalized">Assigned</span>' : 
            '<span class="badge badge-not-finalized">Pending</span>';
        
        html += `
            <tr style="border-bottom: 1px solid var(--border);">
                <td style="padding: 16px;">
                    <span style="font-weight: 600; color: var(--primary); font-size: 15px;">Group #${group.group_id}</span>
                </td>
                <td style="padding: 16px;">
                    <div style="font-weight: 600; color: var(--text); margin-bottom: 4px;">${group.leader_name}</div>
                    <div style="font-size: 13px; color: var(--text-light);">
                        ${group.leader_username} | ${group.leader_email}
                    </div>
                </td>
                <td style="padding: 16px;">
                    <select id="guide-select-${group.group_id}" 
                            class="guide-select" 
                            onchange="assignGuideManual(${group.group_id}, this.value)"
                            style="width: 100%; padding: 10px 12px; border: 1px solid var(--border); border-radius: 8px; font-size: 14px; ${isAssigned ? 'background: var(--success-light);' : ''}">
                        <option value="">-- Select Faculty --</option>
                        ${selectedFaculty.map(facultyId => {
                            const faculty = facultyData.find(f => f.faculty_id === facultyId);
                            const facultyGroupCount = allocationData[facultyId]?.length || 0;
                            const selected = facultyId === assignedFacultyId ? 'selected' : '';
                            return `<option value="${facultyId}" ${selected}>${faculty.name} (${facultyGroupCount} groups)</option>`;
                        }).join('')}
                    </select>
                </td>
                <td style="padding: 16px; text-align: center;">
                    ${statusBadge}
                </td>
            </tr>
        `;
    });
    
    html += `
                    </tbody>
                </table>
            </div>
        </div>
    `;
    
    grid.innerHTML = html;
    feather.replace();
    
    // Check if all groups assigned
    const totalAssigned = Object.values(allocationData).reduce((sum, groups) => sum + groups.length, 0);
    if (totalAssigned === groupsData.length && totalAssigned > 0) {
        document.getElementById('btn-finalize').classList.remove('hidden');
    } else {
        document.getElementById('btn-finalize').classList.add('hidden');
    }
}

// Assign guide manually (new function for direct dropdown assignment)
function assignGuideManual(groupId, newGuideId) {
    // Ensure we have allocation data structure
    if (!allocationData) {
        allocationData = {};
        selectedFaculty.forEach(fid => {
            allocationData[fid] = [];
        });
    }
    
    if (!newGuideId) {
        // Unassign - remove from current faculty
        for (const [facultyId, groups] of Object.entries(allocationData)) {
            allocationData[facultyId] = groups.filter(g => g.group_id !== groupId);
        }
        // Suppressed 'Group unassigned' notification to avoid rapid repeated alerts in the wizard
    } else {
        newGuideId = parseInt(newGuideId);
        
        // Remove from current faculty if assigned
        for (const [facultyId, groups] of Object.entries(allocationData)) {
            allocationData[facultyId] = groups.filter(g => g.group_id !== groupId);
        }
        
        // Add to new faculty
        const group = groupsData.find(g => g.group_id === groupId);
        if (!group) {
            showAlert('Group not found', 'error', { anchor: '.wizard-container' });
            return;
        }
        
        if (!allocationData[newGuideId]) {
            allocationData[newGuideId] = [];
        }
        allocationData[newGuideId].push(group);
        
        const faculty = facultyData.find(f => f.faculty_id === newGuideId);
        // Suppressed 'Group assigned' notification to avoid repeated alerts in the wizard
    }
    
    // Re-render to update counts and status
    initializeManualAllocation();

}

// Generate allocation (only for automatic mode)
function generateAllocation() {
    if (allocationMode !== 'automatic') {
    showAlert('Generation is only available in Automatic mode', 'warning', { anchor: '.wizard-container' });
        return;
    }
    
    if (selectedFaculty.length === 0) {
    showAlert('Please select at least one faculty member', 'warning', { anchor: '.wizard-container' });
        return;
    }
    
    if (groupsData.length === 0) {
    showAlert('No groups found for allocation', 'warning', { anchor: '.wizard-container' });
        return;
    }
    
    // Automatic allocation logic - truly random distribution
    const allocation = {};
    selectedFaculty.forEach(fid => {
        allocation[fid] = [];
    });
    
    // Shuffle both groups and faculty for true randomization
    const shuffledGroups = [...groupsData].sort(() => Math.random() - 0.5);
    const shuffledFaculty = [...selectedFaculty].sort(() => Math.random() - 0.5);
    
    // Distribute groups evenly but randomly
    // First, calculate how many groups each faculty should get
    const groupsPerFaculty = Math.floor(groupsData.length / selectedFaculty.length);
    const remainder = groupsData.length % selectedFaculty.length;
    
    let groupIndex = 0;
    
    // Assign groups to faculty
    shuffledFaculty.forEach((facultyId, facultyIndex) => {
        // Some faculty get one extra group if there's a remainder
        const numGroupsForThisFaculty = groupsPerFaculty + (facultyIndex < remainder ? 1 : 0);
        
        for (let i = 0; i < numGroupsForThisFaculty && groupIndex < shuffledGroups.length; i++) {
            allocation[facultyId].push(shuffledGroups[groupIndex]);
            groupIndex++;
        }
    });
    
    allocationData = allocation;
    renderAllocationPreview();
    
    // Show finalize button
    document.getElementById('btn-finalize').classList.remove('hidden');
    document.getElementById('btn-generate').innerHTML = '<i data-feather="refresh-cw"></i> Regenerate Allocation';
    feather.replace();
    // Allocation generated - success notification suppressed in wizard to avoid repeated alerts
}

// Render allocation preview
function renderAllocationPreview() {
    const grid = document.getElementById('allocation-preview-grid');
    
    if (!allocationData) {
        grid.innerHTML = '<div class="empty-allocation"><i data-feather="inbox"></i><p>No allocation generated</p></div>';
        feather.replace();
        return;
    }
    
    // Check if we have required data
    if (facultyData.length === 0 || groupsData.length === 0) {
        grid.innerHTML = '<div class="loading">Loading allocation data...</div>';
        return;
    }
    
    let html = '';
    
    // Get all assigned group IDs
    const assignedGroupIds = new Set();
    Object.values(allocationData).forEach(groups => {
        groups.forEach(g => assignedGroupIds.add(g.group_id));
    });
    
    // Get unassigned groups
    const unassignedGroups = groupsData.filter(g => !assignedGroupIds.has(g.group_id));
    
    // Show unassigned groups first (if in manual mode and there are any)
    if (allocationMode === 'manual' && unassignedGroups.length > 0) {
        html += `
            <div class="faculty-allocation-card" style="border: 2px dashed var(--warning-dark); background: var(--warning-light);">
                <div class="faculty-allocation-header">
                    <div class="faculty-allocation-name">⚠️ Unassigned Groups</div>
                    <div class="faculty-allocation-count" style="background: var(--warning-dark);">${unassignedGroups.length} groups</div>
                </div>
                <div class="groups-list">
                    ${unassignedGroups.map(group => `
                        <div class="group-item">
                            <div class="group-info">
                                <span class="group-number">Group #${group.group_id}</span>
                                <span class="group-leader">${group.leader_name} (${group.leader_username})</span>
                            </div>
                            <button class="btn-change-guide" onclick="openChangeGuideModal(${group.group_id}, '${group.leader_name}', '${group.leader_username}')" style="background: var(--warning-dark); color: white;">
                                Assign Guide
                            </button>
                        </div>
                    `).join('')}
                </div>
            </div>
        `;
    }
    
    // Show faculty allocations
    // In automatic mode, hide faculties with zero assigned groups to reduce noise when there are
    // more faculty selected than groups available. We will show a simple summary for those.
    const visibleFaculty = [];
    const hiddenFaculty = [];
    selectedFaculty.forEach(facultyId => {
        const groups = allocationData[facultyId] || [];
        if (allocationMode === 'automatic' && groups.length === 0) {
            hiddenFaculty.push(facultyId);
        } else {
            visibleFaculty.push(facultyId);
        }
    });

    visibleFaculty.forEach(facultyId => {
        const faculty = facultyData.find(f => f.faculty_id === facultyId);
        if (!faculty) return;
        const groups = allocationData[facultyId] || [];

        html += `
            <div class="faculty-allocation-card">
                <div class="faculty-allocation-header">
                    <div class="faculty-allocation-name">${faculty.name}</div>
                    <div class="faculty-allocation-count">${groups.length} groups</div>
                </div>
                <div class="groups-list">
                    ${groups.length === 0 ? 
                        '<div style="padding: 20px; text-align: center; color: var(--text-light); font-size: 13px;">No groups assigned</div>' :
                        groups.map(group => `
                            <div class="group-item">
                                <div class="group-info">
                                    <span class="group-number">Group #${group.group_id}</span>
                                    <span class="group-leader">${group.leader_name} (${group.leader_username})</span>
                                </div>
                                <button class="btn-change-guide" onclick="openChangeGuideModal(${group.group_id}, '${group.leader_name}', '${group.leader_username}')">
                                    Change
                                </button>
                            </div>
                        `).join('')
                    }
                </div>
            </div>
        `;
    });

    // If we hid any faculties in automatic mode, render a small summary card listing them
    if (hiddenFaculty.length > 0) {
        const hiddenNames = hiddenFaculty
            .map(id => {
                const f = facultyData.find(x => x.faculty_id === id);
                return f ? f.name : `Faculty #${id}`;
            })
            .slice(0, 10) // show up to 10 names to avoid overflow
            .join(', ');

        html += `
            <div class="faculty-allocation-card" style="opacity:0.9; background:#fafafa; border:1px dashed #e5e7eb;">
                <div class="faculty-allocation-header">
                    <div class="faculty-allocation-name">${hiddenFaculty.length} faculty with no groups</div>
                    <div class="faculty-allocation-count">${hiddenFaculty.length}</div>
                </div>
                <div class="groups-list" style="padding:12px; font-size:13px; color:#374151;">
                    ${hiddenNames}${hiddenFaculty.length > 10 ? ' and more...' : ''}
                </div>
            </div>
        `;
    }
    
    grid.innerHTML = html;
    feather.replace();
}

// Open change guide modal
function openChangeGuideModal(groupId, leaderName, leaderUsername) {
    document.getElementById('change-group-id').value = groupId;
    document.getElementById('change-group-info').textContent = `Group #${groupId}`;
    document.getElementById('change-leader-info').textContent = `Leader: ${leaderName} (${leaderUsername})`;
    
    // Populate faculty dropdown
    const select = document.getElementById('change-new-guide');
    select.innerHTML = '<option value="">Select a faculty...</option>';
    
    selectedFaculty.forEach(facultyId => {
        const faculty = facultyData.find(f => f.faculty_id === facultyId);
        const option = document.createElement('option');
        option.value = facultyId;
        option.textContent = `${faculty.name} (${allocationData[facultyId]?.length || 0} groups)`;
        select.appendChild(option);
    });
    
    document.getElementById('change-guide-modal').classList.add('active');
}

// Close change guide modal
function closeChangeGuideModal() {
    document.getElementById('change-guide-modal').classList.remove('active');
}

// Handle change guide form submission
document.getElementById('change-guide-form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const groupId = parseInt(document.getElementById('change-group-id').value);
    const newGuideId = parseInt(document.getElementById('change-new-guide').value);
    
    // Find current guide
    let currentGuideId = null;
    for (const [facultyId, groups] of Object.entries(allocationData)) {
        const group = groups.find(g => g.group_id === groupId);
        if (group) {
            currentGuideId = parseInt(facultyId);
            // Remove from current
            allocationData[facultyId] = groups.filter(g => g.group_id !== groupId);
            break;
        }
    }
    
    // Add to new guide
    const group = groupsData.find(g => g.group_id === groupId);
    if (!allocationData[newGuideId]) {
        allocationData[newGuideId] = [];
    }
    allocationData[newGuideId].push(group);
    
    renderAllocationPreview();
    closeChangeGuideModal();
    
    // Check if all groups are assigned to enable finalize
    const totalAssigned = Object.values(allocationData).reduce((sum, groups) => sum + groups.length, 0);
    if (totalAssigned === groupsData.length) {
        document.getElementById('btn-finalize').classList.remove('hidden');
    }
    
    // Save state
    // Suppressed 'Guide assignment updated' transient notification; server flash preferred for persistent messages
});

// Finalize allocation
async function finalizeAllocation() {
    if (!allocationData) {
    showAlert('Please generate allocation first', 'warning', { anchor: '.wizard-container' });
        return;
    }
    
    const confirmed = await window.showConfirm({
        title: 'Confirm Finalize',
        message: 'Are you sure you want to finalize this allocation?<br/><br/>Emails will be sent to all faculty and students. This action cannot be easily undone.',
        okText: 'OK',
        cancelText: 'Cancel',
        danger: true
    });

    if (!confirmed) return;
    
    // Prepare data
    const finalData = {
        action: 'finalize_allocation',
        semester_id: selectedSemester,
        allocation: allocationData
    };
    
    try {
        const response = await fetch('allocate_guides_actions.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(finalData)
        });
        
        // Try to parse JSON; if server returned HTML (PHP error/notice), handle gracefully
        const text = await response.text();
        let result = null;
        try {
            result = JSON.parse(text);
        } catch (e) {
            // Non-JSON response (likely a PHP warning/error). Show a friendly message and log the full response.
            console.error('Finalize allocation unexpected response:', text);
                showAlert('Server returned an unexpected response. Check console for details.', 'error', { anchor: '.wizard-container' });
            return;
        }

        if (result && result.success) {
            // Finalization succeeded. Redirect to admin home. Server-side flash will show any persistent message.
            console.log('Allocation finalization success:', result.message);
            window.location.href = 'admin_home.php';
        } else {
            showAlert(result.message || 'Finalization failed', 'error', { anchor: '.wizard-container' });
        }
    } catch (error) {
    showAlert('Error finalizing allocation: ' + error.message, 'error', { anchor: '.wizard-container' });
    }
}

// Wizard navigation
function nextStep() {
    if (currentStep === 1) {
        if (!selectedSemester) {
            showAlert('Please select a semester', 'warning', { anchor: '.wizard-container' });
            return;
        }
        loadFaculty();
    } else if (currentStep === 2) {
        if (selectedFaculty.length === 0) {
            showAlert('Please select at least one faculty member', 'warning', { anchor: '.wizard-container' });
            return;
        }
        loadGroups();
    }
    
    currentStep++;
    updateWizardUI();
    
    // Save state
    
}

function previousStep() {
    currentStep--;
    updateWizardUI();
    
    // Save state
    
}

function updateWizardUI() {
    // Hide all steps
    document.querySelectorAll('.wizard-content').forEach(step => {
        step.classList.add('hidden');
    });
    
    // Show current step
    document.getElementById(`step-${currentStep}`).classList.remove('hidden');
    
    // Load data for current step if needed
    if (currentStep === 2 && selectedSemester && facultyData.length === 0) {
        loadFaculty();
    } else if (currentStep === 3 && selectedSemester && groupsData.length === 0) {
        loadGroups();
    }
    
    // Update step indicators
    document.querySelectorAll('.wizard-step').forEach((step, index) => {
        step.classList.remove('active', 'completed');
        if (index + 1 < currentStep) {
            step.classList.add('completed');
        } else if (index + 1 === currentStep) {
            step.classList.add('active');
        }
    });
    
    // Update navigation buttons
    document.getElementById('btn-back').disabled = currentStep === 1;
    document.getElementById('current-step').textContent = currentStep;
    
    if (currentStep === 3) {
        document.getElementById('btn-next').classList.add('hidden');
    } else {
        document.getElementById('btn-next').classList.remove('hidden');
        // Enable next button based on step
        if (currentStep === 1) {
            document.getElementById('btn-next').disabled = !selectedSemester;
        } else if (currentStep === 2) {
            document.getElementById('btn-next').disabled = selectedFaculty.length === 0;
        }
    }
    
    feather.replace();
}

// Initialize feather icons
feather.replace();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
