<?php
require 'db.php';
session_start();

$query = 'select * from doctor';
$result = $conn->query($query);
?>
<html>
    <head>
        <meta charset="UTF-8">
        <title></title>
    </head>
    <body>
        <h1 align="center">patient-home</h1>
        <form method="post"><input type="submit" name="logout" value='Logout'></form>
        <h4>All the doctors</h4>
        <?php
        echo '<table border=1 cellpadding=10>'
        . '<tr>'
        . '<th>Id</th>'
        . '<th>Doctor Name</th>'
        . '<th>Specialist</th>'
        . '<th>Email</th>'
        . '</tr>';

        while ($row = $result->fetch_assoc()) {
            echo '<tr>'
            . '<td>' . $row['did'] . '</td>'
            . '<td>' . $row['dname'] . '</td>'
            . '<td>' . $row['specialist'] . '</td>'
            . '<td>' . $row['email'] . '</td>'
            . '<td><form method="post">'
            . '<input type="hidden" name="did" value="' . $row['did'] . '">'
            . '<input type = "submit" name = "book" value = "Book Appointment">'
            . '</form>'
            . '</td>'
            . '</tr>';
        }
        echo '</table>';

        if (isset($_POST['book'])) {
            $did = $_POST['did'];
            echo "<form method='post'>"
            . "Appointment-date:~<input type='date' name='adate' required>"
            . "<input type='hidden' name='did' value='$did'>"
            . "<input type='submit' name='sub-date' value='done'>"
            . "</form>";
        }
        if (isset($_POST['sub-date'])) {
            $adate = $_POST['adate'];
            $did = $_POST['did'];
            $user = $_SESSION['username'];
            $query2 = "select pid from patient where pname='$user'";
            $result2 = $conn->query($query2);
            $a = $result2->fetch_assoc();
            $pid = $a['pid'];
            $query = "insert into appointment(adate , did , pid) values(?,?,?)";
            $stmt = $conn->prepare($query);
            $stmt->bind_param('sii', $adate, $did, $pid);

            if ($stmt->execute()) {
                echo 'appointment req sent';
            } else {
                echo 'error';
            }
        }
        ?>
        <?php
        if (isset($_POST['logout'])) {
            session_destroy();
            header("Location:index.php");
            exit();
        }
        ?>
    </body>
</html>
