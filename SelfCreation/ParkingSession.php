<!DOCTYPE html>
<!--
Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/EmptyPHPWebPage.php to edit this template
-->
<html>
    <head>
        <meta charset="UTF-8">
        <title></title>
    </head>
    <body>
        <?php
        include_once 'connector.php';
        include 'base.html';
        ?>
        <BR><BR>
        <fieldset style="height: 50%; width: 50%; margin: 0 auto;">
            <legend>Parking Sessions</legend>
            <form action="" method="post">
                <table border="1" align="center" cellpadding="10px" style="font-size: 22px;">
                    <tr>
                        <td>
                            <label for="lblLotLocation">Lot Location</label>
                        </td>
                        <td>:</td>
                        <td>
                            <select name="txtParkingLot" required style="height: 30px; width: 200px;">
                                <option disabled selected>--SELECT--</option>
                                <?php
                                $stmt = $conn->prepare("SELECT * FROM parking_lots");
                                $stmt->execute();
                                $res = $stmt->get_result();

                                while ($row = $res->fetch_assoc()) {
                                    echo "<option value='" . htmlspecialchars($row['lot_id']) . "'>" . htmlspecialchars($row['location']) . "</option>";
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
                                <option disabled selected>--SELECT--</option>
                                <?php
                                $stmt = $conn->prepare("SELECT * FROM vehicles");
                                $stmt->execute();
                                $res = $stmt->get_result();

                                while ($row = $res->fetch_assoc()) {
                                    echo "<option value='" . htmlspecialchars($row['vehicle_id']) . "'>" . htmlspecialchars($row['plate_number']) . "</option>";
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
                            <input type="date" name="txtDate" id="txtDate" style="height: 30px; width: 200px;" required>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label for="lblEntryTime">Entry Time</label>
                        </td>
                        <td>:</td>
                        <td>
                            <input type="time" name="txtEntryTime" id="txtEntryTime" style="height: 30px; width: 200px;" required>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label for="lblExitTime">Exit Time</label>
                        </td>
                        <td>:</td>
                        <td>
                            <input type="time" name="txtExitTime" id="txtExitTime" style="height: 30px; width: 200px;" required>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="3" align="center">
                            <input type="submit" name="btnInsert" value="Insert" style="height: 30px; width: 100px; font-size: 18px;">
                        </td>
                    </tr>
                </table>
            </form>
        </fieldset>
        <?php
        if (isset($_POST['btnInsert'])) {
            $lot_id = $_POST['txtParkingLot'];
            $vehicle_id = $_POST['txtVehicleNumber'];
            $date = $_POST['txtDate'];
            $entry_time = $_POST['txtEntryTime'];
            $exit_time = $_POST['txtExitTime'];

//                echo "<BR>".$lot_id."<BR>".$vehicle_id."<BR>".$date."<BR>".$entry_time."<BR>".$exit_time;
            $stmt = $conn->prepare("INSERT INTO parking_sessions(lot_id, vehicle_id, date, entry_time, exit_time) VALUES(?, ?, ?, ?, ?)");
            $stmt->bind_param("iisss", $lot_id, $vehicle_id, $date, $entry_time, $exit_time);

            if ($entry_time >= $exit_time) {
                echo "<p style='color:red;'>Error: Exit time must be later than entry time.</p>";
            } else {
                if ($res->num_rows > 0) {
                    echo "<p style='color:red;' align='center'>Error: This vehicle is already parked and cannot be added again!</p>";
                } else {
                    $stmt = $conn->prepare("INSERT INTO parking_sessions(lot_id, vehicle_id, date, entry_time, exit_time) VALUES(?, ?, ?, ?, ?)");
                    $stmt->bind_param("iisss", $lot_id, $vehicle_id, $date, $entry_time, $exit_time);
                    if ($stmt->execute()) {
                        echo "<p align='center' style='color:green;'>Parking session added successfully!</p>";
                    } else {
                        echo "<p align='center' style='color:red;'>Error inserting data: " . $stmt->error . "</p>";
                    }
                }
            }
        }
        // TO view the data and search it
        ?>
        <BR><BR>
        <fieldset style="height: 50%; width: 50%; margin: 0 auto;">
            <legend>Parking Sessions</legend>
            <form action="" method="POST">
                <table align="center" cellpadding="10px" style="font-size: 22px;">
                    <tr>
                        <td>
                            <input type="text" name="txtSearch" id="txtSearch" style="height: 30px; width: 1000px;" placeholder="Eg. BMW M3 Competition, Beach Side, etc..">
                        </td>
                        <td>
                            <input type="submit" name="btnSearch" id="btnSearch" value="Search" style="height: 30px; font-size: 18px;">
                        </td>
                    </tr>
                </table>
            </form>
            <?php
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

            $stmt = $conn->prepare($sql);
            $stmt->execute();
            $res = $stmt->get_result();

            echo "<table border='1' cellpadding='10px' align='center' style='font-size: 18px;'>";
            echo "<tr>";
            echo "<th>Location</th>";
            echo "<th>Plate Number</th>";
            echo "<th>Vehicle Model</th>";
            echo "<th>Date</th>";
            echo "<th>Entry Time</th>";
            echo "<th>Exit Time</th>";
            echo "<th style='width: 170px;'>Operations</th>";
            echo "</tr>";

            while ($row = $res->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($row['lot_location']) . "</td>";
                echo "<td>" . htmlspecialchars($row['plate_number']) . "</td>";
                echo "<td>" . htmlspecialchars($row['vehicle_model']) . "</td>";
                echo "<td>" . htmlspecialchars($row['session_date']) . "</td>";
                echo "<td>" . htmlspecialchars($row['entry_time']) . "</td>";
                echo "<td>" . htmlspecialchars($row['exit_time']) . "</td>";
                echo "<td><a href='edit.php?id=" . urlencode($row['session_id']) . "' style='padding: 10px;'>Edit</a><a href='delete.php?id=" . urlencode($row['session_id']) . "' style='padding: 10px;'>Delete</a><a href='details.php?id=" . urlencode($row['session_id']) . "' style='padding: 10px;'>Details</a>
          </td>";
                echo "</tr>";
            }
            echo "</table>";
            ?>
        </fieldset>
    </body>
</html>
