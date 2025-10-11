<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/EmptyPHP.php to edit this template
 */

$conn = new mysqli("localhost", "root", "6640", "db_selfcreation");

$stmt = $conn->prepare("SELECT * FROM vehicles WHERE vehicle_id = ?");
$stmt->bind_param("i", $_GET['id']);
$stmt->execute();
$res = $stmt->get_result();
echo "<table border='1'>";
while ($row = $res->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . $row['vehicle_id'] . "</td>";
    echo "<td>" . $row['plate_number'] . "</td>";
    echo "<td>" . $row['owner_name'] . "</td>";
    echo "<td>" . $row['vehicle_model'] . "</td>";
    echo "<td>" . $row['vehicle_type'] . "</td>";
    echo "<tr>";
}
echo "</table>";
