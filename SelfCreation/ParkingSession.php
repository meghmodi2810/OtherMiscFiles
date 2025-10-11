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
            $_SESSION['entry_time'] = $entry_time;
            $exit_time = $_POST['txtExitTime'];
            $_SESSION['exit_time'] = $exit_time;

            // 1. Check time validity
            if ($entry_time >= $exit_time) {
                echo "<p style='color:red;'>Error: Exit time must be later than entry time.</p>";
            } else {
                // 2. Check if this vehicle is already parked on the same date
                $check = $conn->prepare("SELECT * FROM parking_sessions WHERE vehicle_id = ? AND date = ?");
                $check->bind_param("is", $vehicle_id, $date);
                $check->execute();
                $checkRes = $check->get_result();

                if ($checkRes->num_rows > 0) {
                    echo "<p style='color:red;' align='center'>Error: This vehicle is already parked and cannot be added again!</p>";
                } else {
                    // 3. Insert new session
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
        <fieldset style="width: 90%; margin: 0 auto;">
            <legend>Parking Details</legend>
            <input type="text" id="txtSearch" name="txtSearch" 
                   placeholder="Eg. Mercedes, Beach, BMW M3...." 
                   style="height:30px; width:100%; font-size:16px; margin-bottom:10px;"
                   onkeyup="searchData(this.value)">

            <table border="1" cellpadding="18" align="center" style="font-size:18px; width:100%;">
                <thead>
                    <tr>
                        <th>Location</th>
                        <th>Plate Number</th>
                        <th>Vehicle Model</th>
                        <th>Date</th>
                        <th>Entry Time</th>
                        <th>Exit Time</th>
                        <th>Operations</th>
                    </tr>
                </thead>
                <tbody id="tableData">
                </tbody>
            </table>
        </fieldset>

        <script>
            function searchData(query = "") {
                const tableData = document.getElementById("tableData");
                const xhr = new XMLHttpRequest();
                xhr.open("POST", "search.php", true);
                xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                xhr.onload = function () {
                    if (xhr.status === 200) {
                        tableData.innerHTML = xhr.responseText;
                    }
                };
                xhr.send("query=" + encodeURIComponent(query));
            }

            // load all records on page load
            window.onload = function () {
                searchData();
            };
        </script>
    </body>
</html>
