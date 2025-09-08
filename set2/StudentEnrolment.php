<!DOCTYPE html>
<!--
Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/EmptyPHPWebPage.php to edit this template
-->
<html>
    <head>
        <meta charset="UTF-8">
        <title></title>
    </head>
    <body>
        <?php
        session_start();
        if (!isset($_SESSION['username'])) {
            header('Location: login.php');
        }
        ?>
        <form action="" method="post">
            <table>
                <tr>
                    <th>
                        Student Name : 
                    </th>
                    <td>
                        <input type="text" name="txtName" placeholder="Enter your name here.." pattern="[A-Za-z ]+"  title='Only alphabets are allowed!' required>
                    </td>
                </tr>
                <tr>
                    <th>
                        Stream : 
                    </th>
                    <td>
                        <select name="txtStream">
                            <option selected disabled>--SELECT--</option>
                            <option>Science</option>
                            <option>Commerce</option>
                            <option>Arts</option>
                            <option>Diploma</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th>
                        HSC Percentage : 
                    </th>
                    <td>
                        <input type="number" name="txtPercentage" step="0.01" placeholder="Enter your percentage here.." min="0" max='100' required>
                    </td>
                </tr>
                <tr>
                    <th>
                        Course : 
                    </th>
                    <td>
                        <select name="txtCourse">
                            <option selected disabled>--SELECT--</option>
                            <option value="101">Integrated M.sc.(IT)</option>
                            <option value="102">BBA</option>
                            <option value="103">B. tech</option>
                            <option value="104">Integrated MCA</option>
                            <option value="105">B. com</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td>
                        <input type="submit" name="btnEnrol" value="Enroll">
                    </td>
                </tr>
                <tr>
                    <td>
                        <input type="submit" name="btnLogout" value="Logout">
                    </td>
                </tr>
            </table>
        </form>
        <?php
        if (isset($_POST['btnLogout'])) {
            session_destroy();
            header('Location: login.php');
        }
        if (isset($_POST['btnEnrol'])) {
            $_SESSION['name'] = $_POST['txtName'];
            $_SESSION['stream'] = $_POST['txtStream'];
            $_SESSION['course'] = $_POST['txtCourse'];
            $_SESSION['percentage'] = $_POST['txtPercentage'];

            header('Location: AdmissionStatus.php');
        }
        ?>
    </body>
</html>
