<!DOCTYPE html>
<!--
Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/EmptyPHPWebPage.php to edit this template
-->
<html>
    <head>
        <meta charset="UTF-8">
        <title>Login page</title>
    </head>
    <body>
        <?php 
            session_start();
            if(isset($_SESSION['username'])) {
                header("Location: booking.php");
            }
        ?>
        <form action="" method="POST">
            <label for="lblUsername">
                Username :
            </label>
            <input type="text" name="txtUsername" id="txtUsername" placeholder="Enter your username.." required><BR><BR>
            <label for="lblPassword">
                Password :
            </label>
            <input type="password" name="txtPassword" id="txtPassword" placeholder="Enter your password.." required><BR><BR>
            <input type="submit" value="Login" name="btnLogin">
        </form>
        <?php
            if(isset($_POST['btnLogin'])) {
                $username = $_POST['txtUsername'];
                $password = $_POST['txtPassword'];
                
                if($username == 'Admin' && $password == 'Admin123') {
                    $_SESSION['username'] = $username;
                    header("Location: booking.php");
                }
            }
        ?>
    </body>
</html>
