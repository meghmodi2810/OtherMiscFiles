<?php
include_once 'db_connection.php';

$value = $_GET['text'];
$query = "SELECT * FROM feemaster "
            . "JOIN hostelmaster ON "
            . "hostelmaster.studentId = feemaster.studentId";
if($value === "") {
    $stmt = $conn->prepare($query);
} else {
    $query = $query . " WHERE hostelmaster.StudentName like ? "
            . "OR hostelmaster.RoomType like ? "
            . "OR feemaster.MonthlyFees like ? "
            . "OR feemaster.Duration like ? "
            . "OR feemaster.TotalFees like ? ";
    
    $stmt = $conn->prepare($query);
    $liketext = "%" . $value . "%";
    $stmt->bind_param("sssss", $liketext, $liketext, $liketext, $liketext, $liketext);
}

$stmt->execute();
$res = $stmt->get_result();

echo "<TABLE border=1>";
echo "<tr>";
    echo "<td>" . "StudentName" . "</td>";
    echo "<td>" . "RoomType" . "</td>";
    echo "<td>" . 'MonthlyFees' . "</td>";
    echo "<td>" . 'Duration' . "</td>";
    echo "<td>" . 'TotalFees' . "</td>";
    echo "</tr>";
while($r = $res->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . $r['StudentName'] . "</td>";
    echo "<td>" . $r['RoomType'] . "</td>";
    echo "<td>" . $r['MonthlyFees'] . "</td>";
    echo "<td>" . $r['Duration'] . "</td>";
    echo "<td>" . $r['TotalFees'] . "</td>";
    echo "</tr>";
}
echo "</TABLE>";
$stmt->close();
$conn->close();