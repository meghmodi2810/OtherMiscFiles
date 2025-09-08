<!DOCTYPE html>
<!--
Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
Click nbfs://nbhost/SystemFileSystem/Templates/Project/PHP/PHPProject.php to edit this template
-->
<html>
    <head>
        <meta charset="UTF-8">
        <title>Session - Cookie management</title>
    </head>
    <body>
        <?php
            session_start();
            if(isset($_SESSION['usr'])) {
                header('Location: Welcome.php');
            }
        ?>
        <form action="" method="POST">
            <table>
                <tr>
                    <td>
                        <label for="lblUsername">Username : </label>
                    </td>
                    <td>
                        <input type="text" name="txtUsername" id="txtUsername" placeholder="Enter your username here.." required>
                    </td>
                </tr>
                <tr>
                    <td>
                        <label for="lblPassword">Password : </label>
                    </td>
                    <td>
                        <input type="password" name="txtPassword" id="txtPassword" placeholder="Enter your password here.." required>
                    </td>
                </tr>
                <tr>
                    <td>
                        <input type="submit" value="Login" name="btnLogin" >
                    </td>
                </tr>
            </table>
        </form>
        <?php
            if(isset($_POST['btnLogin'])) {
                $usr = $_POST['txtUsername'];
                $pwd = $_POST['txtPassword'];
                
                if($usr == 'Admin' && $pwd == 'admin123') {
                    $_SESSION['usr'] = $usr;
                    $_SESSION['pwd'] = $pwd;
                    
                    header('Location: Welcome.php');
                } else {
                    echo 'Invalid login credentials!';
                }
            }
        ?>
    </body>
</html>
