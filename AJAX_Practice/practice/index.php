<?php require 'db.php'; ?>
<html>
    <head>
        <meta charset="UTF-8">
        <title></title>
    </head>
    <body>
        <h1 align="center">Civil Hospital Management System</h1>
        <form method="post">
            username:~<input type="text" name="username" value='<?php
            if (isset($_COOKIE['username'])) {
                echo $_COOKIE['username'];
            }
            ?>' required/><br>
            password:~<input type="password" name="password" value='<?php
            if (isset($_COOKIE['password'])) {
                echo $_COOKIE['password'];
            }
            ?>' required/><br>
            <input type="submit" name="login" value="Login">
            <h3><a href='register.php'>Register</a> yourself if you are a new User</h3>
        </form>
        <?php
        session_start();
        if (isset($_POST['login'])) {
            $username = $_POST['username'];
            $pass = $_POST['password'];
            $query = 'select * from patient where pname=?';
            $stmt = $conn->prepare($query);
            $stmt->bind_param('s', $username);
            $stmt->execute();
            $result = $stmt->get_result();
            $stmt->close();
            $query2 = 'select * from doctor where dname=?';
            $stmt2 = $conn->prepare($query2);
            $stmt2->bind_param('s', $username);
            $stmt2->execute();
            $result2 = $stmt2->get_result();
            if (mysqli_num_rows($result) >= 1) {
                $row = $result->fetch_assoc();
                if (password_verify($pass, $row['password'])) {
                    $_SESSION['username'] = $username;
                    setcookie('username', $username, time() + (1 * 60 * 60 * 24));
                    setcookie('password', $pass, time() + (1 * 60 * 60 * 24));
                    header("Location:patient-home.php");
                    exit();
                } else {
                    echo 'Invalid username or pass.';
                    exit();
                }
            } else if (mysqli_num_rows($result2) >= 1) {
                $row = $result2->fetch_assoc();
                if (password_verify($pass, $row['password'])) {
                    $_SESSION['username'] = $username;
                    setcookie('username', $username, time() + (1 * 60 * 60 * 24));
                    setcookie('password', $pass, time() + (1 * 60 * 60 * 24));
                    header("Location:doctor-home.php");
                    exit();
                } else {
                    echo 'Invalid username or pass.';
                    exit();
                }
            }
        }
        ?>
    </body>
</html>
