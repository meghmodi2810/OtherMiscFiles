<?php
require_once 'db_connect.php';
$str = $_GET['str'];
$query = "SELECT * FROM restaurant";

if($str === "") {
    $stmt = $conn->prepare($query);
} else {
    $query .= " WHERE NAME like ? OR"
            . " LOCATION like ? OR "
            . " CAPACITY like ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sss", $likestr, $slikestr, $likestr);
    $likestr = "%" . $str . "%";
}
$stmt->execute();
$res = $stmt->get_result();

echo "<TABLE border=1 cellpadding=20px>";
echo "<TR>";
echo "<TH>Name</TH>";
echo "<TH>Location</TH>";
echo "<TH>Capacity</TH>";
echo "<TH>Operations</TH>";
echo "</TR>";
while ($row = $res->fetch_assoc()) {
    echo "<TR>";
    echo "<TD>" . $row['NAME'] . "</TD>";
    echo "<TD>" . $row['LOCATION'] . "</TD>";
    echo "<TD>" . $row['CAPACITY'] . "</TD>";
    echo "<td>"
    . "<a href='edit.php?name={$row['NAME']}'>Edit</a> "
    . " | "
    . "<a href='delete.php?name={$row['NAME']}'>Delete</a>"
    . "</td>";
    echo "</TR>";
}
echo "</TABLE>";

$stmt->close();
$conn->close();
