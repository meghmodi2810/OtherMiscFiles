<?php

require_once 'db_connection.php';
$text = $_GET['text'];

$query = "SELECT * FROM feemaster JOIN hostelmaster "
        . "ON hostelmaster.StudentId = feemaster.StudentId";

if ($text === "") {
    $stmt = $conn->prepare($query);
} else {
    $query = $query . " WHERE hostelmaster.StudentName like ? "
            . "OR feemaster.MonthlyFees like ? "
            . "OR feemaster.Duration like ? "
            . "OR feemaster.TotalFees like ?";
    $liketext = "%" . $text . "%";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssss", $liketext, $liketext, $liketext, $liketext);
}
$stmt->execute();

$res = $stmt->get_result();

echo "<TABLE border=1 widht=100>";
echo "<TR>";
echo "<TD>StudentId</TD>";
echo "<TD>Student Name</TD>";
echo "<TD>Room Type</TD>";
echo "<TD>Student Monthly Fees</TD>";
echo "<TD>Duration of Hostel</TD>";
echo "<TD>Total Fees</TD>";
echo "<TR>";
while ($r = $res->fetch_assoc()) {
    echo "<TR>";
    echo "<TD>" . $r['StudentId'] . "</TD>";
    echo "<TD>" . $r['StudentName'] . "</TD>";
    echo "<TD>" . $r['RoomType'] . "</TD>";
    echo "<TD>" . $r['MonthlyFees'] . "</TD>";
    echo "<TD>" . $r['Duration'] . "</TD>";
    echo "<TD>" . $r['TotalFees'] . "</TD>";
    echo "</TR>";
}
echo "</TABLE>";
