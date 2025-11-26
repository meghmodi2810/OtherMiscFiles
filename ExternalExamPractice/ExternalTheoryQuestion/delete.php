<?php
require_once 'db_connect.php';

$stmt = $conn->prepare("DELETE FROM restaurant WHERE NAME = ?");
$stmt->bind_param("s", $_GET['name']);

if($stmt->execute()) {
    echo "Success!";
} else {
    echo "Failure!";
}
header("Location: listing.php");
