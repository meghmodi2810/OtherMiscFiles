<?php

require_once 'db_connect.php';
include_once 'base.php';

session_start();
if (!isset($_SESSION['name'])) {
    header("Location: index.php");
}

$stmt = $conn->prepare("SELECT * FROM review WHERE USERNAME = ?");
$stmt->bind_param("s", $_SESSION['name']);

$stmt->execute();
$res = $stmt->get_result();

echo "<TABLE border=1 cellpadding=20px>";
echo "<TR>";
echo "<TH>Restaurant</TH>";
echo "<TH>Rating</TH>";
echo "<TH>Description</TH>";
echo "</TR>";
while ($row = $res->fetch_assoc()) {
    echo "<TR>";
    echo "<TD>" . $row['RESTAURANT'] . "</TD>";
    echo "<TD>" . $row['RATING'] . "</TD>";
    echo "<td>" . (($row['DESCRIPTION'] === "") ? "-" : $row['DESCRIPTION']) . "</td>";
    echo "</TR>";
}
echo "</TABLE>";

$stmt->close();
$conn->close();
