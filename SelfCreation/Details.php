<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <title>Parking Session Details</title>
    </head>
    <body>
        <?php
            include 'connector.php';
            include 'base.html';
            
            $id = $_GET['id'];

            $sql = "SELECT parking_sessions.*, vehicles.owner_name, vehicles.plate_number, vehicles.vehicle_model,
                           parking_lots.location, parking_lots.hourly_rate
                    FROM parking_sessions
                    JOIN vehicles ON vehicles.vehicle_id = parking_sessions.vehicle_id
                    JOIN parking_lots ON parking_lots.lot_id = parking_sessions.lot_id
                    WHERE session_id = ?";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $res = $stmt->get_result();
           
            echo "<br><br>";
            if ($row = $res->fetch_assoc()) {
                
                // Calculate total charges
                $entry = new DateTime($row['entry_time']);
                $exit = new DateTime($row['exit_time']);
                $interval = $entry->diff($exit);
                $hours = $interval->h + ($interval->days * 24); // total hours
                if ($interval->i > 0) {
                    $hours += 1; // round up if there are extra minutes
                }
                $total_charge = $hours * $row['hourly_rate'];

                echo "<table border='1' cellpadding='10px' align='center' style='font-size: 18px;'>";
                echo "<tr><th>Name</th><td>" . htmlspecialchars($row['owner_name']) . "</td></tr>";
                echo "<tr><th>Vehicle Number</th><td>" . htmlspecialchars($row['plate_number']) . "</td></tr>";
                echo "<tr><th>Vehicle Model</th><td>" . htmlspecialchars($row['vehicle_model']) . "</td></tr>";
                echo "<tr><th>Location</th><td>" . htmlspecialchars($row['location']) . "</td></tr>";
                echo "<tr><th>Entry Time</th><td>" . htmlspecialchars($row['entry_time']) . "</td></tr>";
                echo "<tr><th>Exit Time</th><td>" . htmlspecialchars($row['exit_time']) . "</td></tr>";
                echo "<tr><th>Total Charges</th><td>₹" . htmlspecialchars($total_charge) . "</td></tr>";
                echo "</table>";
            } else {
                echo "<p style='color:red; text-align:center;'>No record found for this session ID.</p>";
            }
        ?>
    </body>
</html>
