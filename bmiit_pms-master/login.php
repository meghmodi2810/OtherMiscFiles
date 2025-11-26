<?php
session_start();
require_once 'db.php';
$error = '';
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['user_role'] == 'admin') {
        header("Location: admin/admin_home.php");
        exit();
    } elseif ($_SESSION['user_role'] == 'faculty') {
        header("Location: faculty/faculty_home.php");
        exit();
    } elseif ($_SESSION['user_role'] == 'student') {
        header("Location: student/student_home.php");
        exit();
    }
}

$success = '';

if (isset($_GET['msg']) && $_GET['msg'] == 'passchanged') {
    $success = "Password changed successfully! Please login.";
}
if (isset($_POST['submit'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (strlen($password) < 8) {
        $error = "Password must be at least 8 characters long.";
    }

    if (!ctype_digit($username) && !filter_var($username, FILTER_VALIDATE_EMAIL)) {
        $error = "Username must be a valid Enrollment no or email. No Special characters allowed.";
    }

    if ($error == '') {

        $stmt = $conn->prepare("SELECT * FROM users WHERE username=? limit 1");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && mysqli_num_rows($result) == 1) {
            $row = $result->fetch_assoc();
            if (password_verify($password, $row['password_hash'])) {
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['user_role'] = $row['role'];
                if (isset($_POST['remember_me'])) {
                    setcookie('username', $row['username'], time() + 60 * 60 * 24 * 7);
                    setcookie('password', $_POST['password'], time() + 60 * 60 * 24 * 7);
                }
                if ($row['first_login'] == 0) {
                    if ($row['role'] == 'admin') {

                        $stmt = $conn->prepare("SELECT admin_id FROM admin WHERE user_id=?");
                        $stmt->bind_param("i", $row['id']);
                        $stmt->execute();
                        $res = $stmt->get_result()->fetch_assoc();
                        $_SESSION['admin_id'] = $res['admin_id'];

                        header("Location: admin/admin_home.php");
                        exit();
                    } elseif ($row['role'] == 'faculty') {

                        $stmt = $conn->prepare("SELECT faculty_id FROM faculty WHERE user_id=?");
                        $stmt->bind_param("i", $row['id']);
                        $stmt->execute();
                        $res = $stmt->get_result()->fetch_assoc();
                        $_SESSION['faculty_id'] = $res['faculty_id'];

                        header("Location: faculty/faculty_home.php");
                        exit();
                    } elseif ($row['role'] == 'student') {

                        $stmt = $conn->prepare("SELECT student_id FROM students WHERE user_id=?");
                        $stmt->bind_param("i", $row['id']); // use $row, not $user
                        $stmt->execute();
                        $res = $stmt->get_result()->fetch_assoc();
                        $_SESSION['student_id'] = $res['student_id'];

                        header("Location: student/student_home.php");
                        exit();
                    }
                } else {
                    header("Location:set_password.php?passkey=$password");
                    exit();
                }
            } else {
                $error = "Invalid Username or Password.";
            }
        } else {
            $error = "Invalid Username or Password.";
        }
    }
}
?>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <link rel="icon" type="image/ico" href="assets/bmiitfavicol.ico">
        <title>BMIIT PMS Login</title>
        
        <!-- Google Fonts - Inter -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
        
        <!-- Font Awesome -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
        
        <link rel="stylesheet" href="css/login.css">
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
                <h2>Login</h2>
                <div class="form-alerts">
                    <?php if ($error): ?>
                        <div class="alert error"><?php echo $error; ?></div>
                    <?php endif; ?>
                    <?php if ($success): ?>
                        <div class="alert success"><?php echo $success; ?></div>
                    <?php endif; ?>
                </div>


                <form method="post">
                    <input type="text" name="username" placeholder="Email or Enrollment ID" 
                           value="<?php
                           if (isset($_POST['username'])) {
                               echo $_POST['username'];
                           } elseif (isset($_COOKIE['username']))
                               echo $_COOKIE['username'];
                           ?>" required>

                    <div class="password-container">
                        <input type="password" name="password" id="password" placeholder="Password" 
                               value="<?php
                               if (isset($_POST['password'])) {
                                   echo $_POST['password'];
                               } elseif (isset($_COOKIE['password']))
                                   echo $_COOKIE['password'];
                               ?>" required>
                        <i class="fas fa-eye toggle-password" data-target="password"></i>
                    </div>

                    <div class="checkbox-container">
                        <input type="checkbox" name="remember_me" id="remember_me">
                        <label for="remember_me">Remember Me</label>
                    </div>

                    <input type="submit" name="submit" value="Login">
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