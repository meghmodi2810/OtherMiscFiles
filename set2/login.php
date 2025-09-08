<!DOCTYPE html>
<!--
Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
Click nbfs://nbhost/SystemFileSystem/Templates/Project/PHP/PHPProject.php to edit this template
-->
<html>
    <head>
        <meta charset="UTF-8">
        <title>Login Page</title>
    </head>
    <body>
        <?php
        session_start();
        if (isset($_SESSION['username'])) {
            header('StudentEnrolment.php');
        }
        ?>
        <form method="post" action="">
            <table>
                <tr>
                    <td>
                        Username : 
                    </td>
                    <td>
                        <input type="text" name="txtName" id="txtName" placeholder="Enter your username.." required>
                    </td>
                </tr>
                <tr>
                    <td>
                        Password : 
                    </td>
                    <td>
                        <input type="password" name="txtPwd" id="txtPwd" placeholder="Enter your password.." required>
                    </td>
                </tr>
                <tr>
                    <td>
                        <input type="submit" name="btnLogin" value="Login">
                    </td>
                </tr>
            </table>
        </form>
        <?php
        if (isset($_POST['btnLogin'])) {
            $username = $_POST['txtName'];
            $password = $_POST['txtPwd'];

            if ($username == 'Admin' && $password == 'Admin123') {
                $_SESSION['username'] = $username;
                header('Location: StudentEnrolment.php');
            } else {
                echo "Invalid Login Credentials! Try again!";
            }
        }
        ?>
    </body>
</html>
