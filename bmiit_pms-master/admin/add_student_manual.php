<?php
session_start();
require_once '../db.php';
require '../email_system/email_helper.php';
require_once __DIR__ . '/includes/bulk_helpers.php';  // For validation functions

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
$alert_type = "";

if (isset($_POST["student_form_submit"])) {
    $enroll = trim($_POST["student_enroll"]);
    $name = trim($_POST['student_name']);
    $email = trim($_POST['student_email']);
    $class_id = $_POST['student_class'];
    $phone = trim($_POST['student_phone']);

    // Validate phone number (including dummy number check)
    if (!is_valid_phone($phone)) {
        $phoneError = get_validation_error('phone', $phone);
        $message = $phoneError ? $phoneError : "Invalid phone number";
        $alert_type = "error";
    }
    // Validate email (including dummy email check)
    elseif (!is_valid_email($email)) {
        $emailError = get_validation_error('email', $email);
        $message = $emailError ? $emailError : "Invalid email address";
        $alert_type = "error";
    }
    // Validate name
    elseif (!is_valid_name($name)) {
        $nameError = get_validation_error('name', $name);
        $message = $nameError ? $nameError : "Invalid name format";
        $alert_type = "error";
    }
    else {
        $stmt = $conn->prepare("SELECT id FROM users WHERE username=?");
        $stmt->bind_param("s", $enroll);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $message = "Enrollment number already exists!";
            $alert_type = "error";
        } else {
            $stmtEmail = $conn->prepare("SELECT student_id FROM students WHERE email=?");
            $stmtEmail->bind_param("s", $email);
            $stmtEmail->execute();
            $stmtEmail->store_result();

            if ($stmtEmail->num_rows > 0) {
                $message = "Email already exists!";
                $alert_type = "error";
                $stmtEmail->close();
            } else {
            $result = $conn->query("SELECT MAX(id) AS max_id FROM users");
            $row = $result->fetch_assoc();
            $next_id = $row['max_id'] ? $row['max_id'] + 1 : 1;

            $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
            $passkey = '';
            for ($i = 0; $i < 8; $i++) {
                $passkey .= $chars[random_int(0, strlen($chars) - 1)];
            }

            $hashed_password = password_hash($passkey, PASSWORD_BCRYPT);

            $stmt = $conn->prepare("INSERT INTO users (id,username, password_hash, role, first_login, is_active) VALUES (?, ?, ?, 'student', 1, 0)");
            $stmt->bind_param("iss", $next_id, $enroll, $hashed_password);

            if ($stmt->execute()) {
                $result2 = $conn->query("SELECT MAX(student_id) AS max_id FROM students");
                $row2 = $result2->fetch_assoc();
                $next_student_id = $row2['max_id'] ? $row2['max_id'] + 1 : 1;

                $stmt2 = $conn->prepare("INSERT INTO students(student_id, user_id, name, email, class_id, phone, temp_passkey) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt2->bind_param("iississ", $next_student_id, $next_id, $name, $email, $class_id, $phone, $passkey);

                if ($stmt2->execute()) {
                    $messageLine = "You have been successfully registered into the BMIIT Project Management Portal.\n\nUsername: $enroll\nPasskey: $passkey\n\nAfter first login, you can set your own password.";
                    list($htmlBody, $plainBody) = buildSimpleEmailBody($name, $messageLine);
                    $emailResult = sendEmail($email, $name, 'Welcome to BMIIT Project Management Portal', $htmlBody, $plainBody);

                    if ($emailResult['success']) {
                        $message = "Student added. Credentials emailed to $email.";
                        $alert_type = "success";
                    } elseif (isset($emailResult['paused']) && $emailResult['paused']) {
                        $message = "Student added. Emails were not sent as the email service is disabled right now. Please share credentials manually.";
                        $alert_type = "warning";
                    } else {
                        $message = "Student added, but email was not sent.";
                        $alert_type = "error";
                    }
                } else {
                    $message = "Error inserting student data: " . $stmt2->error;
                    $alert_type = "error";
                }
                $stmt2->close();
            } else {
                $message = "Error inserting user: " . $stmt->error;
                $alert_type = "error";
            }
            $stmt->close();
        }
        }
    }
}

// Setup session user array for new includes
if (!isset($_SESSION['user'])) {
    $_SESSION['user'] = [
        'user_id' => $_SESSION['user_id'],
        'username' => $_SESSION['user_id'] ?? 'admin',
        'role' => $_SESSION['user_role'] ?? 'admin'
    ];
}

