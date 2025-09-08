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
            if(!isset($_SESSION['username'])) {
                header("Location: login.php");
            }
            echo 'WELCOME DEAR, ',$_SESSION['username'];
        ?>
        
        <form method="post" action="">
            <table border='1' cellpadding='20px'>
                <tr>
                    <td>
                        Employee Code : 
                    </td>
                    <td>
                        <input type="number" name="txtEmpCode" id="txtEmpCode" placeholder="Enter your Employee Code here.." required>
                    </td>
                </tr>
                <tr>
                    <td>
                        Name : 
                    </td>
                    <td>
                        <input type="text" name="txtName" id="txtName" placeholder="Enter your name.." required>
                    </td>
                </tr>
                <tr>
                    <td>
                        Department : 
                    </td>
                    <td>
                        <select name="txtDepartment">
                            <option selected disabled>--SELECT--</option>
                            <option>BMIIT</option>
                            <option>SRIMCA</option>
                            <option>MPC</option>
                            <option>SRCP</option>
                            <option>CGPIT</option>
                            <option>BVP</option>
                            <option>AMTICS</option>
                            <option>Others</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td>
                        Designation : 
                    </td>
                    <td>
                        <input type="text" name="txtDesignation" id="txtDesignation" placeholder="Enter your designation.." required>
                    </td>
                </tr>
                <tr>
                    <td>
                        Basic Salary : 
                    </td>
                    <td>
                        <input type="number" name="txtSalary" id="txtSalary" placeholder="Enter your Salary.." required>
                    </td>
                </tr>
                <tr>
                    <td colspan='2' align='center'>
                        <input type="submit" name="btnSubmit" value="Sumbit">
                    </td>
                </tr>
            </table>
        </form>
        <a href='viewSalary.php'>Click here to go to ViewSalary.php</a>
        <form action="" method="post">
            <input type="submit" name="btnLogout" value="Logout">
        </form>
        <?php
            if(isset($_POST['btnLogout'])) {
                session_destroy();
                header('Location: login.php');
            }
            if(isset($_POST['btnSubmit'])) {
                $name = $_POST['txtName'];
                $salary = $_POST['txtSalary'];
                
                $_SESSION['name'] = $name;
                $_SESSION['salary'] = $salary;
                
                header("Location: ViewSalary.php");
            }
        ?>
    </body>
</html>
