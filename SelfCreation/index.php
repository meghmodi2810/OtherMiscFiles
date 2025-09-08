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
        include 'base.html';
        include 'connector.php';
        ?>

        <br><br>
        <fieldset style="height: 50%; width: 50%; margin: 0 auto;">
            <legend>Insert parking entries</legend>
        <form action="" method="POST">
            <table border="1" align="center" cellpadding="10px" style="font-size: 22px;">
                <tr>
                    <td>
                        <label for="txtLocation">Location</label>
                    </td>
                    <td>:</td>
                    <td>
                        <input type="text" id="txtLocation" name="txtLocation" size="30" placeholder="Enter parking lot location.." style="height: 30px; width: 200px;" required>
                    </td>
                </tr>
                <tr>
                    <td>
                        <label for="txtCapacity">Capacity</label>
                    </td>
                    <td>:</td>
                    <td>
                        <input type="number" id="txtCapacity" name="txtCapacity" min="10" max="1000" style="height: 30px; width: 200px;" placeholder="Enter capacity here.." required>
                    </td>
                </tr>
                <tr>
                    <td>
                        <label for="txtRate">Hourly Rate</label>
                    </td>
                    <td>:</td>
                    <td>
                        <input type="number" id="txtRate" name="txtRate" min="10" max="1000" step="0.01" style="height: 30px; width: 200px;" placeholder="Enter hourly rate here.." required>
                    </td>
                </tr>
                <tr>
                    <td colspan="3" align="center">
                        <input type="submit" name="btnInsert" value="Insert">
                    </td>
                </tr>
            </table>
        </form>
        </fieldset>
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
        }
        echo "<BR><BR>";
        // For Select query
        echo "<h1 align='center'style='background-color: lightblue; text-decoration: underline dashed;'>All items</h1>";
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
        // Close connection at the end
        if (isset($conn)) {
            $conn->close();
        }
        ?>
    </body>
</html>