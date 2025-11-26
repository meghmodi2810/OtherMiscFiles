<!DOCTYPE html>
<!--
Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/EmptyPHPWebPage.php to edit this template
-->
<html>
    <head>
        <meta charset="UTF-8">
        <title>Index page</title>
    </head>
    <body>
        <?php
        session_start();
        require_once 'db_connection.php';
        ?>
        <form action="" method="POST">
            <table border="1">
                <tr>
                    <td>
                        <label for="lblStudentName">Student Name : 
                    </td>
                    <td>
                        <select name="txtName" required>
                            <option value="" disabled selected>--SELECT--</option>
                            <?php
                            $stmt = $conn->prepare("SELECT * FROM hostelmaster");
                            $stmt->execute();

                            $res = $stmt->get_result();
                            while ($row = $res->fetch_assoc()) {
                                echo "<OPTION value='{$row['StudentId']}'>" . $row['StudentName'] . "</OPTION>";
                            }
                            $stmt->close();
                            ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td>
                        <label for="lblDuration">Duration(months) : </label>
                    </td>
                    <td>
                        <input type="number" name="txtDuration" style="width: 200px;" min="1" max="12" id="txtDuration" placeholder="Enter duration here.." required>
                    </td>
                </tr>
                <tr>
                    <td colspan="2" align="center">
                        <input type="submit" name="btnSubmit" value="Submit">
                    </td>
                </tr>
            </table>
        </form>
        <?php
            if(isset($_POST['btnSubmit'])) {
                $sid = $_POST['txtName'];
                $duration = $_POST['txtDuration'];
                
                $stmt = $conn->prepare("SELECT * FROM hostelmaster WHERE StudentId = ?");
                $stmt->bind_param("s", $sid);
                $stmt->execute();
                $res = $stmt->get_result();
                
                while($row = $res->fetch_assoc()) {
                    $monthlyfees = ($row['RoomType'] == 'AC') ? 1500 : 1000;
                    $totalfees = $monthlyfees * $duration;
                    $stmt2 = $conn->prepare("INSERT INTO feemaster(StudentId, MonthlyFees, Duration, TotalFees) VALUES(?, ?, ?, ?)");
                    $stmt2->bind_param("sdid", $sid, $monthlyfees, $duration, $totalfees);
                    
                    if($stmt2->execute()) {
                        echo "Inserted successfully!";
                    } else {
                        echo "Failed to insert record!";
                    }
                    
                    $stmt2->close();
                }
                $stmt->close();
                $conn->close();
            }
        ?>
    </body>
</html>
