<?php
require_once 'db_connection.php';
$query = "SELECT * FROM accountmaster "
        . "JOIN loanmaster ON "
        . "accountmaster.AccNo = loanmaster.AccNo";
$text = $_GET['text'];
if($text === "") {
    $stmt = $conn->prepare($query);
} else {
    $liketext = "%" . $text . "%";
    $query = $query . " WHERE accountmaster.CustomerName like ? "
            . "OR loanmaster.LoanAmount like ? "
            . "OR loanmaster.No_of_inst like ? "
            . "OR loanmaster.LoanType like ? "
            . "OR loanmaster.NetAmount like ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sssss", $liketext, $liketext, $liketext, $liketext, $liketext);
}

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
while($r = $res->fetch_assoc()) {
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