$page_title = 'Add Student Manually';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<main class="main-wrapper">
    <div class="container fade-in">
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?= $alert_type ?>">
                <?= $message ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h1 class="card-title">
                    <i data-feather="user-plus"></i>
                    Add Student Manually
                </h1>
                <p class="card-subtitle">Register a new student into the system</p>
            </div>

            <?php
            $query = "SELECT s.id as semester_id, c.name as course_name, s.semester_no, s.year 
                     FROM semesters s 
                     JOIN courses c ON s.course_id = c.id 
                     WHERE s.project_active = 1 
                     ORDER BY c.name, s.semester_no";
            $result = $conn->query($query);
            $active_semesters = array();
            while ($row = $result->fetch_assoc()) {
                $active_semesters[] = $row;
            }

            $classResult = $conn->query("SELECT id, name, semester_id FROM classes");
            $classes = [];
            while ($row = $classResult->fetch_assoc()) {
                $classes[] = $row;
            }
            ?>

            <form method="post" id="student_form" class="form-grid-2">
                <div class="form-group">
                    <label for="student_name">Full Name</label>
                    <input type="text" 
                           id="student_name" 
                           name="student_name" 
                           pattern="[A-Za-z ]+" 
                           placeholder="Enter student's full name"
                           value="<?= isset($_POST['student_name']) ? htmlspecialchars($_POST['student_name']) : '' ?>" 
                           required/>
                    <small class="help-text">Only letters and spaces allowed</small>
                </div>

                <div class="form-group">
                    <label for="student_enroll">Enrollment Number</label>
                    <input type="text" 
                           id="student_enroll" 
                           name="student_enroll" 
                           pattern="[0-9]{15}" 
                           placeholder="202307100110087" 
                           value="<?= isset($_POST['student_enroll']) ? htmlspecialchars($_POST['student_enroll']) : '' ?>" 
                           required/>
                    <small class="help-text">15-digit enrollment number</small>
                </div>

                <div class="form-group">
                    <label for="student_email">Email Address</label>
                    <input type="email" 
                           id="student_email" 
                           name="student_email" 
                           placeholder="student@example.com" 
                           value="<?= isset($_POST['student_email']) ? htmlspecialchars($_POST['student_email']) : '' ?>" 
                           required/>
                    <small class="help-text">Use real email (avoid dummy emails like abc@abc.com)</small>
                </div>

                <div class="form-group">
                    <label for="student_phone">Phone Number</label>
                    <input type="tel" 
                           name="student_phone" 
                           id="student_phone" 
                           pattern="[0-9]{10}" 
                           placeholder="9876543210" 
                           value="<?= isset($_POST['student_phone']) ? htmlspecialchars($_POST['student_phone']) : '' ?>" 
                           required/>
                    <small class="help-text">10-digit mobile number (avoid dummy numbers)</small>
                </div>

                <div class="form-group">
                    <label for="student_semester_id">Course & Semester</label>
                    <select name="student_semester_id" id="student_semester_id" required>
                        <option value="">Select course and semester</option>
                        <?php foreach ($active_semesters as $sem): ?>
                            <option value="<?= $sem['semester_id'] ?>">
                                <?= "{$sem['course_name']} - Semester {$sem['semester_no']} ({$sem['year']})" ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small class="help-text">Choose the semester to assign this student</small>
                </div>

                <div class="form-group">
                    <label for="student_class">Division</label>
                    <select name="student_class" id="student_class" required>
                        <option value="">First select course & semester</option>
                    </select>
                    <small class="help-text">Division will be populated after selecting semester</small>
                </div>

                <div class="form-actions" style="grid-column: 1 / -1;">
                    <button type="submit" id="student_form_submit" name="student_form_submit" class="btn">
                        <i data-feather="user-plus"></i>
                        Add Student
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
                    Important Information
                </h3>
            </div>
            <div class="card-body">
                <ul style="line-height: 1.6; padding-left: 20px; font-size: 13px;">
                    <li>A random 8-character password will be generated and sent to the student's email</li>
                    <li>The student will be required to change their password on first login</li>
                    <li>The enrollment number will serve as the username for login</li>
                    <li>Ensure the email address is valid and accessible by the student</li>
                    <li>Student accounts are created as inactive until first login</li>
                    <li>Avoid using dummy/test data (fake emails, repeated phone numbers, etc.)</li>
                </ul>
            </div>
        </div>
    </div>
</main>

<!-- Inject classes data for JavaScript to use -->
<script>
window.classesData = <?= json_encode($classes) ?>;
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
