<!DOCTYPE html>
<!--
Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/EmptyPHPWebPage.php to edit this template
-->
<html>
    <head>
        <meta charset="UTF-8">
        <title>Master Form 1</title>
    </head>
    <body>
        <h1 align="center">Parking Lot Entries</h1>
        <?php
        include 'connector.php';
        ?>

        <br><br>

        <form action="" method="POST">
            <table border="1" align="center" cellpadding="10px" style="font-size: 18px;">
                <tr>
                    <td>
                        <label for="txtLocation">Location</label>
                    </td>
                    <td>:</td>
                    <td>
                        <input type="text" id="txtLocation" name="txtLocation" size="30" placeholder="Enter parking lot location.." required>
                    </td>
                </tr>
                <tr>
                    <td>
                        <label for="txtCapacity">Capacity</label>
                    </td>
                    <td>:</td>
                    <td>
                        <input type="number" id="txtCapacity" name="txtCapacity" min="10" max="1000" required>
                    </td>
                </tr>
                <tr>
                    <td>
                        <label for="txtRate">Hourly Rate</label>
                    </td>
                    <td>:</td>
                    <td>
                        <input type="number" id="txtRate" name="txtRate" min="10" max="1000" step="0.01" required>
                    </td>
                </tr>
                <tr>
                    <td colspan="3" align="center">
                        <input type="submit" name="btnInsert" value="Insert">
                    </td>
                </tr>
            </table>
        </form>

        <?php
        if (isset($_POST['btnInsert'])) {
            // For Insert query
            $location = $_POST['txtLocation'];
            $capacity = $_POST['txtCapacity'];
            $rate = $_POST['txtRate'];

            $stmt = $conn->prepare("INSERT INTO parking_lots(location, capacity, hourly_rate) VALUES(?, ?, ?)");
            $stmt->bind_param("sdd", $location, $capacity, $rate);

            if ($stmt->execute()) {
                echo "<p align='center' style='color: green;'>Record inserted successfully!</p>";
            } else {
                echo "<p align='center' style='color: red;'>Error: " . $stmt->error . "</p>";
            }
            
            // For Select query
            $stmt = $conn->prepare("SELECT * FROM parking_lots");
            $stmt->execute();
            $res = $stmt->get_result();

            echo "<table border='1' align='center' style='font-size: 18px;'>";
            echo "<tr>";
            echo "<th>Lot ID</th>";
            echo "<th>Location</th>";
            echo "<th>Capacity</th>";
            echo "<th>Hourly Rate</th>";
            echo "</tr>";

            while ($row = $res->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($row['lot_id']) . "</td>";
                echo "<td>" . htmlspecialchars($row['location']) . "</td>";
                echo "<td>" . htmlspecialchars($row['capacity']) . "</td>";
                echo "<td>" . htmlspecialchars($row['hourly_rate']) . "</td>";
                echo "</tr>";
            }
            echo "</table>";

            $stmt->close();
        }

        // Close connection at the end
        if (isset($conn)) {
            $conn->close();
        }
        ?>
    </body>
</html>