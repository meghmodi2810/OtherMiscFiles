<?php
session_start();
require_once '../db.php';
require '../email_system/email_helper.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

if ($_SESSION['user_role'] != 'admin') {
    exit("Access denied");
}

$message = "";
$alert_type = "";

if (isset($_POST["faculty_form_submit"])) {
    $name = trim($_POST['faculty_name']);
    $email = trim($_POST['faculty_email']);
    $phone = trim($_POST['faculty_phone']);
    $specialization = trim($_POST['faculty_specialization']);
    $experience = (int) $_POST['faculty_experience'];

    // check duplicate email
    $stmtEmail = $conn->prepare("SELECT faculty_id FROM faculty WHERE email=?");
    $stmtEmail->bind_param("s", $email);
    $stmtEmail->execute();
    $stmtEmail->store_result();

    if ($stmtEmail->num_rows > 0) {
        $message = "Email already exists!";
        $alert_type = "error";
        $stmtEmail->close();
    } else {
        $stmtEmail->close();

        // next user id
        $result = $conn->query("SELECT MAX(id) AS max_id FROM users");
        $row = $result->fetch_assoc();
        $next_user_id = $row['max_id'] ? $row['max_id'] + 1 : 1;

        // random password
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $passkey = '';
        for ($i = 0; $i < 8; $i++) {
            $passkey .= $chars[random_int(0, strlen($chars) - 1)];
        }
        $hashed_password = password_hash($passkey, PASSWORD_BCRYPT);

        $stmtUser = $conn->prepare("INSERT INTO users (id, username, password_hash, role, first_login, is_active) VALUES (?, ?, ?, 'faculty', 1, 0)");
        $stmtUser->bind_param("iss", $next_user_id, $email, $hashed_password);

        if ($stmtUser->execute()) {
            $result2 = $conn->query("SELECT MAX(faculty_id) AS max_id FROM faculty");
            $row2 = $result2->fetch_assoc();
            $next_faculty_id = $row2['max_id'] ? $row2['max_id'] + 1 : 1;

            $stmtFaculty = $conn->prepare("INSERT INTO faculty(faculty_id, user_id, name, email, phone, specialization, experience, temp_passkey) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmtFaculty->bind_param("iissssis", $next_faculty_id, $next_user_id, $name, $email, $phone, $specialization, $experience, $passkey);

                if ($stmtFaculty->execute()) {
                    $messageLine = "You have been successfully registered into the BMIIT Project Management Portal.\n\nUsername: $email\nPasskey: $passkey\n\nAfter first login, you can set your own password.";
                    list($htmlBody, $plainBody) = buildSimpleEmailBody($name, $messageLine);
                    $emailResult = sendEmail($email, $name, 'Welcome to BMIIT Project Management Portal', $htmlBody, $plainBody);

                    if ($emailResult['success']) {
                        $message = "Faculty added. Credentials emailed to $email.";
                        $alert_type = "success";
                    } elseif (isset($emailResult['paused']) && $emailResult['paused']) {
                        $message = "Faculty added. Emails were not sent as the email service is disabled right now. Please share credentials manually.";
                        $alert_type = "warning";
                    } else {
                        $message = "Faculty added, but email was not sent.";
                        $alert_type = "error";
                    }
            }
            $stmtFaculty->close();
        } else {
            $message = "Error inserting user: " . $stmtUser->error;
            $alert_type = "error";
        }
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

// Specializations list (kept in sync with the bulk upload template)
$specializations = array(
    'Data Science',
    'Web Development',
    'Mobile Development',
    'Machine Learning',
    'Artificial Intelligence',
    'Cybersecurity',
    'Cloud Computing',
    'DevOps',
    'Database Management',
    'Software Engineering',
    'Network Administration',
    'IoT',
    'Blockchain',
    'UI/UX Design',
    'Other'
);

$page_title = 'Add Faculty Manually';
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
                <h1 class="card-title">Add Faculty Manually</h1>
                <p class="card-subtitle">Register a new faculty member into the system</p>
            </div>

            <form method="post" id="faculty_form" class="form-grid-2">
                <div class="form-group">
                    <label for="faculty_name">Full Name</label>
                    <input type="text" 
                           id="faculty_name" 
                           name="faculty_name" 
                           pattern="[A-Za-z ]+" 
                           placeholder="Enter faculty name"
                           value="<?= isset($_POST['faculty_name']) ? htmlspecialchars($_POST['faculty_name']) : '' ?>" 
                           required/>
                    <small class="help-text">Only letters and spaces allowed</small>
                </div>

                <div class="form-group">
                    <label for="faculty_email">Email Address</label>
                    <input type="email" 
                           name="faculty_email" 
                           id="faculty_email"
                           placeholder="faculty@example.com"
                           value="<?= isset($_POST['faculty_email']) ? htmlspecialchars($_POST['faculty_email']) : '' ?>" 
                           required/>
                    <small class="help-text">This will be used as the username</small>
                </div>

                <div class="form-group">
                    <label for="faculty_phone">Phone Number</label>
                    <input type="tel" 
                           name="faculty_phone" 
                           id="faculty_phone"
                           pattern="[0-9]{10}"
                           placeholder="10-digit mobile number"
                           value="<?= isset($_POST['faculty_phone']) ? htmlspecialchars($_POST['faculty_phone']) : '' ?>" 
                           required/>
                    <small class="help-text">10 digits only, no spaces or special characters</small>
                </div>

                <div class="form-group">
                    <label for="faculty_specialization">Specialization</label>
                    <select name="faculty_specialization" id="faculty_specialization" required>
                        <option value="">Select specialization</option>
                        <?php
                            foreach ($specializations as $spec) {
                                $selected = (isset($_POST['faculty_specialization']) && $_POST['faculty_specialization'] === $spec) ? ' selected' : '';
                                echo '<option value="' . $spec . '"' . $selected . '>' . $spec . '</option>';
                            }
                        ?>
                    </select>
                    <small class="help-text">Area of expertise</small>
                </div>

                <div class="form-group">
                    <label for="faculty_experience">Years of Experience</label>
                    <input type="number" 
                           name="faculty_experience" 
                           id="faculty_experience"
                           min="0" 
                           max="50"
                           placeholder="Years"
                           value="<?= isset($_POST['faculty_experience']) ? htmlspecialchars($_POST['faculty_experience']) : '' ?>" 
                           required/>
                    <small class="help-text">Total teaching/industry experience</small>
                </div>

                <div class="form-actions" style="grid-column: 1 / -1;">
                    <button type="submit" id="faculty_form_submit" name="faculty_form_submit" class="btn">
                        <i data-feather="user-plus"></i>
                        Add Faculty
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
                    <li>A random 8-character password will be generated and sent to the faculty's email</li>
                    <li>The faculty will be required to change their password on first login</li>
                    <li>The email address will serve as the username for login</li>
                    <li>Ensure the email address is valid and accessible by the faculty member</li>
                    <li>Faculty accounts are created as inactive until first login</li>
                </ul>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
