<?php
include_once 'connector.php';

$search = isset($_POST['query']) ? trim($_POST['query']) : "";

$sql = "SELECT 
            parking_sessions.session_id,
            parking_lots.location AS lot_location,
            vehicles.plate_number AS plate_number,
            vehicles.vehicle_model AS vehicle_model,
            parking_sessions.date AS session_date,
            parking_sessions.entry_time AS entry_time,
            parking_sessions.exit_time AS exit_time
        FROM parking_sessions
        JOIN vehicles ON vehicles.vehicle_id = parking_sessions.vehicle_id
        JOIN parking_lots ON parking_lots.lot_id = parking_sessions.lot_id";

if ($search !== "") {
    $sql .= " WHERE vehicles.plate_number LIKE ? 
              OR vehicles.vehicle_model LIKE ?
              OR parking_lots.location LIKE ?";
    $stmt = $conn->prepare($sql);
    $like = "%$search%";
    $stmt->bind_param("sss", $like, $like, $like);
} else {
    $stmt = $conn->prepare($sql);
}

$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows > 0) {
    while ($row = $res->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['lot_location'] . "</td>";
        echo "<td>" . $row['plate_number'] . "</td>";
        echo "<td>" . $row['vehicle_model'] . "</td>";
        echo "<td>" . $row['session_date'] . "</td>";
        echo "<td>" . $row['entry_time'] . "</td>";
        echo "<td>" . $row['exit_time'] . "</td>";
        echo "<td>
        <a href='edit.php?id=" . $row['session_id'] . "'>Edit</a> | 
        <a href='delete.php?id=" . $row['session_id'] . "'>Delete</a> | 
        <a href='Details.php?id=" . $row['session_id'] . "'>Details</a>
      </td>";
        echo "</tr>";
    }
} else {
    echo "<tr><td colspan='7' align='center'>No records found</td></tr>";
}
