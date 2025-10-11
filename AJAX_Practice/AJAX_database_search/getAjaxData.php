<?php
include_once 'db_connection.php';

$text = isset($_GET['text']) ? trim($_GET['text']) : "";

if($_GET['text'] === "") {
    $query = "SELECT * FROM vehicles";
    $stmt = $conn->prepare($query);
} else {
    $query = "SELECT * FROM vehicles "
            . "WHERE plate_number like ? "
            . "OR owner_name like ? "
            . "OR vehicle_model like ? "
            . "OR vehicle_type like ?";
    
    $stmt = $conn->prepare($query);
    $liketext = "%" . $text . "%";
    $stmt->bind_param("ssss", $liketext, $liketext, $liketext, $liketext);
}

$stmt->execute();
$res = $stmt->get_result();

if($res->num_rows > 0) {
    echo "<table border=1>
            <tr>
                <th>Plate Number</th>
                <th>Owner Name</th>
                <th>Vehicle Model</th>
                <th>Vehicle Type</th>
            </tr>";
    while ($row = $res->fetch_assoc()) {
        echo "<tr>
                <td>{$row['plate_number']}</td>
                <td>{$row['owner_name']}</td>
                <td>{$row['vehicle_model']}</td>
                <td>{$row['vehicle_type']}</td>
              </tr>";
    }
    echo "</table>";
} else {
    echo "<p> No data found! </p>";
}

$stmt->close();
$conn->close();