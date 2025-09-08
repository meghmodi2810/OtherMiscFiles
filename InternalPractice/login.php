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
        $mysqli = new mysqli("localhost", "root", "6640", "db_phpdatabase");

        if ($mysqli->connect_error) {
            die("Connection error! Please check your connection!");
        }
        ?>
        <form action="" method="post">
            <table>
                <tr>
                    <th>
                        <label for="lblEnrollment">EnrollmentNumber : </label>
                    </th>
                    <td>
                        <input type="number" name="numEnro" id="numEnro">
                    </td>
                </tr>
                <tr>
                    <th>
                        <label for="lblPassword">Password : </label>
                    </th>
                    <td>
                        <input type="password" name="txtPassword" id="txtPassword">
                    </td>
                </tr>
                <tr>
                    <td>
                        <input type="submit" value="Login" name="btnLogin">
                    </td>
                </tr>
                <tr>
                    <td>
                        Don't Have an account?&nbsp;
                        <a href="registration.php">Register</a>
                    </td>
                </tr>
            </table>
        </form>
        <?php
        if (isset($_POST['btnRegister'])) {
            
        }
        ?>
    </body>
</html>