<!DOCTYPE html>
<!--
Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/EmptyPHPWebPage.php to edit this template
-->
<html>
    <head>
        <meta charset="UTF-8">
        <title>Login Page</title>
    </head>
    <body>
        <?php
            session_start();
            require_once 'db_connect.php';
            if(isset($_SESSION['name'])) {
                header("Location: listing.php");
            }
        ?>
        <form action="" method="POST">
            <table border="1" cellpadding="20px">
                <tr>
                    <td>
                        <label for="lblUsername">Username : </label>
                    </td>
                    <td>
                        <input type="text" style="height: 30px; width: 200px;" name="txtUsername" id="txtUsername" placeholder="Enter your username.." required>
                    </td>
                </tr>
                <tr>
                    <td>
                        <label for="lblPassword">Password : </label>
                    </td>
                    <td>
                        <input type="password" style="height: 30px; width: 200px;" name="txtPassword" id="txtPassword" placeholder="Enter your password.." required>
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                        <input type="submit" name="btnLogin" value="Login">
                    </td>
                </tr>
                <tr>
                    <td colspan="2" align="center">
                        Not a user? <a href="Registration.php">Registration</a>
                    </td>
                </tr>
            </table>
            <?php 
                if(isset($_POST['btnLogin'])) {
                    $stmt = $conn->prepare("SELECT * FROM user");
                    $stmt->execute();
                    
                    $res = $stmt->get_result();
                    while($row = $res->fetch_assoc()) {
                        if($row['USERNAME'] === $_POST['txtUsername'] && $row['PASSWORD'] === $_POST['txtPassword']) {
                            $_SESSION['name'] = $row['USERNAME'];
                            header("Location: listing.php");
                        }
                    }
                    echo 'Invalid Username/password!';
                    $stmt->close();
                    $conn->close();
                }
            ?>
        </form>
    </body>
</html>
