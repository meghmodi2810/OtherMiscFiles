<?php
session_start();
require_once '../db.php';

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

if ($_SESSION['user_role'] != 'faculty') {
    exit("Access denied");
}

$faculty_id = $_SESSION['faculty_id'] ?? null;
if (!$faculty_id) {
    session_destroy();
    header("Location: ../login.php");
    exit();
}

// Fetch faculty info
$stmt = $conn->prepare("SELECT name FROM faculty WHERE faculty_id = ?");
$stmt->bind_param("i", $faculty_id);
$stmt->execute();
$faculty = $stmt->get_result()->fetch_assoc();

if (!$faculty) {
    die("Faculty record not found. Please contact admin.");
}

// Setup session user array for new includes
if (!isset($_SESSION['user'])) {
    $_SESSION['user'] = [
        'user_id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'] ?? 'faculty',
        'role' => 'faculty',
        'name' => $faculty['name']
    ];
}

$page_title = 'Faculty Dashboard';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<main class="main-wrapper">
    <div class="container fade-in">
        <div class="card">
            <div class="card-header">
                <h1 class="card-title">Welcome, <?= $faculty['name'] ?>!</h1>
                <p class="card-subtitle">Faculty Dashboard</p>
            </div>
        </div>
        
        <div class="dashboard-cards">
            <div class="stat-card">
                <h3>My Students</h3>
                <p>0</p>
            </div>
            <div class="stat-card">
                <h3>My Groups</h3>
                <p>0</p>
            </div>
            <div class="stat-card">
                <h3>Pending Reviews</h3>
                <p>0</p>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Quick Actions</h2>
                <p class="card-subtitle">Common tasks and activities</p>
            </div>
            <div class="quick-actions">
                <a href="/bmiit_pms/faculty/my_projects.php" class="btn">
                    <i data-feather="folder"></i>
                    My Projects
                </a>
                <a href="/bmiit_pms/faculty/review_project_ideas.php" class="btn">
                    <i data-feather="eye"></i>
                    Review Ideas
                </a>
                <a href="/bmiit_pms/faculty/grade_milestones.php" class="btn-accent">
                    <i data-feather="check-circle"></i>
                    Grade Milestones
                </a>
                <a href="/bmiit_pms/common/messages.php" class="btn-secondary">
                    <i data-feather="message-square"></i>
                    Messages
                </a>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
