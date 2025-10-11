<?php
require 'db.php';
session_start();

$dname=$_SESSION['username'];
$q = "select did from doctor where dname='$dname'";
$res = $conn->query($q);
$a = $res->fetch_assoc();
$did = $a['did'];

$query = "select p.pname as Patient_Name, a.adate as Appointment_Date , p.disease as Disease, p.email as Patient_Email "
        . "from patient p join appointment a "
        . "on p.pid=a.pid "
        . "where did=$did ";
$result=$conn->query($query);

?>
<html>
    <head>
        <meta charset="UTF-8">
        <title></title>
    </head>
    <body>
        <h1 align='center'>doctor-home</h1>
        <form method="post"><input type="submit" name="logout" value='Logout'></form>
        <h4>All the Appointments</h4>
        <?php
        echo '<table border=1 cellpadding=10>'
        . '<tr>'
        . '<th>Patient Name</th>'
        . '<th>Appointment Date</th>'
        . '<th>Disease</th>'
        . '<th>Email</th>'
        . '</tr>';

        while ($row = $result->fetch_assoc()) {
            echo '<tr>'
            . '<td>' . $row['Patient_Name'] . '</td>'
            . '<td>' . $row['Appointment_Date'] . '</td>'
            . '<td>' . $row['Disease'] . '</td>'
            . '<td>' . $row['Patient_Email'] . '</td>'
            . '<td><form method="post">'
            . '<input type="hidden" name="did" value="' . $did . '">'
            . '<input type = "submit" name = "accept" value = "Accept">'
            . '</form>'
            . '</td>'
            . '</tr>';
        }
        echo '</table>';
        ?>
        <?php
        if(isset($_POST['accept'])){
            $did=$_POST['did'];
            $q="update appointment set status=1 where did=$did";
            if($conn->query($q)){
                echo 'update successfully';
            }else{
                echo 'error';
            }
            
        }
        if (isset($_POST['logout'])) {
            session_destroy();
            header("Location:index.php");
            exit();
        }
        ?>
    </body>
</html>
