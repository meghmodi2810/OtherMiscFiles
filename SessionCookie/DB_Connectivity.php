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
            $connect = mysqli_connect('localhost','root','root','db_146');
            if(!$connect) {
                die('Error in database connection!');
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
                        <input type="submit" name="btnSignup" value="Sign up">
                    </td>
                </tr>
            </table>
        </form>
        <?php
            if(isset($_POST['btnSignup'])) {
                $username = $_POST['txtName'];
                $password = $_POST['txtPwd'];
                
                $Hashpassword = password_hash($password, PASSWORD_DEFAULT);
                $query = "INSERT INTO tblLoginCreds(Username, Password) VALUES('$username', '$Hashpassword')";
                
                $res = mysqli_query($connect, $query);
                echo $query;
                
            }
        ?>

    </body>
</html>
