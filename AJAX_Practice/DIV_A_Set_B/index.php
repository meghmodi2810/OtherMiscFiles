<!DOCTYPE html>
<!--
Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/EmptyPHPWebPage.php to edit this template
-->
<html>
    <head>
        <meta charset="UTF-8">
        <title>AJAX</title>
    </head>
    <body>
        
        <?php
            include_once 'db_connection.php';
        ?>
        <form action="" method="POST">
            Student Name : 
            <select name="txtName">
                <option value="" disabled selected required>--SELECT--</option>
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
            <input type="number" name="txtDuration" min="1" max="12" required><BR><BR>
            
            <input type="submit" id="btnSubmit" name="btnSubmit" value="Submit">
        </form>
        <?php
            if(isset($_POST['btnSubmit'])) {
                $name = $_POST['txtName'];
                
               $query = "SELECT StudentId, RoomType FROM hostelmaster WHERE StudentName = ?";

                
                $stmt = $conn->prepare($query);
                $stmt->bind_param("s", $name);
                
                $stmt->execute();
                $res = $stmt->get_result();
                
                while($r = $res->fetch_assoc()) {
                    $sid = $r['StudentId'];
                    $roomtype = $r['RoomType'];
                    
                    if($roomtype == "AC") {
                        $price = 1500;
                    } else {
                        $price = 1000;
                    }
                    
                    $duration = $_POST['txtDuration'];
                    $total_price = $price * $duration;
                    $query = "INSERT INTO feemaster(StudentId, MonthlyFees, Duration, TotalFees) "
                            . "VALUES(?, ?, ?, ?)";
                    
                    $stmt = $conn->prepare($query);
                    $stmt->bind_param("idid", $sid, $price, $duration, $total_price);
                    
                    if($stmt->execute()) {
                        echo "Data inserted!";
                    } else {
                        echo "Error while insertion: " . $stmt->error;
                    }
                }
            }
            
            include 'AJAX_Search.php';
            include 'Search_Normal.php';
        ?>
    </body>
</html>
