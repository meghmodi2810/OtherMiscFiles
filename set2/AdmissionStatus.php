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
        <h1 align="center"> Admission status </h1>
        <?php
        session_start();
        if (!isset($_SESSION['username'])) {
            header('Location: login.php');
        }

        if (!isset($_SESSION['percentage'])) {
            echo "We've detected you've come to this page without enroll, all values will be null";
            $_SESSION['percentage'] = 0;
            $_SESSION['name'] = 'null';
            $_SESSION['course'] = 'null';
            $_SESSION['stream'] = 'null';
            $status = 'null';
        } else {
            if ($_SESSION['course'] == 101) {
                if ($_SESSION['stream'] == 'Commerce' && $_SESSION['percentage'] >= 65) {
                    $status = 'Eligible';
                } elseif ($_SESSION['stream'] == 'Science' && $_SESSION['percentage'] >= 60) {
                    $status = 'Eligible';
                } else {
                    $status = 'Not eligible';
                }
            } else {
                die('Admission only for Integrated M.Sc.(IT)');
            }
        }
        ?>
        <table cellpadding="20px" border="1" align="center" style="font-size: 20px">
            <tr>
                <th>
                    Name : 
                </th>
                <td>
                    <?php echo $_SESSION['name']; ?>
                </td>
            </tr>
            <tr>
                <th>
                    course : 
                </th>
                <td>
                    <?php echo $_SESSION['course']; ?>
                </td>
            </tr>
            <tr>
                <th>
                    stream : 
                </th>
                <td>
                    <?php echo $_SESSION['stream']; ?>
                </td>
            </tr>
            <tr>
                <th>
                    eligibility : 
                </th>
                <td>
                    <?php echo $status; ?>
                </td>
            </tr>
        </table>
    </body>
</html>
