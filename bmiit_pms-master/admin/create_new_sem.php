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

$message = "";

if (isset($_POST['submit'])) {
    $course_id = (int) $_POST['course_id'];
    $semester_no = (int) $_POST['semester_no'];
    $num_classes = (int) $_POST['num_classes'];
    $interclass = (int) $_POST['interclass_allowed'];
    $team_size = (int) $_POST['team_size'];

    $check_sql = "SELECT * FROM semesters WHERE year = ? AND semester_no = ? AND course_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("sii", $year, $semester_no, $course_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        $message = "Error: Semester already exists for this academic year and course!";
    } else {
        $result = $conn->query("SELECT MAX(id) AS max_id FROM semesters");
        $row = $result->fetch_assoc();
        $semester_id = $row['max_id'] ? $row['max_id'] + 1 : 1;

        $result2 = $conn->query("SELECT MAX(id) AS max_id FROM classes");
        $row2 = $result2->fetch_assoc();
        $next_class_id = $row2['max_id'] ? $row2['max_id'] + 1 : 1;

        $result3 = $conn->query("SELECT MAX(id) AS max_id FROM semester_config");
        $row3 = $result3->fetch_assoc();
        $config_id = $row3['max_id'] ? $row3['max_id'] + 1 : 1;

        $currentYear = date("Y");          // e.g., 2025
        $nextYearShort = date("y") + 1;    // e.g., 26
        $academicYear = $currentYear . "-" . $nextYearShort;

        // We no longer need to deactivate other semesters as multiple can be active now

        $stmt = $conn->prepare("INSERT INTO semesters (id, year, semester_no, course_id, project_active) VALUES (?, ?, ?, ?, 1)");
        $stmt->bind_param("isii", $semester_id, $academicYear, $semester_no, $course_id);
        $stmt->execute();

        for ($i = 0; $i < $num_classes; $i++) {
            $class_name = chr(65 + $i); // 'A', 'B', ...
            $stmt2 = $conn->prepare("INSERT INTO classes (id, semester_id, name) VALUES (?, ?, ?)");
            $stmt2->bind_param("iis", $next_class_id, $semester_id, $class_name);
            $stmt2->execute();
            $next_class_id++;
        }

        $stmt3 = $conn->prepare("INSERT INTO semester_config (id, semester_id, interclass_allowed, team_size) VALUES (?, ?, ?, ?)");
        $stmt3->bind_param("iiii", $config_id, $semester_id, $interclass, $team_size);
        $stmt3->execute();

        $message = "Semester created successfully with $num_classes class(es).";
    }
}

// Setup session user array for new includes
if (!isset($_SESSION['user'])) {
    $_SESSION['user'] = [
        'user_id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'] ?? 'admin',
        'role' => 'admin'
    ];
}

$page_title = 'Create New Semester';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<main class="main-wrapper">
    <div class="container fade-in">
        <?php if ($message): ?>
            <div class="alert alert-<?= strpos($message, 'Error') !== false ? 'error' : 'success' ?>">
                <?= $message ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h1 class="card-title">
                    <i data-feather="calendar"></i>
                    Create New Semester
                </h1>
                <p class="card-subtitle">Configure semester settings and academic structure</p>
            </div>
            
            <form method="POST" id="create_sem_form" class="form-grid-2">
                <div class="form-group">
                    <label for="course_id">Course</label>
                    <select name="course_id" id="course_id" required>
                        <option value="">Select a course</option>
                        <option value="1">B.Sc (IT)</option>
                        <option value="2">M.Sc (IT)</option>
                        <option value="3">Integrated M.Sc (IT)</option>
                    </select>
                    <small class="help-text">Choose the program for this semester</small>
                </div>

                <div class="form-group">
                    <label for="semester_no">Semester Number</label>
                    <select name="semester_no" id="semester_no" required>
                        <option value="">Select semester</option>
                    </select>
                    <small class="help-text">Available semesters based on selected course</small>
                </div>

                <div class="form-group">
                    <label for="num_classes">Number of Classes</label>
                    <input type="number" 
                           name="num_classes" 
                           id="num_classes" 
                           min="1" 
                           max="10" 
                           placeholder="e.g., 3"
                           required>
                    <small class="help-text">How many divisions (A, B, C, etc.)</small>
                </div>

                <div class="form-group">
                    <label for="team_size">Team Size</label>
                    <input type="number" 
                           name="team_size" 
                           id="team_size" 
                           min="2" 
                           max="10" 
                           placeholder="e.g., 4"
                           required>
                    <small class="help-text">Maximum members per project group</small>
                </div>

                <div class="form-group" style="grid-column: 1 / -1;">
                    <label>Allow Interclass Groups?</label>
                    <div class="radio-group">
                        <label>
                            <input type="radio" name="interclass_allowed" value="1" required>
                            Yes - Students can form groups across divisions
                        </label>
                        <label>
                            <input type="radio" name="interclass_allowed" value="0" checked required>
                            No - Groups limited to same division
                        </label>
                    </div>
                </div>

                <div class="form-actions" style="grid-column: 1 / -1;">
                    <button type="submit" name="submit" class="btn">
                        <i data-feather="plus-circle"></i>
                        Create Semester
                    </button>
                    <a href="/bmiit_pms/admin/admin_home.php" class="btn-secondary">
                        <i data-feather="arrow-left"></i>
                        Back to Dashboard
                    </a>
                </div>
            </form>
        </div>

        <!-- Information Card -->
        <div class="card" style="margin-top: 20px;">
            <div class="card-header">
                <h3 class="card-title">
                    <i data-feather="info"></i>
                    What happens when you create a semester?
                </h3>
            </div>
            <div class="card-body">
                <ul style="line-height: 1.6; padding-left: 20px; font-size: 13px;">
                    <li>The system will create the specified number of class divisions (A, B, C, etc.)</li>
                    <li>Project activities will be enabled for this semester immediately</li>
                    <li>Team size restrictions will be enforced during group formation</li>
                    <li>Students can be assigned to this semester after creation</li>
                    <li>Multiple semesters can be active simultaneously</li>
                </ul>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
