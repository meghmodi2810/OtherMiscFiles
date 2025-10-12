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
    <body>
        <?php
            require_once 'db_connection.php';
        ?>
        <form action="" method="POST">
            Customer Name : 
            <select name="txtName" id="txtName" required>
                <option value="" selected disabled>--SELECT--</option>
                <?php
                    $stmt = $conn->prepare("SELECT CustomerName FROM accountmaster");
                    $stmt->execute();
                    
                    $res = $stmt->get_result();
                    while($r = $res->fetch_assoc()) {
                        echo "<option>" . $r['CustomerName'] . "</option>";
                    }
                    $stmt->close();
                ?>
            </select><BR><BR>
            
            Loan Amount :
            <input type="number" name="txtLoan" id="txtLoan" required><BR><BR>
            
            Number of installments : 
            <input type="number" name="txtInstallment" id="txtInstallment" required><BR><BR>
            
            Loan Type : 
            <select name="txtType" id="txtType" required>
                <option value=""  selected disabled>--SELECT--</option>
                <option>Car</option>
                <option>Home</option>
                <option>Other</option>
            </select><BR><br>
            
            <input type="submit" name="btnSubmit" id="btnSubmit" value="Submit">
        </form>
        <?php
            if(isset($_POST['btnSubmit'])) {
                $name = $_POST['txtName'];
                $amt = $_POST['txtLoan'];
                $installment = $_POST['txtInstallment'];
                $type = $_POST['txtType'];
                
                $stmt = $conn->prepare("SELECT AccNo, BranchType FROM accountmaster WHERE CustomerName = ?");
                $stmt->bind_param("s", $name);
                $stmt->execute();
                
                $res = $stmt->get_result();
                while($r = $res->fetch_assoc()) {
                    $accno = $r['AccNo'];
                    $branch = $r['BranchType'];
                    if($branch == 'City') {
                        $interest = 10;
                    } else {
                        $interest = 15;
                    }
                    
                    $net = $amt + ($amt * $interest/100);
                    
                    $stmt2 = $conn->prepare("INSERT INTO loanmaster(AccNo, LoanAmount, No_of_inst, LoanType, NetAmount) VALUES(?, ?, ?, ?, ?)");
                    $stmt2->bind_param("idisd", $accno, $amt, $installment, $type, $net);
                    if($stmt2->execute()) {
                        echo "Successful!";
                    } else {
                        echo "Failed!";
                    }
                    $stmt2->close();
                }
                $stmt->close();
                $conn->close();
                
                include 'ajax_search.php';
            }
        ?>
    </body>
</html>
