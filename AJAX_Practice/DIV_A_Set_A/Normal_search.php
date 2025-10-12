<!DOCTYPE html>

<html>
    <head>
        <meta charset="UTF-8">
        <title>Search</title>
    </head>
    <body>
        <?php
            require_once 'db_connection.php';
        ?>
        <form action="" method="POST">
            Search Using field : 
            <select name="txtSearch" id="txtSearch">
                <option value="">--SELECT--</option>
                <option>City</option>
                <option>Village</option>
            </select>
            <input type="submit" name="btnSubmit" id="txtSubmit">
        </form>

        <?php
        if (isset($_POST['btnSubmit'])) {
            $search = $_POST['txtSearch'];
            $query = "SELECT * FROM accountmaster "
                    . "JOIN loanmaster ON "
                    . "accountmaster.AccNo = loanmaster.AccNo "
                    . "WHERE accountmaster.BranchType = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("s", $search);

            $stmt->execute();
            $res = $stmt->get_result();

            echo "<TABLE border=1 style='width: 100; font-size: 22px;'>";
            echo "<TR>";
            echo "<TD>Customer Name</TD>";
            echo "<TD>Branch Type</TD>";
            echo "<TD>Loan Amount</TD>";
            echo "<TD>Number of installment</TD>";
            echo "<TD>Loan Type</TD>";
            echo "<TD>Net Amount</TD>";
            echo "</TR>";
            while ($r = $res->fetch_assoc()) {
                echo "<TR>";
                echo "<TD>" . $r['CustomerName'] . "</TD>";
                echo "<TD>" . $r['BranchType'] . "</TD>";
                echo "<TD>" . $r['LoanAmount'] . "</TD>";
                echo "<TD>" . $r['No_of_inst'] . "</TD>";
                echo "<TD>" . $r['LoanType'] . "</TD>";
                echo "<TD>" . $r['NetAmount'] . "</TD>";
                echo "</TR>";
            }
            $stmt->close();
            $conn->close();
        }
        ?>
    </body>
</html>
