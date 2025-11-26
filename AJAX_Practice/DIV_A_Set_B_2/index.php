<!DOCTYPE html>
<!--
Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/EmptyPHPWebPage.php to edit this template
-->
<html>
    <head>
        <meta charset="UTF-8">
        <title>Index Page</title>
    </head>
    <?php
        require_once 'db_connection.php';
    ?>
    <body>
        <form action="" method="POST">
            Student Name : 
            <select name="txtName" id="txtName" required>
                <option value="" disabled selected>--SELECT--</option>
                <?php
                    $stmt = $conn->prepare("SELECT StudentName FROM hostelmaster");
                    $stmt->execute();
                    
                    $res = $stmt->get_result();
                    while($r = $res->fetch_assoc()) {
                        echo "<OPTION>" . $r['StudentName'] . "</OPTION>";
                    }
                    
                    $stmt->close();
                ?>
            </select><BR><BR>
            
            Duration : 
            <input type="number" name="txtDuration" id="txtDuration" min="1" max="12" style="width:122px;" title="Enter months only!" placeholder="Enter the duration..." required><BR><BR>
            
            <input type="submit" name="btnSubmit" id="btnSubmit">
        </form>
        <?php
            if(isset($_POST['btnSubmit'])) {
                $name = $_POST['txtName'];
                $duration = $_POST['txtDuration'];
                
                $query = "SELECT StudentId FROM hostelmaster WHERE StudentName = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("s", $name);
                
                $stmt->execute();
                
                $res = $stmt->get_result();
                while($r = $res->fetch_assoc()) {
                    $monthlyfees = ($r['RoomType'] === "AC") ? 1500 : 1000;
                    $studentId = $r['StudentId'];
                    $stmt2 = $stmt->prepare("INSERT INTO feemaster(StudentId, MonthlyFees, Duration, TotalFees) VALUES(?, ?, ?, ?)");
                    $totalfee = $monthlyfees * $duration;
                    $stmt->bind_param("idid", $studentId, $monthlyfees, $duration, $totalfee);
                    
                    if($stmt->execute()) {
                        echo "Insertion Successful!";
                    } else {
                        echo "Insertion Failed!";
                    }
                }
                $stmt->close();
                $conn->close();
            }
        ?>
    </body>
</html>
