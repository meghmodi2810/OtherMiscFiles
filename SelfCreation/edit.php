<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <title>Edit Parking Session</title>
    </head>
    <body>
        <?php
        include 'connector.php';
        include 'base.html';
        
        $session_id = $_GET['id'];
        
        // Handle form submission
        if (isset($_POST['btnUpdate'])) {
            $lot_id = $_POST['txtParkingLot'];
            $vehicle_id = $_POST['txtVehicleNumber'];
            $date = $_POST['txtDate'];
            $entry_time = $_POST['txtEntryTime'];
            $exit_time = $_POST['txtExitTime'];
            
            // Validate time
            if ($entry_time >= $exit_time) {
                echo "<p style='color:red; text-align:center;'>Error: Exit time must be later than entry time.</p>";
            } else {
                // Check if this vehicle is already parked on the same date (excluding current session)
                $check = $conn->prepare("SELECT * FROM parking_sessions WHERE vehicle_id = ? AND date = ? AND session_id != ?");
                $check->bind_param("isi", $vehicle_id, $date, $session_id);
                $check->execute();
                $checkRes = $check->get_result();
                
                if ($checkRes->num_rows > 0) {
                    echo "<p style='color:red; text-align:center;'>Error: This vehicle is already parked on this date!</p>";
                } else {
                    // Update the parking session
                    $stmt = $conn->prepare("UPDATE parking_sessions SET lot_id = ?, vehicle_id = ?, date = ?, entry_time = ?, exit_time = ? WHERE session_id = ?");
                    $stmt->bind_param("iisssi", $lot_id, $vehicle_id, $date, $entry_time, $exit_time, $session_id);
                    
                    if ($stmt->execute()) {
                        echo "<p style='color:green; text-align:center;'>Parking session updated successfully!</p>";
                        echo "<script>setTimeout(function(){ window.location.href = 'ParkingSession.php'; }, 1500);</script>";
                    } else {
                        echo "<p style='color:red; text-align:center;'>Error updating record: " . $stmt->error . "</p>";
                    }
                }
            }
        }
        
        // Fetch current session data
        $stmt = $conn->prepare("SELECT * FROM parking_sessions WHERE session_id = ?");
        $stmt->bind_param("i", $session_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
        ?>
        
        <br><br>
        <fieldset style="height: 50%; width: 50%; margin: 0 auto;">
            <legend>Edit Parking Session</legend>
            <form action="" method="post">
                <table border="1" align="center" cellpadding="10px" style="font-size: 22px;">
                    <tr>
                        <td>
                            <label for="lblLotLocation">Lot Location</label>
                        </td>
                        <td>:</td>
                        <td>
                            <select name="txtParkingLot" required style="height: 30px; width: 200px;">
                                <option disabled>--SELECT--</option>
                                <?php
                                $lotStmt = $conn->prepare("SELECT * FROM parking_lots");
                                $lotStmt->execute();
                                $lotRes = $lotStmt->get_result();

                                while ($lotRow = $lotRes->fetch_assoc()) {
                                    $selected = ($lotRow['lot_id'] == $row['lot_id']) ? 'selected' : '';
                                    echo "<option value='" . htmlspecialchars($lotRow['lot_id']) . "' $selected>" . htmlspecialchars($lotRow['location']) . "</option>";
                                }
                                ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label for="lblVehicleNumber">Vehicle Number</label>
                        </td>
                        <td>:</td>
                        <td>
                            <select name="txtVehicleNumber" required style="height: 30px; width: 200px;">
                                <option disabled>--SELECT--</option>
                                <?php
                                $vehicleStmt = $conn->prepare("SELECT * FROM vehicles");
                                $vehicleStmt->execute();
                                $vehicleRes = $vehicleStmt->get_result();

                                while ($vehicleRow = $vehicleRes->fetch_assoc()) {
                                    $selected = ($vehicleRow['vehicle_id'] == $row['vehicle_id']) ? 'selected' : '';
                                    echo "<option value='" . htmlspecialchars($vehicleRow['vehicle_id']) . "' $selected>" . htmlspecialchars($vehicleRow['plate_number']) . "</option>";
                                }
                                ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label for="lblEntryDate">Entry Date</label>
                        </td>
                        <td>:</td>
                        <td>
                            <input type="date" name="txtDate" id="txtDate" value="<?php echo htmlspecialchars($row['date']); ?>" style="height: 30px; width: 200px;" required>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label for="lblEntryTime">Entry Time</label>
                        </td>
                        <td>:</td>
                        <td>
                            <input type="time" name="txtEntryTime" id="txtEntryTime" value="<?php echo htmlspecialchars($row['entry_time']); ?>" style="height: 30px; width: 200px;" required>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label for="lblExitTime">Exit Time</label>
                        </td>
                        <td>:</td>
                        <td>
                            <input type="time" name="txtExitTime" id="txtExitTime" value="<?php echo htmlspecialchars($row['exit_time']); ?>" style="height: 30px; width: 200px;" required>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="3" align="center">
                            <input type="submit" name="btnUpdate" value="Update" style="height: 30px; width: 100px; font-size: 18px; margin-right: 10px;">
                            <input type="button" value="Cancel" onclick="window.location.href='ParkingSession.php'" style="height: 30px; width: 100px; font-size: 18px;">
                        </td>
                    </tr>
                </table>
            </form>
        </fieldset>
        
        <?php
        } else {
            echo "<p style='color:red; text-align:center;'>No parking session found with this ID.</p>";
            echo "<p style='text-align:center;'><a href='ParkingSession.php'>Go back to Parking Sessions</a></p>";
        }
        
        // Close connection
        if (isset($conn)) {
            $conn->close();
        }
        ?>
    </body>
</html>