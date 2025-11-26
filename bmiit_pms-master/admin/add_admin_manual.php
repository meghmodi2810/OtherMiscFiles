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

// Setup session user array for new includes
if (!isset($_SESSION['user'])) {
    $_SESSION['user'] = [
        'user_id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'] ?? 'admin',
        'role' => $_SESSION['user_role'],
        'name' => $_SESSION['username'] ?? 'Administrator'
    ];
}

$message = "";
$alert_type = "";

if (isset($_POST["admin_form_submit"])) {
    $name = trim($_POST['admin_name']);
    $email = trim($_POST['admin_email']);
    $phone = trim($_POST['admin_phone']);

    // Check duplicate email
    $stmtEmail = $conn->prepare("SELECT admin_id FROM admin WHERE email=?");
    $stmtEmail->bind_param("s", $email);
    $stmtEmail->execute();
    $stmtEmail->store_result();

    if ($stmtEmail->num_rows > 0) {
        $message = "Email already exists!";
        $alert_type = "error";
        $stmtEmail->close();
    } else {
        $stmtEmail->close();

        // Get next user id
        $result = $conn->query("SELECT MAX(id) AS max_id FROM users");
        $row = $result->fetch_assoc();
        $next_user_id = $row['max_id'] ? $row['max_id'] + 1 : 1;

        // Generate random password
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $passkey = '';
        for ($i = 0; $i < 8; $i++) {
            $passkey .= $chars[random_int(0, strlen($chars) - 1)];
        }
        $hashed_password = password_hash($passkey, PASSWORD_BCRYPT);

        $stmtUser = $conn->prepare("INSERT INTO users (id, username, password_hash, role, first_login, is_active) VALUES (?, ?, ?, 'admin', 1, 1)");
        $stmtUser->bind_param("iss", $next_user_id, $email, $hashed_password);

        if ($stmtUser->execute()) {
            // Get next admin id
            $result2 = $conn->query("SELECT MAX(admin_id) AS max_id FROM admin");
            $row2 = $result2->fetch_assoc();
            $next_admin_id = $row2['max_id'] ? $row2['max_id'] + 1 : 1;

            $stmtAdmin = $conn->prepare("INSERT INTO admin(admin_id, user_id, name, email, phone, temp_passkey) VALUES (?, ?, ?, ?, ?, ?)");
            $stmtAdmin->bind_param("iissss", $next_admin_id, $next_user_id, $name, $email, $phone, $passkey);

            if ($stmtAdmin->execute()) {
                // Send a standardized welcome email (no links)
                $messageLine = "You have been successfully registered as an Administrator in the BMIIT Project Management Portal.\n\nUsername: $email\nPasskey: $passkey\n\nAfter first login, you can set your own password.";
                list($htmlBody, $plainBody) = buildSimpleEmailBody($name, $messageLine);
                $emailResult = sendEmail($email, $name, 'Welcome to BMIIT Project Management Portal - Admin Access', $htmlBody, $plainBody);

                if ($emailResult['success']) {
                    $message = "Admin added. Credentials emailed to $email.";
                    $alert_type = "success";
                } elseif (isset($emailResult['paused']) && $emailResult['paused']) {
                    $message = "Admin added. Emails were not sent as the email service is disabled right now. Please share credentials manually.";
                    $alert_type = "warning";
                } else {
                    $message = "Admin added, but email was not sent.";
                    $alert_type = "error";
                }
            } else {
                $message = "Error inserting admin: " . $stmtAdmin->error;
                $alert_type = "error";
            }
            $stmtAdmin->close();
        } else {
            $message = "Error inserting user: " . $stmtUser->error;
            $alert_type = "error";
        }
        $stmtUser->close();
    }
}

$page_title = 'Add Administrator';
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
                    <i data-feather="shield"></i>
                    Add New Administrator
                </h1>
                <p class="card-subtitle">Register a new administrator into the system</p>
            </div>

            <form method="post" id="admin_form" class="form-grid-2">
                <div class="form-group">
                    <label for="admin_name">Full Name</label>
                    <input type="text" 
                           id="admin_name" 
                           name="admin_name" 
                           pattern="[A-Za-z ]+"
                           placeholder="Enter administrator's full name"
                           value="<?php if(isset($_POST['admin_name'])) echo htmlspecialchars($_POST['admin_name']); ?>" 
                           required/>
                    <small class="help-text">Only letters and spaces allowed</small>
                </div>

                <div class="form-group">
                    <label for="admin_email">Email Address</label>
                    <input type="email" 
                           id="admin_email"
                           name="admin_email" 
                           placeholder="admin@example.com"
                           value="<?php if(isset($_POST['admin_email'])) echo htmlspecialchars($_POST['admin_email']); ?>" 
                           required/>
                    <small class="help-text">This will be used as the username</small>
                </div>

                <div class="form-group">
                    <label for="admin_phone">Phone Number</label>
                    <input type="tel" 
                           id="admin_phone"
                           name="admin_phone" 
                           pattern="[0-9]{10}"
                           placeholder="10-digit phone number"
                           value="<?php if(isset($_POST['admin_phone'])) echo htmlspecialchars($_POST['admin_phone']); ?>" 
                           required/>
                    <small class="help-text">10 digits only, no spaces or special characters</small>
                </div>

                <div class="form-actions" style="grid-column: 1 / -1;">
                    <button type="submit" id="admin_form_submit" name="admin_form_submit" class="btn">
                        <i data-feather="user-plus"></i>
                        Add Administrator
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
                    <li>A random 8-character password will be generated and sent to the admin's email</li>
                    <li>The admin will be required to change their password on first login</li>
                    <li>Admin accounts are activated immediately upon creation</li>
                    <li>The email address will serve as the username for login</li>
                    <li>Ensure the email address is valid and accessible by the new administrator</li>
                </ul>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
