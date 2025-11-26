<?php

require_once 'db_connection.php';

$str = $_GET['str'];
$likestr = "%$str%";
$query = "SELECT hostelmaster.StudentId, "
        . " hostelmaster.StudentName, "
        . " feemaster.MonthlyFees, "
        . " feemaster.Duration, "
        . " feemaster.TotalFees "
        . "FROM feemaster JOIN hostelmaster "
        . "ON feemaster.StudentId = hostelmaster.StudentId";


if($str === "") {
    $stmt = $conn->prepare($query);
} else {
    $query .= " WHERE feemaster.StudentId like ? OR "
            . "feemaster.MonthlyFees like ? OR "
            . "feemaster.Duration like ? OR "
            . "feemaster.TotalFees like ? OR "
            . "hostelmaster.StudentName like ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sdids", $likestr, $likestr, $likestr, $likestr, $likestr);
}

$stmt->execute();
$res = $stmt->get_result();

echo "<TABLE border=1 cellpadding=20px>";
echo "<TR>";
echo "<TD>Student ID</TD>";
echo "<TD>Student Name</TD>";
echo "<TD>Monthly Fees</TD>";
echo "<TD>Duration</TD>";
echo "<TD>Total Fees</TD>";
echo "</TR>";

while($row = $res->fetch_assoc()) {
    echo "<TR>";
    echo "<TD>" . $row['StudentId'] . "</TD>";
    echo "<TD>" . $row['StudentName'] . "</TD>";
    echo "<TD>" . $row['MonthlyFees'] . "</TD>";
    echo "<TD>" . $row['Duration'] . "</TD>";
    echo "<TD>" . $row['TotalFees'] . "</TD>";
    echo "</TR>";
}

$stmt->close();
$conn->close();