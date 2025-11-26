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

$admin_id = $_SESSION['admin_id'] ?? null;
if (!$admin_id) {
    session_destroy();
    header("Location: ../login.php");
    exit();
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

$admin_id=$_SESSION['admin_id'];
$admin_data=$conn->query("SELECT * FROM admin");
$res=$admin_data->fetch_assoc();
// Queries for dashboard cards
$totaladmin = $conn->query("SELECT COUNT(*) AS c FROM users where role='admin'")->fetch_assoc()['c'];
$totalStudents = $conn->query("SELECT COUNT(*) AS c FROM students")->fetch_assoc()['c'];
$totalFaculties = $conn->query("SELECT COUNT(*) AS c FROM faculty")->fetch_assoc()['c'];

$page_title = 'Admin Dashboard';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>
<script>
            window.onpageshow = function (event) {
                if (event.persisted) {
                    window.location.reload();
                }
            };
        </script>
<main class="main-wrapper">
    <div class="container fade-in">
        <?php if (!empty($_SESSION['flash_message'])): ?>
            <div class="alert alert-<?= htmlspecialchars($_SESSION['flash_type'] ?? 'info') ?>">
                <?= htmlspecialchars($_SESSION['flash_message']) ?>
            </div>
            <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
        <?php endif; ?>
        <div class="card">
            <div class="card-header">
                <h1 class="card-title">Welcome, <?= $res['name'] ?>!</h1>
                <p class="card-subtitle">Administrator Dashboard</p>
            </div>
        </div>
        
        <div class="dashboard-cards">
            <div class="stat-card">
                <h3>Total Admins</h3>
                <p><?php echo $totaladmin; ?></p>
            </div>
            <div class="stat-card">
                <h3>Total Students</h3>
                <p><?php echo $totalStudents; ?></p>
            </div>
            <div class="stat-card">
                <h3>Total Faculty</h3>
                <p><?php echo $totalFaculties; ?></p>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Quick Actions</h2>
                <p class="card-subtitle">Common administrative tasks</p>
            </div>
            <div class="quick-actions">
                <a href="/bmiit_pms/admin/add_student_manual.php" class="btn">
                    <i data-feather="user-plus"></i>
                    Add Student
                </a>
                <a href="/bmiit_pms/admin/add_faculty_manual.php" class="btn">
                    <i data-feather="briefcase"></i>
                    Add Faculty
                </a>
                <a href="/bmiit_pms/admin/create_new_sem.php" class="btn-accent">
                    <i data-feather="calendar"></i>
                    Create Semester
                </a>
                <a href="/bmiit_pms/admin/manage_groups.php" class="btn-secondary">
                    <i data-feather="layers"></i>
                    Manage Groups
                </a>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
