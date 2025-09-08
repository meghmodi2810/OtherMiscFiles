<!DOCTYPE html>
<!--
Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
Click nbfs://nbhost/SystemFileSystem/Templates/Project/PHP/PHPProject.php to edit this template
-->
<html>
    <head>
        <meta charset="UTF-8">
        <title></title>
    </head>
    <body>
        <?php
        session_start();
        if (isset($_SESSION['username'])) {
            header('Location: Redirector.php');
        }
        if(isset($_COOKIE['LoginCreds'])) {
            $str = $_COOKIE['LoginCreds'];
            $loginCreds = explode(',', $str);
        } else {
            $loginCreds = array("", "");
        }
        if(isset($_POST['btnDelete'])) {
            setcookie('LoginCreds', '', time()-3600*24);
        }
        ?>
        <form method="post" action="">
            <table>
                <tr>
                    <td>
                        Username : 
                    </td>
                    <td>
                        <input type="text" name="txtName" id="txtName" placeholder="Enter your username.." value='<?php echo $loginCreds[0] ?>' required>
                    </td>
                </tr>
                <tr>
                    <td>
                        Password : 
                    </td>
                    <td>
                        <input type="password" name="txtPwd" id="txtPwd" placeholder="Enter your password.." value='<?php echo $loginCreds[1] ?>' required>
                    </td>
                </tr>
                <tr>
                    <td>
                        <input type="submit" name="btnLogin" value="Login">
                    </td>
                    <td>
                        <input type="submit" name="btnDelete" value="Delete Cookie">
                    </td>
                </tr>
            </table>
        </form>
        <?php
        if (isset($_POST['btnLogin'])) {
            $username = $_POST['txtName'];
            $password = $_POST['txtPwd'];

            if ($username == 'Admin' && $password == 'Admin123@123') {
                $_SESSION['username'] = $username;
                $_SESSION['password'] = $password;
                $str = $username.','.$password;
                setcookie('LoginCreds', $str, time()+3600*24, path:1);
                header("Location: Redirector.php");

//                    echo $_SESSION['username'];
//                    echo $_SESSION['password'];
            } else {
                echo 'Invalid Login Credentials! Please try again!';
            }
            
        }
        
        ?>

    </body>
</html>
