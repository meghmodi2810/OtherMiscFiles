<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$error = '';
$success = '';

if (isset($_GET['passkey'])) {
    $user_id = $_SESSION['user_id'];
    $role = '';
    $user_name = '';

    $stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE id=?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && mysqli_num_rows($result) == 1) {
        $row = $result->fetch_assoc();
        $role = $row['role'];
    } else {
        die("Invalid user or passkey.");
    }

    if ($role == 'student') {
        $stmt = mysqli_prepare($conn, "SELECT name FROM students WHERE user_id=?");
    } elseif ($role == 'faculty') {
        $stmt = mysqli_prepare($conn, "SELECT name FROM faculty WHERE user_id=?");
    } else {
        $stmt = mysqli_prepare($conn, "SELECT username AS name FROM users WHERE id=?");
    }

    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && mysqli_num_rows($result) == 1) {
        $row = $result->fetch_assoc();
        $user_name = $row['name'];
    } else {
        die("Invalid user.");
    }
} else {
    header("Location: login.php");
    exit();
}

if (isset($_POST['setpass'])) {
    $new_pass = $_POST['new-pass'];
    $con_pass = $_POST['confirm-pass'];

    if ($new_pass != $con_pass) {
        $error = "Passwords do not match.";
    } elseif (strlen($new_pass) < 8) {
        $error = "Password must be at least 8 characters long.";
    } elseif (!preg_match('/[A-Z]/', $new_pass)) {
        $error = "Password must include at least one uppercase letter.";
    } elseif (!preg_match('/[0-9]/', $new_pass)) {
        $error = "Password must include at least one number.";
    } elseif (ctype_alnum($new_pass)) {
        $error = "Password must include at least one special character.";
    } elseif ($new_pass == $_GET['passkey']) {
        $error = "New password cannot be the same as the old password.";
    } else {
        $new_pass_hash = password_hash($new_pass, PASSWORD_BCRYPT);
        $stmt = mysqli_prepare($conn, "UPDATE users SET password_hash=?, first_login=0, is_active=1 WHERE id=?");
        $stmt->bind_param('si', $new_pass_hash, $user_id);
        $stmt->execute();

        if ($role == 'student') {
            mysqli_query($conn, "UPDATE students SET temp_passkey=NULL WHERE user_id=$user_id");
        } elseif ($role == 'faculty') {
            mysqli_query($conn, "UPDATE faculty SET temp_passkey=NULL WHERE user_id=$user_id");
        } elseif ($role == 'admin') {
            mysqli_query($conn, "UPDATE admin SET temp_passkey=NULL WHERE user_id=$user_id");
        }

        $success = "Password updated successfully!";
        session_destroy();
        header("Location: login.php?msg=passchanged");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/ico" href="assets/bmiitfavicol.ico">
    <title>Set Password</title>
    
    <!-- Google Fonts - Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <link rel="stylesheet" href="css/setpass.css">
    <script>
        window.onpageshow = function (event) {
            if (event.persisted) {
                window.location.reload();
            }
        };
    </script>
</head>
<body>
    <!-- Left Panel -->
     
    <div class="login-panel">
        <img src="assets/Bmiit.png" alt="BMIIT Logo">
        <h1>BMIIT Project Management Portal</h1>
        <hr>
        <p>Your gateway to smarter project management.</p>

    </div>

    <!-- Right Panel -->
    <div class="login-form-container">
        <div class="login-container">
            <h2>Set Password</h2>
            <p style="text-align: center; margin-bottom: 20px; color: #666;">Welcome, <?php echo $user_name; ?>!</p>
            <div class="form-alerts">
                <?php if ($error): ?>
                    <div class="alert error"><?php echo $error; ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="alert success"><?php echo $success; ?></div>
                <?php endif; ?>
            </div>


            <form method="post">
                <div class="password-container">
                    <input type="password" name="new-pass" id="new-pass" placeholder="New Password" required>
                    <i class="fas fa-eye toggle-password" data-target="new-pass"></i>
                </div>
                <div class="password-container">
                    <input type="password" name="confirm-pass" id="confirm-pass" placeholder="Confirm Password" required>
                    <i class="fas fa-eye toggle-password" data-target="confirm-pass"></i>
                </div>

                <input type="submit" name="setpass" value="Set Password">
            </form>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const toggleButtons = document.querySelectorAll('.toggle-password');
            
            toggleButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const targetId = this.getAttribute('data-target');
                    const input = document.getElementById(targetId);
                    
                    if (input.type === 'password') {
                        input.type = 'text';
                        this.classList.remove('fa-eye');
                        this.classList.add('fa-eye-slash');
                    } else {
                        input.type = 'password';
                        this.classList.remove('fa-eye-slash');
                        this.classList.add('fa-eye');
                    }
                });
            });
        });
    </script>
</body>
</html>
